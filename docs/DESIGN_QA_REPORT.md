# Design QA Report - SIA MI Alghivari

Date: revisi rancangan 29 Oktober 2026, bukan tanggal QA UI. Reviewer implementasi/build belum ditunjuk; build/version/SHA aplikasi inti belum tersedia. Status laporan **PROPOSED / NOT VERIFIED**, pengujian inti **NOT RUN**.
Scope/screens yang direncanakan: SCR-01..11 dan SX-01..11 pada [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md). Approved design direction: belum ada persetujuan menyeluruh; kandidat Register Akademik, [DESIGN_DIRECTION](DESIGN_DIRECTION.md), [DESIGN_DECISIONS](DESIGN_DECISIONS.md) serta [facelift-v2](support/facelift-v2/README.md) menjadi acuan sesuai statusnya. Prototipe adalah referensi, bukan panduan wajib atau bukti implementasi inti.

## Evidence and limitations

- Actual build/code/prototype inspected untuk QA aplikasi inti: belum tersedia/belum diinspeksi. Tugas ini membaca dokumen dan menyiapkan kontrak pemeriksaan, tidak menjalankan browser, inspector, render PDF atau pengujian UI.
- Screenshot/video paths + viewport/device details: belum ada capture aplikasi inti. Bukti historis prototipe berada pada [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md) dan [qa/rebuild-report](support/facelift-v2/qa/rebuild-report.md), sesuai scope/revisinya; tidak dijalankan ulang atau dipindahkan menjadi hasil QA server.
- Keyboard/assistive-tech/a11y/contrast tools actually run: NOT RUN untuk inti. Perhitungan hex kandidat yang disebut DESIGN_SYSTEM bukan contrast rendered, keyboard atau sertifikasi WCAG aplikasi. Tidak ada skor Lighthouse/browser/performa yang dihasilkan pada rancangan laporan ini.
- What could not be checked, and why: semua interaksi, role/context/props nyata, responsif, fokus, request gagal/conflict/revoke, private files dan PDF karena implementasi inti belum tersedia. Tidak dapat menyatakan tidak ada blocker atau semua controls berfungsi.

Pemeriksaan yang direncanakan mengikuti [DESIGN_BRIEF](DESIGN_BRIEF.md), [DESIGN_SYSTEM](DESIGN_SYSTEM.md), [CONTENT_STYLE](CONTENT_STYLE.md), [TEST_PLAN T-18](TEST_PLAN.md) dan [ACCEPTANCE_TEST_MATRIX](ACCEPTANCE_TEST_MATRIX.md). Catat per keluarga layar: revisi/build, role/fixture, keadaan awal, langkah, expected/actual, viewport/browser/OS/device, keyboard/zoom/assistive method dan evidence teredaksi. Gunakan data sintetis dari [TEST_DATA_PLAN](TEST_DATA_PLAN.md), termasuk nama panjang/blank/0, kosong/error, policy99/100/101, sumber usang, expired delegation, PDF gagal dan reconnect.

Kriteria penerimaan utama: input nilai/absensi sesuai konteks dan status, konfigurasi global hanya pihak berhak, Perkembangan Belajar tersedia tanpa rapor, Rapor Online hanya published/current aktif anak terkait, serta approve tidak apply. Uji seluruh state loading/empty/populated/validation/error/success/denied/stale-version/network-unknown; pesan berhasil harus sesuai commit/file ready, tidak sekadar animasi. Scope berpindah atau izin hilang membersihkan data lama; tidak ada autosave/offline POST retry/localStorage untuk mempertahankan akses.

Target viewport 360×800 dan 1440×900 mengikuti AC-X03, ditambah intermediate/zoom/reflow relevan; itu target uji, bukan hasil perangkat nyata. Periksa Tab/label/heading/fokus dialog/error, screen reader, target touch, pesan selain warna dan reduced motion. Kontrol disabled/hidden tidak menggantikan penolakan server. Reverb update tidak merebut fokus atau menggandakan badge; nilai blank dan0 tetap jelas. PDF diperiksa melalui render aktual dan snapshot yang benar mengikuti aturan numerik Q7.1-Q7.5 yang diterima ketika implementasi Dompdf/wrapper 3.1.x (D-17) tersedia. Susunan Q7.1 sudah diterima pada D-18: uji pertemuan per mapel, catatan opsional/teks panjang/escaping dan snapshot detail yang sama, catatan draft binaan saja, histori final immutable, font/pagination, tanpa menyalin format/identitas contoh rapor sebagai keputusan.

## Rubric (see `.ai/standards/DESIGN_EVALUATION.md`)

Rubrik [DESIGN_EVALUATION](../.ai/standards/DESIGN_EVALUATION.md) dipertahankan. Rating0-5 hanya sesudah observasi aktual; N/V berarti belum terverifikasi, bukan0 atau skor lulus. Rumus kelak `(rating/5) × weight`; total diagnostik tidak mengatasi blocker. Ambang angka penerimaan belum dipilih, tidak ditetapkan setelah melihat hasil.

| Criterion | Weight | Evidence-based rating 0–5 or N/V | Weighted score / reason |
| --- | ---: | --- | --- |
| Task clarity & flow | 20 | N/V | N/V. Dibutuhkan alur nyata SCR-04..10/SX-04..10: konteks periode/kelas/mapel/anak jelas, save/share/finalize/publish dan approve/apply terpisah |
| Hierarchy & content | 15 | N/V | N/V. Dibutuhkan screenshot/observasi konteks dan data scoped, label blank/0/pertemuan/rekap sementara, error/kosong spesifik tanpa angka/fitur palsu |
| Interaction/state coverage | 15 | N/V | N/V. Dibutuhkan positive/failure/conflict/revoke per SCR/SX, fokus/error recovery serta expected/actual request yang membuktikan tombol bekerja |
| Accessibility | 20 | N/V | N/V. Dibutuhkan keyboard/assistive/zoom/contrast rendered dan scan sesuai scope, terutama register/dialog/error; target WCAG 2.2 AA belum terbukti |
| Responsive robustness | 15 | N/V | N/V. Dibutuhkan viewport/device nyata tercatat,360×800/1440×900 dan intermediate relevan, nama panjang/overflow/scroll/fokus tak tertutup |
| Distinctiveness & consistency | 10 | N/V | N/V. Dibutuhkan konsistensi arah yang benar-benar disepakati, token/komponen Tailwind-shadcn dan perilaku task-specific; bukan skor karena plugin dipakai |
| Performance & trust | 5 | N/V | N/V. Dibutuhkan timing lab berlabel dan pesan commit/download benar, private props/file, scope berubah aman; PB-08 dan SLI masih usulan, bukan hasil |

Total: **N/V**, seluruh dimensi belum dinilai; bobot total 100 bukan skor kualitas 100. Hard blockers: **belum diperiksa / NOT VERIFIED**, bukan none observed. Kegagalan aksesibilitas/alur utama/responsif yang serius atau copy menipu kelak menghalangi signoff terlepas total.

Penerapan antislop During sesi, taste-skill app screens dan Ponytail full mengikuti [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md). Skill/inspector/gate tidak memberi persetujuan visual atau menggantikan bukti usability/accessibility/backend. Jangan menambahkan dependency/animasi/chart/fitur agar rubrik tampak lengkap; reusable Tailwind/shadcn mengikuti kebutuhan yang dirancang.

## Findings & recheck

Tabel ini khusus temuan aktual setelah inspeksi. Risiko/expected failure yang sudah ada pada SX/DV/test plan tidak dimasukkan sebagai bug yang seolah direproduksi. Jika ada temuan, beri ID DQ, severity/dampak, build/role/langkah, evidence, koreksi/owner dan hasil recheck aktual; riwayat gagal dipertahankan.

| ID | Severity | Surface & reproduction | Actual evidence | User harm | Specific correction | Recheck |
| --- | --- | --- | --- | --- | --- | --- |
| Belum ada ID temuan aktual | Belum dinilai | Inspeksi aplikasi inti NOT RUN | Belum tersedia | Belum dinilai; bukan tidak ada risiko | Menunggu inspeksi; tidak mengarang fix/owner | NOT RUN |

Release recommendation: **PENDING / NOT VERIFIED**, belum ada rekomendasi BLOCK/CONDITIONAL/PASS dari reviewer implementasi. Reviewer & approver: belum ditunjuk/belum ada keputusan atau tanggal aktual. Unverified risks and follow-up: seluruh rubrik/SCR/SX/DV, API nyata, akses server, PDF serta keputusan visual/Q7 terkait belum dibuktikan. Lihat [QA_EVIDENCE_REPORT](QA_EVIDENCE_REPORT.md), [SECURITY_VERIFICATION_REPORT](SECURITY_VERIFICATION_REPORT.md) dan [RELEASE_PLAN](RELEASE_PLAN.md) untuk bukti lintas bidang yang masih harus dikumpulkan; rancangan ini tidak memberi GO rilis.

D-28 telah dipilih, bukan hasil uji: API-T55..57/DBV29..30 dan T-11/N-T15 memerlukan bukti koreksi-grant/state, hold per siswa, policy/calculation asli, immutable snapshot/PDF, race render/publish/revoke, guardian list/count/detail/file dan progression applied tetap. UI koreksi/notice/retry perlu browser/keyboard/responsif sesuai SCR-05..10. Actual **NOT RUN / NOT VERIFIED**; kesimpulan reviewer implementasi/GO tetap **PENDING**. [Acuan kanonis](ACADEMIC_FLOW.md).

## Hipotesis, bukti lokal dan bukti publik — D-34

Hipotesis/expected di bawah adalah rancangan, bukan hasil observasi. Gunakan [kontrak bukti bertahap](QA_EVIDENCE_REPORT.md) untuk envelope/revisi/status/privasi. Bukti prototipe hanya referensi; hasil lokal tidak otomatis menjadi hasil publik, dan public smoke tidak menggantikan tes lokal yang sesuai risiko.

| Tahap | Hipotesis dan rujukan | Expected / kriteria penerimaan | Bukti yang direncanakan | Bukti / hasil aktual | Kesimpulan reviewer |
| --- | --- | --- | --- | --- | --- |
| Lokal inti | Referensi desain dapat menjadi UI tugas nyata yang aksesibel; SCR/SX/DV dan T-18 | Nilai/absensi/konfigurasi/perkembangan/rapor/approval berfungsi terhadap backend, loading/error/conflict/revoke jujur, keyboard/reflow/fokus benar | Build/role/fixture, steps expected-actual, capture aplikasi inti, viewport/device/keyboard/assistive/contrast rendered dan actual PDF snapshot; bukan screenshot prototipe | Belum tersedia; NOT RUN / NOT VERIFIED | PENDING; belum ada review implementasi |
| Publik demo | Build dan data scoped mempertahankan UX melalui network/HTTPS yang sebenarnya | Login/navigasi/submit/read-back/PDF/reconnect dan state gagal yang aman tetap benar di browser/device yang dicatat | Public target/digest/config dan capture/trace sintetis aman, layout/check accessibility terpengaruh; skor/rating hanya setelah observasi dan tidak disalin dari lokal | Belum tersedia; NOT RUN / NOT VERIFIED | PENDING; belum ada review implementasi |

Isi evidence/result per fitur/skenario setelah benar-benar dijalankan; simpan hasil gagal, perubahan dan recheck secara terpisah. Tidak membuat command/path/artefak/skor/approval fiktif, tidak mengubah required NOT RUN menjadi PASS. Domain/pengiriman nyata/aktivasi/restore target aktif memerlukan scope operasional yang sesuai; dokumen ini tidak menjalankannya.
