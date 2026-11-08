# Content & Microcopy Style — SIA MI ALGHIVARI

Pemilik keputusan: pemilik proyek. Diperbarui: 29 Oktober 2026. Status **PROPOSED / blueprint**; copy/alur implementasi belum disetujui menyeluruh. [Prototipe facelift-v2](support/facelift-v2/README.md) sudah ada sebagai referensi, bukan panduan. Uji pengguna dan verifikasi copy pada aplikasi inti tetap **NOT RUN**; bukti prototipe dibatasi artefaknya.

Acuan: [PRD](PRD.md), [alur pengguna](USER_FLOWS.md), [fitur](FEATURES/FEATURE_SPEC_TEMPLATE.md), [kontrak API](API_CONTRACT.md), [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md) dan [sistem desain](DESIGN_SYSTEM.md). Contoh kalimat di bawah adalah usulan microcopy sintetis, bukan pesan dari aplikasi yang sudah berjalan.

## Voice and language

Saat implementasi, gunakan antislop core/copywriting untuk menilai kejelasan pesan dan kebenaran state; taste-skill app-screen membantu hirarki isi, bukan mengganti istilah akademik agar mengikuti template. Contoh copy/label prototipe boleh dipertimbangkan setelah cocok dengan [PRD](PRD.md) dan [API](API_CONTRACT.md); bukan teks yang wajib disalin. Panduan lintas skill dan bukti mengikuti [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md).

Bahasa utama Indonesia, untuk admin, guru, kepala sekolah dan wali pada layar desktop maupun ponsel. Gunakan kalimat singkat yang menjelaskan objek, periode dan akibat tindakan; hindari jargon framework, klaim promosi, istilah backend dan pujian yang tidak membantu tugas sekolah.

Sapaan netral tanpa gelar/asumsi gender. Pakai nama siswa/kelas hanya dari proyeksi server yang diizinkan; jangan menebak hubungan keluarga dari nama. Judul menunjukkan tugas, misalnya “Kehadiran per mapel” atau “Tinjau rapor”, bukan slogan dekoratif.

| Istilah pilihan | Makna dan batas penulisan |
| --- | --- |
| Tahun ajaran / semester / kelas | Konteks akademik selalu dibedakan; jangan menyebut perpindahan semester sebagai kenaikan kelas. |
| Guru mapel / wali kelas | Dua penugasan berbeda pada akun guru; wali kelas tidak otomatis mengedit nilai mapel lain. |
| Orang tua/wali | Role pengawas anak terkait; “wali kelas” tetap guru penanggung jawab kelas, bukan orang tua. |
| Perkembangan Belajar | Aktivitas nilai dibagikan dan kehadiran disahkan; tersedia tanpa rapor. Rekap berlabel “Sementara”. |
| Rapor Online | Dokumen semester yang telah difinalisasi lalu dipublikasikan; jangan menyebut setiap rekap berjalan sebagai rapor. |
| Draf | Belum dibagikan/disahkan; bukan hasil yang dapat dilihat wali. Label Indonesia untuk state draft. |
| Penilaian dibagikan | Hasil aktivitas mapel published; tidak berarti rapor semester sudah terbit. |
| Kehadiran disahkan | Sesi finalized masuk perhitungan; tidak berarti semua proses semester selesai. |
| Rapor difinalisasi / dipublikasikan | Dibekukan tetapi belum terlihat wali / telah tersedia bagi wali; dua keadaan dan tindakan berbeda. |
| Belum ada nilai / 0 | Blank dilewati dari rata-rata / nilai numerik yang tetap dihitung; jangan memakai “-” yang ambigu sebagai satu-satunya penjelasan. |
| Belum dicatat / alpa | Status kehadiran belum diisi / ketidakhadiran yang dipilih sah; tidak saling menggantikan. |
| Pertemuan | Satuan absensi per mapel; bukan “hari” atau jumlah jam pelajaran. |
| Disetujui / diterapkan | Keputusan principal / perubahan kelas atau status yang dilakukan terpisah; approval bukan kenaikan langsung. |
| Diarsipkan / dibatalkan | Dampak domain sesuai histori; jangan menjanjikan hapus permanen atau pemulihan jika prosedurnya belum tersedia. |

Label tindakan dimulai kata kerja: “Simpan draf”, “Sahkan kehadiran”, “Bagikan hasil”, “Finalisasi rapor”, “Siapkan PDF”, “Publikasikan rapor”, “Unduh rapor”, “Setujui usulan”, “Tolak usulan”, “Terapkan keputusan”. Jangan memakai satu tombol “Proses” untuk akibat berbeda atau label “Simpan” yang diam-diam memublikasikan.

Aksi hanya ditampilkan bila ada grant; aksi yang sah tetapi belum memenuhi prasyarat boleh nonaktif dengan alasan teks. Contoh: “Belum dapat difinalisasi: komponen berbobot positif belum memiliki nilai.” Jangan menyebut “Akses penuh” untuk Master karena audit/status final/approval tetap dibatasi.

## Screen-content rules

| Keadaan | Usulan copy dan makna |
| --- | --- |
| Aktivitas anak belum dibagikan | “Belum ada aktivitas yang dibagikan untuk periode ini.” Filter dapat diubah; tidak meminta wali menginput nilai. |
| Rapor belum tersedia | “Rapor semester ini belum tersedia. Anda tetap dapat membuka Perkembangan Belajar.” Jangan membocorkan draf/finalized/revoked pada daftar wali. |
| Belum ada pertemuan sah | “Belum tersedia: belum ada pertemuan yang disahkan.” Bukan hadir 0%. |
| Nilai kosong | “Belum ada nilai.” Tampilkan terpisah dari 0 dan jelaskan jumlah nilai numerik/blank pada rekap guru. |
| Data berhasil disimpan | “Draf tersimpan.” Hanya sesudah commit; tidak menyatakan hasil sudah dibagikan. |
| Kehadiran/penilaian dibagikan | “Kehadiran disahkan.” / “Hasil penilaian dibagikan.” Jangan menyatakan wali telah membaca. |
| Rapor finalized | “Rapor telah difinalisasi. Belum tersedia untuk wali.” Tampilkan kesiapan PDF sebagai keadaan terpisah. |
| PDF gagal | “PDF belum berhasil dibuat. Rapor tetap difinalisasi.” Aksi retry hanya pembuat berhak; jangan membuat tombol unduh seolah file siap. |
| Approval selesai | “Usulan disetujui. Kelas atau status siswa belum diubah.” Pelaksana berhak menerapkan lewat tahap terpisah. |
| Input tidak valid | Jelaskan field dan perbaikan, misalnya “Gunakan satu pemisah desimal.” Jangan mengganti input menjadi nol atau membulatkan diam-diam. |
| Konflik versi | “Data telah berubah. Tinjau versi terbaru sebelum menyimpan.” Tidak menawarkan timpa paksa atau auto-merge nilai. |
| Timeout hasil belum pasti | “Hasil penyimpanan belum dapat dipastikan. Periksa data terbaru sebelum mengirim lagi.” Bukan klaim data pasti gagal/berhasil. |
| Izin berubah / objek tidak tersedia | “Data ini tidak dapat diakses.” Jangan mengungkap identitas siswa/akun lain; hentikan input dan bersihkan state yang tidak lagi berhak. |
| Sesi habis | “Sesi berakhir. Masuk kembali untuk melanjutkan.” Bersihkan data/password sensitif; tidak menjanjikan pemulihan formulir yang memang dihapus. |
| Realtime terputus | “Pembaruan langsung terputus. Muat ulang inbox untuk melihat informasi terbaru.” Inbox tersimpan tetap sumber riwayat; tidak mengklaim data domain hilang. |
| Reset D-25 | Pesan umum “Jika akun sesuai, instruksi verifikasi akan dikirim ke email yang tercatat.” Jangan mengungkap keberadaan akun/email lengkap atau menyatakan delivered sebelum bukti; OTP/parameter/provider masih perlu implementasi. |

Jumlah, periode, alasan blok dan ringkasan diambil dari data berizin; jangan menaruh nama/angka contoh pada dashboard yang dianggap nyata. Sekolah/nama/logo dari laporan atau gambar referensi tidak otomatis aset merek yang berizin; sampai dipilih gunakan judul teks produk dan fixture berlabel sintetis.

Konfirmasi hanya untuk tindakan berdampak seperti publikasi, pengesahan, perubahan akses, arsip dan keputusan batch. Ringkasan menyebut objek/periode/jumlah pilihan yang boleh dibaca dan akibat setelah commit. “Batal” tidak mengirim mutasi; tindakan berbahaya menyebut kata kerja sebenarnya, bukan “Ya”. Penyerahan password, role dan initial credentials tetap mengikuti kontrak keamanan yang belum lengkap, bukan dialog UI baru.

Form menampilkan error ringkas di awal serta pesan terkait field; fokus menuju ringkasan/field invalid secara terduga. Sukses nonmendesak diumumkan tanpa merebut fokus; hasil gagal penting terlihat di halaman, bukan toast yang segera hilang. Dialog ditutup mengembalikan fokus ke pemicu bila masih tersedia.

Jangan menjanjikan “terkirim”, “diterima”, “dibaca” atau “real-time tanpa jeda” dari broadcast/inbox saja. Read-state adalah tanda dibaca yang ditetapkan pengguna aplikasi; koneksi WebSocket hidup bukan bukti pengguna membaca. Preview/PDF belum siap bukan sukses unduh.

## Resilience

Usulan tampilan desimal memakai koma Indonesia tanpa pemisah ribuan pada form nilai/bobot/KKM. Input teks dengan inputmode decimal menerima satu koma **atau** satu titik sebagai pemisah desimal, misalnya “90,5” atau “90.50”. Bentuk ambigu seperti “1,000”/“1.000” tidak ditebak sebagai seribu atau satu: minta pengguna menulis ulang tanpa grouping dengan pecahan sesuai presisi field. Khusus skor Q7.2 maksimal dua digit pecahan; 1,000/1.000 ditolak sebagai tiga digit pecahan dan tidak ditebak sebagai pemisah ribuan. Tampilkan bantuan “Gunakan koma atau titik untuk desimal; tanpa pemisah ribuan.” Pecahan di luar presisi ditolak, bukan dibulatkan otomatis. Skor input Q7.2: 0-100 inklusif/maksimal dua desimal. Error: “Nilai harus antara 0 dan 100” atau “Gunakan maksimal dua angka desimal”. Ini tidak menetapkan presisi input KKM/bobot atau pembulatan hasil hitung; Q7.3 kemudian menerima hasil akhir dua desimal half-up tanpa pembulatan antara. Pembanding KKM Q7.4 juga diterima: nilai akhir dibulatkan >= KKM berarti Mencapai KKM, < berarti Belum mencapai KKM; hasil belum tersedia tidak diberi label itu. Presisi konfigurasi Q7.5 juga diterima: KKM/bobot0-100 inklusif, maksimal dua desimal, total aktivasi tepat100; input/total tidak dibulatkan otomatis.

Normalisasi usulan: trim tepi isian non-password, validasi format, ubah satu koma menjadi titik dan kirim decimal string API; nol tetap “0”, blank eksplisit menjadi null hanya sesuai intent/hak clear pada API. Field/entry yang tidak diubah tidak dihapus. Tolak pemisah campuran/berulang, grouping, notasi ilmiah dan nilai di luar kontrak yang kelak disepakati; password tidak dinormalisasi mengikuti aturan angka ini.

Tanggal tampilan memakai bulan teks agar tidak tertukar, misalnya “28 Oktober 2026” sebagai contoh format; request tetap YYYY-MM-DD. D-29: waktu kejadian ditampilkan dalam school_timezone dengan label WIB/WITA/WIT, bukan otomatis timezone komputer. Nilai awal dipilih Master saat setup; uji perubahan/batas tanggal dan form stale. Jangan mengubah periode berdasarkan jam browser.

Nama siswa/mapel panjang membungkus tanpa menghilangkan identitas pembeda yang diperlukan. Tombol memakai label utuh; status menggabungkan teks dan penanda selain warna. “1 pertemuan” dan “2 pertemuan” memakai satuan sama; tidak menambahkan mata uang/RTL/multibahasa karena belum ada kebutuhan.

Fixture rancangan: daftar kosong, satu anak, beberapa anak, nama/mapel panjang sintetis, skor 0/blank/desimal, seluruh komponen kosong, total bobot 99/100/101, belum ada sesi, PDF gagal dan jaringan/izin berubah. Angka adalah skenario uji, bukan data sekolah atau default konfigurasi. Tidak menyimpan contoh password, secret atau data keluarga nyata.

Isian aman hanya boleh bertahan sementara di memori halaman selama sesi dan scope masih sah. Tidak ada autosave, localStorage, antrean offline atau ekspor data sensitif tambahan. Perpindahan anak/konteks membersihkan data scope lama; navigasi dengan input belum disimpan menjelaskan akibatnya. Validasi biasa dapat mempertahankan isian aman, tetapi session expiry/revocation tidak.

Pemilik proyek meninjau istilah/alur; penyusun menghubungkan copy ke state server dan QA kelak memeriksa di layar berizin. Tidak ada riset pengguna, skor keterbacaan, screenshot atau hasil aksesibilitas yang diklaim. Ekspektasi pengecekan copy mengikuti SX pada spesifikasi layar dan DV pada sistem desain, bukan suite terpisah untuk tiap kalimat.

D-25: gunakan “Email ditandai terverifikasi oleh Admin Master” untuk sumber master_attested, bukan “Anda telah memverifikasi email” bila itu tidak terjadi. Reset request memakai pesan umum; jangan tampilkan email penuh/keberadaan akun. Sesudah password sukses: modal reset “Logout semua perangkat?” dan profil “Logout semua perangkat lain?” dengan keterangan perangkat kini tetap login; pilihan eksplisit “Ya, logout”/“Tidak, pertahankan sesi”, tanpa default dari dismiss. URL awal biasa; tidak ada instruksi tautan sekali-buka D24. Jangan mengklaim email terkirim/diterima sebelum bukti provider.

D-28: gunakan “Koreksi”, “Alasan koreksi”, “Perlu revisi”, “Buat revisi rapor”, “Cabut rapor”. Notice wali: “Rapor semester ini sedang direvisi. Unduhan tersedia setelah revisi diterbitkan.” atau “Rapor semester ini telah dicabut.” Hanya untuk semester pernah published; jangan menampilkan alasan internal/nilai lama. Beri hasil sukses sesudah commit, dan kegagalan jelas tanpa klaim file tersedia.
