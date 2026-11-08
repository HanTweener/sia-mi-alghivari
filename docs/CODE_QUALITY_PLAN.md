# Rencana kualitas kode - SIA MI Alghivari

Diperbarui: 29 Oktober 2026. Pemilik standar/penerimaan: pemilik proyek; pelaksana: pengembang fitur terkait. Status: **PROPOSED / blueprint**. Aplikasi inti/server belum diimplementasikan; command dan tes backend **BELUM DIKONFIGURASI / NOT RUN**. Prototipe frontend terisolasi sudah ada; bukti dan command prototipe tidak menjadi bukti kualitas aplikasi inti.

Acuan: [TECH_STACK.md](TECH_STACK.md), [ARCHITECTURE.md](ARCHITECTURE.md), [review arsitektur](ARCHITECTURE_REVIEW.md), [PRD](PRD.md), [pipeline](CICD_PIPELINE_SPEC.md) dan [verifikasi database](DATABASE_VERIFICATION_REPORT.md). Dokumen ini menetapkan rencana, bukan laporan pemeriksaan yang sudah lulus.

## 1. Kondisi awal dan batas

Belum tersedia manifest/runtime atau workflow aplikasi inti di root. [Prototipe facelift-v2](support/facelift-v2/README.md) mempunyai package.json, lockfile, React/TypeScript, Tailwind/shadcn, skrip build/test/format dan fixture sendiri. Hasil tinjauan historis mengikuti [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md) dan [laporan rebuild](support/facelift-v2/qa/rebuild-report.md), dibatasi revisi/artefak yang diuji; tidak dijalankan ulang pada pembaruan dokumentasi ini. Tidak ada baseline backend, kapasitas atau durasi CI aplikasi inti yang diukur.

Target mengikuti usulan Laravel/PHP, Inertia, React/TypeScript dan MySQL pada tech stack. Pilihan backend/database/paket tetap mengikuti status persetujuannya. Tidak memasang dependensi, membuat konfigurasi gate atau menjalankan aplikasi pada tahap ini.

Tujuannya: perubahan mudah dipahami dan menjaga hasil akademik, hak akses, histori serta kegagalan yang jujur. Satu alat untuk setiap kebutuhan yang nyata; tidak menambah dashboard kualitas, generic repository, mutation testing atau linter tambahan hanya untuk memenuhi template.

## 2. Standar source dan tanggung jawab

Gunakan **Ponytail full** sebagai pedoman implementasi: perubahan terkecil yang menyelesaikan kebutuhan secara lengkap, dengan source tetap terbaca. Sebelum mengedit, petakan pemanggil, konsumen kontrak, tes, fixture, konfigurasi dan ekspor yang terkena; periksa risiko data hilang/bocor atau alur pengguna rusak.

Urutan pilihan: hilangkan kebutuhan yang tidak diminta; gunakan helper/komponen/pola proyek yang sesuai; baru fasilitas platform atau dependensi yang telah dipilih. Komponen rumah yang layak didahulukan sebelum membuat kontrol baru. Jangan membuat wrapper/repository/opsi/konversi tambahan tanpa kebutuhan, tetapi tetap pertahankan konversi desimal/ID, null/omitted, validasi dan error handling yang diwajibkan kontrak.

Logika baru yang tidak trivial memerlukan tes kecil yang bermakna atau self-check yang sesuai; aturan keamanan, transaksi dan kalkulasi tetap memerlukan kasus positif/negatif/race yang relevan. Perubahan copy/style reversibel kecil tidak memerlukan suite baru. Keterbatasan teknis yang sengaja diterima dicatat dengan alasan dan pemicu perbaikan; jika berada pada source, gunakan komentar singkat `shortcut: <batas>, <kapan ditingkatkan>`. Jangan memakai label shortcut untuk melewati kebutuhan, keamanan, aksesibilitas atau gate rilis.

| Area | Aturan yang direncanakan | Tanda perlu diperbaiki saat review |
| --- | --- | --- |
| PHP/Laravel | Ikuti formatter/framework, tipe parameter/return dan nullability jelas; nama menggambarkan operasi | Controller menyimpan rumus/SQL panjang, service serba guna, exception ditelan menjadi sukses. |
| Action dan transaksi | Satu pemilik use case, policy diperiksa server, satu transaksi domain/audit/inbox | Commit bertingkat yang memecah batch, write langsung melewati audit, jaringan/PDF di dalam lock. |
| Kalkulasi | Fungsi PHP deterministik, input/hasil presisi, blank berbeda dari 0, policy/calculation version jelas | Float browser menentukan hasil akhir, konstanta bobot/KKM, rounding atau formula ganda di beberapa layar. |
| React/TypeScript | Props/form/result bertipe, usulan strict aktif; komponen/hook memiliki tujuan jelas | any/cast menutupi kontrak salah, state turunan digandakan, efek/fetch berulang atau subscription tidak dibersihkan. |
| Query/model | FK/unique/cast sesuai model, scope/filter sebelum count/pagination, parameterisasi dan batas hasil | N+1, SELECT seluruh relasi sensitif, sort kolom bebas dari klien, daftar tidak dibatasi. |
| UI/form | Label/fokus/keyboard jelas; loading, kosong, validasi, gagal, sukses dan konflik mengikuti data nyata | Tombol mati, sukses palsu, error hanya warna, kehilangan isian aman atau ID kelas/anak salah setelah berpindah konteks. |
| Blade/PDF | Template cetak terpisah dari React, escaping dan layout dapat dibaca; snapshot sebagai sumber | Merender live data untuk rapor lama, teks terpotong, hasil berbeda dari rekap server. |
| Audit/log/file | Diff dan log minimum tanpa secret/PII berlebih; file privat lewat route berizin | Hash/password/token di log, path klien dipercaya, cascade menghapus histori atau audit. |

Pertahankan struktur Laravel biasa: controller/request/policy/action/query dan service yang diperlukan. Tidak ada batas jumlah baris/class atau skor kompleksitas yang dianggap bukti otomatis arsitektur baik. Pecah kode bila tanggung jawab/ketergantungannya sulit dipahami; jangan membuat abstraksi tanpa penggunaan nyata.

Source PHP/JS/TS/TSX/Blade/CSS manusia tetap rapi, bukan blok satu baris padat. Aset minified hasil build berbeda dari source. Komentar menjelaskan alasan/aturan yang tidak jelas dari kode; jangan menutupi nama yang buruk dengan komentar panjang. Vendor, dependensi dan hasil build tidak direformat sebagai bagian fitur.

Kebijakan type strict, level Larastan, aturan lint dan pengecualian vendor/generated ditetapkan eksplisit saat bootstrap, mengikuti versi riil. Jangan mematikan strict, menambah blanket ignore/baseline atau menyembunyikan error untuk membuat CI hijau.

## 3. Matriks pemeriksaan otomatis

Semua baris di bawah masih **BELUM DIKONFIGURASI / NOT RUN**. Kolom tool adalah pilihan rancangan, bukan executable/script yang telah diverifikasi. Pengembang yang mengubah area tersebut memiliki pemeriksaannya; pemilik proyek meninjau pengecualian.

| ID | Kategori | Tool/pendekatan yang direncanakan | Berlaku dan syarat aktivasi |
| --- | --- | --- | --- |
| CQ-01 | Format PHP | Laravel Pint, mode check tanpa mengubah source pada CI | Source PHP tersedia, versi/preset terkunci dan command diuji. Pint bukan formatter Blade. |
| CQ-02 | Analisis PHP | Larastan/PHPStan | Konfigurasi paths, level dan bootstrap Laravel cocok; tidak memasukkan hasil generated/vendor sebagai source proyek. |
| CQ-03 | Format/lint frontend | Satu formatter dan linter kompatibel tooling starter kit final | Baca package.json/lockfile dahulu; jangan otomatis menumpuk ESLint/Prettier di atas alat bawaan yang setara. |
| CQ-04 | Typecheck | TypeScript, pemeriksaan tanpa emit | tsconfig dan script riil tersedia; build bundler tidak dianggap pengganti typecheck. |
| CQ-05 | Tes PHP | PHPUnit melalui konfigurasi proyek | Unit kalkulator dan HTTP/domain; integrasi memakai MySQL target serta grant runtime yang benar. |
| CQ-06 | Browser inti | Playwright | Alur login/admin-guru-wali dan area berubah; data/session terisolasi, tanpa mengandalkan urutan tes. |
| CQ-07 | Build/artefak | Composer/npm dan build tooling starter kit; verifikasi image runtime | Install dari lockfile, build aset, startup/HTTP image tanpa secret demo. |
| CQ-08 | Dependensi | Audit Composer/npm atau fasilitas ekivalen yang dipilih | Cakup dependensi runtime dan pengembangan; pisahkan severity, dampak dan perbaikan tanpa upgrade mayor buta. |
| CQ-09 | Secret/image | Scanner secret dan image yang dipilih untuk pipeline | Nama/version/command/policy ditetapkan sebelum publish diaktifkan; jangan menganggap fitur GitHub berbayar tersedia. |
| CQ-10 | Keterbacaan source | Audit struktur lokal dan review diff, ditambah formatter sesuai bahasa | Perubahan source/template/script manusia; compiled assets dikecualikan, Blade buatan proyek tetap diperiksa. |
| CQ-11 | UI/PDF | Browser/keyboard/responsive review dan render PDF | Wajib untuk perubahan terkait; otomatisasi seperlunya, bukan snapshot setiap komponen. |

Tidak mencatat argv fiktif seperti npm run lint/test/build sebelum script tersedia. Saat bootstrap, daftarkan argv nyata per kategori beserta working directory, runtime/container, mode check, output, timeout dan cakupan. CI tidak boleh menjalankan formatter auto-fix atau sekadar menginstal tool tanpa menjalankan pemeriksaannya.

Pendamping framework yang sudah tersedia di repository adalah .ai/tools/source_structure_audit.py. Pada perubahan source kelak, gunakan argv sesuai interpreter lingkungan, misalnya `["python", ".ai/tools/source_structure_audit.py", "--root", ".", "--changed"]`; mode changed memerlukan Git. Jika belum ada Git, gunakan paths source yang benar-benar ada. Ini contoh pemanggilan alat lokal, bukan bukti pemeriksaan aplikasi inti; hasil audit prototipe historis dicatat terpisah pada paket referensi. Audit heuristik tidak membuktikan perilaku atau batas modul.

Setelah command nyata diverifikasi, barulah sinkronkan script Composer/npm, CI dan bila digunakan .ai/quality/quality-gates.json dengan argv terpisah, bukan shell string bebas. Manifest framework tidak diubah pada tahap blueprint. Check wajib yang kosong/gagal tidak boleh tampil PASS; dry-run bukan eksekusi pengujian.

## 4. Strategi pengujian berdasarkan risiko

| Lapisan | Fokus proyek dan kasus minimum | Rujukan |
| --- | --- | --- |
| Unit kalkulator | Rata-rata berbobot, blank sebagian/seluruhnya, 0, bobot 0/total 100, hadir/nol pertemuan dan versi hasil | AV-01; DBV-07/08/09. Batas skor input Q7.2 diterima; uji validasi sebelum cast/insert melalui kasus service/HTTP. Q7.3 expected half-up dua desimal dan tanpa pembulatan antara sudah dipilih; comparator Q7.4 memakai nilai akhir yang dibulatkan dan >= KKM. Uji equality/di bawah/di atas/raw vs rounded, unavailable dan snapshot policy lama; presisi konfigurasi Q7.5 juga diterima; implementasi belum diverifikasi. Satu kalkulator server, uji tie dan kegagalan pembulatan dini, bukan pembandingan float. |
| Policy/HTTP | Guru beda mapel/kelas, wali beda anak, role rangkap, principal read/approve, homeroom inactive, delegasi dicabut dan profil tanpa eskalasi | AV-02; DBV-10/11/12/14/15. |
| Integrasi MySQL | Unique/FK/CHECK, audit/inbox rollback, batch atomik, token versi, idempotensi dan dua penulis serentak | AV-03/04; DBV-01 sampai DBV-17/20 yang berlaku. |
| Snapshot/file | Rapor lama stabil, finalized belum dapat diunduh wali, publish kelas sendiri, render gagal/retry dan file di luar scope | AV-05/06; DBV-17/18/19. |
| Antrean/realtime | Tanpa broadcast rollback, dispatch gagal, worker/Reverb mati, private channel ditolak, dedup dan reconnect/catch-up | AV-07; DBV-13/21. |
| Browser/UI | Input satu kelas, konflik, Perkembangan Belajar tanpa rapor, publikasi dan unduh, approval/apply serta profil | AV-09 dan alur pada USER_FLOWS. |
| Upgrade/operasi | Build image, kontrak job, upgrade schema, volume tetap, backup/restore dan domain/WSS | AV-10; DBV-02/24/25 serta rencana deployment. |

AV merujuk [ARCHITECTURE_REVIEW.md](ARCHITECTURE_REVIEW.md); DBV merujuk [DATABASE_VERIFICATION_REPORT.md](DATABASE_VERIFICATION_REPORT.md). Jangan menyalin seluruh skenario ke suite baru hanya untuk mengganti nama ID. Setiap skenario dipetakan ke bukti/tes yang benar-benar mengujinya.

Utamakan assertion hasil dan efek nyata: row/constraint, scope respons, snapshot, file dan antrean. Mock dipakai untuk isolasi efek luar yang tepat; tidak mengganti seluruh DB/policy atau membuat tes selaras dengan implementasi yang salah. Tes E2E memakai locator berbasis role/label dan keadaan yang dapat ditunggu, bukan timeout tidur tetap atau selector CSS rapuh. Bersihkan data secara aman hanya pada resource uji terisolasi.

Fixture sintetis harus membedakan anak/kelas/penugasan dan state publikasi. Bekukan waktu pada tes yang memerlukan batas delegasi/semester; uji timezone eksplisit. Tes konkurensi menggunakan koneksi independen dengan sinkronisasi terkontrol, bukan tes berurutan yang diberi nama race test. Kegagalan autentikasi/akses tidak diuji hanya dengan akun migrator/root.

Tidak menetapkan target coverage persentase tanpa baseline. Ukur cakupan cabang kalkulator/izin/transisi setelah source tersedia; setiap aturan penting harus memiliki kasus positif, negatif/batas dan kegagalan yang relevan. Tidak menulis tes untuk copy/style reversibel kecil atau tes yang hanya meniru detail internal. Jika muncul logika frontend terisolasi yang belum terjangkau browser, tambahkan unit runner hanya setelah kebutuhannya jelas.

## 5. Kapan pemeriksaan wajib dijalankan

| Tahap | Pemeriksaan dan batas keberhasilan |
| --- | --- |
| Iterasi lokal | Formatter/static/type dan tes terfokus untuk area berubah; untuk UI/PDF periksa state, ponsel/keyboard/render terkait. Tidak mengulang semua suite pada setiap edit kecil. |
| PR/integrasi ke main | Seluruh pemeriksaan otomatis yang diwajibkan untuk aplikasi/tahap fitur, suite regresi yang relevan termasuk negatif otorisasi/data, build dan review diff. Perubahan source menjalankan audit struktur yang sesuai. |
| Commit hasil merge | Verifikasi revisi main aktual, build image rilis sekali, uji artefak serta scan wajib sebelum publish. Bukti PR dari commit berbeda tidak otomatis menjadi bukti rilis tersebut. |
| CD demo | Gate CI/image lolos, kompatibilitas schema/job dan backup terpenuhi; pemeriksaan HTTP, DB, worker dan WSS/alur inti pada target. Publish GHCR saja bukan deployment berhasil. |
| Produksi sekolah | Seluruh gate engineering, QA, keamanan, database, deployment/pemulihan yang berlaku, bukti revisi terkini, review sesuai risiko dan otorisasi rilis. Tidak digantikan tes lokal terfokus. |

Dokumen-only yang tidak mengubah perilaku cukup diperiksa isi, tautan dan konsistensinya. Jika CI memakai path filter, job agregator tetap memastikan check wajib yang dibutuhkan benar-benar berjalan; status skip akibat filter bukan PASS perilaku. Rincian orchestration: [CICD_PIPELINE_SPEC.md](CICD_PIPELINE_SPEC.md).

## 6. Review, temuan dan pengecualian

Review perubahan menilai nama/tanggung jawab, dependensi, policy/action, transaksi/lock, proyeksi data, null/0, efek gagal/retry dan UI yang benar-benar bekerja. Pastikan test menguji aturan yang disepakati, bukan memilih aturan produk yang masih terbuka. Bagian Q6/visual Q7 dan lifecycle yang terbuka harus diselesaikan untuk fitur terkait sebelum diklaim lengkap.

Temuan yang membocorkan data/secret, melewati izin, menghilangkan audit/histori, menghasilkan nilai salah, merusak snapshot atau menyebabkan kehilangan data menghalangi merge/rilis. Jangan meloloskannya dengan menghapus tes, mengurangi strict/level, snapshot update buta atau menambahkan ignore umum. Kekurangan maintainability yang terbatas dapat dicatat dengan pemilik, alasan, dampak, kontrol pengganti dan tenggat yang ditinjau.

Flaky test ditelusuri penyebabnya; retry boleh mengumpulkan diagnosis tetapi tidak mengubah kegagalan berulang menjadi bukti sehat. Jika karantina diperlukan, catat cakupan yang hilang dan pengganti pemeriksaannya; invariant kritis yang kehilangan bukti tetap menghalangi rilis. Keputusan dependency vulnerability berdasarkan severity dan keterjangkauan nyata; temuan high/critical yang relevan harus diperbaiki sebelum rilis. Scanner bersih bukan bukti tidak ada kerentanan.

Review independen digunakan sesuai risiko nyata perubahan keamanan, integritas dan deployment; telaah penyusun atau tool hijau tidak diklaim sebagai review independen. Tidak menambah agen/alat review permanen untuk setiap perubahan kecil.

## 7. Bukti, aktivasi dan batas kesederhanaan

Plugin berikut adalah alat kerja penyusun/pengembang, bukan paket runtime, konfigurasi CI yang otomatis aktif, atau pemberi persetujuan produk. Baca SKILL.md yang benar-benar tersedia pada sesi; jangan menyalin folder plugin/cache ke aplikasi atau menginstal dependensi hanya untuk mengisi gate.

| Skill | Pemakaian pada implementasi yang diotorisasi | Bukti dan batas |
| --- | --- | --- |
| `antislop:antislop` beserta pendamping UI/copy/human/mobile yang relevan | Mode **During** dipilih pemilik pada sesi 29 Oktober 2026. Terapkan pada proses kerja: arah produk jelas, copy/data jujur, kontrol berfungsi, state, keyboard dan responsif; bukan larangan dekorasi tanpa menilai tujuan. | Jalankan Delivery Gate dengan bukti sesuai cakupan perubahan UI. Jangan menyebut PASS tanpa inspeksi/eksekusi; pada pekerjaan dokumen saja, pemeriksaan UI/runtime tetap NOT RUN atau N/A dengan alasan. Preferensi sesi ini tidak mengubah pengaturan global. |
| `taste-skill:design-taste-frontend` | Untuk produk sekolah gunakan alur **app screens**, bukan alur landing page. Pakai objek/tugas nyata, hierarki yang jelas, tabel/angka/label yang konsisten dan referensi facelift sebagai bahan pembanding. | Ketika UI diubah, jalankan pemeriksaan app yang tersedia, telaah screenshot lebar relevan dan perbaiki lalu cek ulang. Nilai/gaya acak atau skor inspector tidak menggantikan kebutuhan, policy, riset atau verifikasi aksesibilitas. |
| `ponytail:ponytail` | Level **full**: satu perbaikan pada sumber bersama, reuse yang sesuai, tanpa fitur/lapisan/dependensi spekulatif; penyelesaian tetap mencakup seluruh konsumen yang terpengaruh. | Laporan akhir menyebut apa yang tidak diperiksa dan risiko yang tersisa. Penghematan tidak menjadi alasan memadatkan source, melemahkan tipe, menghapus validasi atau mengurangi gate wajib. |

Hierarki keputusan tetap kebutuhan pemilik, PRD/akses/API dan batas keamanan, lalu arah desain yang ditinjau, kemudian saran skill. Jika aturan estetika skill tidak sesuai kontrak, jangan diam-diam mengubah aturan akademik, mengganti stack, membuat logo/font/animasi baru atau menambah dependensi. Styling mengikuti [DESIGN_SYSTEM](DESIGN_SYSTEM.md): Tailwind dan shadcn/ui yang sesuai didahulukan; CSS manual hanya setelah kebutuhan tidak tercakup dan alasannya dicatat. Verifikasi versi/argumen tool yang benar sebelum dipakai; kegagalan menjalankan tool dilaporkan, bukan dibuat PASS.

Catat revisi/commit, digest image bila relevan, waktu, environment/runtime/engine, argv aktual, exit code, fixture, laporan serta manual check dan kekurangannya. Status menggunakan PASS/FAIL hanya sesudah eksekusi, NOT RUN bila belum dijalankan, BLOCKED bila terhalang dan SKIP/N/A dengan alasan spesifik. Laporan tidak memuat secret/data pribadi atau gambar yang membocorkan profil.

Saat implementasi diotorisasi: pilih rilis starter kit/paket, baca script/lockfile, konfigurasi alat minimum dan paths, uji satu kegagalan yang disengaja untuk memastikan gate tidak hijau palsu, lalu simpan baseline bersih pada CI. Jangan mengaktifkan command placeholder atau mengklaim gate berlaku hanya karena dokumen/JSON sudah terisi. Perintah dan baseline kualitas dirawat bersama perubahan toolchain.

Pengukuran awal yang berguna adalah jumlah/penyebab kegagalan lint/type, cacat regresi domain, flaky tests dan durasi pemeriksaan nyata. Belum ada hasil atau target angka yang diklaim. Pilih perbaikan dari masalah berulang; belum membutuhkan SonarQube, layanan telemetry tambahan, lint rule arsitektur khusus atau test framework ganda.

Referensi primer diperiksa 28 Oktober 2026: [Laravel Pint](https://laravel.com/framework/docs/13.x/pint), [Larastan](https://github.com/larastan/larastan), [TypeScript strict](https://www.typescriptlang.org/tsconfig/strict.html), [Playwright best practices](https://playwright.dev/docs/best-practices). Versi/konfigurasi tetap ditetapkan saat bootstrap proyek yang sebenarnya.

D-25: paket OTP hanya Fouladgar, tanpa lapisan auth generik/Spatie/Livewire baru. Repository adapter untuk hash/atomic consume dan session-generation guard memenuhi kebutuhan nyata; jangan mengklaim keduanya fitur default paket. Pertahankan password/proof/pending/audit consistency, tujuan server, parameter terpusat, secret redaction pada mail jobs/errors. Tes risiko AS/REC/AUTH-PW/DBV26, termasuk No mempertahankan sesi dan modal setelah commit; semua NOT RUN. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

D26/D27: jangan mencampur source assignment/creator dengan pemilik izin terkini. Reuse query parent/class-subject yang sama pada nilai, absensi, rapor dan notifikasi; tanpa copy/merge nilai atau tabel peserta/engine transfer. Tests meaningful: one-item report, all-score mean, current/old grant, provenance FK, date boundaries, stale roster dan frozen denominator. Form Inertia konflik roster berupa error form/review, bukan raw409. Source/migration/tes aplikasi inti belum diimplementasikan; bukti prototype terpisah, blueprint review bukan runtime proof.

D-28 diterima: koreksi sumber adalah action terpisah dari draft CRUD; transaksi memuat audit/inbox dan penahanan rapor per siswa. Isi snapshot immutable, metadata lifecycle/version diaudit. Revisi memakai policy/calculation asli termasuk retired, satu versi published/current; render di luar lock dan attach/publish recheck. Jangan menambah workflow engine/tabel koreksi atau undo applied otomatis. [Lifecycle kanonis](ACADEMIC_FLOW.md), verifikasi API-T55..57/DBV29..30 **NOT RUN**.

D-34: [QA_EVIDENCE_REPORT](QA_EVIDENCE_REPORT.md) menjadi kontrak bukti per tahap/revisi. Slice lokal mencatat hasil nyata CQ/AC terkait, integrasi/rilis tetap seluruh checks yang berlaku pada revisi target, kemudian public smoke terotorisasi memeriksa artefak/config aktif. Bukti prototipe tidak memenuhi checks backend. Command, output, counts, findings, reviewer dan approval tidak diisi sebelum eksekusi; semua inti sekarang NOT RUN.
