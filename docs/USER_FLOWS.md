# Alur pengguna dan rancangan navigasi

Tanggal pembaruan: 29 Oktober 2026. Status: blueprint UX aplikasi inti. [Prototipe facelift-v2](support/facelift-v2/README.md) dan evidence frontend tersedia sebagai **referensi desain, bukan panduan**; state simulasi tidak membuktikan server/policy/konflik jaringan. Acuan perilaku tetap [PRD](PRD.md), [matriks izin](ROLE_PERMISSION_MATRIX.md) dan API. Pengujian aplikasi inti NOT RUN.

## Navigasi dan konteks

Satu akun dan satu login. Pengguna peran rangkap memilih konteks kerja, misalnya Administrasi atau Mengajar; konteks hanya mengubah navigasi, bukan hak server. Tahun ajaran, semester dan kelas/mapel aktif tampil jelas. Kembali dari detail mengembalikan filter daftar sebelumnya.

| Area | Fokus layar |
| --- | --- |
| Beranda | Tugas nyata yang tersedia bagi pengguna: jadwal, input belum lengkap, rapor siap terbit, usulan menunggu keputusan; tidak ada angka contoh yang disajikan sebagai data nyata. |
| Administrasi | Profil sekolah/konfigurasi khusus Master; akun/role sesuai izin; direktori siswa, wali, guru dengan field yang dibatasi. |
| Akademik | Periode, kelas, mapel, jadwal, penugasan serta satu halaman Konfigurasi Penilaian global untuk KKM/katalog komponen/bobot. Data Entry melihat aksi input yang dibolehkan, bukan kontrol konfigurasi. |
| Mengajar | Kelas/mapel ditugaskan -> pertemuan atau penilaian -> input daftar siswa -> validasi/simpan/publikasi. |
| Wali kelas | Siswa binaan, rekap lintas mapel, kelengkapan dan publikasi rapor; tidak ada edit nilai mapel lain dari penugasan wali kelas saja. |
| Kepala sekolah | Ringkasan/baca akademik dan antrean keputusan naik/lulus. Jika juga guru, konteks Mengajar terpisah. |
| Portal wali | Pilih anak -> dua menu terpisah: Perkembangan Belajar untuk aktivitas nilai/kehadiran berjalan, dan Rapor Online untuk dokumen semester final yang dipublikasikan. |
| Aktivitas | Audit terfilter menurut izin, inbox notifikasi sendiri, detail sumber yang masih boleh dibaca. |
| Profil | Username, kontak/alamat, foto dan password sendiri. Tidak ada field role, penugasan atau relasi anak. |

## Alur inti

| ID | Pengguna dan tujuan | Langkah utama | Selesai dan cabang gagal |
| --- | --- | --- | --- |
| J-01 | Master menyiapkan akses | Akun/role/email langsung verified administratif -> sistem password sementara -> email URL biasa/username/password -> login terbatas -> password baru/konfirmasi -> commit -> modal wajib -> aplikasi | Master-only; Q6.1 M dahulu/D biodata/link tetap, existing tambah role tidak reset. Email gagal status teknis aman/reissue terbatas, tanpa credential-read API. |
| J-02 | Akademik menyiapkan pembelajaran | Tahun/semester -> kelas/anggota -> mapel/pengajar/wali kelas -> jadwal; konfigurasi pusat KKM/komponen/bobot -> tinjau total -> aktifkan revisi global | Konflik jadwal, duplikasi anggota dan total bobot bukan 100 ditolak. Tidak ada konfigurasi per mapel. |
| J-03 | Data Entry membantu input | Pilih tugas/delegasi aktif -> form siswa/wali atau daftar nilai/absensi -> validasi -> simpan | Tidak ada tombol hapus/publish dari role ini; delegasi berakhir saat simpan menghasilkan penolakan tanpa kehilangan konteks. |
| J-04 | Guru mencatat absensi | Penugasan mapel -> buka sesi bertanggal -> daftar status siswa -> draft -> sahkan | Status belum diisi bukan alpa; hanya sesi finalized masuk jumlah pertemuan. Kehadiran tidak diinput sebagai nilai manual. |
| J-05 | Guru menilai | Pilih komponen input dari katalog pusat -> buat penilaian -> input satu kelas -> draft -> tinjau -> publish | Guru tidak membuat komponen/bobot sendiri. Blank dilewati dengan label Belum ada nilai, 0 dihitung; konflik versi tidak menimpa data pengguna lain. |
| J-06 | Wali kelas memublikasikan rapor | Kelas binaan -> periksa ketersediaan komponen berbobot positif -> preview versi global/per siswa -> catatan wali kelas opsional pada draft binaan -> finalisasi/publish | Blank sebagian boleh dilewati; seluruh komponen blank berbobot positif menghalangi finalisasi. Tidak mengedit nilai guru lain. Master/Akademik juga memiliki alur ini. |
| J-07 | Wali memantau perkembangan | Pilih anak -> Perkembangan Belajar -> filter mapel/komponen/tanggal -> nilai yang dibagikan dan kehadiran yang disahkan | Bisa dibuka sepanjang periode tanpa menunggu rapor. Blank ditandai; rekap diberi label sementara; draft internal tidak bocor. |
| J-07B | Wali menerima rapor online | Pilih anak -> Rapor Online -> pilih semester/versi published -> lihat/unduh | Hanya setelah seluruh rekap difinalisasi lalu rapor dipublikasikan. Jika belum tersedia, jelaskan bahwa Perkembangan Belajar tetap dapat dibuka. |
| J-08 | Kepala sekolah memutuskan | Antrean usulan -> periksa siswa/rapor/tujuan -> pilih individual atau batch -> konfirmasi keputusan | Approval mencatat principal dan versi; sumber berubah meminta tinjau ulang; tidak langsung mengubah kelas/status sebelum apply. |
| J-09 | Admin pelaksana menerapkan | Pilih keputusan approved -> preview tahun/kelas tujuan -> terapkan | Usulan mekanisme: Master/Akademik; cegah apply ganda dan tolak batch invalid seluruhnya; histori lama tetap. |
| J-10 | Pengguna melihat aktivitas | Inbox/audit terfilter -> pilih item -> detail yang diizinkan | Menandai dibaca tidak membuat notifikasi baru; hasil revoke/arsip menampilkan pesan netral. |
| J-11 | Semua role profil/reset password | Profil: sesi+OTP email -> password/konfirmasi -> commit -> modal -> profil, current tetap login. Reset: username/email -> OTP -> password -> modal -> login biasa | Yes profil logout sesi lain, Yes reset semua sesi login; No mempertahankan. OTP/proof/race/pending/secret mengikuti D25, tidak auto-login reset atau bypass target. |

## Perilaku antarmuka

Form nilai/absensi berfokus pada satu penilaian atau satu pertemuan untuk satu kelas. Desktop dapat memakai grid dengan label tetap; ponsel memakai daftar siswa/isian yang tidak menuntut menggulir 14 kolom. Pembaruan kelompok memakai preview dampak; jumlah hanya scope yang boleh dilihat pengguna.

Semua layar relevan mempunyai loading, kosong, error, validasi, sukses, akses ditolak dan konflik edit. Tombol proses dinonaktifkan selama request, tetapi server tetap mencegah duplikasi. Simpan gagal mempertahankan isian yang aman; token/password tidak disimpan ulang di form.

Label berbahasa Indonesia, urutan fokus keyboard jelas, informasi selain warna, judul periode konsisten, dan pesan kesalahan spesifik. Konfirmasi diperlukan untuk tindakan berdampak seperti publish, pembatalan dan apply batch; tidak untuk setiap simpan rutin.

Guru melihat total nilai, jumlah penilaian numerik dan jumlah blank sehingga pembagi rata-rata dapat dipahami. Preview menampilkan versi KKM/bobot global; perubahan versi sebelum finalisasi meminta tinjau ulang. Sesi kosong tampil Belum tersedia, bukan hadir 0% otomatis.

Susunan rapor ringkas Q7.1 dan rincian hitung di aplikasi ada pada [alur akademik](ACADEMIC_FLOW.md); format ringkas disepakati 29 Oktober 2026. Angka Q7, desain visual, warna/branding, wireframe, font/asset dan pagination belum disetujui menyeluruh atau diuji. Dokumen ini tidak menjadi bukti responsive/accessibility telah diuji.

D-28: koreksi published/finalized -> review sebelum/usulan + reason -> commit sumber/audit/inbox/hold per siswa -> Perkembangan Belajar terbaru; rapor -> draft revisi policy/calculation asli -> preview/completeness -> finalized -> PDF ready -> publish satu versi aktif. MA/W binaan dapat revoke beralasan tanpa pengganti; closed hanya MA mengoreksi sumber. Lihat [alur rinci](ACADEMIC_FLOW.md); applied progression tidak dibalik otomatis.
