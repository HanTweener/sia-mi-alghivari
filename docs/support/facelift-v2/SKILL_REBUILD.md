# Register Akademik Terang

29 Oktober 2026. Rebuild prototipe menggunakan Anti Slop **DURING** (pilihan `1`, sesi ini), Taste Skill untuk app screens, dan Ponytail **full**. Lima peran proyek dilibatkan kembali: visual_designer, ux_designer, frontend_engineer, design_critic dan qa_engineer. Status visual **PROPOSED**, menunggu penilaian pemilik.

## Arah sebelum implementasi

Design Read: register akademik untuk guru dan wali, bidang mint dengan pilihan zamrud, daftar kerja teratur; **ENERGY 2 / RHYTHM 1 / MOTION 1**. Warna memberi energi, urutan berulang menjaga keterbacaan, gerakan sebatas feedback kontrol.

Taste menawarkan Framed sheet dan Accent ladder untuk brief admin sekolah. Dipilih **Accent ladder**, seed **879545572**, tanpa redraw; warna hijau dan font sistem dipertahankan lewat `--keep palette,type`. Rekaman mesin: `.taste-skill/direction.json` pada root proyek. Face: tangga warna zamrud dari mint ke warna penuh. Signature: identitas siswa bernomor dan ID, nilai rata kanan, status blank/terisi yang tetap membedakan `0`.

Inventaris pekerjaan dan data:

- Objek utama: satu penilaian PH-03 Matematika IV A dan satu pertemuan mapel ke-12.
- Delapan siswa sintetis; lima nilai terisi, tiga blank, termasuk nilai `0` dan `78,5`.
- Guru mengisi, menyimpan draf, kemudian meninjau pembagian hasil atau pengesahan presensi.
- Wali memilih satu dari dua anak sintetis dan memfilter aktivitas yang sudah dibagikan/disahkan.
- Nilai draf tidak masuk perkembangan; rapor semester terpisah dan belum tersedia.
- Simpan hanya di memori tab, reset setelah muat ulang; tidak ada akun atau server.
- Tujuh kajian visual memiliki form/tindakan nonaktif berlabel, navigasinya bekerja.
- Angka dan nama berasal fixture `prototype/src/data.ts`, daftar riwayat di `Guardian.tsx`, dan fixture statis `studies.tsx`; seluruhnya harus diganti saat implementasi data nyata.

| Layar | Satu pertanyaan utama |
| --- | --- |
| Penilaian | Siswa mana sudah/belum dinilai pada PH-03, dan apakah draf siap dibagikan? |
| Presensi | Siapa sudah dicatat pada pertemuan mapel ini, dan apakah semua isian sudah disimpan? |
| Perkembangan | Hasil apa yang sudah dibagikan untuk anak/mapel yang dipilih? |
| Rapor | Apakah rapor semester tersedia; jika belum, ke mana wali dapat melihat perkembangan? |

## Keputusan dan jangkauan perubahan

| Keputusan | Alasan |
| --- | --- |
| Mint pada sidebar/canvas, putih pada daftar | Isi register memimpin tanpa kehilangan keluarga warna hijau yang diminta pemilik. |
| Zamrud penuh pada pilihan nav dan tombol menu/tindakan | Pilihan tetap hidup pada tampilan awal; area kecil ini menjaga permintaan warna tegas. |
| Amber pada keterangan demo/perhatian, rose pada invalid | Warna menyatakan arti dengan label teks, bukan memberi warna berbeda per siswa. |
| Font sistem 24/20px judul, isi 14–16px | Terbaca untuk pekerjaan berulang, bekerja offline tanpa asset/font baru. |
| Baris desktop sekitar 64px, kontrol tetap 44px | Lebih banyak siswa terlihat tanpa mengurangi target sentuh. |
| Demo disclosure dan menu dialog pada ≤1100px | Mengurangi chrome sebelum isian; tujuan menu, keterangan sintetis dan risiko reload tetap tersedia. |
| Simpan menjadi primary ketika dirty | Tindakan yang dibutuhkan berikutnya menonjol; review tetap diblokir sampai tersimpan. |
| Istilah bagikan hasil untuk assessment | Memisahkan pembagian penilaian dari publikasi rapor, sesuai SCR-06. |
| Satu panel daftar, tanpa grafik/kartu KPI baru | Objek pekerjaan adalah daftar siswa, bukan statistik dekoratif. |
| Ikon daftar, kalender, buku, dokumen, cari dan menu | Membantu mengenali tugas/kontrol yang benar-benar tersedia; tanpa ikon hias pada setiap panel. |

Grid desktop pada 1440px: sidebar 240 + ruang luar 80 + gutter 32 + area isi 976 + gutter 32 + ruang luar 80 = 1440. Header dan main berbagi batas maksimum 1040px agar tiga kolom register tidak terpisah terlalu jauh. Ponsel 390px: gutter 16 + isi 358 + gutter 16 = 390. Tidak ada kolom kartu yatim atau rail kosong. Nama panjang membungkus.

Pengecualian/adaptasi terhadap resep skill: tidak menyalin `assets/app.css`; geometri diwujudkan dengan utility Tailwind karena aturan pemilik. Tidak membuat grafik atau komponen halaman pemasaran untuk memenuhi kit. Pilihan nav memakai zamrud penuh pada area kecil agar keinginan warna hidup tetap terbaca; tidak ada sidebar pekat seluas layar. Tidak menambah font/logo/foto.

Source yang dijangkau: shell `App.tsx`, tema/baseline `styles.css`, tujuh kajian `studies.tsx`, GradeRegister, Attendance, Guardian, PupilIdentity, variant Input dan wrapper Dialog. Button/CVA dan Radix yang terpasang dipakai kembali. Delapan unit test, helper QA menu/disclosure dan selector pembagian disesuaikan; HTML/PDF ekspor dan dokumentasi disinkronkan. Tidak menambah dependency.

Dokumentasi diperiksa: [Tailwind theme](https://tailwindcss.com/docs/theme), [responsive design](https://tailwindcss.com/docs/responsive-design), [shadcn Button](https://ui.shadcn.com/docs/components/radix/button), [Dialog](https://ui.shadcn.com/docs/components/radix/dialog). Source tetap memakai versi dependency yang terkunci. Tidak ada fallback CSS komponen manual; `styles.css` hanya import, tema dan baseline Tailwind.

## Look-and-fix dan bukti

Round 1 inspector: 0 FAIL/0 WARN, tetapi screenshot memperlihatkan area sebelum isian masih panjang. Lint memberi satu WARN zero-padding indeks; dihapus. Kritik independen menemukan fokus menu/dirty dialog kembali ke pemicu yang salah atau BODY. Wrapper Dialog sekarang menerima pengembalian fokus khusus, tanpa mengubah validasi atau snapshot.

Round 2: judul/status dirapatkan, petunjuk rentang dikaitkan lewat `aria-describedby` pada seluruh input dan ditempatkan setelah daftar, menu aktif lebih tegas. Input pertama pada 390×844 mulai **503px (59,6%)**, sebelumnya round 1 593px. Retest critic: menu Escape kembali ke pemicu; pilih layar menuju main; dirty cancel/Escape mempertahankan nilai dan menuju main; discard menuju tujuan. Tidak ada temuan fungsional tersisa pada lingkup review independen.

| Pemeriksaan | Hasil dan bukti |
| --- | --- |
| TypeScript + Vite build | PASS, build produksi yang diuji; hash source/served pada folder QA. |
| Vitest | PASS 8/8; validasi, blank/0, partial share, presensi, guardian, dirty dan disclosure. |
| Browser | PASS 13/13, termasuk navigasi ponsel dan fokus setelah dialog; `qa/evidence/interaction-results.json`. |
| Layout inti | PASS 16 kombinasi 320/390/768/1440; `qa/evidence/layout-inspection.json`. |
| Kajian visual | PASS 14 layout 390/1440; `evidence/study-layout.json`. |
| Taste lint | PASS 23 file, 0 FAIL/0 WARN. |
| Taste inspector | PASS 1440/768/390, 0 FAIL/0 WARN; `evidence/taste-final-compact/report.json` dan screenshot. |
| Kontras token opaque | PASS 21 pasangan; `evidence/color-contrast-results.json`, bukan sertifikasi aksesibilitas. |
| Batas styling | PASS satu CSS global 108 baris, nol selector CSS komponen/deklarasi manual/inline style; `evidence/source-styling-check.json`. |
| Formatter dan struktur | PASS Prettier; audit eksplisit 26 file, 0 error/warning. |
| HTML mandiri | PASS empat pemeriksaan offline, dua HTML × 390/1440; tanpa pageerror/request HTTP, `evidence/packaging-check.json`. |
| PDF tinjauan | PASS 14 halaman diekspor ulang, dirender Poppler dan diperiksa komposisinya; `evidence/rebuild-pdf-contact.png`. |
| Identitas hasil | PASS served build identik dengan dist; source 33 file dan artefak dicatat pada SHA-256 di QA/evidence. |

Round 3 menanggapi WARN `hero-void` dari inspector round 2: kolom kerja desktop dibatasi 1040px beserta headernya; ruang antaridentitas dan nilai mengecil. Inspector akhir tidak memiliki FAIL/WARN. Bukti round 2 tetap tersimpan pada `evidence/taste-final`, tidak disamarkan sebagai hasil akhir.

Tangkapan Taste desktop/tablet/ponsel ditelaah langsung: judul dan register menjadi fokus, tepi isi sejajar, tidak ada panel kosong yang diregangkan; hijau menyatakan pilihan/tindakan/hasil, `0` tampil sebagai angka, blank tetap kosong. Count lima/delapan/tiga berasal baris dan filter memperbarui count tampilan. Tidak ada chart/legend yang perlu dipadankan. Navigasi ponsel terlipat dan data tetap utuh. Hasil ini penilaian pada fixture, bukan bukti riset pengguna.

## Anti Slop Delivery Gate oleh main agent

PASS di bawah berlaku pada prototipe berlabel dan cakupan bukti browser/source di atas. Tidak menyatakan aplikasi produksi siap atau WCAG tersertifikasi.

| Item | Status dan bukti |
| --- | --- |
| R-02 copy | PASS: copy UI yang diubah spesifik kegiatan, tanpa em dash pemasaran; pemisah rentang angka tetap tanda rentang. |
| R-03 mobile | PASS: 16 layout tanpa overflow, nama panjang dan input/radio ≥44px diperiksa; isian pertama y503. |
| R-17 data | PASS: count dihitung dari delapan fixture, banner menyatakan sintetis. |
| R-18 testimonial | PASS: tidak ada testimonial atau klaim pengguna. |
| R-23 asset | PASS: tidak membuat logo/avatar/foto/font atau destinasi baru; empat layar tetap lingkup awal. |
| R-24 navigasi | PASS: empat layar, menu ponsel dan link HTML kajian menuju tujuan tersedia. |
| R-25 kontras | PASS pada 21 pasangan opaque yang diperiksa; putih/zamrud 5,48:1, teks sidebar 11,39:1. |
| R-26 kontrol | PASS: click-through di QA; kajian statis sengaja disabled dan berlabel. |
| R-27 keadaan | PASS: loading/empty/error/conflict sintetis, invalid, dirty/saved/shared/signed dan rapor kosong diuji. |
| R-28 FAQ | PASS: tidak ada FAQ. |
| R-32 keyboard | PASS cakupan fokus, Tab, Enter, Escape dan radio pada QA; perbaikan DC01/DC02 diretest independen. |
| R-33 source | PASS: perubahan fitur langsung pada TSX/tema melalui patch source; tidak ada script rewrite CSS/fitur. |
| R-34 tema | PASS: satu tema terang, tidak ada toggle tema yang tidak lengkap. |
| R-35 run | PASS: build dan click-through 13 skenario dicatat, tanpa pageerror pada pemeriksaan. |
| R-36 klaim | PASS: tidak ada angka performa/security/compliance yang direka. |
| R-37 arah | PASS: arah register + warna hidup dari pemilik, deal Taste dan Design Read dicatat sebelum integrasi akhir. |
| R-38 konten | PASS: nama/nilai/riwayat diberi konteks sintetis; tidak diklaim sebagai catatan siswa nyata. |
| R-39 semantik | PASS pada source/keyboard yang ditelaah: landmark, nama kontrol, label tabel/per siswa, focus dan reduced motion; bukan uji AT. |
| R-40 media/performa | PASS pada mekanisme source: tanpa media eksternal atau script pihak ketiga pemblokir; tidak mengklaim skor performa. |
| R-01 gradient | PASS: tidak ada gradient/glow. |
| R-04 ikon | PASS: setiap ikon terkait tugas/kontrol; alasan di tabel keputusan. |
| R-06 tipe | PASS: font sistem dan ukuran kerja tercatat; tracking kapital/indeks dekoratif dihapus dari register. |
| R-07 latar | PASS: mint polos, tanpa pola dekoratif. |
| R-08 panah | PASS: panah pada review dihapus; link antar-HTML tetap menyatakan navigasi. |
| R-09 badge | PASS: hanya keadaan draf/hasil dan status kajian, tidak ada badge promosi di atas judul. |
| R-10 glass | PASS: tidak ada glass/backdrop blur. |
| R-12 shadow | PASS: bayangan untuk dialog, daftar diam tanpa shadow. |
| R-13 glow | PASS: tidak ada glow. |
| R-14 kartu | PASS: panel berasal daftar/form akademik, tanpa kartu fitur berulang. |
| R-19 motion | PASS: feedback warna, motion-reduce; tidak ada entrance atau animasi looping. |
| R-22 ilustrasi | PASS: tidak ada ilustrasi dekoratif. |
| Liveliness dials | PASS: ENERGY 2/RHYTHM 1/MOTION 1 sesuai register kerja berulang. |
| Liveliness fokus | PASS: daftar nilai, daftar presensi, aktivitas anak dan keadaan rapor memimpin masing-masing layar. |
| Liveliness whitespace | PASS: gutter konsisten dan panel mengikuti panjang data, tidak ada rail kosong tambahan. |
| Liveliness aksen | PASS: zamrud disengaja, amber/rose hanya keadaan/perhatian. |
| Liveliness motif | PASS: identitas siswa/ID/nilai/status berulang sesuai objek akademik. |
| Liveliness Design Read | PASS: tercatat di awal dokumen dan diturunkan dari brief pemilik. |
| C-1 intentionality | PASS: keputusan mayor memiliki alasan pada tabel. |
| C-2 completeness | PASS dalam scope prototipe: tindakan interaktif berjalan di memori, kajian nonaktif jelas. |
| C-3 content | PASS: susunan mengikuti satu penilaian/pertemuan, bukan template marketing. |
| C-4 resilience | PASS pada viewport/keyboard/state yang diperiksa; perangkat fisik/AT belum diuji. |
| C-5 evidence | PASS: bukti aktual ditautkan, kegagalan intermediate disimpan di QA. |
| R-05 komposisi | PASS: satu register utama, tanpa hero/bento/KPI dekoratif. |
| R-11 radius | PASS: kontrol 6px, panel/dialog 8px; bukan semua pill. |
| R-15 CTA | PASS: Simpan draf, Tinjau pembagian, Bagikan hasil, Sahkan menyebut akibat. |
| R-16 copy | PASS: tidak ada slogan/buzzword AI. |
| R-20 identitas | PASS pada telaah visual: PH-03, kelas, mapel, pertemuan dan siswa menjadi isi utama. |
| R-21 dark | PASS: satu tema terang sesuai arah yang dipilih. |
| R-29 palette | PASS: keluarga zamrud, amber perhatian, rose error dengan peran tercatat. |
| R-30 orisinalitas | PASS pada telaah: arah berbasis register akademik, tidak menyalin aplikasi populer. |
| R-31 alasan | PASS: warna/layout/tipe/spacing/panel/ikon memiliki alasan tertulis. |

Review React terfokus juga diterapkan: hooks tetap di level komponen, derived state tidak dipindah ke effect, key siswa stabil, update memori memakai handler yang ada, tidak menambah fetching/storage/dependency. Tidak mengoptimalkan delapan baris dengan abstraksi baru.

Belum diperiksa: perangkat fisik/keyboard layar, pembaca layar, riset pengguna, data/API nyata, otorisasi server, persistensi, performa lapangan dan keamanan produksi. Q6/Q7/AR-04/05 tetap terbuka. Berkas HTML adalah generated output; edit source, bukan bundle. Lihat [QA rebuild](qa/rebuild-report.md) untuk rincian dan batas.
