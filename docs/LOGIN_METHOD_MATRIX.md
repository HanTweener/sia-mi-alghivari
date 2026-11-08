# Login method matrix — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status **PROPOSED / blueprint**; tidak ada metode yang sudah dipasang/diaktifkan. Matriks membatasi core pada login lokal, bukan daftar fitur yang harus ditambahkan. Implementasi **NOT IMPLEMENTED**, semua pengujian **NOT RUN**.

Acuan: [kebutuhan autentikasi](AUTHENTICATION_REQUIREMENTS.md), [sesi](SESSION_TOKEN_SECURITY_SPEC.md), [pemulihan](RECOVERY_AND_STEPUP_PLAN.md), [PRD](PRD.md), [API](API_CONTRACT.md) dan [stack](TECH_STACK.md).

## Method | User population | Risk/assurance | Enabled/N/A | Library/provider | Recovery | Required tests | Owner

| Method | User population | Risk/assurance | Enabled/N/A | Library/provider | Recovery | Required tests | Owner |
| --- | --- | --- | --- | --- | --- | --- | --- |
| LM-01 · Username/password lokal | Semua role/rangkap | Password sementara hanya forced change, password normal satu faktor | Dipilih core, belum aktif | Laravel sesi/Fortify sesuai kit | D-25 reset OTP email -> modal -> login biasa; profil OTP dan sesi kini tetap | AUTH/REC/SES; NOT RUN | Pemilik proyek |
| LM-02 · Email/password sebagai alternatif username | Tidak dipilih untuk login | Email wajib verified administratif oleh Master D-25, tidak mengubah identifier login | N/A login email | Tidak dikonfigurasi | Username/email boleh lookup reset; kirim ke email tercatat | Tolak login email bila bukan username sah; NOT RUN | Pemilik proyek |
| LM-03 · OTP email reset/profil; magic-link/OTP login tetap N/A | Akun aktif dengan email tercatat | Proof terbatas tujuan; email Master-attested bukan jaminan mailbox | OTP reset/profil dipilih D-25, belum aktif; login OTP N/A | Fouladgar + Laravel Mail/queue; provider belum dipilih | Reset login biasa; profil current session tetap, modal pilihan sesi wajib | REC-01/03/04/05/07/08; AS-08/19/20/21; NOT RUN | Pemilik proyek |
| LM-04 · SMS/WhatsApp OTP | Tidak dipilih | Nomor kontak bukan bukti kendali kanal; menambah biaya dan ketergantungan | N/A | Tidak ada provider dipilih | Bukan fallback diam-diam | N/A provider/OTP | Pemilik proyek |
| LM-05 · Social sign-in | Tidak dipilih | Login provider dan account-linking menambah lifecycle identitas | N/A Google/GitHub/Apple/provider lain | Tidak ada OAuth/OIDC app diprovisikan | Tidak ada akun tertaut untuk recovery | N/A state/nonce/PKCE/provider proof pada core | Pemilik proyek |
| LM-06 · Delegated API connect, bukan login | Tidak ada kebutuhan akses API pihak ketiga | Grant eksternal terpisah dari identitas login dan izin akademik | N/A | Tidak ada token/scopes/provider connect | Tidak menjadi pemulihan akun lokal | N/A consent/revoke grant luar | Pemilik proyek |
| LM-07 · Passkey/WebAuthn | Tidak dipilih | Membutuhkan enrollment, pengikatan credential dan recovery tersendiri | N/A pada tahap ini | Tidak ada library/device enrollment dipilih | Tidak ada passkey cadangan yang diasumsikan | N/A authenticator challenge/recovery | Pemilik proyek |
| LM-08 · MFA | Tidak dipilih | Penguatan dapat dibahas untuk risiko nyata; konfirmasi ulang password tetap satu faktor | N/A; tidak menambah TOTP/recovery codes | Tidak ada faktor kedua dipasang | Tidak ada reset MFA pada core | N/A faktor kedua; jangan mengklaim fresh password sebagai MFA | Pemilik proyek |
| LM-09 · Enterprise SSO/SAML/OIDC | Sekolah tunggal tanpa IdP institusi yang dipilih | Federation/provisioning terpisah dari role tetap aplikasi | N/A | Tidak ada IdP dipilih | Tidak mengandalkan admin IdP yang belum ada | N/A federation/provisioning | Pemilik proyek |
| LM-10 · Konfirmasi password terbaru, bukan metode login baru | Pengguna yang melakukan tindakan sensitif tertentu | Usulan re-verification akun sesi; tidak memberi grant dan tidak melindungi bila password juga dicuri | PROPOSED step-up, belum aktif; window/transport terbuka | Kandidat fasilitas password confirmation framework yang diverifikasi | Bukan recovery; tidak dapat dipakai pengguna tanpa sesi/kredensial | REC-01/02 dan SES; NOT RUN | Pemilik proyek; rincian sebelum aktivasi |

JWT bearer bukan metode login yang ditambahkan oleh matriks ini. Core memakai sesi server; remember-me diusulkan OFF pada tahap awal sesuai dokumen sesi, bukan default framework yang dianggap disetujui. Tidak ada registrasi publik atau akun siswa mandiri.

Q6.1 Master akun wali dahulu lalu D biodata/link anak tetap. D-25 menggantikan D-23/D-24: seluruh akun baru email langsung verified oleh Master, password sementara emailed dengan URL biasa, forced change dan modal wajib. Existing tambah role tidak direset. Tidak ada public signup; bootstrap dan email hilang belum dipilih. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

## Social sign-in and delegated API connect must be distinct rows

LM-05 dan LM-06 sengaja dipisah walaupun keduanya N/A: sign-in membuktikan identitas melalui provider; connect memberi akses API provider dengan consent tersendiri. Tidak ada fitur sosial/connect yang diaktifkan karena starter kit atau framework menyediakan contoh.

Bila kebutuhan berubah, pemilik memilih populasi/hasil nyata dahulu; barulah desain identity binding, token lifecycle, recovery, izin provider dan tes yang relevan diperbarui. Tanpa kebutuhan itu, tidak membuat route callback, tabel account-link, secret provider, layar consent, atau pengiriman pesan.

Hasil yang diharapkan sekarang: LM-01 untuk login username/password dan LM-03 untuk OTP reset/profil termasuk core D-25; LM-10 tetap usulan step-up administratif, bukan OTP sign-in umum. Parameter/provider/integrasi belum final; fitur N/A tidak terbuka dari scaffolding. Hasil implementasi/pengujian aktual belum tersedia. Tidak ada persetujuan seluruh metode/kontrol atau bukti autentikasi aman dari pengisian matriks ini.
