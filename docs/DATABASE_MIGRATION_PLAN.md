# Rencana migrasi dan pemulihan database - SIA MI Alghivari

Diperbarui: 28 Oktober 2026. Pemilik/operator: pemilik proyek. Status: **blueprint**, izin eksekusi **belum diberikan**. Semua migrasi/backup/restore **NOT RUN**. Acuan: [model](DATA_MODEL.md), [provider](DATABASE_PROVIDER_IMPLEMENTATION.md), [rencana deployment](DEPLOYMENT_PLAN.md).

## 1. Batas pekerjaan

Tidak ada database lama yang diimpor. Migration awal membangun schema baru secara bertahap melalui Laravel setelah implementasi diminta. Laporan KP adalah referensi kebutuhan, bukan sumber seed pribadi. Perubahan ini tidak membuat file migration, akun SQL atau menjalankan perintah database.

Dev, CI dan demo memakai database/volume/credential terpisah. Reset hanya boleh pada resource tes yang benar-benar sementara dan teridentifikasi; jangan menjalankan fresh/reset/rollback destruktif atau menghapus volume demo untuk memulihkan rilis. Migration tidak dijalankan oleh setiap startup app/worker/Reverb.

## 2. Urutan fondasi yang diusulkan

ID berikut kelompok rencana, bukan file migration yang sudah ada. Foreign key disusun topologis dari parent ke child; semua referensi aktor ke users harus sudah tersedia.

| Kelompok | Tabel/pekerjaan | Ketergantungan dan pemeriksaan |
| --- | --- | --- |
| DBM-01 | users, user_roles, teachers, students, student_guardians, system_settings, audit_logs | Skema akun/profil, username/kolasi, batas wali baru Q6; tidak memaksakan alur akun yang belum disepakati. Siapkan mutex akademik internal. |
| DBM-02 | academic_years, terms, classrooms, subjects, class_subjects, homeroom_assignments, enrollments, teaching_assignments, schedule_slots | D26/27 parent stabil, effective dates/nonoverlap, roster_version, FK kelas/term dan source; unique siswa/term tetap. |
| DBM-03 | scoring_components, grading_policies/components, data_entry_delegations | Seed identitas komponen stabil; tidak mengaktifkan KKM/bobot contoh sebagai keputusan sekolah. assignment_requests hanya setelah alurnya disepakati. |
| DBM-04 | assessments, scores, attendance_sessions/records | Blank/nol, status kegiatan, FK/unique, versi induk dan kelas/periode konsisten. |
| DBM-05 | report_cards, report_card_items, student_progressions | Snapshot/version, rujukan report/enrollment, source/target dan apply idempoten. supersedes FK ditambahkan setelah tabel induk tersedia. |
| Infrastruktur | notifications setelah audit_logs/users; migrations, jobs/failed_jobs serta sesi/cache yang benar-benar dipilih | Dibuat saat fitur terkait dimulai, bukan harus menunggu DBM-05. Grant runtime/audit dan dedup diuji sejak fondasi. |

Akun Master awal disediakan melalui prosedur operator tanpa password contoh di repository. Seed demo sintetis eksplisit dan terpisah dari migration produksi; menjalankan upgrade tidak mengubah password, role atau data yang sudah ada.

## 3. Perubahan setelah schema pertama

| Jenis perubahan | Strategi | Pemulihan dan risiko |
| --- | --- | --- |
| Tabel/kolom baru kompatibel | Expand; nullable/default aman bila sesuai makna, deploy pembaca kompatibel | Image lama dapat dipakai bila kontrak lama tetap bekerja; kolom baru tidak harus langsung dihapus. |
| Unique/FK/CHECK/index | Audit data existing, tangani pelanggaran, rehearse waktu/metadata lock | Jangan memaksa constraint bila ada duplikat. Opsi online DDL bergantung operasi/versi, bukan jaminan tanpa lock. |
| Perubahan bentuk data | Expand -> backfill terbatas/idempoten -> verifikasi -> cutover -> contract pada rilis lain | Simpan pembaca lama selama transisi; checkpoint backfill dan compare hasil. |
| Policy/range/calculation | Revisi policy/calculation version, simpan snapshot lama | Tidak backfill nilai rapor final dengan rumus baru. Perubahan aturan Q7 memerlukan keputusan pemilik. |
| Drop/rename/type narrowing/purge | Di luar CD rutin; inventaris konsumen, backup/restore, maintenance dan persetujuan spesifik | Kembali ke image lama belum tentu aman; restore atau roll-forward sesuai keadaan aktual. |

MySQL DDL dapat menyebabkan implicit commit; atomic DDL bukan jaminan seluruh rangkaian migration dapat dibatalkan dengan ROLLBACK. Jangan menyisipkan DDL ke transaksi domain/audit. [Implicit commit](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html).

## 4. Integrasi CD

Sebelum merge, tes bootstrap schema bersih dan upgrade dari versi sebelumnya pada MySQL target, termasuk data/snapshot yang sudah ada. Job main menghasilkan image dan manifest revisi/migration yang cocok. Deployment satu target diserialkan; migrator memakai akun khusus setelah preflight, bukan credential runtime aplikasi.

CD otomatis hanya untuk perubahan kompatibel yang telah direhearsal. Koordinasikan drain worker/maintenance bila skema atau payload job tidak kompatibel. Uji kode lama dan baru bila rollout mengizinkan keduanya hidup bersamaan. Pergantian image tetap menjaga mysql/storage. Perubahan engine mayor/patch juga memiliki rehearsal, bukan mengikuti tag terbaru otomatis.

Abort jika backup tidak dapat dipakai, revisi/schema tak cocok, data melanggar constraint, lock melebihi batas yang ditetapkan, atau smoke integritas gagal. Hentikan promosi, catat schema yang benar-benar telah diterapkan, lalu pilih roll-forward atau image lama yang masih kompatibel. Jangan menjalankan down massal sebagai jalan pintas.

## 5. Backup dan restore

Baseline usulan: backup logis InnoDB, terkoordinasi dengan file privat dan manifest versi. Snapshot konsisten melalui mysqldump single-transaction tidak membenarkan DDL bersamaan. Untuk menggabungkan DB dan file secara konsisten, gunakan jendela tanpa perubahan yang relevan atau metode koordinasi yang teruji. Jangan menganggap menyalin volume MySQL yang sedang hidup adalah backup konsisten. [mysqldump](https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html).

Backup mencakup schema/data/audit/inbox/snapshot, file privat yang dirujuk, migration/versi image dan konfigurasi nonsecret. Simpan material pemulihan secret secara terpisah dan terlindungi; jangan mencampurkan APP_KEY/password/token ke laporan atau artefak CI. Cadangan yang mengandung data pribadi harus dilindungi, dibatasi akses dan berada di lokasi terpisah dari volume aktif.

Restore ke project/database/storage terisolasi terlebih dahulu. Periksa checksum arsip, hasil import, FK/unique, hitungan data, snapshot rapor, referensi file dan tes alur inti dengan akun runtime. Baru alihkan target setelah pemeriksaan/otorisasi yang berlaku. Nonaktifkan worker/tunnel saat rehearsal agar job lama tidak mengirim ulang efek; rekonsiliasi antrean/inbox sesudah pemulihan, jangan menganggap restore otomatis tepat-sekali.

D-31 menetapkan jadwal/retensi backup, D-30 tujuan Drive/manual; lokasi folder/kuota/key, batas lock/maintenance dan pelaksana capture/pemantauan masih perlu ditetapkan sebelum demo/CD aktif. Retensi3lokal/7harian+4mingguanDrive diterima; target formal RPO/RTO dikeluarkan D-32, restore fungsional tetap NOT RUN. PITR/binlog, replika dan failover bukan kemampuan yang sudah disiapkan. Penghapusan data di masa depan juga harus memperhitungkan salinan/backup dan proses restore.

## 6. Bukti yang harus direkam

Setiap rehearsal mencatat revisi sumber, digest MySQL/aplikasi, schema awal/akhir, jumlah/distribusi fixture, langkah/perintah aktual, durasi/lock, hasil integritas dan strategi pemulihan. Semua hasil masuk [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md). Tanpa bukti tersebut, status tetap NOT RUN dan kesiapan deployment belum terbukti.

## 7. Tambahan fondasi D-25

DBM-01 mencakup metadata users verified Master/forced change/versi/pending; buat otp_tokens dan sessions setelah users pada tahap identitas, tanpa menunggu akademik. Sesuaikan repository/migration/prune Fouladgar terhadap hash/schema aplikasi; jangan menjalankan vendor plaintext migration mentah atau membuat password_reset_tokens kedua. Tes clean bootstrap, reissue/verify dua koneksi, wrong-purpose/expired, transaksi password/proof/pending/audit, pilihan sesi yes/no dan user FK/grants. Tanpa backfill email_verified_at rekaan atau credential contoh di seed. Tidak ada migration dijalankan.

## 8. Fondasi akademik D-26/D-27

DBM02: classrooms/subjects -> class_subjects -> assignment dates/schedule; enrollments effective dates + roster_version pada classroom. DBM04 activity class_subject_id/source assignment composite FK + meeting unique stable; DBM05 item report unique class_subject dan teaching_history_snapshot. Saat bootstrap belum ada data lama, jadi tidak ada penyalinan/merge nilai aktif yang dijalankan. Bila perubahan fisik kemudian mengenai schema berisi data, rehearsal/backfill/FK/snapshot preservation wajib, tanpa menggabungkan otomatis histori ambigu.

Uji interval [start,end), term bounds, nonoverlap, same-date zero range, kalender sekolah, jadwal teacher baru, delegated cutoff, old/new grant race, eligible draft version/final roster dan duplicate re-enroll term sama. Tidak membuat tabel session_participants/transfer atau scheduler activation. DBV27/28 planned, seluruh migration/tes NOT RUN.

D-28 memperbarui [model logis](DATA_MODEL.md) report_cards: availability current/needs_revision/superseded, version/source_version dan metadata revocation (usulan fisik); isi snapshot/item/file tetap immutable. Tidak menambah tabel koreksi. Penulis sumber yang memengaruhi rekap menahan rapor per siswa bersama audit/inbox, dengan mutex akademik/lock deterministik; guardian query published/current aktif, bukan status saja. Unique revision tidak cukup untuk satu unfinished/satu published-current; conditional constraint + action locking perlu dibuktikan DBV29/30. Revisi mempertahankan original policy/calculation, file attach/publish recheck. Migration/SQL/EXPLAIN/runtime **NOT RUN**.

D-29 tidak menambah tabel: key school_timezone wajib/allowlist/version pada system_settings. Jangan seed default zona dari host/laporan; Master memilih WIB/WITA/WIT saat setup. Database/runtime UTC, DATE/TIME tidak direwrite ketika key berubah, file/snapshot lama tetap. Uji DBV23/API-T58 untuk restore setting dan round-trip; cap delegasi turunan DATE saat perubahan zona masih gap sebelum aktivasi terkait. DDL/migration/drill NOT RUN.

D-30: [recovery](DATA_RECOVERY_PLAN.md) menetapkan arsip terenkripsi lokal lalu upload manual pemilik ke Google Drive. Preflight target berdata membedakan set lokal, upload_pending dan salinan offhost terverifikasi; jangan menganggap capture lokal sudah melindungi kehilangan komputer. Jika perubahan berisiko memerlukan set offhost baru, tunda perubahan sampai upload/verifikasi operator selesai; jangan menampilkan CD/backup sukses palsu. Setelah capture immutable, aplikasi tidak perlu ditahan sepanjang upload manual. Ruang SSD100GB bebas saat dilaporkan diperiksa ulang untuk build/capture/download/restore; tidak purge volume/cadangan otomatis. D-31 menetapkan jadwal/retensi3lokal/7harian+4mingguanDrive; target formal RPO/RTO dikeluarkan D-32, backup/restore fungsional tetap belum diuji dan tidak ada waktu pemulihan yang dijanjikan. Semua operasi NOT RUN.

D-31: ikuti [jadwal/retensi kanonis](DATA_RECOVERY_PLAN.md) setelah demo/hari aktif dan sebelum perubahan berisiko. Pertahankan set mingguan saat label harian bergeser; verifikasi pengganti/remote serta set recoverable sebelum rotasi. Tidak melakukan pembersihan otomatis atau menurunkan retensi ketika ruang penuh. Bukti backup/rotasi/restore masih NOT RUN.
