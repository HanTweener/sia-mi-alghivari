# Matriks peran dan hak akses

Diperbarui: 29 Oktober 2026. Status: blueprint; Q1-Q5 untuk aturan inti dikonfirmasi pemilik pada [PRD](PRD.md). Detail usulan belum final. Belum ada policy yang diimplementasikan; pemilik meminta blueprint dahulu.

## Cara membaca

C = tambah, R = baca, U = ubah, D = hapus domain sesuai integritas (arsip/pembatalan jika sudah memiliki histori). P = finalisasi/publikasi, A = persetujuan, T = terapkan hasil. Tanda '-' berarti tidak ada grant dari peran tersebut. Izin khusus A/P/T berbeda dari CRUD.

Peran akun: `master_admin`, `academic_admin`, `data_entry_admin`, `teacher`, `principal`, `guardian`. Penugasan guru mapel/wali kelas mempunyai cakupan kelas/periode. Satu akun boleh beberapa peran. Tabel menunjukkan hak yang diberikan tiap peran, bukan larangan global yang mematikan peran lain.

D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

| Sumber daya/tindakan | Master | Akademik | Data Entry | Guru mapel | Wali kelas | Kepala sekolah | Wali murid |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Administrasi konfigurasi sistem | CRUD | - | - | - | - | - | - |
| Tahun ajaran/semester | CRUD | CRUD | R konteks kerja | R penugasan | R binaan | R | R konteks anak |
| Kelas/mapel | CRUD | CRUD | CRU, tanpa D | R penugasan | R binaan | R | R konteks anak |
| Jadwal | CRUD | CRUD | R konteks kerja | R penugasan | R binaan | R | R anak |
| KKM global, scoring_components dan bobot total termasuk kehadiran | CRUD | CRUD | R untuk input yang ditugaskan | R, tanpa override | R, tanpa override | R | R yang tercantum pada hasil anak |
| Penugasan guru/wali kelas | CRUD | CRUD | CRU usulan; aktivasi hak oleh Akademik/Master | R penugasan | R binaan | R | R identitas profesional wali kelas |
| Buat akun/email verified administratif/password sementara (D-25) | Buat akun/konfigurasi; trigger email akses awal, tanpa membaca kembali password | - | - | - | - | - | - |
| Password pertama/reset/profil D-25 | Diri sendiri sesuai flow/proof | Diri sendiri | Diri sendiri | Diri sendiri | Diri sendiri | Diri sendiri | Diri sendiri, tanpa akses anak sebelum relasi sah |
| Role akun/jabatan kepala sekolah | CRUD | R direktori profesional | - | - | - | R direktori yang diizinkan | - |
| Biodata siswa/wali dan hubungan anak | CRUD | CRUD domain akademik | CRU siswa/wali, tanpa D | R minimum siswa diajar | R minimum siswa binaan | R | R diri/anak terkait |
| Biodata guru | CRUD | CRU biodata profesional; usulan batas, tanpa perubahan role | R direktori profesional seperlunya | R diri | R diri | R | R wali kelas anak, field profesional |
| Biodata/akun admin lain | CRUD domain, tanpa membaca password | Belum ada grant untuk pengelolaan akun admin lain | Belum ada grant | - | - | R direktori profesional, bukan kredensial | - |
| Penilaian/absensi | CRUD draft/P; koreksi D-28 | CRUD draft/P; koreksi D-28 | CRU draft dalam delegasi aktif; tanpa D/P/koreksi final | CRUD/P parent efektif; koreksi published/final hanya term terbuka | R siswa binaan | R | R anak, published/finalized saja |
| Perkembangan Belajar berjalan | R | R domain akademik | R sesuai pekerjaan/delegasi | R siswa diajar | R binaan | R | R aktivitas anak yang sudah dibagikan/disahkan, tanpa menunggu rapor final |
| Catatan wali kelas pada rapor (Q7.1) | RU hanya draft; histori final tetap | R sesuai hak rapor; tanpa grant tulis baru | R sesuai pekerjaan; tanpa tulis | R hanya bila proyeksi rapor mengizinkan; tanpa tulis | RU draft binaan aktif; final hanya R | R | R hanya published/current aktif anak |
| Finalisasi/publikasi rapor gabungan | P/revisi bina domain | P/revisi domain | R sesuai pekerjaan | R hasil mapel/kelas diajar | R/P/revisi hanya binaan aktif setelah lengkap | R | R/unduh hanya published/current aktif anak |
| Koreksi hasil sah D-28 | Nilai/status absensi; termasuk term closed | Sama dalam domain akademik | - | Parent efektif, term terbuka saja | - dari tugas wali kelas | - dari jabatan ini | - |
| Cabut rapor D-28 | Command beralasan | Command beralasan | - | - dari tugas mapel | Command binaan aktif beralasan | - dari jabatan ini | - |
| Susun usulan naik/lulus | CRU usulan | CRU usulan (rekomendasi pelaksana) | R konteks input, tidak memutuskan | R siswa diajar | R binaan | R | R keputusan anak yang sudah berlaku |
| Approve/reject naik/lulus | Hanya bila juga principal | Hanya bila juga principal | - | Hanya bila juga principal | Hanya bila juga principal | A, pengecualian READ-only yang dikonfirmasi pemilik | - |
| Terapkan keputusan yang disetujui | T (usulan pelaksana) | T (usulan pelaksana) | - | - | - | R, tidak menerapkan dari jabatan ini | R hasil anak |
| Audit log | R seluruh domain, tanpa CUD | R lingkup akademik | R lingkup pekerjaan/delegasi | R penugasan, field terbatas | R binaan, field terbatas | R domain yang boleh dibaca | R diri/anak, event yang boleh dilihat |
| Notifikasi | R/U read-state milik sendiri | Sama | Sama | Sama | Sama | Sama | Sama |
| Profil sendiri | RU field diizinkan | Sama | Sama | Sama | Sama | Sama | Sama |

Batas biodata guru/admin, proses aktivasi input penugasan Data Entry dan pelaksana kenaikan adalah usulan yang belum disepakati secara rinci; jangan menganggap matriks ini persetujuan akses luas ke akun staf. Izin yang belum eksplisit tidak diaktifkan saat implementasi. Kewenangan Master atas role/jabatan sudah dikonfirmasi.

## Keputusan yang telah dikonfirmasi

- Q1: kepala sekolah boleh approval naik kelas/lulus; akses lain dari jabatan kepala sekolah tetap baca. Hak mengajar dari penugasan mapel tetap berlaku.
- Q2: Data Entry boleh menambah/memperbarui data operasional yang disebut pemilik, tanpa hapus dan konfigurasi akademik. Nilai/absensi pengganti guru memerlukan penugasan khusus yang tercatat.
- Q3: wali kelas juga boleh memublikasikan rapor kelas binaannya, tanpa hak mengedit nilai guru mapel lain.
- Q7.1: susunan rapor ringkas disepakati 29 Oktober 2026; wali kelas boleh mengisi catatan opsional draft binaan, lalu dibekukan pada snapshot final. Ini tidak memberi hak mengubah skor guru lain atau role lain di luar grant yang tercantum. Aturan angka Q7.2-Q7.5 telah diterima pada D-19..22; detail visual/render belum final dan seluruh pengujian implementasi NOT RUN.
- Q4: hanya Master memberikan/mengubah role dan mengangkat kepala sekolah; Akademik mengelola penugasan wali kelas dan guru mapel.
- Q5: absensi per mapel; KKM dan bobot global seluruh mapel dikelola Master/Akademik, tanpa override guru. Kehadiran ikut bobot total 100%; blank dilewati dan 0 dihitung; komponen berbobot positif tanpa data mencegah finalisasi.
- Q6.1 tetap Master akun wali/role/aktivasi dahulu, D melengkapi biodata/hubungan berizin. D-25 berlaku seluruh akun baru: Master verified email administratif/password sementara emailed/forced change/modal; reset/profil OTP email. D tidak membuat credential/role akun lain; hak mandiri tetap. Akun existing tambah role tidak direset. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).
- Q6.3, 29 Oktober 2026: Master dan Admin Akademik boleh memberikan dan mencabut delegasi input nilai/kehadiran kepada Data Entry. Ini bukan persetujuan mekanisme aktivasi usulan penugasan guru Data Entry atau perubahan batas C/U draft.
- Semua pengguna dapat memperbarui profil sendiri, sebagai pengecualian eksplisit terhadap R pada informasi pribadi.
- Audit hanya ditulis sistem; Master tidak memperoleh pengecualian CUD.

## Peran rangkap dan batas keamanan

Evaluasi: akun aktif -> tindakan -> sumber daya -> field -> keadaan draft/final/closed -> grant yang cocok. Hak digabung untuk tindakan yang sah pada sumber daya tersebut. Contoh principal+teacher boleh membaca akademik sekolah dan menginput hanya pada mapel yang diajar. Data Entry+teacher memperoleh D/P nilai hanya pada penugasan guru sendiri, bukan melalui delegasi Data Entry.

Master tidak memakai bypass global terhadap audit, field rahasia, histori final, atau approval khusus kepala sekolah. Password/hash/token tidak dapat dibaca oleh role apa pun. Mengangkat role/jabatan dan aktivasi penugasan yang memperluas akses bukan edit biodata.

Data Entry boleh menyiapkan penugasan tetapi tidak dapat mengaktifkan usulannya sendiri. Ini rekomendasi kontrol agar input penugasan tidak menjadi jalan memberi hak mengajar kepada diri sendiri; bukan fitur workflow generik. Rincian persetujuannya perlu dicatat pada keputusan implementasi.

Delegasi Data Entry dibatasi pengajar/mapel/kelas/semester, tindakan grade-entry atau attendance-entry, masa berlaku, pemberi dan alasan. Q6.3 telah mengonfirmasi Master/Akademik sebagai pemberi dan pencabut delegasi. Mekanisme permintaan atau perubahan delegasi serta kontrak rinci tetap perlu dirancang sesuai keputusan yang belum final. Actor audit tetap petugas asli; tidak menyamar sebagai guru yang berhalangan.

Audit/notifikasi bukan pintu akses baru. Wali tidak melihat audit nilai draft, password atau perubahan internal staf. Guru tidak melihat alamat/kontak seluruh wali hanya karena mengajar kelas. Log gabungan/bulk dibatasi per objek dan field, termasuk jumlah yang ditampilkan.

## Uji izin yang direncanakan

Role rangkap; siswa kelas lain; anak wali lain; penggantian ID; edit role dari profil; Data Entry menambah/aktivasi penugasan sendiri; delegasi kedaluwarsa/dicabut; Master membuat/mengubah/menghapus log; principal mengubah konfigurasi; principal mengapprove tetapi tidak mengedit nilai tanpa penugasan; draft lewat log/notifikasi; URL rapor; semester closed; bulk berisi objek di luar izin.

Semua uji **NOT RUN**. Matriks adalah spesifikasi, bukan bukti izin sudah aman.

D26/D27: Master/Akademik handover guru efektif dan mengelola class_subject/enrollment bertanggal. Guru baru parent yang sama berhak histori dan draft; guru lama kehilangan grant assignment itu saja, role lain tetap. Published/final bukan edit handover. Delegasi lama berakhir, MA regrant bila perlu; tidak memberi D hak aktivasi/penempatan/hapus baru. Rapor tetap satu mapel dan akses keseluruhan PDF tidak melebar. Aktor enrollment D/allowlist Q6 belum dipilih. [Alur](ACADEMIC_FLOW.md).

D-28 dikonfirmasi: hak koreksi, revisi dan pencabutan di atas tidak membolehkan PATCH snapshot final, perubahan roster/tanggal sesi final, penulisan audit manual atau otomatis membalik keputusan applied. Rincian lifecycle kanonis pada [ACADEMIC_FLOW](ACADEMIC_FLOW.md). Finalized/published sumber operasional dapat dikoreksi melalui command; isi rapor final tetap immutable, metadata lifecycle diaudit.

D-29: hanya Master C/U school_timezone dan tidak boleh menghapus key wajib; semua role membaca proyeksi zona/label pada halaman yang memang boleh diakses. Bukan grant GET /system-settings. Pilihan WIB/WITA/WIT satu acuan kalender/tampilan, perubahan dinamis versioned/audited. [Rincian](PRD.md).
