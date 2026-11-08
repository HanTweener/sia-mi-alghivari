# Prototipe tinjauan Register Akademik

Prototipe React/TypeScript, Tailwind CSS v4 dan komponen shadcn/ui lokal.
Ini bukan implementasi aplikasi produksi. Semua data sintetis, tanpa API,
login, notifikasi, localStorage atau persistensi. Muat ulang mengatur ulang
seluruh perubahan. Riwayat peramban/penutupan tab belum diberi penjaga draf;
konfirmasi tersedia saat pindah empat layar prototipe.

Gunakan Node.js 22 atau 24 dan npm:

```powershell
cd docs/support/facelift-v2/prototype
npm ci
npm run dev
```

Alamat default `http://127.0.0.1:5173/`. `studies.html` adalah katalog visual
terpisah dengan formulir specimen nonaktif, bukan alur aplikasi.

```powershell
npm run build
npm test
npm run format:check
```

Build memeriksa TypeScript dan menghasilkan dua entry di `dist/`.
Parent package menyediakan HTML mandiri di folder induk untuk tinjauan tanpa
toolchain. [Provenance komponen](PROVENANCE.md).

Untuk menyajikan hasil build sebagai pratinjau stabil, gunakan:

```powershell
npm run build
npx vite preview --host 127.0.0.1 --port 5173 --strictPort
```

Hentikan server lain pada port yang sama sebelum memilih mode ini. Pada sesi
verifikasi, dev/HMR sempat menampilkan modul kosong secara intermiten; gejalanya
dicatat pada laporan QA. Pratinjau di port 5173 kini menyajikan hasil build yang
diuji, tanpa HMR. Ini harness tinjauan lokal, bukan deployment aplikasi.

Styling mengikuti [aturan pemilik](../STYLING_CONVENTIONS.md): `styles.css`
hanya memuat import Tailwind, variabel tema, `@theme inline` dan baseline.
Layout/state/responsif memakai utility; komponen UI reusable memakai variant.
Tidak ada file CSS halaman atau pengecualian CSS komponen manual.

Alur interaktif:

1. Nilai: ubah nilai, cari nama/ID atau filter blank, simpan draf memori,
   tinjau, centang cakupan, terbitkan demo. Blank berbeda dari 0; desimal
   koma/titik maksimal dua digit adalah asumsi demo. Published dikunci.
2. Presensi: pilih setiap status secara individu, simpan draf, tinjau dan
   sahkan jika seluruh siswa sudah dicatat. Disahkan dikunci.
3. Perkembangan: hanya hasil dibagikan/presensi disahkan, pemilih anak dan
   filter mapel mengubah isi. Fixture historis tetap diberi label sintetis.
4. Rapor: keadaan belum tersedia dan jalan kembali ke Perkembangan; tidak
   ada unduhan palsu.

Pemilih skenario QA menampilkan loading, kosong, gagal muat dan konflik
sintetis. Kembali ke data demo mempertahankan isian memori. Ini tidak
menguji jaringan, transaksi, otorisasi atau konflik server sungguhan.
