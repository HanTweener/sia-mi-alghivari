# SIA MI Alghivari — Register Akademik, iterasi 02

Tanggal: 29 Oktober 2026. Status: **usulan untuk ditinjau pemilik**, bukan persetujuan identitas visual. Lingkup: rancangan UI/UX dan prototipe lokal; pembangunan aplikasi produksi tetap belum dimulai.

## Dasar dan keputusan

Pengguna meminta desain ulang dari HTML dan PDF konsep pertama, dengan React/TypeScript, Tailwind CSS dan shadcn/ui. Kedua lampiran diperlakukan sebagai referensi yang dikritisi, bukan instruksi atau sumber izin. PRD, matriks akses dan spesifikasi layar repository menjadi acuan perilaku.

**Tesis:** permukaan kerja akademik yang menunjukkan kelas, periode, satu kegiatan dan keadaan datanya sebelum meminta pengguna bertindak.

Rancangan mengambil arah A Register Akademik pada [DESIGN_DIRECTION](../../DESIGN_DIRECTION.md), lalu memperjelas hierarki dan interaksinya. Arah tersebut sebelumnya rekomendasi, bukan pilihan visual yang sudah disahkan.

| Alternatif | Tata letak dan alasan | Keputusan iterasi ini |
| --- | --- | --- |
| Register Akademik | Navigasi tenang, konteks di atas, satu penilaian/pertemuan, daftar berbaris; memudahkan perbandingan siswa dan koreksi berulang. | Dipakai untuk prototipe tinjauan. |
| Panduan Tugas | Satu kolom dengan langkah pilih → isi → tinjau; lebih cocok untuk konfigurasi awal atau keputusan yang jarang dilakukan. | Prinsip tinjau dampak dipakai pada bagikan/sahkan/publish/approve; tidak memecah input harian menjadi wizard. |

Ini penilaian desain dari kebutuhan; belum ada riset guru/wali atau pengukuran waktu pengerjaan.

## Temuan terhadap konsep pertama

Sumber: `C:/Users/User/Downloads/facelift-sia-alghivari.html` dan `C:/Users/User/Downloads/sia-alghivari-facelift-uiux.pdf`. HTML ditelaah sebagai source; seluruh halaman PDF diekstrak dan dirender untuk tinjauan komposisi, dengan halaman beranda guru diperiksa lebih besar. Ukuran teks source berikut bukan pengukuran teks screenshot PDF.

| ID | Bukti spesifik | Dampak | Perbaikan |
| --- | --- | --- | --- |
| F01 | PDF hlm. 3 / `teacherDashboard`: hero sapaan, empat kartu metrik, dua panel dan kartu kelas mendahului pekerjaan input. | Terlalu banyak elemen setara; pengguna harus mencari draf yang ingin dilanjutkan. | Default Mengajar membuka daftar pekerjaan atau aktivitas terakhir yang sah; prototipe langsung menunjukkan satu penilaian. |
| F02 | CSS source memakai banyak teks 10–12px dan warna pucat seperti `#93a498`; PDF hlm. 5 merangkum form sebagai panel kecil berlapis. | Bantuan/status sukar dibaca dan mendominasi melalui jumlah panel, bukan kejelasan. | Isi utama 16px, metadata 14px, teks sekunder gelap; aturan pemisah dan ruang antarbagian menggantikan kartu berulang. |
| F03 | `teacherGrade` mengunci publikasi ketika ada blank dan menyebut publikasi otomatis. | Blank diperlakukan sebagai kegagalan, bertentangan dengan SCR-06. | Bagikan eksplisit melalui tinjauan; blank sebagian tetap boleh dan dijelaskan. Seluruh komponen berbobot positif tanpa data menghalangi finalisasi rapor, bukan otomatis semua publikasi penilaian. |
| F04 | `gradeTable` berupa banyak penilaian dan kotak angka visual; beberapa kontrol hanya menampilkan catatan/toast. | Tampak selesai tetapi tidak membuktikan alur pencatatan. | Input sungguhan satu penilaian, pencarian/filter sungguhan, simpan memori demo dan tinjauan dampak yang dapat diuji. |
| F05 | `teacherAttendance` menulis 27/28 dan “Tersimpan sebagai contoh”, sementara hanya lima baris ditampilkan. | Ringkasan tidak mengikuti interaksi yang sedang dilakukan. | Semua hitungan diturunkan dari fixture yang benar-benar ada; perubahan belum disimpan dibedakan dari snapshot simpan demo. |
| F06 | `parentDashboard`, PDF hlm. 9/12: slogan, metrik dan kalender lebih menonjol daripada aktivitas anak; tidak ada jalur Rapor Online terpisah. | Wali sulit membedakan informasi berjalan dengan dokumen semester. | Identitas anak → aktivitas dibagikan/disahkan → menu Rapor Online tersendiri, dengan empty state yang tetap mengarah ke perkembangan. |
| F07 | `academicDashboard` menyatakan Akademik tidak boleh mengubah angka nilai; `waliDashboard` menggeneralisasi baca saja. | Hak produk disempitkan secara keliru. | Ikuti matriks: Akademik CRUD domain akademik sesuai keadaan; wali kelas tidak mengedit nilai guru lain tetapi boleh memublikasikan rapor binaan setelah lengkap. |
| F08 | Role contoh melewatkan Data Entry dan kepala sekolah; monogram `A.` ditampilkan sebagai merek. | Cakupan aktor tidak utuh; asset contoh dapat disalahartikan sebagai identitas resmi. | Spesifikasi seluruh aktor tetap tersedia; prototipe hanya menyertakan jalur yang benar-benar bekerja. Identitas memakai nama teks sekolah. |

Penggunaan kartu atau warna hijau sendiri bukan temuan cacat. Persoalannya adalah hierarki, keterbacaan, aturan domain dan perilaku kontrol yang terlihat pada referensi ini.

## Sistem visual

Warna berikut kandidat prototipe, bukan palette resmi sekolah. Tidak memakai foto siswa, logo rekaan, font unduhan atau ilustrasi dekoratif.

| Token | Nilai | Penggunaan |
| --- | --- | --- |
| canvas | `#F4FBF7` | Latar bernuansa mint |
| surface | `#FFFFFF` | Form, daftar dan konteks kegiatan |
| text | `#123B30` | Judul dan isi |
| muted | `#47675C` | Metadata yang tetap terbaca |
| primary | `#047857` | Tindakan utama dan teks status positif |
| primary-soft | `#D1FAE5` | Hover dan aksen isi; header tabel putih |
| border | `#D2E5D9` | Pemisah bagian; input memakai border lebih tegas |
| sidebar | `#EDF8F1` | Navigasi mint dengan teks hijau gelap |
| sidebar-active | `#047857` | Pilihan aktif zamrud penuh dengan teks putih |
| warning text | `#92400E` | Blank/belum lengkap dengan penjelasan teks |
| warning surface | `#FEF3C7` | Pesan perhatian |
| success surface | `#BBF7D0` | Feedback berhasil, teks `#166534` |
| danger | `#BE123C` | Error, disertai label dan cara memperbaiki |

Font sistem `ui-sans-serif, system-ui, sans-serif`; angka tabular rata kanan. Judul halaman 24px/30px, ponsel 20px/25px; judul bagian 18px, isi/input 16px, metadata 14px/20px. Nama siswa dapat membungkus. Tidak ada teks kritis di bawah 14px. Radius kontrol 6px, dialog 8px; border tipis dan bayangan hanya pada lapisan overlay. Jarak menggunakan kelipatan 4px: 8/12/16/24/32.

Rebuild skill 29 Oktober 2026 memakai Taste Accent ladder, seed 879545572, dengan warna hijau dan font sistem dipertahankan. Sidebar desktop 240px berada pada bidang mint, pilihan aktif zamrud penuh; ponsel/tablet memakai menu dialog. Daftar putih menjadi fokus; baris desktop sekitar 64px dengan kontrol 44px. Keterangan demo dapat dibuka, bukan mengambil banyak ruang sebelum isi. Detail keputusan dan bukti: [SKILL_REBUILD](SKILL_REBUILD.md). Pembaruan warna sebelumnya merupakan riwayat pada [COLOR_REFRESH](COLOR_REFRESH.md).

## Navigasi aplikasi yang dituju

| Konteks berhak | Menu tugas | Batas penting |
| --- | --- | --- |
| Mengajar | Pekerjaan mengajar, Penilaian, Kehadiran, Jadwal, Siswa diajar | Berdasarkan penugasan mapel/kelas/periode. |
| Wali kelas | Kelas binaan, Rekap, Persiapan rapor | Boleh publish rapor binaan; tidak mengedit nilai pengajar lain. |
| Administrasi Akademik | Direktori akademik, Periode & kelas, Jadwal & penugasan, Aturan penilaian, Persiapan rapor, Usulan kenaikan | Grant CRUD domain mengikuti matriks; pengangkatan role/jabatan bukan biodata. |
| Data Entry | Data operasional, Input yang didelegasikan | Tanpa D/konfigurasi/P; delegasi membatasi objek, tindakan dan masa berlaku. |
| Kepala sekolah | Ringkasan akademik, Tinjau usulan naik/lulus | Approve/reject terpisah dari apply; tugas mengajar terpisah jika juga guru. |
| Master | Akses & akun, Pengaturan sistem, Akademik, Audit | Tanpa CUD audit atau bypass approval principal. |
| Portal wali | Perkembangan Belajar, Rapor Online | Hanya anak terkait; aktivitas berjalan tidak bergantung pada rapor. |

Inbox dan profil mandiri merupakan utilitas semua konteks, dengan akses sesuai proyeksi server. Pemilih konteks hanya menampilkan grant yang benar-benar dimiliki. Untuk prototipe, pemilih skenario ditandai sebagai alat tinjauan dan tidak dianggap login atau kontrol otorisasi.

Jangan menampilkan menu yang berujung ke layar kosong atau berpura-pura selesai. Prototipe iterasi ini membatasi navigasi aktif pada Penilaian, Kehadiran, Perkembangan Belajar dan Rapor Online. Keluarga lain di bawah tetap spesifikasi, belum implementasi.

## Empat permukaan prototipe

### Penilaian — SCR-06

Urutan: konteks → judul kegiatan/komponen/tanggal → status draf → pencarian dan filter belum ada nilai → daftar siswa → ringkasan perubahan dan tindakan. Desktop tabel native dengan nomor, nama, input nilai dan keadaan. Ponsel setiap siswa menjadi satu baris terstruktur bertumpuk; input tetap berlabel nama siswa, bukan tabel yang diperkecil.

Fixture sintetis menyertakan nama panjang, angka 0, desimal dan blank. Hitungan total/terisi/blank mengikuti seluruh fixture; hitungan hasil pencarian diberi label berbeda. Filter tidak menghapus isian. Input menerima satu koma atau titik desimal, tanpa grouping; rentang 0–100/presisi contoh dibatasi dan diberi keterangan sebagai asumsi demo karena Q7 belum final.

“Simpan draf” hanya menyimpan salinan dalam memori prototipe; feedback menyebut simulasi. “Bagikan hasil” membuka review jumlah terisi/blank dan konteks. Blank sebagian tidak mengunci publikasi. Review tidak sama dengan finalisasi rapor. Setelah hasil dibagikan, prototipe tidak mengarang lifecycle koreksi published yang belum disepakati.

Perubahan belum tersimpan memicu konfirmasi ketika pindah konteks/layar. Validasi format menunjuk siswa terkait. Filter tetap dapat diubah tanpa membuang isian. Tidak ada autosave, localStorage, impor spreadsheet atau timpa paksa.

### Kehadiran — SCR-05

Satu mapel, satu pertemuan dengan tanggal dan kelas eksplisit. Setiap siswa memilih Hadir/Izin/Sakit/Alpa; awal dapat belum dicatat. Pilihan status dan ringkasan terisi mengikuti fixture. Tidak ada tandai semua hadir. Ponsel memakai kontrol native yang cukup lapang, tanpa deretan chip mini.

Simpan draf terpisah dari sahkan. Pengesahan mengharuskan seluruh status terisi dan review eksplisit. Feedback tetap menyebut demo. Kehadiran dihitung per pertemuan mapel yang disahkan; draf tidak masuk rekap wali. Koreksi sesi disahkan belum disimulasikan sebagai edit bebas.

### Perkembangan Belajar — SCR-07

Nama anak dan kelas ditempatkan sebelum aktivitas. Pemilih anak mengubah isi fixture dan konteks, bukan sekadar nama header. Tampilkan tanggal, mapel, judul kegiatan, nilai atau status pertemuan yang dibagikan/disahkan. Filter mapel benar-benar memfilter daftar. Tidak menampilkan draf internal, ranking, grafik dekoratif atau nilai semester buatan.

Perkembangan selalu dapat dibuka meskipun rapor belum ada. Jika ada ringkasan berjalan, labelnya Sementara, dengan sumber dan satuan jelas. Hindari mengartikan belum ada aktivitas sebagai nilai 0 atau alpa. Nama anak fixture tidak diambil dari laporan historis.

### Rapor Online — SCR-09

Menu terpisah. Empty state: “Rapor belum tersedia” dan akses kembali ke Perkembangan Belajar. Jangan menyebut jumlah draft/finalized/revoked kepada wali. Jangan menyediakan tombol unduh palsu. Versi published dan unduhan berizin merupakan spesifikasi integrasi produksi, bukan keberhasilan prototipe tanpa backend.

## Cakupan keluarga layar lain

| Keluarga | Rancangan yang harus diterapkan kelak | Keadaan penting |
| --- | --- | --- |
| SCR-01 Masuk/profil | Form username/password tunggal, akun disediakan sekolah; profil tanpa edit role/anak/penugasan | Error aman, sesi habis, password manager, validasi profil. Recovery belum menjanjikan metode yang belum diputuskan. |
| SCR-02 Pekerjaan/konteks | Daftar kegiatan berizin, konteks rangkap eksplisit; jumlah berasal query scope | Tidak ada penugasan/anak, periode belum aktif, perubahan konteks dengan draf. |
| SCR-03 Administrasi | Direktori dengan pencarian/pagination; detail dan form satu kolom; penugasan dipisah dari biodata | Filter kosong, duplikasi, konflik jadwal, grant belum aktif; Q6 tetap terbuka. |
| SCR-04 Aturan global | Tabel komponen pendek, total bobot 100%, KKM dan revisi global | Total invalid, sumber stale, review dampak aktivasi; guru hanya baca, tanpa override. |
| SCR-08 Persiapan rapor | Daftar kesiapan siswa/mapel, alasan penghambat dekat baris, review snapshot | Finalisasi → siapkan PDF → publish terpisah; file gagal, sumber/policy berubah, komponen positif kosong. |
| SCR-10 Naik/lulus | Daftar usulan dengan pilihan page-only, preview dampak, review keputusan | Approve/reject tidak apply; batch invalid ditolak seluruhnya; perubahan sumber meminta review baru. Pelaksana Q6 belum final. |
| SCR-11 Inbox/audit | Daftar kejadian berteks dan tujuan scoped; audit tidak dapat diedit | Loading/error/kosong berbeda, read-state server, reconnect catch-up; izin tujuan dicabut. |

## Keadaan, aksesibilitas dan ukuran

Kontrol interaksi minimum 44px untuk sasaran produk. Fokus terlihat, skip link, satu h1, landmark nav/main, label input per siswa, `<th scope>` tabel, error terhubung ke input, status nonmendesak melalui live region. Dialog memiliki judul/deskripsi, fokus terkurung, Escape dan pengembalian fokus. Enter pada input tidak memublikasikan/mengesahkan secara implisit.

Desktop >1100px: navigasi samping dan tabel input. Tablet ≤1100px: menu dialog dan area kerja mengisi lebar. Ponsel ≤760px: daftar siswa satu kolom, tindakan dapat membungkus. Kajian visual memakai batas 900px untuk navigasi dan 640px untuk tabel statis. Reflow 320px tidak memerlukan scroll horizontal halaman. Hindari sticky footer menutup input/keyboard. Tidak mengandalkan hover/drag atau warna saja. Hormati reduced motion.

| Keadaan | Perlakuan |
| --- | --- |
| Loading | Konteks tetap, status memuat; tidak menganggap data lama milik anak/kelas baru. |
| Kosong | Bedakan belum ada data, hasil filter kosong dan rapor belum terbit. |
| Validasi | Pesan per field + ringkasan, pertahankan isian yang masih berhak diakses. |
| Gagal simpan | Jangan memperbarui label tersimpan; tawarkan retry eksplisit bila hasil pasti gagal. |
| Konflik | Jelaskan versi berubah; baca ulang keadaan terbaru, tanpa force overwrite. |
| Timeout belum pasti | Periksa keadaan server sebelum kirim ulang; tidak auto-retry POST. |
| Akses dicabut/sesi habis | Bersihkan isian dan data sensitif, hentikan edit. |
| Sukses | Hanya setelah commit server pada aplikasi kelak; prototipe menyebut simulasi memori secara eksplisit. |

Pemilih keadaan dalam prototipe adalah alat QA. Tampilan error/loading sintetis bukan bukti jaringan atau keamanan telah diuji. Lihat laporan QA untuk keadaan yang benar-benar dieksekusi.

## Pemetaan React/TypeScript, Tailwind dan shadcn/ui

Pisahkan fixture dan state demo dari komponen tampilan. Gunakan komponen UI lokal shadcn/ui mengikuti provenance yang dicatat di source, dengan variant pada komponen reusable. Tabel tetap HTML semantik; jangan menambahkan mesin datagrid untuk delapan baris. Tailwind memetakan token tema ke utility, termasuk responsive dan state. Arahan pemilik 29 Oktober 2026: CSS global mengikuti dokumentasi Tailwind/shadcn; CSS manual hanya bila kebutuhan tidak ditemukan pada dokumentasi keduanya. Detail implementasi dan pengecualian: [STYLING_CONVENTIONS](STYLING_CONVENTIONS.md).

| Komponen aplikasi | Tanggung jawab | Batas integrasi kelak |
| --- | --- | --- |
| AcademicShell / ContextHeader | Navigasi dan konteks kegiatan | Grant/konteks berasal server, bukan daftar role hardcode sebagai izin. |
| ScoreRoster / AttendanceRoster | Form satu kegiatan; label dan kesalahan per siswa | Decimal string/null/omitted mengikuti intent API; clear existing memerlukan D. |
| ReviewDialog | Review dampak satu tindakan | Tombol konfirmasi memanggil command berizin dengan versi sumber. |
| ChildContext / ProgressList | Aktivitas anak dipilih | Data dari query scoped published/finalized, bukan filter rahasia di browser. |
| ReportAvailability | Ketersediaan versi published | Unduh route aplikasi privat, bukan path storage publik. |
| DemoStateControl | Fixture dan keadaan tinjauan | Hanya paket prototipe; tidak dibawa ke aplikasi produksi. |

Referensi teknis yang diperiksa: [Tailwind dengan Vite](https://tailwindcss.com/docs/installation/using-vite), [instalasi manual shadcn/ui](https://ui.shadcn.com/docs/installation/manual), [shadcn/ui Tailwind v4](https://ui.shadcn.com/docs/tailwind-v4). Vite hanya harness prototipe; tidak mengubah blueprint Laravel/Inertia aplikasi.

## Kriteria tinjauan dan batas

Penerimaan prototipe menuntut input/counter/search/filter berjalan, blank berbeda dari 0, simpan dan bagikan terpisah, review keyboard, pergantian anak nyata, rapor kosong tidak menutup perkembangan, serta layout desktop/ponsel yang diperiksa. Evidence aktual, hash source dan sisa keterbatasan dicatat pada QA; target WCAG 2.2 AA bukan sertifikasi.

Belum mencakup implementasi server, transaksi/audit, autentikasi, izin objek, koneksi realtime, PDF rapor resmi, pagination data besar, uji pembaca layar/perangkat fisik, atau riset pengguna. Q6/Q7, AR-04/05, format rapor, timezone sekolah, logo resmi dan persetujuan visual tetap terbuka. Prototype tidak mengubah keputusan kanonis tersebut.
