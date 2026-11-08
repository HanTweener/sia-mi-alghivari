# Kontrak HTTP dan integrasi - SIA MI Alghivari

Versi dokumen: 0.4, 30 Oktober 2026. Pemilik kontrak: pemilik proyek. Status: **PROPOSED / blueprint**; seluruh route, payload dan batas teknis di bawah adalah rancangan, bukan endpoint yang sudah tersedia. Implementasi **NOT STARTED**, pengujian kontrak **NOT RUN**. Pilihan Laravel/Inertia/MySQL mengikuti status persetujuan pada TECH_STACK.

Acuan: [PRD](PRD.md), [fitur F-01..11 dan AC](FEATURES/FEATURE_SPEC_TEMPLATE.md), [matriks izin](ROLE_PERMISSION_MATRIX.md), [akses objek](RESOURCE_ACCESS_POLICY.md), [model data](DATA_MODEL.md), [arsitektur](ARCHITECTURE.md) dan [notifikasi](NOTIFICATION_REQUIREMENTS.md). Dokumen ini mengonkretkan transport dan payload, tanpa menyelesaikan Q6/visual Q7/lifecycle diam-diam.

## 1. Bentuk kontrak dan autentikasi

Satu aplikasi first-party dengan routing/sesi Laravel dan halaman React melalui Inertia. Tidak menambah backend API terpisah, prefix /api/v1, JWT, registrasi publik, GraphQL atau OpenAPI untuk seluruh halaman. Kontrak JSON kecil dapat dijadikan schema mesin kelak bila membantu tes; tidak membuat file schema kosong sekarang.

Base URL mengikuti APP_URL lingkungan; route di bawah relatif ke origin aplikasi. Dev memakai localhost yang dikonfigurasi; demo memakai HTTPS pada domain yang belum diaktifkan. Host Reverb/WSS terpisah dari origin HTTP tetapi autentikasi channel tetap di aplikasi. [ENVIRONMENT_SPEC.md](ENVIRONMENT_SPEC.md).

Gunakan cookie sesi Secure pada demo, HttpOnly untuk cookie sesi, dan scope host aplikasi. Identitas/role/actor berasal dari sesi/database, bukan query/header role dari klien. Akun aktif diperiksa pada setiap request; logout membatalkan sesi dan klien membersihkan subscription/state sensitif. Hak objek diperiksa lagi saat baca, simpan, job dan download; capability props bukan grant.

Mutasi browser menggunakan perlindungan request forgery Laravel yang dikonfigurasi dan dukungan XSRF cookie/header klien. Pada Laravel 13, pemeriksaan origin same-origin HTTPS dapat mendahului fallback token; jangan menganggap setiap request tanpa token otomatis 419. Uji origin/token sesuai konfigurasi final. Tidak mengaktifkan CORS lintas proyek/domain lain untuk kebutuhan ini. [Laravel CSRF](https://laravel.com/framework/docs/13.x/csrf#origin-verification).

## 2. Transport, sukses dan error

| Jenis | Request | Sukses | Error/validasi |
| --- | --- | --- | --- |
| H: halaman | GET browser awal atau navigasi X-Inertia dari adapter | HTML awal; navigasi memakai page object Inertia berisi component/props/url/version | Redirect login jika sesi habis; halaman error Inertia 403/404/429/500/503 sesuai sebab, tanpa stack trace. |
| M: form/command Inertia | POST/PATCH/PUT/DELETE melalui adapter; intent dan payload allowlist | Redirect ke GET setelah commit; usulkan 303 untuk mutasi agar follow-up GET, mengikuti adapter resmi | Validasi/domain conflict: redirect kembali dengan errors per field/_form dan kode aman di props; tidak dipaksa menjadi 422 JSON. |
| J: JSON pendukung | Fetch/Axios dengan Accept application/json, cookie sesi, tanpa X-Inertia; mutasi memakai proteksi forgery | 200 dengan shape endpoint atau 204 tanpa body | 401 guest, 403 forbidden, 404 objek di luar scope/tidak ada, 422 validasi, 409 konflik bisnis bila berlaku, 429/503 bila dibatasi/tidak tersedia. |
| F: file privat | GET browser biasa/tautan HTML, bukan Inertia router | 200 application/pdf + Content-Disposition attachment setelah policy/status/file lolos | Tidak ada redirect ke path storage publik; 404 untuk file/objek tak dapat dibuka, 503 jika penyimpanan sementara gagal. Tidak ada PDF palsu berstatus 200. |
| B: auth broadcaster | POST /broadcasting/auth oleh Echo, sesi aplikasi | 200 format adapter broadcaster, bukan envelope data umum | Guest/denial sesuai middleware/adapter yang dikonfigurasi; usulkan guest401 JSON dan scope403. |

Untuk Inertia, 409/X-Inertia-Location merupakan kontrol protokol seperti asset mismatch/redirect, bukan optimistic concurrency bisnis. Usulkan konflik domain berupa redirect-back dengan errors.version atau errors._form serta props.form_error.code = stale_version/state_conflict/source_changed; isian aman dipertahankan dan pengguna meninjau ulang. Jangan memanggil Inertia::location untuk konflik nilai. [Protocol](https://inertiajs.com/docs/v3/core-concepts/the-protocol), [redirect](https://inertiajs.com/docs/v3/the-basics/redirects), [validasi](https://inertiajs.com/docs/v3/the-basics/validation).

JSON error usulan: `{"message":"Data tidak dapat disimpan.","code":"validation_failed","errors":{"entries.1.score":["Nilai tidak valid."]},"request_id":"req-demo-001"}`. errors hanya untuk field/baris yang boleh diketahui pemanggil. 404 tidak mengungkap apakah ID milik pengguna lain ada. 403 untuk fungsi yang terlarang tanpa perlu mengungkap objek. Kesalahan login memakai pesan umum, bukan enumerasi akun.

Fallback token mismatch Laravel dapat menghasilkan 419; form dapat diarahkan kembali dengan pesan sesi kedaluwarsa tanpa mengulang mutasi, JSON menerima error terstruktur. Jika origin-only dipilih, kegagalan origin dapat 403. Rate limit429 menyertakan petunjuk retry/Retry-After bila tersedia. Error500/503 membawa request_id aman tanpa SQL/trace/secret. Timeout jaringan bukan bukti transaksi batal; jangan auto-retry semua POST.

## 3. Tipe, query, proyeksi dan versi

- ID dipertukarkan sebagai string opaque, walau PK internal BIGINT. Jangan parsing ke number JavaScript, menganggap ID berurutan sebagai hak, atau memakai NIS sebagai primary key.
- Desimal memakai string basis10, titik dan tanpa persen/pemisah ribuan: score "0.00", weight_percentage "20.00". score null berarti blank pada proyeksi/input eksplisit; tidak sama dengan nol. Q7.2 menetapkan score input 0-100 inklusif/maksimal dua digit pecahan; integer/satu digit pecahan sah. Rentang invalid atau pecahan berlebih gagal validasi field/baris sebelum cast/insert, tanpa pembulatan otomatis. Presisi hasil hitung/pembulatan akhir dan input kkm/weight_percentage tidak diputuskan melalui batas score ini.
- Tanggal kegiatan YYYY-MM-DD, jam jadwal HH:mm, weekday integer1..7; waktu kejadian ISO8601 UTC. D-29: satu school_timezone untuk kalender/tampilan, pilihan WIB/Asia/Jakarta, WITA/Asia/Makassar, WIT/Asia/Jayapura. DATE/TIME tidak digeser, host/browser tidak menentukan zona; nilai awal belum dipilih.
- version adalah integer nonnegatif teknis untuk induk yang diedit, terpisah dari revision policy/rapor dan asset version Inertia. actor, finalized_by/at, published_by/at, snapshot, final_score, recipient dan storage path ditetapkan server.
- Query daftar usulan: page>=1, per_page default25/maksimum100, q, sort serta filter allowlist per daftar. Batas ini proposal kontrak teknis, bukan benchmark kapasitas. Filter/relasi anak/kelas diterapkan sebelum count/pagination; sort stabil dengan ID pembeda. Filter tak dikenal/format salah ditolak, bukan diteruskan sebagai SQL.
- Proyeksi list: items dan pagination {current_page, per_page, total, last_page}; count hanya objek yang berhak. Query/form tidak menerima include=* atau daftar kolom bebas. Shared props hanya auth minimum, konteks, kemampuan UI dan flash/errors yang diperlukan.
- Payload PATCH hanya mengubah field/entry yang dikirim. Field sensitif/non-allowlist ditolak, bukan diabaikan sehingga tampak berhasil. Metadata internal form seperti CSRF/method override ditangani framework, bukan model mass assignment.

Nama field, component Inertia dan route name final dirawat bersama kontrak ini saat implementasi; perubahan yang mematahkan props/payload harus memperbarui frontend/tes pada rilis sama. Tidak ada janji API publik berversi untuk klien luar.

## 4. Katalog operasi

Semua operasi berikut rancangan. T berarti bentuk kontrak dapat dirinci dari kebutuhan sekarang; TERBUKA berarti aktor/field/transisi tertentu masih terbuka dan tidak boleh diaktifkan otomatis. Semua T tetap memerlukan keputusan stack dan implementasi yang diotorisasi.

Notasi scope: M=Master, A=Akademik, D=Data Entry, G=guru mapel aktif, W=wali kelas aktif, P=principal, O=orang tua/wali. R=proyeksi baca menurut scope/field/keadaan pada matriks izin, bukan semua pengguna membaca semua objek. MA berarti M atau A. Peran rangkap memperoleh grant yang cocok, tanpa bypass audit/status/approval. D tidak memiliki D(elete) domain, konfigurasi atau publikasi; notasi D pada kolom hak selalu nama role.

### 4.1 Akun, profil dan konfigurasi (F-01/F-02)

| ID | Method dan route | Hak, payload utama dan batas | Transport / status |
| --- | --- | --- | --- |
| HTTP-01 | GET /login; POST /login; POST /logout | Login username/password; logout sesi sendiri. Tidak ada register publik. Kontrak keamanan login mengikuti framework, tanpa password pada log | H/M; T |
| HTTP-02 | GET /users; GET /users/{user}; POST /users; PATCH /users/{user}; DELETE /users/{user} | M akun/role/aktivasi; create name/username/email wajib, Master-attested verified, sistem password sementara/email. Tidak menerima password bebas atau mengembalikan credential. Existing expected_version, tanpa reset saat tambah role. D hanya biodata/link setelah Q6.1 | H/M; T blueprint D-25, implementasi NOT STARTED |
| HTTP-03 | PUT /users/{user}/roles; PATCH /users/{user}/activation | M saja; roles dari set tetap atau is_active boolean, expected_version. Jabatan principal ditetapkan melalui roles, tidak lewat edit biodata | M; T |
| HTTP-04 | GET /profile; PATCH /profile; PUT /profile/password | Diri sendiri allowlist; PUT password/password_confirmation setelah proof OTP profile_password_change server. Bukan current_password/target_user_id/role/anak/status/verified. Commit mengembalikan state modal pending, bukan langsung selesai flow | H/M; T blueprint |
| HTTP-05 | POST /profile/avatar; DELETE /profile/avatar; GET /users/{user}/avatar | Upload multipart avatar, penghapusan avatar sendiri, baca foto sesuai field scope. Batas konten/ukuran ditentukan sebelum aktif; bukan file arbitrary | M/F sesuai jenis gambar; T |
| HTTP-06 | GET /system-settings; PUT /system-settings/{key}; DELETE /system-settings/{key} | Master; key/value allowlist dan expected_version. school_timezone wajib string IANA Asia/Jakarta/Asia/Makassar/Asia/Jayapura, UI WIB/WITA/WIT; konfirmasi dampak perubahan. PUT versi0 untuk konfigurasi pertama, tidak ada default host; DELETE key wajib ditolak. Proyeksi timezone pada halaman bukan akses admin settings | H/M; T; D-29 dinamis tanpa restart |

GET foto memakai Content-Type gambar sah/disposition aman. Direktori profesional lewat resource kerja, bukan membuka /users. D-25 menggantikan D-23/D-24, URL awal biasa dan forced-change gate berlaku server pada semua halaman/API/file/inbox/channel. Master tidak membaca password dari list/detail/props. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

D-25 keluarga operasi AUTH-PW di bawah adalah blueprint baru, bukan endpoint aktif. Cookie/CSRF/limiter/no-store/error netral tetap; OTP, password/proof tidak muncul di URL/flash/audit/inbox. GET tidak mengonsumsi credential atau mengubah password. Field verified/credential/generation/pending tidak boleh disisipkan lewat profil.

### 4.1.1 Lifecycle password D-25

ID AUTH-PW adalah kontrak tambahan, tidak mengubah HTTP-01..28/RES/CT yang sudah dirujuk. Nama route/DTO rancangan perlu dibuktikan pada implementasi; OTP package tidak menyediakan seluruh lifecycle ini.

| ID | Method/route rancangan | Input dan hasil yang diharapkan |
| --- | --- | --- |
| AUTH-PW-01 | POST /users/{user}/temporary-password | Master, akun baru belum selesai setup, expected_version; sistem reissue acak/email, credential/konteks awal lama dibatalkan; tidak menerima/mengembalikan password plaintext. Cooldown/TTL OPEN. |
| AUTH-PW-02 | GET /password/first-change; POST /password/first-change | Forced-change session saja; password/password_confirmation. Hash/proof/flag/credential revision/pending/audit atomik -> halaman modal; tidak menerima user_id dari klien. Normal domain ditolak sebelum gate selesai. |
| AUTH-PW-03 | GET /forgot-password; POST /forgot-password | identifier username/email -> pesan umum dan request context; OTP hanya alamat tersimpan dari akun aktif yang cocok. Tidak mengembalikan keberadaan user/email penuh atau mengganti credential/status/sesi. |
| AUTH-PW-04 | POST /password/otp/verify; POST /password/otp/resend | Kode OTP + context browser server, purpose/target bukan input tepercaya. Verify atomik -> proof terbatas; resend membatalkan challenge/proof lama tujuan sama, rate limited. Tidak login otomatis. |
| AUTH-PW-05 | GET /reset-password; POST /reset-password | Proof reset di server + password/password_confirmation; password/proof/pending/audit atomik -> modal pending. Tidak ada token secret di URL, reset tidak membuat sesi login. |
| AUTH-PW-06 | POST /profile/password/otp | Sesi normal aktif, destination dari akun; purpose profil server. Verify/resend reuse AUTH-PW-04 tanpa mencampur tujuan; completion PUT HTTP-04. |
| AUTH-PW-07 | GET /password/session-choice; POST /password/session-choice | Proof post-change terbaru atau fresh login sah untuk resume, operation_id publik bukan grant. POST logout_other_sessions boolean wajib, tanpa default implisit; operasi/revisi usang ditolak. Reset true mencabut semua sesi login lalu login biasa; profile true mencabut semua sesi lain dan mempertahankan kini, false mempertahankan sesi sah. |

Server menyimpan pending choice bersama password, bukan menunda password sampai modal. Refresh/response hilang tidak me-replay POST password. Form konfirmasi salah dapat diperbaiki selama proof sah; submit duplicate/stale op/current account berubah ditolak tanpa efek tambahan. Jika proof post-change hilang, login biasa memakai password baru dapat melanjutkan modal pending. Sesi lama tanpa proof credential terbaru tidak boleh memilih; current profile session diperbarui. Respons aman cukup status/stage/operation opaque/next route; tidak ada OTP/password/hash pada props.

Perubahan password/inbox/audit DB atomik, tetapi email setelah commit best effort/terpantau. Failed mail/enqueue bukan rollback password atau jaminan delivered. Batas TTL/length/limit/provider/retry, pending-proof expiry dan middleware fisik masih OPEN. AUTH-PW mengikuti AS-08/19/20/21, REC-01/03/04/05/07/08 dan API-T45..51.

### 4.2 Resource akademik dan biodata (F-03)

Untuk setiap resource pada tabel, bentuk standar ialah GET collection/GET {id} untuk halaman baca, POST collection untuk tambah, PATCH {id} untuk ubah allowlist dengan expected_version, DELETE {id} untuk hapus domain bila diizinkan. Tidak menerima status final/role/actor melalui CRUD generik. DELETE yang mengenai histori menggunakan arsip/pembatalan atau ditolak; tanpa cascade nilai/rapor/audit.

Grant dinilai dari efek, bukan HTTP verb saja. D tidak boleh mengarsipkan/nonaktifkan data operasional, melepas hubungan, atau menghapus skor melalui field PATCH yang secara efektif merupakan DELETE. Pada subject, is_active=false yang mengarsipkan mapel memerlukan grant hapus domain MA; D dibatasi field C/U yang tidak menghapus akses/histori. Aksi profil sendiri yang sudah diizinkan tetap memakai grant mandiri.

| ID | Collection | Field create/ubah yang dirancang | Hak tulis dan batas |
| --- | --- | --- | --- |
| RES-01 | /students | nis, name, birth_date opsional; status melalui aksi lifecycle yang sah | MA C/U/D; D C/U. GET R; pergantian tahun/kelulusan tidak lewat PATCH status. |
| RES-02 | /teachers | user_id pada profil baru, staff_number opsional; user harus sudah berhak sebagai teacher | M; field biodata profesional yang dapat diubah A masih TERBUKA-Q6. GET R; tidak membuat role lewat profile teacher. |
| RES-03 | /guardians | Baca/perbarui biodata wali yang akun/role guardian dan aktivasinya sudah disiapkan M menurut Q6.1 | D melengkapi biodata serta hubungan siswa setelah akun tersedia; detail field MA/D tetap TERBUKA-Q6. Tidak ada POST profil wali pending atau aktivasi/role lewat biodata. Pembuatan akun melalui HTTP-02; Q6.2/D-25 memilih password sementara emailed dan forced change, sedangkan parameter/provider/integrasi/bootstrap/fallback masih terbuka. |
| RES-04 | /academic-years | name, starts_on, ends_on | MA, D hanya R konteks. Awal tidak melewati akhir. |
| RES-05 | /terms | academic_year_id, semester1/2, starts_on/ends_on; dibuat draft | MA, status active/closed melalui command eksplisit. |
| RES-06 | /classrooms | term_id, grade_level1..6, section | MA C/U/D; D C/U. Tidak menerima teacher_id/homeroom_teacher_id. Parent yang telah dipakai tidak dipindah lewat PATCH. |
| RES-07 | /subjects | code, name, is_active | MA C/U/D; D C/U tanpa menyisipkan konfigurasi bobot. |
| RES-08 | /enrollments | student_id, classroom_id, term_id, starts_on, ends_on nullable; date range/reason/version pada update | MA; D penempatan tetap TERBUKA-Q6. Unik siswa/term termasuk ended, range akhir eksklusif. Tidak PATCH kelas/term sebagai transfer; backdate mengubah roster final atau membuat nilai published tidak lagi berlaku ditolak. |
| RES-09 | /schedule-slots | class_subject_id, weekday, starts_at, ends_at | MA; bentrok kelas/guru efektif menurut rentang tanggal dicek pada create/update/handover. D R konteks; jadwal tidak terikat ID guru lama. |
| RES-10 | /class-subjects | classroom_id, subject_id | MA mengelola identitas mapel kelas; unique pasangan, parent yang dipakai tidak dipindah/dihapus berantai. Tidak memberi D hak aktivasi baru; GET menurut scope. |

Relasi wali pada akun existing: GET /students/{student}/guardians (R field minimum), POST rute tersebut dengan guardian_user_id/relationship (MA/D sesuai pengelolaan data siswa), PATCH /students/{student}/guardians/{guardian} untuk relationship, DELETE pasangan hanya MA. Role/relasi terkini diverifikasi server. Kewenangan linking tidak mencakup pembuatan akun guardian aktif. Seluruh parent/nested ID harus benar-benar berhubungan, bukan sekadar valid secara individual.

| ID | Method dan route lifecycle | Hak dan kontrak | Status |
| --- | --- | --- | --- |
| HTTP-07 | POST /terms/{term}/activate; POST /terms/{term}/close | MA, expected_version dan expected_active_term_id (boleh null saat belum ada active). Aktivasi global satu term; close memvalidasi keadaan dan efek akademik | M; T |
| HTTP-08 | GET/POST /teaching-assignments; GET/PATCH/DELETE /teaching-assignments/{assignment}; POST /teaching-assignments/{assignment}/replace | MA create class_subject_id/teacher_id/starts_on/ends_on, tanpa overwrite asal yang dipakai. Replace teacher_id/effective_on/reason/expected_version/expected_current_assignment_id -> close interval lama/new row/check jadwal/batas delegasi/audit/inbox atomik. Grant G memakai assignment efektif parent saat ini | H/M; D26 blueprint, koreksi final/undo bukan PATCH biasa |
| HTTP-09 | GET/POST /homeroom-assignments; GET/PATCH/DELETE /homeroom-assignments/{assignment}; POST /homeroom-assignments/{assignment}/replace | GET R; MA. Create classroom_id/teacher_id, replace teacher_id/reason/expected_version; tutup lama+buat baru atomik, satu aktif dan histori tetap | H/M; T |
| HTTP-10 | /assignment-requests serta command approve/reject; /data-entry-delegations serta revoke | Q6.3: MA pemberi/pencabut delegasi grade_entry/attendance_entry dengan petugas/scope/masa berlaku/alasan. D hanya C/U draft berdelegasi; tanpa self-activation lewat hak D. Reviewer aktivasi usulan penugasan D serta mekanisme permintaan/perubahan delegasi masih terbuka | Aktor grant/revoke delegasi dikonfirmasi; detail kontrak/route family dan approval assignment-requests TERBUKA-Q6, belum diaktifkan |

D26/D27 mengganti status terbuka AR04/05. PATCH tidak memindah identitas parent historis atau menyamar sebagai guru asal. Handover biasa memakai replace dengan interval tidak kosong/overlap, tanggal cutover milik guru baru; same-date second handover yang membentuk interval nol ditolak. Homeroom tetap mekanisme terpisah. Masuk/keluar siswa bertanggal diizinkan sesuai RES08; transfer antarkelas term sama dan koreksi struktur/roster final ditunda. Koreksi skor/status mengikuti D-28. [Aturan akademik](ACADEMIC_FLOW.md).

### 4.3 Policy global dan aktivitas mapel (F-04/F-05/F-06)

| ID | Method dan route | Hak dan payload | Transport / status |
| --- | --- | --- | --- |
| HTTP-11 | GET/POST /scoring-components; GET/PATCH/DELETE /scoring-components/{component} | GET sesuai konteks R; MA menulis code/name/source_type/sort_order/is_active. Code/source_type yang sudah dipakai tidak diganti, hapus tidak menghilangkan histori | H/M; T |
| HTTP-12 | GET/POST /grading-policies; GET/PATCH /grading-policies/{policy}; DELETE /grading-policies/{policy} | MA; kkm dan seluruh components. PATCH/DELETE hanya draft belum dipakai; pernah aktif immutable, perubahan menjadi revisi baru. GET konfigurasi tidak dibuka luas ke wali | H/M; T; Q7.5 KKM/bobot0-100 inklusif, maksimal dua desimal; Q7.3/04 diterima |
| HTTP-13 | POST /grading-policies/{policy}/activate | MA; expected_version, expected_active_policy_id dan expected_active_policy_revision (keduanya null bila belum ada active). Validasi jumlah decimal tepat100 tanpa epsilon/pembulatan total, tidak dua active, preview stale ditolak | M; T |
| HTTP-14 | GET/POST /class-subjects/{classSubject}/attendance-sessions; GET/PATCH/DELETE /attendance-sessions/{session} | MA/G efektif saat ini atau D delegasi assignment efektif C/U draft. Create meeting_number/held_on/topic, server memilih source assignment/created_by. Unique nomor per class_subject, asal bukan grant. Tidak reset nomor saat guru berganti | H/M; T blueprint D26/27 |
| HTTP-15 | PATCH /attendance-sessions/{session}/records; POST /attendance-sessions/{session}/finalize | expected_version dan expected_roster_version; entries enrollment_id/status. Roster eligible pada held_on, stale roster mengembalikan error form roster_changed dan wajib review; finalized set records tepat lengkap dibekukan. MA/G finalize, D hanya C/U draft berdelegasi dan tanpa clear/delete existing | M; T blueprint D27 |
| HTTP-16 | GET/POST /class-subjects/{classSubject}/assessments; GET/PATCH/DELETE /assessments/{assessment} | MA/G efektif parent atau D delegasi C/U draft; create scoring_component_id/title/assessed_on. Asal/creator server tetap saat guru baru melanjutkan draft; published bukan edit biasa. Enrollment kelas/term dan tanggal berlaku untuk skor diperiksa | H/M; T blueprint D26/27 |
| HTTP-17 | PATCH /assessments/{assessment}/scores; POST /assessments/{assessment}/publish | Scores expected_version/entries; MA/G/D delegasi pada draft. Publish hanya MA/G, blank sebagian boleh | M; T; skor input Q7.2 dan pembulatan hasil Q7.3 diterima; pembanding KKM Q7.4 diterima; presisi konfigurasi Q7.5 diterima |
| HTTP-18 | POST /assessments/{assessment}/corrections; POST /attendance-sessions/{session}/corrections | D-28: MA, atau G current parent hanya term terbuka; closed MA saja. expected_version, reason, entries; score enrollment_id/score decimal string atau null(clear); absensi enrollment_id/status H/I/S/A existing. Koreksi atomik, status aktivitas tetap, before/after audit+inbox dan penahanan rapor terkait | M; T D-28; tanpa reopen/PATCH status bebas |

Delegation_id bila dikirim hanya petunjuk; server memastikan pemilik, capability, assignment, masa berlaku dan pencabutan saat simpan. Aktor selalu petugas sebenarnya. Klien tidak mengirim teacher_id untuk menyamar, status final, calculated_attendance, bobot atau hasil akhir pada payload nilai/absensi.

### 4.4 Perkembangan dan rapor (F-07/F-08)

| ID | Method dan route | Hak dan kontrak | Transport / status |
| --- | --- | --- | --- |
| HTTP-19 | GET /children; GET /children/{student}/progress; GET /children/{student}/homeroom-contact | O melalui relasi anak aktif; filter term_id/subject_id/component_id/from/to/page. Aktivitas published/finalized, rekap sementara, kontak profesional W; tanpa syarat rapor | H; T |
| HTTP-20 | GET /children/{student}/report-cards; GET /classrooms/{classroom}/report-preview; POST /report-cards; GET/PATCH /report-cards/{report} | Daftar anak hanya O terkait, filter term_id/revision/page, hanya published/current aktif; notice netral untuk semester pernah terbit yang held/revoked. Preview/create draft MA/W binaan; enrollment_id/term_id dan expected_latest_revision integer (0 bila belum ada) untuk create. Detail report GET R menurut scope/status; O hanya published/current aktif anak. PATCH hanya M/W binaan aktif saat draft, expected_version dan homeroom_note string/null (null mengosongkan catatan opsional); field lain ditolak, batas teks harus ditetapkan sebelum endpoint aktif | H/M; T; D26 satu item per class_subject dan D27 roster frozen, runtime belum verified |
| HTTP-21 | POST /report-cards/{report}/finalize; POST /report-cards/{report}/publish | MA/W binaan; finalize expected_version, expected_policy_id dan expected_source_version dari preview, publish expected_version. Server menghitung snapshot, bukan menerima final_score | M; T; susunan Q7.1 disepakati; aturan numerik Q7.2-Q7.5 diterima; detail render belum final |
| HTTP-22 | POST /report-cards/{report}/prepare-file; GET /report-cards/{report}/pdf | Prepare/retry MA/W, finalized/current dan expected_version; efek file di luar transaksi snapshot. Download R dengan syarat publikasi untuk O dan scope mapel/kelas bagi staf | M/F; T; renderer Dompdf/wrapper 3.1.x dipilih pada D-17, susunan Q7.1 disepakati; aturan numerik Q7.2-Q7.5 diterima; detail render belum final |
| HTTP-23 | POST /report-cards/{report}/revisions; POST /report-cards/{report}/revoke | D-28 MA/W binaan aktif termasuk term closed; reason/expected_version, revisi juga expected_latest_revision. Revisi per siswa dari latest immutable versi pada lineage yang sama; policy/calculation asli. Revoke finalized/published non-superseded; tidak menimpa isi snapshot/file | M; T D-28 |

Q7.5 menetapkan input kkm dan setiap weight_percentage 0-100 inklusif/maksimal dua digit pecahan; integer/satu digit sah. Validasi format/range/pecahan asli sebelum cast/insert, tidak auto-round/clamp atau mengubah blank menjadi0. Invalid satu field/baris menolak mutasi kebijakan secara atomik tanpa audit/inbox sukses; set components lengkap/unik tetap diperiksa. Total draft belum100 tidak memberi hak aktif; aktivasi memerlukan jumlah tepat100 tanpa toleransi. Nilai konfigurasi tetap dipilih Master/Akademik, bukan konstanta contoh.

Q7.3 menetapkan final_score dua desimal half-up setelah penjumlahan kontribusi presisi penuh; rata-rata/kontribusi tidak dibulatkan sebelum total. Nilai akhir snapshot/props/detail/PDF konsisten dan tidak dihitung ulang oleh klien. Preview yang belum lengkap tidak diberi angka akhir palsu. Sumber/policy/calculation version tetap dibekukan; Q7.4 kkm_status dihitung server dari final_score dua desimal >= KKM versi yang dibekukan; < berarti belum mencapai, bukan membandingkan total sebelum pembulatan. KKM/status/nilai pada snapshot, props dan PDF konsisten, tidak menerima label dari klien. Hasil belum tersedia tetap tidak diberi label gagal atau nilai0 palsu.

Q7.1 menetapkan bentuk ringkas sesuai ACADEMIC_FLOW. Detail komponen/nilai blank hanya dibaca pada proyeksi rapor yang berhak dari snapshot revisi yang sama; tidak mengungkap detail semua mapel kepada guru satu mapel. PATCH catatan hanya field homeroom_note pada draft, cek scope/state/version dalam transaksi, audit actor asli dan inbox internal ikut commit; gagal audit berarti catatan tetap. Catatan kosong tidak memblokir finalisasi dan tidak membolehkan PATCH snapshot final. Teks di-escape pada UI/PDF, bukan HTML mentah atau instruksi renderer.

Preview adalah baca tanpa membuat report/file/audit CUD. source_version merupakan token konsistensi dari seluruh sumber rekap dan konfigurasi yang relevan, bukan credential/otorisasi. Server membandingkan ulang sumber di dalam protokol lock saat finalisasi. Metode pembentukan token dan version fisik harus ditetapkan saat implementasi; token tidak menggantikan validasi kelengkapan dan hitung ulang.

Prepare-file eksplisit agar GET PDF tidak membuat/mengubah data diam-diam. Tidak mengantrekan PDF massal tanpa kebutuhan. Kegagalan render membuat report tetap finalized dengan status file belum siap pada proyeksi; publish ditolak sampai file revisi sah tersedia dan availability/source masih current. Versi needs_revision/revoked/superseded tetap ditahan. File/snapshot yang sudah published tidak diganti melalui retry. Rapor dibuka melalui proyeksi versi final; file_path tidak dikirim sebagai input atau URL storage publik.

Daftar Rapor Online anak hanya memuat versi published/current aktif: semester, revision, waktu publikasi dan URL aplikasi berizin. Semester pernah published yang held/revoked dapat notice minimal tanpa isi snapshot/count versi internal/alasan. Draft/finalized belum pernah terbit dan superseded tidak masuk daftar/detail/unduh wali. PDF gabungan memerlukan grant seluruh isi; G satu mapel tidak otomatis mendapatkannya.

### 4.5 Keputusan, audit dan inbox (F-09/F-10/F-11)

| ID | Method dan route | Hak dan kontrak | Transport / status |
| --- | --- | --- | --- |
| HTTP-24 | GET /progressions; GET /progressions/{progression}; POST /progressions; PATCH/DELETE /progressions/{progression}; POST /progressions/{progression}/submit | GET R; create/edit/submit usulan MA dan batas cancel/delete draft masih TERBUKA-Q6. Create membawa siswa/tahun/report sumber, type dan tujuan; expected_version wajib pada edit/delete/submit objek existing. Tidak menerima approved_by/status approved | H/M; TERBUKA-aktor persiapan Q6 |
| HTTP-25 | POST /progressions/{progression}/approve; POST /progressions/{progression}/reject | P saja (termasuk M yang juga P), expected_version, expected_source_report_id/revision, reason wajib saat reject. Approve tidak langsung mengganti kelas/status | M; T, persiapan sumber menunggu Q6 |
| HTTP-26 | POST /progressions/batch-approve; POST /progressions/batch-reject | P; items pasangan progression_id/expected_version dan sumber yang diharapkan, reason reject. Validasi seluruh batch; semua commit atau semua gagal | M; T |
| HTTP-27 | POST /progressions/{progression}/apply; POST /progressions/batch-apply | Usulan MA pelaksana, bukan grant P dari jabatan. Approved/current source, target dan waktu berlaku sah; apply sekali tanpa menghapus asal | M; TERBUKA-Q6 aktor/waktu berlaku |
| HTTP-28 | GET /audit-logs; GET /audit-logs/{audit} | R scope/field/visibility terkini, filter waktu/action/objek/term dan pagination. Tidak ada POST/PATCH/DELETE audit untuk pengguna, termasuk M | H; T |
| HTTP-29 | GET /inbox | Halaman inbox diri; tidak menerima recipient_id bebas | H; T |
| HTTP-30 | GET /notifications; GET /notifications/unread-count; GET /notifications/{notification} | Recipient sesi dan scope/visibility sumber diverifikasi lagi; query read=all/unread/read, page/per_page. Ringkasan/count tanpa draft/objek terlarang | J; T |
| HTTP-31 | PATCH /notifications/{notification}/read-state | Diri sendiri seluruh role, payload read=true; simpan read_at server sekali, audit teknis tanpa notifikasi baru. Pengulangan no-op tidak mengganti waktu | J; T; belum menambah mark-all-read/mark-unread |
| HTTP-32 | POST /broadcasting/auth | Echo: socket_id/channel_name; sesi aktif dan hanya channel pengguna sendiri. Payload auth dihasilkan adapter, tanpa tanda tangan buatan sendiri | B; T dengan konfigurasi auth middleware eksplisit |

Channel usulan server adalah users.{user_id} private; nama wire yang diminta Echo mengikuti adapter, misalnya private-users.301. Server mencocokkan ID dengan pengguna sesi, tidak percaya channel_name dari klien. Event inbox.changed.v1 hanya membawa notification_id dan type; tidak membawa nilai/biodata, daftar penerima atau isi batch umum. Klien refetch/dedup, dan reconnect mengambil inbox terkini. Angka .v1 ini versi payload event, bukan REST API baru.

Default framework Laravel13 mendaftarkan auth broadcaster dengan web dan pengecualian PreventRequestForgery. Karena itu jangan mengklaim default /broadcasting/auth pasti CSRF-protected/419. Kontrak proyek mengusulkan middleware sesi aktif/auth:web serta negosiasi JSON401/403; keputusan menambah kembali proteksi forgery pada route tersebut harus dikonfigurasi dan diuji bersama Echo. Route GET tambahan default adapter tidak dianggap kontrak bisnis proyek. [Broadcast authorization](https://laravel.com/framework/docs/13.x/broadcasting#authorizing-channels), [registrasi route framework](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Broadcasting/BroadcastManager.php).

Tidak ada route CUD isi notifikasi atau penerima, meskipun pemanggil M. Sumber yang sudah diarsipkan boleh menampilkan ringkasan netral jika scope historis masih sah, tanpa link aktif; relasi yang dicabut mengeluarkan item dari daftar/count yang tidak lagi berhak. Perubahan read-state milik sendiri adalah pengecualian akses profil/inbox, bukan izin menghapus data operasional bagi D.

## 5. Payload dan respons konkret

Contoh berikut seluruhnya sintetis untuk bentuk kontrak, bukan data sekolah, default KKM/bobot yang disetujui atau hasil hitung final Q7. Payload form ditampilkan dalam JSON untuk keterbacaan; transport sebenarnya mengikuti adapter Inertia/multipart yang dipilih.

### 5.1 Simpan daftar nilai: PATCH /assessments/801/scores

```json
{
  "expected_version": 4,
  "entries": [
    { "enrollment_id": "501", "score": "0.00", "feedback": null },
    { "enrollment_id": "502", "score": null, "feedback": null }
  ]
}
```

Usulan semantik PATCH yang perlu dijaga: entry tidak dikirim = tidak berubah; score numerik "0.00" = simpan nol; score:null pada baris yang sudah ada = kosongkan/hapus skor numerik, sehingga memerlukan grant DELETE domain pada state mutable. D tidak boleh menyelundupkan hapus melalui PATCH: seluruh batch ditolak jika meminta clear existing row. null ketika tidak ada skor lama adalah no-op. String kosong bukan nol; UI menormalisasi blank secara eksplisit tanpa otomatis membersihkan semua entry yang tidak dikirim.

Enrollment harus satu rombel/periode dengan class_subject assessment dan berlaku pada assessed_on untuk input skor. Tanggal source assignment asal bukan syarat kepemilikan; current G parent boleh melanjutkan draft lama tanpa mengubah asal. ID duplikat, final_score/bobot/graded_by/source assignment spoof ditolak. Audit tiap objek/version/inbox atomik, satu invalid/asing rollback semua. Stale version tidak overwrite; status published/final tetap lifecycle koreksi terpisah.

### 5.2 Simpan absensi draft

PATCH /attendance-sessions/901/records menerima expected_version, expected_roster_version dan entries enrollment_id/status (present/excused/sick/absent/null untuk clear draft oleh pemilik hak D). Omitted bukan alpa; D Data Entry tidak clear existing. Roster berubah -> redirect kembali dengan error form roster_changed sebelum menulis; UI meminta review eligible/removed. Record draft peserta yang kini tidak berlaku hanya boleh dihapus secara eksplisit oleh MA/G berhak dengan audit; tidak auto-delete atau memberi D hak hapus. Setelah review, simpan memakai roster version terkini; finalize mengunci sumber dan memeriksa ulang set/status tepat lengkap sebelum frozen. Draft tidak menjadi penyebut/wali; record final tidak direfilter memakai enrollment sekarang.

### 5.3 Revisi policy global

```json
{
  "kkm": "70.00",
  "components": [
    { "scoring_component_id": "11", "weight_percentage": "20.00" },
    { "scoring_component_id": "12", "weight_percentage": "30.00" },
    { "scoring_component_id": "13", "weight_percentage": "20.00" },
    { "scoring_component_id": "14", "weight_percentage": "20.00" },
    { "scoring_component_id": "15", "weight_percentage": "10.00" }
  ]
}
```

POST /grading-policies membuat draft baru; PATCH draft juga wajib expected_version. components adalah set lengkap, bukan patch satu bobot yang otomatis menyesuaikan sisanya. ID unik dan sah, tepat satu sumber kehadiran sesuai katalog, bobot0..100 dan total100 sebelum activate; tidak ada subject_id/classroom_id/teacher_id. Tidak menerima revision/activated_by dari klien. Menghilangkan baris komponen pada draft diperlakukan sebagai perubahan konfigurasi MA, tidak mengubah policy pernah aktif.

### 5.4 Preview dan finalisasi rapor

GET preview mengembalikan proyeksi enrollment/term, policy_id/revision, calculation_version, source_version, report_version jika draft ada, items per mapel, missing_components, can_finalize dan blocked_reasons. Nilai tidak tersedia adalah null dengan availability yang jelas, bukan final_score=0 palsu. Preview hasil parsial tidak diberi label rapor resmi.

```json
{
  "expected_version": 2,
  "expected_policy_id": "71",
  "expected_source_version": "source-demo-12"
}
```

Payload finalize di atas tidak menerima nilai/KKM/rincian snapshot. Untuk rapor pertama, perubahan policy aktif/sumber menghasilkan source_changed dan meminta preview ulang. Revisi D-28 tetap memakai policy/calculation asli meski policy retired; perubahan sumber revisi tetap meminta preview ulang. Jika komponen positif kosong, errors._form menjelaskan kelengkapan yang memang boleh dilihat pengguna. Publish hanya dari finalized/current, file siap, source_version masih sah dan scope W/MA masih sah. Finalized belum memberi akses O; GET PDF tidak mengesampingkan syarat published bagi O.

### 5.5 Approval batch

```json
{
  "items": [
    { "progression_id": "1001", "expected_version": 3, "expected_source_report_id": "701", "expected_source_report_revision": 1 },
    { "progression_id": "1002", "expected_version": 2, "expected_source_report_id": "702", "expected_source_report_revision": 1 }
  ]
}
```

Batch harus nonkosong, ID unik dan semua item valid/berhak/current sebelum commit. Reject menambah reason wajib. Apply tidak digabung dengan approve dan hanya boleh sesudah Q6 pelaksana ditetapkan. Jika sumber/tujuan usang, apply ditolak dan seluruh batch tidak berubah. Transisi needs_review yang dipersist dilakukan oleh action perubahan sumber atau peninjauan terpisah yang diaudit, bukan perubahan sebagian tersembunyi ketika batch apply gagal. Jangan diam-diam menerapkan sumber baru atau mengembalikan 207 partial success.

### 5.6 Inbox JSON dan error form

```json
{
  "items": [
    {
      "id": "1201",
      "category": "assessment_shared",
      "summary": "Hasil penilaian anak sudah dibagikan.",
      "visible_item_count": 1,
      "read_at": null,
      "created_at": "2026-10-28T08:00:00Z",
      "target": { "kind": "student_progress", "href": "/children/401/progress" }
    }
  ],
  "pagination": { "current_page": 1, "per_page": 25, "total": 1, "last_page": 1 }
}
```

GET unread-count memberi `{"unread_count":1}`; PATCH read-state memberi id/read_at/unread_count yang berizin dengan 200. Target href relatif ditetapkan server dari route allowlist dan tidak memberi akses baru; destination tetap dicek ulang. Nilai read_at server, bukan tanggal dari klien. Event Reverb hanya menunjuk notification_id/type, tidak menyalin shape inbox ini.

Form conflict Inertia diproyeksikan melalui props setelah redirect, misalnya errors.version="Data telah berubah. Tinjau ulang sebelum menyimpan." dan form_error.code="stale_version". Props error tidak membawa nilai/identitas objek di luar scope atau raw exception. Field props tersebut adalah usulan aplikasi, bukan perubahan protokol Inertia.

## 6. Retry, konkurensi, limit dan caching

Setiap update/delete/command kritis terhadap objek existing membawa expected_version pada induk yang diubah: skor pada assessment, absensi pada session, policy pada policy, profil/avatar pada user, relasi wali pada student, dan transisi rapor/progression pada objeknya. Header/key-value settings menggunakan versi key existing; PUT key baru memakai expected_version=0 dengan precondition key belum ada. Payload role/activation/password juga mengikuti versi user yang dibaca melalui halaman berizin.

Create objek baru tidak membawa version objek yang belum ada. Untuk create session/assessment, enrollment, schedule atau assignment, server mengunci parent yang sudah ada lalu memeriksa izin/state/relasi terkini serta unique; tidak ada field parent-version generik yang wajib tetapi tidak dijelaskan di katalog. Create draft report memakai expected_latest_revision yang eksplisit. Parent ID dari URL harus cocok dengan child, bukan hanya validasi exists. Lock, unique, state dan version berada dalam transaksi domain/audit/inbox; stale version tidak dicoba ulang otomatis.

Finalize/publish/approve/apply dan batch memakai precondition status/version serta constraint bisnis agar replay tidak mengulangi perubahan. Replay yang sudah selesai dapat diberi state_conflict/stale_version lalu klien membaca GET terbaru; tidak harus mengembalikan sukses yang sama. Mark-read idempoten sebagai set keadaan, tanpa mengganti timestamp/audit jika tidak berubah. Retry deadlock hanya seluruh transaksi aman yang belum commit, dengan batas konfigurasi.

Tidak memperkenalkan header Idempotency-Key generik atau tabel receipt yang belum dirancang. Create yang tidak punya natural unique, misalnya draft assessment/policy baru, tidak dijamin idempoten dan tidak diulang otomatis setelah timeout; klien menandai hasil belum pasti lalu memeriksa daftar/status. Jika kelak retry otomatis create dibutuhkan, persistence dedup terikat aktor/aksi/payload harus dirancang dan diuji sebelum jaminan tersebut diberikan. Ini berbeda dari correlation request_id yang bukan dedup atau credential.

Usulan limit daftar25/maks100; nilai/absensi dibatasi satu roster kelas yang berhak, tanpa ID duplikat, dan batch keputusan diberi batas backend sebelum aktivasi. Hard cap body/batch/upload, ukuran teks, timeout proses, rate limit login/command/inbox/PDF dan retensi belum ditentukan dari beban nyata; harus dikunci saat implementasi dan diuji. Over-limit input menghasilkan422, body/upload melebihi batas413, throttling429. Tidak mengklaim batas tak terhingga atau benchmark dari angka pagination.

Data pribadi, page props, inbox, audit dan PDF privat tidak dicache publik di Cloudflare/CDN; usulkan Cache-Control private,no-store pada respons sensitif. Tidak menyimpan props nilai/password ke localStorage atau URL. Cache/ETag tidak menjadi cache izin tak berbatas; sumber scope/pencabutan tetap diperiksa. Request download tidak membawa session token pada query.

## 7. Bagian yang belum boleh dikunci sebagai implementasi

- Q6.1/Q6.3 tetap, D-25 memilih Q6.2/Q6.4 password sementara/email verified Master/OTP/modal/reset login biasa. Parameter, provider email, binding/repository/middleware dan fallback email/bootstrap belum final; allowlist/delegasi/penugasan/apply Q6 tetap terbuka. Tidak membuka reset administratif atau fitur lain dari scaffold.
- Q7: detail visual cetak dan bukti implementasi. Susunan Q7.1, batas skor input Q7.2, pembulatan hasil Q7.3, comparator Q7.4 serta presisi konfigurasi Q7.5 diterima; contoh DTO bukan persetujuan KKM/bobot default.
- D26/27 menyelesaikan aturan AR04/05; representasi tanggal/race/jadwal/FK/roster dan current grant perlu implementasi/uji. Transfer antarkelas term sama dan koreksi struktur/roster/tanggal aktivitas final ditunda; koreksi skor/status D-28 dikonfirmasi; D-29 memilih school_timezone dinamis; nilai awal setup dan rekonsiliasi cap delegasi saat zona berubah tetap belum selesai, tidak memulai modul transfer.
- D-28 menetapkan aktor/state koreksi skor/status, revisi/revoke rapor dan semester closed. Struktur/roster/tanggal aktivitas final dan undo progression applied tetap tidak dibuka; implementasi/lock/source token belum diverifikasi.
- Fisik version/source_version, limit akhir, kebijakan sesi, scanner, renderer serta middleware broadcasting/CSRF perlu dipastikan pada rilis framework yang benar-benar dipilih. Semua ini rancangan, bukan konfigurasi terpasang.

## 8. Verifikasi kontrak dan kompatibilitas

| ID | Perilaku yang harus dibuktikan | Status |
| --- | --- | --- |
| CT-01 | HTML/page object/redirect/validation Inertia sesuai adapter, konflik bisnis tidak disalahartikan sebagai asset409 | NOT RUN |
| CT-02 | JSON401/403/404/422/409/429/503 dan error form aman; CSRF origin/fallback/expiry sesuai konfigurasi, tanpa retry mutasi buta | NOT RUN |
| CT-03 | Matrix role rangkap, nested ID beda kelas/anak, query count, field mass-assignment dan izin dicabut | NOT RUN |
| CT-04 | Decimal string, null/omitted/zero, clear existing row ditolak bagi D; attendance null bukan alpa; batch tidak sebagian | NOT RUN |
| CT-05 | Policy set lengkap/total100, tanpa override, versi pernah aktif immutable dan activation race | NOT RUN |
| CT-06 | Stale version/source, duplicate command dan apply, timeout tak pasti, rollback audit/inbox tanpa event | NOT RUN |
| CT-07 | Perkembangan tanpa rapor; snapshot/PDF wali hanya published/current aktif anak. Held/revoked/superseded/draft/finalized dan anak lain tidak bocor lewat count/detail/file, notice minimal semester pernah terbit | NOT RUN |
| CT-08 | Snapshot/revisi/file gagal/retry, GET tidak membuat PDF/data, published file tidak diganti diam-diam | NOT RUN |
| CT-09 | Approval hanya P, apply terpisah, source changed/needs_review dan batch tidak partial | NOT RUN |
| CT-10 | Inbox/count/read-state/current scope, channel guest/orang lain ditolak dan format auth broadcaster benar | NOT RUN |
| CT-11 | Kontrak event minimal, worker/Reverb/dispatch gagal, reconnect/dedup tanpa kehilangan inbox | NOT RUN |
| CT-12 | Content-Type/disposition/cache/privacy, limit input/upload, manifest props/job kompatibel dengan frontend/image baru | NOT RUN |

Gunakan mapping fitur/AC, AV pada [review arsitektur](ARCHITECTURE_REVIEW.md), DBV pada [verifikasi DB](DATABASE_VERIFICATION_REPORT.md) dan CQ pada [kualitas kode](CODE_QUALITY_PLAN.md); jangan membuat suite duplikat sekadar mengganti ID. Test memakai MySQL target, akun runtime dan fixture sintetis. Catat revisi, engine/digest, request/hasil aman dan langkah aktual. Pemeriksaan Markdown/route catalogue bukan pengujian HTTP, izin atau keamanan.

Perubahan field required, nullability, enum, route, error code atau payload job/event harus memperbarui produsen/konsumen dan tes pada rilis yang kompatibel. Dev/main/CD mengikuti [CICD_PIPELINE_SPEC.md](CICD_PIPELINE_SPEC.md); jangan menganggap frontend lama otomatis cocok dengan kontrak baru. Rujukan primer yang diperiksa: [Laravel JSON validation](https://laravel.com/framework/docs/13.x/validation#validation-error-response-format), [file download](https://laravel.com/framework/docs/13.x/responses#file-downloads), [error negotiation](https://laravel.com/framework/docs/13.x/errors#rendering-exceptions-as-json) dan sumber Inertia/broadcasting pada bagian terkait. Tidak ada endpoint, schema mesin, command atau tes aplikasi yang dibuat/dijalankan melalui dokumen ini.

Telaah dokumentasi dibantu dua subagen untuk cakupan fitur/izin dan protokol. Temuan nama precondition, versi pada create dan daftar rapor anak diperbaiki oleh agen utama. Telaah ini tidak merupakan pengujian HTTP, audit keamanan atau persetujuan proposal teknis.

### Tambahan integrasi D-26/D-27

Semua resource parent class_subject -> classroom -> term harus cocok dengan enrollment. teaching_assignment_id asal pada kegiatan adalah provenance server, bukan target bebas atau syarat guru lama masih aktif. D yang didelegasikan memakai assignment yang efektif pada parent/capability yang sama; delegation asal lama tidak ikut pindah. Grant G current + draft memberi handover; tidak memberi guru semua rapor gabungan, master-only role atau hak koreksi published/final otomatis; gunakan command D-28 dengan grant/state yang sesuai.

GET preview roster menyajikan version/diff tanpa mutasi. Submit review lewat PATCH entries eksplisit; server membedakan menambah status eligible dengan clear record draft out-of-roster yang sebelumnya memang milik sesi ini. Tidak boleh menerima enrollment asing demi clear; D tanpa delete grant ditolak. Finalize expected_roster_version harus cocok dengan classroom.roster_version dan reviewed state. Preview rapor/source_version mencakup class-subject/assignment/enrollment-roster perubahan relevan; konflik meminta review, bukan hitung ulang diam-diam.

RES10/HTTP08/14..17/20/RES08 dirujuk API-T52..54, DBV27/28, AC03/05/06 dan T04/06/07/08. Semua masih blueprint/NOT RUN; tidak ada route lama yang perlu alias kompatibilitas karena belum ada aplikasi inti.

Transport konflik roster D27: HTTP15 adalah M/Inertia. Gunakan redirect kembali + field/error code roster_changed pada expected_roster_version, tampilkan diff dan minta review tanpa overwrite; jangan raw HTTP409 yang bercampur protokol asset-version/location Inertia. Hanya endpoint J eksplisit dapat memakai409 konflik bisnis sesuai tabel transport; tidak menambah endpoint JSON untuk kebutuhan ini.

### D-28 — payload dan konsistensi koreksi/revisi

HTTP-18 adalah mutation Inertia M: expected_version integer parent, reason wajib nonblank, entries set tidak kosong/ID unik. Skor menerima score sebagai decimal string basis10 sesuai bagian3, rentang0..100 maksimal dua desimal, atau null untuk menghapus baris menjadi blank; sebelum masuk/setelah keluar tetap tidak boleh diberi skor. Absensi hanya status present/excused/sick/absent milik frozen record existing; tidak menerima record asing atau roster/tanggal/komponen/class_subject/teacher/status lifecycle dari klien. No-op seluruh entries ditolak sebagai state_conflict; validasi/izin satu entry gagal membatalkan keseluruhan. Batas panjang alasan/batch dikunci sebelum endpoint aktif, bukan tanpa batas.

Aktor, status sumber published/finalized, parent/enrollment, term, alasan dan expected_version dicek di bawah lock akademik. Perubahan sumber + parent version/source token + metadata needs_revision rapor terdampak + needs_review progression belum applied + audit per objek + inbox satu transaksi. Audit memuat before/after field sah, tidak menimpa log lama. Respons redirect ke sumber dengan hasil yang sah; stale_version/source_changed/state_conflict berupa error form setelah redirect, bukan raw409 Inertia. Setelah timeout baca ulang state/audit berizin, jangan replay koreksi/publish tanpa memeriksa version.

Dampak per siswa meliputi rincian snapshot, bukan hanya final_score yang dibulatkan; data kelas lain tidak ikut muncul dalam preview jumlah/daftar. GET perkembangan anak memakai sumber terkoreksi; rapor held tidak bocor melalui props/detail/file/count. Semester yang sebelumnya published boleh menampilkan notice minimal Perlu revisi atau Dicabut tanpa snapshot/reason internal.

HTTP-23 revisions menerima expected_version versi sumber, expected_latest_revision dan reason; supersedes_id/enrollment/term/policy/calculation/version baru ditentukan server. Parent adalah latest immutable pada lineage tersebut, termasuk finalized yang needs_revision; cegah percabangan/dua revisi unfinished dengan lock enrollment dan pemeriksaan latest. Satu draft revisi existing dibaca melalui GET berizin, tidak dibuat ulang karena retry. Revisi mewarisi grading_policy_id dan calculation_version asli, bukan policy active saat ini. Preview/finalize revisi memakai sumber terbaru dengan policy asli dan token konsistensi yang mencakup sumber relevan; perubahan active policy lain tidak mengganti kebijakan revisi.

Revoke memerlukan reason/expected_version dan versi finalized/published yang belum revoked/superseded; tidak membuat revisi otomatis, tidak menghapus file/snapshot. Revoked tidak kembali published. Metadata availability current/needs_revision/superseded terpisah dari status; published/current aktif satu-satunya detail/download wali. Publish HTTP-21 juga memverifikasi availability, source_version snapshot masih cocok dan private file siap; atomik supersede versi published sebelumnya. Jika sumber berubah setelah finalized, jangan refresh snapshot: tandai needs_revision dan buat revisi baru.

Prepare/retry-file HTTP-22 hanya finalized/current berizin; render di luar lock, attach di bawah lock memeriksa report version/availability dan fingerprint snapshot/file. Render usang/orphan tidak dipasang atau dipublikasikan. Gagal file/publish tidak membuka kembali rapor held/revoked/superseded. GET tidak render/mengubah state. Snapshot asal/PDF byte tidak pernah ditimpa; alasan/revoked_at/by/availability/version adalah metadata lifecycle yang dapat berubah dengan audit. [Alur kanonis](ACADEMIC_FLOW.md).

Slot revisi belum selesai D-28 berarti status draft atau finalized dengan availability current; finalized/needs_revision, revoked dan superseded tidak menempati slot kandidat. Saat membuat revisi eksplisit dari latest finalized/current, atomik ubah metadata asal menjadi needs_revision + naikkan version bersama create/audit/inbox agar tidak dapat publish bersaing. Membuat draft pengganti dari published/current tanpa koreksi sumber atau revoke tidak otomatis menahan versi terbit lama; ia tetap aktif sampai supersede saat revisi baru published. Pemeriksaan satu kandidat dan satu versi published/current adalah dua invariant terpisah.

Penahanan D-28 hanya mengubah availability versi finalized/published yang masih current dan terdampak; versi revoked/superseded tetap tertutup, tidak diaktifkan atau dikembalikan ke status sebelumnya. Revoke kandidat revisi yang belum pernah published tidak mencabut versi published/current lain yang masih sah. Notice wali mengikuti perubahan ketersediaan rapor publik pada semester terkait; mutasi versi internal tidak memunculkan notice “Dicabut” bila rapor aktif wali tidak berubah.

### D-29 — konfigurasi timezone dan proyeksi waktu

HTTP-06 school_timezone memakai value string IANA dari tiga pilihan; payload WIB/WITA/WIT sebagai nilai, UTC offset bebas, string kosong, timezone lain, actor/role atau pengubahan timezone server ditolak. UI label WIB/WITA/WIT dipetakan ke nilai kanonis; placeholder Pilih zona waktu sekolah tidak dikirim sebagai value. expected_version menjaga dua Master concurrent; alasan bila diminta oleh kontrol domain tidak dipakai untuk memilih zona di luar allowlist. No-op value sama tidak membuat audit/inbox perubahan palsu.

Shared page props minimal memuat school_timezone id/label/version, timestamp ISO8601 UTC dan tanggal DATE literal. Pembaca semua role memperoleh proyeksi timezone seperlunya, tanpa GET /system-settings atau isi key lain. Frontend memformat timestamp dengan zona eksplisit, bukan browser-default; client tidak menentukan tanggal/grant resmi. Sumber school_timezone dibaca dinamis, bukan .env/config cache/VITE build constant.

Mutasi akademik sensitif memeriksa expected_school_timezone_version dari konteks form/preview, selain expected_version induk; token preview sumber juga mencakup versi setting yang relevan. Server di bawah mutex/key/parent lock memeriksa kembali timezone/version dan grant/state; perubahan setting -> redirect/error form school_timezone_changed, minta refresh/review, tanpa efek parsial. Pengaturan yang hilang/invalid menggagalkan operasi akademik dengan penjelasan konfigurasi belum lengkap, tanpa fallback host. Login/profil mandiri tidak mendapat dependensi timezone baru.

Transaksi PUT memuat key/version/audit/inbox sesuai matriks penerima konfigurasi. Setelah commit, request/job berikutnya membaca setting baru; unit kerja yang sudah berjalan tidak mengganti value di tengah pemrosesan, mutasi tetap recheck sebelum commit. Baseline sederhana tanpa cache setting lintas request/job; jika cache kelak dipakai harus invalidasi/version konsisten. Tidak perlu restart/rebuild/clear config untuk perubahan key database ini. Refresh penuh mengambil timezone baru; tidak menjanjikan push refresh realtime/auto-reload form.

UTC expiry yang sudah tersimpan tidak direbase; gap cap delegasi assignment DATE tetap OPEN sebagaimana [DATA_MODEL](DATA_MODEL.md). Final snapshot/PDF tidak diubah karena timezone. D-29 belum merupakan endpoint/runtime/test tersedia.

Versi timezone adalah precondition konteks form/preview, terpisah dari fingerprint isi snapshot rapor final. Perubahan school_timezone saja tidak menandai rapor final needs_revision, tidak mengganti source_version rekap frozen atau membatalkan progression yang sudah sah. Publish/render tetap memakai zona/label snapshot bila dicetak; request usang meminta refresh lalu memeriksa ulang grant terkini, tanpa mewajibkan revisi rapor hanya karena zona berubah.
