# PRD - SIA MI Alghivari

Versi kebutuhan: 0.20, 30 Oktober 2026. Blueprint; D25 dikonfirmasi menggantikan D23/D24: email verified Master, password sementara emailed/forced change, OTP Fouladgar reset/profil, modal sesudah commit dan reset login biasa. Q1-Q5/Q6.1/Q6.3/Q7.1-Q7.5 tetap. Parameter/provider/integrasi/fallback dan detail Q6/visual belum final. Tidak ada izin implementasi atau hasil tes aplikasi.

Sumber: arahan pemilik dalam percakapan dan [laporan KP](support/KP%20FARHAN%201%20revisi%20bab%202%20acc.pdf). Arahan pemilik menggantikan usulan fitur/peran yang bertentangan pada kajian awal.

## 1. Prinsip dan lingkup

Bangun ulang dari nol, satu sekolah, satu aplikasi/database, data demo sintetis. Upgrade skema, struktur kode, dan UI harus mendukung alur yang nyata. Fitur tambahan yang tidak diperlukan ditunda.

Aktor: Admin Master, Admin Akademik, Admin Data Entry, guru, kepala sekolah, orang tua/wali. Guru mapel dan wali kelas adalah penugasan guru. Role dapat dirangkap; hak suatu tindakan dievaluasi terhadap sumber daya yang sesuai, bukan melalui urutan pangkat tunggal.

Penugasan wali kelas disimpan pada `homeroom_assignments` yang merujuk guru dan rombel. `classrooms` tidak menyimpan foreign key guru secara langsung. Periode penugasan mengikuti periode rombel; hak wali kelas berasal dari penugasan aktif.

Kepala sekolah membaca seluruh bidang yang diizinkan, dengan pengecualian approval kenaikan/kelulusan yang sudah dikonfirmasi. Hak mengajar jika kepala sekolah juga guru tetap berasal dari penugasan, bukan jabatan kepala sekolah.

Konfigurasi sistem, pemberian role dan pengangkatan kepala sekolah hanya Master. Pengubahan profil sendiri dan publikasi rapor binaan oleh wali kelas merupakan pengecualian eksplisit terhadap batas baca terkait. Tidak ada pengguna yang dapat CRUD audit log.

## 2. Kebutuhan fungsional

| ID | Kebutuhan pemilik | Kriteria penerimaan rencana |
| --- | --- | --- |
| FR-001 | Akun dan peran rangkap | Satu akun dapat admin dan guru, guru dan wali kelas, atau kepala sekolah dan guru. Hak tiap kelas diperiksa; tidak ada kenaikan role melalui edit profil. |
| FR-002 | Konfigurasi sistem khusus Master | Hanya Master membuka administrasi konfigurasi dan mengubahnya. Informasi sekolah yang tampil pada rapor adalah proyeksi publik-terbatas, bukan akses konfigurasi. Tidak ada password/token yang bisa dibaca melalui UI. |
| FR-003 | Pengelolaan akademik | Tahun ajaran, semester, kelas, mapel, jadwal, KKM, bobot komponen, wali kelas dan pengajar sesuai matriks. Hanya Master menetapkan kepala sekolah/role; Akademik mengelola wali kelas dan guru mapel. |
| FR-004 | Informasi pribadi | Master mengelola seluruh profil domain; admin lain dibatasi jenis data dan field. Guru melihat profil sendiri dan data siswa yang relevan untuk tugas; wali melihat dirinya, anak dan kontak profesional wali kelas. Akun admin/role tidak tercakup izin edit biodata siswa. |
| FR-005 | Absensi per mapel | Guru membuka sesi bertanggal pada penugasan mapelnya, mengisi status siswa lalu mengesahkan. Belum dicatat/draft/hadir/izin/sakit/alpa dibedakan. Hanya sesi finalized masuk total pertemuan; bukan jumlah hari sekolah. |
| FR-006 | Penilaian | Katalog scoring_components berisi tugas, ujian harian, UTS, UAS dan kehadiran otomatis. Guru memilih komponen input dari katalog pusat, tanpa mengubah bobot/KKM. Skor input Q7.2: 0-100 inklusif, maksimal dua angka desimal. Invalid ditolak sebelum cast/insert tanpa pembulatan otomatis. Blank ditandai Belum ada nilai dan dilewati dari jumlah/pembagi; 0 tetap dihitung. Draft tidak masuk hasil publik. |
| FR-007 | KKM dan bobot global | Satu KKM dan set bobot untuk seluruh mapel, diatur hanya Master/Akademik. Total seluruh komponen termasuk kehadiran tepat 100%; tanpa override per guru/mapel/kelas. Rumus komponen = rata-rata nilai numerik * bobot/100; hadir = hadir/total pertemuan finalized * 100 * bobot/100. Perubahan menjadi revisi global baru, tanpa menimpa rapor final. |
| FR-008 | Rapor | Master/Akademik atau wali kelas binaan memfinalisasi/memublikasikan snapshot jika setiap komponen berbobot positif mempunyai data. Blank sebagian dilewati; seluruh komponen blank atau kehadiran tanpa pertemuan dengan bobot positif menghalangi finalisasi. Wali mengunduh versi published anaknya. Susunan ringkas Q7.1 disepakati; gambar hanya referensi; aturan Q7.1-Q7.5 telah disepakati, detail visual/render belum final dan belum diverifikasi. Catatan wali kelas opsional diisi pada draft binaan lalu dibekukan saat finalisasi. |
| FR-009 | Naik kelas dan lulus | Usulan per siswa, persetujuan kepala sekolah individual/massal sesuai Q1. Persetujuan dan penerapan dipisah agar menyiapkan tahun berikutnya tidak langsung mengubah siswa saat ini. Tidak ada kelulusan/kenaikan otomatis berdasarkan KKM saja. |
| FR-010 | Audit setiap CUD | Semua perubahan domain yang berhasil dan perubahan profil/read-state notifikasi tercatat. Perubahan bisnis dan audit atomik; audit gagal berarti perubahan ditolak. Tidak ada route insert/update/delete audit untuk pengguna, termasuk Master. |
| FR-011 | Notifikasi perubahan | Setiap CUD domain dinilai penerimanya sesuai izin/keadaan publikasi. Satu kelompok perubahan menghasilkan satu notifikasi per penerima/cakupan. Audit tetap per objek. Tidak ada bocoran draft atau rekursi notifikasi. |
| FR-012 | Profil mandiri dan lifecycle password D-25 | Profil sendiri tanpa role/anak/status. Profil: sesi aktif + OTP email + password/konfirmasi -> commit -> modal pilihan sesi wajib, perangkat kini tetap login. Reset username/email -> OTP email -> password/konfirmasi -> commit -> modal -> login biasa tanpa auto-login. Akun baru password sementara emailed dan forced change. |
| FR-013 | Pemantauan perkembangan oleh wali | Wali dapat membuka aktivitas penilaian dan kehadiran anak kapan saja selama periode berjalan, tanpa menunggu finalisasi atau publikasi rapor. Sumbernya nilai yang sudah dibagikan guru dan sesi kehadiran yang disahkan, bukan report_cards. Rekap berjalan diberi label sementara; draft internal tetap tidak terlihat. |

## 3. Alur penting

### Penilaian dan rapor

Pemantauan perkembangan dan penerimaan rapor adalah dua alur terpisah. Menu Perkembangan Belajar menampilkan aktivitas per penilaian/pertemuan, nilai yang sudah dibagikan, blank dan rekap sementara anak dari data operasional. Menu Rapor Online menampilkan hanya dokumen semester yang seluruh rekapnya telah difinalisasi dan dipublikasikan. Belum ada rapor tidak boleh menghalangi akses perkembangan.

Konfigurasi pusat aktif -> penugasan guru -> pilih komponen dan buat penilaian -> input satu kelas -> simpan draft -> publikasi hasil mapel -> rekap dengan rata-rata numerik dan kehadiran per mapel -> preview snapshot rapor -> Master/Akademik atau wali kelas binaan finalisasi/publish -> wali unduh. Blank sebagian dilewati; nol dihitung. Komponen berbobot positif tanpa data menghalangi finalisasi, bukan menjadi nol otomatis. Pembatalan/finalisasi ulang mempunyai alasan dan revisi; rapor lama tidak ditulis ulang.

Aturan rinci, ilustrasi numerik dan rekomendasi isi rapor ada pada [ACADEMIC_FLOW.md](ACADEMIC_FLOW.md). Bobot disimpan sebagai baris grading_policy_components untuk katalog scoring_components; tidak memakai empat kolom bobot tetap pada setiap penugasan guru.

Alur rapor: draft -> finalized -> published. Sebelum finalized, seluruh mapel yang diikuti harus tercakup dengan rekap valid, ketersediaan komponen berbobot positif sesuai aturan blank, kehadiran yang masuk rekap telah disahkan, serta versi konfigurasi konsisten. Finalized belum terlihat wali sebagai rapor; hanya published dapat dibuka/diunduh. Publikasi sebuah assessment dan pengesahan satu sesi absensi tidak sama dengan finalisasi rapor semester. Blank sebagian tetap dilewati, bukan diwajibkan berubah menjadi angka.

### Kenaikan dan kelulusan

Siapkan tahun/kelas tujuan -> susun usulan beserta hasil rapor -> kepala sekolah meninjau sesuai Q1 -> setujui/tolak dengan alasan -> terapkan pada batas tahun yang dipilih. Pembuat usulan, pemberi persetujuan dan pelaksana tercatat. Siapa pembuat usulan/pelaksana dicantumkan sebagai usulan pada matriks, belum dianggap izin final.

Kenaikan membuat keanggotaan periode tujuan; keanggotaan lama tetap. Kelulusan mengubah status pada saat berlaku tanpa menghapus nilai. Terapkan unik per siswa/tahun asal, cegah klik ganda dan perubahan proposal setelah approval. Jika data sumber berubah, keputusan perlu ditinjau ulang. Batch validasi dahulu dan ditolak seluruhnya bila ada baris tak berhak/invalid; tidak ada sukses sebagian tersembunyi.

### Perubahan dan notifikasi

Validasi izin/field/keadaan -> transaksi data + audit per objek + inbox penerima terpilih -> commit -> database queue/worker memancarkan sinyal melalui Reverb -> Echo mengambil ulang inbox berizin pada React. Inbox tersimpan tetap dapat dibaca jika socket gagal; reconnect melakukan catch-up. Reverb/Echo untuk notifikasi domain; D-25 menambah email akses/password, bukan seluruh CUD melalui email. Read-state tetap diaudit tanpa notifikasi baru; audit creation juga tidak memicu audit/notifikasi rekursif.

## 4. UI dan struktur implementasi

Navigasi mengikuti tugas pengguna; konteks tahun/semester/kelas selalu jelas. Pengguna dengan peran rangkap dapat memilih konteks kerja, tetapi pergantian tampilan tidak mengubah izin server. Tombol hanya muncul untuk tindakan berhak.

Input nilai/absensi satu kelas; halaman daftar mempunyai pencarian, filter dan pagination. Preview batch menunjukkan jumlah serta masalah sebelum pengajuan. Ponsel menggunakan daftar/tampilan terfokus, desktop dapat menggunakan grid. Seluruh layar mencakup loading, kosong, validasi, gagal simpan, sukses, akses ditolak dan konflik edit. Label/fokus keyboard dan penjelasan selain warna wajib diperiksa.

Gunakan struktur framework biasa: validasi, policy, transaksi dan action untuk alur bersama (publish, approve, rollover). Hindari engine workflow/izin generik. Kode sumber manusia tetap terbaca; pengujian mengikuti efek nyata dan batas akses.

## 5. Batas keamanan dan integritas

- Izin objek dan field di server pada baca, tulis, bulk, cetak, file, audit dan notifikasi. Scope role rangkap tidak melebar ke kelas/anak lain.
- Master tidak melewati audit, integritas data, atau pembatasan keadaan final. CRUD penuh pada domain bukan izin menghapus jejak atau mengubah keputusan kepala sekolah diam-diam.
- Data yang sudah dipakai rapor/riwayat tidak dihapus permanen lewat CRUD biasa; pembatalan/arsip adalah tindakan delete domain yang tetap tercatat. Finalisasi/revisi perlu alasan.
- Foto profil dibatasi jenis/ukuran, diverifikasi konten, diberi nama server, dan disimpan tanpa jalur executable; foto lama tidak dihapus sebelum referensi baru berhasil tersimpan.
- Audit password hanya metadata perubahan, tanpa hash, password lama/baru, token atau isi sesi. Pembacaan data sensitif menggunakan proyeksi field minimum.
- Notifikasi, audit, dan file rapor tidak memberi akses baru; perubahan hubungan wali/penugasan berlaku pada akses berikutnya.
- Perlindungan audit ditargetkan terhadap seluruh akun aplikasi. Pengelola database/server di luar aplikasi mempunyai kemampuan berbeda; tidak ada klaim bahwa DBA mustahil mengubah penyimpanan.

## 6. Keputusan pemilik dan hal yang masih terbuka

| ID | Keputusan atau pertanyaan | Status/perlakuan |
| --- | --- | --- |
| Q1 | Kepala sekolah boleh approval naik/lulus sebagai pengecualian READ-only; hak mengajar tetap sesuai penugasan | Dikonfirmasi pemilik. |
| Q2 | Data Entry C/U operasional tanpa D/konfigurasi; bantuan nilai/absensi hanya delegasi khusus tercatat | Dikonfirmasi pemilik. |
| Q3 | Wali kelas juga boleh memublikasikan rapor kelas binaan, bersama Master/Akademik | Dikonfirmasi pemilik. Tidak memberi hak mengedit nilai guru mapel lain. |
| Q4 | Hanya Master mengatur role/jabatan kepala sekolah; Akademik mengatur penugasan wali kelas/mapel | Dikonfirmasi pemilik. |
| Q5 | Absensi per mapel; KKM/bobot terpusat seluruh mapel; rata-rata nilai numerik dikali bobot, kehadiran berbobot, total 100%; blank dilewati dan 0 dihitung | Dikonfirmasi pemilik. Seluruh nilai satu komponen berbobot positif blank menghalangi finalisasi, juga dikonfirmasi. Nilai KKM/bobot diisi Master/Akademik, bukan konstanta blueprint. |
| Q6 | Field biodata guru/admin dan allowlist biodata wali; siapa pengusul/pelaksana rollover; aktivasi usulan penugasan Data Entry serta kontrak rinci permintaan/perubahan delegasi | Detail tersebut belum final. Q6.1/Q6.3 tetap; akses awal Q6.2 dan lifecycle Q6.4 dipilih D-25. Angka expiry/limit, provider email dan detail integrasi belum final. |
| Q6.1 | Urutan akun wali dan pengisian biodata/hubungan anak | Dikonfirmasi pemilik 29 Oktober 2026: Master membuat akun wali, menetapkan role guardian dan mengaktifkannya terlebih dahulu; Data Entry kemudian melengkapi biodata dan menghubungkannya dengan siswa sesuai kewenangan. Tidak ada alur pencatatan wali tanpa akun atau profil pending baru. Aktivasi administratif tidak memberi akses ke siswa sebelum hubungan sah tersedia. |
| Q6.2 | Akses pertama akun baru | D-25 dikonfirmasi 29 Oktober 2026 menggantikan D-23/D-24: Master akun/role/email verified administratif + password sementara acak sistem -> email URL biasa/username/password sementara -> login terbatas -> wajib password baru/konfirmasi -> commit -> modal pilihan sesi -> aplikasi. Semua akun baru; existing tambah role tidak direset. TTL/reissue/provider/integrasi OPEN; tautan sekali-buka/10 menit tidak diteruskan. |
| Q6.3 | Pemberi dan pencabut delegasi input nilai/kehadiran Data Entry ketika guru berhalangan | Dikonfirmasi pemilik 29 Oktober 2026: Master dan Admin Akademik berwenang memberikan serta mencabut delegasi. Batas tetap petugas, penugasan kelas/mapel/semester, capability dan masa berlaku yang tercatat; Data Entry hanya C/U draft dan actor audit tetap petugas asli. Keputusan aktor ini tidak menyetujui alur permintaan/persetujuan penugasan guru, detail DTO/limit atau implementasi. |
| Q6.4 | Email, OTP dan pilihan sesi | D-25 dikonfirmasi 29 Oktober 2026: email langsung verified oleh Master sebagai attestation administratif; Fouladgar dipilih untuk OTP email reset/profil. Modal sesudah password sukses wajib dengan pilihan logout/tidak. Reset login biasa; profil tetap login pada perangkat kini, pilihan logout untuk semua sesi lain. No tidak diam-diam logout akibat hash berubah. Provider SMTP/API dan angka parameter OPEN. |
| Q7 | Penilaian dan rapor | Aturan Q7.1-Q7.5 telah diterima. Detail visual/font/asset/ukuran kertas/pagination dan bukti implementasi/render tetap belum dipilih/diverifikasi; persetujuan aturan bukan hasil tes atau sign-off visual. |
| Q7.1 | Susunan rapor semester ringkas dengan detail komponen di aplikasi | Dikonfirmasi pemilik 29 Oktober 2026. Kepala dokumen; nama/NIS/kelas; tabel No/mapel/KKM/nilai akhir/status KKM; kehadiran per mapel H/S/I/A/total pertemuan/persentase; catatan wali kelas opsional pada draft binaan; penutup/nama/ruang tanda tangan. Tidak menambah predikat A-D, deskripsi kompetensi, sikap, ekstrakurikuler, ranking, rata-rata keseluruhan, NISN wajib, lampiran PDF wajib atau e-signature. Detail visual/font/asset/pagination belum diverifikasi. Rincian dan rujukan pada [alur akademik](ACADEMIC_FLOW.md). |
| Q7.2 | Batas input nilai penilaian | Dikonfirmasi pemilik 29 Oktober 2026: 0-100 inklusif, maksimal dua angka desimal. 0 sah, blank tetap belum ada nilai; pecahan berlebih/rentang invalid ditolak sebelum cast/insert tanpa pembulatan otomatis. Tidak menetapkan presisi input KKM/bobot, presisi hasil antara/akhir atau pembanding KKM. |
| Q7.3 | Pembulatan nilai akhir mapel | Dikonfirmasi pemilik 29 Oktober 2026: pertahankan presisi rata-rata/kontribusi hingga total, lalu bulatkan nilai akhir dua desimal dengan half-up. 79,416666... menjadi 79,42; 79,425 menjadi 79,43. Tidak memilih pembanding KKM atau presisi input KKM/bobot. |
| Q7.4 | Perbandingan KKM | Dikonfirmasi pemilik 29 Oktober 2026: nilai akhir dua desimal hasil Q7.3 dibandingkan dengan KKM global pada versi kebijakan yang sama; >= berarti Mencapai KKM, < berarti Belum mencapai KKM. Contoh hasil79,995→80,00 dengan KKM80 mencapai. Blank/belum lengkap tidak diberi status di bawah KKM atau nilai0 palsu. Tidak menetapkan presisi input KKM/bobot. |
| Q7.5 | Batas konfigurasi KKM dan bobot komponen | Dikonfirmasi pemilik 29 Oktober 2026: KKM global 0-100 inklusif dan bobot tiap komponen 0-100% inklusif, masing-masing maksimal dua angka desimal. Pecahan berlebih/rentang invalid ditolak sebelum cast/insert tanpa pembulatan otomatis; total bobot termasuk hadir wajib tepat100% untuk aktivasi. Master/Akademik memilih nilainya; tanpa default baru, toleransi total atau override mapel. |
| AR-04 | Pergantian guru di tengah semester | D-26 dikonfirmasi: class_subjects stabil, histori teaching_assignments, current grant dan provenance tetap. Published/final hanya dikoreksi melalui D-28; bukan efek handover atau PATCH biasa. Satu rekap/baris rapor per mapel. |
| AR-05 | Keanggotaan efektif dan penyebut per siswa | D-27 dikonfirmasi: enrollment bertanggal, draft roster direview, final attendance_records frozen. D-28 hanya mengoreksi status peserta existing, bukan tanggal/daftar peserta. Backdate mengubah roster final tetap ditolak; transfer term sama/rapor transfer ditunda. |

Istilah wali kelas yang mengawasi siswa dipahami sebagai peserta didik di kelas binaannya; bukan akses bebas ke seluruh informasi pribadi orang tua.

## 7. Tahap implementasi dan penerimaan

1. Akun, profil, role/penugasan, policy objek, data master, audit atomik dan notifikasi in-app dasar.
2. Akademik, jadwal, KKM global, katalog komponen dan bobot total 100%; daftar nilai dan sesi absensi per mapel dengan data sintetis.
3. Rekap, snapshot/revisi rapor, publikasi, unduh wali dan izin file.
4. Usulan/approval/penerapan kenaikan/kelulusan individual dan batch; penuntasan UI dan bukti tes.

Pengujian yang direncanakan: guru beda kelas, wali beda anak, role rangkap, eskalasi dari profil/admin bawahan, Master mencoba menulis audit, audit gagal, draft bocor lewat notif/log, file rapor ditebak, bobot invalid, skor kosong/nol, edit rapor final, double approval, batch berisi objek terlarang, pencabutan role/relasi, serta snapshot histori. Status semua tes aplikasi: **NOT RUN**, implementasi belum ada.

Penerimaan pemisahan portal: wali tetap melihat aktivitas yang sudah dibagikan/disahkan ketika report_cards belum ada atau masih draft; rapor finalized tetapi belum published tidak bisa dibuka/diunduh wali; notifikasi aktivitas dan rapor menuju menu berbeda; perubahan data operasional tidak menimpa snapshot rapor final lama. Seluruh skenario ini masih NOT RUN.

Fitur tambahan di luar kebutuhan di atas (CSV, chat, AI, kanal pesan eksternal, banyak sekolah) ditunda. Stack dan akses yang belum jelas tidak dinyatakan disetujui hanya karena tercantum di dokumen ini.

Keputusan teknis demo terbaru ada pada [TECH_STACK.md](TECH_STACK.md) dan [CICD_PIPELINE_SPEC.md](CICD_PIPELINE_SPEC.md): dev/main, GitHub Actions, GHCR dan continuous deployment ke Compose melalui runner lokal. Ini tidak mengubah hak akses domain atau mengotorisasi implementasi/deployment sekarang.

### Keputusan koreksi dan rapor D-28

Dikonfirmasi pemilik 29 Oktober 2026. Master/Akademik mengoreksi nilai published atau status kehadiran finalized melalui command khusus beralasan/versioned; guru mapel efektif hanya parent yang diajar saat semester terbuka. Semester closed hanya Master/Akademik melalui command koreksi tanpa membuka kembali semester. Data Entry tetap C/U draft dalam delegasi; wali kelas tidak mengoreksi skor/status guru lain dari tugas wali kelas.

Koreksi mempertahankan status aktivitas, provenance/creator dan frozen roster; audit before/after aman serta inbox berizin ikut transaksi. Nilai blank boleh diisi, angka diganti atau dikosongkan kembali; 0 tetap berbeda dari blank. Kehadiran hanya H/I/S/A peserta existing. Struktur/tanggal/kelas/mapel/komponen/roster final tidak dapat diubah melalui alur ini.

Rapor finalized/published terdampak ditandai Perlu revisi, publikasi/download ditahan langsung tanpa menimpa snapshot/PDF. Perkembangan Belajar menampilkan sumber terkoreksi dengan pemberitahuan rapor sedang direvisi. Master/Akademik atau wali kelas aktif binaan boleh membuat revisi, memfinalisasi/memublikasikan, serta mencabut rapor dengan alasan. Revisi per siswa memakai KKM/bobot/calculation_version asli, completeness dan file siap; versi terbit baru menjadi satu-satunya rapor aktif wali. Pencabutan menghentikan akses tanpa harus langsung membuat pengganti; penerbitan kembali melalui revisi baru.

Sumber keputusan naik/lulus yang belum applied menjadi needs_review dan perlu persetujuan ulang; applied tidak dibalik otomatis, tetapi memerlukan tindak lanjut staf. Lifecycle rinci pada [alur akademik](ACADEMIC_FLOW.md), payload pada [API_CONTRACT](API_CONTRACT.md). Seluruh runtime NOT RUN; dokumen/PDF yang sudah diterima tidak dapat ditarik dari perangkat pengguna.

### Satu timezone sekolah — D-29

Dikonfirmasi pemilik 30 Oktober 2026. Satu konfigurasi school_timezone untuk tampilan waktu seluruh pengguna dan kalender akademik. Master boleh mengubahnya melalui konfigurasi sistem kapan saja, dengan expected_version, konfirmasi dampak dan audit/inbox atomik. Pilihan UI menggantikan asumsi/placeholder zona sebelumnya:

| Label pilihan | Nilai internal IANA | Offset |
| --- | --- | --- |
| WIB | Asia/Jakarta | UTC+07:00 |
| WITA | Asia/Makassar | UTC+08:00 |
| WIT | Asia/Jayapura | UTC+09:00 |

Tidak menambah display_timezone atau timezone per pengguna. Lokasi perangkat orang tua tidak mengubah acuan sekolah. Runtime Laravel/koneksi database tetap UTC; school_timezone adalah pengaturan domain dinamis, bukan pengubahan timezone server.

Nilai awal zona sekolah belum dipilih pemilik; jangan otomatis mengambil timezone host, browser atau asumsi lokasi laporan. Form menampilkan Pilih zona waktu sekolah tanpa pilihan default sampai Master menetapkan satu nilai sah. Konfigurasi awal harus tersedia sebelum operasi akademik yang bergantung tanggal.

Backend membaca setting terbaru pada request/job baru, menggunakan satu value/version konsisten selama unit kerja dan memeriksa ulang saat transaksi sensitif. Setelah penyimpanan selesai, pengguna cukup refresh untuk melihat label/waktu terbaru; tidak memerlukan restart app/worker/Reverb, rebuild image/aset atau edit .env. Halaman yang sudah terbuka tidak dijanjikan berubah tanpa refresh.

Waktu kejadian/audit/OTP yang disimpan UTC tidak digeser; DATE kegiatan/enrollment/semester dan TIME jadwal tidak dikonversi. school_timezone menentukan tanggal hari ini dan penafsiran jadwal/tanggal efektif saat evaluasi. Perubahan zona dapat mengubah grant berbasis tanggal di sekitar tengah malam, termasuk membuat tanggal efektif tampak berlaku kembali; konfirmasi menjelaskan dampak ini, server selalu mengevaluasi grant terbaru. Snapshot/item/PDF rapor final tetap immutable.

Batas delegasi valid_from/valid_until yang sudah berupa instant UTC tidak direbase otomatis. Aturan cap akhir assignment bertanggal saat timezone diubah masih memerlukan keputusan tersendiri sebelum alur terkait diaktifkan; keputusan satu timezone/pilihan UI ini tidak menuntaskan gap tersebut. Detail pada [model](DATA_MODEL.md) dan [kontrak](API_CONTRACT.md). Seluruh runtime NOT RUN.

D-32 dikonfirmasi pemilik 30 Oktober 2026: proyek demo portofolio tidak menetapkan target formal RPO/RTO atau gate numerik kehilangan data/waktu pemulihan. Usulan sebelumnya dikeluarkan, bukan keputusan terbuka. Backup manual D-30/jadwal-retensi D-31 dan verifikasi restore fungsional tetap berlaku; seluruh runtime NOT RUN. [Rancangan pemulihan](DATA_RECOVERY_PLAN.md).

D-33 dikonfirmasi pemilik 30 Oktober 2026: demo memakai PC pemilik Windows/WSL2/Compose dan image main/GHCR berdigest melalui runner tepercaya; domain calon DigitalPlat .dpdns.org, Cloudflare authoritative DNS, satu Tunnel untuk hostname sia/ws saudara, HTTPS/WSS publik dan origin privat. Sleep off selama sesi, tanpa layanan24/7. Nama aktual/domain belum dimiliki dan konfigurasi/cert/resource/smoke belum diverifikasi. [Arsitektur](DEPLOYMENT_ARCHITECTURE.md). Persetujuan rancangan tidak mengotorisasi registrasi/provisioning/deployment sekarang.

D-34, arahan pemilik 30 Oktober 2026: lengkapi rancangan dokumen relevan dengan hipotesis/expected serta kebutuhan bukti; hasil aktual diisi bertahap setelah implementasi/uji lokal dan setelah demo publik terotorisasi. Bukti prototipe/desain berdiri sendiri, tidak membuktikan aplikasi inti. [Kontrak pencatatan](QA_EVIDENCE_REPORT.md) mengatur tahap/revisi/artefak dan pembaruan; seluruh actual inti NOT RUN, keputusan reviewer/release PENDING. Ini izin dokumentasi, bukan implementasi/provisioning/rilis.
