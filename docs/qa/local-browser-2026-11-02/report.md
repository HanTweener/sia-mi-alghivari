# Pemeriksaan browser lokal — 2 November 2026

- Target: `http://localhost:8000`, Docker development, revisi awal `c060308`; working tree bersih sebelum pemeriksaan.
- Metode: skill computer-use dibaca dan diinisialisasi; kontrol browser melalui CUA in-app browser karena provider Chrome tidak tersedia pada kontrol browser. Interaksi UI sungguhan; pembacaan source/metadata runtime hanya untuk diagnosis.
- Data: fixture sintetis `DemoPortfolioSeeder` yang sudah ada (16 akun saat mulai). Tidak melakukan seed/reset/migrasi, tidak mengirim email eksternal, tidak memperbaiki aplikasi selama audit.
- Status: BERJALAN. PASS hanya berlaku untuk skenario yang benar-benar dicoba, bukan sertifikasi seluruh fitur atau rilis.
- Bukti screenshot di `evidence/`. Akun/password/token asli tidak dicatat.
- Inventaris route runtime: [routes-coverage.csv](routes-coverage.csv), 112 route. Sebanyak 56 mempunyai anotasi hasil/percobaan (termasuk FAIL/PARTIAL/UNVERIFIED), 56 masih NOT RUN, termasuk pengajuan yang BLOCKED. Angka 56 bukan jumlah route lulus atau ukuran kelengkapan pengujian peran/data.

## Cakupan dan hasil

| Kelompok | Skenario yang sudah dicoba | Hasil |
| --- | --- | --- |
| Beranda/login | Beranda, login demo Master, redirect sesudah login | PASS |
| Akun/peran | Daftar 16 akun, buka dialog tambah, required validation, batal, pencarian tanpa hasil | PASS untuk skenario tersebut; simpan akun/ubah akses belum dicoba |
| Pengaturan | Buka, simpan nilai yang sama, nama aksesibel kontrol | FAIL B01/B02 |
| Tahun/semester | Buat tahun2029/2030 dan semester1 draft, required validation | PASS buat; label semester FAIL B03 dan lokalisasi FAIL B06; aktivasi belum dicoba |
| Kelas/roster/mapel/jadwal | Daftar, tambah kelas QA, buka roster dan mapel kelas | Pembuatan PASS; roster/mapel FAIL B03/B04; operasi turunannya terblokir |
| Siswa/wali/import/export | TambahQA, cari via Enter, edit, buka/batal taut wali, exportCSV/template, impor invalid dan valid | Tambah/cari/export/import PASS; edit FAIL B07; pagination FAIL B08; commit relasi wali NOT RUN |
| Kebijakan penilaian | Katalog, tambah komponen QA, nonaktifkan komponen QA, simpan draf bobot 99%, klik aktivasi 99% | CRUD yang disebut PASS; alert aktivasi memblokir kontrol browser, hasil dialog tidak terverifikasi |
| Absensi/delegasi | URL langsung class-subject1, sesiQA10, isi3/4, simpan/reload, finalisasi disabled; isi4/4, sahkan, terkunci; dialog delegasi | PASS simpan/reload/finalisasi/kunci; grant/revoke delegasi NOT RUN |
| Penilaian/skor | BuatUTS7; skor101,0,79 koma99,100,blank; simpan/reload; template/imporCSV; tiga desimal; publikasi/kunci | PASS rentang/persistensi/CSV/publikasi/kunci; FAIL B09 pesan presisi |
| Rapor/PDF/P5/ekstrakurikuler | Preview kelas13/rincian/filter; draft16 catatan/ekskul/P5; penolakan finalisasi; published1 detail/PDF/renderulang | PASS catatan/ekskul/P5 dan penolakan incomplete; FAIL B10/B11; finalisasi valid/publikasi/revisi/revoke NOT RUN |
| Kenaikan/kelulusan | List/status8; batch draf rombelhistoris hasil0; detail4/editcatatan; klik Ajukan | List/edit PASS; pengajuan tertahan dialog alat; approve/reject/batch/apply NOT RUN |
| Wali murid | Belum dicoba | NOT RUN |
| Profil/password/pemulihan | Profil dibuka, submit tanpa perubahan | PASS buka saja; persistensi perubahan profil dan pemulihan NOT RUN; profil reguler tidak menyediakan ubah password |
| Inbox/notifikasi/audit | Lonceng → loading → kosong, inbox kosong, filter belum dibaca/kategori; audit list, filter UPDATE_SETTINGS, detail18, toggle JSON | PASS untuk skenario tersebut; inbox berisi/read-state dan realtime belum diuji |
| Pembatasan peran/responsif/keyboard | Belum dicoba | NOT RUN |

## Temuan

### B01 — Pengaturan tersimpan tanpa konfirmasi hasil (P2)

- Reproduksi: Master → Pengaturan Sistem → Simpan Pengaturan, tanpa mengubah nilai.
- Actual: tombol kembali normal; tidak ada pesan sukses yang terlihat. Expected: pengguna mendapat konfirmasi bahwa permintaan berhasil atau gagal.
- Diagnosis: `SystemSettingController::update` mengirim flash `success`; middleware membagikannya tetapi `resources/js/Pages/Admin/Settings/Index.tsx` tidak merender flash tersebut. Keyakinan tinggi. Audit18 `UPDATE_SETTINGS` berhasil dibuka melalui UI pada sesi lanjutan dan membuktikan permintaan sebelumnya tersimpan; bukti `evidence/audit-settings.jpg`.
- Bukti: `evidence/settings-save.jpg`. Tidak mengubah nilai pengaturan.

### B02 — Input pengaturan tidak mempunyai nama aksesibel (P2)

- Reproduksi: buka Pengaturan Sistem, periksa accessibility tree. Combobox dan seluruh textbox tidak mempunyai label aksesibel meskipun teks label terlihat.
- Diagnosis: label tidak dihubungkan dengan `htmlFor`/`id`, bukan pembungkus input, dan kontrol tidak memakai `aria-label`/`aria-labelledby` pada `Admin/Settings/Index.tsx`. Keyakinan tinggi.

### B03 — Nama semester kosong pada daftar/dialog rombel (P2)

- Reproduksi: Master → Rombel & Jadwal → Pilih Semester atau Tambah Rombel. Opsi tampil kosong, kecuali `(Aktif)`; kartu menampilkan fallback `Periode Aktif`.
- Diagnosis: frontend membaca `term.full_name`; accessor `Term::getFullNameAttribute` ada tetapi tidak dimasukkan dalam `$appends`/proyeksi JSON. Controller mengirim model langsung. Keyakinan tinggi.
- Bukti: `evidence/classes-semester-labels.jpg`.

### B04 — Membuka roster gagal di backend (P1)

- Reproduksi: Master → Rombel & Jadwal → Roster kelas QA (`/academic/classrooms/25/roster`). Navigasi tidak menyelesaikan halaman roster; UI tetap daftar kelas.
- Diagnosis terkonfirmasi log: `Call to undefined method App\\Models\\User::roles()` di `EnrollmentController::index`. Query guru memakai `whereHas('roles', ...)` dan `roles.name`, sedangkan model menggunakan `userRoles`. Log selanjutnya juga menunjukkan rendering error melampaui batas 30 detik. Keyakinan tinggi.
- Dampak: daftar enrollment, tambah/keluar/transfer siswa dan penetapan wali kelas terblokir dari halaman ini. Klik Mapel kelas 13 juga mereproduksi error yang sama di `ClassSubjectController::index` (query guru menggunakan relasi `roles` yang tidak tersedia); pengelolaan mapel/guru/jadwal terblokir.
- Bukti: `evidence/roster-failure.jpg`; log lokal 2026-11-02 06:51:19 UTC. Tidak menyalin log mentah yang memuat konteks sensitif.

### B05 — Navigasi ke beberapa fitur utama tidak tersedia (P1)

- Beranda Master hanya menyediakan akun, rombel, kebijakan, kenaikan, pengaturan, audit, profil. Tidak ada menu tahun/semester, katalog mata pelajaran, atau master siswa. Halaman modul-modul itu hanya dapat diuji lewat URL route yang diverifikasi dari `routes/web.php`.
- Diagnosis: `Welcome.tsx` menuliskan tujuh tautan tersebut secara statis; belum ada layout/navigasi aplikasi yang menyediakan modul lainnya. Terkonfirmasi juga pada wali.hasan: sesudah login tetap menerima menu admin tanpa tautan portal anak. Klik Pengaturan menghasilkan 403; portal anak hanya dibuka lewat route terdaftar `/children`.

### B06 — Pesan validasi tampil sebagai kunci terjemahan (P2)

- Reproduksi: halaman Tahun Ajaran → Tambah Tahun Ajaran → submit yang ditolak karena tanggal belum diterima form. Actual: `validation.required` tampil, bukan penjelasan berbahasa Indonesia. Expected: pesan field yang dapat dipahami.
- Nilai tanggal kosong terkonfirmasi dari DOM setelah respons; sesudah isian tanggal diikuti keyboard ArrowUp/ArrowDown, tahun dan semester berhasil dibuat. Masalah input date diklasifikasikan sebagai keterbatasan alat, bukan defect aplikasi. Kegagalan lokalisasi pesan tetap nyata: APP_LOCALE dan APP_FALLBACK_LOCALE sama-sama id, sedangkan direktori lang hanya memuat vendor.

### B07 — Tombol Edit siswa tidak menampilkan formulir (P1)

- Reproduksi: Master → Buku Induk Siswa → Edit pada siswa QA20261102. Tidak ada dialog/form edit atau perubahan halaman setelah klik dan observasi ulang.
- Diagnosis: `Students/Index.tsx` memiliki state `editingStudent`, `openEditModal` dan `handleEditSubmit`, tetapi JSX hanya merender modal tambah, tautkan wali dan impor. Tidak ada JSX untuk modal edit. Keyakinan tinggi.
- Bukti: `evidence/student-edit-no-dialog.jpg`. Update biodata/status melalui UI terblokir meskipun endpoint PUT tersedia.

### B08 — Daftar siswa dipaginasi server tetapi tanpa kontrol halaman (P2)

- Reproduksi: Buku Induk menampilkan 15 siswa; tidak ada tombol/tautan halaman berikutnya walau fixture berisi48 siswa + siswa QA.
- Diagnosis: `StudentController::index` memakai `paginate(15)` dan props mendefinisikan `links`, tetapi `Students/Index.tsx` tidak merender pagination. Keyakinan tinggi. Pencarian lewat Enter berhasil, termasuk siswa imporQA; tidak menggantikan kebutuhan kontrol pagination.

### B09 — Skor dengan tiga desimal ditolak tanpa pesan pada UI (P1)

- Reproduksi: penilaianQA7 draft, ubah skor0 menjadi79.995, Simpan Draf Nilai. UI tetap menunjukkan79.995 tanpa pesan presisi; sesudah reload nilai tetap0.00.
- Expected: skor >2 desimal ditolak dengan pesan pada siswa/field yang bermasalah, tanpa menimbulkan kesan tersimpan.
- Diagnosis: controller menghasilkan error `entries.*.score`, tetapi `Assessments/Show.tsx` hanya merender `pageErrors.entries` dan validasi client rentang dengan parseFloat; key error per indeks dan error expected_version tidak ditampilkan. Keyakinan tinggi untuk presisi yang direproduksi; stale-version belum diuji.
- Bukti: `evidence/score-precision-no-error.jpg`. Nol/79,99/100 dan blank sebelumnya tersimpan dan terverifikasi setelah reload. CSV nilai juga berhasil diimpor menggunakan ID dari template yang diunduh.

### B10 — Rapor demo published tidak mempunyai rincian nilai dan PDF kosong (P1)

- Reproduksi: Master → rapor1 published → rincian0 mata pelajaran → Unduh Rapor PDF. Tautan membuka viewer tab baru, menampilkan1 halaman kosong.
- Diagnosis terkonfirmasi: `DemoPortfolioSeeder` menulis PDF literal302byte tanpa content stream; item rapor diambil dari class-subject kelas semester lampau padahal fixture class-subject dibuat untuk kelas aktif. Metadata DB: report1 mempunyai0 item. Ini kegagalan fixture/demo, bukan bukti kegagalan generator PDF sebenarnya.
- Bukti: `evidence/report-pdf-blank.jpg`, salinan berkas backing `evidence/report-1-seeded.pdf` (bukan klaim hasil download). pypdf membaca1page,0 karakter teks,0 content streams dan memberi peringatan startxref salah.
- Dampak: demo rapor resmi tidak menyajikan capaian; finalisasi draft16 ditolak dengan pesan jelas `Rombel belum memiliki mata pelajaran terdaftar`. Lifecycle valid tidak bisa dituntaskan dari fixture ini tanpa menyiapkan sumber akademik lengkap.

### B11 — Render ulang PDF rapor demo gagal karena bentuk snapshot tidak cocok (P1)

- Reproduksi: rapor1 published → Render Ulang PDF. Actual: `Gagal merender berkas PDF: Cannot access offset of type string on string`.
- Diagnosis: fixture menulis `identity_snapshot.classroom` sebagai string `1-A`, sementara `ReportPdfService::prepareViewData` mengharapkan array identitas kelas dengan kunci `name`. Keyakinan tinggi. Ini terjadi pada jalur generator sebenarnya, bukan hanya file placeholder B10.
- Bukti: `evidence/report-render-failure.jpg`. Tidak memperbaiki fixture atau generator selama audit.

### B12 — Jenis kelamin laki-laki ditampilkan Perempuan pada usulan (P2)

- Reproduksi: daftar kenaikan menunjukkan Muhammad Rayhan (NIS20250001) sebagai Perempuan; lembar absensi/nilai dan rapor siswa yang sama menampilkan L/laki-laki.
- Diagnosis: `Progressions/Index.tsx` dan `Show.tsx` membandingkan gender dengan `M`, sedangkan data aplikasi memakai `L`/`P`. Semua nilai `L` jatuh ke cabang Perempuan. Keyakinan tinggi.

### B13 — Persentase kehadiran tampil0% saat data persentase tidak ada (P2)

- Reproduksi: detail usulan4 menampilkan Hadir90, Izin1, Sakit2, Alpa0, namun Persentase0%.
- Diagnosis: fixture attendance_summary hanya mempunyai hitungan, tanpa rate; `Progressions/Show.tsx` merender `rate || '0'`. Expected: hitung rasio bila kontraknya mengizinkan, atau tampilkan Belum tersedia; jangan mengubah data yang tidak ada menjadi0. Keyakinan tinggi.
- Terulang dari akun wali pada rincian rapor1: hadir90/izin1/sakit2/alpa0, persentase0.0% dan Total Pertemuan kosong. `Guardian/Reports/Show.tsx` mengharapkan attendance_rate/total_sessions yang tidak disediakan fixture, lalu memakai fallback0.0.

### B14 — Worker queue tidak dapat beroperasi pada konfigurasi lokal (P1, diagnosis runtime)

- `sia_queue_dev` masih restart berulang dengan `SQLSTATE[HY000] [2002] Connection refused` saat membaca cache queue-restart. Queue/reverb tidak memperoleh override DB container seperti app, sehingga membaca loopback/port host dari .env.
- Metadata koneksi app juga menunjukkan CACHE_STORE=database/QUEUE_CONNECTION=database sementara tabel cache dan jobs tidak ada. Sesudah koneksi worker dibetulkan, tabel infrastruktur ini masih perlu disiapkan lewat perubahan yang ditinjau.
- Notifikasi realtime/pengiriman job tidak dinyatakan lulus. Status unhealthy Reverb saja bukan bukti server WebSocket mati karena healthcheck image dapat mengukur HTTP admin yang tidak dijalankan proses CLI.

### B15 — Cakupan filter mapel tidak konsisten pada portal wali (P2)

- Reproduksi: wali.hasan → anak1 → Rekap per Mata Pelajaran → filter Matematika. Riwayat/Aktivitas Nilai menjadi0, tetapi ringkasan kehadiran tetap10/10 (100%) dan rekap tetap12 mapel termasuk aktivitas Al-Qur'an Hadis. Tidak ada label yang menerangkan bahwa sebagian panel mengabaikan filter.
- Diagnosis: `ChildProgressController` menerapkan subject_id pada query activities, sedangkan subjectSummaries dibangun dari seluruh classSubjects/aktivitas enrollment. Frontend memakai subjectSummaries untuk kartu kehadiran dan rekap, tetapi activities untuk jumlah penilaian. Keyakinan tinggi; scope filter perlu diselaraskan atau dijelaskan pada UI.
- Bukti: `evidence/guardian-filter-mismatch.jpg`. Keadaan kosong riwayat dan pergantian semester historis bekerja.

### B16 — Data Entry menerima direktori seluruh akun melampaui scope terdokumentasi (P1)

- Reproduksi: login quick-demo dataentry → `/admin/users`. Semua akun tampil, termasuk Master/Akademik/Principal, dengan username/email/role/status aktif/status sandi. Ini akses baca; tidak mencoba mutasi akun.
- Diagnosis: `UserPolicy::viewAny` memasukkan DATA_ENTRY_ADMIN; `UserController::index` mengambil semua User tanpa pembatasan objek atau proyeksi per peran. Matriks izin hanya memberikan direktori profesional guru seperlunya dan belum memberi grant akun admin lain/role kepada Data Entry. Ada konflik implementasi terhadap kebutuhan yang ditemukan; detail matriks masih berlabel blueprint. Keyakinan tinggi, ditinjau independen dari source/PRD. Bukan klaim eskalasi mutasi.
- Bukti: `evidence/dataentry-users-directory.jpg`, urutan login browser. Semua data yang diperiksa berasal dari fixture demo sintetis.

### B17 — Kontrol mutasi akun ditampilkan pada peran tanpa hak mutasi (P2)

- Reproduksi pada dataentry: tombol Tambah Akun Baru dan Nonaktifkan tetap tampil. Filter Guru bekerja sesudah respons server, tetapi tombol tetap ada.
- Diagnosis: JSX Users/Index merender Tambah tanpa capability dan Nonaktifkan hanya menurut is_active. Backend create/manageRoles/delete memanggil authorize dan policy Master-only; penolakan mutasi belum diuji runtime. Ini defect kontrol UI, bukan bukti bahwa server mengizinkan perubahan.

## Data QA dan perubahan selama pengujian

- Rombel `Kelas 1 QA-20261102`, ID 25, semester aktif. Pembuatan berhasil melalui UI dan kartu muncul setelah submit. Tidak dihapus selama audit.
- Komponen `qa_20261102` / `QA Komponen 20261102`, berhasil dibuat lalu dinonaktifkan melalui UI. Komponen aktif yang sudah ada tidak diubah.
- Draf kebijakan revisi 2, KKM70, bobot 21.50/22.50/22.50/22.50/10 (99%). Tidak diaktifkan; kebijakan aktif revisi 1 dipertahankan.
- Tahun ajaran2029/2030 dan semester1 draft; periode aktif2026/2027 tetap.
- Mapel QA1102 dibuat, diedit menjadi `QA Mapel 20261102 Disunting`, lalu dinonaktifkan.
- Siswa QA20261102 dan QAIMPORT1102 ditambahkan tanpa wali; CSV invalid tidak diimpor.
- Sesi absensi10 `QA Presensi 20261102` finalized dengan masing-masing1 Hadir/Izin/Sakit/Alpa. Data ini sekarang masuk rekap fixture aktif.
- PenilaianUTS7 `QA UTS 20261102` published: skor0/79.99/100/blank, beserta catatan sintetis. Publikasi ini sekarang terlihat bagi wali terkait.
- Rapor draft16 catatan ditambah QA; satu ekstrakurikuler dan satu P5 sintetis ditambahkan. Tetap draft; finalisasi incomplete ditolak.
- Usulan4 catatan ditambah QA, versi2, tetap draft menurut metadata DB sesudah pengajuan tertahan dialog. Batch rombel historis tidak membuat usulan baru (0 dibuat/0 dilewati karena tidak ada enrollment aktif pada kelas yang dipilih).
- Profil wali.hasan alamat diubah menjadi `Alamat sintetis QA 20261102`; tersimpan sesudah reload. Satu notifikasi fixture milik wali ditandai dibaca dan status bertahan sesudah reload.
- Guru membuat penilaian8 `QA Guru 20261102`, mapel Akidah Akhlak/class-subject2, skor pertama55.00 tersimpan sesudah reload; tetap draft.
- Akademik membuat sesi11 `QA Delegasi 20261102`, class-subject2; tetap draft tanpa catatan. Proposal grant dataentry sampai2026-11-03 disiapkan lalu ditutup; konfirmasi pengguna diminta sebelum grant/revoke dan hapus dua draft QA8/11. Belum ada grant atau penghapusan.
- Tidak menghapus data atau mengubah source aplikasi, migrasi, password, role, grant/delegasi, publikasi rapor atau enrollment siswa.

## Review cakupan independen (source saja)

- Subagen QA read-only meninjau route dan laporan, tanpa browser/API/PHPUnit atau perubahan file. Review diminta standar testing proyek. Hasilnya daftar coverage gap, bukan hasil tes tambahan.
- Teacher/guardian/dataentry/principal diarahkan ke beranda yang sama dari source; portal children tidak ditautkan. Positif/negatif setiap peran masih perlu browser.
- Kandidat source yang belum direproduksi: UI grant delegasi hanya membuat capability attendance_entry; policy nilai membutuhkan grade_entry. Belum dihitung sebagai defect runtime terkonfirmasi.
- Endpoint PATCH metadata absensi dan penilaian tersedia di backend, tetapi pencarian frontend tidak menemukan pemanggil/form edit metadata. Ini gap UI source-only, bukan hasil uji endpoint.
- Hapus draft absensi/penilaian tersedia di halaman daftar, dengan syarat status/peran. Tidak ada tombol tersebut pada detail yang sudah finalized/published merupakan perilaku sesuai kondisi source; operasi hapus belum dijalankan.
- Profil reguler hanya mendukung nama, telepon, alamat. Setup/pemulihan password adalah alur terpisah; jangan menganggap ubah password tersedia dari profil.
- Review akhir memeriksa konsistensi inventaris/laporan; label pengajuan, submit profil tanpa perubahan, PATCH metadata dan DELETE draft diperjelas. Tidak menambah bukti PASS runtime.

## Batasan dan gangguan alat

- Sesi pertama dihentikan alat computer-use karena URL Chrome Windows tidak dapat diverifikasi dengan cukup yakin. Sebelumnya, JavaScript alert aktivasi99% memblokir tab IAB; `getJsDialog` tidak mengembalikan handle. Ini gangguan alat, bukan bukti bahwa aktivasi99% berhasil/gagal di backend. User meminta lanjut, dan sesi browser baru berhasil dibuka.
- Sesi lanjutan tertahan kembali pada native confirm `Ajukan usulan ini ke Kepala Madrasah untuk ditinjau?`. Escape, getJsDialog dan close melalui API tidak berhasil. Permintaan menutup/membatalkan dialog dikirim ke pengguna; pemeriksaan peran belum dilanjutkan sebelum input browser pulih. Jangan mencatat pengajuan sebagai PASS.
- Unduh rapor membuka PDF inline di tab7, bukan event download; viewer dan backing file diperiksa. Upaya download event tidak menghasilkan berkas dan tidak dinyatakan PASS.

- Bila alur setup/pemulihan mencapai entri password baru, kebijakan computer-use mewajibkan handoff pengguna. Penghapusan data dan perubahan akses sensitif memerlukan konfirmasi sesuai tindakan; belum dilakukan.
- Pengujian otomatis/CI, load, produksi, email nyata, dan semua kombinasi data tidak termasuk bukti browser ini.
