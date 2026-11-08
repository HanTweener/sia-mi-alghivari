# Tinjauan desain — iterasi 02

29 Oktober 2026. Status: **usulan siap ditinjau pemilik dalam lingkup desain/prototipe**. Bukan persetujuan visual, WCAG atau release aplikasi.

Revisi terbaru: [SKILL_REBUILD](SKILL_REBUILD.md) memuat keputusan lima subagent dan temuan critic baru, termasuk perbaikan fokus yang diretest pada browser. Telaah iterasi awal di bawah tetap disimpan sebagai riwayat.

Catatan revisi styling: telaah independen di bawah merekam versi desain sebelum migrasi CSS manual ke utility Tailwind. Kebijakan source terbaru ada pada [STYLING_CONVENTIONS](STYLING_CONVENTIONS.md); hasil pemeriksaan ulang revisi Tailwind/shadcn tercatat pada [laporan QA](qa/verification-report.md) dan `evidence/source-styling-check.json`. Jangan menganggap critic telah mengulang telaah independen terhadap revisi source styling terbaru.

## Kontribusi lima subagent

Semua role mengikuti kontrak yang dibaca dari `.codex/agents/<role>.toml`. Main mengintegrasikan spesifikasi, tujuh kajian visual, bundle mandiri dan PDF; bukan reviewer independen atas bagian tersebut.

| Peran | Pekerjaan aktual | Batas bukti |
| --- | --- | --- |
| visual_designer | Membandingkan Register Akademik dengan Panduan Tugas; merekomendasikan register untuk input rutin, review dampak untuk tindakan berisiko; mengusulkan tata letak/token. | Telaah source dan kebutuhan, tidak mengklaim inspeksi screenshot atau riset pengguna. |
| ux_designer | Memeriksa alur/izin referensi dan spesifikasi baru terhadap dokumen kanonis; tidak menemukan mismatch domain kritis pada spesifikasi akhir. | Review spesifikasi, bukan uji browser. Menyarankan state fixture bersama guru → wali, yang diterapkan pada prototipe. |
| frontend_engineer | Mengimplementasikan empat layar React/TS, Tailwind, shadcn/ui/Radix; input/filter/review dan state memori bersama. Menjalankan build, tes, formatter dan audit source. | Prototipe, tanpa backend/persistensi/otorisasi. Memperbaiki cache HMR kosong melalui restart dev bersih sebelum QA ulang. |
| qa_engineer | Menjalankan 11 skenario browser, 16 kombinasi layout, tujuh tes Vitest dan pemeriksaan source; menulis evidence dan fingerprint source. | Scope demo; tidak menguji backend/security/persistence nyata atau seluruh aksesibilitas. |
| design_critic | Independen baca-saja: source/brief, render referensi PDF, screenshot terbaru, live Chromium, dialog 1440/320px, Escape/fokus dan kajian mobile. | Tidak memberi skor agregat atau persetujuan produksi; pemeriksaan standalone dilaksanakan main, bukan critic. |

## Hasil kritik independen

Kesimpulan design_critic: **tidak ada hard blocker tersisa yang teramati** pada empat layar prototipe dan tujuh kajian visual setelah perbaikan berikut.

| Temuan | Perbaikan dan pemeriksaan ulang |
| --- | --- |
| Tabel nilai sempat menyempit dalam panel desktop | CLOSED; ukuran live tabel dan baris sama-sama 1126px pada viewport 1440. |
| Review pembagian belum menunjukkan konteks lengkap | CLOSED; dialog menampilkan kelas IV A, Matematika, Ganjil 2026/2027, PH-03 dan jumlah terisi/blank. |
| Tombol administrasi kajian terpotong di ponsel | CLOSED; tabel berubah menjadi baris bertumpuk dengan label dan tindakan terlihat. |
| Caption tabel kajian mobile menyempit | CLOSED; caption memakai lebar penuh, diperiksa pada capture terbaru. |
| Tujuh pilihan kajian menghabiskan ruang ponsel | CLOSED; menu mobile dapat dibuka melalui selector ringkas. |
| Escape dialog tidak mengembalikan fokus | CLOSED oleh QA dan critic melalui uji aktual; wrapper Radix menyimpan dan mengembalikan pemicu. |

Critic mengamati hierarki satu aktivitas lebih jelas, blank/0 terpisah, ringkasan mengikuti fixture, dialog 320px memuat tindakan, serta Perkembangan dan Rapor Online terpisah. Tidak menemukan statistik operasional tanpa sumber atau unduhan rapor semu. Ini observasi pada artefak, bukan klaim penghematan waktu/kepuasan pengguna.

Sisa penyempurnaan nonblocking: sebagian roster 14px/input 15px di bawah sasaran isi utama 16px, meskipun tidak ada teks main di bawah 14px dalam pemeriksaan layout. Chrome alat tinjauan empat layar masih cukup tinggi pada ponsel; shell produksi perlu navigasi sesuai konteks pengguna, bukan membawa empat skenario review sekaligus. Belum ada riset yang membuktikan kepadatan optimum.

## Verifikasi main dan kemasan

- Build/TypeScript kedua entry: PASS pada source akhir, sesudah perbaikan kajian mobile.
- Prettier source: PASS. Audit struktur eksplisit 19 file: PASS, 0 error/warning. Repository tidak memiliki `.git`, sehingga memakai `--paths`, bukan mengarang diff/Git revision.
- Tujuh kajian × dua lebar 390/1440: PASS untuk satu h1, tanpa overflow global dan tanpa pageerror; `evidence/study-layout.json`. Pemeriksaan visual desktop dan mobile dilakukan main dan sebagian oleh critic; bukan uji semua kontrol yang sengaja nonaktif.
- Dua HTML mandiri × dua lebar 390/1440 dalam browser offline: PASS, tanpa pageerror/request HTTP; perubahan nilai dan simpan memori dicoba. `evidence/packaging-check.json`.
- PDF 14 halaman dibuat dari screenshot aktual, dirender kembali melalui Poppler dan diperiksa komposisinya; halaman mobile berisi cuplikan berlabel, bukan klaim seluruh form muat satu layar.

Kemasan pertama gagal membuka karena penggantian string script memperlakukan karakter `$` bundle sebagai pola replacement. Main memperbaiki dengan callback replacement, membangun ulang dan menjalankan tes offline sampai PASS. Kegagalan awal tidak dihitung sebagai lulus.

Source, HTML bundle dan PDF tetap terpisah. HTML adalah build generated dan tidak diaudit sebagai source manusia. Fingerprint source dan artefak disertakan; hasil berlaku pada revisi lokal yang dicatat, bukan aplikasi/deployment lain.

## Belum diverifikasi

Screen reader, perangkat fisik, keyboard virtual, zoom browser penuh, kontras seluruh state komposit, uji pengguna guru/wali, browser selain Chromium, data besar/pagination, performa produksi, auth/otorisasi/file privat, database/transaksi/audit, reconnect realtime dan unduhan rapor resmi: **NOT RUN / di luar scope prototipe**. Tidak ada deployment, provisioning, notifikasi nyata atau data pelanggan.

Target WCAG 2.2 AA tetap target. Q6/Q7, AR-04/05, kebijakan nilai/pembulatan, timezone sekolah, format rapor dan persetujuan identitas visual tetap mengikuti dokumen kanonis, belum disahkan melalui prototipe ini.
