# Hasil verifikasi facelift v2

> Revisi terbaru adalah [rebuild skill](../SKILL_REBUILD.md), dengan [QA rebuild](rebuild-report.md). Isi final3d di bawah adalah riwayat refaktor styling; JSON browser/layout dan fingerprint kini menunjuk rebuild terbaru. Jangan menyamakan fingerprint terkini dengan bundle historis pada teks arsip di bawah.

> Follow-up refactor Tailwind/shadcn: **final3d PASS untuk cakupan yang diuji**, setelah perbaikan label skenario dan overflow tablet. Baseline freeze2 lengkap tersimpan di `tmp/tailwind-refactor/qa-before/`. Bukti kegagalan intermediate 3c tetap tersedia; hasil final di bawah berasal dari built preview 3d. Locator QA memakai role/label semantik dan tidak bergantung pada class styling lama.

29 Oktober 2026, Asia/Jakarta. **PASS untuk ruang lingkup prototipe empat layar yang diuji. Bukan persetujuan rilis produksi atau sertifikasi aksesibilitas.** Seluruh data sintetis. Identitas visual masih memerlukan penilaian pemilik.

## Material dan lingkungan

QA membaca `.codex/agents/qa_engineer.toml`, SCR-05/06/07/09 dan kontrak state dalam `docs/SCREENS/SCREEN_SPEC_TEMPLATE.md`, `REDESIGN_SPEC.md`, `DESIGN_EVALUATION.md`, standar source structure, HTML referensi lama serta source React baru. PDF referensi lama ditelaah oleh tim desain; QA ini tidak mengklaim mengulang inspeksi PDF tersebut.

Eksekusi final dilakukan pada `http://127.0.0.1:5173/` yang menyajikan **built preview final3d**, memakai Chromium headless aktual melalui Playwright, Node v24.22.1, Windows. Versi browser tersimpan dalam [interaction-results.json](evidence/interaction-results.json). Ini bukan pengujian perangkat fisik. Tidak ada Git repository pada workspace; [source-sha256.json](evidence/source-sha256.json) merekam 33 file source/config/dokumentasi dan hash file dist. [served-build-sha256.json](evidence/served-build-sha256.json) membuktikan HTML dan tiga asset JS/CSS yang diterima browser identik dengan dist. Bundle utama: `main-BBFEOKCx.js`, SHA-256 `0493ce0b0cb743a0645bad8e83e81144d788e984dc13d8feafca34d15b2c69bd`.

## Matriks eksekusi

| Penerimaan | Tes aktual / bukti | Hasil |
| --- | --- | --- |
| Q01 konteks akademik dan label demo | Seluruh screenshot empat layar; T01, T04, T07, T09 | PASS |
| Q02 blank berbeda dari 0 | T01; T04 memeriksa 0 pada perkembangan Bagas setelah pembagian | PASS |
| Q03 simpan memori jujur | T04: ubah → simpan → bagikan; reload mengembalikan fixture 85; localStorage/sessionStorage kosong | PASS |
| Q04 batas dan format input | T03: -1/101/1,234/1.000,00/1e2/spasi/abc ditolak; 0/100/78,25/78.25/blank diterima | PASS, asumsi demo 0–100 maksimal 2 desimal; Q7 produksi tetap terbuka |
| Q05 bagikan sebagian melalui review | T04: 5 terisi/3 blank, konfirmasi awal disabled; snapshot hanya hasil dibagikan, field kemudian read-only | PASS |
| Q06 keyboard dialog | T05: 12 Tab tetap dalam modal; Escape membatalkan dan mengembalikan fokus ke pemicu | PASS |
| Q07 Enter tidak menerbitkan | T05 Enter pada field nilai tidak membuka dialog atau membagikan hasil | PASS |
| Q08 pertemuan mapel/status eksplisit | T07: 0 radio terpilih awal; tidak ada kontrol semua hadir; pilihan Hadir/Izin/Sakit/Alpa aktual | PASS |
| Q09 draf dan sahkan terpisah | T07: review disabled sampai 8 status terisi dan disimpan; sahkan melalui review; 32 radio terkunci, native overlay tetap opacity 0; hasil Hadir muncul di perkembangan | PASS |
| Q10 anak dan perkembangan independen rapor | T04/T09: pemilih anak mengubah nilai; perkembangan tetap tersedia saat rapor kosong | PASS |
| Q11 rapor kosong/CTA | T09 CTA membuka perkembangan anak terpilih; T04 tidak ada tombol unduh palsu | PASS |
| Q12 pencarian/count/filter | T02 mencari Bagas→1 hasil, tanpa hasil→pesan, reset→8; filter blank 3; T11 ketik 97 tetap fokus sampai blur lalu 2 hasil | PASS |
| Q13 perubahan belum tersimpan | T06 cancel tetap mempertahankan 9; discard mengembalikan 85 dan berpindah layar | PASS |
| Q14 responsive | 16 kombinasi empat layar × lebar 320/390/768/1440; scrollWidth=clientWidth; nama panjang membungkus; tabel desktop penuh; Data normal 76.2px muat dalam 91px pada select, termasuk tablet 768 | PASS pada Chromium yang diuji |
| Q15 label/heading/keyboard | Hasil layout masing-masing satu h1; input berlabel nama siswa; T08 h1 pada state; T10 skip link mencapai main; T05 fokus modal | PASS parsial, screen reader NOT RUN |
| Q16 small dialog/motion/zoom | T10 dialog muat 320×800 dan reduced-motion aktif tanpa animasi subtree main; reflow 320 pada Q14 | PASS untuk cek tersebut; zoom browser 200/400% NOT RUN |
| Q17 checks source | QA mengeksekusi Vitest 7/7, formatter dan audit 23 source: 0 error, 0 warning | PASS; build terakhir dilaporkan frontend dan root, served build diverifikasi hash oleh QA |
| Keadaan loading/empty/error/conflict | T08: seluruhnya dilabeli simulasi; tindakan simpan tidak tersedia; input 92 kembali utuh; satu h1 tetap ada | PASS untuk simulasi, bukan failure injection backend |
| Filter mapel nyata | T09 Bahasa Indonesia→1 aktivitas, Alya 86→Bagas 79; hitungan sesuai | PASS |

Seluruh 11 skenario browser **PASS**, tanpa pageerror pada setiap skenario. Skrip: [check-interactions.mjs](check-interactions.mjs); hasil: [interaction-results.json](evidence/interaction-results.json). Layout: [inspect-layout.mjs](inspect-layout.mjs), [layout-inspection.json](evidence/layout-inspection.json). Semua 16 kombinasi juga tidak menemukan teks terukur pada main di bawah 14px; pengecekan ukuran ini tidak membuktikan kontras atau WCAG.

Screenshot final tersimpan pada `qa/evidence/{grades,attendance,progress,report}-{320,390,768,1440}.png`. Inspeksi visual QA aktual mencakup grades 1440/320, attendance 390/768, progress 390 dan report 320 serta [presensi disahkan 390](evidence/attendance-signed-390.png); screenshot lain diproduksi dan diukur tetapi tidak semuanya ditelaah secara visual individual. Hierarki, tabel penuh dan pembungkusan nama tetap sebanding dengan baseline, bukan klaim identik pixel.

## Komponen dan source checks

Perintah QA yang benar-benar dijalankan:

| Perintah / lokasi | Hasil |
| --- | --- |
| `node docs/support/facelift-v2/qa/check-interactions.mjs http://127.0.0.1:5173/` / root | exit 0; 11 PASS pada built preview |
| `node docs/support/facelift-v2/qa/inspect-layout.mjs http://127.0.0.1:5173/` / root | exit 0; 16 pengukuran dan screenshot |
| `npm run test -- --reporter=default --reporter=junit --outputFile=../qa/evidence/vitest-results.xml` / prototype | exit 0; 7 PASS, 0 gagal |
| `npm run format:check` / prototype | exit 0; semua file cocok dengan Prettier |
| `python .ai/tools/source_structure_audit.py --root . --paths docs/support/facelift-v2/prototype/src` / root | exit 0; 23 file, 0 error, 0 warning |
| `python docs/support/facelift-v2/qa/capture-source.py` / root | 33 hash source/config/dokumentasi dan hash dist tersimpan |
| `node docs/support/facelift-v2/qa/verify-served-build.mjs http://127.0.0.1:5173/` / root | exit 0; 4/4 respons HTML/JS/CSS identik dengan dist |

Output perintah dan seluruh exit code final tersedia pada [final3d-commands.json](evidence/final3d-commands.json). Run dev yang gagal dan regresi 3c dipertahankan terpisah, tidak ditimpa menjadi PASS.

JUnit aktual: [vitest-results.xml](evidence/vitest-results.xml), suite `src/App.test.tsx`, describe `academic register prototype`; selector positif/negatif:

- `keeps zero distinct from blank and filters without losing edits`
- `validates decimal assumptions and blocks invalid saves`
- `restores focus after Escape and permits explicit partial publication`
- `protects dirty navigation and discards only unsaved changes`
- `requires complete saved attendance then publishes the signed snapshot`
- `switches child fixtures, filters actual subjects and opens progress from empty report`
- `preserves edits through explicitly simulated failure and conflict states`

Vitest berjalan dalam jsdom dan tidak menggantikan bukti Chromium. Source audit heuristik tidak membuktikan arsitektur. Audit folder QA sendiri membaca 0 file karena ekstensi `.mjs` bukan bahasa yang dikenali utility; hasil itu tidak dipakai sebagai bukti audit source prototype.

## Temuan dan recheck

| ID / severity | Reproduksi, expected dan actual awal | Pemilik/perbaikan | Recheck |
| --- | --- | --- | --- |
| QA-01 Medium | Buka Tinjau pembagian→Escape. Expected fokus kembali pemicu; actual BODY | Frontend, `src/components/ui/dialog.tsx`; wrapper menyimpan pemicu dan memulihkan fokus | T05 PASS final3d (perbaikan awal freeze2) |
| QA-02 Medium | Pilih skenario Memuat/Kosong/Gagal/Konflik. Expected title/h1 tugas tetap; actual semula tanpa h1 | Frontend, `src/App.tsx` dan `src/components/StatePanel.tsx`; task h1 dipertahankan | T08 PASS final3d (perbaikan awal freeze2) |
| QA-03 Low | Buka Rapor semester kosong. Expected jalur perkembangan dekat pesan; semula hanya teks dan navigasi global | Frontend, `src/components/Guardian.tsx`; CTA eksplisit | T09 PASS final3d (perbaikan awal freeze2) |
| QA-04 Medium | Filter blank→ketik nilai lebih dari satu digit. Row semula hilang sesudah digit pertama (ditemukan frontend) | Frontend, `src/components/GradeRegister.tsx`; row dipertahankan selama fokus | T11 PASS final3d (perbaikan awal freeze2) |
| QA-05 Low | Pada lebar 768, label nilai Skenario demo terpotong menjadi Data n; expected pilihan aktif terbaca penuh | Root, toolbar `src/App.tsx`; wrapper select shrink-0 dan container flex-wrap | Recheck final3d: 16/16 select textFits=true, screenshot attendance-768.png sudah terbaca penuh |
| QA-06 Medium | Generic disabled opacity pada Input.choice berisiko memperlihatkan native radio di atas kontrol sesudah sahkan (ditemukan frontend) | Frontend, `src/components/ui/input.tsx`; choice memakai disabled opacity 0 | T07 PASS final3d (perbaikan awal 3b); 32 radio computed opacity 0 dan screenshot disahkan bersih |
| QA-07 Medium | Perbaikan QA-05 pada 3c membuat toolbar tidak cukup lebar: semua layar 768 mempunyai scrollWidth 794 pada clientWidth 768; expected tanpa scroll horizontal global | Root, `src/App.tsx`; container toolbar memakai flex-wrap. Bukti gagal: layout-3c-failed.json dan attendance-768-3c-failed.png | Final3d 16/16 PASS; pada 768 scrollWidth=clientWidth=768 dengan label skenario utuh |
| QA-ENV | Run dev freeze3b T01–T10 PASS tetapi T11 mendapat halaman putih. Run gagal disimpan pada freeze3b-browser-completion.json dan failure-T11.png | Dev/HMR intermittency teramati; sebab pastinya tidak dibuktikan QA. Frontend menghentikan dev dan menyajikan built preview | Built preview 11/11 PASS pada 5174 lalu 5173 dengan hash asset identik; dev intermittency tetap batas workflow pengembangan |

Tidak ada defect prototipe terbuka dari cek yang dijalankan pada final3d. Ini tidak berarti seluruh kemungkinan cacat atau seluruh 11 keluarga layar telah diuji; dev/HMR intermittency tetap tercatat sebagai batas workflow.

## Tinjauan desain berdasarkan bukti

Task clarity dan hierarki membaik: konteks tugas, status draf dan satu roster mendahului tindakan. Angka berasal fixture aktual. Tabel desktop dan daftar ponsel koheren; tidak ada hero, metric card dekoratif atau kalender yang mengalahkan pekerjaan. Penyimpanan/membagikan/sahkan dijelaskan sebagai demo, dan rapor dipisahkan. Fokus, label, batas kecil dan state teruji sebagian; pembaca layar/kontras rendered/zoom belum lengkap. Tidak ada skor agregat atau klaim WCAG AA karena ambang penerimaan pemilik dan audit aksesibilitas penuh belum tersedia.

Tujuh kajian statis tambahan berasal dari root, bukan empat alur interaktif. QA membaca [study-layout.json](../evidence/study-layout.json): 14 entri pada 390/1440, tanpa overflow dan masing-masing satu heading; daftar error kosong. Ini **bukti layout yang dieksekusi root**, bukan bukti fungsional kajian. Login, admin, policy, finalisasi/PDF rapor, kenaikan, audit dan inbox **NOT RUN sebagai fitur**.

## Batas styling setelah refactor

QA membaca seluruh `prototype/src/styles.css`: hanya import Tailwind, variable theme dan baseline `@apply`. Bukti checker AST yang dijalankan root [source-styling-check.json](../evidence/source-styling-check.json) mencatat satu CSS global 84 baris, 0 component CSS selector, 0 deklarasi manual di luar variable theme dan 0 inline style TSX. Pencarian source QA tidak menemukan `style=` atau tag `<style>`. Ini memenuhi batas source styling yang diminta; tidak menggantikan uji browser atau membuktikan seluruh utility/class adalah pilihan desain terbaik.

## Batas dan risiko yang masih ada

- **Backend/persistensi/server auth: NOT RUN.** Tidak ada DB, API akademik, audit transaksi, constraint, BOLA/BFLA/tenant, anonymous access, JWT, logout, recovery atau linking untuk diuji pada prototipe. Skenario BACKEND_FAILURE_MODE_REVIEW/PLAN tidak terpenuhi oleh jsdom atau demo memori. Tidak ada persetujuan keamanan/rilis.
- **Notifikasi/realtime: NOT RUN.** Tidak ada provider/scheduler/token consent/replay/reconnect/DST yang diuji; aksi demo tidak mengirim pesan.
- **Perangkat/browser:** Chromium headless saja. Safari, Firefox, keyboard virtual, perangkat fisik, screen reader, zoom 200/400%, pengukuran performa dan riset guru/wali **NOT VERIFIED / NOT RUN**.
- **Artefak paket:** QA menguji dist via built preview dengan hash asset yang sama. HTML standalone/PDF/ZIP hasil repackaging refactor tetap memerlukan pemeriksaan root terpisah; laporan ini tidak menyamakan cek dist dengan eksekusi semua interaksi pada HTML inline final.
- Lifecycle koreksi setelah published/finalized sengaja dikunci. Q6/Q7 dan keputusan domain lain tetap terbuka untuk aplikasi produksi. Tidak ada klaim kesiapan implementasi server.

Frontend dan root melaporkan `npm run build` final3d: PASS. QA tidak mengeksekusi ulang build agar tidak mengubah dist yang sedang diverifikasi. QA mengeksekusi test/formatter/source audit sendiri, serta membandingkan respons preview dengan file dist melalui SHA-256. Root juga melaporkan audit keseluruhan source/tools 26 file tanpa isu; audit QA yang dieksekusi langsung mencakup 23 file dalam src.
