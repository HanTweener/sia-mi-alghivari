# Blueprint alur akademik dan rapor

Versi 0.9, 30 Oktober 2026. Status: susunan rapor ringkas Q7.1 dikonfirmasi pemilik; batas skor input Q7.2 juga dikonfirmasi; Q7.3 pembulatan akhir juga dikonfirmasi; Q7.4 pembanding KKM juga dikonfirmasi; Q7.5 presisi input KKM/bobot juga dikonfirmasi; detail visual dan implementasi belum diverifikasi. Tidak ada implementasi aplikasi atau migration.

Acuan: [PRD](PRD.md), [model data](DATA_MODEL.md), [matriks akses](ROLE_PERMISSION_MATRIX.md).

## 1. Keputusan yang telah dikonfirmasi

- Absensi per mata pelajaran: guru membuka sesi, lalu mengisi kehadiran siswa sesuai penugasan mapelnya.
- Satu KKM dan satu set bobot komponen terpusat berlaku untuk seluruh mapel. Tidak ada override per guru, mapel atau kelas pada tahap core.
- Master dan Akademik dapat mengatur KKM, komponen dan bobot. Guru dengan role mengajar saja tidak dapat mengubahnya. Bila seorang guru juga admin yang berhak, pengaturan dilakukan berdasarkan hak admin tersebut.
- Komponen awal nilai input: tugas, ujian harian, UTS, UAS. Kehadiran menjadi komponen otomatis tersendiri; total bobot seluruh komponen, termasuk kehadiran, harus tepat 100%.
- Nilai komponen input adalah rata-rata nilai numerik dikali bobot. Blank dilewati dan ditandai Belum ada nilai; nilai 0 tetap dihitung.
- Jika seluruh nilai satu komponen berbobot positif blank, rapor belum dapat difinalisasi. Keputusan ini dikonfirmasi pemilik melalui jawaban klarifikasi.
- Gambar rapor dari pemilik hanya referensi, bukan template wajib atau sumber data sekolah yang akan disalin.

## 2. Konfigurasi pusat

Katalog `scoring_components` menyimpan identitas komponen yang stabil. `grading_policies` menyimpan versi KKM global; `grading_policy_components` menyimpan bobot setiap komponen pada versi tersebut. Nama tabel dan rincian kolom ada di DATA_MODEL bagian 3.

Komponen source_type score memakai rumus rata-rata yang sama. Komponen source_type attendance mengambil nilai dari sesi kehadiran, tanpa form input skor kehadiran manual. Tidak ada engine formula bebas. Komponen yang sudah dipakai tidak dihapus sehingga referensi assessment dan rapor tetap sah.

Q7.5 / D-22 disepakati pemilik 29 Oktober 2026: KKM global 0-100 inklusif dan bobot tiap komponen 0-100% inklusif, masing-masing maksimal dua angka desimal. Integer/satu desimal sah. Validasi pecahan asli sebelum cast/insert tanpa memangkas, membulatkan atau menganggap blank sebagai0. Contoh valid KKM70,50 dan bobot12,50%; invalid -0,01/100,01/70,555/12,555. Angka contoh bukan default sekolah. Bobot0 tetap sah; total harus tepat100% untuk aktivasi, tanpa epsilon atau pembulatan total. Contoh33,33+33,33+33,34 sah;33,33+33,33+33,33=99,99 belum dapat aktif. Draft belum total100 tetap belum aktif sesuai alur sebelumnya; per-field invalid tidak boleh disimpan diam-diam.

Alur Master/Akademik: buka Konfigurasi Penilaian -> isi KKM dan seluruh bobot pada draft revisi -> sistem tampilkan total -> aktifkan jika tepat 100% -> seluruh mapel memakai revisi global yang sama. Guru hanya membaca konfigurasi dan memilih komponen input saat membuat assessment.

Perubahan menjadi revisi baru. Preview rekap menunjukkan versi yang dipakai; finalisasi ditolak jika konfigurasi berganti sesudah preview, lalu pengguna meninjau ulang. Rapor final mempertahankan snapshot versi sebelumnya. Ini perlindungan histori, bukan konfigurasi berbeda per mapel.

## 3. Absensi per mapel

1. Guru memilih class_subject kelas/mapel melalui teaching_assignment yang efektif saat ini.
2. Guru membuka sesi bertanggal dengan nomor pertemuan dan topik opsional.
3. Sistem menampilkan siswa yang enrollment-nya berlaku pada held_on; status awal belum dicatat, versi roster ditampilkan untuk review.
4. Guru mengisi hadir, izin, sakit atau alpa; draft dapat disimpan tanpa memberi status alpa otomatis.
5. Sesi disahkan setelah seluruh status siswa yang berlaku terisi; actor, perubahan dan notifikasi mengikuti izin.
6. Hanya sesi finalized yang masuk jumlah pertemuan terlaksana untuk siswa dan mapel tersebut. Draft atau sesi yang dibatalkan tidak masuk penyebut.

Setiap sesi dihitung satu pertemuan, bukan jumlah jam pelajaran atau satu hari sekolah. Rumus yang diminta menghitung hadir dalam pembilang; izin/sakit/alpa tetap masuk total pertemuan yang berlaku, tetapi bukan hadir. Belum ada pertemuan menghasilkan Belum tersedia, bukan persentase 0.

Tidak menggabungkan absensi banyak mapel menjadi jumlah hari. Pada rapor, gunakan istilah pertemuan dan rekap per mapel agar satu hari dengan banyak mapel tidak terhitung sebagai banyak hari.

### Pergantian guru dan keanggotaan efektif — D-26/D-27

Dikonfirmasi pemilik 29 Oktober 2026; menggantikan status terbuka AR-04/05. class_subjects adalah identitas stabil kelas-mapel/semester, teaching_assignments histori guru bertanggal. MA melakukan handover; kelas-mapel, jadwal, assessment/pertemuan dan satu item rapor tetap, tanpa menyalin data. Guru baru efektif membaca histori dan melanjutkan draft, guru lama kehilangan grant assignment itu tetapi role lain tetap. Creator/source assignment/audit tidak ditimpa, actor perubahan/finalisasi tetap pengguna sebenarnya. Published/final tidak dibuka untuk edit; delegasi lama berakhir, regrant oleh MA, dan bentrok jadwal guru baru diperiksa.

Nilai digabung pada tingkat aktivitas published di class_subject, bukan rata-rata dari rata-rata guru. Contoh Guru A memberi 90, Guru B memberi 70 dan 100 pada komponen sama -> (90+70+100)/3, bukan (90+85)/2. Kehadiran seluruh guru digabung sebagai satu rangkaian pertemuan; nomor tidak dimulai ulang atau menghasilkan baris rapor kedua.

Tanggal memakai usulan [starts_on,ends_on), akhir eksklusif; tanpa perubahan per jam. A berakhir15 Nov dan B mulai15 Nov berarti tanggal15 milik B. Assignment berlaku menurut tanggal kalender sekolah, bukan asal pembuat assessment. Draft yang dibuat A untuk tanggal setelah pergantian masih dapat dilanjutkan B. Rentang tidak kosong/overlap; D-29 memakai satu school_timezone pilihan WIB/WITA/WIT; nilai awal dan rekonsiliasi cap delegasi saat pergantian zona masih perlu ditetapkan.

Enrollment juga mempunyai starts_on/ends_on: sebelum masuk/setelah keluar tidak dihitung dan tidak dianggap alpa. Pada draft, eligible roster dihitung pada tanggal pertemuan, perubahan membership mengusangkan versi/form dan harus ditinjau sebelum finalize. Finalisasi mengunci sumber, memeriksa set peserta dan status lengkap, lalu attendance_records menjadi frozen roster; tidak menambah tabel peserta. Backdate yang mengubah peserta sesi final ditolak sampai koreksi resmi tersedia. Record final tidak difilter ulang memakai status siswa/keanggotaan sekarang.

Contoh kelas dengan 10 pertemuan, siswa masuk mulai pertemuan ke-7 dan mempunyai 3 hadir + 1 sakit pada empat sesi finalized: total berlaku 4, hadir 3, persentase 75%. Bukan3/10=30%; hadir/sakit/izin/alpa tetap satuan pertemuan, bukan hari. Bila satu dari empat masih draft, hanya tiga finalized masuk penyebut sesuai status sebenarnya, bukan otomatis tetap4.

Sebelum siswa masuk, nilai tetap blank/bukan0 dan tidak dihitung. Komponen berbobot positif yang tetap tanpa nilai/kehadiran masih menghalangi finalisasi seperti aturan Q5; tidak mengimpor nilai asal atau membuat nilai pengganti. Kenaikan/pergantian periode tetap memakai alur yang ada. Transfer antarkelas tengah semester, re-enroll kelas lain term sama, impor nilai asal serta rapor khusus pindahan ditunda; unique siswa/term dipertahankan. [Model dan integritas](DATA_MODEL.md).

## 4. Nilai dan rumus

Q7.2 / D-19 disepakati pemilik 29 Oktober 2026: skor input 0-100 inklusif, maksimal dua angka desimal. Contoh sah: 0, 100, 87,5 dan 87,50; API memakai decimal string bertitik. Contoh ditolak: -0,01, 100,01 dan 87,555. Validasi format/rentang/pecahan asli sebelum cast/insert, tanpa memangkas, membulatkan atau membatasi otomatis ke 0/100. Blank tetap belum ada nilai, 0 tetap dihitung. Batas ini tidak menentukan presisi hitung, pembulatan akhir, pembanding KKM atau presisi input KKM/bobot. Rancangan lama yang memakai max_score berbeda-beda dan normalisasi dihapus dari blueprint core. Jika skala lain diperlukan kelak, aturan konversi harus dibahas tersendiri.

Untuk siswa, mapel dan semester yang sama:

- Rata-rata komponen = jumlah nilai numerik / banyaknya nilai numerik.
- Kontribusi komponen = rata-rata komponen * bobot_persen / 100.
- Persentase hadir = jumlah hadir / jumlah pertemuan finalized yang berlaku * 100.
- Kontribusi hadir = persentase hadir * bobot_kehadiran_persen / 100.
- Nilai akhir mapel = jumlah kontribusi semua komponen pada kebijakan global.

Nilai yang digunakan untuk hasil publik dan rapor berasal dari assessment published. Blank dalam assessment published tetap blank; assessment draft tidak masuk perhitungan publik. Publikasi assessment tidak mensyaratkan semua sel nilai terisi, sehingga nilai blank sebagian tetap dapat dilewati seperti arahan pemilik.

### Blank, nol dan komponen tanpa data

| Kondisi | Hasil |
| --- | --- |
| 90, blank, 100 | Jumlah 190, pembagi 2, rata-rata 95. Blank ditandai, bukan 0. |
| 90, 0, 100 | Jumlah 190, pembagi 3; 0 masuk perhitungan. |
| Semua blank, bobot lebih dari 0 | Belum ada nilai; tidak menghitung 0/0, belum dapat finalisasi rapor. |
| Belum ada sesi, bobot hadir lebih dari 0 | Belum tersedia; belum dapat finalisasi rapor. |
| Komponen berbobot 0 tanpa data | Tidak menyumbang dan tidak memblokir finalisasi; tetap tampil sesuai konteks, tanpa angka palsu. |
| Ada blank tetapi minimal satu nilai numerik pada setiap komponen berbobot positif | Hitung dari nilai numerik; blank sebagian saja tidak menghalangi finalisasi. |

Jangan membagi ulang bobot untuk menutup komponen kosong. Total bobot konfigurasi tetap 100%; kelengkapan data adalah pemeriksaan terpisah. Preview dapat menunjukkan kontribusi yang tersedia, tetapi hasil parsial tidak disebut nilai akhir rapor.

### Ilustrasi lengkap

Angka berikut hanya ilustrasi hitung, bukan bobot default atau kebijakan sekolah yang disetujui.

| Komponen | Data | Rata-rata/persentase | Bobot | Kontribusi sebelum pembulatan |
| --- | --- | --- | --- | --- |
| Tugas | 90, 90, 100 | 93,333333... | 20% | 18,666666... |
| Ujian harian | 80, blank, 0 | 40 | 20% | 8 |
| UTS | 85 | 85 | 25% | 21,25 |
| UAS | 90 | 90 | 25% | 22,50 |
| Kehadiran | 18 hadir / 20 pertemuan | 90 | 10% | 9 |
| Total | Semua komponen berbobot positif mempunyai data | - | 100% | 79,416666... |

Q7.3 / D-20 disepakati pemilik 29 Oktober 2026: pertahankan presisi rata-rata dan kontribusi selama menghitung, lalu bulatkan total akhir mapel dua desimal dengan half-up. Gunakan aritmetika decimal/rasional yang menjaga hasil, bukan pembulatan float atau hasil preview JavaScript. Detail library/representasi internal belum dipilih; tidak mengunci hasil antara menjadi dua desimal. Contoh total menjadi 79,42. Kontribusi tugas pada tampilan dua desimal menjadi 18,67, bukan 18,66. Jangan memangkas rata-rata atau setiap kontribusi sebelum menjumlahkan nilai akhir.

Jika kontribusi ditampilkan dua desimal, jumlah angka tampilan dapat berbeda 0,01 dari hasil hitung presisi penuh; beri penjelasan singkat. Q7.4 / D-21 dikonfirmasi pemilik 29 Oktober 2026: bandingkan nilai akhir dua desimal sesudah pembulatan Q7.3 dengan KKM global versi kebijakan yang sama. Nilai akhir >= KKM berarti Mencapai KKM; nilai akhir < KKM berarti Belum mencapai KKM. Gunakan perbandingan decimal tepat tanpa epsilon atau pembulatan KKM diam-diam. Dengan KKM80, hasil79,995→80,00 mencapai; nilai akhir79,99 belum mencapai. Nilai/KKM/status snapshot sama pada UI dan PDF. Status ini tidak mengubah alur approval naik/lulus. Contoh tie hasil hitung: 79,425 menjadi 79,43; 79,995 menjadi 80,00. Ini hasil turunan, bukan izin menginput skor tiga desimal.

## 5. Susunan rapor yang disepakati (Q7.1)

Pemilik menyetujui susunan berikut pada 29 Oktober 2026 (Q7.1 / D-18): PDF rapor ringkas, dengan rincian komponen pada detail rapor di aplikasi dari snapshot yang sama. Rincian tetap mengikuti hak keseluruhan isi rapor; akses guru ke satu mapel tidak membuka rapor gabungan. Tidak ada lampiran PDF wajib atau banyak kolom komponen pada cetak. Persetujuan ini belum mengunci ukuran kertas, font, logo, pagination, rumus pembulatan atau rentang angka.

| Bagian | Isi disepakati | Sumber / batas |
| --- | --- | --- |
| Kepala dokumen | Nama madrasah, alamat, judul Laporan Hasil Belajar, semester dan tahun ajaran | Snapshot profil sekolah/periode. Logo hanya bila asset berizin tersedia dan dipilih; tidak menyalin logo/identitas contoh. |
| Identitas | Nama siswa, NIS dan kelas | Snapshot siswa/enrollment. Revisi tetap metadata histori; tidak menambah NISN wajib. |
| Hasil per mapel | No., mata pelajaran, KKM global, nilai akhir, status Mencapai KKM/Belum mencapai KKM | Snapshot hasil dan kebijakan yang dipakai. Rentang predikat A/B/C tidak diciptakan otomatis. |
| Rincian perhitungan di aplikasi | Rata-rata komponen, bobot, kontribusi, jumlah nilai/blank dan rincian kehadiran | Snapshot komponen dari revisi sah, bukan hitung ulang dengan data terbaru. Bukan lampiran PDF wajib atau modul penilaian baru. |
| Kehadiran per mapel | Hadir, sakit, izin, alpa, total pertemuan dan persentase hadir | Sesi finalized yang berlaku bagi siswa. Satuan pertemuan, tidak dijumlahkan sebagai hari sekolah. Belum ada pertemuan berarti Belum tersedia, bukan 0%. |
| Catatan | Catatan wali kelas opsional | Wali kelas aktif binaan mengisi teks pada draft rapor; Master mempertahankan hak domainnya. Ini pengecualian field tertentu terhadap akses baca wali kelas, tanpa hak mengubah skor guru lain. Dibekukan bersama snapshot final; bukan modul nilai perilaku. |
| Penutup | Tempat/tanggal terbit, nama wali kelas dan kepala madrasah, ruang tanda tangan | Snapshot saat terbit; ruang tanda tangan wali kelas, kepala madrasah dan orang tua/wali. Tidak menambah fitur tanda tangan elektronik atau persetujuan kepala sekolah atas publikasi rapor. |

Susunan ini mengadaptasi referensi, bukan menyalin rapor resmi. Predikat A-D, deskripsi kompetensi, nilai sikap, ekstrakurikuler, ranking dan rata-rata keseluruhan mapel tidak ditambahkan pada susunan awal yang disepakati. Label dan aturan pembanding Q7.4 disepakati: gunakan nilai akhir yang sudah dibulatkan, bukan angka sebelum pembulatan.

Referensi tambahan: [Panduan Pembelajaran dan Asesmen revisi 2025, BSKAP](https://repositori.kemendikdasmen.go.id/33290/1/Panduan%20Pembelajaran%20Asesmen.pdf), lampiran hlm. 136-137 (halaman PDF 144-145), mencontohkan rapor SD dengan identitas, hasil per mapel, uraian pembelajaran, catatan, kehadiran dan pengesahan. Halaman contoh tersebut diperiksa teks dan visual pada 29 Oktober 2026, lalu diadaptasi melalui Q7.1. Panduan memakai kehadiran dalam hari dan memuat capaian/kegiatan yang belum menjadi lingkup proyek. Rumus, KKM dan bobot proyek tetap mengikuti keputusan pemilik, tidak dianggap berasal dari panduan tersebut.

Referensi proses tambahan: [LHB Dalam Bentuk File PDF, Kemenag Jateng, 20 Desember 2021](https://jateng.kemenag.go.id/lhb-dalam-bentuk-file-pdf/) menjelaskan praktik MTsN 2 Pekalongan: nilai berbobot dan KKM, guru menyelesaikan nilai lalu wali kelas merekap sebelum rapor dibagikan. Itu contoh historis MTs, bukan pedoman wajib MI atau sumber bobot kehadiran proyek. Belum ditemukan referensi yang persis memakai seluruh komponen/bobot kehadiran kita.

Deskripsi kompetensi panjang, kokurikuler, ekstrakurikuler dan penilaian perilaku ditunda karena belum mempunyai alur/sumber data yang disepakati. Jangan mengisi otomatis dengan teks generik. Sketsa visual, asset dan hasil render Dompdf tetap perlu ditinjau; persetujuan susunan bukan bukti layout sudah lulus.

## 6. Rapor final dan izin

### Pemantauan perkembangan berbeda dari rapor online

Keputusan pemilik: wali dapat memantau aktivitas penilaian dan kehadiran anak kapan saja, tanpa menunggu seluruh semester selesai atau rapor terbit. Ini halaman data operasional yang terus mengikuti aktivitas yang sudah dibagikan/disahkan, bukan pembacaan dokumen rapor.

| Aspek | Perkembangan Belajar | Rapor Online |
| --- | --- | --- |
| Kapan tersedia | Sepanjang periode berjalan, termasuk saat data sebagian masih blank dan rapor belum ada. | Setelah seluruh rekap mapel/kehadiran difinalisasi dan rapor dipublikasikan. |
| Sumber | Assessments/scores yang sudah dibagikan dan attendance_sessions/records yang disahkan. | Snapshot report_cards/report_card_items versi published. |
| Isi | Aktivitas per tanggal/mapel/komponen, nilai tersedia, tanda Belum ada nilai, kehadiran dan rekap sementara. | Rekap akhir semester, KKM/bobot versi final dan dokumen rapor unduhan. |
| Perubahan | Mengikuti koreksi operasional yang dibagikan; tanggal pembaruan dan label sementara jelas. | Tidak berubah diam-diam; koreksi membutuhkan revisi rapor. |
| Ketergantungan pada rapor | Tidak ada; status Belum tersedia pada rapor tidak menutup halaman perkembangan. | Harus lolos finalisasi seluruh rekap dan publikasi. |

Istilah dipisahkan: assessment published berarti hasil aktivitas sudah dibagikan; attendance session finalized berarti pertemuan telah disahkan; report finalized berarti rekap semester sudah dibekukan. Dua status aktivitas pertama tidak mengharuskan seluruh semester atau rapor final. Draft internal tetap privat; arahan pemantauan kapan saja tidak mengubah draft menjadi data publik.

### Finalisasi dan publikasi rapor

Status rapor: draft -> finalized -> published, dengan revoked untuk versi yang ditarik. Sebelum finalized, periksa seluruh mapel yang diikuti telah masuk rekap, data komponen berbobot positif tersedia, absensi yang dipakai telah disahkan, dan versi kebijakan sesuai preview. Ini tidak mewajibkan setiap sel nilai numerik terisi: blank sebagian tetap dilewati sesuai keputusan sebelumnya. Rapor finalized belum tampil pada menu Rapor Online wali sebelum published.

Guru mapel mengisi dan memublikasikan nilai mapelnya; wali kelas tidak mengedit nilai guru mapel lain. Master/Akademik atau wali kelas binaan membuat preview dari data publik, memeriksa komponen berbobot positif tersedia, lalu memfinalisasi dan memublikasikan snapshot. Wali mengunduh versi published anaknya.

Snapshot mencakup versi KKM/bobot global, label komponen, angka input yang dirangkum, jumlah blank, kehadiran dan hasil. Perubahan berikutnya tidak menimpa rapor final lama. Nilai akhir kosong bukan nilai 0 dan bukan label Belum mencapai KKM; hasil yang belum bisa dihitung tetap diberi status belum tersedia.

## 7. Bukti dan hal terbuka

Belum ada aplikasi inti/server, migration atau PDF rapor resmi proyek. Pembaruan 29 Oktober 2026: [facelift-v2](support/facelift-v2/README.md) mempunyai prototipe UI, tes frontend dan PDF tinjauan desain sebagai referensi; semuanya tidak membuktikan rumus/izin/transaksi server atau mengesahkan asumsi demo Q7. Tes backend yang direncanakan tetap mencakup blank versus 0, komponen positif kosong, bobot 0/total100, nol pertemuan, presisi, scope anak/kelas, pergantian kebijakan dan snapshot; seluruhnya NOT RUN.

Q7.1 susunan rapor ringkas dan catatan wali kelas opsional sudah dikonfirmasi. Q7.2 batas skor input 0-100/maksimal dua desimal juga dikonfirmasi. Q7.3 hasil akhir dua desimal half-up tanpa pembulatan antara juga dikonfirmasi. Q7.4 perbandingan KKM memakai nilai akhir yang dibulatkan juga dikonfirmasi. Q7.5 batas konfigurasi KKM/bobot diterima. Terbuka: detail visual cetak/font/asset/pagination. Predikat/deskripsi kompetensi tidak diaktifkan pada lingkup awal. Keputusan role/delegasi dan batas biodata yang belum final tetap ada pada PRD Q6. Tidak ada implementasi dimulai dari dokumen ini.

## 8. Koreksi sah, revisi dan pencabutan rapor — D-28

Dikonfirmasi pemilik 29 Oktober 2026. Master/Akademik dapat mengoreksi hasil published/finalized, termasuk semester closed. Guru mapel yang efektif pada class_subject saat request/commit hanya ketika semester terbuka; assignment asal hanyalah provenance. Wali kelas, principal dan Data Entry tidak memperoleh hak koreksi dari tugas itu; role rangkap tetap dievaluasi terpisah. Closed bukan alasan membuka semua CRUD atau membuka semester kembali.

Pengguna memilih Koreksi, meninjau data lama/usulan dan rapor terdampak, mengisi alasan lalu mengonfirmasi. Command dengan expected_version memperbarui sumber, version/source token, audit per objek dengan diff aman dan inbox dalam satu transaksi. Tidak ada tahap reopen yang membocorkan draft atau menghapus status published/finalized. Actor koreksi adalah pengguna sebenarnya; creator/source assignment tetap. Koreksi identik tanpa perubahan ditolak tanpa event sukses palsu.

- Skor: isi blank, ganti angka 0..100 maksimal dua desimal, atau kosongkan kembali. Tidak ada baris scores berarti blank; nilai0 tetap baris numerik. Kosongkan skor published hanya melalui command koreksi beralasan, bukan PATCH draft/Data Entry.
- Absensi: ubah H/I/S/A pada attendance_records existing yang telah dibekukan. Tidak menambah/menghapus peserta, mengubah held_on, class_subject, komponen atau memindah enrollment. Pembilang hadir dapat berubah, jumlah peserta/pertemuan frozen tetap.
- Perubahan enrollment yang mengubah roster final tetap ditolak. Struktur/tanggal aktivitas final, pembatalan seluruh aktivitas dan undo handover bukan bagian koreksi ini.

Dampak sumber dihitung per siswa dan rapor yang benar-benar terkait, bukan seluruh kelas otomatis. Koreksi, termasuk perubahan input rekap lain yang sah sesudah finalisasi, harus mengusangkan preview/draft dan menahan versi finalized/published terdampak dalam transaksi yang sama. Tidak menunggu queue/Reverb. Bahkan bila pembulatan akhir sama, perubahan rincian sumber snapshot tetap memerlukan revisi. Perkembangan Belajar menunjukkan sumber terbaru yang published/finalized, terpisah dari snapshot rapor.

Isi snapshot/item/PDF final immutable; metadata lifecycle/version dapat berubah secara diaudit. Usulan representasi sederhana: status draft/finalized/published/revoked terpisah dari availability current/needs_revision/superseded. current berarti sumber snapshot masih sah, bukan otomatis published. Wali hanya boleh detail/download versi published + current yang aktif; kebutuhan revisi atau pencabutan ditampilkan sebagai pemberitahuan minimal pada semester yang sebelumnya memang dipublikasikan, tanpa membuka snapshot tertahan, alasan internal atau daftar versi internal.

Alur revisi per siswa: versi terakhir -> draft revisi baru dengan supersedes_id -> preview sumber terkini memakai grading_policy_id/KKM/bobot dan calculation_version asli -> completeness -> finalized -> file siap -> published. Master/Akademik atau wali kelas aktif binaan menjalankan alur ini, termasuk rapor semester closed, tanpa mengubah sumber dari hak wali kelas. Perubahan catatan hanya M/W menurut grant catatan draft; A tidak mendapat hak tulis catatan baru. Tidak mengaktifkan kebijakan lama secara global atau berpindah otomatis ke policy aktif terbaru.

Hanya satu revisi belum selesai per enrollment/term; create stale/duplikat ditolak atau diarahkan membaca revisi existing yang berhak. Draft belum immutable dapat dipreview ulang; snapshot yang sudah finalized dan kembali usang membutuhkan revisi baru. Sumber/calculation version lama harus masih didukung; bila tidak tersedia, blokir dan laporkan, jangan fallback diam-diam. Jangan mengarang grade0 ketika koreksi mengosongkan satu komponen berbobot positif; revisi tidak bisa final sampai data lengkap.

Publikasi revisi baru memeriksa ulang scope, version, source_version dan file milik snapshot yang sama. Dalam satu transaksi, revisi baru published/current dan versi published sebelumnya menjadi superseded; tidak ada dua versi aktif wali. Versi yang revoked tetap revoked. Gagal create/finalize/render/publish tidak membuka kembali versi needs_revision/revoked. Retry render memakai snapshot frozen yang sama, tanpa menimpa file versi lama; renderer di luar DB lock, pemasangan hasil memeriksa lagi version/availability/source.

Pencabutan oleh Master/Akademik atau W aktif binaan memakai alasan/expected_version pada versi finalized/published yang belum revoked/superseded. Metadata status revoked/revoked_at/by berubah, snapshot/file tidak dihapus. Tidak memerlukan revisi pengganti segera; penerbitan kembali selalu versi baru, tidak mengubah revoked menjadi published. Wali terkait mendapat pemberitahuan aman hanya jika semester rapor pernah published; pencabutan finalized yang belum pernah terbit tetap internal. Versi histori hanya staf dengan scope baca yang cukup. File yang sudah diunduh tidak dapat ditarik dari perangkat.

Penahanan/revoke/replacement sumber naik-lulus membuat keputusan submitted/approved yang belum applied menjadi needs_review; preview draft diusangkan. Persetujuan ulang principal diperlukan sebelum apply. Jika sudah applied, histori keputusan/enrollment/kelulusan tetap; proyeksi staf menandai tindak lanjut melalui perubahan sumber/audit, tanpa undo otomatis atau modul mutasi baru. Seluruh pengujian implementasi/race/PDF/akses masih NOT RUN.

Slot revisi belum selesai D-28 berarti status draft atau finalized dengan availability current; finalized/needs_revision, revoked dan superseded tidak menempati slot kandidat. Saat membuat revisi eksplisit dari latest finalized/current, atomik ubah metadata asal menjadi needs_revision + naikkan version bersama create/audit/inbox agar tidak dapat publish bersaing. Membuat draft pengganti dari published/current tanpa koreksi sumber atau revoke tidak otomatis menahan versi terbit lama; ia tetap aktif sampai supersede saat revisi baru published. Pemeriksaan satu kandidat dan satu versi published/current adalah dua invariant terpisah.

Penahanan D-28 hanya mengubah availability versi finalized/published yang masih current dan terdampak; versi revoked/superseded tetap tertutup, tidak diaktifkan atau dikembalikan ke status sebelumnya. Revoke kandidat revisi yang belum pernah published tidak mencabut versi published/current lain yang masih sah. Notice wali mengikuti perubahan ketersediaan rapor publik pada semester terkait; mutasi versi internal tidak memunculkan notice “Dicabut” bila rapor aktif wali tidak berubah.

## 9. Kalender sekolah dan tampilan waktu — D-29

Satu school_timezone Master: WIB (Asia/Jakarta), WITA (Asia/Makassar), WIT (Asia/Jayapura). Pilihan berfungsi untuk kalender akademik dan tampilan semua pengguna, termasuk wali di wilayah berbeda. Simpan UTC untuk waktu kejadian; tanggal kegiatan tetap DATE, jam jadwal tetap TIME. Nilai awal tidak diasumsikan dari lokasi laporan/perangkat.

Contoh sintetis 29 Oktober 2026 pukul16:30 UTC: WIB29 Oktober23:30, WITA30 Oktober00:30, WIT30 Oktober01:30. Assignment mulai30 Oktober belum berlaku di WIB, tetapi sudah berlaku pada WITA/WIT. Mengganti setting dapat mengubah evaluasi itu, sehingga Master mengonfirmasi dampak sebelum commit; refresh menampilkan zona baru. Tidak ada restart atau perubahan massal histori.

Request/preview lama yang versi school_timezone-nya berubah harus ditinjau ulang sebelum mutasi sensitif; jangan simpan memakai grant/tanggal yang dihitung dari setting lama. DATE roster final dan snapshot rapor tetap, hanya penafsiran waktu kini/tampilan yang mengikuti zona terbaru. Batas UTC eksplisit tetap instant; aturan rekonsiliasi cap delegasi dari assignment DATE masih OPEN dan bukan bagian yang diterima otomatis oleh D-29. [Kontrak](API_CONTRACT.md).
