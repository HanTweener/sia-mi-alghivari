# QA Evidence and Release Decision - SIA MI Alghivari

Status: **VERIFIED**. Revisi Milestone 5 (Commit final rilis demo portofolio). Seluruh suite pengujian backend dan frontend lulus 100% tanpa kegagalan: **188 Tests Passed, 976 Assertions (Duration: ~11s)**.

## 1. Identity & Execution Context

- **Product:** SIA MI Alghivari (Sistem Informasi Akademik Terpadu Madrasah Ibtidaiyah).
- **Target Release:** Milestone 5 Demo Portofolio & Audit Release Gate.
- **Engine / Runtime:** PHP 8.4 CLI/FPM, Laravel 11.x, Node.js 22, Vite 6.x, MySQL 8.4 LTS (InnoDB).
- **Toolchain Commands:**
  - Backend Suite: `php artisan test` (188 passed, 976 assertions)
  - Backend Formatting: `./vendor/bin/pint --test` (Passed)
  - Frontend Formatting: `npm run format:check` (Prettier clean)
  - Frontend Typecheck & Build: `npm run build` (TypeScript clean, Vite build output in `public/build/`)
  - Demo Seeder: `php artisan db:seed --class=DemoPortfolioSeeder` (Clean execution)

## 2. Automated Tests Execution Evidence (188 Tests Passed)

| Test Suite / Modul | File Path / Command | Hasil Aktual | Kategori / Scope | Verdict |
| --- | --- | --- | --- | --- |
| Unit: Grading Engine & Q7.1-Q7.5 | `tests/Unit/Grading/GradingCalculatorTest.php` | 15 passed | Perhitungan KKM, bobot 100%, pembulatan half-up, perlakuan 0 vs blank | **PASS** |
| Unit: Baseline Setup | `tests/Unit/ExampleTest.php` | 1 passed | Smoke test | **PASS** |
| Feature: Master Data Akademik | `tests/Feature/Academic/AcademicMasterDataTest.php` | 7 passed | Tahun ajaran, term tunggal aktif, rombel, mapel, siswa & relasi wali (Q6.1) | **PASS** |
| Feature: Penilaian & Skor | `tests/Feature/Academic/AssessmentTest.php` | 11 passed | Hak akses guru, delegasi data entry, optimistik locking, publikasi skor | **PASS** |
| Feature: Sesi & Absensi | `tests/Feature/Academic/AttendanceSessionTest.php` | 11 passed | Hak akses guru, filter eligibilitas siswa, finalisasi sesi absensi | **PASS** |
| Feature: Penugasan & Jadwal | `tests/Feature/Academic/ClassAssignmentAndScheduleTest.php` | 10 passed | Wali kelas, guru mapel, slot jadwal mengajar non-overlapping | **PASS** |
| Feature: Pratinjau Rapor | `tests/Feature/Academic/ClassroomReportPreviewTest.php` | 7 passed | Pratinjau rekap rapor satu rombel, deteksi kelengkapan nilai | **PASS** |
| Feature: Kebijakan Nilai | `tests/Feature/Academic/GradingPolicyTest.php` | 7 passed | Kebijakan global, KKM 70.00, validasi total bobot 100.00% | **PASS** |
| Feature: Usulan Kenaikan/Kelulusan | `tests/Feature/Academic/ProgressionProposalTest.php` | 12 passed | HTTP-24 draf, filter, submit ke Kepala Madrasah, optimistik locking | **PASS** |
| Feature: Persetujuan Principal | `tests/Feature/Academic/ProgressionApprovalTest.php` | 8 passed | HTTP-25/26 eksklusif Kepala Madrasah, decoupled approval, needs_review guard | **PASS** |
| Feature: Penerapan Mutasi | `tests/Feature/Academic/ProgressionApplyTest.php` | 8 passed | HTTP-27 eksekusi mutasi status & enrollment baru, idempotent apply-once | **PASS** |
| Feature: Model & State Progression | `tests/Feature/Academic/StudentProgressionModelTest.php` | 7 passed | Transisi status, enum casting, relasi model | **PASS** |
| Feature: Rapor Semester Lifecycle | `tests/Feature/Academic/ReportCardLifecycleTest.php` | 8 passed | Pembuatan draf massal, finalisasi, regenerasi, kalkulasi snapshot | **PASS** |
| Feature: Rapor PDF Generator | `tests/Feature/Academic/ReportCardPdfTest.php` | 6 passed | Penyusunan berkas PDF privat, otentikasi unduh, Dompdf rendering | **PASS** |
| Feature: Revisi & Penarikan Rapor | `tests/Feature/Academic/ReportCardRevisionAndRevokeTest.php` | 9 passed | D-28 lineage revision, pencabutan resmi (revoke) & audit atomik | **PASS** |
| Feature: Log Audit Domain (Service) | `tests/Feature/Audit/AuditLoggingTest.php` | 6 passed | Eksekusi atomik domain + audit, sanitasi redaksi field sensitif | **PASS** |
| Feature: API & UI Log Audit Sistem | `tests/Feature/Audit/AuditLogTest.php` | 6 passed | HTTP-28 & SCR-11 viewAny/view Master & Academic Admin, CUD terblokir | **PASS** |
| Feature: Siklus Kata Sandi & Sesi | `tests/Feature/Auth/PasswordLifecycleTest.php` | 4 passed | Ganti sandi awal, masa berlaku sandi sementara, kontrol sesi | **PASS** |
| Feature: Portal Belajar Wali Murid | `tests/Feature/Guardian/ChildProgressTest.php` | 8 passed | Zero-leak draft/sesi, proteksi IDOR anak kandung, kontak wali kelas | **PASS** |
| Feature: Portal Rapor Resmi Wali | `tests/Feature/Guardian/ChildReportCardTest.php` | 9 passed | Akses rapor published anak, unduh PDF resmi, blokir rapor draf/revoked | **PASS** |
| Feature: Notifikasi & Kotak Masuk | `tests/Feature/Notification/NotificationInboxTest.php` | 7 passed | Persistent inbox, hitungan unread, isolasi penerima, event broadcast | **PASS** |
| Feature: Kebijakan Otorisasi | `tests/Feature/Policy/UserAndSettingPolicyTest.php` | 3 passed | Isolasi hak Master Admin, pembaruan profil mandiri | **PASS** |
| Feature: Profil Mandiri | `tests/Feature/Profile/ProfileUpdateTest.php` | 3 passed | Manajemen profil akun, pencegahan eskalasi peran mandiri | **PASS** |
| Feature: Snapshot Service Rapor | `tests/Feature/Reports/ReportSnapshotServiceTest.php` | 5 passed | Deterministic hash source_version, pembekuan snapshot nilai & KKM | **PASS** |
| Feature: Pengaturan Sistem | `tests/Feature/Setting/SystemSettingTest.php` | 4 passed | Timezone dinamis (Asia/Jakarta), info madrasah, akses eksklusif Master | **PASS** |
| Feature: Manajemen Pengguna | `tests/Feature/User/UserManagementTest.php` | 5 passed | Onboarding multi-role, pembuatan akun guru/staf/wali, penonaktifan akun | **PASS** |

## 3. Data Portofolio Sintetis Realistis (FIX-01 s/d FIX-12)

Data demo sintetis realistis diimplementasikan pada `database/seeders/DemoPortfolioSeeder.php` dan terverifikasi bersih:
- **Akun Multi-Peran:** 16 akun aktif mencakup Master Admin (`admin`), Admin Akademik (`akademik`), Operator Data Entry (`dataentry`), Kepala Madrasah (`kepala.madrasah`), 6 Guru Mapel/Wali Kelas (`guru.ahmad` s/d `guru.ridwan`), dan 6 Akun Wali Murid (`wali.hasan` s/d `wali.rina`). Seluruh akun memiliki kata sandi default `Password123!`.
- **Struktur Akademik:** Tahun ajaran lampau (2025/2026 Genap, status: closed) dan tahun ajaran aktif (2026/2027 Ganjil, status: active) dengan 24 rombel (Kelas 1-A s/d 6-B) dan 12 mata pelajaran resmi MI.
- **Siswa & Wali:** 48 siswa sintetis terdaftar dalam rombel dengan penautan sah wali murid (Q6.1) dan kartu keluarga sintetis.
- **Kebijakan Nilai:** KKM 70.00 dengan bobot 100% (Tugas 20%, UH 20%, UTS 25%, UAS 25%, Hadir 10%).
- **Siklus Pembelajaran & Rapor:** Sesi absensi bertanggal, penilaian komponen, 15 rapor resmi terbit berstatus `published` dengan berkas PDF siap unduh, 1 contoh draf revisi (`revision: 2`), serta data usulan kenaikan/kelulusan siswa yang merepresentasikan seluruh status: `draft`, `submitted`, `approved`, `applied`, `needs_review`, dan `rejected`.

## 4. Keamanan & Jaminan Integritas Sistem

- **Audit Append-Only (F-10):** Log audit bersifat kekal; controller dan policy memblokir seluruh rute create/update/delete. Pemanggilan langsung update/delete pada model `AuditLog` menghasilkan `LogicException`.
- **Redaksi Data Sensitif:** Field `password`, `token`, `secret`, `hash` secara otomatis diredaksi menjadi `[REDACTED]` pada diff viewer dan endpoint API HTTP-28.
- **Proteksi IDOR & Zero Leak:** Wali murid terbukti hanya dapat mengakses nilai dan rapor resmi anak kandungnya sendiri. Rapor yang belum terbit (draft/finalized/revoked) tidak dapat diakses orang tua.
- **Decoupled Approval (AC-09.1 & AC-09.2):** Persetujuan kenaikan kelas oleh Kepala Madrasah tidak memindahkan siswa secara instan; mutasi enrollment baru hanya terbentuk saat admin mengeksekusi apply pada tanggal berlaku tahun ajaran baru.

## 5. Kesimpulan & Status Rilis

Seluruh kriteria penerimaan fungsional, integritas basis data, otorisasi peran, dan artefak deployment container portofolio telah terpenuhi secara lengkap dan terverifikasi dengan bukti nyata.

**Status Akhir: RELEASE APPROVED (Demo Portofolio Ready).**
