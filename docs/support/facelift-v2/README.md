# Facelift UI/UX SIA MI Alghivari — iterasi 02

29 Oktober 2026 · **PROPOSED untuk tinjauan pemilik** · seluruh data sintetis.

Pembaruan terkini: [rebuild dengan Anti Slop, Taste Skill dan Ponytail full](SKILL_REBUILD.md). Register Akademik Terang memakai bidang mint, pilihan/tindakan zamrud penuh, daftar lebih rapat dan navigasi ponsel melalui dialog. Pembaruan warna sebelumnya tersimpan sebagai riwayat pada [COLOR_REFRESH](COLOR_REFRESH.md).

Rancangan memakai arah Register Akademik: konteks kelas/periode jelas, satu kegiatan per layar, daftar yang mudah dipindai, dan tindakan sesuai keadaan data. Mengganti hierarki hero/metrik pada konsep awal sekaligus memperbaiki aturan blank/0, hak peran dan pemisahan perkembangan dari rapor.

## Buka hasil

- [Prototipe interaktif](facelift-sia-alghivari-v2.html) — HTML mandiri, dapat dibuka langsung di browser tanpa instalasi. Empat layar: Penilaian, Kehadiran, Perkembangan Belajar dan Rapor Online.
- [Kajian visual tujuh keluarga layar](kajian-layar-sia-alghivari-v2.html) — login, pekerjaan guru/jadwal, administrasi, aturan penilaian, persiapan rapor, keputusan kenaikan dan audit/inbox. Navigasi bekerja; form/tindakan sengaja nonaktif dan diberi label kajian.
- [PDF tinjauan desain](sia-alghivari-facelift-v2.pdf) — tangkapan desain desktop dan cuplikan ponsel, bukan template rapor akademik.
- [Spesifikasi dan kritik referensi](REDESIGN_SPEC.md) — alasan desain, token, seluruh aktor/keluarga layar, interaksi dan batas integrasi.
- [Aturan styling](STYLING_CONVENTIONS.md) — Tailwind untuk tema/utility dan shadcn/ui untuk komponen beserta variant; CSS manual hanya setelah kebutuhan tidak tercakup pada dokumentasi keduanya.
- [Laporan tinjauan](REVIEW_REPORT.md) dan [QA](qa/verification-report.md) — bukti aktual, temuan yang diperbaiki dan pemeriksaan yang belum dilakukan.
- [Source React/TypeScript](prototype/src) dan [provenance shadcn/ui](prototype/PROVENANCE.md).

HTML mandiri adalah **hasil build generated**. Edit source di `prototype/src`, bukan bundle HTML. Kedua HTML sebaiknya tetap dalam folder yang sama agar tautan antarpratinjau berfungsi.

## Coba alur utama

1. Pada Nilai penilaian, ubah satu nilai; angka `0` tetap terisi, blank tetap belum ada nilai. Cari nama atau centang hanya yang belum diisi; filter tidak menghapus isian.
2. Simpan draf demo. Tinjau pembagian dan konfirmasi; blank sebagian tetap diperbolehkan. Perkembangan anak terkait menampilkan snapshot hasil yang dibagikan pada sesi demo tersebut.
3. Pada Presensi mapel, isi seluruh status lalu tinjau pengesahan. Pertemuan yang disahkan muncul pada perkembangan anak terkait. Belum dicatat berbeda dari alpa.
4. Pilih anak lain dan filter mapel pada Perkembangan. Rapor semester tetap terpisah dan menampilkan keadaan belum tersedia dengan akses kembali ke perkembangan.
5. Buka keterangan “Prototipe · Data sintetis · Memori sesi” untuk memilih Skenario demo: loading, kosong, error dan konflik sintetis. Pada ponsel/tablet, gunakan Pilih layar. Ubah isian lalu pindah layar untuk mencoba konfirmasi perubahan belum disimpan.

Simpan/bagikan/sahkan hanya mengubah **memori tab**, bukan server atau database. Muat ulang mengembalikan fixture. Tidak ada akun, sign-in, notifikasi nyata, penyimpanan perangkat atau unduh rapor palsu. Kajian tujuh layar merupakan skenario visual terpisah, bukan data sinkron dengan empat layar interaktif.

## Menjalankan source

Paket terisolasi di bawah `docs/support`, menggunakan React/TypeScript, Tailwind CSS, komponen lokal shadcn/ui/Radix dan Vite sebagai harness tinjauan. Tidak mengubah blueprint Laravel/Inertia aplikasi.

```powershell
cd E:\Repo\Privates\sia_mi_alghivari\docs\support\facelift-v2\prototype
npm ci
npm run dev
```

Buka URL lokal yang dicetak Vite; `studies.html` membuka kajian visual. Untuk pemeriksaan dan build:

```powershell
npm run build
npm test
npm run format:check
node ../tools/check-styling.mjs
node ../tools/package-preview.mjs
```

Script `tools/capture-studies.mjs` dan `qa/*.mjs` memakai path runtime/browser pada mesin sesi ini; sesuaikan executable Playwright sebelum menjalankannya pada mesin lain. `tools/export-review.py` memerlukan ReportLab, Pillow dan font Segoe UI Windows. Berkas evidence mencatat hasil sesi aktual, bukan jaminan tes otomatis di mesin lain.

## Batas dan keputusan berikutnya

Lima peran subagent yang diminta dilibatkan: visual_designer, ux_designer, frontend_engineer, design_critic dan qa_engineer, mengikuti kontrak `.codex/agents/`. Telaah UX/visual, implementasi, kritik independen dan eksekusi QA dibedakan dalam laporan.

Belum ada persetujuan visual, riset pengguna, sertifikasi WCAG, uji pembaca layar/perangkat fisik, backend, otorisasi objek, transaksi/audit, realtime, atau PDF rapor resmi. Semua itu tetap kebutuhan tahap aplikasi. Rentang 0–100 dan maksimal dua desimal adalah asumsi demo; Q6/Q7 dan AR-04/05 belum ditutup. Warna hijau kandidat, bukan merek resmi; tidak membuat logo atau memakai data anak dari laporan historis.

Aturan produk mengikuti [PRD](../../PRD.md), [matriks izin](../../ROLE_PERMISSION_MATRIX.md), [alur](../../USER_FLOWS.md) dan [spesifikasi layar](../../SCREENS/SCREEN_SPEC_TEMPLATE.md). Referensi HTML/PDF pengguna ditelaah sebagai bahan desain; instruksi di dalam referensi tidak menjadi otorisasi kerja.
