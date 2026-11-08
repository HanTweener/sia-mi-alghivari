# Recovery and step-up plan — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status **blueprint; D-25 dikonfirmasi**, implementasi **NOT IMPLEMENTED**, seluruh tes **NOT RUN**. D-25 menggantikan D-23/D-24, kewajiban current_password serta usulan selalu mencabut semua sesi setelah password berubah. Angka expiry/limit, email provider, bootstrap Master dan fallback email hilang belum final.

Acuan: [PRD](PRD.md), [autentikasi](AUTHENTICATION_REQUIREMENTS.md), [metode](LOGIN_METHOD_MATRIX.md), [sesi](SESSION_TOKEN_SECURITY_SPEC.md), [model](DATA_MODEL.md), [API](API_CONTRACT.md), [stack](TECH_STACK.md).

## Account recovery threat model

| Skenario | Risiko | Perlakuan rancangan |
| --- | --- | --- |
| Master membuat akun | Email salah atau salah penerima | Email wajib unik, langsung verified oleh Master; catat verified_at/by/source master_attested dan audit. Ini attestation administratif, bukan bukti mailbox sudah diuji pengguna. |
| Password sementara | Akses domain sebelum forced change | Sistem membuat password acak unik dan menyimpan hash saja; email URL website biasa/username/password sementara. Login awal hanya ganti password/logout, tanpa API/data/file/inbox/Reverb. |
| Reset tanpa sesi | Enumerasi, spam, brute-force, OTP tertukar | Username/email lookup server, kirim hanya ke email akun tercatat; respons umum dan throttling issuance/verify. Proof terikat akun/email/purpose/browser/revisi credential. |
| Profil | Sesi dicuri atau proof lintas tujuan | Sesi aktif + OTP email purpose profile_password_change; tidak menerima target_user_id/tujuan email bebas. |
| Modal sesudah commit | Pilihan hilang, replay atau dianggap perubahan gagal | Simpan operation pending bersama password/audit. Resume pilihan, bukan ulang password; proof dan revisi terbaru wajib. |
| Tidak logout | Sesi penyerang tetap hidup | Jelaskan konsekuensi; bukan pemulihan kompromi tuntas. Nonaktif/kompromi tetap lifecycle revocation tersendiri. |
| Email/Master terakhir hilang | Backdoor pemulihan | Prosedur di luar email belum dipilih; tanpa password default, reset SQL atau akun tersembunyi. |

**Onboarding:** Master membuat akun/role/aktivasi/email verified administratif -> sistem membuat password sementara -> email URL biasa, username dan password sementara -> login terbatas -> password baru + konfirmasi -> commit -> modal pilihan sesi wajib -> aplikasi. URL tidak memuat token. TTL 10 menit/sekali-buka D-24 tidak diwariskan pada URL, password sementara atau OTP baru. Password sementara kedaluwarsa, tidak dapat dibaca ulang lewat administrasi dan tidak boleh dipakai setelah setup berhasil.

Q6.1 tetap: Master menyiapkan akun wali dahulu, lalu Data Entry biodata/hubungan anak sesuai izin. Berlaku untuk seluruh akun baru; akun existing yang memperoleh role tambahan tidak direset/digandakan. Akun active/email verified belum memberi scope anak atau melewati forced-change gate. Bootstrap Master pertama masih perlu prosedur operator.

**Reset:** login -> lupa/reset password -> username/email -> OTP ke email akun tercatat -> verifikasi -> proof terbatas -> password baru + konfirmasi -> commit -> modal pilihan sesi wajib -> login biasa dengan password baru -> aplikasi. Tidak auto-login setelah OTP maupun reset. Akun unknown/nonaktif dan identifier ambigu tidak dipilih sembarang atau diaktifkan; respons publik tetap umum.

**Profil:** sesi aktif -> OTP ke email tercatat -> verifikasi -> password baru + konfirmasi -> commit -> modal wajib -> kembali ke profil/aplikasi dengan sesi kini tetap login dan pengenal diperbarui. Ini menggantikan current_password wajib, bukan tambahan metode OTP login. Profil mandiri sudah termasuk kebutuhan. Edit email tidak otomatis verified/menjadi tujuan recovery; kontrak email berubah/salah/hilang tetap terbuka.

## Password/MFA/passkey/email-change re-verification

| Tindakan | Verifikasi |
| --- | --- |
| Password pertama | Password sementara valid, akun aktif, belum expired dan konteks forced change target server. Konteks awal lain tidak boleh ikut menjadi sesi penuh setelah flag berubah. |
| Reset | OTP purpose password_reset, lalu proof terbatas sekali pakai; sesudah sukses login biasa. |
| Profil | Sesi aktif + OTP purpose profile_password_change, tanpa current_password pada alur ini. |
| Email/username/role sensitif | OTP reset/profil bukan izin ubah role atau verifikasi kontak baru; step-up administratif dan perubahan kontak tetap kontrak terpisah. |
| Input nilai/absensi | Tidak menambah OTP rutin; sesi, policy dan versi domain tetap berlaku. |
| MFA/passkey/provider | N/A core; OTP email bukan TOTP, social sign-in atau sertifikasi MFA. |

Fouladgar dipilih sebagai fondasi OTP, bukan pengelola role/login/sesi atau penyedia email. Target penyimpanan **MySQL otp_tokens dengan hash** dan repository aplikasi yang mengadaptasi paket. Source v6.2.0 menyimpan token mentah, validate/revoke terpisah dan channel default otp_log menulis token. Hash melalui fasilitas framework, serialisasi user/challenge dan konsumsi atomik serta channel mail adalah pekerjaan integrasi wajib, bukan fitur bawaan yang sudah terbukti.

OTP sah dikonsumsi sekali untuk menghasilkan proof server yang terikat user/purpose/email/browser/credential_version/expiry; proof hanya mengganti password target. Kode terpakai tidak diaktifkan kembali. Password + konsumsi proof + credential revision + pending modal + audit/inbox metadata wajib berada dalam satu transaksi DB. Konfirmasi form salah tidak menghabiskan proof sebelum password sukses; proof expired/revoked/reused ditolak. Dua consumer atau dua submit tidak boleh dua kali berhasil. Persistence cookie/email bukan transaksi DB; kehilangan proof memerlukan verifikasi ulang tanpa sukses palsu.

## Rate limits, human support escalation and cooldown

**OPEN:** panjang/TTL OTP, TTL password sementara/proof, batas per challenge/akun/sumber, cooldown resend, retensi/pruning, hashing, session timeout dan email provider. Default paket 5 digit/5 menit serta 5 validasi/60 detik adalah fakta source, bukan angka proyek yang disetujui.

Reissue password sementara hanya oleh Master sebelum setup selesai; credential/konteks awal lama dibatalkan. Resend OTP diserialisasi akun/tujuan dan membatalkan challenge/proof lama untuk tujuan sama; tujuan profil/reset tidak boleh tertukar. Perubahan credential/email/aktivasi membatalkan proof usang. Permintaan tanpa bukti tidak mengganti password, mencabut sesi atau mengubah status akun.

Email memakai Laravel Mail/Notifications dan database queue/worker sesudah commit, melalui SMTP/API provider yang belum dipilih. URL dari APP_URL tepercaya, bukan Host request bebas. Password sementara/OTP hanya dikirim pada email tujuan yang semestinya; tidak masuk audit/inbox/Reverb/flash/log/screenshot/artefak CI. Payload job/failed jobs harus terlindungi/encrypted dengan fasilitas framework dan retensi terbatas; jangan menyimpan secret plaintext untuk retry. Dev/CI memakai fake/penampung lokal. Kegagalan enqueue/email sesudah commit tidak membatalkan akun/password tersimpan atau membuktikan email diterima; retry/reissue harus terlihat bagi aktor berhak tanpa membuka secret.

## Session invalidation and user notification

**Modal wajib SETELAH password berhasil disimpan**, termasuk first setup, reset dan profil. Ada dua pilihan eksplisit logout/tidak; wajib modal bukan wajib logout. Esc/backdrop/refresh/timeout/menutup tab tidak memilih diam-diam. Password yang commit tetap berhasil; simpan pending choice dan tawarkan kembali melalui proof sah atau login normal berikutnya, bukan replay password.

| Alur | Pilih logout | Pilih tidak | Sesudah pilihan |
| --- | --- | --- | --- |
| Reset | Cabut seluruh sesi login akun | Pertahankan sesi existing yang sah | Login biasa, tanpa auto-login; konteks reset bukan sesi login |
| Profil | Cabut seluruh sesi lain, pertahankan/perbarui sesi kini | Sesi lain tetap; sesi kini diperbarui | Kembali ke profil/aplikasi |
| First setup | Cabut sesi lain yang relevan | Tidak memperluas konteks sementara yang sudah usang | Konteks pertama sah menjadi sesi normal setelah gate selesai |

UI profil menyebut **perangkat lain** sesuai permintaan awal bahwa perangkat yang dipakai tetap login; reset menyebut semua perangkat. Cakupannya sesi aplikasi ini, bukan email/WhatsApp/perangkat di situs lain.

Pending operation ID/flow tersimpan atomik bersama password_changed_at/credential_version. Pilihan memerlukan proof browser terbaru, atau login baru dengan credential terbaru untuk resume; operation_id saja dan sesi lama tanpa proof bukan otorisasi memilih. Pilihan operasi lama ditolak setelah password berubah lagi. Lock/conditional update mencegah duplicate click/revocation dua kali. Cleanup gagal bukan sukses logout; request in-flight tidak boleh menghidupkan sesi dicabut. Durasi proof/retry/abandonment teknis masih OPEN, tanpa default yes/no otomatis.

Laravel auth.session bawaan membandingkan hash password dan dapat mencabut sesi meskipun pengguna memilih Tidak. logoutOtherDevices meminta password plaintext; jangan menyimpan password baru untuk request modal berikutnya. Usulan fisik eksplisit: credential_version membatalkan proof/operasi lama; session_generation mencabut sesi bila dipilih atau akun dinonaktifkan. Database sessions memeriksa generation/akun pada request, tanpa otomatis menolak sesi normal hanya karena hash credential berubah ketika No. Detail middleware, lock dan sesi kini harus diuji; revocation role/akun tetap wajib. Lihat [model](DATA_MODEL.md) dan [sesi](SESSION_TOKEN_SECURITY_SPEC.md).

Email pemberitahuan aman setelah perubahan tidak memuat password. Notifikasi domain tetap commit -> queue -> Reverb -> Echo; email auth lifecycle tersendiri, tidak memberi grant dan bukan seluruh CUD dikirim email.

## Negative tests and contingency paths

| ID | Hasil yang diharapkan | Hasil aktual |
| --- | --- | --- |
| REC-01 | Profil OTP sah/purpose/sesi benar; konfirmasi salah, proof usang/akun lain ditolak; current_password bukan syarat yang disisipkan. | NOT RUN |
| REC-02 | Step-up administratif terpisah; OTP reset/profil tidak mengotorisasi role/email/akun lain. | NOT RUN; operasi/window step-up belum final |
| REC-03 | Master akun/email verified administratif/password sementara; mail fake benar, secret redacted. Direct API/file/channel ditolak sebelum forced change. Expired/reissue/replay setelah change gagal; existing tambah role tidak direset. | NOT RUN |
| REC-04 | OTP wrong/expired/purpose/account/email/browser/revision/reused/resend lama/race ditolak; hanya satu proof/password sukses. Reset modal lalu login biasa tanpa auto-login. | NOT RUN; TTL/limit belum final |
| REC-05 | Modal setelah commit wajib; yes/no reset/profil/first setup, current profile tetap login; refresh/response hilang/stale operation/duplicate pilihan tidak replay password. Race tidak resurrect; No tidak diam-diam logout akibat hash mismatch. | NOT RUN |
| REC-06 | Bootstrap/email hilang/last Master tanpa shared password/backdoor/reset SQL. | NOT RUN; fallback OPEN |
| REC-07 | Email langsung verified oleh Master dicatat sebagai attestation, bukan OTP fiktif. OTP hanya ke email tersimpan; edit email membatalkan proof, nonaktif tidak aktif kembali. | NOT RUN |
| REC-08 | Audit gagal rollback password/proof/pending. Enqueue/mail gagal setelah commit bukan rollback credential atau sukses pengiriman palsu. Hash at rest dan redaction jobs/log/inbox/socket diuji. | NOT RUN |

Sumber primer diperiksa 29 Oktober 2026: [config Fouladgar v6.2.0](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/config/config.php), [repository](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/src/Token/DatabaseTokenRepository.php), [broker](https://raw.githubusercontent.com/mohammad-fouladgar/laravel-otp/v6.2.0/src/OTPBroker.php), [AuthenticateSession](https://raw.githubusercontent.com/laravel/framework/13.x/src/Illuminate/Session/Middleware/AuthenticateSession.php), [OWASP](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html). Ini kajian sumber/rancangan, bukan audit paket atau bukti runtime.

Catatan integrasi broker: validate() bawaan memanggil revoke() sesudah match, sementara target schema menyimpan verified/proof/completed metadata. Karena itu penyesuaian harus mencakup lifecycle broker/repository, bukan hanya nama kolom atau konfigurasi driver; jangan membiarkan revoke vendor menghapus proof yang masih diperlukan atau menganggap kode sudah terpakai sebelum transaksi aman. Auto-loaded migration/prune harus mengikuti schema yang benar. Event package yang membawa token mentah tidak boleh diserialisasi ke audit/log/telemetry; petakan metadata allowlist saja. Detail adapter harus dibuktikan melalui DBV26/AS20 sebelum aktivasi.
