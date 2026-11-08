# Test Strategy - SIA MI Alghivari

Revisi: 29 Oktober 2026. Status PROPOSED blueprint; owner pelaksana/reviewer dan persetujuan rilis belum ditetapkan. Implementasi aplikasi inti NOT STARTED; seluruh tes inti NOT RUN. Dokumen menetapkan hipotesis dan bukti yang diharapkan, bukan laporan kelulusan.

## Product context and scope

Target: alur satu sekolah dari akademik, nilai/absensi, Perkembangan Belajar, Rapor Online sampai approval dan penerapan naik/lulus. Sumber kebutuhan: [PRD](PRD.md), [37 AC fitur](FEATURES/FEATURE_SPEC_TEMPLATE.md), [matriks izin](ROLE_PERMISSION_MATRIX.md) dan [akses objek](RESOURCE_ACCESS_POLICY.md). Traceability dirawat pada [ACCEPTANCE_TEST_MATRIX](ACCEPTANCE_TEST_MATRIX.md); skenario utama pada [TEST_PLAN](TEST_PLAN.md), kontrak pada [API_TEST_MATRIX](API_TEST_MATRIX.md).

Risiko utama adalah data siswa terbaca pihak lain, nilai salah, perubahan tanpa audit, serta histori rusak. Hipotesis: policy objek/field/keadaan, transaksi bisnis+audit+inbox, versi/constraint dan snapshot menjaga invariant tersebut. Tes harus mencoba mematahkan hipotesis melalui HTTP, database, file, worker dan browser nyata.

Stack backend/engine/runner mengikuti status usulan pada [TECH_STACK](TECH_STACK.md) dan [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md); React/TypeScript serta Reverb/Echo yang dipilih tidak berarti seluruh paket telah terpasang. Satu tes boleh membuktikan beberapa AC/CT/DBV/AV/BF; ID dokumen bukan kewajiban membuat suite duplikat.

[Prototipe facelift-v2](support/facelift-v2/README.md) hanya referensi desain. Bukti historisnya tidak dihitung sebagai tes aplikasi inti, sesi, database, audit atau realtime. Target UI mengikuti [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md) dan [DESIGN_SYSTEM](DESIGN_SYSTEM.md), termasuk WCAG 2.2 AA sebagai target, bukan sertifikasi.

Di luar lingkup: OAuth/social login/JWT, banyak sekolah/tenant, pembayaran, marketing, SMS/WhatsApp/push, jadwal notifikasi/DST dan offline write. D-25 email akses/password termasuk core dengan mail fake, tidak N/A; pengiriman nyata memerlukan provider/otorisasi. Tetap uji lintas pengguna/anak/kelas.

## Risk inventory

Skor berikut perkiraan desain, bukan frekuensi insiden terukur. Likelihood/impact 1 sampai 4; skor 12-16 kritis, 6-11 tinggi, 3-5 sedang. Kebocoran data/eskalasi hak tetap kritis meskipun skor perkiraan lebih rendah; prioritas tidak mengurangi tes wajib.

| User flow / area | Likelihood 1–4 | Impact 1–4 | Risk / justification | Required evidence | Owner |
| --- | ---: | ---: | --- | --- | --- |
| Identitas, sesi dan akses siswa | 3 | 4 | 12/kritis: role rangkap atau ID tebakan memperluas akses | T-01/02/18; HTTP/DB/file/channel negatives dan browser, [auth plan](AUTH_SECURITY_TEST_PLAN.md) | Pengembang identitas dan reviewer keamanan; belum ditunjuk |
| Konfigurasi global dan kalkulasi | 3 | 4 | 12/kritis: blank menjadi nol, bobot/KKM salah atau revisi tertimpa | T-05/06; oracle numerik, policy race dan raw snapshot | Pengembang akademik dan peninjau aturan; belum ditunjuk |
| Nilai/absensi dan delegasi | 3 | 4 | 12/kritis: salah siswa, draft bocor atau izin kedaluwarsa | T-04/07/08; roster aktual, null/omitted/0, current authorization | Pengembang fitur; belum ditunjuk |
| Rapor, PDF dan histori | 3 | 4 | 12/kritis: snapshot bergeser, file anak lain, published tanpa file | T-09/10/11; DB+storage+browser dan render PDF | Pengembang rapor; belum ditunjuk |
| Approval/apply naik atau lulus | 3 | 4 | 12/kritis: approval melewati principal, apply ganda/parsial | T-12/13; race dua koneksi dan histori enrollment | Pengembang progression dan reviewer; belum ditunjuk |
| Audit, inbox dan transaksi | 3 | 4 | 12/kritis: domain tersimpan tanpa jejak atau draft bocor | T-14/15/16; fault injection dan akun runtime DB | Pengembang backend; belum ditunjuk |
| Realtime dan worker | 3 | 3 | 9/tinggi: hint hilang/duplikat atau badge salah | T-17; Reverb/Echo nyata, reconnect dan catch-up inbox | Backend/frontend; belum ditunjuk |
| UI, aksesibilitas dan konflik | 3 | 3 | 9/tinggi: salah konteks atau gagal tersamar sukses | T-18; dua viewport, keyboard/assistive tech, error states | Frontend dan reviewer; belum ditunjuk |
| Upgrade, restore dan kapasitas | 2 | 4 | 8/tinggi: data/file hilang atau host tidak pulih | T-19/20; rehearsal terisolasi, hasil ukur, smoke revisi target | Operator dan reviewer; belum ditunjuk |

## Planned test layers

Seluruh command dan path laporan aplikasi inti BELUM DIKONFIGURASI. Tool merupakan kandidat rencana kualitas; bootstrap harus mencatat argv/working directory/versi/timeout/path artefak aktual sebelum CI mengandalkannya.

| Layer | Tool/command | Mock or real boundary? | Suite/JUnit report path | Required? Owner and N/A reason |
| --- | --- | --- | --- | --- |
| Unit | PHPUnit kandidat; argv belum tersedia | Kalkulator murni, data terkontrol, tanpa HTTP/DB | Belum tersedia; NOT RUN | Wajib hitung/kelengkapan/keadaan; pengembang aturan belum ditunjuk |
| Integration | PHPUnit dan engine MySQL target menurut DB-001; argv belum tersedia | DB nyata, constraint/grant runtime, dua koneksi independen, storage uji; fake untuk injeksi kegagalan | Belum tersedia; NOT RUN | Wajib transaksi/race/snapshot/queue; backend belum ditunjuk |
| API/contract | HTTP feature dan consumer props/JSON/file/channel; argv belum tersedia | Middleware/policy/action/DB nyata, bukan mock policy saja | Belum tersedia; NOT RUN | Wajib CT-01..12/katalog API; pengembang API belum ditunjuk |
| Critical browser E2E or native-device | Playwright kandidat; argv belum tersedia | Browser ke inti+DB nyata; Reverb/worker nyata pada realtime | Belum tersedia; NOT RUN | Browser wajib; native N/A karena tidak ada app native; frontend/QA belum ditunjuk |
| Accessibility automation + manual | Alat scan dipilih saat bootstrap; keyboard, zoom/reflow, screen reader | UI inti berjalan, scan ditambah pengamatan manusia | Belum tersedia; NOT RUN | Wajib UI berubah, bukan sertifikasi scan; reviewer belum ditunjuk |
| Security/operations | Auth negatives, scanner terpilih, migration/restore/image smoke; argv belum tersedia | Lokal/CI terisolasi dan akun runtime, bukan layanan publik aktif | Belum tersedia; NOT RUN | Wajib sesuai perubahan dan seluruh gate relevan saat rilis; operator/reviewer belum ditunjuk |

## Deterministic environments and data

Ikuti [TEST_DATA_PLAN](TEST_DATA_PLAN.md) dan [ENVIRONMENT_SPEC](ENVIRONMENT_SPEC.md): dev/CI/demo terpisah DB/volume/credential/queue. Fixture sintetis berlabel, seed deterministik dan target disposable; tanpa biodata/kredensial laporan KP. Akun/browser context per role/test, tanpa state tes sebelumnya. In-memory/SQLite tidak menggantikan bukti constraint/lock/kolasi/grant engine target.

Kontrol waktu server, locale, seed acak dan jaringan. Race memakai koneksi/proses independen dengan barrier dan deadline tercatat, termasuk keadaan awal belum ada baris; satu transaksi pembungkus test tidak membuktikan race. Fixture worker/concurrency harus committed terlihat di koneksi lain. Assert efek DB/file/inbox dan teardown meskipun request/worker gagal. Secret tes dibuat runtime tanpa dicetak.

Q6 allowlist/delegasi/penugasan/apply, fallback mailbox/bootstrap, parameter/provider/hashed repository/middleware D-25, race revocation/nilai awal timezone-cap delegasi/limit/retensi masih gap. Expected terkonfirmasi diuji; bagian OPEN belum diterima sebelum keputusan/bukti. Q7 aturan diterima, visual/render belum verified.

## Regression, flakiness and gate policy

- Iterasi: tes aturan/perilaku berubah berikut konsumen, akses negatif dan failure path relevan. Kontrak berubah menyertakan produsen/konsumen pada revisi kompatibel yang sama.
- Integrasi PR/main: CI terkonfigurasi, regresi alur kritis, kontrak, akses/integritas terpengaruh. Perubahan policy/session/sumber kalkulasi memicu ulang HTTP/job/file/channel serta snapshot terkait.
- Rilis/demo publik: seluruh gate engineering, QA, identitas, DB, keamanan dan deployment yang berlaku pada revisi/image target, termasuk restore/rollback/smoke; ikuti [pipeline](CICD_PIPELINE_SPEC.md) dan otorisasi rilis. Focused test atau Markdown check tidak menggantikan gate tersebut.
- FAIL pada akses, nilai, audit/inbox, histori/file privat memblokir penerimaan. Required NOT RUN/SKIP juga gap pemblokir, bukan N/A. Keputusan terbuka tidak ditutup dengan mock sukses.
- Flaky test menyimpan kegagalan awal/trace/seed dan diagnosis. Rerun untuk investigasi, bukan menghapus kegagalan. Quarantine memerlukan alasan, owner dan tenggat konkret; alur kritis tetap blocker sampai perbaikan atau bukti pengganti sah tersedia.
- Angka pilot pada [PERFORMANCE_BUDGET](PERFORMANCE_BUDGET.md), [LOAD_TEST_PLAN](LOAD_TEST_PLAN.md) dan [SLO_SLI](SLO_SLI.md) adalah usulan pembanding, bukan kapasitas/SLA terukur. Kalibrasi terhadap host dan sepakati budget/retensi sebelum gate diterima; coverage membantu menemukan celah, bukan bukti benar.

## Release evidence

Catat revision/SHA aktual, image digest, lock/runtime/DB version, fixture/seed, argv/working directory, waktu/lingkungan, exit code, testcase passed/failed/skipped, path report/JUnit/trace dan batas cakupan. Bukti browser mencatat viewport/browser/OS, langkah dan expected/actual; PDF mencatat sumber snapshot, artefak serta hasil render yang ditinjau. Redaksi credential/cookie/token.

Hubungkan AC → T/API/auth/notifikasi → testcase runner; satu testcase boleh mengisi beberapa rujukan bila assertion membuktikannya. Laporan belum ada ditulis belum tersedia, tanpa tautan JUnit fiktif. Peninjau/pemilik rilis, approved date/CI link/SHA belum tersedia. PASS/FAIL/SKIP/NOT RUN berdasarkan eksekusi tercatat. Validasi dokumen tidak memberi PASS pada aplikasi.

D-28: aturan koreksi/revisi/revoke telah diterima, tidak lagi gap aktor/lifecycle HTTP-18/23. Tambahan API-T55..57/DBV29..30 dan perluasan T-11/AC existing menguji policy asli, frozen roster, per-siswa hold, race/failure, grant dan progression applied tetap; bukti runtime tetap NOT RUN.

## Pencatatan bertahap D-34

Gunakan [QA_EVIDENCE_REPORT](QA_EVIDENCE_REPORT.md) sebagai satu kontrak envelope/tahap hasil. Per fitur: tetapkan hipotesis/expected dari AC dan keputusan yang disepakati -> implementasi lokal setelah diotorisasi -> jalankan tes unit/HTTP/DB/file/job/browser yang relevan -> catat actual/revisi/artefak dan keterbatasan -> perbaiki/recheck tanpa menghapus riwayat -> integrasi/CI dan gate berlaku -> demo publik terotorisasi -> smoke/observasi publik pada artefak/config aktif.

Hasil LOCAL hanya mencakup fitur/revisi/environment yang benar-benar diuji, bukan seluruh aplikasi. Sebelum integrasi/rilis jalankan pemeriksaan lengkap yang berlaku pada revisi target; focused test lokal tidak menggantikannya. PUBLIC adalah bukti jalur HTTPS/WSS/config/host dan E2E scoped, bukan formal staging atau produksi sekolah. Load/fault/restore tetap pada resource uji terisolasi, pengiriman email nyata memerlukan scope tersendiri.

Prototipe/UI reference, hipotesis, expected dan actual inti tidak digabung. Command/JUnit/trace/score/findings/approval diisi hanya dari eksekusi nyata; untuk testcase belum dijalankan pertahankan NOT RUN dan kesimpulan NOT VERIFIED. Semua local/public aplikasi inti saat ini NOT RUN. RPO/RTO formal tetap di luar scope D-32.
