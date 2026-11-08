# Rancangan implementasi database provider - SIA MI Alghivari

Diperbarui: 28 Oktober 2026. Pemilik/operator: pemilik proyek. Status: **PROPOSED / NOT IMPLEMENTED**. Provider pada dokumen ini berarti implementasi engine MySQL di Compose, bukan layanan cloud yang telah dipilih. Tidak ada akun SQL, DDL, migration atau container yang dibuat.

## 1. Kontrak engine

| Bagian | Rancangan | Bukti yang kelak diperlukan |
| --- | --- | --- |
| Engine | Baseline MySQL 8.4 LTS, seluruh tabel InnoDB, disepakati 29 Oktober 2026; Community Server dan patch/image masih rancangan | Versi/edition aktual dan daftar engine tabel. |
| Image | Citra MySQL tepercaya, patch/digest terkunci; tidak memakai tag mutable tanpa identitas versi | Digest, arsitektur CPU dan hasil startup. |
| Driver | PDO MySQL/mysqlnd sesuai PHP Laravel yang dipilih | Versi ekstensi, koneksi dan tes integrasi. |
| Encoding | utf8mb4; kolasi teks umum dan kolasi identitas dipilih eksplisit | Unicode, case/accent dan unique username/email/code diuji. |
| SQL/isolation | Strict mode, FK/CHECK aktif; baseline InnoDB REPEATABLE READ dengan locking read untuk keputusan tulis | Session mode/isolation nyata pada HTTP/worker/CI. Jangan mengandalkan default image tanpa inspeksi. |
| Waktu | Koneksi DB/runtime/audit UTC; DATE/TIME literal, school_timezone dinamis WIB/WITA/WIT untuk kalender/tampilan | D-29 round-trip serta race setting vs grant/semester; nilai awal setup dan rekonsiliasi cap delegasi masih terbuka. |
| Lokasi | Network privat Compose, volume mysql persisten, dev/demo/CI terpisah | Tidak ada ingress DB publik; restart/recreate menjaga data. |
| Transport/storage | Tidak lewat tunnel publik. TLS wajib bila koneksi melintasi host/network tak tepercaya; lindungi disk host dan backup | Konfigurasi TLS/enkripsi aktual; network Docker saja bukan enkripsi. |

Patch, collation final, kapasitas, koneksi/timeout, ekstensi dan opsi durability ditetapkan setelah pengukuran. Jangan menurunkan durability demi benchmark. [Panduan MySQL lokal](../.ai/references/database-profiles/mysql/ENGINEERING_GUIDE.md).

## 2. Representasi dan constraint

ID internal direkomendasikan BIGINT unsigned dengan tipe FK identik; NIS/staff_number/code tetap teks agar nol awal tidak hilang. Ukuran teks dipilih berdasarkan validasi nyata. Status memakai kode tetap yang dibatasi, bukan istilah bebas dari browser. DECIMAL digunakan untuk skor/bobot/KKM, tidak FLOAT/DOUBLE; serialisasi nilai desimal ke PHP/TypeScript tidak boleh mengubahnya menjadi sumber hitungan float.

scores.score direkomendasikan DECIMAL(5,2) NOT NULL; tidak ada baris berarti blank, bukan NULL numerik atau 0 otomatis. Q7.2 menerima skor input 0-100 inklusif/maksimal dua desimal; rancangan CHECK rentang mengikutinya. Validasi pecahan asli wajib sebelum cast/insert: DECIMAL(5,2) tidak membuktikan input tiga desimal ditolak karena konversi DB dapat membulatkannya. Belum DDL/tes; batas ini bukan keputusan presisi hitung/pembulatan akhir atau input KKM/bobot. Q7.5 menerima KKM/bobot 0-100 inklusif/maksimal dua desimal: target grading_policies.kkm dan grading_policy_components.weight_percentage DECIMAL(5,2), CHECK 0-100. Pecahan asli divalidasi sebelum cast/insert, tidak mengandalkan konversi DECIMAL untuk menolaknya. Total bobot tepat100 diperiksa lintas baris di action pada aktivasi, tanpa epsilon/pembulatan total; draft yang belum100 tidak aktif. Belum DDL/tes. Q7.3 hasil akhir disimpan dengan dua desimal dari total presisi penuh; nilai antara tidak dibatasi oleh tipe scores.score. Detail library/representasi hitung harus membuktikan oracle Q7.3. Batas validasi konfigurasi mengikuti Q7.5; tipe/constraint fisik tetap harus diimplementasikan dan diuji; kebijakan rounding/calculation_version dibekukan pada snapshot.

Foreign key menggunakan unique key rujukan yang eksplisit, termasuk classrooms(id, term_id) untuk enrollment. Hindari bergantung pada FK ke indeks nonunik. RESTRICT/NO ACTION untuk data historis; audit target menyimpan identitas stabil tanpa cascade. Aturan satu kelas/periode antara score/session dan enrollment tetap dicek aplikasi karena model tidak menggandakan semua kolom kelas pada setiap child.

### Unique bersyarat MySQL

MySQL tidak diasumsikan memiliki sintaks partial index PostgreSQL. Rekomendasi: generated column yang bernilai kunci saat status relevan dan NULL untuk histori, lalu UNIQUE. MySQL mengizinkan beberapa NULL pada unique index; generated column dapat diindeks unik. [CREATE INDEX](https://dev.mysql.com/doc/refman/8.4/en/create-index.html), [generated indexes](https://dev.mysql.com/doc/refman/8.4/en/create-table-secondary-indexes.html).

| Kebutuhan | Kunci konseptual yang diusulkan |
| --- | --- |
| Wali kelas aktif | Nilai classroom_id jika active, selain itu NULL; unique pada hasil. Bukan unique classroom_id untuk seluruh histori. |
| Mapel kelas dan riwayat guru D26 | Unique class_subjects(classroom_id,subject_id). Nonoverlap rentang assignment di bawah lock parent/mutex, bukan unique flag active saja; grant assigned+tanggal berlaku. |
| Semester aktif global | Konstanta 1 jika active, selain itu NULL; unique. |
| Grading policy aktif global | Konstanta 1 jika active, selain itu NULL; unique. |
| Satu komponen otomatis kehadiran | Slot 1 untuk source_type attendance; unique agar tidak dihitung dua kali. Katalog score tetap dapat bertambah. |
| Keputusan naik/lulus aktif | Slot tahun sumber untuk status keputusan yang masih berlaku, selain itu NULL; unique(student_id, hasil). Status applied tetap menempati slot. Daftar status harus konsisten dengan transisi. |

Nama kolom/index dan ekspresi SQL final baru dibuat pada migration. Perubahan definisi status perlu memeriksa slot historis. Query cek-kemudian-insert tanpa unique tidak cukup menghadapi race. CHECK per baris tidak boleh digunakan untuk menjamin SUM bobot atau membaca tabel lain. [CHECK constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html).

## 3. Lock, versi dan transaksi

Gunakan transaksi satu koneksi InnoDB untuk domain/audit/inbox. Baseline sederhana yang diusulkan adalah satu baris mutex akademik internal pada system_settings, disiapkan bootstrap, bukan field konfigurasi UI. Seluruh penulis akademik yang memengaruhi snapshot mengambil SELECT FOR UPDATE atas baris itu lebih dahulu; nilainya tidak perlu diubah. Ini menserialkan penulisan akademik/finalisasi satu sekolah tanpa service lock tambahan. Baris tidak dibuat secara lazy di tengah request; bila hilang operasi gagal aman.

Setelah mutex, urutkan lock induk/objek konsisten dan cek ulang role, penugasan, delegasi, status serta version. Lock penggantian wali tetap pada classroom yang sudah ada. Sesi/nilai menggunakan version integer induk yang dinaikkan dalam conditional update; zero affected rows berarti konflik. Detail kolom teknis merupakan usulan, bukan schema yang sudah ada.

Pada REPEATABLE READ, keputusan mutasi memakai locking read terbaru; jangan membaca snapshot biasa lebih dahulu lalu menganggapnya keadaan terakhir setelah menunggu lock. Lock tidak menggantikan unique/FK. Retry deadlock/timeout hanya seluruh unit aman dengan batas yang ditetapkan dan hasil idempoten. Tidak memakai SKIP LOCKED untuk melewati siswa invalid pada batch atomik. [Locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).

Mutex membatasi paralelisme penulisan, sehingga durasi transaksi harus singkat dan batch dibatasi. Jangan menahan mutex selama render PDF/broadcast. Ukur lock wait sebelum beralih ke protokol per-term yang lebih rumit; perubahan itu memerlukan bukti konsistensi global policy/finalisasi.

## 4. Identitas database dan audit

| Identitas | Hak yang direncanakan | Pembatasan |
| --- | --- | --- |
| Runtime aplikasi/worker | DML minimum per tabel sesuai pekerjaan | audit_logs hanya SELECT/INSERT; tanpa UPDATE/DELETE, DDL, GRANT atau hak administratif. |
| Migrator | DDL/DML yang diperlukan pada database target | Hanya langkah deployment/migration; secret tidak masuk container runtime tetap. |
| Backup | Hak baca/dump yang dibutuhkan metode terpilih | Tidak memakai root; privilege detail mengikuti opsi dump dan diuji. |
| Operator darurat | Akses pemulihan yang dikendalikan pemilik | Di luar role Master aplikasi; tindakan ditelusuri terpisah. |

Hak MySQL bersifat aditif. Jangan memberi UPDATE/DELETE pada database.* lalu menganggap REVOKE per tabel meniadakannya: gunakan grant per tabel tanpa hak luas yang menutupi batas audit. Setiap tabel baru memerlukan pembaruan grant dan tes akun runtime. [GRANT](https://dev.mysql.com/doc/refman/8.4/en/grant.html).

Audit INSERT hanya melalui service internal dalam transaksi domain; tidak ada API/form insert pengguna. Akun SQL runtime yang dapat INSERT tidak membuktikan jejak tahan terhadap kompromi server/DBA. Append-only membatasi UPDATE/DELETE akun runtime dan seluruh role aplikasi, bukan menjanjikan storage mustahil diubah administrator.

## 5. Indeks, operasi dan keluaran implementasi

Indeks prioritas ada pada [DATA_ACCESS_PATTERNS.md](DATA_ACCESS_PATTERNS.md). Hindari indeks duplikat leftmost-prefix dan pengindeksan JSON snapshot tanpa query nyata. Uji EXPLAIN pada query berizin dengan distribusi fixture yang dicatat; jangan membuat benchmark palsu dari jumlah baris perkiraan.

Saat implementasi diminta, keluaran yang diharapkan: migration/constraint bernama, konfigurasi koneksi tanpa secret, grant terpisah, bootstrap mutex/katalog, seed sintetis, healthcheck, backup/restore serta tes pada image MySQL yang sama dengan demo. KKM/bobot tidak di-hardcode sebagai default yang diklaim disetujui; aktivasi dilakukan admin setelah validasi.

Sesi/cache mengikuti driver final Laravel; jobs/failed_jobs dan tabel migrations adalah infrastruktur. Belum menambah broker, provider managed, replika atau binlog/PITR operasional. Rencana perubahan/pemulihan: [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md). Hasil aktual: [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md), seluruhnya **NOT RUN**.

## 6. Infrastruktur password D-25

Target [DATA_MODEL](DATA_MODEL.md): users metadata forced change/verified Master/credential_version/session_generation/pending; otp_tokens berisi hash dan lifecycle proof; sessions database. Migrasi vendor Fouladgar auto-load tidak otomatis cocok: konfigurasi/binding repository serta migration/prune perlu adaptasi, tanpa dua tabel/password broker paralel. Lock users -> otp_tokens, reissue/verify/complete/pilihan sesi diserialisasi; tidak memakai mutex akademik atau memegang lock selama SMTP. Unique slot akun/tujuan, FK RESTRICT, expiry/pruning dan grant runtime diuji. OTP hash at rest wajib, bukan raw token vendor. Pemisahan credential/session version menjaga No tetap mempertahankan sesi; auth.session hash mismatch bukan implementasi yang sesuai. Semua DDL/race/grant/runtime NOT RUN.

## 7. Class-subject, rentang dan frozen roster D-26/D-27

Schema DATA_MODEL0.6: class_subjects sebelum assignments/schedule; unique(parent kelas,mapel), assignment unique(id,class_subject_id) mendukung FK gabungan source activity. Session unique(class_subject_id,meeting_number), report item unique(report_card_id,class_subject_id). Enrollment tetap unique(student_id,term_id) termasuk ended; FK classroom/term konsisten. Status assignment assigned/cancelled + rentang DATE akhir eksklusif menentukan efektif, bukan scheduler active flag.

Mutex akademik -> class_subject/classroom -> assignment/enrollment -> draft/final object dalam urutan konsisten. Handover cek overlap seluruh interval dan jadwal guru baru, akhiri batas delegasi lama/audit/inbox atomik; tidak network/file dalam lock. Enrollment change menolak perbedaan final roster/nilai published applicability, menaikkan roster_version. Finalize recheck version + eligible set/status dan membekukan records; report count record final, bukan refilter status enrollment sekarang. CHECK per baris tidak membuktikan overlap lintas row atau kesamaan konteks enrollment-activity. DBV27/28 menguji dua koneksi, FK, date boundary dan grants. Physical SQL/migration/runtime NOT RUN.

Cutover delegasi D26: handover yang tanggalnya belum tiba tidak menulis revoked_at=sekarang untuk memutus hak lebih awal. Batasi valid_until sesuai akhir assignment dan evaluasi grant tanggal efektif; delegasi tidak terbawa ke assignment baru. Konversi tanggal memakai school_timezone terkonfigurasi (D-29); rekonsiliasi cap delegasi dari DATE terhadap UTC existing saat zona berubah masih OPEN, tanpa rebase otomatis. Ini detail implementasi yang harus diuji, bukan scheduler/fitur baru.

D-28 memperbarui [model logis](DATA_MODEL.md) report_cards: availability current/needs_revision/superseded, version/source_version dan metadata revocation (usulan fisik); isi snapshot/item/file tetap immutable. Tidak menambah tabel koreksi. Penulis sumber yang memengaruhi rekap menahan rapor per siswa bersama audit/inbox, dengan mutex akademik/lock deterministik; guardian query published/current aktif, bukan status saja. Unique revision tidak cukup untuk satu unfinished/satu published-current; conditional constraint + action locking perlu dibuktikan DBV29/30. Revisi mempertahankan original policy/calculation, file attach/publish recheck. Migration/SQL/EXPLAIN/runtime **NOT RUN**.
