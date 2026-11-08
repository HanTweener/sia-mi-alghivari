# Spesifikasi fitur inti - SIA MI Alghivari

Revisi: 30 Oktober 2026. Dokumen ini berisi rancangan fitur nyata proyek, menggantikan template umum pada nama file yang dipertahankan. Status desain: blueprint dengan aturan inti Q1-Q5 dikonfirmasi; detail usulan ditandai terpisah. Implementasi **NOT STARTED**; seluruh kriteria dan pengujian **NOT RUN**.

## 1. Hasil dan batas produk

Satu aplikasi untuk satu sekolah: admin menyiapkan akademik, guru mencatat pembelajaran, wali kelas menerbitkan rapor lengkap, kepala sekolah menyetujui naik/lulus, dan wali memantau anak serta menerima rapor resmi. Data portofolio sintetis; bukan salinan biodata pada laporan KP.

Acuan kanonis: [PRD](../PRD.md), [matriks izin](../ROLE_PERMISSION_MATRIX.md), [akses objek](../RESOURCE_ACCESS_POLICY.md), [model data](../DATA_MODEL.md), [alur akademik](../ACADEMIC_FLOW.md), dan [alur pengguna](../USER_FLOWS.md). Dokumen ini merinci hasil yang harus terlihat tanpa mengubah keputusan sumber tersebut.

Route, transport, payload, respons dan semantik versi/clear input dirancang pada [API_CONTRACT.md](../API_CONTRACT.md). Kontrak membedakan Inertia, JSON, file dan auth broadcaster; bagian yang bergantung sisa Q6/parameter password D-25 tetap terbuka. Q7.1–5/D-18..22, handover/roster D-26/27 serta koreksi/revisi/revoke D-28 sudah diterima; detail visual/render dan implementasi belum diverifikasi. Ini bukan backend API terpisah atau implementasi endpoint.

| Fitur | Kebutuhan PRD | Hasil pengguna |
| --- | --- | --- |
| F-01 Akun, biodata dan profil | FR-001, FR-004, FR-012 | Satu akun memiliki hak rangkap yang terkontrol dan profil mandiri. |
| F-02 Konfigurasi sistem | FR-002 | Master mengatur informasi sekolah yang diperlukan. |
| F-03 Akademik dan penugasan | FR-003, FR-004 | Periode, siswa, kelas, pengajar dan jadwal siap digunakan. |
| F-04 Konfigurasi penilaian global | FR-007 | Semua mapel memakai KKM dan bobot pusat yang konsisten. |
| F-05 Kehadiran per mapel | FR-005 | Guru membuka dan mengesahkan pertemuan beserta status siswa. |
| F-06 Penilaian aktivitas | FR-006 | Guru menyimpan dan membagikan nilai dengan blank/0 yang benar. |
| F-07 Perkembangan Belajar | FR-013 | Wali memantau aktivitas anak tanpa menunggu rapor. |
| F-08 Rapor Online | FR-008 | Rapor lengkap dibekukan, dipublikasikan dan diunduh dengan izin. |
| F-09 Kenaikan/kelulusan | FR-009 | Keputusan principal disetujui dahulu dan diterapkan terpisah. |
| F-10 Audit | FR-010 | Perubahan memiliki riwayat sistem yang tidak dapat diedit pengguna. |
| F-11 Notifikasi | FR-011 | Pengguna menerima perubahan yang relevan tanpa bocoran scope. |

Lingkup tidak mencakup banyak sekolah, API publik terpisah, chat, AI, offline write, kanal eksternal untuk notifikasi CUD, formula bebas, atau impor massal baru. Email akses/password D-25 termasuk core; provider, parameter dan detail integrasinya masih terbuka. CRUD sederhana tetap memakai pola framework; tidak memerlukan workflow/permission engine generik.

## 2. F-01 - Akun, biodata dan profil

**Alur:** Master menetapkan akun aktif, role dan jabatan; admin mengelola biodata sesuai cakupan; pengguna masuk melalui satu sesi dan memperbarui profil sendiri. Pilihan konteks Administrasi/Mengajar hanya mengubah navigasi.

**Input/data:** users/user_roles/teachers/students/student_guardians dan otp_tokens/sessions; identitas, email wajib unik, foto/profil. D-25 Master membuat akun/email langsung verified administratif dan password sementara sistem emailed dengan URL biasa -> forced change -> modal pilihan sesi setelah commit. Q6.1 M dahulu lalu D biodata/link, existing tambah role tidak direset. Reset/profil OTP Fouladgar mengikuti [lifecycle](../RECOVERY_AND_STEPUP_PLAN.md); allowlist lain tetap Q6.

**Output/scope:** akun dapat memiliki kombinasi admin, teacher, principal dan guardian. Scope guru melalui penugasan aktif; scope wali melalui hubungan anak. Profil mandiri hanya allowlist nama/username/kontak/alamat/foto/password; role, penugasan, hubungan anak dan status akun tidak dapat diubah melalui profil.

**Gagal:** nonaktif/revoke, OTP/proof salah/expired/wrong-purpose/akun/browser/revisi, konfirmasi tidak cocok, foto invalid atau username duplikat menolak tanpa efek palsu. Audit gagal rollback password/proof/pending. Mail gagal sesudah commit tidak membatalkan akun/password dan tidak dianggap delivered. Secret tidak masuk UI admin/log/inbox/socket; foto lama tetap sampai referensi baru tersimpan.

- AC-01.1: Principal+teacher dapat membaca bidang sekolah yang diizinkan dan memasukkan nilai hanya pada mapel yang ditugaskan; mengganti konteks menu tidak memperluas hak.
- AC-01.2: Q6.1 Master akun wali/role/aktivasi dahulu lalu D biodata/link berizin. D-25 semua akun baru email langsung verified oleh Master, password sementara sistem emailed, forced change server sebelum domain dan modal wajib sesudah password commit. Non-Master tidak membuat/aktivasi/role; hash/password sementara/OTP tidak bisa dibaca ulang lewat administrasi. Existing tambah role tidak direset; nonaktif/reissue/expired/replay ditolak. URL biasa menggantikan D-23/D-24.
- AC-01.3: Reset username/email -> OTP tersimpan server -> password/konfirmasi -> commit -> modal pilihan logout/tidak wajib -> login biasa, tanpa auto-login. Profil sesi aktif + OTP tujuan profil, tanpa current_password -> commit -> modal -> current session tetap login, yes mencabut sesi lain/No mempertahankan. OTP/proof lintas akun/purpose/browser/email/revision/replay/race dan stale modal ditolak; response hilang resume pilihan tanpa replay password. Foto invalid/username duplikat/anak asing tidak menimpa atau membuka profil lama.

## 3. F-02 - Konfigurasi sistem khusus Master

**Alur:** Master membuka administrasi konfigurasi, mengubah nilai yang diizinkan, meninjau dan menyimpan. Informasi sekolah yang diperlukan rapor dibaca melalui proyeksi tersendiri.

**Input/data:** `system_settings` dengan key/struktur allowlist untuk profil sekolah dan pengaturan domain yang telah ditetapkan. Kredensial aplikasi, database, tunnel dan secret bukan isi menu ini; pengaturan internal database bukan konfigurasi pengguna.

**Output/scope:** hanya Master mendapat CRUD konfigurasi; principal membaca informasi sekolah yang memang boleh diproyeksikan, bukan endpoint administrasi. Penyimpanan disertai audit serta inbox penerima yang berhak dalam transaksi yang sama.

**Gagal:** key/struktur tidak dikenal ditolak; kegagalan audit membatalkan perubahan; perubahan profil sekolah tidak menulis ulang identitas pada snapshot rapor final lama.

- AC-02.1: Non-Master ditolak ketika membuka atau mengubah administrasi konfigurasi meskipun mengetahui URL/ID; informasi sekolah yang boleh tampil tetap tersedia pada rapor.
- AC-02.2: Penyimpanan key di luar allowlist atau field kredensial ditolak; UI tidak mempunyai kemampuan membaca secret runtime.
- AC-02.3: Ketika audit gagal, nilai konfigurasi lama tetap; ketika profil sekolah berubah sah, rapor final sebelumnya mempertahankan snapshot semula.

## 4. F-03 - Akademik, data operasional dan penugasan

**Alur:** Master/Akademik menyiapkan tahun/semester, rombel, mapel, anggota, guru/wali kelas dan jadwal. Data Entry menambah/memperbarui siswa, wali, kelas/mapel dan menyiapkan penugasan sesuai batas Q2/Q6, tanpa hapus atau konfigurasi akademik.

**Input/data:** `academic_years`, `terms`, `classrooms`, `enrollments`, `subjects`, `teaching_assignments`, `homeroom_assignments`, `schedule_slots`, dan delegasi khusus pada `data_entry_delegations`. Tanggal dan hubungan kelas/periode harus konsisten; satu siswa tidak memperoleh dua rombel pada semester yang sama.

**Output/scope:** `classrooms` tanpa ID guru langsung; wali kelas melalui satu `homeroom_assignments` aktif per rombel dengan histori inactive. Penggantian wali kelas atomik. Jadwal tidak tumpang tindih pada guru/kelas/periode yang sama. Role/jabatan tetap hanya Master.

**Batas delegasi:** Data Entry membantu draft nilai/absensi hanya dalam capability, mapel/kelas/semester dan masa berlaku tercatat; actor tetap petugas asli. Q6.3 mengonfirmasi Master/Akademik sebagai pemberi dan pencabut delegasi. Mekanisme permintaan/perubahan delegasi, DTO rinci serta aktivasi usulan penugasan guru Data Entry masih terbuka; bukan persetujuan alur lengkap.

**Gagal:** beda kelas/term, duplikasi anggota, overlap tanggal guru, bentrok jadwal guru baru dan delegasi usang ditolak. D26/27 ditetapkan: current-parent history/draft, final roster frozen; backdate yang mengubah final atau transfer antarkelas term sama ditolak. Koreksi final tersendiri, tanpa menyalin nilai.

- AC-03.1: Homeroom replacement tetap atomik. D26 MA handover guru bertanggal pada class_subject stabil, nonoverlap/jadwal valid, close delegation lama; current guru baru membaca histori/melanjutkan draft, asal/creator/audit tetap, guru lama tanpa grant assignment itu. Satu rekap/item mapel, published/final tetap dilindungi; role lain tetap.
- AC-03.2: Data Entry ditolak ketika menghapus data, mengubah konfigurasi, memberi dirinya hak aktif melalui penugasan, atau memublikasikan hasil melalui delegasi input.
- AC-03.3: Master/Akademik dapat memberi/mencabut delegasi sesuai Q6.3; role lain tanpa grant tersebut ditolak. Delegasi kedaluwarsa/dicabut saat form masih terbuka ditolak saat simpan; enrollment beda semester atau jadwal bertabrakan tidak tersimpan sebagian. Input tetap draft dengan actor petugas asli.

## 5. F-04 - KKM, komponen dan bobot global

**Alur:** Master/Akademik menyiapkan draft revisi kebijakan, memilih komponen pusat, mengisi KKM/bobot, memeriksa total dan mengaktifkan satu revisi global.

**Input/data:** `scoring_components` sebagai katalog stabil source_type score/attendance; `grading_policies` sebagai kepala revisi; `grading_policy_components` sebagai baris bobot. Komponen awal tugas, ujian harian, UTS, UAS dan kehadiran otomatis. Bobot fleksibel, bukan empat kolom tetap.

**Output/scope:** satu KKM dan set bobot untuk seluruh mapel, tanpa override guru/mapel/kelas. Total termasuk kehadiran tepat 100%; bobot 0 sah. Revisi yang pernah aktif dipertahankan dan rapor final membekukan versi.

**Gagal:** total selain 100% tidak dapat aktif; guru/Data Entry/principal tanpa role admin yang berhak ditolak menulis. Aktivasi serentak tidak menghasilkan dua kebijakan aktif. Q7.5 KKM/bobot0-100 inklusif/maksimal dua desimal diterima; pecahan asli invalid ditolak sebelum cast/insert tanpa pembulatan input/total. Total99,99/100,01 tidak dapat aktif;33,33+33,33+33,34 tepat100 dapat aktif. Q7.2-Q7.4 aturan skor/hasil/KKM juga diterima.

- AC-04.1: Kebijakan dengan total 99% atau 101% gagal diaktifkan dan kebijakan aktif lama tetap; total tepat 100% dapat diaktifkan oleh Master/Akademik.
- AC-04.2: Guru hanya memilih komponen input pusat; request override KKM/bobot atau skor manual untuk komponen kehadiran ditolak.
- AC-04.3: Perubahan konfigurasi menghasilkan revisi baru; preview lama meminta tinjau ulang sebelum finalisasi dan rapor final lama tidak berubah.

## 6. F-05 - Kehadiran per mata pelajaran

**Alur:** guru memilih penugasan mapel, membuka sesi bertanggal/bernomor, mengisi siswa, menyimpan draft dan mengesahkan. Data Entry dengan delegasi hanya membantu draft; Master/Akademik mengikuti grant akademik dan batas keadaan.

**Input/data:** `attendance_sessions` dan `attendance_records`; status hadir/izin/sakit/alpa, topik opsional, actor/waktu pengesahan. Tidak ada baris berarti belum dicatat, bukan alpa. Siswa harus anggota rombel yang sama.

**Output/scope:** sesi draft tidak masuk perkembangan wali/perhitungan. Sesi finalized yang berlaku bagi siswa dihitung satu pertemuan; pembilang hanya hadir, izin/sakit/alpa tetap di penyebut. Kehadiran banyak mapel tidak diubah menjadi jumlah hari.

**Gagal:** pengesahan dengan status siswa belum lengkap, sesi duplikat, objek di luar penugasan atau versi usang ditolak. Koreksi hasil sah harus mengikuti keadaan/revisi beralasan; tidak diam-diam mengganti snapshot rapor final.

- AC-05.1: Draft kosong belum dicatat, bukan alpa. D27 roster berdasarkan membership pada held_on; perubahan roster_version meminta review dan stale finalize -> error form roster_changed. Finalize menserialkan membership, set peserta/status tepat lengkap H/I/S/A lalu frozen records. D tidak mendapat hak clear/delete dari rekonsiliasi.
- AC-05.2: Dua finalized H/I dan satu draft ->1/2. D27 siswa masuk sesi7 dari10, empat finalized dengan3H/1S ->75%, bukan30%; sebelum masuk/setelah keluar tidak dihitung/alpa. Frozen denominator tidak difilter ulang dengan enrollment kini; backdate mengubah final ditolak, no transfer term sama.
- AC-05.3: Guru kelas lain, D tanpa delegasi dan wali ditolak; draft privat. D-28 koreksi H/I/S/A finalized MA termasuk closed atau G current term terbuka; D/W/P tanpa role tambahan ditolak. Roster/tanggal/parent frozen tetap, audit before/after dan hold rapor terkait atomik.

## 7. F-06 - Penilaian aktivitas dan rekap

**Alur:** guru memilih komponen score pusat, membuat penilaian, mengisi satu kelas, menyimpan draft lalu membagikan hasil. Blank sebagian boleh dipublikasikan dengan label Belum ada nilai. Delegasi Data Entry hanya memasukkan/memperbarui draft.

**Input/data:** `assessments`, `scores`, enrollment dan kebijakan aktif. Blank tidak dibuat sebagai baris skor numerik; 0 adalah nilai numerik. Assessment draft tidak masuk hasil publik/rapor. Komponen tidak memiliki formula/bobot khusus dari guru.

**Output:** kontribusi komponen = jumlah nilai numerik / banyak nilai numerik × bobot/100. Kontribusi hadir = hadir / pertemuan finalized yang berlaku × 100 × bobot hadir/100. Nilai akhir adalah jumlah kontribusi; hasil parsial belum disebut nilai akhir rapor.

**Gagal:** seluruh komponen berbobot positif blank menghasilkan Belum ada nilai dan memblokir finalisasi; bobot hadir positif dengan nol pertemuan menghasilkan Belum tersedia. Tidak ada nilai nol pengganti, pembagian 0/0 atau redistribusi bobot. Q7.2 skor input 0-100 inklusif/maksimal dua desimal diterima: invalid satu baris menggagalkan seluruh roster sebelum cast/insert, tanpa pembulatan input. Q7.3 menerima hasil akhir dua desimal half-up setelah total kontribusi presisi penuh, tanpa pembulatan antara; Q7.4 pembanding KKM memakai nilai akhir yang sudah dibulatkan: >= mencapai, < belum mencapai; hasil tidak tersedia tidak dibandingkan sebagai0.

- AC-06.1: Nilai 90, blank, 100 menghasilkan jumlah 190/pembagi 2; nilai 90, 0, 100 menghasilkan jumlah 190/pembagi 3 dan tampilan membedakan blank dari nol.
- AC-06.2: Komponen positif seluruhnya blank atau hadir positif tanpa pertemuan memblokir finalisasi; komponen bobot 0 kosong tidak memblokir dan tidak mengubah bobot komponen lain.
- AC-06.3: G current parent boleh draft asal guru lama; provenance tetap, average semua published score lintas guru. PATCH published ditolak. D-28 koreksi blank/0/angka oleh MA termasuk closed atau G current hanya term terbuka; versi/reason/izin salah tidak mengubah data. Perubahan per siswa menahan rapor terkait meski final_score bulat sama.

## 8. F-07 - Perkembangan Belajar untuk wali

**Alur:** wali memilih anak terkait, membuka Perkembangan Belajar, lalu memfilter mapel/komponen/tanggal untuk aktivitas nilai dan kehadiran yang telah dibagikan/disahkan.

**Input/data:** query `assessments` published/`scores` dan `attendance_sessions` finalized/`attendance_records` melalui hubungan `student_guardians`; tidak membaca `report_cards` sebagai syarat atau membuat tabel perkembangan duplikat.

**Output/scope:** daftar aktivitas, blank yang jelas, rekap sementara, waktu pembaruan dan kontak profesional wali kelas yang diizinkan. Satu wali dapat memilih anak yang memang terhubung; field staf pribadi dan aktivitas anak lain tidak disertakan.

**Gagal/kosong:** belum ada aktivitas dibagikan menampilkan keadaan kosong; belum ada rapor tidak mengunci halaman. Relasi anak yang dicabut membatasi daftar, jumlah, detail dan tautan berikutnya. Draft internal tidak bocor melalui rekap/audit/notifikasi.

- AC-07.1: Wali dapat membaca aktivitas anak yang dibagikan/disahkan ketika rapor belum ada, draft, atau finalized belum published; rekap tetap berlabel sementara.
- AC-07.2: Mengganti ID anak atau memfilter ID assessment anak lain tidak mengungkap data maupun jumlah; draft penilaian dan sesi tidak muncul.
- AC-07.3: Halaman membedakan Belum ada nilai dari 0 dan menyediakan akses Rapor Online terpisah dengan pesan belum tersedia bila belum dipublikasikan.

## 9. F-08 - Finalisasi, publikasi dan unduh rapor

**Alur:** Master/Akademik atau wali kelas aktif kelas binaan meninjau rekap seluruh mapel, versi konfigurasi dan kelengkapan; memfinalisasi snapshot; memastikan dokumen siap; lalu memublikasikan. Wali memilih semester/versi published/current aktif dan mengunduh.

**Input/data:** `report_cards`, `report_card_items`, sumber aktivitas sah dan penugasan wali kelas. Snapshot menyimpan identitas sekolah/siswa/kelas/guru/periode, versi kebijakan, KKM/bobot, rincian perhitungan dan kehadiran; susunan ringkas Q7.1 disepakati; aturan Q7.1-Q7.5 disepakati; detail visual/render belum final. Catatan wali kelas opsional tingkat rapor diisi pada draft binaan, dibekukan pada finalized; detail komponen aplikasi memakai snapshot revisi yang sama.

**Output/scope:** draft → finalized → published; revoked untuk versi ditarik. Finalized belum dapat dibaca/diunduh wali. Wali kelas tidak mengubah skor guru lain. File private diambil melalui otorisasi saat permintaan, bukan URL publik tetap/path dari klien.

**Gagal:** mapel tidak tercakup, komponen positif kosong, versi sumber/konfigurasi berubah atau wali kelas tidak aktif memblokir finalisasi. Render gagal menampilkan kegagalan/retry, tidak sukses unduh. Koreksi memakai revisi beralasan; data terbaru tidak menimpa snapshot lama.

- AC-08.1: Wali kelas aktif boleh mengisi catatan opsional draft serta memfinalisasi/memublikasikan rapor lengkap binaan; kelas lain, penugasan dicabut, edit skor guru lain dan edit catatan finalized/published ditolak. Catatan kosong tidak menghalangi finalisasi.
- AC-08.2: O hanya published/current aktif anak terkait; absent/draft/finalized/needs_revision/revoked/superseded dan anak asing tidak memberi snapshot/PDF/count versi internal. Semester pernah published boleh notice netral ditahan/dicabut. MA/W binaan aktif boleh revoke beralasan, termasuk term closed; O kehilangan unduh berikutnya.
- AC-08.3: PDF/rincian sesuai Q7.1 dan snapshot sama; isi lama/checksum tidak ditimpa. D-28 MA/W binaan revisi per siswa memakai policy/calculation asli, completeness dan file siap, publish mengganti versi aktif atomik. Source berubah saat render/publish menahan versi; gagal file/revisi tidak membuka hold/revoked. T-11 dan API-T55..57 membuktikan koreksi, lineage dan race; semua NOT RUN.

## 10. F-09 - Persetujuan dan penerapan naik/lulus

**Alur:** siapkan usulan siswa/tahun sumber/rapor dan tujuan; principal meninjau lalu approve/reject individual atau batch; pelaksana berhak menerapkan pada waktu berlaku. Master/Akademik sebagai pengusul/pelaksana masih rekomendasi Q6.

**Input/data:** `student_progressions`, sumber rapor/version dan tujuan tahun/kelas untuk kenaikan. State draft/submitted/approved/rejected/applied/cancelled/needs_review memisahkan keputusan dari perubahan keanggotaan.

**Output/scope:** principal memberi approval sebagai pengecualian READ-only; Master tanpa principal tidak melewati langkah itu. Apply kenaikan membuat enrollment tujuan; kelulusan mengubah status saat berlaku. Histori lama dipertahankan; perpindahan semester tidak otomatis menaikkan tingkat.

**Gagal:** sumber/tujuan berubah setelah approval menjadi needs_review dan memerlukan tinjau ulang; apply ganda ditolak. Batch diperiksa seluruhnya sebelum transaksi; satu baris tidak berhak/invalid membatalkan seluruh batch dengan masalah per baris. Tidak otomatis lulus karena KKM.

- AC-09.1: Approval principal tercatat tanpa langsung mengganti kelas/status siswa; Master tanpa principal ditolak memberi persetujuan resmi.
- AC-09.2: Proposal approved yang sumbernya berubah tidak dapat diterapkan; apply sah hanya sekali dan tidak menghapus enrollment/nilai tahun asal.
- AC-09.3: Batch dengan satu siswa invalid/di luar izin tidak mengubah siswa lain; hasil menampilkan kegagalan batch tanpa klaim sukses sebagian.

## 11. F-10 - Riwayat audit sistem

**Alur:** setiap CUD domain berhasil memanggil penulis audit internal pada transaksi bisnis yang sama; pengguna membuka riwayat sesuai objek/field yang masih boleh dibaca.

**Input/data:** `audit_logs` memuat actor asli, action/target/scope historis, waktu server, diff aman, alasan wajib dan batch/correlation. Per objek dalam batch memiliki audit sendiri; arsip/pembatalan tetap perubahan domain tercatat.

**Output/scope:** tidak ada endpoint CUD audit untuk pengguna, termasuk Master. Audit append-only; koreksi berupa event tambahan. Pencabutan relasi membatasi akses berikutnya; event draft internal tidak menjadi terlihat hanya karena assessment kemudian published.

**Gagal:** gagal audit menggagalkan bisnis/inbox; request gagal tidak dicatat sebagai perubahan berhasil. Tidak ada password/hash/token/session dalam diff; insert audit tidak membuat audit/notifikasi rekursif. Perlindungan akun aplikasi tidak diklaim mengalahkan administrator database/server.

- AC-10.1: Kegagalan insert audit membatalkan data bisnis dan inbox; batch berhasil mempunyai audit setiap objek dengan actor petugas asli, termasuk input delegasi.
- AC-10.2: Semua role, termasuk Master, ditolak insert/update/delete log melalui aplikasi; credential runtime tidak dapat UPDATE/DELETE audit atau menjalankan DDL.
- AC-10.3: Wali hanya melihat event diri/anak yang boleh ditampilkan, tanpa draft internal atau field rahasia; pagination/count audit tidak membocorkan objek terlarang.

## 12. F-11 - Inbox dan notifikasi realtime

**Alur:** setiap CUD domain menilai penerima dari role/scope/visibilitas; inbox disimpan bersama bisnis/audit; setelah commit database queue/worker memancarkan sinyal Reverb; Echo mengambil ulang inbox berizin.

**Input/data:** `notifications` milik recipient sesi, sumber audit/batch/kategori/scope dan ringkasan aman. Perubahan massal dikelompokkan satu notifikasi per operasi/penerima/kategori/cakupan; audit tetap per objek. Draft hanya ke penerima internal berhak.

**Output/scope:** private channel per pengguna, payload minimal notification_id/type, daftar/badge/detail/tujuan diperiksa ulang server. Sinyal duplikat tidak membuat inbox ganda; reconnect membaca riwayat persisten. Notifikasi aktivitas dan rapor menuju menu berbeda.

**Gagal:** kegagalan inbox membatalkan transaksi; broadcast gagal sesudah commit tidak membatalkan data tersimpan. Socket bukan exactly-once; catch-up inbox memulihkan tampilan. Read-state milik sendiri diaudit teknis tanpa notifikasi baru; tujuan yang tak lagi berhak menampilkan pesan netral.

- AC-11.1: Batch nilai menghasilkan satu notifikasi aman per penerima/cakupan, tetap memiliki audit tiap objek dan tidak mengirim draft kepada wali.
- AC-11.2: Saat worker/Reverb mati, mutasi dengan inbox yang sudah commit tetap berhasil; setelah reconnect pengguna membaca inbox terbaru tanpa duplikasi dari sinyal ulang.
- AC-11.3: Subscription channel pengguna lain, pembacaan inbox orang lain dan akses tujuan setelah relasi dicabut ditolak; menandai dibaca tidak memicu notifikasi rekursif.

## 13. Kontrak bersama UI, transaksi dan kegagalan

Rancangan aplikasi mengikuti [arsitektur](../ARCHITECTURE.md): satu sesi server, policy/action/query serta kalkulator nilai terpisah; bentuk satu aplikasi Laravel + Inertia + React/TypeScript dikonfirmasi 29 Oktober 2026, engine MySQL juga dipilih pada tanggal tersebut, baseline versi inti juga disepakati pada D-15, sedangkan versi persis, konfigurasi dan paket tambahan belum dikunci. Reverb/Echo telah dipilih; frontend bukan sumber izin atau hasil hitung resmi.

Mutasi mengikuti validasi → policy action/resource/field/state → pemeriksaan versi/lock → transaksi bisnis + audit + inbox → commit → efek jaringan/file yang terkontrol. Tidak ada bypass global Master, transaksi DB/filesystem palsu, atau offline write. File diselesaikan lalu ditautkan; lock tidak menunggu render/socket.

Navigasi selalu menampilkan tahun/semester/kelas/mapel/anak yang dipilih. Input nilai/absensi memakai satu aktivitas/pertemuan: grid desktop dan daftar terfokus ponsel. Loading, kosong, validasi, sukses, gagal, izin dicabut dan konflik versi mempunyai pesan spesifik; data yang aman dipertahankan saat simpan gagal, password/token tidak.

- AC-X01: Pada daftar, pencarian, pagination, badge, audit, file dan batch, filter izin diterapkan sebelum hasil/jumlah disajikan; menebak ID tidak memperluas akses.
- AC-X02: Dua editor/preview usang menerima konflik yang jelas; retry akibat timeout tidak membuat pengesahan/publikasi/apply atau inbox duplikat dan tidak menimpa versi baru diam-diam.
- AC-X03: Pada viewport uji 360 × 800 dan 1440 × 900, alur input serta portal wali dapat digunakan dengan keyboard, label/fokus terbaca dan pesan selain warna; viewport ini target verifikasi, bukan bukti uji.
- AC-X04: UI menampilkan kegagalan simpan/unduh yang sebenarnya dan mempertahankan isian aman; tombol proses yang dinonaktifkan tidak menggantikan kontrol duplikasi server.

## 14. Urutan, bukti dan keputusan tersisa

Urutan ketergantungan: akun/profil/akses dan fondasi audit/inbox → akademik/konfigurasi → nilai/absensi serta perkembangan wali → rapor → naik/lulus. Fondasi audit/inbox mendampingi mutasi sejak awal; realtime tidak menjadi prasyarat membaca riwayat tersimpan.

Rencana bukti: unit kalkulator untuk F-04/F-06; integration engine target untuk transaksi, constraint, konkurensi, snapshot dan apply; HTTP/policy/channel/file untuk scope; browser untuk perjalanan admin/guru/wali dan state UI. Gunakan fixture sintetis dan catat commit, environment, langkah aktual serta hasil. Acuan [rencana kualitas](../CODE_QUALITY_PLAN.md), [AV-01..10](../ARCHITECTURE_REVIEW.md) dan [DBV](../DATABASE_VERIFICATION_REPORT.md). Tidak ada command aplikasi atau hasil uji yang diklaim tersedia.

| Keputusan tersisa | Batas rancangan sebelum diselesaikan |
| --- | --- |
| Q6: allowlist biodata staf/wali, mekanisme penugasan/delegasi, pengusul/pelaksana rollover dan detail integrasi password | Q6.1 urutan Master membuat/mengaktifkan akun wali dahulu lalu Data Entry biodata/hubungan sudah dikonfirmasi. Q6.2/Q6.4 dipilih D-25; parameter expiry/limit/provider/repository/middleware dan detail Q6 lain masih terbuka. Input biodata tidak menerbitkan akun aktif/role atau credential. |
| Q7: detail visual dan bukti implementasi | Batas skor input Q7.2, pembulatan hasil Q7.3, pembanding KKM Q7.4 dan presisi konfigurasi Q7.5 diterima pada D-19..D-22; susunan ringkas Q7.1 diterima 29 Oktober 2026, detail visual/font/asset/pagination belum diverifikasi. |
| AR-04 / D26 dikonfirmasi | class_subjects stabil/assignment history/current grant/satu item mapel; runtime/FK/jadwal/race belum diverifikasi. Koreksi final bukan handover. |
| AR-05 / D27 dikonfirmasi | Enrollment efektif, frozen roster dan denominator per siswa; backdate final ditolak. Transfer antarkelas term sama/rapor transfer ditunda, bukan kasus yang diimplementasikan otomatis. |

Status keseluruhan: **11 fitur, 37 kriteria penerimaan; implementasi NOT STARTED, seluruh AC/pengujian NOT RUN**. Dokumen adalah rancangan kebutuhan proyek, bukan instruksi membuat fitur di luar scope atau izin implementasi/deployment sekarang.

Penyusunan dilakukan subagen penulis fitur dan diintegrasikan agen utama. Telaah dokumentasi oleh subagen terpisah tidak menemukan konflik substantif pada role, approval dan status keputusan. Cakupan telaah tersebut bukan pengujian implementasi atau audit keamanan.

D-28 mengikat F-05/F-06/F-07/F-08/F-09/F-10/F-11: [alur koreksi dan revisi](../ACADEMIC_FLOW.md), [kontrak HTTP-18/23](../API_CONTRACT.md). Revisi tidak memberi W hak mengedit nilai; applied progression tidak dibalik otomatis, histori/target tetap dan tindak lanjut staf ditandai.

D-29 melengkapi F-02/AC-02.1..3: satu school_timezone Master dinamis, label WIB/WITA/WIT, IANA allowlist; kalender dan tampilan sama, UTC internal. Refresh tanpa restart; non-Master admin settings ditolak, nilai invalid/DELETE key wajib/stale/audit failure tidak mengubah key. Semua role hanya proyeksi timezone, snapshot/PDF lama tetap. Nilai awal setup belum dipilih; cap delegasi DATE vs UTC masih OPEN. [Kontrak](../API_CONTRACT.md).
