# Keputusan database - SIA MI Alghivari

ID: DB-001. Tanggal: 28 Oktober 2026. Pemilik keputusan: pemilik proyek. Status: **MySQL 8.4 LTS/InnoDB disepakati pemilik 29 Oktober 2026**; patch, driver/image, konfigurasi dan rancangan fisik belum dikunci. Implementasi **NOT STARTED**, seluruh tes **NOT RUN**. Dokumen ini menjadi catatan keputusan database kanonis; tidak perlu menggandakan keputusan yang sama ke ADR lain saat blueprint.

## 1. Fakta dan kebutuhan

- Bangun dari nol, satu sekolah, demo sintetis. Tidak ada dump database lama yang harus diimpor.
- Relasi utama: akun/peran, siswa/wali, periode/rombel/penugasan, kegiatan mapel, nilai, rapor, naik/lulus, audit dan inbox. Makna tabel mengikuti [DATA_MODEL.md](DATA_MODEL.md).
- Perubahan domain, audit dan inbox harus atomik. Penugasan, finalisasi rapor dan apply kenaikan membutuhkan pengendalian perubahan serentak dan duplikasi.
- Query utama berupa daftar berfilter/berpaginasi, join menurut anak/kelas/mapel, input satu kelas dan agregasi semester. [DATA_ACCESS_PATTERNS.md](DATA_ACCESS_PATTERNS.md).
- Target demo memakai Compose, database queue dan Reverb. Tidak ada akses SQL dari browser atau kebutuhan sinkronisasi offline.
- Jumlah siswa/pengguna, beban serentak, volume histori, pertumbuhan, p95 dan budget koneksi belum diketahui/diukur. Host memakai komputer pemilik; D-30 memilih backup lokal lalu unggah manual Google Drive, dan D-31 menetapkan jadwal/retensinya. Folder, alat/key, kuota dan biaya operasional belum final/terverifikasi; target formal RPO/RTO berada di luar lingkup portofolio (D-32).
- Profil, nilai, kehadiran dan metadata audit bersifat pribadi. Password hash adalah data keamanan; tidak boleh disalin ke log/fixture.

## 2. Pilihan yang dibandingkan

| Pilihan | Kecocokan | Operasi dan batas | Perlakuan |
| --- | --- | --- | --- |
| SQLite, satu store | Baseline sederhana, relasi/transaksi lokal | Tanpa service DB terpisah, tetapi perilaku writer/locking/tipe tidak membuktikan perilaku MySQL | Layak prototipe kecil; tidak menggantikan tes engine target. |
| MySQL 8.4 LTS / InnoDB, satu store | FK/unique, transaksi input kelompok dan pelaporan relasional | Memerlukan service, backup, pengaturan hak dan rancangan constraint khusus engine | Baseline MySQL 8.4 LTS/InnoDB disepakati pemilik 29 Oktober 2026; patch dan konfigurasi rinci belum dikunci. |
| PostgreSQL, satu store | Sama-sama cocok untuk transaksi/relasi; partial index membantu beberapa aturan | Driver dan SQL/migration berbeda; belum ada kebutuhan yang mewajibkan perpindahan | Alternatif yang dibandingkan; MySQL telah dipilih. |
| MySQL + Redis/search/store tambahan | Berguna bila ada kebutuhan terukur | Menambah operasi, salinan data dan konsistensi | Tidak ditambahkan pada inti. |

Tidak ada benchmark pembanding yang diklaim. MySQL 8.4 bukan klaim LTS terbaru; patch dan dukungan diperiksa saat implementasi.

## 3. Keputusan rancangan

**MySQL 8.4 LTS dengan tabel InnoDB disepakati pemilik 29 Oktober 2026** sebagai baseline database proyek. Rekomendasi implementasi: Community Server, utf8mb4 dan akses Laravel melalui PDO MySQL/mysqlnd; edition, encoding/kolasi dan driver belum dikunci melalui persetujuan baseline ini. Patch server/PHP/driver serta digest image belum dipilih. Dev, CI dan demo memakai engine serta konfigurasi semantik yang sama, dengan resource dan kredensial terpisah. MariaDB tidak dianggap pengganti identik tanpa kajian kompatibilitas.

Satu database per lingkungan menyimpan domain, audit, inbox dan antrean framework. PDF/foto berada pada storage privat, referensinya di DB. Tidak menambah replica baca, sharding, partitioning, Redis, CDC atau tabel pemantauan duplikat. Reverb adalah pengiriman sinyal, bukan store nilai.

Tradeoff: host/database menjadi titik kegagalan tunggal, worker berbagi kapasitas DB, migration khusus engine mengurangi portabilitas, dan DDL tidak diasumsikan bisa di-rollback. Tidak menjanjikan high availability atau point-in-time recovery pada awal demo. Biaya mencakup host, disk/indeks/log/backup dan pemeliharaan; belum ada estimasi yang disetujui.

## 4. Batas keamanan dan pemulihan

Role aplikasi tidak menjadi akun SQL terpisah per pengguna. Policy server mengendalikan anak/kelas/mapel/field. Runtime diberi hak per tabel, audit hanya SELECT/INSERT, tanpa DDL. Master aplikasi bukan DBA. [DATABASE_PROVIDER_IMPLEMENTATION.md](DATABASE_PROVIDER_IMPLEMENTATION.md).

Volume persisten bukan backup. Rancang backup logis terkoordinasi dengan file privat, salinan di lokasi terpisah dan uji restore terisolasi. Jadwal/retensi backup D-31 diterima; kelayakan ruang/kuota/key dan restore fungsional masih perlu diverifikasi sebelum CD aktif. Target formal RPO/RTO dikeluarkan D-32; belum ada bukti restore. [DATABASE_MIGRATION_PLAN.md](DATABASE_MIGRATION_PLAN.md).

## 5. Hal terbuka dan kajian ulang

Patch/image/digest, kolasi identitas, parameter/hash/binding/middleware/provider/retensi password D-25 belum final. D26/27 aturan pergantian guru/keanggotaan telah dipilih; schema/race tetap belum diverifikasi. MySQL otp_tokens hash/sessions dan metadata users dirancang pada DATA_MODEL; tidak ada DDL/runtime atau pemilihan angka dari default paket. Q6/Q7 mengikuti PRD.

Kaji ulang bila hosting tidak mendukung runtime, beban nyata melampaui satu host, muncul multi-sekolah atau diperlukan PITR/HA. Migrasi ke engine/provider lain memerlukan ekspor/transformasi teruji, pencocokan hitungan/relasi/snapshot dan rehearsal pemulihan; bukan sekadar mengganti DSN.

Penerimaan/bukti: [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md). Referensi: [manual MySQL 8.4](https://dev.mysql.com/doc/refman/8.4/en/), [profil MySQL lokal](../.ai/references/database-profiles/mysql/ENGINEERING_GUIDE.md).

D-28 memperbarui [model logis](DATA_MODEL.md) report_cards: availability current/needs_revision/superseded, version/source_version dan metadata revocation (usulan fisik); isi snapshot/item/file tetap immutable. Tidak menambah tabel koreksi. Penulis sumber yang memengaruhi rekap menahan rapor per siswa bersama audit/inbox, dengan mutex akademik/lock deterministik; guardian query published/current aktif, bukan status saja. Unique revision tidak cukup untuk satu unfinished/satu published-current; conditional constraint + action locking perlu dibuktikan DBV29/30. Revisi mempertahankan original policy/calculation, file attach/publish recheck. Migration/SQL/EXPLAIN/runtime **NOT RUN**.
