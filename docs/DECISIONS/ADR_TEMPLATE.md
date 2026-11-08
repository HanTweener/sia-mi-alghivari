# Catatan keputusan proyek - SIA MI Alghivari

ID: ADR-SIA-001. Diperbarui: 29 Oktober 2026. Pemilik keputusan: pemilik proyek. Penyusun: agen utama berdasarkan kebutuhan dan keputusan pemilik dalam percakapan. **Status: blueprint dengan keputusan terkonfirmasi dan usulan teknis yang dibedakan di bawah. Implementasi NOT STARTED; pengujian NOT RUN.**

Nama file ADR_TEMPLATE.md dipertahankan sesuai lokasi yang diminta pemilik, tetapi isinya kini catatan keputusan nyata proyek. Tidak ada isian yang harus disalin atau dilengkapi sebagai template umum. Perubahan ini menggantikan penafsiran sebelumnya sebagai template reusable.

## 1. Konteks dan masalah

SIA MI Alghivari dibangun ulang dari nol berdasarkan laporan kerja praktik, untuk portofolio. Belum ada aplikasi/database lama yang dimigrasikan. Produk mengelola data akademik, kegiatan guru, perkembangan anak, rapor dan keputusan naik/lulus pada satu sekolah.

Pemilik menginginkan upgrade schema, kode dan UI/UX yang bermanfaat tanpa menambah kerumitan yang tidak diperlukan. Tantangan utamanya ialah hak peran rangkap dengan scope berbeda, konsistensi nilai/absensi, pemisahan perkembangan dari rapor resmi, histori yang terjaga dan operasi demo yang dapat diperbarui secara otomatis.

Sumber kebutuhan: [PRD](../PRD.md), [brief](../PROJECT_BRIEF.md), [matriks izin](../ROLE_PERMISSION_MATRIX.md), [model data](../DATA_MODEL.md) dan [spesifikasi fitur inti](../FEATURES/FEATURE_SPEC_TEMPLATE.md). Laporan KP merupakan landasan historis; arahan pemilik terbaru mengungguli usulan lama.

## 2. Keputusan dan statusnya

| Kode | Keputusan untuk proyek ini | Status dan rujukan |
| --- | --- | --- |
| D-01 | Satu sekolah, dibangun dari nol, data portofolio sintetis dan fitur inti dahulu | Dikonfirmasi pemilik; bukan sistem multi-sekolah. |
| D-02 | Satu akun dapat mempunyai beberapa role; hak dievaluasi per action, resource, field, penugasan dan keadaan | Dikonfirmasi; [akses sumber daya](../RESOURCE_ACCESS_POLICY.md). |
| D-03 | Hanya Master mengatur role/jabatan; principal read dengan pengecualian approve naik/lulus; wali kelas boleh memfinalisasi/memublikasikan rapor binaan | Aturan inti dikonfirmasi; tidak memberikan hak mengedit nilai mapel lain. |
| D-04 | Penugasan wali kelas melalui homeroom_assignments, bukan teacher_id di classrooms | Dikonfirmasi; penugasan aktif memberi scope, histori tetap disimpan. |
| D-05 | Absensi per mapel, KKM/bobot terpusat, komponen fleksibel, total bobot termasuk hadir 100%; blank dilewati dan 0 dihitung | Dikonfirmasi; komponen positif seluruhnya kosong menghalangi finalisasi. |
| D-06 | Perkembangan Belajar dari aktivitas yang dibagikan/disahkan; Rapor Online dari snapshot final yang published | Dikonfirmasi; monitoring tidak menunggu rapor, rapor lama tidak berubah diam-diam. |
| D-07 | Setiap CUD domain memiliki audit sistem; domain + audit + inbox disimpan atomik; pengguna termasuk Master tidak dapat CUD audit | Dikonfirmasi; read-state diaudit tanpa notifikasi rekursif. |
| D-08 | Inbox persisten dengan Laravel Reverb/Echo untuk sinyal realtime setelah commit, melalui database queue/worker | Disepakati sebagai blueprint; private channel, catch-up dan dedup. Tidak menjanjikan exactly-once. |
| D-09 | Frontend menonjolkan React/TypeScript | Tujuan dikonfirmasi; baseline React 19/TypeScript mengikuti starter kit disepakati pada D-15; rilis kit dan paket persis belum dikunci. |
| D-10 | Satu aplikasi Laravel + Inertia + React/TypeScript | Dikonfirmasi pemilik 29 Oktober 2026 untuk bentuk aplikasi. Struktur kelompok fitur/action/query/policy/kalkulator masih rancangan ARC-001 pada [review arsitektur](../ARCHITECTURE_REVIEW.md); pemilihan MySQL dicatat terpisah pada D-11; baseline versi inti pada D-15; paket tambahan dan versi persis belum dikunci. |
| D-11 | Database MySQL | Baseline MySQL 8.4 LTS/InnoDB dikonfirmasi pemilik 29 Oktober 2026; patch/image, konfigurasi dan rancangan fisik belum dikunci pada [DB-001](../DATABASE_DECISION_RECORD.md). |
| D-12 | dev -> PR/main -> GitHub Actions CI/build -> GHCR -> runner deployment lokal -> Docker Compose; Cloudflare Tunnel untuk akses publik demo | Disepakati sebagai blueprint; belum ada konfigurasi, domain aktif atau deployment. |
| D-13 | Master menyiapkan akun wali/role guardian dan aktivasi terlebih dahulu; Data Entry kemudian melengkapi biodata serta hubungan siswa | Q6.1 dikonfirmasi pemilik 29 Oktober 2026. Tanpa profil wali pending baru; allowlist biodata/recovery tetap terbuka; metode D23/D24 kemudian digantikan D25; parameter/integrasi password belum final. |
| D-14 | Master dan Admin Akademik memberikan dan mencabut delegasi input nilai/kehadiran Data Entry | Q6.3 dikonfirmasi pemilik 29 Oktober 2026; scope/capability/waktu dan C/U draft tetap, actor audit petugas asli. Tidak menetapkan approval usulan penugasan atau seluruh kontrak permintaan/perubahan delegasi. |
| D-15 | Baseline versi inti: Laravel 13.x, PHP 8.5.x, Inertia 3.x, React 19.x, TypeScript mengikuti rilis starter kit yang dipilih, Tailwind CSS 4.x, shadcn/ui seperlunya, serta MySQL 8.4 LTS/InnoDB | Dikonfirmasi pemilik 29 Oktober 2026. Versi paket persis, rilis starter kit dan image/digest ditetapkan serta diuji saat bootstrap; tidak menyetujui seluruh paket tambahan, mengubah Q6/Q7 atau mengotorisasi implementasi. Rujuk [TECH_STACK](../TECH_STACK.md). |
| D-16 | Docker Desktop + WSL2 pada Windows; HTTP app memakai FrankenPHP classic berbasis Debian/PHP 8.5 | Dikonfirmasi pemilik 29 Oktober 2026. App/queue/reverb tetap satu image aplikasi dengan proses terpisah. Tanpa Octane/worker mode FrankenPHP; worker antrean notifikasi tetap berjalan. Versi FrankenPHP/rilis Debian/ekstensi/digest dan kompatibilitas perangkat belum diverifikasi; bukan izin instalasi/deployment. Rujuk [arsitektur deployment](../DEPLOYMENT_ARCHITECTURE.md). |
| D-17 | Renderer PDF rapor: Dompdf melalui barryvdh/laravel-dompdf 3.1.x | Dikonfirmasi pemilik 29 Oktober 2026. Template Blade khusus cetak dari snapshot finalized, file privat siap sebelum published. Patch/lockfile, ekstensi/font dan hasil render pada image proyek belum diverifikasi. Pilihan renderer tidak menyelesaikan isi/layout Q7 atau mengotorisasi implementasi. Rujuk [TECH_STACK](../TECH_STACK.md). |
| D-18 | Q7.1 susunan rapor ringkas, rincian komponen di aplikasi, catatan wali kelas opsional | Dikonfirmasi pemilik 29 Oktober 2026; isi/batas kanonis pada [alur akademik](../ACADEMIC_FLOW.md). Kehadiran per mapel dalam pertemuan, status KKM tanpa predikat A-D; catatan draft binaan dibekukan saat final. Tidak menyelesaikan rentang/presisi/pembulatan/perbandingan KKM, detail visual, koreksi/revoke atau mengotorisasi implementasi. |
| D-19 | Q7.2 skor input 0-100 inklusif, maksimal dua angka desimal | Dikonfirmasi pemilik 29 Oktober 2026. 0 sah, blank tetap belum ada nilai; invalid ditolak sebelum cast/insert tanpa pembulatan/pemotongan otomatis. Tidak menetapkan presisi hitung/pembulatan akhir/KKM atau presisi input bobot/KKM; tanpa implementasi. Rujuk [alur akademik](../ACADEMIC_FLOW.md). |
| D-20 | Q7.3 pembulatan nilai akhir dua desimal half-up, tanpa pembulatan antara | Dikonfirmasi pemilik 29 Oktober 2026. Rata-rata/kontribusi dipertahankan presisinya hingga total akhir; 79,416666...→79,42 dan79,425→79,43. Bukan keputusan comparator KKM, presisi konfigurasi atau paket kalkulator. Implementasi/tes tetap NOT RUN. Rujuk [alur akademik](../ACADEMIC_FLOW.md). |
| D-21 | Q7.4 status KKM dari nilai akhir yang sudah dibulatkan | Dikonfirmasi pemilik 29 Oktober 2026: final_score hasil Q7.3 >= KKM global versi yang sama berarti Mencapai KKM, < berarti Belum mencapai KKM. Raw79,995→80,00 vs KKM80 mencapai; unavailable tidak menjadi0 atau label gagal. Snapshot/UI/PDF sama. Tidak menetapkan presisi konfigurasi, nilai KKM/bobot default atau approval naik/lulus. Rujuk [alur akademik](../ACADEMIC_FLOW.md). |
| D-22 | Q7.5 konfigurasi KKM/bobot0-100 inklusif, maksimal dua desimal; total aktivasi tepat100% | Dikonfirmasi pemilik 29 Oktober 2026. Master/Akademik tetap memilih angka; tanpa default baru. Invalid ditolak sebelum cast/insert, tanpa pembulatan otomatis/toleransi total atau override mapel. Draft belum100 tidak aktif; mutasi atomik. Bukan izin implementasi/DDL. Rujuk [alur akademik](../ACADEMIC_FLOW.md). |
| D-23 | Q6.2 metode tautan password pertama guardian baru, diserahkan pribadi oleh Master setelah memastikan identitas | HISTORIS / SUPERSEDED D-25, 29 Oktober 2026. Tautan awal/sekali-buka tidak lagi berlaku; lihat lifecycle baru. |
| D-24 | Q6.2 TTL 10 menit dan tautan sekali dibuka | HISTORIS / SUPERSEDED D-25, 29 Oktober 2026. Tautan awal/sekali-buka tidak lagi berlaku; lihat lifecycle baru. |
| D-25 | Password sementara emailed dan OTP email Fouladgar | Dikonfirmasi pemilik 29 Oktober 2026: Master akun/email langsung verified administratif; sistem password sementara emailed bersama username/URL biasa, login terbatas/forced change. Reset OTP email -> password/konfirmasi -> commit -> modal pilihan sesi wajib -> login biasa. Profil OTP -> password -> modal, perangkat kini tetap login. Mengganti D-23/D-24/current_password wajib/usulan selalu logout. Tabel OTP hash/atomik, versi credential/sesi/pending dan email provider masih blueprint; angka parameter/bootstrap/email hilang OPEN. [Lifecycle](../RECOVERY_AND_STEPUP_PLAN.md). |
| D-26 | AR-04 identitas mapel kelas dan pergantian guru | Dikonfirmasi 29 Oktober 2026: class_subjects stabil, teaching_assignments histori bertanggal MA; guru baru membaca histori/melanjutkan draft, asal/actor tetap, guru lama kehilangan grant assignment saja. Delegasi lama berakhir, jadwal dicek, published/final bukan edit biasa. Satu rekap/item rapor mapel tanpa copy atau average-per-guru. [Alur](../ACADEMIC_FLOW.md). |
| D-27 | AR-05 enrollment efektif dan frozen roster | Dikonfirmasi 29 Oktober 2026: tanggal mulai/akhir, peserta final dibekukan melalui attendance_records lengkap. Penyebut per siswa dari record sesi finalized; sebelum masuk/setelah keluar tidak dihitung/alpa. Draft review ulang, backdate yang mengubah roster final ditolak; transfer antarkelas term sama/rapor transfer ditunda, unique siswa/term tetap. Representasi tanggal/locking masih blueprint, runtime NOT RUN. [Model](../DATA_MODEL.md). |
| D-28 | Koreksi sah skor/status tanpa reopen; MA termasuk closed, G current hanya term terbuka. MA/W binaan revisi dan revoke rapor; hold per siswa, snapshot immutable, policy/calculation asli, satu published/current, progression belum applied needs_review dan applied tanpa undo | Dikonfirmasi pemilik 29 Oktober 2026; [alur](../ACADEMIC_FLOW.md), [kontrak](../API_CONTRACT.md), [model](../DATA_MODEL.md). Implementasi dan seluruh tes NOT RUN. |
| D-29 | Satu school_timezone dinamis Master untuk kalender dan tampilan: label WIB/WITA/WIT, internal Asia/Jakarta/Asia/Makassar/Asia/Jayapura; UTC backend/DB, DATE/TIME literal, refresh tanpa restart; audit/version dan immutable histori | Dikonfirmasi pemilik 30 Oktober 2026; [PRD](../PRD.md). Nilai awal belum dipilih; rekonsiliasi cap delegasi DATE vs UTC saat perubahan masih OPEN. Runtime NOT RUN. |
| D-30 | Backup dibuat lokal terlebih dahulu, lalu pemilik mengunggah manual ke Google Drive; satu host, SSD kosong100GB | Dikonfirmasi pemilik 30 Oktober 2026. Tanpa integrasi/sync Drive otomatis; lokal belum offhost, upload/checksum/restore dibedakan. Jadwal/retensi kemudian diterima D-31; alat/key/kuota dan kapasitas belum final/teruji; target formal RPO/RTO kemudian dikeluarkan D-32. [Recovery](../DATA_RECOVERY_PLAN.md). |
| D-31 | Jadwal backup manual: setelah sesi demo dan unggah hari yang sama, harian ketika ada perubahan dalam demo beberapa hari, sebelum migrasi/deploy berisiko. Retensi3lokal,7harian+4mingguanDrive; set mingguan dari harian tanpa duplikat | Dikonfirmasi pemilik 30 Oktober 2026. Pengganti/remote terverifikasi dan set recoverable wajib sebelum rotasi; bukan izin hapus/operasi sekarang. Target formal RPO/RTO kemudian dikeluarkan D-32; tool/key/path/kuota masih terbuka. [Recovery](../DATA_RECOVERY_PLAN.md). |
| D-32 | Target formal RPO/RTO dilewatkan dari lingkup demo portofolio; usulan angka sebelumnya tidak dipakai dan tidak menjadi blocker/gate numerik rilis | Dikonfirmasi pemilik 30 Oktober 2026. Backup D-30/D-31 serta restore fungsional/integritas/grant tetap required dan NOT RUN. Tidak berlaku pada target formal saja, bukan PASS pemulihan. [Recovery](../DATA_RECOVERY_PLAN.md). |
| D-33 | Host demo PC pemilik Windows/WSL2/Compose, mainCI->GHCRdigest->runner; domain calon `<nama>.dpdns.org`, Cloudflare authoritative DNS/NS, satu Tunnel dua host sia/ws, HTTPS/WSS edge dan network Compose privat; mode sleep off selama sesi | Dikonfirmasi pemilik 30 Oktober 2026. Domain belum dimiliki; NS/zone/cert/config/resource/smoke belum verified. Resource6GB4CPU tetap usulan, tanpa24/7; bukan izin registrasi/provisioning/deploy. [Arsitektur](../DEPLOYMENT_ARCHITECTURE.md). |
| D-34 | Dokumentasi relevan dilengkapi hipotesis/expected dan kebutuhan bukti; actual dicatat bertahap per fitur lokal lalu demo publik terotorisasi, prototipe terpisah | Arahan pemilik 30 Oktober 2026. Bukti/test/result/reviewer/approval hanya dari eksekusi nyata; semua inti NOT RUN, GO PENDING. Template berbasis kejadian/di luar scope tidak diisi fiktif. [Kontrak bukti](../QA_EVIDENCE_REPORT.md). |

Tabel ini merangkum keputusan lintas aspek. Rincian dan status ARC-001/DB-001 tetap dirawat pada dokumen kanonisnya, bukan diputuskan ulang lewat ringkasan ini. Dikonfirmasi pada satu baris tidak menjadikan semua baris atau seluruh paket/versi Accepted.

## 3. Pilihan arsitektur yang dipertimbangkan

| Pilihan | Kelebihan terhadap tujuan | Biaya/batas | Perlakuan |
| --- | --- | --- | --- |
| Laravel + Blade/Livewire | Baseline sederhana bagi pengembangan berpusat PHP | Kurang menonjolkan tujuan React/TypeScript pemilik | Alternatif historis yang tetap layak; bukan usulan utama terbaru. |
| Laravel + Inertia + React dalam satu aplikasi | React nyata, satu sesi/routing server dan transaksi domain terpusat | Kontrak props/form dan penguasaan PHP/TS tetap diperlukan | Bentuk aplikasi dipilih pemilik 29 Oktober 2026; belum persetujuan seluruh stack. |
| Backend API + frontend terpisah | Cocok bila klien/deployment independen diperlukan | Dua aplikasi, kontrak API/auth dan operasi tambahan | Ditunda karena belum ada kebutuhan yang membenarkannya. |

Tidak menambah microservices, Redis, workflow/permission builder, SSR atau backend API publik untuk kebutuhan saat ini. Pilihan MySQL dibandingkan SQLite/PostgreSQL pada DB-001; tidak mengklaim engine paling cepat tanpa benchmark.

## 4. Bentuk implementasi yang dituju

Controller/request menangani HTTP dan validasi bentuk; policy memeriksa izin; action menjadi pemilik operasi tulis dan transaksi; query menyajikan proyeksi berizin; kalkulator PHP menghitung nilai secara deterministik. Eloquent boleh dipakai langsung sesuai kebutuhan. Tidak membuat repository/interface untuk setiap tabel atau lapisan DDD wajib.

Nilai numerik dikelompokkan per komponen, dirata-ratakan, lalu dikalikan bobot. Kehadiran memakai proporsi hadir dari pertemuan finalized yang berlaku untuk siswa. Komponen score dan attendance mempunyai sumber yang berbeda. Versi policy/calculation dibekukan bersama snapshot rapor; JavaScript hanya presentasi/preview, bukan sumber hasil resmi.

Transaksi perubahan memuat domain, audit per objek dan inbox terkelompok. Efek socket/render file berjalan sesudah commit atau di luar lock yang relevan. DB, filesystem dan WebSocket tidak dianggap satu transaksi ACID. Rapor hanya dipublikasikan setelah snapshot lengkap/finalized dan file siap sesuai rancangan. Detail: [ARCHITECTURE.md](../ARCHITECTURE.md).

## 5. Konsekuensi dan pengamanan

| Konsekuensi | Perlakuan proyek |
| --- | --- |
| Role rangkap lebih fleksibel tetapi berisiko memperluas akses tanpa sengaja | Policy server di setiap entry point, scope sebelum count/pagination, tanpa bypass Master global. |
| Audit wajib menambah tulisan dan disiplin transaksi | Service internal ikut transaksi, bulk tetap audit per objek, SQL runtime tanpa UPDATE/DELETE audit atau DDL. Bukan jaminan tahan terhadap DBA. |
| Snapshot memerlukan revisi/storage dan alur file tersendiri | Rapor published tidak dirender ulang dari nilai/konfigurasi terbaru; gagal file dapat dicoba ulang tanpa sukses palsu. |
| Database queue dan Reverb menambah proses latar belakang | Satu image app/queue/reverb, Compose mengelola proses; tanpa Redis pada rancangan satu instance. |
| Satu host lebih sederhana tetapi menjadi titik kegagalan tunggal | Volume persisten, backup/restore teruji, indikator gagal yang jujur; PC/internet/Docker mati dapat membuat demo tidak tersedia. |
| Mutex akademik mengurangi race tetapi membatasi paralelisme tulisan | Masih usulan fisik DB; transaksi singkat, tanpa render/jaringan di dalam lock, ukur contention sebelum mempersempit lock. |
| CD mempercepat pembaruan tetapi runner menguasai Docker host | PR/build memakai runner GitHub, deployment hanya main tepercaya, image berdigest, isolasi dev/demo dan gate target. |

Kembali ke image lama bukan rollback schema/data otomatis. Upgrade/migration harus kompatibel dan direhearsal sesuai [rencana migrasi](../DATABASE_MIGRATION_PLAN.md) dan [deployment](../DEPLOYMENT_PLAN.md). Persetujuan blueprint CD belum mengotorisasi aktivasi layanan sekarang.

## 6. Validasi dan hal yang belum diputuskan

| Area | Bukti penerimaan yang direncanakan | Status aktual |
| --- | --- | --- |
| Izin dan role rangkap | Guru beda kelas/mapel, wali beda anak, delegasi dicabut, principal/role Master serta scope file/audit/channel | NOT RUN |
| Kalkulasi dan snapshot | Blank/0, bobot 0/100, komponen positif kosong, hadir/nol pertemuan, pergantian policy dan snapshot lama | NOT RUN |
| Transaksi dan race | Audit/inbox gagal, batch invalid, unique aktif, versi usang, duplicate approve/apply dan penulisan saat finalisasi | NOT RUN |
| File/realtime/deployment | Render gagal/retry, private channel, dispatch setelah commit gagal, reconnect, upgrade/restore dan image target | NOT RUN |

Rujukan pengujian: AV pada [review arsitektur](../ARCHITECTURE_REVIEW.md), DBV pada [verifikasi database](../DATABASE_VERIFICATION_REPORT.md), CQ pada [rencana kualitas](../CODE_QUALITY_PLAN.md). Source/test aplikasi inti belum tersedia. Pembaruan 29 Oktober 2026: [prototipe terisolasi](../support/facelift-v2/README.md) memiliki source/tes/evidence frontend, tetapi hanya referensi desain; bukan bukti implementasi keputusan DB, auth, audit atau server sudah lulus.

Telaah konsistensi dokumentasi pada 28 Oktober 2026 dilakukan subagen peninjau yang terpisah dari penyusun. Perbandingan dengan PRD, matriks izin, TECH_STACK, ARC-001 dan DB-001 tidak menemukan konflik substantif. Cakupannya hanya catatan keputusan ini; bukan audit keamanan, persetujuan pemilik atau verifikasi implementasi.

Keputusan terbuka: Q6 allowlist/delegasi/aktivasi usulan penugasan/pelaksana rollover; D-25 parameter TTL/limit/retensi/provider, hashed repository/middleware, bootstrap/email hilang. D-23/D-24 historis superseded; tidak ada kelanjutan link sekali-buka yang perlu diimplementasikan. Q7.1-Q7.5 diterima D18..22, visual/render dan AR04/05 masih belum verified.

Keputusan operasi yang belum final: paket tambahan/versi persis dan konfigurasi MySQL, versi FrankenPHP/rilis Debian/ekstensi serta kompatibilitas host, visibilitas repository/package, implementasi isolasi runner, nama lengkap/registrasi/aktivasi DNS-TLS D-33, kapasitas, nilai awal timezone/rekonsiliasi cap delegasi, retensi domain/artefak di luar backup D-31 dan pemulihan akun. Target formal RPO/RTO tidak lagi menjadi keputusan terbuka untuk demo portofolio (D-32). Angka performa/kapasitas belum diukur.

## 7. Kajian ulang dan riwayat

Kaji ulang bila ada konsumen API/mobile independen, multi-sekolah, kebutuhan offline, HA/PITR atau efek wajib replay, maupun bottleneck terukur yang tidak cukup diperbaiki secara lokal. Tidak otomatis menambah service/cache hanya karena teknologi tersebut tersedia.

Riwayat 28 Oktober 2026: atas koreksi pemilik, file ini diubah dari format kosong/reusable menjadi catatan keputusan konkret proyek. Keputusan terkonfirmasi tetap mengikuti percakapan/PRD; proposal teknis tidak dinaikkan statusnya. Tahap aktif tetap pemantapan blueprint tanpa implementasi.
