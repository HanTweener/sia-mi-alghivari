# Pembaruan warna — Emerald & Amber

Catatan versi: dokumen ini mencatat pembaruan sebelum rebuild skill. Arah terkini ada pada [SKILL_REBUILD](SKILL_REBUILD.md); navigasi kini mint dengan pilihan zamrud penuh, header tabel putih.

29 Oktober 2026. Pemilik menyatakan tampilan sudah oke dan meminta warna lebih hidup karena versi sebelumnya terlalu netral/pucat. Perubahan ini mempertahankan susunan, isi dan alur; palette baru menunggu tinjauan pemilik dan bukan klaim warna resmi sekolah.

Navigasi memakai hijau tua `#064E3B` dengan teks terang. Pilihan aktif memakai zamrud `#34D399` dengan teks gelap dan penanda amber. Tombol utama memakai hijau `#047857`. Latar berubah menjadi mint `#E9F7F1`, header tabel `#D1FAE5`, sukses `#BBF7D0` dan perhatian amber `#FEF3C7`. Form/daftar tetap putih. Tidak menambahkan grafik, gradient, elemen konten atau animasi.

Seluruh warna diatur melalui CSS variables dan pemetaan tema Tailwind; navigasi memakai utility token `sidebar-*`. Komponen reusable dan variant shadcn/ui tetap dipakai, tanpa selector CSS komponen atau inline style manual. Rujukan: [Tailwind theme](https://tailwindcss.com/docs/theme), [shadcn/ui theming](https://ui.shadcn.com/docs/theming), [aturan styling proyek](STYLING_CONVENTIONS.md).

## Bukti revisi ini

- Build/TypeScript dan formatter: PASS.
- Audit source 26 file: PASS, 0 error/warning. Pemeriksaan styling: PASS, satu CSS global 100 baris untuk tema/baseline, nol selector CSS komponen/inline style; `evidence/source-styling-check.json`.
- 11 skenario browser: PASS pada built preview `http://127.0.0.1:5173/`; `qa/evidence/interaction-results.json`. Dijalankan main untuk revisi warna, bukan review independen baru.
- 16 kombinasi core pada 320/390/768/1440 px: PASS, tanpa overflow global, satu h1, label skenario muat dan teks main terukur minimum 14px; `qa/evidence/layout-inspection.json`.
- Tujuh kajian × dua lebar 390/1440 px: PASS untuk layout/satu h1/pageerror; `evidence/study-layout.json`.
- 21 pasangan token opaque: PASS matematis. Teks tombol putih/zamrud 5,48:1; teks navigasi 9,23:1; metadata navigasi 7,29:1; pilihan aktif 5,06:1; indikator fokus navigasi 5,82:1. Konfigurasi dapat diuji ulang dengan `python .ai/tools/design_token_audit.py docs/support/facelift-v2/evidence/color-tokens.json`.
- Main memeriksa screenshot baru nilai 1440/390 dan administrasi 1440; warna, teks navigasi aktif/nonaktif dan adaptasi layout terlihat sesuai perubahan. Screenshot lain diproduksi dan diukur; bukan semua diperiksa individual.
- Dua HTML mandiri × dua lebar 390/1440: PASS offline, tanpa pageerror/request HTTP; `evidence/packaging-check.json`. PDF 14 halaman diperbarui memakai token tema dan screenshot terbaru, dirender ulang dengan Poppler, lalu komposisi seluruh halaman diperiksa dalam contact sheet.

Pemeriksaan browser pertama gagal terhubung karena preview dari sesi sebelumnya sudah tidak berjalan. Preview dibuka kembali, lalu seluruh skenario dieksekusi ulang sampai PASS. Bukti kegagalan setup disimpan di `qa/evidence/color-preview-not-running.json`; tidak dihitung sebagai keberhasilan.

Perhitungan token bukan sertifikasi WCAG atau uji seluruh kombinasi compositing/disabled/focus. Pembaca layar, perangkat fisik dan pengujian pengguna tidak diulang. Backend, auth/izin server dan persistensi tetap di luar scope prototipe. Hasil unit pada laporan styling sebelumnya bersifat historis; untuk revisi warna ini verifikasi perilaku memakai 11 skenario browser yang benar-benar dijalankan.

