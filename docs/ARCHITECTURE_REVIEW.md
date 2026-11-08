# Review arsitektur aplikasi - SIA MI Alghivari

Diperbarui: 29 Oktober 2026. Objek: [ARCHITECTURE.md](ARCHITECTURE.md). Penyusun/telaah: agen utama berdasarkan dokumen proyek; **bukan review independen atau audit implementasi**. Pemilik keputusan: pemilik proyek. Status: **PROPOSED / telaah blueprint**. Source aplikasi, dependency graph aktual dan hasil uji belum tersedia; seluruh verifikasi runtime **NOT RUN**.

## 1. Fakta dan batas telaah

| Sumber yang ditelaah | Fakta yang dipakai |
| --- | --- |
| [PRD](PRD.md) dan [brief](PROJECT_BRIEF.md) | Bangun dari nol, satu sekolah; Q1-Q5, Q6.1/Q6.3, lifecycle password D-25 dan Q7.1-Q7.5 dikonfirmasi. Sisa allowlist/delegasi/penugasan/apply Q6 serta detail visual/render belum selesai/diverifikasi; jangan implementasi dahulu. |
| [TECH_STACK](TECH_STACK.md) | React/TypeScript, Reverb/Echo dan Compose/CD dikonfirmasi sebagai blueprint; kombinasi backend/database/paket belum approved menyeluruh. |
| [Model](DATA_MODEL.md) dan [akses](RESOURCE_ACCESS_POLICY.md) | Role rangkap, scope anak/kelas/mapel, homeroom terpisah, audit atomik dan snapshot rapor. |
| [Arsitektur DB](DATABASE_ARCHITECTURE.md), [provider](DATABASE_PROVIDER_IMPLEMENTATION.md) dan [pola akses](DATA_ACCESS_PATTERNS.md) | Engine MySQL dipilih 29 Oktober 2026; baseline 8.4 LTS/InnoDB juga disepakati; patch/konfigurasi belum dikunci; generated unique, versi induk dan mutex akademik adalah rancangan, bukan constraint yang terbukti berjalan. |
| [Notifikasi](NOTIFICATION_REQUIREMENTS.md), [alur pengguna](USER_FLOWS.md) dan [CI/CD](CICD_PIPELINE_SPEC.md) | Inbox persisten, sinyal setelah commit, portal perkembangan terpisah dari rapor, image berdigest dan runner deployment terbatas. |

Telaah menilai kesesuaian batas/tanggung jawab dan menandai keputusan yang masih kurang. Tidak ada endpoint, class, import, transaksi, latensi atau deployment aktual yang dinyatakan sudah diperiksa. Dokumen bukan bukti keamanan atau persetujuan rilis.

## 2. ARC-001: bentuk aplikasi dan arah dependensi

Detail keputusan ARC-001 tetap kanonis pada bagian ini. [ADR-SIA-001](DECISIONS/ADR_TEMPLATE.md) merangkum keputusan lintas aspek proyek dengan rujukan ke bagian ini dan DB-001, dengan status per lingkup. ARC-001 diusulkan 28 Oktober 2026; **bentuk satu aplikasi Laravel + Inertia + React/TypeScript diterima pemilik 29 Oktober 2026**. Detail pengelompokan fitur, action/query/policy dan kalkulator tetap rancangan untuk implementasi; persetujuan bentuk aplikasi ini tidak mengunci paket tambahan atau seluruh detail dependensi. Baseline versi inti kemudian disepakati pada D-15; versi paket persis belum dikunci. Engine MySQL dipilih melalui keputusan terpisah DB-001 pada 29 Oktober 2026.

Konteks: aplikasi akademik memerlukan React nyata untuk portofolio, banyak batas akses, kalkulasi yang dapat diuji dan proses transaksi yang konsisten. Belum ada kebutuhan klien mobile/API independen atau skala yang membenarkan distribusi service.

| Opsi | Manfaat | Biaya dan risiko | Penilaian untuk kebutuhan ini |
| --- | --- | --- | --- |
| Laravel + Blade/Livewire dengan struktur MVC | Baseline sederhana, lebih sedikit integrasi frontend | Kurang mendukung tujuan menonjolkan React/TypeScript | Tetap alternatif sah, bukan rekomendasi utama. |
| Laravel + Inertia + React dengan pengelompokan fitur | Satu aplikasi/sesi, React nyata, policy/transaksi terpusat | Perlu kontrak props/type, disiplin action/query dan render PDF tersendiri | Bentuk satu aplikasi dipilih pemilik 29 Oktober 2026; pengelompokan kode tetap rancangan. |
| Laravel API + React/Next terpisah | Konsumen dan deployment bisa independen | Dua aplikasi, kontrak API/auth/CORS dan operasi tambahan | Ditunda sampai kebutuhan konsumen/deployment terpisah nyata. |

Opsi kedua dipilih untuk bentuk aplikasi Laravel + Inertia + React/TypeScript. Rancangan struktur: monolit yang dibagi menurut fitur menggunakan direktori Laravel biasa, bukan paket layanan independen. Controller menangani HTTP, action mengoordinasikan mutasi, policy mengatur hak, query menyajikan proyeksi, dan kalkulator PHP murni memegang rumus. Eloquent tetap dapat dipakai langsung; tidak ada repository/interface wajib per model.

Konsekuensi: biaya awal lebih kecil daripada distribusi service, tetapi disiplin batas perlu dijaga. Satu image dan DB memudahkan transaksi sekaligus menjadi batas kapasitas/ketersediaan satu host. Ketergantungan Laravel pada action/persistence diterima; yang dipisahkan dari framework adalah kalkulasi yang memang dapat berdiri sendiri. Ini tidak diklaim sebagai Clean Architecture empat lapis penuh.

Kontrak lintas fitur: tulisan melalui pemilik operasi bisnis, satu transaksi luar untuk use case lintas modul, audit/inbox ikut transaksi, efek jaringan/file di luar lock. React tidak menjadi sumber hak, status final atau hasil kalkulasi. Halaman Inertia dan endpoint pendukung dalam aplikasi memakai sesi/policy sama; belum ada API publik stabil yang dijanjikan kepada klien luar.

Biaya pembalikan: pindah renderer UI perlu kontrak props/route/form baru; pindah engine perlu migration/locking/constraint baru; memisahkan service memerlukan batas transaksi/otorisasi dan pemulihan efek lintas proses. Bukan sekadar memindahkan folder.

## 3. Temuan, risiko dan keputusan terbuka

Temuan berikut adalah kekurangan spesifikasi/risiko rancangan, bukan bug yang ditemukan pada aplikasi berjalan. Pemilik produk menyelesaikan aturan; pengembang membuktikan penerapannya setelah implementasi diotorisasi.

| ID | Temuan dan dampak jika diabaikan | Tindak lanjut / batas sebelum implementasi fitur |
| --- | --- | --- |
| AR-01 | Bentuk Laravel + Inertia + React/TypeScript diterima; engine MySQL juga dipilih; baseline versi inti pada D-15 diterima; konfigurasi, paket tambahan dan versi persis belum final. Persetujuan bentuk aplikasi dapat disalahartikan sebagai persetujuan seluruh detail | Pertahankan status per lingkup; kunci konfigurasi MySQL, rilis/lockfile dan image/ekstensi sebelum bootstrap yang bergantung padanya. HTTP server FrankenPHP classic berbasis Debian dan host Windows Docker Desktop/WSL2 disepakati pada D-16; kompatibilitas belum diuji. |
| AR-02 | Q6.1/Q6.3 tetap; D25 mengganti akses awal/password: temporary/email verified Master/OTP/modal/reset login biasa. Field/delegasi/penugasan/apply masih OPEN; OTP hash/atomic/proof/sesi No dan email delivery belum diuji | Jangan mengandalkan raw vendor migration atau auth.session hash mismatch. Ikuti lifecycle dan model; parameter/provider/bootstrap/fallback belum final, tanpa public signup/escalation. |
| AR-03 | Q7.1-Q7.5 diterima: susunan ringkas, skor input 0-100/maksimal dua desimal, hasil dua desimal half-up tanpa pembulatan antara, pembanding hasil dibulatkan >= KKM dan presisi konfigurasi. Konsistensi preview/PDF belum diuji | Implementasikan satu kalkulator server dengan policy/calculation version dan oracle dari aturan yang telah dipilih; buktikan blank/0, kelengkapan, presisi serta snapshot/PDF. Detail visual/render belum verified, bukan alasan membuka kembali keputusan numerik. |
| AR-04 | DIKONFIRMASI D-26: class_subjects stabil, assignment histori bertanggal, satu rekap/item mapel, source/creator tetap. Guru baru histori/draft, guru lama tanpa grant assignment itu; published/final dilindungi dan delegasi lama berakhir | Selaraskan FK/unique/jadwal/otorisasi current-parent dan cek bentrok saat handover. Status keputusan selesai; implementasi/AV/DBV belum diverifikasi, tanpa copy nilai atau grant lintas mapel. |
| AR-05 | DIKONFIRMASI D-27: enrollment efektif, frozen roster final, penyebut record per siswa; draft direview ulang, backdate mengubah final ditolak. Transfer antarkelas tengah term/rapor transfer ditunda | Uji boundary/race/enrollment ended tanpa refilter histori. Tetap unique siswa/term, tidak menambah tabel peserta/transfer. D-28 koreksi status dipilih; struktur/roster final ditunda, nilai awal timezone/rekonsiliasi cap delegasi OPEN; runtime NOT RUN. |
| AR-06 | Mutex akademik menserialkan tulisan dan hanya aman jika semua penulis mengikuti protokol; bulk yang lama dapat menghambat guru lain | Batasi batch/durasi, larang file/jaringan di dalam lock, uji dua koneksi. Jangan mempersempit lock sebelum ada bukti konsistensi dan pengukuran contention. |
| AR-07 | DB, file dan WebSocket tidak satu transaksi; crash dapat meninggalkan file orphan atau sinyal hilang | Snapshot dahulu, file diselesaikan lalu ditautkan, publish memeriksa kesiapan; inbox persisten + reconnect. Uji kegagalan antar-tahap, tanpa klaim exactly-once. |
| AR-08 | Runner lokal mempunyai akses Docker luas; publikasi source/PR tak tepercaya dapat memperbesar risiko host | Jalur deployment terbatas dan terpisah dari PR/build; putuskan visibilitas repo/package dan isolasi akun/host sebelum aktivasi CD. |
| AR-09 | Kapasitas/SLO, retensi data domain/key backup, nilai awal timezone/rekonsiliasi cap delegasi serta parameter/provider/bootstrap/fallback pemulihan akun belum final. Kanal email dipilih D-25; alur backup D-30 dan jadwal/retensi D-31 dipilih; target formal RPO/RTO di luar scope D-32 | Selesaikan detail yang diperlukan sebelum fitur/operasi terkait diaktifkan; ukur kapasitas dengan fixture sintetis dan uji restore fungsional. Jangan menganggap timezone host, provider email atau kesiapan backup sudah terbukti. |

AR-04/D-26 dan AR-05/D-27 dikonfirmasi pemilik 29 Oktober 2026. Aturan produk diselesaikan sesuai [alur akademik](ACADEMIC_FLOW.md); schema/constraint/otorisasi/locking yang diselaraskan tetap blueprint dan harus dibuktikan. D-28 memilih koreksi skor/status melalui command serta revisi/revoke rapor; transfer, koreksi struktur/tanggal/roster final dan undo applied tetap di luar scope.

## 4. Rencana verifikasi batas arsitektur

| ID | Kontrak yang perlu dibuktikan | Metode/evidence yang direncanakan | Status |
| --- | --- | --- | --- |
| AV-01 | Kalkulator deterministik, blank/0/komponen positif/kehadiran benar | Unit test PHP tanpa HTTP/DB/socket; expected mengikuti keputusan Q7 | NOT RUN |
| AV-02 | Semua entry point memakai policy dan proyeksi aman, termasuk role rangkap | HTTP/job/channel/file negative tests; tidak cukup tombol tersembunyi | NOT RUN |
| AV-03 | Mutasi/audit/inbox atomik dan batch tidak sebagian | Integration test MySQL dengan failure injection; DBV-13 sampai DBV-16 | NOT RUN |
| AV-04 | Versi/lock/dedup menjaga finalisasi dan apply serentak | Dua koneksi terkoordinasi, stale preview, duplicate retry; DBV-05/06/17/20 | NOT RUN |
| AV-05 | Perkembangan tidak bergantung pada rapor dan tidak membocorkan draft | HTTP/browser dari wali dengan rapor absent/draft/finalized/published | NOT RUN |
| AV-06 | Snapshot/file stabil dan kegagalan render dapat dipulihkan | Integration/file test, revisi sah, izin download, failure antar-tahap | NOT RUN |
| AV-07 | Realtime best effort tidak merusak inbox, scope atau read-state | Worker/Reverb mati, dispatch gagal, reconnect, dedup dan role dicabut | NOT RUN |
| AV-08 | Struktur menjaga tanggung jawab tanpa abstraksi berlebih | Review source/diff dan analisis PHP/TS; kalkulator tidak mengimpor HTTP/DB/queue; tidak membuat lint rule/alat baru tanpa kebutuhan | NOT RUN |
| AV-09 | UI memakai kontrak/state nyata dan dapat digunakan di ponsel/keyboard | Browser alur admin-guru-wali, validation/conflict/empty/error/loading | NOT RUN |
| AV-10 | Image/upgrade/backup tidak merusak data atau kontrak job | Pipeline, rehearsal MySQL/restore dan smoke revisi target; DBV-02/24/25 | NOT RUN |

DBV merujuk [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md). Saat aplikasi tersedia, rekam revisi, engine/digest, fixture, perintah/langkah aktual, output dan reviewer. Matriks ini belum merupakan tes atau daftar pemeriksaan yang telah lulus.

## 5. Kesimpulan telaah dan pemicu perubahan

Rekomendasi penyusun: gunakan ARC-001 sebagai dasar pembahasan, pertahankan satu codebase/DB dan pisahkan kalkulator serta operasi bisnis yang memiliki aturan nyata. Batas yang dirancang sesuai kebutuhan terdokumentasi, tetapi implementasi, keamanan dan performanya belum terbukti. Persetujuan pemilik mencakup bentuk aplikasi; detail desain lainnya belum disetujui menyeluruh. Belum ada review implementasi independen atau status siap rilis.

Buka kembali keputusan bila muncul klien API/mobile independen, banyak sekolah, kebutuhan offline, efek wajib replay, kebutuhan HA/PITR atau contention/latency terukur yang tidak dapat ditangani perbaikan lokal. Mulai dari penyebab yang terukur; tidak otomatis menjadikan microservices, Redis atau outbox sebagai solusi.

Langkah berikut: kunci detail teknis yang diperlukan untuk bootstrap, lalu selesaikan sisa keputusan hanya sebelum fitur terkait dimulai. AR-04/05 dan aturan numerik tidak perlu diputuskan ulang; implementasi dimulai hanya setelah instruksi baru. Risiko implementasi otorisasi/produksi harus mendapat review/gate sesuai risikonya; telaah penyusun ini tidak menggantikannya.

D-28 diterima: koreksi sumber adalah action terpisah dari draft CRUD; transaksi memuat audit/inbox dan penahanan rapor per siswa. Isi snapshot immutable, metadata lifecycle/version diaudit. Revisi memakai policy/calculation asli termasuk retired, satu versi published/current; render di luar lock dan attach/publish recheck. Jangan menambah workflow engine/tabel koreksi atau undo applied otomatis. [Lifecycle kanonis](ACADEMIC_FLOW.md), verifikasi API-T55..57/DBV29..30 **NOT RUN**.
