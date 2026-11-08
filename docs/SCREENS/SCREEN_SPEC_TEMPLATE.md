# Screen Specification — SIA MI Alghivari

Diperbarui: 29 Oktober 2026. Pemilik keputusan: pemilik proyek. Status **PROPOSED / blueprint**; 11 keluarga layar SCR-01..11 untuk aplikasi inti yang belum diimplementasikan. [Facelift-v2](../support/facelift-v2/README.md) mempunyai empat layar interaktif dan tujuh kajian visual sebagai **referensi, bukan panduan**. Simpan/bagikan/sahkan di prototipe hanya mengubah memori tab; belum ada bukti server untuk kontrak layar ini.

Acuan: [PRD](../PRD.md), [alur](../USER_FLOWS.md), [fitur/37 AC](../FEATURES/FEATURE_SPEC_TEMPLATE.md), [izin](../ROLE_PERMISSION_MATRIX.md), [API](../API_CONTRACT.md), [copy](../CONTENT_STYLE.md), [arah desain](../DESIGN_DIRECTION.md), [sistem desain](../DESIGN_SYSTEM.md) dan [sesi](../SESSION_TOKEN_SECURITY_SPEC.md). Screenshot/source/evidence prototipe tersedia dalam paket referensi; belum ada Figma atau hasil riset pengguna.

## Purpose & hierarchy

Usulan arah “Register Akademik”: permukaan terang hangat, emerald terbatas untuk penanda/aksi, teks slate, sidebar desktop dan bar konteks akademik; tabel/form menjadi isi utama. Branding, warna dan tipografi belum disetujui pemilik. Gunakan font sistem dan identitas teks sampai aset resmi dipilih; tidak menyalin logo dari referensi. Tidak membuat hero, grafik atau kartu dekoratif pada portal kerja.

Hierarki setiap halaman: judul tugas → konteks periode/kelas/mapel/anak → data/keputusan utama → aksi berizin → bantuan/alasan blok. Beranda bukan pengganti daftar kerja. Role rangkap memakai pemilih konteks navigasi; server tetap menentukan grant untuk objek/field/state.

Path di bawah hanya route yang sudah dirancang pada API_CONTRACT. Rute halaman kerja yang belum disebutkan tetap TERBUKA, bukan dibuat dari nama SCR. Nama ID layar tidak menjanjikan satu endpoint atau satu komponen raksasa.

### SCR-01 · Masuk dan profil sendiri · F-01

**Tugas:** masuk dengan username/password atau memperbarui profil milik sendiri. Subview login dan profil dipisah secara navigasi, tetap satu keluarga SCR-01.

**Entry/data:** GET/POST /login; GET/PATCH /profile; PUT /profile/password; POST/DELETE /profile/avatar. Login menampilkan dua field, bantuan password manager dan aksi “Masuk”; tidak ada pilihan role atau registrasi. Profil menampilkan identitas/kontak yang diizinkan, foto dan bagian ganti password yang terpisah.

**Aksi/hasil:** Simpan profil/foto sesuai allowlist; ganti password: sesi aktif -> OTP email tercatat -> password baru/konfirmasi -> berhasil disimpan -> modal pilihan logout/tidak wajib. Profil current tetap login; logout menyasar semua sesi lain. Reset memakai username/email -> OTP -> password -> modal -> login biasa. Temporary login hanya forced-change. [Lifecycle](../RECOVERY_AND_STEPUP_PLAN.md).

**Batas:** D25 reset OTP email dipilih tetapi belum aktif/teruji; parameter/provider belum final, tanpa klaim email palsu. Temporary login belum boleh domain. Username/kontak/foto invalid memberi error; password tidak dipertahankan pada error/flash/sesi habis. Pending modal sesudah sukses bukan alasan meminta ulang password.

### SCR-02 · Beranda dan pemilih konteks · lintas fitur

**Tugas:** menemukan pekerjaan nyata yang sesuai peran serta konteks tahun/semester. Entry sesudah login; route beranda final belum ditetapkan pada kontrak.

**Isi:** identitas minimum, konteks aktif, tautan daftar pekerjaan yang berhak seperti input mengajar, rekap binaan, keputusan principal atau pilih anak. Jumlah hanya dari query scoped; bila data belum ada jelaskan keadaan kosong, tanpa angka contoh/grafik aktivitas buatan.

**Interaksi:** konteks Administrasi/Mengajar/Wali kelas/Kepala sekolah/Portal wali tersedia menurut grant; pilihan tampilan bukan pemberian hak. Ubah periode/kelas/anak memperbarui judul dan data; hasil lama tidak ditampilkan seolah milik konteks baru.

### SCR-03 · Akademik, direktori dan administrasi · F-01/F-02/F-03

**Tugas:** menyiapkan periode, data operasional, penugasan dan jadwal melalui daftar/form terfokus, bukan satu form seluruh sekolah.

**Entry/data:** collection /academic-years, /terms, /classrooms, /subjects, /students, /teachers, /guardians, /enrollments, /schedule-slots, /teaching-assignments dan /homeroom-assignments; administrasi /users serta /system-settings khusus Master. Hubungan wali lewat /students/{student}/guardians. Detail route mengikuti API, bukan grant seluruh collection kepada semua role.

**Hierarki:** filter periode/kelas dan pencarian → daftar berkolom minimum → detail/form objek → aksi yang berlaku. Guru/principal/wali mendapat proyeksi baca relevan, bukan halaman /users Master; informasi profesional wali kelas tidak memuat alamat rumah/hash/identitas privat.

**Aksi/hasil:** MA mengelola akademik; D hanya tambah/perbarui bidang operasional yang diizinkan, tanpa hapus/arsip/konfigurasi. Master mengubah role/jabatan dan aktivasi akun; tidak menyamar melalui biodata. Aktivasi/penutupan term serta penggantian wali kelas memakai aksi eksplisit dan ringkasan dampak.

**Penugasan:** tampilkan guru mapel dan wali kelas sebagai relasi berbeda; wali kelas dari homeroom_assignments, bukan teacher_id pada classroom. Histori inactive berlabel tidak aktif dan tidak memberi akses. Jadwal bentrok atau parent berbeda semester ditolak.

**Keputusan D-25:** akun baru/email langsung verified oleh Master dan password sementara emailed, URL website biasa; forced-change gate server tanpa domain/file/channel. D-23/D-24 superseded. OTP input mendukung paste/autocomplete, label dan error keyboard; TTL/limit belum final. Modal setelah commit wajib, tak ada default pilihan dari Esc/backdrop; restore pending setelah refresh/response hilang tanpa meminta ulang password yang telah sukses. Reset tombol lanjut ke login, profil kembali ke halaman tanpa logout current.

**Keputusan Q6.1/Q6.3:** Master akun wali/role/aktivasi dahulu, D biodata/link; M/A grant/revoke delegasi. D-25 akses/password dipilih; allowlist/delegasi/penugasan/apply dan parameter/provider/fallback integrasi belum final. Modal password spesifik, bukan engine workflow umum.

### SCR-04 · Konfigurasi penilaian global · F-04

**Tugas:** MA menyiapkan katalog komponen, KKM dan bobot satu revisi global. Entry /scoring-components, /grading-policies; aktivasi /grading-policies/{policy}/activate.

**Isi:** label “Berlaku untuk seluruh mata pelajaran”, versi/status aktif, KKM, baris komponen sumber score/attendance, bobot dan total. Komponen awal tugas, ujian harian, UTS, UAS, kehadiran otomatis; tidak memuat selector override kelas/mapel/guru.

**Aksi/hasil:** simpan draft revisi dan “Aktifkan kebijakan” ketika total tepat 100%; bobot 0 sah. Revisi pernah aktif menjadi baca saja, perubahan melalui versi baru. Guru hanya membaca konfigurasi dalam konteks berhak; bukan form pengaturan.

**Error:** total 99/101 pada fixture diberi alasan eksplisit; input bukan diubah otomatis agar mencapai100. Konflik aktivasi meminta tinjau ulang. Q7.5 KKM/bobot0-100 inklusif/maksimal dua desimal diterima. Tampilkan total tepat dan error field/baris; pecahan berlebih tidak dipotong/dibulatkan, draft belum100 tidak aktif. Ikuti CONTENT_STYLE/API.

### SCR-05 · Sesi kehadiran per mapel · F-05

**Tugas:** guru membuka satu sesi lalu mengisi roster kelas yang diajar; D hanya membantu draft jika delegasi aktif. Entry /class-subjects/{classSubject}/attendance-sessions dan detail /attendance-sessions/{session}.

**Isi:** kelas/mapel/periode, nomor/tanggal/topik, state draft/finalized; tiap siswa memilih hadir/izin/sakit/alpa dengan label penuh. Awal belum dicatat, bukan alpa; tidak ada status terpilih otomatis atau fitur tandai semua hadir yang belum diminta.

**Aksi/hasil:** “Simpan draf” dan, hanya MA/G berhak, “Sahkan kehadiran”. Sahkan nonaktif dengan jumlah status belum terisi; D tidak mendapat finalize/delete. Counter menyebut pertemuan, bukan hari; draft tidak masuk rekap wali.

**Error:** konflik mempertahankan isian aman hanya selama scope sah untuk dibandingkan, tanpa timpa paksa. Null untuk clear existing record adalah DELETE domain; D tidak bisa melakukannya melalui kontrol kosong. Koreksi finalized menggunakan command D-28 beralasan/versioned untuk status peserta existing, tanpa edit roster/tanggal; bukan edit biasa.

### SCR-06 · Daftar nilai satu penilaian · F-06

**Tugas:** guru memilih komponen pusat, membuat assessment, mengisi satu kelas dan membagikan hasil. Entry /class-subjects/{classSubject}/assessments serta /assessments/{assessment} dan /assessments/{assessment}/scores.

**Isi:** judul/tanggal/komponen, konteks mapel/kelas/periode, draft/published, field skor per siswa dan feedback opsional. Tampilkan blank dengan “Belum ada nilai”, nol tetap 0; jumlah numerik/blank dan pembagi dijelaskan pada rekap yang berhak.

**Aksi/hasil:** “Simpan draf” terpisah dari “Bagikan hasil”. Published boleh blank sebagian; konfirmasi menjelaskan hasil yang dibagikan, tidak menyebut rapor diterbitkan. Tidak menambah mass publish banyak assessment. D delegasi hanya C/U draft; tidak ada hapus/publish atau clear skor existing.

**Input:** native input teks/inputmode decimal dan label siswa, navigasi Tab biasa; tidak membangun spreadsheet engine, shortcut panah khusus atau impor Ctrl+V. CONTENT_STYLE mengatur satu koma/titik → decimal string API, blank/omitted/0 dan validasi ambigu; batas skor input Q7.2 diterima: 0-100 inklusif/maksimal dua desimal; integer/satu desimal sah. Invalid diberi error field/baris tanpa pembulatan atau simpan sebagian. Q7.3 menerima hasil akhir dua desimal half-up dari total presisi penuh; jangan membulatkan rata-rata/kontribusi sebelum total atau menghitung hasil final dari angka tampilan. Q7.4 status memakai nilai akhir dibulatkan >= KKM; nilai/KKM/status berasal dari snapshot/server yang sama, hasil belum tersedia tidak ditampilkan gagal. Kehadiran bukan assessment nilai manual.

### SCR-07 · Perkembangan Belajar · F-07

**Tugas:** wali memilih anak lalu membaca aktivitas yang dibagikan/disahkan kapan saja selama periode, tanpa menunggu rapor. Entry /children, /children/{student}/progress dan /children/{student}/homeroom-contact.

**Isi:** identitas anak minimum, semester, filter mapel/komponen/tanggal, aktivitas nilai/kehadiran dan rekap berlabel sementara. Kontak wali kelas hanya profesional berizin; tidak ada edit nilai atau tombol finalisasi bagi wali.

**Kosong/hasil:** tanpa aktivitas terbit tampil penjelasan dan filter; tanpa rapor halaman tetap dapat dibuka. Assessment draft/sesi draft, aktivitas anak lain dan count terlarang tidak masuk respons/UI. Tidak mengubah data berjalan menjadi rapor sementara.

### SCR-08 · Persiapan dan publikasi rapor staf · F-08

**Tugas:** MA atau W aktif kelas binaan memeriksa seluruh mapel, membekukan snapshot, menyiapkan PDF dan memublikasikan. Entry /classrooms/{classroom}/report-preview, /report-cards dan /report-cards/{report}; command sesuai kontrak API.

**Isi:** siswa/periode, policy/calculation/source version, mapel/rincian komponen, ketersediaan data dan alasan blocked. Blank sebagian dilewati; komponen positif seluruh kosong atau hadir berbobot positif tanpa pertemuan memblokir finalisasi, bukan dibuat nol/redistribusi bobot.

**Catatan draft Q7.1:** W binaan aktif atau Master mengisi catatan opsional melalui PATCH report dengan expected_version/homeroom_note. Sediakan Simpan catatan dengan state loading/error/sukses, konflik meminta muat ulang dan tinjau tanpa menimpa. Bukan input skor; field hanya dapat diubah saat draft. Null mengosongkan catatan, catatan kosong tidak memblokir finalisasi; batas panjang menunggu kontrak sebelum implementasi.

**Aksi/hasil:** “Finalisasi rapor” → “Siapkan PDF” → “Publikasikan rapor”, dengan state dan alasan kesiapan yang berbeda. Finalized belum terlihat wali; PDF gagal membuat keadaan file belum siap dan retry untuk actor berhak, bukan unduh/publish palsu. Policy/sumber berubah meminta preview baru.

**Scope/histori:** W tidak mengedit nilai guru lain. Guru mapel hanya mendapat hasil mapel yang berhak; hak itu tidak otomatis memberikan PDF gabungan. Versi lama immutable; aturan angka Q7.2-Q7.5 diterima; revision/revoke D-28 dan D26/27 konsolidasi/frozen roster dipilih; detail visual cetak dan implementasi belum diverifikasi.

### SCR-09 · Rapor Online wali · F-08

**Tugas:** memilih semester/versi published/current aktif anak dan mengunduh dokumen. Entry /children/{student}/report-cards, /report-cards/{report}, /report-cards/{report}/pdf dengan policy anak/status.

**Isi:** semester, versi, tanggal publikasi dan aksi detail/unduh yang benar-benar tersedia. Susunan Q7.1 disepakati 29 Oktober 2026: identitas, hasil per mapel/KKM/status, kehadiran H/S/I/A/total pertemuan/persentase per mapel, catatan opsional dan penutup. Rincian komponen di aplikasi memakai snapshot revisi yang sama; tidak menambah predikat A-D, ranking, rata-rata keseluruhan atau deskripsi kompetensi. Request PDF memakai tautan browser biasa ke route aplikasi berizin; tidak mengekspos path storage atau menjadikan navigasi Inertia pembaca file.

**Kosong/gagal:** daftar tidak memuat draft/finalized/revoked atau jumlah versinya; “Rapor belum tersedia” diikuti tautan Perkembangan Belajar. File/izin gagal memberi pesan aman tanpa download dummy atau nama anak lain. Data operasional baru tidak mengganti snapshot dokumen lama.

### SCR-10 · Keputusan naik/lulus dan penerapan terpisah · F-09

**Tugas:** principal meninjau usulan lalu approve/reject; pelaksana dengan grant yang kelak disepakati menerapkan keputusan pada tahap lain. Entry /progressions dan detail /progressions/{progression}; command individual/batch dari API.

**Isi principal:** siswa/tahun sumber, rapor/revisi, jenis kenaikan/kelulusan, tujuan dan status. Aksi “Setujui usulan” / “Tolak usulan” meminta ringkasan, alasan penolakan dan current version; hasil approved menjelaskan kelas/status belum diubah.

**Penerapan:** ruang kerja/aksi “Terapkan keputusan” hanya untuk pelaksana berhak setelah Q6 final; jabatan principal saja tidak memberinya grant apply. Pengusul, waktu berlaku, cancel dan beberapa lifecycle masih terbuka; tidak menampilkan tombol aktif berdasarkan dugaan.

**Batch:** tidak ada pilihan awal; pilih per siswa atau “Pilih halaman ini” secara eksplisit. Jumlah terpilih selalu terlihat; tidak ada select-all lintas halaman/filter. Perubahan halaman/filter mengosongkan pilihan. Tinjau semua yang dipilih, lalu command all-or-nothing; satu invalid gagal seluruh batch, bukan sukses sebagian tersembunyi.

**Konflik:** source/target berubah menampilkan perlu tinjau ulang; apply ganda tidak membuat enrollment ganda. Tidak ada auto naik/lulus berdasarkan KKM, penghapusan histori atau tombol Master melewati approval principal.

### SCR-11 · Audit dan inbox · F-10/F-11

**Tugas:** membuka dua sumber berbeda: audit terfilter sebagai riwayat perubahan; inbox diri sebagai pemberitahuan. Entry /audit-logs, detail /audit-logs/{audit}, /inbox; JSON /notifications dan /notifications/unread-count untuk inbox.

**Isi/aksi:** audit menampilkan actor/waktu/action/objek/scope/diff aman tanpa tombol CUD, termasuk bagi Master. Inbox menampilkan ringkasan, waktu, read-state dan tujuan berizin; “Tandai dibaca” sesuai API, tanpa mark-unread/mark-all yang belum dipilih.

**Realtime:** sinyal Echo memicu fetch ulang, bukan menyalin data akademik dari socket. Reconnect mengejar inbox tersimpan; indikator offline tidak membuat riwayat kosong palsu. Notifikasi aktivitas menuju Perkembangan Belajar; rapor menuju Rapor Online.

**Scope:** draft internal/secret/jumlah objek lain tidak muncul pada log/count. Relasi/role dicabut menutup tujuan dan data tidak lagi ditampilkan; tujuan arsip hanya ringkasan netral bila masih berhak. Tidak ada credential, recovery link atau klaim bahwa penerima sudah membaca dari status socket.

## Layout at relevant widths

Ukuran berikut target rancangan untuk diverifikasi, bukan hasil responsive test. Komposisi mengikuti token/kontras/target interaksi pada DESIGN_SYSTEM yang masih usulan; viewport uji utama 360 × 800 dan 1440 × 900 mengikuti AC-X03. Nilai breakpoint final mengikuti implementasi yang kelak dipilih.

| Context | Navigation/priority | Layout & spacing behavior | Content overflow/long names | Actions |
| --- | --- | --- | --- | --- |
| Small phone | Drawer dengan konteks periode/anak tetap mudah ditemukan; judul tugas sebelum data | Satu kolom; input nilai/kehadiran berupa daftar siswa berlabel, bukan tabel lebar seluruh semester | Nama/mapel membungkus; data label-value tetap berpasangan. Layar tidak bergulir horizontal demi aksi rutin | Aksi utama setelah konteks/form; tidak menutup field/error/fokus. Dialog dapat digulir dan tombol batal terjangkau |
| Intermediate/tablet | Drawer/sidebar menurut ruang tersedia, konteks tidak hilang saat navigasi | Satu atau dua area bila isi cukup; tidak mengecilkan input agar tabel muat | Overflow lokal tabel hanya bila perlu, dengan petunjuk; nama lengkap tetap dapat dibaca tanpa hover | Kelompok aksi menumpuk bila sempit, label tidak dipotong menjadi ikon tanpa nama |
| Desktop | Sidebar persisten, bar konteks dan breadcrumb tugas; tanpa menu per-role yang menggandakan izin | Area kerja lebar terkontrol; tabel roster memakai header/row label native, form detail tidak melebar tanpa batas | Long text membungkus; scroll lokal tabel bila perlu, tidak menghilangkan identitas siswa/kolom | Aksi spesifik di dekat tugas, sekunder tidak menyaingi simpan; sticky area tidak menghalangi fokus/zoom |

## State & interaction contract

Kontrak berikut berlaku pada seluruh keluarga; tindakan hanya ada bila berhak. Aksi sah yang terhalang state boleh disabled dengan alasan terbaca. Data loading/kosong tidak diisi angka/logo/nama palsu. Validasi server tetap sumber hasil meskipun UI telah memvalidasi format.

| State/trigger | Visible content/copy | Interaction and focus behavior | Recovery/acceptance |
| --- | --- | --- | --- |
| Initial/loading | Judul/konteks tetap; status “Memuat data” tanpa mengganti data lama seolah konteks baru | Cegah aksi pada objek yang belum siap; tidak mengambil fokus berulang saat fetch inbox | Tampilkan data hanya dari scope respons terbaru, abaikan respons konteks lama |
| Empty | Pesan spesifik belum ada data/filter/rapor; langkah berikut hanya yang berhak | Fokus tetap terduga; tautan Perkembangan Belajar tetap tersedia pada rapor kosong | Kosong dibedakan dari gagal jaringan/izin; tidak menyimpulkan nilai0 |
| Populated | Context, status teks, data minimum dan aksi berizin | Native form/table, label siswa/kolom jelas; pilihan batch kosong secara default | Pagination/count/filter scoped, navigasi detail kembali ke filter berizin |
| Validation/error | Ringkasan dan error per field/baris dengan alasan yang aman | Fokus ringkasan/field error; isian aman hanya dipertahankan selama sesi/scope masih sah | Koreksi lalu kirim ulang oleh pengguna; clear existing bagi D ditolak, bukan dinormalisasi menjadi izin hapus |
| Success | Pesan commit sesuai tindakan: draf tersimpan, disahkan, dibagikan, difinalisasi atau dipublikasikan | Pengumuman nonmendesak tanpa merebut fokus; tampilkan version/state hasil server | Tidak menyebut email/read/unduh berhasil tanpa efek nyata; cek kesiapan PDF |
| Disabled/denied | Tidak berhak: aksi tidak ditampilkan; prasyarat state kurang: alasan teks di dekat kontrol | Disabled tidak menjadi satu-satunya tempat alasan; direct route tetap dapat ditolak server | Izin dicabut/sesi habis membersihkan state sensitif dan menutup edit, bukan mempertahankan data untuk salin ulang |
| Network retry/offline | Error jaringan dibedakan dari hasil transaksi belum pasti dan realtime terputus | Tidak autosave/offline queue/auto-POST retry; pengguna membaca keadaan terbaru | Setelah timeout mutasi periksa GET/list/status berizin; reconnect hanya catch-up inbox, bukan mengulang nilai |

**Konflik versi:** isian aman dapat dibandingkan sementara pada halaman berizin, tetapi tidak ada force overwrite. Refresh sumber meminta keputusan pengguna untuk isian belum tersimpan; bila izin/sesi hilang, state lama dibersihkan. Tidak menyimpan form ke localStorage, URL atau ekspor berkas untuk memulihkan akses yang telah dicabut.

**Navigasi dan pilihan:** pindah anak/kelas/semester memberi peringatan kehilangan perubahan lokal yang belum disimpan; batal tetap pada form, lanjut membersihkan state scope lama. Tidak mengirim mutasi saat pengguna hanya mengganti konteks. Batch SCR-10 dikosongkan saat filter/page berubah; roster SCR-05/06 tetap satu sesi/assessment, bukan fitur select-all lintas penilaian.

**Tab order, headings/landmarks, labels, live announcements:** satu h1 tugas; landmark navigasi/main; skip link; fokus Tab mengikuti dokumen dan label field/row. Error dikaitkan ke input dan ringkasan; perubahan status penting diumumkan seperlunya. Tidak memakai ARIA grid custom untuk input kelas.

**Motion and reduced-motion behavior:** tidak ada animasi dekoratif/auto-scroll data. Transisi drawer/dialog seperlunya mengikuti reduced motion; pembaruan inbox tidak menggoyang layout atau mengambil fokus.

**Keyboard/touch alternatives, target sizing, zoom:** input/select/button native, Enter mengikuti form yang jelas, dialog tidak trap tanpa jalan keluar, Escape/Batal menutup dan fokus kembali ke pemicu sah. Menutup dialog sesudah request dikirim tidak membatalkan transaksi; tunggu/periksa keadaan server sebelum mengulang. Enter pada field roster tidak boleh memublikasikan, mengesahkan atau memberi keputusan secara implisit; tindakan berdampak memerlukan aktivasi tombol yang jelas dan review yang relevan. Target/kontras/zoom mengikuti proposal DESIGN_SYSTEM; uji pembesaran agar context, nama dan pesan tidak tertutup sticky bar.

**Fixtures:** nol data, satu/banyak siswa sintetis, nama/mapel panjang, blank/0/desimal, policy total99/100/101, no meetings, sumber usang, delegasi habis, session expiry, PDF gagal, reconnect serta batch berisi satu invalid. Bukan data sekolah atau penelitian pengguna aktual.

## Implementation agreement

Pakai antislop During dan taste-skill **app screens** sesuai [sistem desain](../DESIGN_SYSTEM.md), serta Ponytail full pada [rencana kualitas](../CODE_QUALITY_PLAN.md). Reuse komponen shadcn/Tailwind yang sesuai sebelum membuat kontrol baru; tinjau pola referensi tanpa menyalin pilihan skenario QA, fixture, asumsi angka atau state memori ke produksi. Pengujian backend tidak boleh digantikan simulasi prototipe.

Komponen bersama yang diperlukan: shell/context bar/drawer, judul/filter/pagination, tabel native dan daftar siswa ponsel, field/error summary, status teks, dialog konfirmasi berdampak, empty/error state serta inbox indicator. Pakai token DESIGN_SYSTEM dan fasilitas komponen yang benar-benar dipilih; tidak membuat library spreadsheet, chart, generic workflow builder atau route API baru untuk memenuhi desain.

Rumus dan finalisasi tetap server; preview React tidak menentukan nilai akhir. Native input desimal menormalisasi koma/titik secara eksplisit menjadi decimal string sesuai CONTENT_STYLE/API, tanpa grouping ambigu atau pembulatan diam-diam. Susunan rapor Q7.1 sudah disepakati; batas skor input Q7.2 diterima, sedangkan pembulatan hasil Q7.3 juga diterima; pembanding Q7.4 juga diterima; presisi konfigurasi Q7.5 juga diterima; asset logo, warna final, font, ukuran kertas dan pagination belum dikunci atau diuji.

Ekspektasi per layar berikut untuk dirujuk DV/AC/CT yang sama, bukan sebelas suite baru. Semua actual **NOT RUN**:

- SX-01 · SCR-01: normal/temporary login, OTP/forced change dan modal dapat dioperasikan keyboard. Direct domain/file/channel sebelum forced change ditolak. OTP/purpose/proof salah tidak mengubah password, modal sesudah commit yes/no benar, reset login biasa/profil current tetap. Refresh/response hilang menampilkan pending tanpa replay password; field role/anak tidak dapat disisipkan.
- SX-02 · SCR-02: konteks rangkap hanya mengubah navigasi; data scope lama hilang saat pindah dan beranda tidak memuat angka contoh.
- SX-03 · SCR-03: daftar/form hanya menampilkan field/aksi berizin; D tidak mendapat hapus/arsip/konfigurasi dan penggantian homeroom menjaga histori.
- SX-04 · SCR-04: total bobot global tepat100 menjadi prasyarat aktivasi, tanpa override guru dan tanpa auto-adjust nilai input.
- SX-05 · SCR-05: status kosong bukan alpa, draft tidak dihitung, D tidak finalize/clear existing, dan satuan tetap pertemuan.
- SX-06 · SCR-06: blank/0/omitted berbeda, input koma/titik tidak ambigu dan publish terpisah dari simpan; tidak ada Ctrl+V import/force overwrite.
- SX-07 · SCR-07: wali membaca hasil yang dibagikan/disahkan tanpa report_cards; draft/anak lain/count privat tidak tampil.
- SX-08 · SCR-08: komponen positif kosong memblokir finalisasi; sumber stale/PDF gagal tidak menghasilkan publish/download palsu; G tidak mendapat PDF gabungan dari hak satu mapel.
- SX-09 · SCR-09: hanya versi published/current aktif anak terdaftar/diunduh; rapor belum tersedia tetap menawarkan perkembangan tanpa membocorkan state internal.
- SX-10 · SCR-10: approve tidak apply; pilihan batch page-only eksplisit, satu invalid tidak sukses sebagian, P tanpa grant pelaksana tidak memiliki aksi apply.
- SX-11 · SCR-11: audit tidak bisa diedit, inbox sendiri tetap tersedia saat socket gagal, read-state tidak rekursif dan pencabutan izin menutup sumber/tujuan berikutnya.

Uji aplikasi inti kelak mencatat revisi, peran/fixture, viewport/browser, keyboard/zoom dan hasil state positif/negatif, tanpa data nyata. Evidence prototipe yang sudah tersedia tetap terpisah dan tidak dijalankan ulang pada tugas ini; PDF tinjauan desain bukan PDF rapor resmi. Pemeriksaan layar/aksesibilitas aplikasi inti tetap NOT RUN, dan telaah dokumen bukan bukti alur server bekerja.

Q6.1/Q6.3 dan D-25 dipilih. Masih terbuka: parameter/provider/hashed repository/middleware/fallback/bootstrap; allowlist/delegasi/approval penugasan/aktor apply, rekonsiliasi cap delegasi saat timezone berubah dan detail visual/render; D-28 koreksi/revisi/revoke serta D-29 satu timezone dipilih. Persetujuan blueprint bukan izin source/install/deploy atau sign-off visual menyeluruh.

### D26/D27: handover dan daftar peserta

Form pengajar MA menampilkan class_subject/tanggal efektif/guru lama-baru/draft dan jadwal bentrok/delegasi terdampak sebelum simpan. Histori dan creator tidak ditimpa, guru baru efektif melihat draft lama pada parent; guru lama tanpa grant lain mendapat akses ditolak.

Absensi menampilkan peserta yang berlaku pada held_on dan perubahan daftar saat enrollment berubah. Stale roster_changed meminta review; jangan kehilangan input tanpa penjelasan atau auto-delete record. Tampilkan pertemuan sebelum masuk/setelah keluar sebagai tidak berlaku/blank, bukan alpa0. Final frozen tidak dapat diubah lewat CRUD; D-28 hanya status H/I/S/A peserta existing dengan grant koreksi, tanpa transfer atau edit roster/tanggal. Keyboard/error/focus dan responsif mengikuti SCR/SX, seluruh browser NOT RUN.

### Perilaku koreksi dan rapor D-28 (SCR-05..10)

MA atau G current term terbuka melihat Koreksi pada hasil sah; term closed MA saja. Form menampilkan sebelum/usulan, alasan wajib dan konfirmasi dampak rapor sesuai scope. Absensi status saja, skor menyediakan aksi kosongkan yang jelas membedakan blank/0. Saat submit tombol sibuk, error kembali fokus ringkasan/field; stale meminta muat ulang dan tinjau tanpa force overwrite. D/W/P tanpa grant tidak mendapat aksi; server tetap memeriksa.

Staf MA/W binaan melihat Perlu revisi, versi/policy asli, Buat revisi dan Cabut rapor dengan konfirmasi alasan. Pertahankan konteks siswa/semester pada tampilan sempit dan urutan Tab; dialog berlabel/fokus terkelola, hasil tidak hanya warna. Revisi/file gagal tetap menampilkan tertahan dan retry berizin, bukan membuka PDF lama.

Wali melihat perkembangan terkoreksi dan notice rapor direvisi/dicabut hanya semester pernah terbit. Detail/PDF held/revoked/superseded tidak ditawarkan; tautan tersimpan ditolak. Setelah versi baru published, tampilkan revisi aktif saja. Tidak membuka draft/history/reason internal; notif menuju Perkembangan Belajar atau Rapor Online sesuai event. Browser/keyboard/responsif **NOT RUN**.

### SCR-03 — pilihan timezone sekolah D-29

Hanya Master pada Konfigurasi Sistem melihat field Zona waktu sekolah. Placeholder Pilih zona waktu sekolah, pilihan WIB, WITA, WIT; keterangan Indonesia Barat (UTC+07:00), Indonesia Tengah (UTC+08:00), Indonesia Timur (UTC+09:00). Backend menerima IANA id sesuai PRD, bukan label/placeholder. Nilai awal belum dipilih; field wajib tanpa auto-select.

Sebelum menyimpan perubahan, konfirmasi menjelaskan: waktu semua pengguna dan tanggal sekolah mengikuti zona baru; penugasan berbasis tanggal dapat berubah berlaku di sekitar tengah malam. Simpan dengan expected_version; loading/error/conflict/sukses jelas, sukses hanya sesudah commit. Fokus kembali ke field/ringkasan bila error; semua pilihan dapat diakses keyboard dan ponsel tanpa kontrol khusus.

Setelah simpan, halaman pengguna memakai setting terbaru setelah refresh. Label waktu WIB/WITA/WIT selalu eksplisit; orang tua di Jayapura tetap melihat WIB jika sekolah memilih WIB. Tidak memaksa auto-reload form atau menawarkan timezone pribadi/tampilan kedua. Saat form akademik stale oleh perubahan zona, school_timezone_changed meminta refresh/review, tanpa overwrite. Keyboard/browser/responsif seluruhnya NOT RUN.
