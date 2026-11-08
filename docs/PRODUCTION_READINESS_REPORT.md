# Production Readiness Report — SIA MI ALGHIVARI

**Revision:** Milestone 5 (Commit Final Rilis Demo Portofolio)  
**Environment:** Container Demo Terisolasi (Docker Compose) & Lingkungan Uji CI/CD  
**Decision:** **RELEASE APPROVED FOR PORTFOLIO DEMO**  
**Status Gate:** Seluruh gate operasional terverifikasi dengan bukti aktual (188 tests passed).

---

## 1. Matriks Kesiapan Release Gate

| Gate | Sumber / Bukti Aktual | Status | Catatan Verifikasi |
| --- | --- | --- | --- |
| **Engineering & QA** | `tests/` (188 tests passed, 976 assertions), Pint style check pass, Prettier check pass, Vite production build clean. | **PASS** | Pengujian menyeluruh unit, master data, absensi, penilaian, rapor, kenaikan kelas, dan audit log. |
| **Security & Threat Review** | `tests/Feature/Audit/AuditLogTest.php`, `tests/Feature/Guardian/ChildReportCardTest.php`, `tests/Feature/Policy/` | **PASS** | Proteksi IDOR wali murid, zero-leak draf rapor, append-only immutability pada log audit, redaksi data sensitif `[REDACTED]`, decoupled principal approval. |
| **Load & Error Budgets** | Optimistic concurrency control (`version`, `expected_version`, `roster_version`), transaksi DB atomik, query berpaginasi (25 baris/halaman). | **PASS** | Mencegah race condition pada pembaharuan rapor, mutasi kenaikan rombel, dan sesi absensi. |
| **Observability & Alerts** | `AuditLogController` (HTTP-28, SCR-11), `NotificationController` (F-11), Diff Viewer before vs after. | **PASS** | Visibilitas penuh aktivitas sistem tanpa ekspos data kredensial; notifikasi persisten di inbox pengguna. |
| **Database & Seeder** | `database/seeders/DemoPortfolioSeeder.php`, MySQL 8.4 LTS, 16 migrations bersih. | **PASS** | Data sintetis realistis mencakup 48 siswa, 24 rombel, 16 pengguna, rapor published, dan progres usulan lintas status. |
| **Deployment & Container** | `docker-compose.demo.yml`, Multi-stage `Dockerfile`, `.env.demo.example` | **PASS** | Pengemasan mandiri frontend dan backend dalam kontainer terisolasi FrankenPHP + MySQL 8.4. |

---

## 2. Kredensial Akun Demo Portofolio

Semua akun demo di bawah ini telah dikonfigurasi dengan kata sandi default: **`Password123!`** dan bypass first-login setup (`must_change_password: false`):

| Peran (Role) | Nama Akun | Username | Email | Fitur Utama yang Dapat Diuji |
| --- | --- | --- | --- | --- |
| **Master Admin** | Administrator Utama | `admin` | `admin@alghivari.sch.id` | Manajemen pengguna & hak akses, pengaturan global timezone/sekolah, audit trail sistem. |
| **Academic Admin** | Staf Tata Usaha & Akademik | `akademik` | `akademik@alghivari.sch.id` | Rombel & jadwal, draf usulan kenaikan kelas (HTTP-24), eksekusi penerapan mutasi (HTTP-27), audit log. |
| **Operator Data Entry** | Operator Data Entri | `dataentry` | `dataentry@alghivari.sch.id` | Input siswa & penautan wali, entri absensi & nilai terbatas berdasarkan delegasi sah guru. |
| **Kepala Madrasah (Principal)**| Drs. H. Miftah Faridl, M.Ag. | `kepala.madrasah`| `kamad@alghivari.sch.id` | Persetujuan & penolakan eksklusif usulan kenaikan/kelulusan siswa (HTTP-25 & HTTP-26). |
| **Guru & Wali Kelas** | Ustadz Ahmad Fauzi, S.Pd.I. | `guru.ahmad` | `ahmad.fauzi@alghivari.sch.id`| Absensi mapel, entri nilai, pratinjau rapor rombel binaan, publikasi rapor semester resmi. |
| **Guru Mapel** | Ustadzah Siti Maryam, S.Pd. | `guru.siti` | `siti.maryam@alghivari.sch.id` | Pengelolaan penilaian & absensi mata pelajaran Matematika. |
| **Wali Murid** | Hasan Basri, S.T. | `wali.hasan` | `hasan.basri@example.test` | Portal perkembangan belajar anak kandung, unduh rapor PDF resmi published (zero-leak). |
| **Wali Murid** | Dewi Sartika, S.E. | `wali.dewi` | `dewi.sartika@example.test` | Portal pemantauan akademik anak binaan. |

---

## 3. Petunjuk Menjalankan Demo Portofolio (Runbook)

### Metode A: Menjalankan Menggunakan Docker Compose (Direkomendasikan)
Stack demo terisolasi telah disiapkan agar langsung siap dioperasikan tanpa konfigurasi rumit:

```bash
# 1. Salin template konfigurasi demo
cp .env.demo.example .env.demo

# 2. Jalankan stack kontainer demo (App, MySQL 8.4, Queue Worker, Reverb)
docker compose -f docker-compose.demo.yml --env-file .env.demo up -d

# 3. Buka peramban web pada:
http://localhost:8000
```
*Catatan:* Kontainer secara otomatis menjalankan migrasi basis data dan menyemai data sintetis realistis saat pertama kali dinyalakan (`DEMO_AUTO_SEED=true`).

### Metode B: Menjalankan Secara Lokal (Host Environment)
```bash
# 1. Instal dependensi backend & frontend
composer install
npm install

# 2. Siapkan file konfigurasi environment
cp .env.example .env
php artisan key:generate

# 3. Jalankan migrasi dan seeder portofolio sintetis
php artisan migrate:fresh
php artisan db:seed --class=DemoPortfolioSeeder

# 4. Bangun aset frontend
npm run build

# 5. Jalankan server lokal
php artisan serve
```

---

## 4. Pernyataan Persetujuan Rilis

Berdasarkan kepatuhan menyeluruh terhadap seluruh kriteria penerimaan fungsional, bukti lulus 100% dari 188 automated tests, integritas migrasi basis data MySQL 8.4, dan kesiapan berkas kontainer demo:

**Keputusan Akhir: SISTEM SIAP DIPRESENTASIKAN UNTUK DEMO PORTOFOLIO.**
