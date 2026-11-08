# Spesifikasi lingkungan - SIA MI Alghivari

Diperbarui: 30 Oktober 2026. Status: kontrak blueprint; belum membuat .env.example, Compose, image atau secret. Contoh berikut bukan konfigurasi siap pakai.

## Pemisahan lingkungan

| Lingkungan | Kode/artefak | Data dan runtime | Akses |
| --- | --- | --- | --- |
| Development | dev; source lokal dan hot reload | Compose project, database, network dan volume dev tersendiri | Localhost; debug pengembangan. |
| CI | Revisi PR/main yang diperiksa | Runner GitHub, database tes sementara, data sintetis; tanpa secret demo | Tidak dipublikasikan sebagai aplikasi. |
| Demo portofolio | Image main yang diperiksa, dikunci digest | Compose project demo, volume persisten, debug mati, aset terkompilasi | HTTPS/WSS melalui Tunnel saat aktif. |
| Produksi sekolah | Belum menjadi lingkungan aktif | Hosting, data, pemulihan dan operasi dikaji tersendiri | Memerlukan otorisasi rilis dan gate terkait. |

Runtime host Windows disepakati memakai Docker Desktop + WSL2 dengan Linux containers; app memakai FrankenPHP classic berbasis Debian/PHP 8.5 (D-16). App/queue/reverb menggunakan satu image aplikasi dengan perintah proses terpisah. Classic mode tidak meniadakan worker antrean.

Branch tidak mengganti konfigurasi runtime otomatis. Dev/demo dapat berjalan bersamaan dengan project name, port lokal, database, network, volume dan secret berbeda. Profil demo hanya memilih cloudflared, bukan batas isolasi. Jangan memasang source/Vite dev ke demo.

## Konfigurasi dan batas eksposur

Nama variabel final mengikuti versi Laravel/Reverb/Compose yang dipakai. Operator/pemilik proyek menyediakan dan mengelolanya; tabel tidak menyimpan nilai secret.

| Konfigurasi | Batas eksposur | Dev | Demo |
| --- | --- | --- | --- |
| APP_ENV / APP_DEBUG | Server | local / true | production / false untuk perilaku runtime; target tetap demo sintetis. |
| APP_URL | URL publik, bukan secret | URL localhost sesuai port | `https://sia.<nama>.dpdns.org` sebagai placeholder D-33, bukan domain aktif. |
| APP_KEY | Secret server | Key dev tersendiri | Key demo persisten, tidak dibuat ulang setiap rilis. |
| DB_HOST / DB_DATABASE / kredensial | Server; password secret | Service mysql dan database dev | Service mysql dalam project demo; database/kredensial terpisah. |
| QUEUE_CONNECTION | Server | database | database; jobs/failed_jobs pada database lingkungan masing-masing. |
| REVERB_SERVER_HOST / REVERB_SERVER_PORT | Bind internal server | Bind yang dijangkau network dev | Bind internal container; port ilustratif 8080. |
| REVERB_HOST / REVERB_PORT / REVERB_SCHEME | Tujuan broadcast backend | Endpoint internal reverb | reverb / 8080 / http sebagai ilustrasi network privat. |
| VITE_REVERB_HOST / VITE_REVERB_PORT / VITE_REVERB_SCHEME | Publik, tertanam saat build | Endpoint browser dev | `ws.<nama>.dpdns.org` / 443 / https sebagai placeholder D-33; Echo memakai WSS. |
| REVERB_APP_ID / REVERB_APP_KEY / REVERB_APP_SECRET | Identitas aplikasi; secret hanya server | Identitas dev | Identitas demo terpisah; frontend hanya menerima key dan parameter publik yang diperlukan. |
| VITE_REVERB_APP_KEY | Publik, tertanam saat build | Key publik aplikasi Reverb dev | Key publik Reverb demo, cocok dengan REVERB_APP_KEY server; bukan APP_SECRET. |
| Cookie sesi / allowed origins | Server | Origin lokal yang dipilih | Cookie secure dan host aplikasi; origins eksplisit. |
| Image/digest registry | Identitas artefak, bukan secret | Build lokal | Digest hasil job main yang lolos. |
| Kredensial pull GHCR | Secret bila package privat | Tidak diperlukan untuk source dev | Read-only package; image publik dapat ditarik tanpa login. |
| Token tunnel | Secret cloudflared | Tidak diperlukan | Diinjeksi runtime hanya ke cloudflared; nama variabel mengikuti metode final. |

Frontend hanya menerima konfigurasi publik. Perubahan hostname Echo yang tertanam membutuhkan build baru; jangan memakai prefix VITE_ untuk APP_KEY, password database atau secret Reverb. Pemisahan endpoint: [Laravel Reverb](https://laravel.com/framework/docs/13.x/reverb).

## Penyediaan dan validasi

- .env lokal diabaikan Git; .env.example kelak hanya nama dan placeholder aman. Secret tidak masuk image layer, build args, log, screenshot atau artefak CI.
- Job publish memakai GITHUB_TOKEN dengan package write hanya pada job tersebut. Host demo memperoleh hak pull saja bila package privat. [GHCR](https://docs.github.com/en/packages/working-with-a-github-packages-registry/working-with-the-container-registry).
- Build tidak menerima secret runtime demo. Siapkan konfigurasi Laravel yang bergantung secret saat runtime; jangan membawa config cache berisi secret build ke image.
- Validasi startup memeriksa variabel wajib, koneksi dan storage tanpa mencetak nilai secret. Akun runtime tidak diberi DDL atau UPDATE/DELETE audit; akun migrasi terpisah.
- Key/volume tidak direset pada restart, merge atau upgrade. Rotasi memerlukan prosedur yang memperhitungkan data terenkripsi dan sesi.

Baseline versi inti pada TECH_STACK serta Docker Desktop/WSL2 dan FrankenPHP classic berbasis Debian telah disepakati. Versi FrankenPHP, rilis Debian, patch/digest image, ekstensi, port final, konfigurasi sesi database/cache, kapasitas dan penyimpanan secret belum ditetapkan; jadwal/retensi backup D-31 diterima, kelayakan ruang/kuota dan eksekusi belum diuji. Semua validasi **NOT RUN**. Acuan: [arsitektur](DEPLOYMENT_ARCHITECTURE.md) dan [pipeline](CICD_PIPELINE_SPEC.md).

## Email dan OTP D-25

Laravel Mail/Notifications + database queue; SMTP/API provider, sender domain/DNS dan secret belum dipilih/diaktifkan. Konfigurasi MAIL_* mengikuti driver Laravel final, secret hanya runtime server tanpa VITE_*; Cloudflare Tunnel bukan mail transport. Dev/CI fake mail/penampung privat, demo hanya kirim nyata setelah otorisasi. OTP channel mail, bukan otp_log; log/debug/failed jobs tidak plaintext credential, queued auth payload harus encrypted/redacted dan retensinya dibatasi. Storage OTP/sesi MySQL, tanpa Redis. Expiry/length/attempt/resend/proof/temporary password adalah parameter OPEN, bukan default package yang dianggap disetujui. Source raw OTP vendor dan hash-mismatch auth.session harus diadaptasi sebelum ready. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

## Timezone dinamis D-29

Acuan internal runtime Laravel/koneksi MySQL UTC dan timestamp kejadian UTC. school_timezone berasal dari database lingkungan masing-masing, bukan variabel VITE, timezone OS/Docker atau nilai .env yang perlu config-cache/restart. Pilihan UI WIB/WITA/WIT -> Asia/Jakarta/Asia/Makassar/Asia/Jayapura; satu acuan kalender dan tampilan. Nilai awal belum dipilih pemilik dan harus dikonfigurasi Master sebelum akademik.

Setiap request/job membaca versi setting terbaru, satu konteks konsisten per unit kerja; tidak memakai singleton/global state yang menyimpan zona job sebelumnya. Master simpan/audit -> pengguna refresh; tidak mengubah config runtime UTC atau restart container. Validasi ini masih NOT RUN, termasuk round-trip DATE/TIME/timestamp dan race setting-mutasi akademik.

## Hostname dan DNS/TLS D-33

Satu zone calon `<nama>.dpdns.org` milik pemilik setelah registrasi, Cloudflare authoritative NS; route `sia.<nama>.dpdns.org` -> app dan `ws.<nama>.dpdns.org` -> reverb dalam satu Tunnel. Placeholder bukan hostname deploy siap pakai; jangan menyisipkannya ke image publik atau menyatakan sertifikat tersedia. Nama lengkap dan zone/certificate yang diterima diverifikasi dahulu.

APP_URL origin HTTPS aplikasi, allowed origins Reverb hanya origin aplikasi, cookie Secure host-only dan proxy HTTPS sesuai hop aktual. Host Echo publik adalah konfigurasi nonsecret build-time, token Tunnel/key secret hanya runtime server; worker broadcast memakai endpoint Reverb internal seperti tabel, bukan hostname browser. Perubahan nama final memerlukan build aset baru yang kemudian diperiksa/dikunci digest, tidak mutasi aset pada image rilis yang sudah diverifikasi.

HTTP/WS antar-service Compose tetap privat; WSS/HTTPS pada edge. Tidak mengekspos MySQL/Docker/runner atau membuka wildcard cookie/origin. Capture metadata konfigurasi secara teredaksi, bukan dump secret. Konfigurasi/registrasi/DNS/TLS/browser/worker/reconnect semuanya NOT RUN.
