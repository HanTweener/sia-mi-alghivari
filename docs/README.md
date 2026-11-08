# Project Documentation Index — SIA MI ALGHIVARI

Status per 30 Oktober 2026: proyek berada pada tahap **pemantapan blueprint**, dan pemilik meminta tidak memulai implementasi dahulu. Dokumen yang telah diisi tetap mengikuti status persetujuan masing-masing; file lain yang masih berisi placeholder adalah template, bukan requirement.

## Blueprint aktif

- [Status proyek](PROJECT_STATE.md) dan [rencana pengerjaan](DELIVERY_PLAN.md): kondisi workspace yang diperiksa, keputusan terbuka, DAG tahap M0–M5, batas kepemilikan file dan kriteria penerimaan yang direncanakan. Implementasi NOT STARTED; tes aplikasi NOT RUN.
D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

- [PRD dan keputusan pemilik](PRD.md): Q1-Q5, Q6.1 urutan akun wali oleh Master sebelum Data Entry biodata/hubungan, dan Q6.3 pemberian/pencabutan delegasi oleh Master/Akademik dikonfirmasi. D-25 password sementara/OTP/email verified Master/modal dipilih; parameter/provider/integrasi, aktivasi usulan penugasan dan detail Q6 lain tetap terbuka. Seluruh aturan angka Q7.2-Q7.5 diterima; detail visual/render belum diverifikasi. Pembanding KKM Q7.4 (hasil akhir dibulatkan >= KKM) diterima pada D-21. Pembulatan akhir dua desimal half-up Q7.3 diterima pada D-20. Batas skor input 0-100/maksimal dua desimal Q7.2 diterima pada D-19. Susunan rapor ringkas Q7.1 diterima pada D-18; detail visual/render belum diverifikasi.
- [Arsitektur aplikasi](ARCHITECTURE.md) dan [review arsitektur](ARCHITECTURE_REVIEW.md): ARC-001 dengan bentuk aplikasi diterima dan detail desain masih rancangan, batas fitur/action/query/policy, alur data, kontrak kegagalan dan pertanyaan terbuka; telaah blueprint, bukan review implementasi independen.
- [Rencana kualitas kode](CODE_QUALITY_PLAN.md): standar PHP/React, pemeriksaan CQ-01..11, strategi tes berdasarkan risiko, gate PR/CD dan bukti; command aplikasi belum dikonfigurasi.
- [Mode kegagalan backend](BACKEND_FAILURE_MODE_PLAN.md) dan [arsitektur notifikasi](NOTIFICATION_ARCHITECTURE.md): rancangan urutan 4, 10 skenario risiko BF serta 6 ekspektasi NAR; transaksi, replay/konflik, file, antrean, catch-up dan pemulihan. Seluruh hasil backend NOT RUN.
- [Strategi pengujian](TEST_STRATEGY.md), [data sintetis](TEST_DATA_PLAN.md), [traceability 37 AC](ACCEPTANCE_TEST_MATRIX.md), [rencana utama](TEST_PLAN.md), [matriks API](API_TEST_MATRIX.md), [keamanan autentikasi lokal](AUTH_SECURITY_TEST_PLAN.md) dan [tes notifikasi](NOTIFICATION_TEST_PLAN.md): urutan 5, skenario positif/batas/kegagalan/penolakan akses dan hasil yang diharapkan. Semua pengujian inti NOT RUN; keputusan terbuka tidak ditutup oleh fixture atau bukti prototipe.
- [Budget performa](PERFORMANCE_BUDGET.md), [rencana beban](LOAD_TEST_PLAN.md), [indikator/target layanan](SLO_SLI.md) dan [pemulihan data](DATA_RECOVERY_PLAN.md): urutan 6, metode/target pilot, hipotesis hambatan, jendela demo dan backup/restore DB+file privat. Angka kapasitas/waktu layanan masih usulan; jadwal/retensi backup diterima D-31 dan target formal RPO/RTO di luar scope portofolio D-32; pengukuran dan drill NOT RUN, tanpa komitmen24/7.
- [Rencana rilis](RELEASE_PLAN.md), [prosedur rilis](RELEASE_RUNBOOK.md) dan [runbook operasi](RUNBOOK.md): urutan 7, prasyarat/gate, rehearsal lokal sementara, rollout digest dari main tepercaya, smoke/observasi, diagnosis serta rollback/roll-forward/restore sesuai dampak. Mengikuti CI/CD dan deployment yang ada, tanpa staging permanen; seluruh operasi NOT RUN dan kesiapan rilis belum terbukti.
- [Engineering](ENGINEERING_QUALITY_REPORT.md), [identitas](IDENTITY_SECURITY_VERIFICATION_REPORT.md), [keamanan](SECURITY_VERIFICATION_REPORT.md), [backend](BACKEND_READINESS_REPORT.md), [notifikasi](NOTIFICATION_READINESS_REPORT.md), [QA desain](DESIGN_QA_REPORT.md), [bukti QA](QA_EVIDENCE_REPORT.md) dan [deployment](DEPLOYMENT_READINESS_REPORT.md): urutan 8, rancangan cakupan verifikasi, rujukan skenario, kriteria penerimaan dan kebutuhan bukti. Actual NOT RUN/NOT VERIFIED, skor N/V dan kesimpulan reviewer/approval PENDING; bukan laporan pengujian yang telah dilaksanakan.
- [Referensi desain facelift-v2](support/facelift-v2/README.md): prototipe frontend dan kajian visual milik pemilik, **referensi bukan panduan wajib**. Detail terkini pada [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md); bukti prototipe bukan bukti aplikasi inti. Penggunaan antislop During, taste-skill app-screen dan Ponytail full mengikuti CODE_QUALITY_PLAN/DESIGN_SYSTEM, bukan dependensi runtime.
- [Kontrak HTTP dan integrasi](API_CONTRACT.md): route/payload/props untuk Inertia dan endpoint JSON/file/channel pendukung; scope, error, versi, retry dan bagian yang masih terbuka. Blueprint, seluruh tes HTTP NOT RUN.
- [Catatan keputusan proyek](DECISIONS/ADR_TEMPLATE.md) dan [spesifikasi fitur inti](FEATURES/FEATURE_SPEC_TEMPLATE.md): isi konkret SIA MI Alghivari sesuai koreksi pemilik. Nama file lama dipertahankan; kebutuhan terkonfirmasi dibedakan dari usulan/detail terbuka, implementasi NOT STARTED dan pengujian NOT RUN.
- [Matriks izin](ROLE_PERMISSION_MATRIX.md) dan [kebijakan akses](RESOURCE_ACCESS_POLICY.md): role rangkap, scope dan pengecualian.
- [Model ancaman](THREAT_MODEL.md), [kebutuhan autentikasi](AUTHENTICATION_REQUIREMENTS.md), [metode login](LOGIN_METHOD_MATRIX.md), [keamanan sesi](SESSION_TOKEN_SECURITY_SPEC.md), [pemulihan/step-up](RECOVERY_AND_STEPUP_PLAN.md) dan [kebutuhan otorisasi](AUTHORIZATION_REQUIREMENTS.md): rancangan login lokal, pencabutan akses, data siswa dan kontrol yang belum diterapkan. Alur onboarding/password sementara dan pemulihan OTP email telah dipilih D-25; parameter/provider/integrasi, bootstrap/fallback email hilang, durasi sesi dan mekanisme fisik revocation tetap terbuka. Pengujian NOT RUN.
- [Model data](DATA_MODEL.md): rancangan struktur, rapor dan naik/lulus; belum ada migration.
- [Keputusan database DB-001](DATABASE_DECISION_RECORD.md) dan [arsitektur database](DATABASE_ARCHITECTURE.md): engine MySQL dipilih; baseline 8.4 LTS/InnoDB disepakati, patch/konfigurasi dan detail fisik masih rancangan, sumber kebenaran, transaksi dan konkurensi.
- [Implementasi provider database](DATABASE_PROVIDER_IMPLEMENTATION.md) dan [pola akses](DATA_ACCESS_PATTERNS.md): constraint/indeks, driver, akun runtime/migrator dan batas audit.
- [Migrasi/pemulihan database](DATABASE_MIGRATION_PLAN.md) dan [laporan verifikasi](DATABASE_VERIFICATION_REPORT.md): urutan pembangunan schema, upgrade/backup/restore dan30skenarioDBV belum dieksekusi.
- [Rancangan teknologi](TECH_STACK.md): satu aplikasi Laravel + Inertia + React/TypeScript, Reverb/Echo serta Compose dan CI/CD demo dikonfirmasi sebagai blueprint; engine MySQL juga dipilih; baseline versi inti disepakati pada D-15 dan renderer PDF Dompdf/wrapper 3.1.x pada D-17; konfigurasi, paket tambahan lain dan versi persis mengikuti status masing-masing, susunan rapor Q7.1 diterima pada D-18; aturan Q7.1-Q7.5 disepakati; detail visual/render belum final.
- [Arsitektur deployment](DEPLOYMENT_ARCHITECTURE.md): Docker Desktop/WSL2 dan FrankenPHP classic berbasis Debian disepakati pada D-16; layanan Compose, Tunnel, domain aplikasi/Reverb dan persistensi, seluruh runtime NOT RUN.
- [Kontrak lingkungan](ENVIRONMENT_SPEC.md): pemisahan dev/CI/demo, konfigurasi publik dan secret.
- [Pipeline CI/CD](CICD_PIPELINE_SPEC.md) dan [rencana deployment](DEPLOYMENT_PLAN.md): dev/main, GHCR, runner lokal, pemeriksaan dan pemulihan; seluruh eksekusi NOT RUN.
- [Alur akademik dan referensi rapor](ACADEMIC_FLOW.md): absensi per mapel, KKM/bobot global, blank/nol, rumus, serta usulan susunan cetak.
- [Alur pengguna](USER_FLOWS.md): navigasi dan perilaku UI.
- [Brief pengalaman](DESIGN_BRIEF.md), [perbandingan arah visual](DESIGN_DIRECTION.md) dan [catatan desain](DESIGN_DECISIONS.md): rekomendasi Register Akademik, dua alternatif yang dibandingkan, bukti kebutuhan dan hipotesis; belum persetujuan visual.
- [Sistem desain](DESIGN_SYSTEM.md), [istilah/microcopy](CONTENT_STYLE.md) dan [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md): token terang usulan, komponen, 11 keluarga layar, state/responsif/keyboard serta ekspektasi verifikasi. Rasio warna tertentu dihitung statis; seluruh uji UI/PDF/aksesibilitas NOT RUN.
- [Notifikasi](NOTIFICATION_REQUIREMENTS.md) dan [matriks penerima](NOTIFICATION_MATRIX.md).
- [Roadmap](ROADMAP.md): blueprint aktif; tahap implementasi belum dimulai.

[Kajian laporan awal](REBUILD_ASSESSMENT.md) adalah referensi historis; kebutuhan terbaru pemilik dalam PRD menggantikan usulan lama yang bertentangan.

## Fill first

1. `PROJECT_BRIEF.md`: outcomes, user needs and scope.
2. `PRD.md`: roles, business rules and verifiable acceptance criteria.
3. `DESIGN_BRIEF.md`: rancangan tujuan pengalaman, konteks dan bukti sudah tersedia; hipotesis belum diuji.
4. `DESIGN_DIRECTION.md`: dua konsep dan rekomendasi visual sudah dirancang, belum disetujui pemilik.
5. `DESIGN_SYSTEM.md`: token, komponen dan kebutuhan interaksi/aksesibilitas usulan; belum source UI atau bukti pemeriksaan layar.
6. `TECH_STACK.md`: actual technology decisions, verified toolchain commands.
7. `ARCHITECTURE.md`: system boundaries and security/data ownership model.

`AGENTS.md` is in the project root and defines how AI agents should work. Fill its project-specific override section and ensure its instructions don't conflict with current repo reality.

## Fill when relevant

- `USER_FLOWS.md`: journeys and state coverage.
- `SCREENS/SCREEN_SPEC_TEMPLATE.md`: spesifikasi konkret 11 keluarga layar proyek pada nama file yang dipertahankan; bukan template kosong untuk disalin.
- `DESIGN_QA_REPORT.md`: evidence-based UI verification and remaining issues.
- `DESIGN_DECISIONS.md`: rationale for changes to interaction/visual direction.
- `CONTENT_STYLE.md`: labels, states, microcopy and content constraints.
- `FEATURES/FEATURE_SPEC_TEMPLATE.md`: spesifikasi konkret fitur inti proyek; rujukan FR/AC, scope dan hasil yang diharapkan.
- `DATA_MODEL.md` dan `API_CONTRACT.md`: model domain dan kontrak HTTP konkret; route/payload masih rancangan, belum diimplementasikan.
- `TEST_PLAN.md` and `RELEASE_PLAN.md`: evidence, safety and deployment.
- `CODE_QUALITY_PLAN.md`: real toolchain and enforceable quality-check commands for this repo.
- `ARCHITECTURE_REVIEW.md`: options/constraints and contracts for major system changes.
- `REFACTOR_PLAN.md`: baseline tests, small transformations, and rollback for risky internal changes.
- `ENGINEERING_QUALITY_REPORT.md`: concrete results and engineering release/merge risks.
- `DECISIONS/ADR_TEMPLATE.md`: catatan keputusan konkret ADR-SIA-001; detail ARC-001/DB-001 tetap mengikuti sumber kanonisnya.
- `ROADMAP.md`: milestones, dependencies and release outcomes.
- `PROJECT_STATE.md` dan `DELIVERY_PLAN.md`: ringkasan status serta rencana pengerjaan konkret yang sudah dirancang; perbarui kondisi aktual, ketergantungan, command dan bukti ketika pekerjaan benar-benar dilakukan.

Design process guidance is under `.ai/standards/DESIGN_INTELLIGENCE.md`; UI-specific quality gates are `.ai/checklists/DESIGN_READINESS.md` and `VISUAL_QA.md`. The quality runner `.ai/tools/quality_gate.py` uses `.ai/quality/quality-gates.json` and fails on required unconfigured checks. It is not automatically enabled in CI; configure the actual project commands first. The script `.ai/tools/design_token_audit.py` audits only **declared opaque hex color pairs**, not actual UI accessibility.

When a file genuinely doesn't apply, state **N/A and why**. Change status from **Draft** to **Approved** only after review. Track significant changes with owner and date.

## v6 additional project documents

Use `THREAT_MODEL.md`, `SECURITY_VERIFICATION_REPORT.md`, `PERFORMANCE_BUDGET.md`, `LOAD_TEST_PLAN.md`, `SLO_SLI.md`, `RUNBOOK.md`, `DEPLOYMENT_PLAN.md`, `PRODUCTION_READINESS_REPORT.md`, `DATA_RECOVERY_PLAN.md` and `INCIDENT_POSTMORTEM.md` to plan and verify release safety; mark unexecuted activities NOT RUN.

## v7.1: backend/API runtime risks (only when applicable)

Complete `BACKEND_FAILURE_MODE_PLAN.md` and `BACKEND_READINESS_REPORT.md` when the project has an operational backend or other critical stateful flows. Decide required vs N/A with owner/reason and map each required case to real JUnit tests. For static-only builds, document why the extra infrastructure/runtime evidence is unnecessary rather than create fictitious tests. See `.ai/standards/RESILIENT_BACKEND_AND_FAILURE_MODES.md` and `.ai/references/FAILURE_MODE_ADOPTION.md`.

## v10 database documentation
For any project with persistence, complete `DATABASE_DECISION_RECORD.md`, `DATA_ACCESS_PATTERNS.md`, `DATABASE_ARCHITECTURE.md`, and `DATABASE_MIGRATION_PLAN.md` when applicable. Review evidence in `DATABASE_VERIFICATION_REPORT.md` and apply `.ai/standards/DATABASE_SELECTION_INTELLIGENCE.md` before choosing SQL, NoSQL, or auxiliary stores. Static projects with no storage should mark these N/A rather than invent infrastructure.

## D-34 — rancangan lengkap seperlunya, bukti dicatat bertahap

Hipotesis/expected serta kebutuhan evidence sekarang berada pada dokumen relevan. Actual lokal dan publik baru diisi setelah implementasi/pengujian masing-masing dilakukan. [QA_EVIDENCE_REPORT](QA_EVIDENCE_REPORT.md) adalah kontrak tahapan/envelope bersama: rancangan, referensi prototipe, aplikasi inti lokal, demo publik terotorisasi. Semua hasil inti tetap NOT RUN/NOT VERIFIED, reviewer/approval PENDING. Kelengkapan dokumen bukan penerimaan aplikasi.

| Kelompok dokumen | Penggunaan pada proyek ini | Pengisian sekarang / berikutnya |
| --- | --- | --- |
| Engineering, identity/security, backend, notification, design QA, QA, deployment dan database verification reports | Rencana verifikasi lokal dan publik pada scope/artefak yang tepat, mengikuti AC/CT/API-T/DBV/T/AS/N-T/BF/AV/CQ yang sudah ada | Hipotesis/expected/kebutuhan bukti sudah dirancang. Command/artefak/output/hasil/skor/temuan aktual baru setelah eksekusi; tanpa memindahkan evidence prototipe ke inti |
| PRODUCTION_READINESS_REPORT, AUTH_READINESS_REPORT, REGRESSION_REGISTER | Ringkasan kesiapan demo (nama file historis), ringkasan auth lokal dan register regresi/flake aktual | Dua ringkasan merujuk laporan sumber, tanpa gate/suite baru. Register aktual kosong sampai temuan direproduksi; bukan klaim0bug |
| APPROVAL_RECORD, INCIDENT_POSTMORTEM, REFACTOR_PLAN | Dipakai untuk persetujuan formal/insiden/refactor yang benar-benar perlu dicatat | Template tetap menunggu kebutuhan/kejadian nyata; jangan mengarang signoff/insiden/smell/baseline source. Keputusan blueprint yang sudah ada tetap pada ADR/PRD |
| COORDINATION_REPORT, TASK_BRIEF_TEMPLATE, AGENT_HANDOFF_TEMPLATE | Alat kerja opsional sesuai milestone/tugas/handoff yang benar-benar dibutuhkan | Bukan requirement produk/gate per fitur; tidak mengaktifkan format JSON, aktor, orkestrasi atau laporan baru hanya karena template tersedia |
| SOCIAL_AUTH_REQUIREMENTS, ACCOUNT_LINKING_POLICY, PROVIDER_AND_SCOPE_MATRIX, TENANT_ISOLATION_POLICY | Social/provider connect dan multi-tenant di luar scope auth lokal/satu sekolah | Template tidak diisi otomatis. N/A berlaku pada fitur itu, sementara role/anak/kelas/session dan email OTP D-25 tetap wajib menurut dokumen kanonis |

Publik berarti demo portofolio pada D-33, bukan staging permanen atau produksi sekolah. Prasyarat lokal/CI sebelum aktivasi dan smoke publik sesudah rollout dibedakan; blueprint tidak mengotorisasi implementasi/provisioning/tes/publish. Gate/validator/Markdown atau screenshot saja bukan bukti aplikasi. Untuk kasus/finding nyata kelak, pertahankan hasil gagal dan recheck; hanya tautkan path/CI artefak yang benar-benar ada dan aman diakses.
