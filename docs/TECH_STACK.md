# Technical Stack - SIA MI Alghivari

Tanggal kajian: 28 Oktober 2026; keputusan bentuk aplikasi dikonfirmasi 29 Oktober 2026. Status: **blueprint; satu aplikasi Laravel + Inertia + React/TypeScript, Reverb/Echo dan alur Docker Compose + GitHub Actions/GHCR/CD dikonfirmasi pada tingkat rancangan. Baseline versi inti dikonfirmasi 29 Oktober 2026; belum implementasi atau penguncian seluruh paket/patch**.

Pemilik mengonfirmasi tujuan frontend React/TypeScript dan penggunaan Laravel Reverb + Laravel Echo untuk notifikasi realtime. Alur demo yang disepakati: dev -> PR/main -> GitHub Actions CI/build -> GHCR -> self-hosted deployment runner -> Docker Compose. Cloudflare Tunnel menjadi jalur publik yang direncanakan. Bentuk satu aplikasi Laravel + Inertia + React/TypeScript dikonfirmasi pemilik 29 Oktober 2026. MySQL dipilih pemilik 29 Oktober 2026. Pemilik juga menyetujui baseline versi inti: Laravel 13.x, PHP 8.5.x, Inertia 3.x, React 19.x, TypeScript mengikuti rilis starter kit yang dipilih, Tailwind CSS 4.x, shadcn/ui seperlunya, serta MySQL 8.4 LTS/InnoDB. Paket tambahan, versi paket persis, image/digest dan detail konfigurasi masih mengikuti status masing-masing. Runtime host Windows memakai Docker Desktop + WSL2; HTTP app memakai FrankenPHP classic berbasis Debian, disepakati pemilik 29 Oktober 2026 (D-16). Renderer PDF rapor memakai Dompdf melalui barryvdh/laravel-dompdf 3.1.x, disepakati pemilik 29 Oktober 2026 (D-17); susunan rapor ringkas Q7.1 disepakati pada D-18; aturan aturan Q7.1-Q7.5 disepakati; detail visual/render belum final. Instruksi tetap: mantapkan blueprint, jangan implementasi dahulu.

## 1. Bentuk aplikasi

Keputusan pemilik, 29 Oktober 2026: satu aplikasi Laravel dengan React/TypeScript melalui Inertia. Laravel menangani routing, sesi, policy, validasi, transaksi, kalkulasi, audit, notifikasi dan file. React menangani antarmuka dalam aplikasi yang sama. Tidak perlu backend REST dan proyek frontend terpisah untuk kebutuhan saat ini.

Batas fitur, struktur kode, kontrak request/file/job dan aturan dependensi dirancang pada [ARCHITECTURE.md](ARCHITECTURE.md). Alasan opsi, risiko dan catatan keputusan ARC-001 ada pada [ARCHITECTURE_REVIEW.md](ARCHITECTURE_REVIEW.md); bentuk aplikasi pada ARC-001 telah diterima, sedangkan detail struktur/dependensi masih rancangan; belum ada implementasi.

Rancangan transport/route/payload ada pada [API_CONTRACT.md](API_CONTRACT.md), mengikuti sesi dan Inertia dengan JSON/file/channel pendukung di aplikasi yang sama. Tidak menjadikan nama API_CONTRACT sebagai keputusan membuat REST API publik terpisah.

Ini pilihan yang mempertahankan penggunaan React nyata sekaligus membatasi pekerjaan integrasi. Aplikasi utamanya terautentikasi; belum ada kebutuhan SEO yang mengharuskan SSR. Target awal browser modern pada desktop/ponsel, koneksi online. Versi minimum browser dan sasaran kapasitas belum ditentukan atau diuji.

## 2. Teknologi: baseline disepakati dan rekomendasi tambahan

| Lapisan | Pilihan / rekomendasi | Kegunaan dan batas |
| --- | --- | --- |
| Backend | Laravel 13.x | Fondasi aplikasi, validasi, transaksi, policy dan migration. Pakai patch kompatibel yang dipastikan ketika implementasi. |
| OTP password | fouladgar/laravel-otp dipilih D-25; rilis dikaji v6.2.0 | Purpose password_reset/profile_password_change dan UI React sendiri; bukan OTP login umum. Versi persis/lockfile saat bootstrap, runtime belum diuji. |
| Storage OTP/sesi | MySQL otp_tokens hash + database sessions Laravel | Repository aplikasi/hash/atomic consume dan generation sesi; vendor plaintext/validate-revoke perlu adaptasi. |
| Email akses/password | Laravel Mail/Notifications + database queue setelah commit | URL biasa/username/password sementara, OTP dan peringatan aman; provider SMTP/API belum dipilih. Payload secret queue/failed jobs terlindungi; dev/CI fake. |
| Runtime PHP | PHP 8.5.x | Baseline PHP disepakati pemilik 29 Oktober 2026; Laravel 13 mendukung PHP 8.3-8.5. Ketersediaan hosting/ekstensi dan seluruh paket tetap diverifikasi. |
| Penghubung UI/server | Inertia 3.x | Routing/controller Laravel menghasilkan halaman React; tidak memerlukan kontrak REST terpisah untuk setiap layar. |
| Frontend | React 19.x + TypeScript | Sesuai tujuan portofolio; tabel penilaian, form kelompok, pemantauan wali dan preview rapor merupakan fitur React yang nyata. Versi TS mengikuti rilis starter kit yang dipilih. |
| Styling | Tailwind CSS 4.x | Layout responsif dan token visual yang konsisten. Identitas produk tetap dirancang khusus. |
| Komponen UI | shadcn/ui yang diperlukan | Form, dialog, menu dan kontrol yang dapat disesuaikan; tidak memasukkan seluruh katalog atau tema dashboard generik. |
| Database | MySQL 8.4 LTS, InnoDB, utf8mb4 | Relasi akademik, constraint, transaksi dan rekap; satu engine sejak pengembangan sampai target deployment bila tersedia. Bukan klaim bahwa 8.4 adalah LTS terbaru. |
| Akses data | Eloquent + query builder | Query terparameterisasi, scope policy dan transaksi; query builder/bulk tetap melewati action dan audit. |
| Login | Autentikasi sesi Laravel/Fortify dari starter kit | Akun dikelola sesuai blueprint; registrasi publik dinonaktifkan. Hak role dan objek bukan bersumber dari React. |
| Otorisasi | Role tetap + Laravel Policies | Mendukung role rangkap, guru/mapel, homeroom_assignments, hubungan wali-anak dan delegasi; tanpa permission builder atau bypass Master global. |
| Kalkulasi nilai | PHP sebagai sumber kebenaran; kandidat brick/math | BigRational/BigDecimal membantu presisi rata-rata dan bobot tanpa mengandalkan float JavaScript. Q7.3 menetapkan pembulatan akhir dua desimal half-up tanpa pembulatan antara; pilihan paket/representasi internal dan bukti implementasinya belum final. |
| Rapor PDF | barryvdh/laravel-dompdf 3.1.x, disepakati pada D-17 | HTML/Blade + CSS cetak sederhana dari snapshot final. Bukan tangkapan layar React dan tidak memakai data operasional terbaru untuk menulis ulang rapor lama. |
| Penyimpanan | Laravel Filesystem, storage privat | Rapor dan foto profil diakses melalui route berizin. Demo memakai volume persisten privat; backup terpisah tetap diperlukan. |
| Inbox notifikasi | Tabel notifications proyek | Sumber riwayat, penerima, read-state dan jumlah belum dibaca; disimpan bersama transaksi domain dan audit. |
| Pengiriman realtime | Laravel Reverb + Laravel Broadcasting | WebSocket melalui private channel per pengguna; broadcast setelah commit, payload minimal dan penerima diverifikasi server. Dipilih pemilik. |
| Penerima frontend | Laravel Echo pada React/TypeScript | Menerima sinyal perubahan dan mengambil ulang inbox/count berizin dari server. Tidak menjadikan event socket sumber data utama. |
| Antrean broadcast | Laravel database queue + worker | Memproses broadcast dengan retry terbatas; memakai database utama tanpa Redis pada rancangan satu instance Reverb. Tabel jobs/failed_jobs adalah infrastruktur framework. |
| Pengujian | PHPUnit, TypeScript check, Playwright untuk alur inti | Tes domain/HTTP/database dan browser menurut risiko nyata; tidak menulis tes setiap komponen tampilan kecil. |
| Kualitas kode | Pint + Larastan; tooling frontend starter kit | Formatting PHP, analisis statis dan pemeriksaan TS/TSX. Tool formatter frontend mengikuti konfigurasi riil, bukan asumsi skrip. |
| Dependensi/build | Composer 2 + npm, Node.js LTS, tooling Vite bawaan rilis starter kit | Commit lockfile. Starter kit yang diperiksa memakai Vite+; pilih rilis kompatibel dan baca skripnya sebelum menentukan perintah CI. |
| Container dan orkestrasi | Docker Desktop + WSL2 pada Windows, Docker Compose | D-16 disepakati pemilik 29 Oktober 2026; menjalankan Linux containers. Layanan app, mysql, queue dan reverb; kompatibilitas perangkat belum diperiksa. |
| HTTP server aplikasi | FrankenPHP classic, image berbasis Debian dengan PHP 8.5 | D-16 disepakati; versi FrankenPHP, rilis Debian, ekstensi dan digest belum dikunci. Tidak mengaktifkan Octane atau worker mode FrankenPHP pada baseline ini. |
| CI/build | GitHub Actions, GitHub-hosted runner | Pemeriksaan kode, tipe, tes, build dan keamanan yang relevan pada PR/main. Dipilih sebagai blueprint; workflow belum dibuat. |
| Registry | GitHub Container Registry (GHCR) | Menyimpan image hasil build main; commit untuk penelusuran, digest untuk mengunci versi deployment. |
| Continuous deployment | Self-hosted runner khusus deployment | Memperbarui Compose pada komputer demo setelah job main tepercaya lolos; tidak menjalankan kode PR atau build tak tepercaya. |
| Akses publik demo | Cloudflare DNS + satu Cloudflare Tunnel/cloudflared | D-33 disepakati: DigitalPlat `<nama>.dpdns.org`, dua hostname saudara `sia.<nama>.dpdns.org` dan `ws.<nama>.dpdns.org`; HTTPS/WSS edge, HTTP/WS internal Compose. Domain belum dimiliki; NS/zone/TLS belum diverifikasi. |

Baseline pada baris Backend, Runtime PHP, Penghubung UI/server, Frontend, Styling, Komponen UI dan Database disepakati pemilik 29 Oktober 2026 (D-15). Versi paket persis/rilis starter kit dikunci melalui lockfile; image runtime dikunci digest saat bootstrap. Baris lain tetap mengikuti status keputusan masing-masing. Persetujuan baseline bukan bukti paket telah terpasang atau diuji bersama. Jangan mengambil branch main tanpa mengunci rilis yang sesuai.

Standar source, pemilihan alat minimum, strategi pengujian dan registrasi command nyata mengikuti [CODE_QUALITY_PLAN.md](CODE_QUALITY_PLAN.md). Formatter/linter frontend mengikuti starter kit final; belum ada script atau gate aplikasi yang dikonfigurasi.

## 3. Perbandingan pilihan frontend

| Pilihan | Kelebihan untuk proyek | Tradeoff dan kecocokan |
| --- | --- | --- |
| Laravel + Blade/Livewire | Lebih banyak pekerjaan dalam PHP; cocok untuk tim yang fokus PHP | Layak sebagai baseline sederhana, tetapi kurang sesuai tujuan pemilik menonjolkan React/TypeScript. |
| Laravel + Inertia + React/TypeScript | React nyata, satu aplikasi dan sesi, policy/route Laravel | Dipilih pemilik 29 Oktober 2026. Tetap perlu mengelola form, state dan tipe data frontend dengan baik. |
| Backend API + React/Next.js terpisah | Berguna jika ada konsumen API/mobile terpisah atau deployment independen | Menambah kontrak API, auth lintas aplikasi dan dua konfigurasi deployment; belum dibutuhkan oleh blueprint ini. |

Inertia tidak menghapus kewajiban validasi/otorisasi atau mengirim semua data server ke browser. Props harus berisi proyeksi data yang memang boleh dilihat pengguna.

## 4. Menghubungkan teknologi dengan blueprint

- Role/penugasan di database dan policy menentukan akses. Mengganti konteks kerja React tidak memberikan hak tambahan. Jangan mengirim hash password, field profil staf yang tidak berhak, atau nilai draft melalui props lalu menyembunyikannya di UI.
- Form satu kelas memakai state lokal React dan fasilitas form Inertia. Validasi, pengecekan versi dan penyimpanan atomik terjadi di server; tidak ada request simpan setiap karakter jika tidak diperlukan. Redux/global store belum dibutuhkan.
- Perkembangan Belajar membaca aktivitas yang dibagikan/disahkan dari data operasional. Rapor Online membaca snapshot finalized lalu published. Keduanya berbeda halaman, query dan izin file.
- Audit dicatat melalui action aplikasi dalam transaksi yang sama dengan perubahan dan notifikasi. Observer model saja tidak menjamin semua perubahan bulk diaudit. Runtime database tidak diberi UPDATE/DELETE pada audit atau hak DDL; rancangan ini tetap harus diuji.
- Nilai/bobot disimpan DECIMAL. Kalkulasi final server tidak bergantung pada angka float atau hasil preview yang dikirim klien. Q7.3 pembulatan akhir dua desimal half-up disepakati; tidak membulatkan rata-rata/kontribusi sebelum total. Q7.4 comparator memakai hasil akhir dibulatkan >= KKM versi yang sama; presisi konfigurasi Q7.5 juga diterima; seluruh aturan numerik tetap perlu bukti implementasi.
- PDF menggunakan template Blade khusus cetak dengan CSS yang didukung Dompdf. Frontend utama tetap React. Dompdf tidak mendukung flexbox/Grid; template tabel cetak harus mengikuti keterbatasan renderer, termasuk satu baris tabel harus muat dalam satu halaman. Susunan Q7.1 telah disepakati; aturan angka Q7, detail visual/pagination, nama panjang, jumlah mapel, font/logo dan hasil render tetap belum diverifikasi. CSS cetak di luar utility Tailwind/shadcn hanya bila kebutuhan tidak didukung keduanya, dengan alasan dan rujukan dicatat sesuai DESIGN_SYSTEM; pemilihan renderer tidak menyetujui seluruh layout.
- Data domain, audit dan inbox disimpan atomik. Setelah commit berhasil, Laravel Broadcasting menjadwalkan broadcast lewat database queue -> worker -> Reverb -> Echo. Rollback tidak mengirim broadcast; kegagalan socket sesudah commit tidak membatalkan data yang sudah tersimpan.
- Reverb memakai private channel per pengguna dengan autentikasi sesi dan otorisasi subscribe. Worker memeriksa kembali akun/penerima/scope; payload cukup notification_id dan jenis event, tanpa nilai atau biodata. React mengambil ulang daftar/badge melalui policy server dan mendeduplikasi ID.
- Saat reconnect, membuka inbox atau refresh, data dipulihkan dari database. Jika socket terputus, fallback fetch/polling terbatas dapat digunakan; WebSocket adalah jalur utama realtime. Pengiriman bukan jaminan exactly-once dan sinyal socket dapat terlewat; inbox persisten tetap tersedia.
- Broadcast memberi tahu perubahan inbox yang sudah tersimpan; tidak membuat notifikasi kedua. Pengelompokan massal tetap satu notifikasi per penerima/cakupan dengan audit per objek. Menandai dibaca tidak memicu notifikasi baru.
- Pembuatan PDF per siswa dapat sinkron jika hasil pengukuran memadai. Worker yang sudah diperlukan untuk broadcast dapat diperluas untuk antrean PDF bila pembuatan massal benar-benar dibutuhkan, dengan antrean terpisah agar tidak menahan notifikasi.

## 5. Pilihan database dan operasi

MySQL direkomendasikan karena pola akses utama berupa siswa-kelas-periode, penugasan, nilai, kehadiran dan laporan yang saling berelasi, dengan transaksi untuk input kelompok/audit/keputusan. Tidak ada kebutuhan pencarian vektor, dokumen bebas atau banyak database.

PostgreSQL juga layak jika lebih cocok dengan pengalaman/hosting pemilik. SQLite merupakan baseline lokal yang lebih ringan, tetapi pengujian SQLite saja tidak membuktikan locking, constraint dan transaksi MySQL. MySQL 8.4 LTS/InnoDB disepakati pemilik 29 Oktober 2026; patch/image dan konfigurasi rinci belum dikunci. Tidak ada benchmark pembanding yang diklaim.

Target demo adalah komputer lokal dengan Docker Compose. App, queue dan reverb menggunakan satu image aplikasi dengan digest yang sama, tetapi perintah proses berbeda. MySQL memakai image database sendiri. Restart policy, healthcheck dan shutdown terkendali mengatur siklus proses; kesehatan layanan tetap perlu dipantau. Perangkat, Docker Engine, runner dan internet harus aktif agar deployment berlangsung. Akses publik juga memerlukan cloudflared dan layanan aplikasi yang hidup.

Layanan inti: app, mysql, queue dan reverb. Vite/source mount hanya untuk pengembangan; demo memakai aset frontend hasil build tanpa Vite dev server. cloudflared melalui profil demo diperlukan ketika akses publik diaktifkan; tidak perlu untuk dev/demo yang hanya lokal. Profil tidak memisahkan data otomatis: dev dan demo memakai Compose project, network, database, volume serta environment terpisah agar dapat berjalan bersamaan.

MySQL, worker dan port administrasi tidak dipublikasikan. Allowed origins Reverb dibatasi, browser memakai WSS, logout membersihkan subscription dan secret server tidak dikirim ke frontend. Database serta storage privat memakai volume persisten; volume bukan backup. HTTP app memakai FrankenPHP classic berbasis Debian dengan PHP 8.5 (D-16); versi FrankenPHP, rilis Debian, patch/digest image, kapasitas host, kelayakan retensi backup D-31 dan target pemulihan masih perlu diuji/ditentukan. Tidak ada provisioning atau deployment dalam tahap blueprint.

Topologi: [DEPLOYMENT_ARCHITECTURE.md](DEPLOYMENT_ARCHITECTURE.md). Kontrak lingkungan: [ENVIRONMENT_SPEC.md](ENVIRONMENT_SPEC.md).

Rancangan database khusus proyek: [DB-001](DATABASE_DECISION_RECORD.md), [arsitektur](DATABASE_ARCHITECTURE.md), [provider MySQL](DATABASE_PROVIDER_IMPLEMENTATION.md), [migrasi/pemulihan](DATABASE_MIGRATION_PLAN.md), dan [laporan verifikasi](DATABASE_VERIFICATION_REPORT.md). Semuanya blueprint; baseline MySQL 8.4 LTS/InnoDB disepakati, patch/image dan rancangan fisik belum dikunci, seluruh tes database NOT RUN.

Rancangan awal satu instance Reverb; Redis baru dipertimbangkan bila perlu beberapa instance dan horizontal scaling. Open source menghilangkan biaya lisensi Reverb, bukan kebutuhan sumber daya hosting.

## 6. Batas kesederhanaan

Reverb/Echo dan database queue dibatasi untuk notifikasi. Tidak menambahkan Next.js, backend API terpisah, JWT, Redux, microservices, Redis, broker eksternal, Kubernetes atau platform permission/workflow umum tanpa kebutuhan baru. Tidak membuat chat, presence atau streaming nilai hanya karena WebSocket tersedia.

Struktur kode mengikuti Laravel biasa: controller tipis, validasi, policy, action bisnis untuk operasi seperti simpan daftar nilai, finalisasi rapor atau apply kenaikan, serta komponen React yang terfokus. Jangan membuat repository abstrak untuk setiap model atau menaruh seluruh kalkulasi bisnis di controller/React.

## 7. Lingkungan, perintah dan verifikasi

Pembaruan 29 Oktober 2026: manifest/runtime aplikasi inti di root belum tersedia, sehingga command dan pengujian server tetap **BELUM DIKONFIGURASI / NOT RUN**. [Prototipe facelift-v2](support/facelift-v2/README.md) sudah memiliki package.json/lockfile dan skrip React/TypeScript/Tailwind/shadcn untuk tinjauan terisolasi; itu bukan keputusan menyalin Vite harness atau versi paketnya ke aplikasi inti. Prototipe menjadi **referensi desain, bukan panduan**, dan bukti tesnya hanya berlaku pada artefak/cakupan yang dicatat.

Alat kerja implementasi mengikuti [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md): Ponytail full untuk perubahan terkecil yang lengkap, antislop During dan taste-skill app-screen untuk kualitas UI berdasarkan tugas. Plugin bukan dependensi runtime atau gate CI yang otomatis aktif; tidak menambah library, layer, CSS manual atau fitur hanya demi resep skill. Keamanan, aksesibilitas, presisi dan pemeriksaan wajib tetap dipertahankan.

Setelah implementasi diotorisasi, tentukan rilis kit, baca dependensinya, cocokkan PHP/Node/MySQL dan buat perintah dari konfigurasi nyata. Uji kalkulasi blank/nol, total bobot, transaksi/audit, role rangkap, isolasi anak/kelas, status rapor dan file, serta alur browser admin-guru-wali. Dev/test memakai data sintetis.

Untuk realtime, uji private channel pengguna lain ditolak, tidak ada broadcast saat rollback, penerima yang dicabut tidak menerima data sensitif, dedup batch/retry, worker/Reverb mati, reconnect/catch-up dan kegagalan dispatch setelah commit. Inbox harus tetap dapat dibaca walau realtime gagal; jangan mengklaim semua sinyal terkirim hanya dari keberhasilan transaksi.

Kredensial berada di environment, tidak di Git atau dokumen. .env.example hanya nama variabel dengan nilai contoh aman. Akun migration berbeda dari runtime untuk menjaga audit append-only. Secret dan hak runner baru dikonfigurasi saat implementasi diotorisasi.

### Alur dev, main dan continuous deployment

1. Kembangkan pada dev dan uji localhost. Branch mengatur versi kode, bukan konfigurasi runtime.
2. PR dev ke main wajib lolos CI dan review pemilik; proteksi main dikonfigurasi sesuai dukungan akun GitHub. PR tidak memperoleh secret demo atau runner lokal.
3. Setelah merge, periksa revisi main tersebut, build image dari lockfile, verifikasi image dan jalankan pemeriksaan keamanan yang diwajibkan sebelum publish GHCR. Catat commit, hasil pemeriksaan dan digest.
4. Job CD bergantung pada keberhasilan job tersebut dan memakai digest hasil job yang sama. Runner lokal khusus demo memperbarui app/queue/reverb tanpa build ulang.
5. Serialisasi deployment per target. Periksa backup dan kompatibilitas migrasi, lakukan pergantian terkendali, lalu uji HTTP, database, queue, Reverb dan alur inti sebelum menyatakan berhasil.
6. Jika gagal, hentikan rilis dan pulihkan sesuai kompatibilitas skema. Kembali ke image lama tidak otomatis mengembalikan database.

CD otomatis adalah target demo sintetis yang disepakati. Build untuk produksi berarti image runtime dengan aset terkompilasi; bukan klaim kesiapan operasional sekolah. Bootstrap, DNS, secret dan runner belum dikerjakan. Produksi sekolah memerlukan kesiapan dan otorisasi rilis tersendiri. Compose satu host tidak menjamin zero downtime.

Rincian: [CICD_PIPELINE_SPEC.md](CICD_PIPELINE_SPEC.md) dan [DEPLOYMENT_PLAN.md](DEPLOYMENT_PLAN.md). Perintah Compose/CI/deploy masih **BELUM DIKONFIGURASI / NOT RUN**; belum membuat Dockerfile, compose.yaml atau workflow.

## 8. Keputusan yang masih perlu pembahasan

1. Rilis starter kit, versi paket persis, Node.js/build tooling dan kompatibilitas seluruh dependensi. Baseline Laravel 13/PHP 8.5/Inertia 3/React 19/TypeScript mengikuti starter kit/Tailwind 4/shadcn seperlunya sudah disepakati 29 Oktober 2026; lockfile belum dibuat.
2. Patch/image/digest MySQL, kolasi serta konfigurasi driver dan operasional. Baseline MySQL 8.4 LTS/InnoDB sudah disepakati pemilik 29 Oktober 2026.
3. Kompatibilitas perangkat Docker Desktop/WSL2, versi FrankenPHP/rilis Debian, patch/digest image, ekstensi dan isolasi runner deployment. Docker Desktop + WSL2 serta FrankenPHP classic berbasis Debian sudah disepakati pada D-16; toolchain belum dipasang atau diperiksa.
4. Bukti implementasi aturan Q7.1-Q7.5 serta detail visual cetak, font/asset, patch Dompdf/wrapper dan kompatibilitas pada image FrankenPHP. Dompdf melalui barryvdh/laravel-dompdf 3.1.x sudah disepakati pada D-17; renderer diganti hanya bila kebutuhan layout nyata melampaui kemampuannya.
5. D-33 memilih host PC pemilik, DigitalPlat .dpdns.org + Cloudflare authoritative DNS/Tunnel/HTTPS-WSS. Nama lengkap/registrasi/aktivasi zone/cert belum diverifikasi; visibilitas repo/package, resource/capacity, tool/key/kuota backup dan biaya operasional tersisa. Hosting produksi sekolah belum dipilih.

Keputusan final yang berpengaruh pada arsitektur/database dicatat pada dokumen keputusan terkait setelah disepakati, bukan menandai seluruh daftar sebagai Approved sekarang.

## 9. Referensi primer yang diperiksa

- [Laravel 13: versi PHP dan kebijakan dukungan](https://laravel.com/framework/docs/releases).
- [Starter kits Laravel: Inertia, React/TypeScript dan Tailwind/shadcn](https://laravel.com/framework/docs/starter-kits).
- [Composer resmi React starter kit](https://github.com/laravel/react-starter-kit/blob/main/composer.json) dan [package.json](https://github.com/laravel/react-starter-kit/blob/main/package.json), diperiksa 28 Oktober 2026, bukan dependensi yang telah dipasang.
- [Inertia](https://inertiajs.com/): integrasi frontend dengan routing server.
- [PHP supported versions](https://www.php.net/supported-versions.php).
- [MySQL LTS release model](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html).
- [Dompdf Laravel v3.1.2](https://github.com/barryvdh/laravel-dompdf/releases/tag/v3.1.2): dukungan Laravel 13 dan pengujian PHP 8.5; [batas layout Dompdf](https://github.com/dompdf/dompdf#limitations-known-issues).
- [Brick Math](https://github.com/brick/math): angka presisi arbitrer dan tipe rasional/desimal.
- [Laravel Reverb](https://laravel.com/framework/docs/13.x/reverb): WebSocket, operasional proses dan scaling; [lisensi MIT](https://github.com/laravel/reverb).
- [Laravel Broadcasting/Echo](https://laravel.com/framework/docs/13.x/broadcasting), [notifikasi setelah commit](https://laravel.com/framework/docs/13.x/notifications#queued-notifications-and-database-transactions), dan [database queue](https://laravel.com/framework/docs/13.x/queues).
- [FrankenPHP classic mode](https://frankenphp.dev/docs/classic/), [image Docker PHP 8.5 dan varian Debian](https://frankenphp.dev/docs/docker/), serta [deployment Laravel dengan FrankenPHP](https://laravel.com/framework/docs/13.x/deployment#frankenphp), diperiksa 29 Oktober 2026; referensi kapabilitas, bukan hasil uji proyek.
- [Docker Desktop WSL2 pada Windows](https://docs.docker.com/desktop/features/wsl/), diperiksa 29 Oktober 2026; dukungan perangkat lokal belum diperiksa.
- [Docker Compose profiles](https://docs.docker.com/compose/how-tos/profiles/) dan [startup/healthcheck](https://docs.docker.com/compose/how-tos/startup-order/).
- [GitHub Actions: publikasi image](https://docs.github.com/en/actions/tutorials/publish-packages/publish-docker-images), [GHCR](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry), dan [self-hosted runner](https://docs.github.com/en/actions/concepts/runners/self-hosted-runners).
- [Docker Compose pull](https://docs.docker.com/reference/cli/docker/compose/pull/) dan [up](https://docs.docker.com/reference/cli/docker/compose/up/): menarik image dan memperbarui container merupakan langkah berbeda.
- [Cloudflare Tunnel routing](https://developers.cloudflare.com/tunnel/concepts/routing/) dan [DigitalPlat](https://domain.digitalplat.org/): kemampuan layanan, bukan verifikasi kepemilikan atau aktivasi domain kandidat.

## 9. Lifecycle password D-25

Acuan [lifecycle](RECOVERY_AND_STEPUP_PLAN.md), [model OTP](DATA_MODEL.md) dan [sesi](SESSION_TOKEN_SECURITY_SPEC.md). Master langsung verified email sebagai attestation administratif; bukan bukti mailbox. D-23/D-24 diganti URL biasa/password sementara; TTL lama tidak diwariskan. Login username/password tetap; email hanya lookup alternatif reset.

Fouladgar dipilih karena purpose/channel/repository sesuai alur. Spatie v1.1.2 alternatif tidak dipilih: fokus OTP authentication, Livewire opsional dan dapat headless; tidak memasang dua paket atau Livewire. [Manifest Fouladgar](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/composer.json), [config](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/config/config.php), [repository](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/src/Token/DatabaseTokenRepository.php), [broker](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/src/OTPBroker.php), [Spatie](https://github.com/spatie/laravel-one-time-passwords). Kajian source bukan audit atau Composer install. Hash at rest, atomic consume/race, mail tanpa otp_log, binding dan limiter wajib diintegrasikan; binding/migration/prune vendor perlu disesuaikan.

Password sementara memakai generator/hash framework, bukan OTP pendek sebagai password. TTL/length/limit/resend/retensi/provider OPEN. Tidak menambah Redis, WhatsApp/SMS, signup publik atau OTP sign-in umum. Seluruh runtime/mail/sesi/race NOT RUN.

## Timezone sekolah D-29

Dikonfirmasi 30 Oktober 2026: satu school_timezone di system_settings, pilihan label WIB/WITA/WIT dengan nilai IANA Asia/Jakarta/Asia/Makassar/Asia/Jayapura. Master mengubah dinamis; backend menentukan kalender dan frontend mengikuti zona sekolah untuk semua pengguna. Runtime Laravel dan koneksi MySQL UTC; DATE/TIME literal, timestamp ISO8601 UTC. Format memakai kemampuan tanggal yang ada di PHP/JavaScript, tanpa paket timezone/service tambahan.

Baca setting/version per request/job; proyeksi shared Inertia membawa id/label/version. Perubahan tidak melalui .env/config:cache/VITE sehingga refresh cukup tanpa restart app/worker/Reverb/rebuild. Hindari global timezone mutabel lintas job, gunakan konversi eksplisit; mutasi sensitif recheck di bawah lock akademik. Nilai awal wajib dipilih saat setup, bukan default dari host. UTC expiry existing tetap, cap delegasi turunan DATE ketika zona berubah masih perlu keputusan. [Kontrak](API_CONTRACT.md), [model](DATA_MODEL.md); seluruh implementasi NOT RUN.

## Backup lokal dan unggah manual D-30

Tujuan Google Drive dan upload manual oleh pemilik dipilih 30 Oktober 2026: capture DB+file+manifest konsisten -> arsip terenkripsi lokal -> upload manual -> verifikasi remote -> latihan restore terisolasi. Tidak memilih paket backup Laravel, Drive API/OAuth/rclone/sync otomatis atau dashboard backup baru. Tool capture/enkripsi, path, kuota dan key recovery belum final; jadwal/retensi3lokal/7harian+4mingguanDrive diterima D-31. Host dilaporkan SSD kosong100GB, RAM24GB; kecukupan resource/retensi serta restore fungsional belum diuji. Target formal RPO/RTO di luar scope portofolio D-32. [Recovery kanonis](DATA_RECOVERY_PLAN.md).

D-31 (30 Oktober 2026): backup+upload setelah sesi pada hari yang sama, setiap hari berubah selama demo beberapa hari, dan sebelum perubahan berisiko; simpan3lokal/7harian+4mingguanDrive tanpa duplikat mingguan. Rotasi mengikuti syarat remote/pengganti terverifikasi dan set recoverable pada [DATA_RECOVERY_PLAN](DATA_RECOVERY_PLAN.md); kebijakan disetujui, eksekusi/ukuran/kuota/restore belum diuji.

D-33 dikonfirmasi 30 Oktober 2026: host demo PC pemilik (generasi13/RAM24GB/SSD kosong100GB), Compose Windows/WSL2, main CI -> GHCR immutable digest -> runner lokal; satu Tunnel/dua hostname aplikasi-Reverb, Cloudflare DNS via NS, HTTPS/WSS publik dan network Compose privat. Mode sleep off selama sesi, tanpa janji24/7. Hostname final publik harus tersedia sebelum build aset Echo yang dipublikasikan; perubahan build-time endpoint membutuhkan build baru. [Rincian kanonis](DEPLOYMENT_ARCHITECTURE.md). Semua registrasi/provisioning/config/runtime NOT RUN.
