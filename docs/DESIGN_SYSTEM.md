# Design System — SIA MI ALGHIVARI

**Owner:** pemilik proyek · **Status:** PROPOSED / blueprint · **Last reviewed:** 30 Oktober 2026, penyelarasan keputusan domain; aturan styling 29 Oktober tetap.

**References:** [alur pengguna](USER_FLOWS.md), [fitur/AC](FEATURES/FEATURE_SPEC_TEMPLATE.md), [kontrak HTTP](API_CONTRACT.md) dan sumber W3C pada bagian aksesibilitas. [Prototipe tinjauan](support/facelift-v2/README.md) tersedia terpisah; belum ada Figma, font merek atau logo yang disetujui.

**Decision inputs:** [DESIGN_BRIEF](DESIGN_BRIEF.md) dan rekomendasi A pada [DESIGN_DIRECTION](DESIGN_DIRECTION.md); belum persetujuan seluruh desain implementasi. Semua token/layout berikut kandidat yang dapat direvisi. Implementasi aplikasi inti **NOT STARTED**. Bukti prototipe dibatasi pada [laporan rebuild](support/facelift-v2/qa/rebuild-report.md); verification-report.md dalam paket adalah arsip sebelumnya. Ini bukan verifikasi aplikasi inti atau sertifikasi aksesibilitas. Perhitungan hex statis dibedakan dari pengujian UI.

**Aturan styling dikonfirmasi pemilik, 29 Oktober 2026:** gunakan dokumentasi Tailwind untuk CSS global, tema, utility layout dan state; gunakan shadcn/ui untuk komponen reusable beserta variant. CSS manual hanya jika kebutuhan tidak ditemukan pada dokumentasi keduanya, dengan kebutuhan, sumber yang diperiksa dan alasan dicatat. Detail dan rujukan resmi: [STYLING_CONVENTIONS](support/facelift-v2/STYLING_CONVENTIONS.md). Ini persetujuan pendekatan implementasi styling, bukan persetujuan seluruh token atau arah visual.

## 1. Brand and experience principles

Pembaruan 29 Oktober 2026: pemilik menetapkan [facelift-v2](support/facelift-v2/README.md) sebagai **referensi desain, bukan panduan wajib**. Arah terbaru paket ada pada [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md); [COLOR_REFRESH](support/facelift-v2/COLOR_REFRESH.md) menyimpan revisi warna sebelumnya. Token tabel blueprint di bawah adalah usulan awal; source `support/facelift-v2/prototype/src/styles.css` menunjukkan kandidat mint/zamrud yang benar-benar dipakai prototipe. Jangan menyalin salah satu palette/ukuran secara otomatis atau menganggapnya brand resmi.

Teratur, tenang, dan terbuka tentang keadaan: satu konteks akademik terlihat, data disusun untuk dibandingkan, tindakan/status memakai istilah domain yang jelas. Prioritas visual adalah isi siswa/aktivitas dan konsekuensi operasi, bukan hiasan.

Arah **Register Akademik** memakai latar terang hangat, hijau gelap untuk aksi, teks gelap, garis pemisah dan tipografi sistem. Ini identitas usulan, bukan warna resmi sekolah. Gunakan nama sekolah dalam teks sampai asset resmi beserta izin penggunaan tersedia. Tidak menyalin logo pada contoh rapor atau membuat klaim sekolah telah memakai produk.

Gunakan tabel/form/daftar standar yang dapat diuji. Beranda hanya menampilkan pekerjaan yang sumber query dan izinnya sudah ada; jika tidak ada data, tampilkan keadaan kosong. Tidak menambahkan chart, skor kinerja, ilustrasi anak, carousel, dark mode atau panel statistik demi dekorasi. Proyeksi baca wali lebih lapang, tetapi token dan istilah tetap sama dengan admin/guru.

## 2. Token system (semantic names, not magic hex everywhere)

| Token | Light | Dark (if supported) | Usage |
| --- | --- | --- | --- |
| `color.bg` | #F7F8F5 | N/A core | Latar aplikasi terang |
| `color.surface` | #FFFFFF | N/A core | Form, daftar dan dialog |
| `color.surface.subtle` | #EEF2EE | N/A core | Header tabel dan kelompok informasi |
| `color.text.primary` | #17231D | N/A core | Isi utama, judul dan angka |
| `color.text.muted` | #526059 | N/A core | Bantuan/konteks; bukan untuk menurunkan keterbacaan data wajib |
| `color.action.primary` | #176B4B | N/A core | Aksi utama dan status berhasil |
| `color.action.hover` | #115238 | N/A core | Hover/pressed aksi utama dengan label tetap |
| `color.action.onPrimary` | #FFFFFF | N/A core | Teks pada tombol utama |
| `color.border` | #CCD5CD | N/A core | Pemisah dekoratif; bukan satu-satunya batas kontrol |
| `color.control.border` | #708275 | N/A core | Batas input/select/control yang diperlukan agar dikenali |
| `color.focus` | #1D4ED8 | N/A core | Outline fokus dengan offset/ruang putih pada kontrol |
| `color.selection` | #E5F1E9 | N/A core | Baris terpilih disertai checkbox/teks, tidak warna saja |
| `color.feedback.error` | #B42318 | N/A core | Error/danger dengan label dan ikon bila berguna |
| `color.feedback.errorBg` | #FEF3F2 | N/A core | Latar pesan error |
| `color.feedback.warning` | #8A4B08 | N/A core | Peringatan/kelengkapan, dengan teks penyebab |
| `color.feedback.warningBg` | #FFFAEB | N/A core | Latar peringatan |
| `color.feedback.successBg` | #ECFDF3 | N/A core | Latar status berhasil dengan foreground action.primary |
| `color.feedback.info` | #1D4ED8 | N/A core | Informasi netral yang perlu dibedakan |
| `color.feedback.infoBg` | #EFF6FF | N/A core | Latar informasi |

Usulan tipografi: font sistem `system-ui, sans-serif`; angka memakai tabular numerals bila tersedia. Isi16px/line-height1,5; tabel/bantuan14–16px dengan line-height minimal1,4; heading halaman24–28px/1,25, subjudul18–20px/1,4. Ukuran relatif mengikuti root font dan pembesaran pengguna. NIS tetap teks, angka nilai rata kanan tanpa mengubah presisi server. Tidak memuat font pihak ketiga pada rancangan awal.

Skala spacing4/8/12/16/24/32px; kontrol tinggi sasaran44px, jarak8px untuk aksi berdekatan. Radius kontrol6px, permukaan8px; border1px, outline fokus usulan2px dengan offset2px. Bayangan ringan hanya untuk layer mengambang, bukan semua bagian. Ikon dari paket kit final bila diperlukan, berlabel accessible; tindakan utama memakai teks. Ukuran/radius adalah token usulan, bukan bukti aksesibilitas.

Layout: desktop mulai sekitar1024 CSS px, sidebar240px dan main fleksibel dengan padding24px; area umum maksimum1440px, halaman input dapat memakai lebar tersedia. Header konteks membungkus teks. Lebar768–1023 memakai drawer dan daftar/tabel sesuai ruang nyata. Di bawah768 satu kolom dengan padding16px dan daftar siswa berlabel; breakpoint kandidat, bukan batas perangkat tetap. Tidak menempatkan form satu kelas dalam modal sempit.

Sticky action bar hanya jika membantu form panjang; sisakan ruang akhir dan scroll offset agar fokus/error/baris terakhir tetap terlihat. Context bar ponsel dipadatkan tanpa menghilangkan tahun/semester/kelas/mapel atau anak. Motion usulan120–160ms untuk feedback/transisi kecil; reduced-motion menghapus perpindahan/animasi yang tidak perlu. Tidak ada animasi nilai atau live-update yang memindahkan fokus.

Perhitungan luminansi relatif pada 28 Oktober 2026 untuk pasangan hex opaque berikut benar-benar dijalankan. Angka dibulatkan hanya untuk pelaporan; semua pasangan lolos ambang statis yang dipilih tanpa memakai pembulatan untuk kelulusan. **Ini bukan pemeriksaan kontras UI yang sudah dirender atau bukti WCAG AA.**

| Pasangan token | Rasio statis | Penggunaan yang dihitung |
| --- | --- | --- |
| text.primary / surface; text.primary / bg | 16,22:1; 15,22:1 | Teks normal |
| text.muted / surface; text.muted / bg | 6,61:1; 6,20:1 | Bantuan/konteks |
| action.onPrimary / action.primary; / action.hover | 6,48:1; 9,18:1 | Label tombol |
| control.border / surface; / bg | 4,09:1; 3,83:1 | Batas kontrol bermakna |
| focus / surface; / bg | 6,70:1; 6,29:1 | Outline yang dipisahkan dari kontrol oleh ruang latar |
| feedback.error / errorBg; warning / warningBg | 6,05:1; 6,51:1 | Teks badge/pesan |
| action.primary / successBg; info / infoBg | 6,14:1; 6,16:1 | Teks badge/pesan |

Opacity, state disabled, overlay, border dekoratif dan kombinasi lain belum diperiksa; saat implementasi hitung/verifikasi pasangan yang benar-benar dipakai. Jangan memakai color.border untuk batas input atau mengandalkan placeholder abu-abu sebagai label.

## 3. Component contracts

Kontrak umum: accessible name terlihat/terhubung label, fokus keyboard jelas, status selain warna, loading/error/success berdasarkan respons nyata. Disabled harus mempunyai alasan yang bisa dibaca tanpa hover; izin yang tidak dimiliki tidak menghasilkan tombol aktif. Ukuran dan overflow harus diuji dengan teks panjang, zoom dan data kosong. Komponen lokal shadcn/ui sudah ada dalam prototipe; reuse hanya setelah kontrak, provenance dan perilakunya sesuai aplikasi inti. Keberadaan komponen/evidence prototipe tidak menjamin seluruh implementasi aksesibel.

| Komponen | Anatomy/variant dan pemakaian | Keyboard, state, responsif dan batas |
| --- | --- | --- |
| Button/link | Primary satu aksi utama per kelompok, secondary untuk pendamping, danger untuk konsekuensi penghapusan/arsip; navigasi memakai link | Native semantics, label spesifik, Enter/Space sesuai elemen; pending mencegah klik ulang dan diumumkan. Enter pada field roster tidak memicu publish/finalize/approval/apply; aksi berdampak diaktifkan eksplisit. Tidak membuat tombol yang belum mempunyai operasi sah. |
| Input angka/teks | Label permanen, bantuan format, nilai dan pesan inline; NIS/username adalah teks | Tab standar, paste boleh; angka memakai kontrak lokal CONTENT_STYLE dan API decimal string. Tidak menjadikan blank0 atau membulatkan sebelum kirim. |
| Select/radio | Pilihan berlabel untuk kelas/komponen dan status H/I/S/A | Awal kehadiran Belum dicatat; radio/select punya label siswa. Native atau komponen kit yang diuji; bukan kontrol klik-only. Tidak mengisi semua Hadir otomatis. |
| Checkbox | Seleksi batch eksplisit, jumlah terpilih dan pilihan halaman ini | Awal tidak ada pilihan; header indeterminate bila sebagian; filter/pagination membersihkan pilihan dengan penjelasan agar tidak ada seleksi tersembunyi. Tidak berarti pilih semua halaman. |
| Form field | Label, input, hint dan error melalui asosiasi programatis | Error summary fokus setelah submit gagal yang terkonfirmasi; tautan ke field/baris. Nilai aman dipertahankan hanya selama sesi/scope masih sah. Tidak mengulang password ke flash/history. |
| Dialog | Judul, dampak/jumlah berizin, tindakan spesifik dan Batal | Fokus masuk sesuai isi/risiko, Tab tetap di dialog, Escape/Batal menutup, kembali ke pemicu/logical fallback. Menutup dialog tidak membatalkan operasi yang sudah dikirim. |
| Toast/status | Ringkasan sukses singkat; error/konflik penting tetap inline | `status`/live announcement seperlunya; tidak mencuri fokus, tidak menjadi satu-satunya bukti error atau unduhan berhasil. |
| Tabs/segmented navigation | Link antarbagian untuk Perkembangan Belajar/Rapor Online dan konteks tugas | Pakai navigasi link bila berpindah halaman; jangan memberi role tabs pada link biasa. Jika tab in-page diperlukan, perilaku keyboard APG wajib diuji; belum kebutuhan custom widget. |
| Table/list | Caption, header baris/kolom, nama/NIS dan nilai/status, sort/filter yang benar-benar didukung | Tabel HTML dengan kontrol native di desktop; ponsel daftar siswa berlabel dengan kontrak data sama. Tidak memberi role grid/custom arrow engine. Overflow tabel esensial tidak membuat seluruh halaman horizontal. |
| Empty state | Penjelasan sebab dan tindakan sah bila ada | Bedakan belum ada data, filter nihil, tidak berhak dan gagal memuat. Jangan mengajak wali menambah nilai atau menampilkan nol palsu. |
| Navigation | Nama sekolah teks, konteks kerja, link berizin, anak/periode, inbox/profil | Nav berlabel, current page jelas, skip link; drawer punya fokus/dialog behavior. Menu bukan penentu izin. Pergantian konteks dengan unsaved form meminta keputusan, tidak memindah nilai ke siswa lain. |
| Card/section | Kelompok informasi satu anak/siswa atau ringkasan keputusan | Heading/list semantik dan teks membungkus; tidak semua kartu clickable atau seluruh halaman menjadi grid kartu. |
| Tooltip | Bantuan singkat untuk ikon opsional | Muncul saat fokus maupun pointer; bukan tempat error, alasan disabled, status atau informasi wajib. Tidak ada hover-only core action. |
| Badge | Status domain berteks, misalnya Draf/Disahkan/Dipublikasikan | Bukan tombol; warna+label, tidak sekadar titik. Status grade/attendance/report berbeda walau memakai warna sama. |
| Pagination/filter | Search, pilihan filter berizin, hasil/jumlah server | Filter mendahului count, reset halaman saat filter berubah; kembali dari detail mengembalikan konteks aman. Bukan virtual table tak terbatas atau pencarian seluruh data tanpa policy. |

Perubahan form disimpan lewat tindakan eksplisit; tidak menambah autosave/queue offline/clipboard import. Perubahan yang belum dikirim hanya berada di memori halaman yang masih berhak, bukan localStorage/remember history nilai/biodata. Sesudah izin hilang/sesi berakhir, bersihkan data sensitif dan gunakan data server berizin terbaru. Timeout belum berarti batal: tampilkan status belum diketahui dan arahkan pemeriksaan keadaan sebelum mengirim ulang.

## 4. Screen inventory and visual hierarchy

| Screen | Primary task | Primary action | Secondary content | Mobile adaptation | States |
| --- | --- | --- | --- | --- | --- |
| SCR-01 Login/profil | Login/forced change/profil/reset D-25 | Masuk; Minta/verifikasi OTP; Simpan password; pilihan logout/tidak wajib setelah sukses | Email tujuan tercatat, no secret props, reset login biasa/profil sesi kini bertahan | Form satu kolom, paste/password manager, modal keyboard | Invalid/expired/rate-limit, pending/refresh, email failure dan sukses server |
| SCR-02 Beranda/konteks | Pilih pekerjaan yang berhak | Buka pekerjaan | Periode/konteks dan pekerjaan nyata tanpa angka dummy | Urutan tugas, drawer | Loading, kosong, revoke |
| SCR-03 Administrasi/akademik | Siapkan data, penugasan dan jadwal | Tambah/simpan sesuai grant | Filter, konteks, histori minimum | Form satu kolom, daftar ringkas | Validasi, duplikat, bentrok, Q6 terbuka |
| SCR-04 Konfigurasi Penilaian | Siapkan revisi KKM/bobot global | Simpan draf; Aktifkan revisi | Total bobot100 dan versi aktif | Komponen sebagai form berlabel | Total invalid, stale active, readonly |
| SCR-05 Kehadiran | Catat satu pertemuan mapel | Simpan draf; Sahkan bila berhak/lengkap | Belum dicatat/H/I/S/A, roster/context | Daftar siswa/status | Partial, denied, konflik, finalized |
| SCR-06 Penilaian | Catat satu assessment | Simpan draf; Bagikan hasil bila berhak | Blank0, komponen pusat, jumlah numerik | Daftar siswa dengan input | Partial, invalid, konflik, published |
| SCR-07 Perkembangan Belajar | Wali membaca aktivitas anak | Pilih/filter aktivitas | Rekap sementara dan jalur rapor terpisah | Daftar aktivitas satu anak | Tidak ada aktivitas, blank0, error, revoke |
| SCR-08 Rapor staf | Periksa/finalisasi/publish snapshot | Finalisasi; Siapkan dokumen; Publikasikan | Kelengkapan, versi sumber/policy dan file | Ringkasan hambatan + detail mapel | Belum lengkap, source changed, file gagal, finalized/published |
| SCR-09 Rapor Online wali | Baca/unduh rapor published anak | Unduh rapor tersedia | Semester/revisi/waktu publikasi | Daftar dokumen; alternatif baca HTML | Belum tersedia, file error, revoked/denied |
| SCR-10 Naik/lulus | Principal memutuskan; pelaksana menerapkan terpisah | Setujui/tolak; Terapkan hanya grant terpisah | Review sumber/tujuan dan pilihan batch | Detail/read-review satu kolom | Invalid batch, source changed, already applied |
| SCR-11 Audit/inbox | Baca riwayat relevan dan notifikasi sendiri | Buka detail; Tandai dibaca | Scope, waktu, count berizin dan status koneksi | Daftar ringkas | Empty, tujuan tidak lagi tersedia, reconnect |

Detail route/field, urutan interaksi dan hasil yang diharapkan terdapat pada [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md). Tabel ini tidak memberi grant baru. Finalisasi/publikasi rapor oleh MA/W binaan, approval oleh principal, serta apply yang masih Q6 tetap dibedakan.

## 5. Accessibility acceptance

Sasaran usulan: **WCAG 2.2 AA**, bukan sertifikasi atau hasil pemeriksaan. Keyboard semua core flow, skip link, heading/landmark, label/error association dan urutan DOM harus bermakna; tidak memakai tabindex positif atau interaksi drag/hover sebagai satu-satunya cara.

Teks normal minimal4,5:1; teks besar sesuai definisi standar minimal3:1; bagian kontrol/state bermakna minimal3:1. Fokus tidak tertutup sticky bar/header; target produk dibuat sepenuhnya terlihat. Sasaran kontrol sentuh44×44 CSS px adalah pilihan produk, lebih besar dari minimum AA24×24 beserta pengecualiannya. Rujukan: [contrast minimum](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html), [non-text contrast](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html), [target size minimum](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html), [focus not obscured](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html).

Uji pembesaran teks200% dan reflow pada lebar320 CSS px, termasuk padanan1280px pada zoom400%. Pengecualian tabel dua dimensi hanya untuk bagian yang benar-benar memerlukan hubungan dua dimensi, bukan izin membuat seluruh halaman/input ponsel horizontal. Rujukan [reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html). PDF cetak tetap diuji pagination/keterbacaan; ringkasan HTML membantu baca ponsel tetapi tidak otomatis membuktikan aksesibilitas PDF.

Gunakan live region seperlunya untuk simpan/error/koneksi; jangan membaca ulang seluruh roster setiap ketikan. Pastikan dialog, form dan tabel mengikuti [WAI dialog](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/), [tabel](https://www.w3.org/WAI/ARIA/apg/patterns/table/) dan dukungan [autentikasi aksesibel](https://www.w3.org/WAI/WCAG22/Understanding/accessible-authentication-minimum.html). Password manager/paste tidak diblokir; tidak menambah MFA/cognitive challenge.

Matriks usulan: keyboard desktop; NVDA dengan Chrome/Edge Windows; Safari/VoiceOver pada perangkat yang tersedia untuk cakupan iOS/macOS yang kelak dipilih. Versi/perangkat aktual dan keterbatasan dicatat saat pemeriksaan, bukan dianggap sudah tersedia. Pemeriksaan assistive/browser aplikasi inti **NOT RUN**; hasil browser prototipe hanya mengikuti artefak/cakupan laporan rebuild, tanpa mengklaim AT/perangkat fisik sudah diuji.

## 6. Copy and content guidelines

Bahasa Indonesia yang spesifik tugas mengikuti [CONTENT_STYLE](CONTENT_STYLE.md). Label Simpan draf tidak sama dengan Bagikan hasil, Sahkan kehadiran, Finalisasi rapor, Publikasikan rapor atau Setujui/Terapkan keputusan. Rekap sementara bukan rapor. Nilai kosong,0 dan belum ada pertemuan dibedakan; Q7.1 disepakati 29 Oktober 2026: rapor ringkas dengan status Mencapai KKM/Belum mencapai KKM, kehadiran per mapel dalam pertemuan, catatan wali kelas opsional pada draft binaan, dan rincian komponen di aplikasi dari snapshot yang sama. Tidak menambah ranking, rata-rata keseluruhan, predikat A-D atau deskripsi kompetensi. Q7.2–5/D-19..22 menerima input skor/KKM/bobot0–100 maksimal dua desimal, total bobot aktif tepat100, pembulatan akhir dua desimal half-up tanpa pembulatan antara dan pembanding nilai akhir dibulatkan >= KKM. Detail visual/render dan implementasi belum diverifikasi; seluruh tes inti NOT RUN.

Status sukses hanya setelah respons server yang sesuai; file siap bukan bukti pengguna telah mengunduh/membaca. Pesan konflik meminta muat ulang/tinjau sumber, bukan menimpa diam-diam; timeout tidak menjanjikan data belum tersimpan. Error/empty memberikan tindakan yang benar-benar berhak, bukan path ke data orang lain. Fixture sintetis diberi label Contoh; statistik/identitas tidak diisi sebagai data nyata.

## 7. Implementation & drift control

Source CSS/React dan komponen lokal ada di [prototipe](support/facelift-v2/prototype/README.md); struktur aplikasi inti belum dibuat. Saat implementasi diotorisasi, gunakan komponen/pola yang sudah layak, petakan token pada satu sumber tema dan pertahankan satu sumber state desktop/mobile. Jangan menyalin harness Vite, fixtures, simulasi server atau dependensi khusus QA sebagai bagian aplikasi inti tanpa kebutuhan.

Gunakan `antislop:antislop` dalam mode During sesuai pilihan sesi pemilik, beserta pendamping UI/copy/human/mobile yang relevan: arah jelas, copy/data jujur, fungsi kontrol/state, keyboard, fokus, kontras dan responsif diperiksa selama pengerjaan. Gunakan `taste-skill:design-taste-frontend` melalui alur **app screens** untuk menilai tugas, kepadatan, susunan data, identitas konteks dan screenshot lebar relevan. Referensi facelift dipakai sebagai bahan pembanding, bukan pembenaran mengabaikan policy/aksesibilitas atau menyalin seluruh gaya.

Skill adalah panduan kerja agen, bukan dependensi runtime atau alasan menambah logo, font, animasi, chart, file CSS dasar atau layanan baru. Ikuti kebutuhan pemilik dan aturan Tailwind/shadcn proyek bila resep style skill berbeda; jangan mengubah PRD atau menyatakan PASS hanya dari nama plugin. Ketika benar-benar mengubah UI, jalankan pemeriksaan skill yang tersedia pada build yang diuji, telaah hasil visual dan periksa ulang perbaikan. Pada pekerjaan dokumen ini, tidak ada inspector/browser gate baru dijalankan. Detail bukti dan prinsip Ponytail full ada pada [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md).

Pemilik meninjau arah/token/asset; pengembang menjaga konsistensi kontrak komponen. Perubahan domain/proyeksi/route mengikuti PRD/API/akses, bukan keputusan warna. Catat perubahan bermakna pada [DESIGN_DECISIONS](DESIGN_DECISIONS.md); jangan menandai Approved tanpa persetujuan nyata. Penggantian token diperiksa ulang pada seluruh state/pasangan relevan.

## 8. Quality review and acceptance

| ID | Pemeriksaan dan hasil yang diharapkan | Aktual |
| --- | --- | --- |
| DV-01 | Pada360×800,768×1024,1440×900 dan reflow320 CSS px, konteks/aksi/error terbaca; tidak ada page overflow atau fokus tertutup. | NOT RUN |
| DV-02 | Keyboard/assistive menyelesaikan input, dialog, filter, konteks, profil dan unduh; label baris/status/sort jelas tanpa spreadsheet engine. | NOT RUN |
| DV-03 | Semua state token/typography/zoom/reduced-motion dan konten panjang terbaca; kontras aktual memenuhi target yang berlaku. | NOT RUN; rasio hex statis saja sudah dihitung, bukan hasil UI. |
| DV-04 | Blank/0, pertemuan, policy global, perkembangan dibanding rapor dan approval dibanding apply memakai istilah/keadaan sesuai domain. | NOT RUN |
| DV-05 | Loading/empty/validasi/gagal/konflik/timeout/revoke/session expiry tidak menghasilkan sukses palsu, data bocor atau retry mutasi otomatis. | NOT RUN |
| DV-06 | Data contoh diberi label; perubahan browser/server dan render PDF menghasilkan dokumen/snapshot berizin yang sama, tanpa clipping/asset palsu. | NOT RUN |

Rujuk SX pada spesifikasi layar, AC-X01..04, CT dan CQ-11; gunakan bukti yang sama bila cakupan cocok, bukan suite duplikat. Setelah aplikasi tersedia catat route, viewport/browser/assistive aktual, state/fixture, revisi, langkah, screenshot aman dan hasil. [DESIGN_QA_REPORT](DESIGN_QA_REPORT.md) tetap menunggu rancangan/eksekusi tahap terkait; tidak mengisi skor atau verdict dari blueprint ini. Kritik dokumen tidak menggantikan inspeksi UI nyata.
