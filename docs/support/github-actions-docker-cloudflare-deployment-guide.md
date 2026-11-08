# Panduan Standar Deployment: GitHub Actions, GHCR, Docker Compose & Cloudflare Tunnel

Dokumen ini adalah panduan standar dan *blueprint* deployment aplikasi berbasis **Docker**, **GitHub Actions (CI/CD)**, **GitHub Container Registry (GHCR)**, dan **Cloudflare Zero Trust Tunnel**. Metode ini dirancang agar dapat digunakan kembali (*reusable*) untuk berbagai proyek web mendatang (Laravel, Node.js, Python, Go, dll).

---

## 1. Perancangan & Arsitektur (Architecture Overview)

### Topologi Alur Deployment
```text
[ Developer Machine ]
        │
        │ 1. Git Push (main)
        ▼
[ GitHub Repository ]
        │
        │ 2. Trigger Workflow (.github/workflows/ci.yml)
        ▼
[ GitHub Actions Runner ]
        ├─► Linting & Unit/Feature Tests
        ├─► Build Frontend Assets (Vite/React/Webpack)
        └─► Build Multi-Stage Docker Image & Push
        │
        ▼
[ GitHub Container Registry (ghcr.io) ]
        │
        │ 3. Docker Pull Image (Pre-built, Immutable)
        ▼
[ Server / Production Machine ]
  ┌─────────────────────────────────────────────────────────┐
  │ Docker Network: app_network                             │
  │                                                         │
  │  [ web-app ] <──── (HTTP Internal: 80) ──── [ tunnel ]  │
  │      │                                          ▲       │
  │      ▼ (Internal DB: 3306)                      │       │
  │  [ mysql ]                                      │       │
  └─────────────────────────────────────────────────┼───────┘
                                                    │
                                4. Secure Outbound  │ (QUIC / TLS)
                                   Tunnel           │ (No Public IP/No Port Fwd)
                                                    ▼
                                       [ Cloudflare Edge Network ]
                                                    ▲
                                                    │ 5. HTTPS Public Access
                                           [ Internet / Users ]
```

### Keunggulan Metode Ini:
1. **Zero Port Forwarding & Zero Open Ports**: Server tidak perlu membuka port 80/443 ke internet publik, sehingga terlindung dari *port scanning* dan serangan langsung ke IP server.
2. **Bekerja di Mana Saja**: Berjalan di VPS, homelab, server sekolah, bahkan koneksi rumahan di balik ISP CGNAT (tanpa IP publik statis).
3. **Gratis SSL/TLS & Proteksi DDoS**: Sertifikat HTTPS dikelola otomatis oleh Cloudflare.
4. **Build Tidak Membebani Server**: Kompilasi aset (Node/PHP/Go) dilakukan di server GitHub Actions, server deploy hanya men-download image hasil build yang ringan dan siap jalan.
5. **Reproducible & Rollback Mudah**: Setiap rilis memiliki image versi tag SHA dan `latest` yang konsisten.

---

## 2. Persiapan (Prerequisites & Credentials)

### A. Persiapan Cloudflare Zero Trust Tunnel
1. Buka dashboard [Cloudflare Zero Trust](https://one.dash.cloudflare.com/) > **Networks** > **Tunnels**.
2. Klik **Create a Tunnel** > Pilih tipe **Cloudflared**.
3. Beri nama tunnel (misal: `project-production-tunnel`).
4. Pada halaman *Install and run a connector*, pilih tab **Docker**.
5. Salin token tunnel yang ada di bagian akhir perintah:
   ```bash
   docker run cloudflare/cloudflared:latest tunnel --no-autoupdate run --token <TOKEN_TUNNEL_ANDA>
   ```
   Simpan `<TOKEN_TUNNEL_ANDA>` untuk diletakkan di file `.env` server.
6. Pada tab **Public Hostname**:
   - **Subdomain & Domain**: Masukkan domain publik Anda (misal `app.domainanda.com`).
   - **Type**: `HTTP`
   - **URL**: `nama-service-web:80` (contoh: `sia-web:80`).
     > **PENTING**: Nama hostname ini harus sama persis dengan nama service atau network alias container web di `docker-compose.prod.yml`.

---

### B. Persiapan Autentikasi GitHub Container Registry (GHCR)
Agar server production dapat men-download image dari GHCR milik akun/organisasi Anda:

#### Opsi 1: Menggunakan Personal Access Token (Classic) — Untuk Private Image
1. Buka [GitHub Settings > Personal Access Tokens (Classic)](https://github.com/settings/tokens).
2. Klik **Generate new token (classic)**.
3. Beri nama pada kolom *Note* (misal: `server-deploy-token`).
4. Centang permission:
   - `[x] read:packages` *(Download packages from GitHub Package Registry)*.
   - `[x] repo` *(Opsional, centang jika server juga butuh clone repositori private)*.
5. Klik **Generate token** dan salin token (`ghp_...`).
6. Di server deploy, jalankan perintah login sekali saja:
   ```bash
   echo "ghp_TOKEN_ANDA" | docker login ghcr.io -u USERNAME_GITHUB --password-stdin
   ```

#### Opsi 2: Mengubah Package Menjadi Public (Tanpa Login & Tanpa Token)
Jika image tidak mengandung kode rahasia (rahasia disimpan di file `.env`, bukan di dalam image):
1. Buka profil GitHub > Tab **Packages** (`https://github.com/USERNAME?tab=packages`).
2. Klik package aplikasi Anda.
3. Masuk ke **Package settings** (kanan bawah).
4. Di bagian **Danger Zone**, klik **Change visibility** > pilih **Public**.
5. Server Anda kini dapat melakukan `docker pull` secara langsung tanpa perlu `docker login`.

---

## 3. Berkas Konfigurasi Proyek (Templates)

### 1. `.dockerignore`
Pastikan file rahasia, dependensi lokal, dan cache tidak ikut ter-copy ke dalam image Docker.

```dockerignore
.git
.github
node_modules
npm-debug.log
yarn-error.log
.env
.env.*
!.env.example
storage/*.key
storage/app/*
!storage/app/.gitignore
storage/framework/cache/*
!storage/framework/cache/.gitignore
storage/framework/sessions/*
!storage/framework/sessions/.gitignore
storage/framework/views/*
!storage/framework/views/.gitignore
storage/logs/*
!storage/logs/.gitignore
tests
```

---

### 2. `Dockerfile` (Multi-Stage Production Build)
Contoh untuk aplikasi Laravel + Inertia/React (dapat disesuaikan untuk framework lain):

```dockerfile
# Stage 1: Build Aset Frontend
FROM node:22-alpine AS frontend-builder
WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# Stage 2: Runtime Server (FrankenPHP / PHP 8.4)
FROM dunglas/frankenphp:1-php8.4-bookworm AS base

# Install extension PHP yang dibutuhkan
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

ENV SERVER_NAME=":80"
ENV CADDY_GLOBAL_OPTIONS="auto_https off"

# Copy source code aplikasi
COPY . /app

# Copy aset frontend dari Stage 1
COPY --from=frontend-builder /build/public/build /app/public/build

# Install dependensi PHP production
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
```

---

### 3. `docker-entrypoint.sh`
Script inisialisasi yang dijalankan saat container pertama kali hidup:

```bash
#!/bin/sh
set -e

# Buat direktori penyimpanan Laravel jika belum ada
mkdir -p /app/storage/framework/cache/data \
         /app/storage/framework/sessions \
         /app/storage/framework/views \
         /app/storage/logs \
         /app/storage/app \
         /app/bootstrap/cache

# Sesuaikan kepemilikan folder ke user web
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data /app/storage /app/bootstrap/cache 2>/dev/null || true
fi

# Migrasi otomatis jika diaktifkan di .env
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
fi

exec "$@"
```

---

### 4. `docker-compose.prod.yml`
File orchestration untuk production di server deploy. Image diarahkan langsung ke GHCR:

```yaml
services:
  sia-web:
    image: ghcr.io/USERNAME_GITHUB/NAMA_REPO:latest
    container_name: sia-web
    restart: unless-stopped
    env_file:
      - .env
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=${DB_DATABASE:-app_db}
      - DB_USERNAME=app_user
      - DB_PASSWORD=${DB_PASSWORD:-secret_db_password}
      - AUTO_MIGRATE=true
    ports:
      # Opsional: Hanya bind ke localhost server jika butuh curl lokal
      - "127.0.0.1:${APP_PORT:-8082}:80"
    volumes:
      - app_storage_data:/app/storage/app
    networks:
      app_network:
        aliases:
          - sia-web
    depends_on:
      mysql:
        condition: service_healthy

  mysql:
    image: mysql:8.4
    container_name: sia_mysql
    restart: unless-stopped
    command: --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci
    environment:
      MYSQL_DATABASE: ${DB_DATABASE:-app_db}
      MYSQL_USER: app_user
      MYSQL_PASSWORD: ${DB_PASSWORD:-secret_db_password}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-root_secret_password}
    ports:
      # Opsional: Akses lokal untuk backup database host
      - "127.0.0.1:${FORWARD_DB_PORT:-3308}:3306"
    volumes:
      - app_mysql_data:/var/lib/mysql
    networks:
      - app_network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-p${DB_ROOT_PASSWORD:-root_secret_password}"]
      interval: 5s
      timeout: 5s
      retries: 10

  queue:
    image: ghcr.io/USERNAME_GITHUB/NAMA_REPO:latest
    container_name: sia_queue
    restart: unless-stopped
    command: ["php", "artisan", "queue:work", "--tries=3", "--sleep=3"]
    env_file:
      - .env
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=${DB_DATABASE:-app_db}
      - DB_USERNAME=app_user
      - DB_PASSWORD=${DB_PASSWORD:-secret_db_password}
    volumes:
      - app_storage_data:/app/storage/app
    networks:
      - app_network
    depends_on:
      sia-web:
        condition: service_started
      mysql:
        condition: service_healthy

  tunnel:
    image: cloudflare/cloudflared:latest
    container_name: sia_tunnel
    restart: unless-stopped
    command: tunnel run
    environment:
      - TUNNEL_TOKEN=${CLOUDFLARE_TUNNEL_TOKEN}
    networks:
      - app_network
    depends_on:
      - sia-web

networks:
  app_network:
    name: app_network
    driver: bridge

volumes:
  app_mysql_data:
    driver: local
  app_storage_data:
    driver: local
```

---

## 4. Pipeline GitHub Actions (`.github/workflows/ci.yml`)

Workflow ini bertugas menjalankan pengujian kualitas (Lint & Test) dan secara otomatis membangun image Docker lalu mengunggahnya ke GHCR hanya ketika push dilakukan ke branch `main`.

```yaml
name: CI & Docker Publish

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
  workflow_dispatch:

jobs:
  backend-lint-test:
    name: Backend Lint & Tests
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_DATABASE: testing_db
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, bcmath, gd, zip
          coverage: none

      - name: Cache Composer Dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.json') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Prepare Environment File
        run: touch .env

      - name: Install Composer Dependencies
        run: composer install --prefer-dist --no-progress --no-interaction

      - name: Check Code Style (Pint)
        run: ./vendor/bin/pint --test

      - name: Execute Tests
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing_db
          DB_USERNAME: root
          DB_PASSWORD: password
          APP_ENV: testing
          APP_KEY: base64:4e14D1zG0hV05i9pQ6V3Qd4u0xZ+v1k3uL8x2y1z0a4=
        run: php artisan test

  frontend-build:
    name: Frontend Lint & Build
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: 'npm'

      - name: Install Frontend Dependencies
        run: npm ci

      - name: Frontend Code Check (Prettier)
        run: npm run format:check

      - name: Frontend Typecheck & Build
        run: npm run build

  docker-publish:
    name: Build & Publish Docker Image to GHCR
    needs: [backend-lint-test, frontend-build]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata (tags, labels)
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/${{ github.repository }}
          tags: |
            type=raw,value=latest
            type=sha,format=short

      - name: Build and Push Docker Image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./Dockerfile
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

> **Catatan Penting Security**: `${{ secrets.GITHUB_TOKEN }}` otomatis disediakan oleh GitHub Actions tanpa perlu membuat token manual di Secrets repositori! Syaratnya cukup tambahkan baris `permissions: packages: write`.

---

## 5. Prosedur Deploy di Server Baru

Jika Anda memulai di server baru atau mesin baru:

1. **Siapkan Direktori Proyek**:
   ```bash
   mkdir -p /opt/my-app && cd /opt/my-app
   ```
2. **Siapkan File `.env`**:
   Isi konfigurasi produksi:
   ```env
   APP_NAME="Nama Aplikasi"
   APP_ENV=production
   APP_KEY=base64:GENERATE_DENGAN_ARTISAN_KEY_GENERATE
   APP_DEBUG=false
   APP_URL=https://app.domainanda.com

   CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoi...TOKEN_TUNNEL_DARI_CLOUDFLARE...
   DB_DATABASE=app_db
   DB_PASSWORD=secret_db_password
   DB_ROOT_PASSWORD=root_secret_password
   ```
3. **Simpan File `docker-compose.prod.yml`** di direktori yang sama.
4. **Login ke GHCR** (Hanya jika package berstatus Private):
   ```bash
   echo "ghp_TOKEN_ANDA" | docker login ghcr.io -u USERNAME_GITHUB --password-stdin
   ```
5. **Jalankan Stack**:
   ```bash
   docker-compose -f docker-compose.prod.yml pull
   docker-compose -f docker-compose.prod.yml up -d
   ```
6. **Inisialisasi Data Awal (Jika Diperlukan)**:
   ```bash
   docker exec sia-web php artisan db:seed --force
   ```

Aplikasi Anda kini sudah aktif dan dapat diakses dari domain Cloudflare secara instan!

---

## 6. Troubleshooting & Pelajaran Berharga (Pitfalls)

| Masalah | Penyebab | Solusi |
|---|---|---|
| **Tidak ada pilihan `read:packages` saat buat token** | Anda berada di menu *Fine-grained PAT (Beta)* yang belum mendukung scope standar package personal. | Gunakan menu **Developer Settings > Personal access tokens > Tokens (classic)**, centang `read:packages`. Atau jadikan package Anda **Public**. |
| **`MYSQL_USER="root"` Error pada MySQL 8.4** | Pada image resmi MySQL, variabel `MYSQL_USER` hanya untuk membuat regular user dan akan error fatal jika diisi "root". | Jangan gunakan variabel `MYSQL_USER: ${DB_USERNAME}` jika `.env` berisi `DB_USERNAME=root`. Buat nama user khusus seperti `MYSQL_USER: app_user`. |
| **Kredensial tidak valid setelah migrasi sukses** | Migrasi (`migrate`) hanya membuat tabel kosong. Tabel `users` belum memiliki baris data. | Jalankan `docker exec sia-web php artisan db:seed --force` untuk mengisi akun admin/demo awal. |
| **Error `network carries label but has active endpoints`** | Ada container lama yang masih terhubung ke network Docker dengan nama yang sama. | Hentikan container lama dengan `docker stop <nama_container_lama>` sebelum menjalankan `docker-compose up -d`. |
| **Cloudflare Tunnel Error 502 / Bad Gateway** | Target URL di Cloudflare salah (misal diarahkan ke `localhost:80` alih-alih nama service Docker). | Di Cloudflare Tunnel Dashboard, arahkan URL ke `sia-web:80` (nama service di compose network), bukan `localhost`. |
| **GitHub Actions gagal pada tahap Prettier / Pint** | Format spasi, baris kosong di akhir file (EOF), atau tanda petik tidak sesuai standar linter. | Jalankan `./vendor/bin/pint` dan `npx prettier --write <path>` sebelum commit ke branch `main`. |

