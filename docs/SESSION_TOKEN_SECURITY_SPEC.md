# Session & token security spec — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status: **PROPOSED / blueprint**; implementasi **NOT STARTED**, seluruh pengujian **NOT RUN**. Struktur template dipertahankan. Acuan perilaku: [autentikasi](AUTHENTICATION_REQUIREMENTS.md), [otorisasi](AUTHORIZATION_REQUIREMENTS.md), [pemulihan](RECOVERY_AND_STEPUP_PLAN.md), [kontrak HTTP](API_CONTRACT.md) dan [lingkungan](ENVIRONMENT_SPEC.md). Tidak ada middleware, cookie atau driver yang sudah terkonfigurasi.

## Opaque session or JWT selection rationale

Usulan untuk satu aplikasi Laravel/Inertia first-party: sesi opaque yang dikelola server, bukan JWT atau bearer token di React. Cookie menyimpan pengenal sesi yang diproses framework; browser bukan sumber role atau hubungan wali-anak. Gunakan fasilitas framework terpelihara, bukan generator/token atau kriptografi buatan sendiri.

Database session dipilih sebagai rancangan D-25 untuk proof, modal dan pilihan revocation, pada MySQL lingkungan yang sama tanpa Redis. Tabel sessions/payload adalah infrastruktur sensitif; ID/driver/schema detail mengikuti framework. Tidak ada runtime atau migration. [Model](DATA_MODEL.md).

Dukungan beberapa browser tidak menambah halaman pengelolaan perangkat. Batas sesi bersamaan masih terbuka. Usulan awal: tidak menyediakan remember-me/persistent login sehingga tidak memerlukan alur recaller terpisah; hapus/disable fitur bawaan yang tidak dipilih ketika starter kit diintegrasikan. Menutup tab/browser tidak dianggap bukti sesi server sudah dicabut.

## Cookie flags or mobile store and CSRF defenses

D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

| Area | Kontrak yang diusulkan dan bukti yang kelak diperlukan |
| --- | --- |
| Cookie sesi | Demo HTTPS: `Secure`, `HttpOnly`, `Path=/`, host-only tanpa `Domain` induk; `SameSite=Lax` sebagai usulan kompatibilitas navigasi. Nama cookie berbeda dev/demo. Jangan memperluas scope ke seluruh domain agar proyek lain atau Reverb dapat memakainya. |
| Localhost | Konfigurasi HTTP lokal terpisah; jangan menyalin pengecualian Secure ke demo. Port bukan batas isolasi cookie, sehingga nama cookie/host dev perlu dibedakan. |
| XSRF cookie/header | Cookie XSRF yang dibaca adapter berbeda dari cookie sesi HttpOnly. Mutasi termasuk login/logout/password memakai perlindungan forgery yang diuji pada rilis framework final; bukan sekadar menyembunyikan token di form. |
| Laravel 13 | Origin same-origin HTTPS dapat lolos sebelum fallback token. Pertahankan fallback; usulan tidak menambah `allowSameSite` lintas subdomain. Token fallback tidak cocok dapat 419; origin-only dapat 403 jika kelak dipilih. Tidak mengklaim semua request tanpa token ditolak. |
| Auth broadcaster | Default Laravel 13 mengecualikan `PreventRequestForgery` pada route broadcaster. Endpoint aplikasi tetap wajib sesi aktif dan channel pengguna sendiri. Penguatan forgery/origin khusus route tersebut masih keputusan konfigurasi yang harus diuji bersama Echo; jangan menganggap proteksi default sama dengan form domain. |
| HTTPS/proxy/origin | Browser memakai HTTPS/WSS melalui Tunnel. Trusted proxy/host harus eksplisit agar scheme/redirect/cookie benar; origin dan CORS lintas proyek tidak dibuka bebas. Auth channel dilakukan pada origin aplikasi, bukan dengan membagi cookie ke host WebSocket. |
| Data pada browser | Password sementara/OTP/password baru tidak pada URL, shared props, localStorage/sessionStorage, flash/log/inbox/socket. URL website awal biasa. Proof/pending tersimpan server, cookie sesi HttpOnly/Secure; XSRF terpisah. Email auth hanya tujuan sah; payload queue terenkripsi/retensi dibatasi. No-store dan isolasi dev/demo wajib. |

SameSite dan HttpOnly adalah lapisan perlindungan, bukan pengganti otorisasi, CSRF dan pencegahan XSS. Konten teks di-escape, proyeksi props minimum dan unggahan tidak dieksekusi. Foto/rapor melalui route berizin; URL file bukan credential.

Rujukan teknis yang diperiksa 28 Oktober 2026: [Laravel request forgery](https://laravel.com/framework/docs/13.x/csrf#origin-verification), [sumber route broadcaster](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Broadcasting/BroadcastManager.php) dan [OWASP session management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html). Konfigurasi di atas adalah usulan proyek, bukan klaim semua nilai tersebut default Laravel.

## Issuer, audience, algorithm, token type if JWT

**N/A:** tidak ada JWT/refresh token, issuer/audience atau algoritme tanda tangan aplikasi yang perlu ditetapkan untuk login lokal ini. Tidak memasang Sanctum/Passport atau layanan identity tambahan hanya untuk memenuhi bagian template. Tidak berlaku pula penyimpanan credential mobile karena aplikasi mobile terpisah belum termasuk lingkup.

## Refresh/revoke/session lifecycle and key rotation

| Pemicu | Hasil yang diharapkan; seluruhnya belum diterapkan |
| --- | --- |
| Login valid | Verifikasi credential dan akun aktif, regenerasi pengenal sesi melalui framework, muat identitas server. Login invalid/nonaktif tidak memperoleh sesi terautentikasi; pesan tidak membedakan keberadaan akun. |
| Request berizin | Periksa sesi dan akun aktif, lalu grant objek/field/state dari sumber server. Session role snapshot atau props lama tidak memberi hak tetap. |
| Idle/absolute expiry | Terapkan batas idle dan batas umur absolut di server. Nilai menit/jam, definisi aktivitas, serta peringatan isian belum disimpan masih TERBUKA sebelum login dinyatakan lengkap. Polling inbox/heartbeat/socket tidak boleh memperpanjang sesi tanpa batas. Jangan menganggap session lifetime bawaan sudah memenuhi batas absolut. |
| Logout biasa | Batalkan sesi saat ini dan regenerasi token CSRF melalui framework; tutup Echo dan hapus state sensitif klien. Sesi lama tidak dapat dipakai ulang; logout bukan hanya menghapus cookie browser. |
| Ganti password sendiri | Sesi aktif + OTP email profil; password/konfirmasi -> transaksi password/proof/pending/audit -> modal wajib -> pilihan logout semua sesi lain atau Tidak. Sesi kini tetap login dan ID diperbarui; bukan auto-cabut semua sesi karena hash berubah. |
| Nonaktif/arsip akun atau pemulihan/kompromi | Nonaktif/kompromi revocation tersendiri tetap efektif. Reset OTP D-25 tidak auto-login; setelah password sukses modal logout seluruh sesi login atau Tidak, kemudian login biasa. Request tanpa bukti tidak mencabut sesi/aktivasi. |
| Perubahan role/jabatan | Master mengubah grant sesuai policy. Usulan target diminta autentikasi ulang sebelum memakai grant baru; semua sesi lama tidak menyimpan hak lama. Pemilik perubahan tidak menyamar/login sebagai target. Mekanisme invalidasi harus ditetapkan dan diuji sebelum jalur ini dinyatakan lengkap. |
| Relasi anak, assignment, delegasi dicabut | Sesi boleh tetap dipakai untuk resource lain yang berhak, tetapi operasi berikutnya pada scope yang dicabut ditolak. UI form lama dan hasil cache bukan pengecualian. |

Revocation bukan selesai dengan delete sessions: request in-flight dapat menulis sesi kembali. D-25 memisahkan usulan credential_version untuk proof dari session_generation untuk revoke; payload sesi normal memeriksa generation/akun. Memilih Tidak mempertahankan sesi normal yang sah, tanpa otomatis invalidasi karena hash password berbeda. Laravel auth.session bawaan/logoutOtherDevices tidak memenuhi kontrak itu tanpa adaptasi; jangan simpan plaintext password untuk modal berikutnya. Pilihan logout memperbarui generation atomik dengan pending/audit; current profile session diterbitkan kembali dengan generation terbaru, yang lama ditolak. Mekanisme/penguncian final dan semua race masih NOT RUN; bukan izin melemahkan role/object checks. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

Jangan mengklaim transaksi database juga membatalkan cookie/file/socket. Jika pencabutan sesi fisik dilakukan sesudah commit, penolakan server terhadap sesi usang harus sudah efektif; kegagalan cleanup dilaporkan secara teknis dan tidak disembunyikan sebagai pencabutan sukses. Race sensitive-write mengikuti [otorisasi](AUTHORIZATION_REQUIREMENTS.md), bukan sekadar session lock.

Password confirmation memakai fasilitas framework setelah dipilih; durasi dan operasi wajibnya mengikuti [rencana step-up](RECOVERY_AND_STEPUP_PLAN.md). Konfirmasi tersebut tidak mengubah role dan tidak otomatis menjalankan kembali POST/approval yang gagal atau timeout.

`APP_KEY` dev/demo berbeda dan persisten antar-rilis. Tidak menghasilkan ulang key tiap restart/deploy. Prosedur rotasi terkontrol memperhitungkan sesi/cookie dan data terenkripsi yang bergantung key; tidak merotasi saat penulisan dokumen. Referensi: [Laravel authentication/session invalidation](https://laravel.com/framework/docs/13.x/authentication#invalidating-sessions-on-other-devices), [regenerasi sesi](https://laravel.com/framework/docs/13.x/session#regenerating-the-session-id).

## Identity versus API token separation

Credential login lokal, cookie sesi, token CSRF, bukti konfirmasi password dan token konsistensi `source_version` mempunyai tujuan berbeda; tidak dapat saling menggantikan. Channel authorization Echo dihasilkan adapter, bukan token login tambahan. Reverb app key bersifat konfigurasi publik; app secret, APP_KEY, token Tunnel dan credential GHCR/DB tetap server/operator saja.

D-25 membedakan password sementara, OTP email tujuan reset/profil, proof terbatas, pending modal, sesi normal, CSRF dan source_version. Tidak saling menggantikan. D-23/D-24 tidak berlaku; URL awal biasa, tidak ada token link sekali-buka.

OAuth/API connect/refresh JWT tidak dipilih. OTP email D-25 dipilih untuk lifecycle password saja; bukan login OTP umum. Tidak ada credential di inbox atau query rapor; provider mail dan parameter belum final.

Socket lama tidak otomatis terputus hanya karena sesi/role dicabut. Logout klien berupaya disconnect; worker memeriksa penerima/scope sebelum broadcast. Payload tetap hint minimal tanpa nilai/biodata dan seluruh fetch berikutnya memeriksa sesi/izin terkini. Hint yang sudah terbang atau browser yang telah menerima data tidak dapat ditarik kembali. Jangan menjanjikan pencabutan instan seluruh socket aktif; mekanisme disconnect server tambahan hanya jika kebutuhan mengharuskannya.

## Logout, account disabled, tenant role change, recovery tests

Sekolah tunggal: kasus tenant membership **N/A**, diganti pengujian isolasi anak/kelas/penugasan. Daftar berikut hasil yang diharapkan, bukan laporan eksekusi; rujuk CT-02/10/11/12 pada [API_CONTRACT](API_CONTRACT.md), tanpa menggandakan suite.

| ID | Skenario dan hasil yang diharapkan | Status |
| --- | --- | --- |
| SES-01 | Login mengganti pengenal sesi; identifier pra-login tidak mengautentikasi akun. Logout mengakhiri sesi server dan replay sesi lama gagal. | NOT RUN |
| SES-02 | Cookie sesi demo memenuhi flags/scope; cookie dev/proyek lain tidak dipakai; sesi tidak terlihat pada JS, URL, log atau socket. XSRF cookie diperiksa terpisah. | NOT RUN |
| SES-03 | Cross-origin/login CSRF, origin same-origin, fallback token invalid/hilang, browser tanpa Fetch Metadata dan middleware broadcaster menghasilkan penolakan/penerimaan sesuai konfigurasi aktual. | NOT RUN |
| SES-04 | Idle dan batas absolut dengan waktu terkontrol menolak sesi kedaluwarsa; background polling tidak membuat sesi abadi. Expiry tidak me-replay mutasi. | NOT RUN |
| SES-05 | D-25 modal setelah commit dan yes/no reset/profil/setup; No benar-benar mempertahankan sesi, profile current tetap login; reset login biasa. Nonaktif/explicit revoke efektif dan request lama tidak resurrect. | NOT RUN |
| SES-06 | Role/relasi/assignment/delegasi dicabut saat form terbuka: request berikutnya dan race mutasi mematuhi grant terkini; penambahan role tidak melewati konfirmasi ulang yang disepakati. | NOT RUN |
| SES-07 | Socket lama/duplikat hint tidak mengungkap isi; re-fetch setelah pencabutan ditolak; logout/reconnect tidak menampilkan state anak/akun sebelumnya. | NOT RUN |
| SES-08 | Audit gagal rollback password/proof/pending; respons/modal hilang resume tanpa replay password; pilihan lama/duplikat ditolak. Cleanup gagal tidak dianggap logout sukses, queue/mail tidak membocorkan credential. | NOT RUN |

Belum ada command atau artefak tes. Masa sesi, remember-me, middleware final dan mekanisme race revocation memerlukan keputusan/verifikasi; status dokumen bukan bukti sesi sudah aman.
