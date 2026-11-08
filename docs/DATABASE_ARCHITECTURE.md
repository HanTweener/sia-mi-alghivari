# Arsitektur database - SIA MI Alghivari

Diperbarui: 28 Oktober 2026. Pemilik: pemilik proyek. Status: **blueprint / PROPOSED**, tanpa DDL/database berjalan. Baseline MySQL 8.4 LTS/InnoDB disepakati pemilik 29 Oktober 2026; patch/image, konfigurasi dan rancangan fisik belum dikunci atau diuji. Semua tes runtime **NOT RUN**.

Acuan: [model logis](DATA_MODEL.md), [DB-001](DATABASE_DECISION_RECORD.md), [PRD](PRD.md), [akses sumber daya](RESOURCE_ACCESS_POLICY.md). DATA_MODEL menentukan makna tabel/relasi; dokumen ini menentukan batas penyimpanan, transaksi dan operasi.

## 1. Sumber kebenaran

| Kelompok | Data dan klasifikasi | Penulis/pembaca | Konsistensi |
| --- | --- | --- | --- |
| Identitas | users, user_roles, teachers, students, student_guardians; pribadi/keamanan | Action server sesuai policy | Peran/relasi aktif dicek lagi saat operasi. |
| Akademik | Periode, rombel, enrollment, penugasan, jadwal, katalog/policy bobot | Master/Akademik; Data Entry terbatas | Constraint dan transaksi mengendalikan relasi/aktivasi. |
| Kegiatan | assessments/scores, attendance_sessions/records; pribadi | Guru/delegasi/admin sesuai scope | Batch atomik, draft berbeda dari published/finalized. |
| Rapor | report_cards/items; snapshot versi aplikasi | Master/Akademik/wali kelas binaan; wali hanya versi published/current aktif anak | Isi dibekukan saat finalized; metadata hold/revoke/supersede berubah melalui command berizin dan audit D-28. |
| Naik/lulus | student_progressions, enrollment/status hasil apply | Principal approve; pelaksana menurut keputusan final Q6 | Approval/apply terpisah, cek sumber/versi dan cegah apply ganda. |
| Audit/inbox | audit_logs append-only, notifications persisten; metadata sensitif | Sistem menulis, pembaca menurut scope | Domain + audit + inbox satu transaksi. |
| Infrastruktur | migrations, jobs/failed_jobs, sesi/cache bila dipilih | Framework, bukan endpoint pengguna | Tidak memicu audit/notifikasi domain rekursif. |
| File privat | PDF/foto pada volume, referensi DB | Server berizin | Bukan satu transaksi ACID dengan DB. |

Satu database per lingkungan, tanpa replica/store turunan. Pemilik proyek mengelola backup/restore. CDC/replication/replay antardatabase **N/A untuk inti**. Jika realtime mati, inbox DB tetap tersedia. Jika DB gagal, simpan/finalisasi ditolak; jangan mengembalikan sukses palsu dari cache.

## 2. Batas akses dan histori

Sekolah tunggal, tanpa tenant_id buatan. Isolasi anak, rombel, periode, mapel dan field wajib pada query daftar/count, objek, bulk, file, audit dan worker. FK bukan bukti izin. React/Echo tidak memiliki credential atau akses SQL langsung.

Role rangkap tidak melewati aturan status/audit. Koreksi sumber serta revisi/revoke rapor memakai command D-28; isi rapor final tidak diedit lewat CRUD biasa. Wali kelas berasal dari homeroom_assignments aktif, bukan classrooms.teacher_id. Perkembangan wali mengambil aktivitas yang dibagikan/disahkan tanpa syarat report_cards; detail/unduh rapor wali hanya snapshot published/current aktif anak terkait.

Relasi yang telah dipakai histori tidak dipindah/dihapus lewat CRUD biasa. Gunakan penugasan baru, arsip/pembatalan atau revisi yang diaudit. Tidak ada cascade yang menghapus nilai, rapor, keputusan atau audit. Retensi/penghapusan permanen domain belum diputuskan; jadwal/retensi backup D-31 diterima dengan syarat pengganti/remote terverifikasi dan set recoverable sebelum rotasi. Jangan membuat purge otomatis atau menghapus data domain berdasarkan retensi backup.

## 3. Integritas berlapis

| Aturan | Database | Action server dalam transaksi |
| --- | --- | --- |
| Satu siswa per semester | Unique(student_id, term_id), FK gabungan classroom/term | Validasi periode, status dan scope. |
| Satu wali kelas aktif/rombel | Unique bersyarat melalui generated column; inactive tetap historis | Lock rombel, tutup lama dan buat baru. |
| Identitas mapel kelas dan histori guru D-26 | Unique class_subjects(classroom_id,subject_id); teaching_assignments menyimpan histori assigned/cancelled dengan rentang [starts_on,ends_on), tanpa unique flag active harian | Sesudah mutex akademik, lock class_subject sebelum cek/tulis seluruh interval assigned yang relevan agar tidak overlap; grant berasal dari assignment yang efektif pada tanggal sekolah. Parent historis tidak dipindah. |
| Satu semester/policy global aktif | Slot aktif unik pada tabel masing-masing | Aktivasi atomik, cek versi preview dan pertahankan histori. |
| Total bobot 100 | DECIMAL, CHECK per baris, unique policy/component | Lock policy, baca seluruh set, cek total dan aktivasi bersama. |
| Skor/absensi tidak ganda | Unique assessment/enrollment dan session/enrollment, FK/status | Enrollment harus satu rombel/periode dengan kegiatan. |
| Blank berbeda dari nol | Blank tidak mempunyai baris scores; score numerik NOT NULL, 0 sah | Tidak memakai truthiness/COALESCE blank menjadi nol. |
| Rapor/naik-lulus | Unique revisi dan FK sumber/tujuan | Cek kelengkapan positif, kelas/periode, policy, approval dan transisi. |

CHECK/FK tidak menjamin seluruh aturan lintas tabel atau otorisasi. Q7.2 skor input 0-100 inklusif/maksimal dua desimal diterima; validasi pecahan asli sebelum cast/insert dan CHECK rentang diperlukan. Q7.3 pembulatan akhir dua desimal half-up tanpa pembulatan antara diterima; comparator Q7.4 diterima (hasil akhir dibulatkan >= KKM); presisi konfigurasi Q7.5 juga diterima; implementasi belum diverifikasi. Detail fisik: [DATABASE_PROVIDER_IMPLEMENTATION.md](DATABASE_PROVIDER_IMPLEMENTATION.md).

## 4. Transaksi dan perubahan bersamaan

Urutan umum: validasi bentuk -> transaksi -> lock mutex akademik internal -> lock induk dalam urutan tetap -> baca ulang keadaan/izin -> validasi seluruh batch -> tulis domain -> audit per objek -> inbox terkelompok -> commit -> dispatch broadcast. Gagal persist salah satunya membatalkan seluruh batch. Tidak memasukkan rendering PDF, jaringan atau broadcast ke dalam lock transaksi.

| Operasi | Protokol yang direncanakan |
| --- | --- |
| Nilai/absensi | Lock assessment/session dan token versi induk; form lama ditolak, bukan last-write-wins diam-diam. |
| Penugasan wali kelas | Sesudah mutex akademik, lock classroom yang sudah ada; unique bersyarat homeroom active melindungi race awal/penggantian, inactive tetap histori. |
| Penugasan guru mapel D-26 | Sesudah mutex akademik, lock class_subject yang sudah ada, lalu assignment/objek terkait dalam urutan tetap. Cek nonoverlap [starts_on,ends_on), jadwal guru pengganti dan batas delegasi; tutup/buat histori, audit dan inbox atomik. Tidak ada scheduler pengubah flag active harian. |
| Jadwal | Lock term sebelum cek/tulis interval guru/kelas; query validasi sebelum transaksi saja tidak cukup. |
| Policy/bobot | Sesudah mutex bersama, lock policy untuk seluruh perubahan draft; aktivasi mengecek set/versi global dan unique slot. |
| Finalisasi rapor | Mutex bersama mencegah penulis sumber/policy menyela; baca sumber secara konsisten dan lock enrollment/report. Lock report saja tidak cukup. |
| Approve/apply | Lock proposal dan sumber/tujuan dalam urutan tetap; cek version/status dan audit bersama hasil. |

Rekomendasi sederhana: satu baris mutex internal pada system_settings, disiapkan bootstrap dan tidak diekspos sebagai konfigurasi UI. Semua penulis akademik yang memengaruhi sumber/snapshot, termasuk katalog, policy, perubahan periode dan approval/apply, mengambil SELECT FOR UPDATE pada baris itu lebih dahulu tanpa mengubah nilainya. Setelah itu lock induk/objek sesuai action dalam urutan deterministik. Gunakan pembacaan terbaru untuk keputusan mutasi setelah menunggu lock, bukan snapshot lama. Baris harus sudah ada; jika hilang operasi gagal aman.

Protokol yang sama wajib diterapkan semua penulis. Serialisasi seluruh penulisan akademik ini konservatif untuk satu sekolah; jaga transaksi singkat dan ukur contention sebelum mempersempit ke lock per-term. Tidak perlu tabel/service lock baru. Mutex bukan pengganti FK/unique atau otorisasi.

Token optimistic yang disarankan berupa version integer pada induk yang diedit; bila memakai updated_at, presisi/conditional update wajib diuji. Kolom teknis ini masih rancangan, belum migration. Retry deadlock dibatasi pada seluruh transaksi yang aman/idempoten, bukan hanya statement terakhir. Unique bisnis mencegah record ganda; request batch yang perlu retry harus mempunyai identitas operasi/dedup terikat pengguna/scope/payload. Desain per action, tanpa engine idempotensi umum.

## 5. Snapshot, file dan efek setelah commit

Finalisasi menyimpan snapshot identitas/penugasan historis, policy/calculation version, label/KKM/bobot, hitungan numerik/blank, kehadiran dan hasil. JSON hanya untuk snapshot/diff terstruktur dengan schema version; data operasional dan relasi tetap relasional. Nama guru atau konfigurasi terbaru tidak mengubah snapshot lama.

Render PDF setelah snapshot finalized, di luar lock. Tulis file sementara, selesaikan penulisan, lalu tautkan ke revisi yang masih sah. Publish mengecek finalized dan kesiapan file sebelum membuka akses. Gagal file dapat dicoba ulang tanpa published palsu; orphan dibersihkan melalui prosedur terkontrol. Jangan mengklaim DB dan filesystem satu transaksi atomik.

Broadcast setelah commit lewat database queue dapat gagal sebelum enqueue. Inbox tetap sumber catch-up; tidak menambah outbox pada inti karena sinyal realtime boleh terlewat. Jika kelak efek harus dijamin dapat diputar ulang, kaji outbox terpisah. [Notifikasi](NOTIFICATION_REQUIREMENTS.md).

## 6. Query, kapasitas dan operasi

Indeks awal berasal dari FK/unique dan [pola akses](DATA_ACCESS_PATTERNS.md). Batasi hasil/pagination, pilih field berizin, eager-load seperlunya dan agregasikan terkelompok. Ukur query plan, latency dan SQL count sebelum menambah indeks; tidak menambah cache nilai/partitioning sekarang.

Budget koneksi mencakup HTTP, worker, migrasi dan backup dengan cadangan operator. Batas koneksi, timeout, SLO dan kapasitas belum ditetapkan/diukur. Biaya meliputi RAM/CPU host, disk tabel/indeks/log/backup dan pemeliharaan, bukan sekadar ukuran data.

D-29: timestamp kejadian/audit UTC, tanggal DATE dan jam TIME literal. school_timezone satu pilihan WIB/WITA/WIT oleh Master, nilai IANA di system_settings dan versi key; tidak mengikuti timezone host/browser. Nilai awal dipilih saat setup. Mutasi setting dan akademik diserialisasi mutex/key/parent, stale timezone-version meminta review. File/snapshot final tetap; cap delegasi turunan DATE terhadap UTC existing ketika zona berubah masih OPEN.

Pemulihan: [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md). Seluruh rencana tes dan bukti aktual: [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md).

## Infrastruktur identitas D-25

[DATA_MODEL bagian2](DATA_MODEL.md) menambah otp_tokens hash/proof lifecycle dan users forced-change/verified Master/credential_version/session_generation/pending. Sesi database dipilih, tanpa Redis; ini bukan tabel akademik baru. Lock users -> challenge dan conditional pending operation melindungi verify/reissue/complete/pilihan sesi; password/proof/pending/audit satu transaksi, tanpa SMTP di dalam lock. Repository/binding/migration/prune Fouladgar perlu disesuaikan terhadap storage hash; source vendor tidak menjamin atomic consume. DBV26 merencanakan clean bootstrap/grants/race/expiry/redaction. Tidak ada DDL/runtime.

## Class-subject dan keanggotaan efektif D-26/D-27

class_subjects identitas kelas-mapel stabil, assignment historis tidak menjadi kunci rekap; activities dan report items memakai parent stabil sambil menyimpan asal/actor. Effective dates tanpa overlap di bawah mutex/parent lock, teacher current menentukan hak draft. Enrollment [starts_on,ends_on), draft roster versioned dan final attendance_records membekukan peserta; denominator tidak berubah ketika enrollment ended. Unique siswa/term tetap dan transfer tengah term ditunda. [Kontrak](ACADEMIC_FLOW.md), [model](DATA_MODEL.md), DBV27/28; semua schema/query/race NOT RUN.

D-28 memperbarui [model logis](DATA_MODEL.md) report_cards: availability current/needs_revision/superseded, version/source_version dan metadata revocation (usulan fisik); isi snapshot/item/file tetap immutable. Tidak menambah tabel koreksi. Penulis sumber yang memengaruhi rekap menahan rapor per siswa bersama audit/inbox, dengan mutex akademik/lock deterministik; guardian query published/current aktif, bukan status saja. Unique revision tidak cukup untuk satu unfinished/satu published-current; conditional constraint + action locking perlu dibuktikan DBV29/30. Revisi mempertahankan original policy/calculation, file attach/publish recheck. Migration/SQL/EXPLAIN/runtime **NOT RUN**.
