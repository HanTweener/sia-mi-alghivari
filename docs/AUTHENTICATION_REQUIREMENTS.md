# Authentication requirements — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status **PROPOSED / blueprint**; implementasi **NOT IMPLEMENTED**, seluruh pengujian **NOT RUN**. Aturan produk yang dikonfirmasi dibedakan dari usulan kontrol. Tidak memasang library, membuat akun atau mengaktifkan layanan dari dokumen ini.

Acuan: [PRD](PRD.md), [metode login](LOGIN_METHOD_MATRIX.md), [sesi](SESSION_TOKEN_SECURITY_SPEC.md), [pemulihan](RECOVERY_AND_STEPUP_PLAN.md), [otorisasi](AUTHORIZATION_REQUIREMENTS.md), [API](API_CONTRACT.md) dan [stack](TECH_STACK.md).

## User categories, assurance and risk

| Pengguna | Risiko dan batas identitas |
| --- | --- |
| Master | Mengelola akun/role/jabatan dan konfigurasi; pengambilalihan berpotensi memperluas akses. Tidak memperoleh password pengguna, bypass audit atau approval principal. |
| Akademik / Data Entry | Data akademik atau input terbatas; identitas login tidak menentukan izin field/state dan tidak memberikan akses akun staf lain. |
| Guru / wali kelas | Guru memakai akun sendiri; penugasan mapel/binaan diperiksa terpisah pada setiap tindakan. Tidak berbagi kredensial saat berhalangan, gunakan delegasi tercatat. |
| Principal | Membaca luas dengan pengecualian approve naik/lulus; role teacher/homeroom lain tetap berasal dari grant/penugasan yang sesuai. |
| Orang tua/wali | Satu akun hanya anak terkait; informasi identitas anak yang diketahui orang lain bukan bukti untuk mengambil alih akun wali. |

Semua memakai akun lokal dan sesi aplikasi yang sama. Role rangkap tidak memerlukan beberapa login. Pilihan konteks menu tidak menjadi pembuktian identitas atau grant. Belum ada penilaian atau sertifikasi tingkat assurance NIST; password satu faktor tetap memiliki risiko phishing, pencurian sesi dan berbagi akun.

Satu sekolah, tanpa tenant perusahaan atau identitas siswa untuk login mandiri pada core. Data demo sintetis tidak menghapus kewajiban menguji pemisahan anak/kelas/akun. Pemulihan identitas nyata sekolah belum diputuskan.

## Requested sign-in methods; alternatives and explicit N/A

Metode rencana: username/password melalui sesi server pada aplikasi first-party; usulan implementasi Laravel/Fortify sesuai rilis starter kit yang kelak dipilih. Login membawa username/password, tidak membawa role yang dipercaya server. Login berhasil memerlukan akun aktif dan credential yang telah siap serta verifikasi hash; sesi diregenerasi dan policy tetap diperiksa setelahnya.

D-25 memilih email untuk password sementara/OTP reset dan profil; bukan OTP login umum atau email sebagai login alternatif. Login tetap username/password, tanpa signup publik/social/API connect/SMS/WhatsApp/passkey/MFA/SSO/JWT. SMTP/API provider belum dipilih dan email tidak dikirim melalui Reverb/Tunnel.

**Usulan password, belum persetujuan pemilik:** minimum 15 karakter untuk metode satu faktor; dukung panjang setidaknya 64 karakter, spasi/Unicode dan password manager/paste; tanpa kewajiban campuran simbol/kapital atau pergantian berkala tanpa alasan kompromi. Periksa password umum/bocor dengan mekanisme aman yang dipilih saat implementasi, tanpa mengirim password lengkap ke layanan luar. Nilai maksimum/body limit serta normalisasi Unicode perlu kontrak dan tes yang konsisten. Dasar: [NIST SP 800-63B-4, Password Verifiers](https://pages.nist.gov/800-63-4/sp800-63b.html#passwordver), diperiksa 28 Oktober 2026; bukan klaim kepatuhan NIST.

Password disimpan melalui hash adaptif dan pemeriksaan library tepercaya; algoritma/parameter mengikuti pilihan framework yang diverifikasi dan pengukuran host sebelum dikunci. Tidak memakai enkripsi reversibel, hash buatan, truncation diam-diam atau password default bersama. Aturan panjang/hash/blocklist adalah usulan desain, bukan konfigurasi yang telah dipasang.

## Registration, verification, recovery, disable/delete

D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

| Tahap | Hasil yang diwajibkan / batas keputusan |
| --- | --- |
| Pembuatan akun | Master-only akun/role/aktivasi/email langsung verified administratif; password sementara sistem emailed -> restricted login -> forced change -> modal wajib -> aplikasi (D-25). Wali Q6.1 tetap Master dahulu lalu D biodata/hubungan, tanpa role/credential dari D. Existing tambah role tidak direset. |
| Master pertama | Identitas operator, metode bootstrap dan penyerahan kredensial belum dipilih; tidak menambah akun tersembunyi, secret pada seed/repo atau password yang diketahui bersama. |
| Verifikasi username/kontak | Email akun baru wajib unik dan verified_at/by/source master_attested oleh Master; bukan bukti mailbox. Normalisasi/unique diuji; edit email tidak otomatis verified/recovery. OTP hanya ke email tercatat, bukan input tujuan bebas. |
| Profil dan password sendiri | Profil allowlist; password memakai sesi aktif + OTP email purpose profil, bukan current_password. Password/konfirmasi -> commit -> modal logout/tidak wajib; perangkat kini tetap login, logout menyasar sesi lain. Role/anak bukan profil. |
| Lupa password / Master terakhir hilang | Reset OTP email dipilih D-25; sesudah password sukses modal wajib lalu login biasa, tanpa auto-login. Fallback mailbox hilang/last Master belum dipilih; tidak ada reset administratif bebas. Aktivasi menunggu parameter/provider/integrasi dan bukti. |
| Disable/delete | Master menonaktifkan/arsip akun sesuai histori; rencana menolak login/request berikutnya dan mencabut sesi. Nilai, rapor dan audit tidak ikut terhapus. Akun nonaktif tidak diaktifkan kembali oleh recovery diam-diam. |
| Role/penugasan/relasi dicabut | Permission diperiksa ulang pada permintaan berikutnya; sesi/socket lama tidak mempertahankan hak. Detail invalidasi pada dokumen sesi. |

Keamanan perubahan kredensial memerlukan audit domain metadata yang atomik, tanpa password/hash/token. Login berhasil/gagal, logout, throttling dan sesi kedaluwarsa adalah event keamanan teknis bila perlu dicatat, bukan CUD domain palsu atau pemicu inbox rekursif. Penerima metadata keamanan dibatasi; tidak menjadi riwayat yang bebas dibaca semua role.

## Abuse controls and privacy/accessibility

- Usulan throttling berbasis username yang dinormalisasi dan sumber jaringan, dengan respons umum untuk akun tidak ada/salah password/nonaktif. Batas angka, durasi dan penyimpanan limiter belum dipilih; hindari lockout permanen yang dapat dipicu penyerang.
- Jangan membocorkan keberadaan akun melalui pesan, status, URL atau jalur waktu yang mudah dibedakan. Uji penanganan akun tidak ada dengan library/framework; jangan membuat delay tidur tetap sebagai bukti perlindungan timing.
- Demo publik memakai HTTPS, sesi cookie aman dan perlindungan request forgery sesuai konfigurasi final; localhost bukan alasan mematikan perlindungan demo. Tidak membuka CORS untuk proyek lain. Detail ada pada kontrak API/sesi.
- Login, password change dan re-verification memakai label/fokus keyboard jelas, pesan selain warna, autocomplete yang sesuai, serta dukungan password manager dan paste. Tidak mempertahankan password pada flash, localStorage, URL, trace/screenshot atau log.
- Foto/username/kontak sesuai allowlist; email baru tidak otomatis verified/recovery dari perubahan profil. Master attests saat membuat akun sesuai D-25, perubahan kontak membutuhkan kontrak tersendiri. OTP/bukti reset tidak boleh mengubah target user/role.
- Login/sesi bukan jaminan socket lama terputus seketika; subscription, dispatch dan fetch inbox tunduk pada lifecycle sesi/izin. Inbox in-app bukan jalur pengiriman kredensial atau peringatan kompromi yang dapat diandalkan sendiri.

| ID | Hasil uji yang diharapkan | Aktual |
| --- | --- | --- |
| AUTH-01 | Credential valid akun aktif menghasilkan sesi baru; password salah, akun tidak ada dan nonaktif ditolak dengan pesan umum. | NOT RUN |
| AUTH-02 | Enumerasi/throttling/forgery serta logout/fixation mengikuti kontrak CT-02 dan SES; tidak mengulang mutasi saat sesi habis. | NOT RUN |
| AUTH-03 | Profil/payload/role rangkap sesuai scope; OTP profil invalid/purpose salah/proof usang, foto invalid atau field terlarang tidak mengubah akun. | NOT RUN |
| AUTH-04 | Kebijakan password yang kelak dipilih menangani batas panjang/Unicode/blocklist tanpa truncation; password/hash/token tidak muncul pada respons, audit atau telemetry. | NOT RUN |
| AUTH-05 | D-25 temporary password gated server, OTP hash/purpose/atomic proof, modal sesudah commit yes/no benar; reset login biasa/profil sesi kini bertahan. Nonaktif/revoke tetap SES/AZ. | NOT RUN |

ID AUTH mengelompokkan ekspektasi, bukan kewajiban membuat suite baru. Gunakan kembali bukti CT/AC/SES/AZ yang benar-benar membuktikan aturan tersebut.

## Approval owner, libraries and environment restrictions

Pemilik proyek memilih kebijakan password, initial credentials, recovery dan batas operasi; pengembang/reviewer implementasi belum ditugaskan. Usulan pustaka Laravel/Fortify tidak berarti seluruh fitur bawaan diaktifkan: register, email reset, remember-me atau metode lain yang tidak dipilih tidak boleh terbuka karena default scaffolding.

Instruksi sekarang hanya blueprint. Dev/CI/demo memakai akun/DB/credential terisolasi; tidak ada login nyata, pengiriman email/SMS, provisioning provider atau pembuatan kredensial dari tugas dokumentasi ini. Tes hanya pada resource terotorisasi dengan data sintetis. Tidak ada hasil scan, review implementasi independen atau persetujuan rilis yang diberikan.

Status penerimaan: D-25 dikonfirmasi untuk alur, email verified administratif oleh Master dan Fouladgar. D-23/D-24/current_password wajib/usulan selalu logout digantikan. Parameter TTL/limit/hash, provider pengiriman, middleware fisik dan fallback email hilang/bootstrap belum final; tidak ada instalasi, runtime atau pengiriman. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).