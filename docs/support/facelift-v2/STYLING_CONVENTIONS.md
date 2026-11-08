# Aturan styling prototipe

Arahan pemilik, 29 Oktober 2026: Tailwind menjadi dasar CSS global dan styling; shadcn/ui untuk komponen reusable beserta variant; CSS manual hanya bila kebutuhan tidak ditemukan dalam dokumentasi Tailwind maupun shadcn/ui.

## Pembagian tanggung jawab

- `prototype/src/styles.css`: import Tailwind, variabel tema semantik, pemetaan `@theme inline`, dan baseline `@layer base` yang berlaku global. Tidak menjadi tempat class layout/komponen atau media query halaman.
- TSX halaman/komposisi: utility Tailwind untuk grid/flex, spacing, ukuran, typography, border, responsive dan keadaan interaksi. `cn` menggabungkan utility secara terbaca; tidak menyembunyikan class CSS manual.
- `prototype/src/components/ui`: komponen reusable dengan pola shadcn/ui dan provenance. Variant didefinisikan pada komponen, menggunakan `class-variance-authority` bila ada variasi tampilan. Button, Input, NativeSelect, Textarea, Label, Badge, Table, Alert dan Dialog mempunyai tanggung jawab terpisah.
- Komposisi produk seperti daftar siswa atau kajian kesiapan rapor memakai komponen UI tersebut. Jangan menambahkan engine styling atau dependency UI lain hanya untuk mengganti utility yang sudah tersedia.

Tema memakai nama semantik seperti `background`, `foreground`, `primary`, `muted-foreground`, `border`, `input`, `warning` dan `canvas`. Perubahan warna pusat tidak menuntut pencarian hex pada seluruh komponen. Nilai ukuran khusus yang menjaga rancangan lama dapat memakai arbitrary values yang terdokumentasi; itu bukan alasan membuat selector CSS manual.

## Rujukan yang diperiksa

| Kebutuhan | Dokumentasi resmi | Penerapan |
| --- | --- | --- |
| CSS global dan tema | [Tailwind theme variables](https://tailwindcss.com/docs/theme), [shadcn/ui theming](https://ui.shadcn.com/docs/theming) | Import, CSS variables, `@theme inline`, baseline tema. |
| Styling komposisi | [Tailwind utility classes](https://tailwindcss.com/docs/styling-with-utility-classes) | Layout dan tampilan berada di TSX, bukan class `.app-shell`/`.study-*` manual. |
| Adaptasi lebar | [Tailwind responsive design](https://tailwindcss.com/docs/responsive-design) | Utility breakpoint dan rentang `max-[...]`; tidak memakai media query halaman di CSS. |
| Fokus, readonly, checked, disabled dan ARIA/data | [Tailwind state variants](https://tailwindcss.com/docs/hover-focus-and-other-states) | `focus-visible`, `peer-checked`, `disabled`, `aria-*`, serta variant selector seperlunya. |
| Ukuran/selector khusus | [Tailwind arbitrary values/properties/variants](https://tailwindcss.com/docs/adding-custom-styles) | Kolom roster, batas dialog dan adaptasi tabel menggunakan kemampuan yang terdokumentasi. |
| Button dan variant | [shadcn/ui Radix Button](https://ui.shadcn.com/docs/components/radix/button) | CVA variant dan ukuran, komposisi Slot, fokus serta disabled. |
| Form reusable | [Input](https://ui.shadcn.com/docs/components/radix/input), [Native Select](https://ui.shadcn.com/docs/components/radix/native-select), [Textarea](https://ui.shadcn.com/docs/components/radix/textarea), [Label](https://ui.shadcn.com/docs/components/radix/label) | Kontrol native berlabel dengan gaya reusable; API perilaku tetap terjaga. |
| Status dan data | [Badge](https://ui.shadcn.com/docs/components/radix/badge), [Alert](https://ui.shadcn.com/docs/components/radix/alert), [Table](https://ui.shadcn.com/docs/components/radix/table) | Status berteks, feedback dan tabel semantik; variasi produk dijelaskan pada provenance. |
| Review/konfirmasi | [shadcn/ui Radix Dialog](https://ui.shadcn.com/docs/components/radix/dialog) | Portal, judul/deskripsi, trap/fokus kembali; utility untuk posisi/ukuran. |

Dokumentasi daring dapat menampilkan versi lebih baru. Refaktor memakai kemampuan yang tersedia pada dependency Tailwind 4.1 yang sudah dikunci di prototipe; tidak menaikkan versi atau mengganti Radix dengan fondasi lain demi mengikuti tampilan default dokumentasi terbaru.

## Aturan pengecualian

Sebelum menulis CSS manual: tentukan kebutuhan spesifik, periksa utility/variant resmi Tailwind dan komponen/variant shadcn/ui yang relevan, lalu periksa apakah komposisi atau arbitrary value menyelesaikannya secara terbaca. Jika benar-benar tidak tercakup, catat kebutuhan, tautan yang diperiksa, alasan, file dan selector pengecualian sebelum implementasi.

**Pengecualian CSS manual untuk layout/komponen pada refaktor ini: tidak ada.** Variabel tema dan baseline global adalah konfigurasi sesuai dokumentasi, bukan pengecualian layout.

Hasil build akan tetap berisi banyak CSS hasil kompilasi Tailwind. Batas ini berlaku pada source manusia di `prototype/src`, bukan jumlah baris CSS generated pada HTML mandiri atau `dist`.
