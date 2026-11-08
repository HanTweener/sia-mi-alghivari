# Laporan Verifikasi Database - SIA MI Alghivari

**Diperbarui:** Milestone 5 (Commit Final Rilis Demo Portofolio)  
**Status Database:** **VERIFIED & OPERATIONAL**  
**Engine:** MySQL 8.4 LTS (InnoDB) / SQLite in-memory untuk pipeline pengujian cepat.

---

## 1. Ringkasan Eksekusi Migrasi & Skema

Total 16 berkas migrasi telah diterapkan secara bersih dan berurutan tanpa galat:
1. `2026_01_01_000000_create_users_table.php` (Akun pengguna, hashed password, credential versioning)
2. `2026_01_01_000001_create_user_roles_table.php` (Multi-peran fleksibel)
3. `2026_01_01_000002_create_system_settings_table.php` (Parameter sekolah & timezone dinamis D-29)
4. `2026_01_01_000003_create_audit_logs_table.php` (Audit trail append-only F-10)
5. `2026_01_01_000004_create_notifications_table.php` (Inbox persisten F-11)
6. `2026_01_01_000010_create_academic_years_and_terms_tables.php` (Tahun ajaran & semester)
7. `2026_01_01_000011_create_classrooms_table.php` (Rombel kelas 1 s/d 6, index <= 64 karakter)
8. `2026_01_01_000012_create_subjects_table.php` (Mata pelajaran resmi MI)
9. `2026_01_01_000013_create_students_and_guardians_tables.php` (Data siswa & penautan wali sah Q6.1)
10. `2026_01_01_000020_create_homeroom_assignments_and_enrollments_tables.php` (Wali kelas & riwayat pendaftaran)
11. `2026_01_01_000021_create_class_subjects_and_teaching_assignments_tables.php` (Mapel kelas & jadwal non-overlapping)
12. `2026_01_01_000022_create_grading_policies_and_scoring_components_tables.php` (Kebijakan KKM & bobot 100%)
13. `2026_01_01_000023_create_data_entry_delegations_and_attendance_tables.php` (Sesi absensi & delegasi entri)
14. `2026_01_01_000024_create_assessments_and_scores_tables.php` (Penilaian terstandardisasi & skor numerik)
15. `2026_01_01_000025_create_report_cards_and_items_tables.php` (Rapor semester resmi, snapshot nilai, source_version VARCHAR(100))
16. `2026_01_01_000030_create_student_progressions_table.php` (Kenaikan kelas & kelulusan siswa, index singkat `sp_*` kompatibel MySQL 8.4)

---

## 2. Matriks Verifikasi Kasus Basis Data (DBV)

| ID | Skenario dan Hasil yang Diharapkan | Metode & Bukti Pengujian | Status Aktual |
| --- | --- | --- | --- |
| **DBV-01** | Bootstrap schema bersih, seluruh foreign key, unique constraint, dan index tersusun rapi pada InnoDB. | `php artisan migrate:fresh` pada MySQL 8.4 LTS; semua tabel beroperasi tanpa peringatan identifier length. | **PASS** |
| **DBV-03** | Username dan email unik; integritas format nomor induk siswa (NIS) terjaga. | Uji duplikasi pada `UserManagementTest` dan `AcademicMasterDataTest`. | **PASS** |
| **DBV-04** | Siswa hanya terdaftar di satu rombel per semester aktif (`unique(['student_id', 'term_id'])`). | Uji constraint enrollment ganda ditolak pada tingkat basis data. | **PASS** |
| **DBV-06** | Hanya satu semester aktif secara sekolah luas (`is_active` tunggal). | Uji aktivasi semester pada `AcademicMasterDataTest`. | **PASS** |
| **DBV-07** | Kebijakan nilai global berbobot tepat 100.00% dan KKM 70.00. Nilai di luar 100% ditolak. | Uji matematis pada `GradingPolicyTest` dan `GradingCalculatorTest`. | **PASS** |
| **DBV-08** | Pembedaan tegas antara nilai kosong (blank) dan nol (0). Nilai 0 sah masuk pembagi rata-rata; blank dilewati. | Uji deterministik pada `GradingCalculatorTest` dan `AssessmentTest`. | **PASS** |
| **DBV-10** | Penilaian dan absensi lintas rombel/siswa yang tidak terdaftar ditolak oleh foreign key dan policy. | Uji negative boundary pada `AttendanceSessionTest` dan `AssessmentTest`. | **PASS** |
| **DBV-13** | Mutasi domain, pencatatan log audit, dan pengiriman notifikasi dieksekusi secara atomik dalam satu transaksi DB. | Uji rollback failure injection pada `AuditLoggingTest`. | **PASS** |
| **DBV-14** | Tabel `audit_logs` bersifat murni append-only. Hak CUD diblokir permanen via policy, controller, dan model exception. | Uji rute modifikasi HTTP dan model `LogicException` pada `AuditLogTest`. | **PASS** |
| **DBV-15** | Data sensitif (password hash, token, session) diredaksi menjadi `[REDACTED]` saat direkam dan ditampilkan. | Uji redaksi payload pada `AuditLogTest` dan sanitasi di `AuditService`. | **PASS** |
| **DBV-18** | Jaminan privasi rapor wali murid (Zero Leak). Hanya rapor berstatus `published` yang dapat diakses; draf tidak bocor. | Uji proteksi IDOR dan visibilitas pada `ChildReportCardTest`. | **PASS** |
| **DBV-20** | Pemisahan persetujuan dari mutasi fisik (Decoupled Approval). Mutasi `applied` membentuk enrollment baru tanpa menghapus arsip. | Uji alur kenaikan pada `ProgressionProposalTest`, `ProgressionApprovalTest`, dan `ProgressionApplyTest`. | **PASS** |
| **DBV-23** | Pengaturan satu zona waktu operasional (`Asia/Jakarta`) tersimpan di `system_settings` dan konsisten lintas sistem. | Uji pengaturan di `SystemSettingTest`. | **PASS** |
| **DBV-25** | Isolasi penyimpanan data dan volume kontainer (`demo_mysql_data`, `demo_storage_data`) aman saat kontainer di-restart. | Uji konfigurasi di `docker-compose.demo.yml`. | **PASS** |

---

## 3. Verifikasi Seeder Portofolio Sintetis (`DemoPortfolioSeeder`)

Data portofolio sintetis realistis (`FIX-01` s/d `FIX-12`) telah disemai dan diverifikasi:
- **Tabel `users` & `user_roles`:** 16 entri akun multi-role terhubung dengan benar.
- **Tabel `academic_years` & `terms`:** 2 tahun ajaran (2025/2026 dan 2026/2027) serta 3 term (2 lampau, 1 aktif).
- **Tabel `classrooms` & `subjects`:** 24 rombel (12 per semester) dan 12 mata pelajaran resmi.
- **Tabel `students` & `student_guardians`:** 48 siswa aktif terdaftar dengan nomor induk resmi dan terhubung ke wali murid binaan.
- **Tabel `grading_policies` & `components`:** 1 kebijakan aktif (KKM 70, bobot 100%).
- **Tabel `attendance_sessions` & `records`:** Sesi pertemuan 1-3 lengkap dengan status kehadiran (hadir, sakit, izin).
- **Tabel `assessments` & `scores`:** Penilaian tugas dan ulangan harian dengan skor tuntas/perlu bimbingan.
- **Tabel `report_cards` & `report_card_items`:** 15 rapor semester resmi terbit (`published`) beserta berkas PDF tersimpan di disk lokal privat, serta 1 draf revisi rapor.
- **Tabel `student_progressions`:** 8 usulan komprehensif mencakup kenaikan kelas, kelulusan, dan tinggal kelas dengan seluruh representasi status: `applied`, `approved`, `submitted`, `draft`, `needs_review`, dan `rejected`.
- **Tabel `audit_logs` & `notifications`:** Jejak audit domain lengkap dan pesan notifikasi terdistribusi ke masing-masing aktor.

**Kesimpulan:** Basis data memenuhi seluruh standar integritas, keandalan relasional, dan kesiapan operasional demo.
