# Kajian pembangunan ulang SIA MI Alghivari

Tanggal: 28 Oktober 2026. Status: **proposal, belum disetujui dan belum diimplementasikan**.

Catatan lanjutan: dokumen ini adalah kajian awal historis. Kebutuhan terbaru pemilik, termasuk tiga admin, kepala sekolah, audit setiap CUD, notifikasi, rapor dan naik/lulus, menjadi acuan di [PRD](PRD.md) dan [matriks izin](ROLE_PERMISSION_MATRIX.md). Bagian lingkup/peran dalam kajian awal yang bertentangan telah digantikan. Pemilik meminta memantapkan blueprint sebelum implementasi.

## Landasan dan batas bukti

Sumber: [laporan kerja praktik](support/KP%20FARHAN%201%20revisi%20bab%202%20acc.pdf), 77 halaman PDF. Teks seluruh halaman dibaca; ERD, relasi tabel, diagram proses, dan wireframe utama diperiksa visual. Nomor halaman di bawah mengikuti nomor tercetak, bukan urutan halaman PDF.

Pemilik mengonfirmasi pembangunan dari nol. Workspace berisi laporan dan dokumen framework; belum ditemukan source aplikasi, konfigurasi dependensi, dump SQL, atau Git repository pada root proyek. PRD dan TECH_STACK masih template. Cuplikan aplikasi dalam laporan adalah referensi historis.

Tidak ada pengujian aplikasi, benchmark, migration, provisioning, atau deployment. Usulan berikut belum menjadi keputusan pemilik. Temuan historis juga belum membuktikan kebutuhan sekolah pada 2026.

## Tanggapan terhadap laporan

Landasan produknya kuat: guru mencatat nilai/kehadiran melalui buku lalu Excel, sedangkan keluarga baru mengetahui perkembangan saat pembagian rapor. Wawancara dan observasi Bab III memberi konteks nyata untuk portofolio.

| Temuan | Bukti laporan | Arah pembangunan ulang |
| --- | --- | --- |
| Batasan menyebut dua level akses, tetapi kebutuhan dan diagram juga memuat admin | Bagian 1.3, hlm. 2; 4.1.1, hlm. 20-21; Gambar 4.4 | Konsistenkan aktor dan izin. |
| Orang tua merupakan penerima manfaat, tetapi belum mempunyai akun/relasi anak sendiri | Bagian 3.2; ERD hlm. 23 | Portal wali dengan relasi anak yang diverifikasi admin. |
| Latar belakang membahas pengiriman tugas melalui pesan grup, tetapi lingkup akhir berfokus pada nilai/absensi | Bagian 1.1 dan 1.3 | Bedakan pencatatan nilai dari LMS/pengumpulan tugas. |
| Absensi P1-P14 dan nilai T1-T14, UTS, UAS | Tabel 4.12-4.13, hlm. 31 | Pertemuan dan penilaian menjadi baris, tanpa batas 14. |
| Akun guru/siswa hampir sama tetapi terpisah | Tabel 4.3-4.4, hlm. 28 | Satu sumber akun, profil domain terpisah. |
| Keanggotaan kelas tidak mempunyai periode eksplisit; referensi semester dalam kode tidak konsisten dengan tabel | Tabel 4.7-4.11; Bab V | Semester dan riwayat rombel yang jelas. |
| Master mapel bercampur dengan guru, kelas, dan jadwal berupa teks | Tabel 4.10, hlm. 30 | Pisahkan mapel, penugasan mengajar, slot jadwal. |
| Angka 75 menjadi ambang langsung dalam kode | Bagian 5.2.6, hlm. 58-60 | Ambang perhatian menjadi pengaturan, bukan aturan universal. |
| Cuplikan edit memasukkan request langsung ke SQL dan mengambil objek melalui ID | Bagian 5.2.5 dan 5.2.7 | Query terparameterisasi, validasi, dan izin terhadap objek. Tidak cukup bukti untuk menyimpulkan semua perilaku aplikasi lama. |
| Penamaan field dan definisi password tidak konsisten di semua tabel/gambar | Tabel 4.1-4.4 dan Gambar 4.2 | Migration menjadi sumber skema baru; tipe kolom saja tidak membuktikan algoritma password. |
| Bab VI mengakui kekurangan UX, navigasi, database, dan query | Bagian 6.2, hlm. 66 | Prioritaskan kemudahan kerja dan integritas data. |
| Tujuan mencakup pengujian, tetapi tidak ditemukan matriks kasus dan hasil pengujian terukur | Bagian 1.4 dibandingkan Bab V-VI | Tampilkan bukti tes; jangan mengklaim dampak tanpa pengukuran. |

Saran laporan untuk menambah trigger tidak perlu diikuti otomatis. Aturan bisnis dan transaksi aplikasi lebih mudah ditelusuri untuk skala ini, dengan foreign key, unique, dan check constraint untuk integritas yang cocok ditegakkan database.

## Fokus produk

**Sistem akademik satu madrasah untuk membantu guru mencatat pembelajaran dan wali murid mengikuti perkembangan anak.** Target awal adalah demo portofolio dengan data sintetis. Kesiapan penggunaan sekolah nyata adalah tahap tersendiri.

Usulan aktor:

- Admin: akun, relasi wali-anak, periode, siswa, guru, rombel, mapel, dan penugasan. Koreksi akademik memerlukan alasan dan audit.
- Guru: nilai/kehadiran sesuai penugasan. Wali kelas ditentukan oleh rombel yang dibimbing, tanpa role terpisah.
- Wali murid: anak yang dihubungkan admin, jadwal, absensi disahkan, dan nilai dipublikasikan. Tidak melihat siswa lain.

Akun siswa dapat ditambah kemudian. Mendahulukan wali merupakan usulan perubahan lingkup untuk konteks MI, bukan kebutuhan yang sudah disetujui. Seorang guru dapat sekaligus wali melalui role tetap dan relasi anak, tanpa akun ganda. Role kepala sekolah dan permission builder belum diperlukan.

## Versi pertama

| Kemampuan | Hasil utuh |
| --- | --- |
| Akun | Dibuat admin, tanpa registrasi publik; sesi login, ubah password, nonaktifkan akun, pemulihan melalui admin terverifikasi dan tercatat. |
| Periode dan master | Tahun ajaran, semester, rombel, anggota, wali kelas, mapel, penugasan; semester baru tidak menimpa histori. |
| Jadwal | Hari/waktu terstruktur, filter guru/kelas, penolakan benturan; tanpa penjadwalan otomatis. |
| Kehadiran | Pertemuan bertanggal, input satu daftar kelas, draft lalu disahkan. Belum dicatat tidak dianggap alpa. |
| Penilaian | Judul, jenis tugas/UTS/UAS, skor maksimum, input satu kelas, validasi, publikasi; tidak dibatasi 14. |
| Portal wali | Pilih anak, rincian nilai terpublikasi, absensi final, jadwal. Relasi anak tidak didaftarkan sendiri. |
| Ringkasan semester | Rekap nilai/kehadiran, cetak atau simpan PDF lewat browser. Belum mengklaim e-Rapor resmi. |
| Audit | Aktor/waktu/target/alasan koreksi penting. Tidak dapat mengedit nilai terpublikasi atau semester tertutup diam-diam. |

Alur demo: admin menyiapkan semester/rombel -> guru membuat pertemuan dan penilaian -> guru mengesahkan absensi dan memublikasikan nilai -> wali melihat anaknya -> semester baru dibuat tanpa menghapus histori.

## Pengembangan setelah inti selesai

1. Impor CSV dengan pratinjau, laporan kesalahan, pemeriksaan duplikasi, dan transaksi. Mulai dari siswa, lalu nilai.
2. Penanda tindak lanjut berdasarkan ambang nilai/kehadiran yang dikonfigurasi. Tampilkan alasan dan data dasarnya; tanpa diagnosis atau pelabelan kemampuan anak.
3. Catatan perkembangan guru untuk anak tertentu, dengan pilihan internal atau dibagikan kepada wali dan aturan izin yang jelas.
4. Snapshot ringkasan semester beserta versi aturan hitung agar hasil final dapat dicetak ulang identik.
5. Pengumuman per kelas di dalam aplikasi.

Pengumpulan tugas/lampiran menjadi pilihan pengembangan jika dipilih sebagai fokus: fitur tersebut menambah penyimpanan, tenggat, validasi unggahan, dan aturan akses.

Di luar lingkup awal: SPP/payment gateway, PPDB, inventaris, perpustakaan, biometrik/GPS, chat langsung, AI, mobile terpisah, banyak sekolah, WhatsApp, serta integrasi sistem pemerintah.

## Pengalaman pengguna

- Beranda guru: jadwal hari ini, absensi belum disahkan, penilaian belum lengkap; semua berasal dari data tersimpan.
- Alur kerja dari rombel/mapel menuju pertemuan/penilaian; input satu kelas, bukan form terpisah setiap siswa dan 14 pertemuan sekaligus.
- Portal wali mengutamakan anak terpilih, informasi terakhir, dan rincian di ponsel. Tabel lebar untuk rekap desktop; daftar per tanggal/penilaian untuk ponsel.
- Label Indonesia, navigasi keyboard, kesalahan dekat isian, dan keterangan selain warna.
- Bedakan kosong, belum diisi, draft, terpublikasi, gagal simpan, dan akses ditolak. Kegagalan tidak menampilkan sukses atau menghilangkan isian.
- Hijau pada laporan dapat menjadi referensi; branding final belum dipilih.

## Teknologi dan struktur yang diusulkan

Pilihan utama: **Laravel 13 + Blade/Livewire + Tailwind + satu MySQL**. Satu aplikasi menangani UI, autentikasi, aturan bisnis, dan data. Gunakan struktur framework biasa, policy izin objek, validasi, dan transaksi. Tambahkan action/service kecil saat ada alur bersama yang nyata, tanpa repository abstrak bagi setiap model.

Laravel 13 memerlukan PHP minimal 8.3; patch dan versi PHP dipastikan saat implementasi. Starter kit Livewire menyediakan titik awal UI/auth. Nonaktifkan registrasi publik dan teams yang tidak dibutuhkan. Rujukan resmi: [rilis Laravel](https://laravel.com/framework/docs/releases), [starter kits](https://laravel.com/framework/docs/starter-kits), [authorization](https://laravel.com/framework/docs/authorization).

MySQL cocok untuk relasi akademik, transaksi, constraint, dan laporan. PostgreSQL juga layak jika cocok dengan lingkungan pemilik; belum ada kebutuhan yang mengharuskan pindah engine. SQLite lebih ringan untuk lokal, tetapi satu MySQL sejak pengembangan dapat mengurangi perbedaan perilaku dengan target hosting. Tidak ada klaim benchmark atau harga hosting.

Belum diperlukan frontend/backend terpisah, microservices, Redis, event bus, atau WebSocket. Cetak PDF awal dapat memakai print stylesheet dan fasilitas browser.

## Data dan keamanan

Skema usulan: [DATA_MODEL.md](DATA_MODEL.md). Prinsip utamanya akun tunggal, wali-anak, periode eksplisit, pemisahan mapel/penugasan, dan absensi/nilai berbentuk baris.

Izin harus berlaku pada baca, tulis, URL langsung, cetak, dan ekspor. Penugasan guru bukan izin seluruh sekolah; relasi wali bukan izin seluruh rombel. Gunakan hash password framework dengan kolom 255 karakter, sesuai [manual PHP](https://www.php.net/manual/en/function.password-hash.php).

Jangan menyalin identitas siswa atau kredensial dari tangkapan layar laporan ke seed atau demo. NIK, alamat lengkap, agama, dan berkas identitas tidak dikumpulkan tanpa kebutuhan. Pastikan izin pemakaian nama/logo sekolah untuk portofolio publik dan jangan menyiratkan adopsi resmi.

Asumsi kajian awal Asia/Jayapura digantikan D-29 (30 Oktober 2026): satu school_timezone dinamis Master dengan pilihan WIB/WITA/WIT; nilai awal dipilih saat setup, bukan lokasi laporan/perangkat. Tanggal akademik mengikuti kalender sekolah, bukan zona komputer pengembang; timestamp kejadian dapat disimpan UTC.

## Tahapan dan bukti selesai

| Tahap | Hasil | Penerimaan saat implementasi |
| --- | --- | --- |
| Fondasi | Akun/izin, master, semester, rombel, penugasan | Guru/wali tidak mengakses kelas/anak lain; satu siswa satu rombel per semester. |
| Operasional | Jadwal, absensi, penilaian | Input kelompok atomik; skor invalid ditolak; kosong bukan nol/alpa; benturan ditolak. |
| Hasil keluarga | Portal, publikasi, cetak | Draft tidak bocor; perhitungan diuji; semester baru mempertahankan histori. |
| Portofolio | Seed sintetis, dokumentasi, demo dan satu fitur lanjutan | Alur admin-guru-wali berfungsi; error/kosong dan ponsel/desktop diverifikasi; bukti tes nyata. |

Tes tambahan: akun nonaktif, relasi wali dicabut, ID dimanipulasi, tanggal di luar semester, duplikasi, edit bersamaan, koreksi nilai, semester tertutup. Seluruh tes aplikasi **NOT RUN**, karena implementasi belum ada.

Narasi portofolio: temuan KP -> batasan rancangan lama -> keputusan penyederhanaan -> alur yang selesai -> bukti pengujian. Dampak efisiensi dan kepuasan harus diukur, bukan disimpulkan dari screenshot.

## Keputusan yang masih terbuka

- Persetujuan fokus satu sekolah dan portal wali dibanding akun siswa.
- Stack sesuai kemampuan, sasaran pekerjaan, dan hosting pemilik.
- Absensi per mapel seperti laporan atau harian kelas; skema usulan mengikuti per mapel. Rekapnya menghitung pertemuan, bukan hari sekolah.
- Rumus nilai, ambang perhatian, nilai kosong, remedial, serta format ringkasan; tidak mengklaim kepatuhan kurikulum/rapor tertentu.
- Perpindahan rombel tengah semester dan pergantian guru belum dicakup lengkap.

Proposal ini tidak mengotorisasi deployment, pemakaian data sekolah sungguhan, atau notifikasi nyata.
