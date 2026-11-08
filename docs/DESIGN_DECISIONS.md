# Design Decision Log — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Log konkret keputusan pengalaman; struktur field template dipakai per catatan. Pilihan visual tetap **PROPOSED**, belum persetujuan seluruh implementasi. Aturan produk mengikuti PRD. Prototipe dan bukti tinjauan sudah tersedia, tetapi aplikasi inti/server belum diimplementasikan; riset pengguna dan tes backend NOT RUN. Keputusan teknis tetap pada [ADR](DECISIONS/ADR_TEMPLATE.md).

## DD-001 — Register Akademik sebagai arah yang direkomendasikan

**Status:** Proposed.  
**Date / owner:** 28 Oktober 2026; penyusun mengusulkan, pemilik proyek memutuskan.  
**User outcome and relevant evidence:** guru/admin dapat memindai konteks dan roster satu aktivitas; kebutuhan tercatat pada PRD/USER_FLOWS, belum usability evidence.  
**Options compared:** A daftar/form register dengan konteks tetap; B panduan langkah yang lebih lapang.  
**Choice and why:** rekomendasikan A untuk mengurangi state/routing tahap tambahan pada input rutin; belum dipilih pemilik.  
**Tradeoffs for a11y, usability, mobile, performance:** tabel dapat padat; ponsel membutuhkan daftar berlabel. Tidak ada estimasi kecepatan atau biaya terukur.  
**Validation signal and revisit condition:** uji pengenalan kelas/periode, penyelesaian input dan koreksi; kaji B bila pengguna sulit mengenali langkah/konteks.  
**Affected specs/components:** [DESIGN_BRIEF](DESIGN_BRIEF.md), [DESIGN_DIRECTION](DESIGN_DIRECTION.md), [DESIGN_SYSTEM](DESIGN_SYSTEM.md).

## DD-002 — Navigasi berdasarkan tugas dengan konteks yang terlihat

**Status:** Proposed, menerapkan prinsip role rangkap yang sudah dikonfirmasi.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** akun admin+guru/principal tetap mengetahui pekerjaan dan objek aktif; PRD serta matriks izin menjadi landasan.  
**Options compared:** satu menu panjang berdasarkan role; konteks tugas dengan area berizin dan pemilih periode/anak.  
**Choice and why:** konteks Administrasi/Mengajar/Wali kelas/Kepala sekolah/Portal wali sesuai grant; tidak memakai switch yang mengubah hak.  
**Tradeoffs for a11y, usability, mobile, performance:** drawer mobile perlu fokus/label; perubahan konteks dengan unsaved form perlu keputusan pengguna, bukan pemindahan state ke anak lain.  
**Validation signal and revisit condition:** pengguna selalu dapat menyebut kelas/periode/anak aktif, menu tidak membuka hak tersembunyi; kaji label jika membingungkan.  
**Affected specs/components:** SCR-02/03/07, navigation/context selector pada [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md), [ROLE_PERMISSION_MATRIX](ROLE_PERMISSION_MATRIX.md).

## DD-003 — Satu aktivitas, tabel native dan daftar siswa pada ponsel

**Status:** Proposed.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** guru mengisi satu assessment/pertemuan tanpa membandingkan banyak kolom semester sekaligus; sesuai J-04/J-05.  
**Options compared:** spreadsheet custom dengan keyboard/import/paste massal; tabel HTML input standar dan daftar ponsel.  
**Choice and why:** native table/input/select/radio dengan Tab biasa; adaptasi mobile tetap satu sumber state/data. Hindari engine spreadsheet atau import yang belum diminta.  
**Tradeoffs for a11y, usability, mobile, performance:** Tab bisa panjang; gunakan urutan siswa konsisten, heading/label dan jalur lompat bagian. Tidak mengandalkan arrow navigation custom.  
**Validation signal and revisit condition:** keyboard dan ponsel dapat mengisi/review roster panjang dengan nama panjang; kaji pagination/pola fokus hanya jika bukti menunjukkan masalah.  
**Affected specs/components:** SCR-05/06, table/list, form field, [CONTENT_STYLE](CONTENT_STYLE.md).

## DD-004 — Status operasional dan rapor mempunyai label serta aksi terpisah

**Status:** Proposed untuk UI; pemisahan domain sudah dikonfirmasi.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** wali membaca Perkembangan Belajar kapan saja tanpa mengira itu rapor final; guru/W memahami perbedaan simpan, bagikan, sahkan, finalisasi dan publikasi.  
**Options compared:** satu halaman nilai/rapor dengan label final generik; dua jalur dengan state/aksi domain eksplisit.  
**Choice and why:** Perkembangan Belajar dan Rapor Online dipisah. Rapor staf: preview → finalized → file siap → published; finalized tidak tampil untuk wali. Blank0 dan pertemuan tetap dibedakan.  
**Tradeoffs for a11y, usability, mobile, performance:** perlu empty state dan tautan antarjalur yang jelas; status diberi teks, bukan hanya warna. Tidak menambah workflow baru.  
**Validation signal and revisit condition:** wali dapat membedakan rekap sementara dan dokumen published; file error tidak memberi sukses palsu.  
**Affected specs/components:** SCR-04..09, badge, [ACADEMIC_FLOW](ACADEMIC_FLOW.md), [API_CONTRACT](API_CONTRACT.md).

## DD-005 — Simpan eksplisit dan pemulihan konflik tanpa menimpa otomatis

**Status:** Proposed.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** pengguna tahu perubahan yang sudah dikirim/tersimpan; AC-X02/04 dan kontrak versi menjadi dasar.  
**Options compared:** autosave/optimistic success pada setiap isian; simpan eksplisit dengan state dirty/pending/confirmed/unknown.  
**Choice and why:** simpan eksplisit, sukses setelah konfirmasi server, stale version meminta tinjau data terbaru; timeout belum berarti batal. Tidak auto-retry mutasi atau menyediakan force overwrite.  
**Tradeoffs for a11y, usability, mobile, performance:** pengguna perlu tindakan simpan; feedback tetap inline dan diumumkan. Isian aman boleh bertahan hanya di memori halaman yang masih berhak.  
**Validation signal and revisit condition:** simulasi stale/timeout/expiry tidak membuat duplikasi, bocoran atau hilang konteks; perbaiki feedback berdasarkan bukti.  
**Affected specs/components:** SCR-04/05/06/08/10, form state, error summary, [sesi](SESSION_TOKEN_SECURITY_SPEC.md).

## DD-006 — Token terang terbatas dan tipografi sistem

**Status:** Proposed, bukan brand resmi sekolah.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** teks/nilai mudah dipindai tanpa menunggu asset/font eksternal; belum ada asset merek yang disetujui.  
**Options compared:** tema dashboard siap pakai dengan banyak aksen/mode; token terang hangat, hijau gelap, teks slate dan status semantik.  
**Choice and why:** satu tema terang, system font, angka tabular; warna/border/focus terpusat. Pakai nama teks sekolah sampai logo berizin tersedia.  
**Tradeoffs for a11y, usability, mobile, performance:** rasio hex tertentu sudah dihitung secara statis; opacity, state, font actual dan tampilan UI belum diuji. Tidak mengklaim AA dari palette.  
**Validation signal and revisit condition:** uji kontras aktual, panjang label dan keterbacaan layar; ubah token jika pasangan/state gagal atau pemilik memilih identitas lain.  
**Affected specs/components:** token/typography pada DESIGN_SYSTEM, seluruh keluarga layar.

## DD-007 — Native semantics, fokus jelas dan target sentuh yang nyaman

**Status:** Proposed.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** core flow dapat dipakai keyboard/assistive, tidak bergantung warna/hover/drag; referensi W3C pada DESIGN_SYSTEM.  
**Options compared:** widget spreadsheet/custom navigasi untuk seluruh kontrol; elemen native dan komponen kit yang perilakunya diuji.  
**Choice and why:** target WCAG 2.2 AA, sasaran produk44px untuk kontrol, reflow320 CSS px, error summary dan fokus dialog kembali ke pemicu. Tidak menambah dependency aksesibilitas tanpa kebutuhan.  
**Tradeoffs for a11y, usability, mobile, performance:** ruang kontrol bertambah; sticky bar dan tabel dua dimensi harus diuji terpisah. Target produk44px bukan klaim minimum AA.  
**Validation signal and revisit condition:** DV/SX positif-negatif dengan zoom, keyboard dan pembaca layar; kegagalan core flow menghalangi penerimaan terkait.  
**Affected specs/components:** navigation, field, dialog, table/list dan DV-01..03 pada DESIGN_SYSTEM.

## DD-008 — Review dampak dan izin tindakan tanpa kontrol palsu

**Status:** Proposed untuk UI; pembatasan grant merujuk keputusan produk.  
**Date / owner:** 28 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** principal hanya approve/reject dari jabatan itu, W publish binaan, D membantu draft tanpa D/P; batch tidak menyembunyikan objek yang dipilih.  
**Options compared:** semua tombol tampil dengan asumsi backend menolak; kontrol berizin dengan disabled state hanya untuk prasyarat yang belum terpenuhi.  
**Choice and why:** tampilkan aksi sesuai grant dan state; review konteks/jumlah berizin sebelum publish/approve/apply. Seleksi awal kosong dan dibatasi halaman terlihat; perubahan filter membersihkannya secara eksplisit. Approval tidak otomatis apply.  
**Tradeoffs for a11y, usability, mobile, performance:** alasan state disabled harus terlihat tanpa hover; menyembunyikan aksi bukan pengganti policy server. Detail Q6 yang belum final tidak diaktifkan sebagai tombol kerja; koreksi/revisi/pencabutan rapor mengikuti D-28 yang telah dipilih, dengan implementasi dan pengujian tetap NOT RUN.  
**Validation signal and revisit condition:** uji role rangkap, revoke, source changed dan batch satu item invalid; tidak ada tombol mati, sukses parsial tersembunyi atau akses lintas anak.  
**Affected specs/components:** SCR-03/04/08/10/11, dialog/checkbox, [otorisasi](AUTHORIZATION_REQUIREMENTS.md), [akses](RESOURCE_ACCESS_POLICY.md).

## DD-009: Facelift-v2 sebagai referensi dan skill sebagai alat kerja

**Status:** Proposed untuk desain implementasi; penggunaan prototipe sebagai referensi ditetapkan pemilik.  
**Date / owner:** 29 Oktober 2026; pemilik proyek.  
**User outcome and relevant evidence:** [facelift-v2](support/facelift-v2/README.md) memuat source, empat layar interaktif dan tujuh kajian visual. Source serta tangkapan nilai desktop/ponsel dibaca pada pembaruan dokumentasi ini; pengujian prototipe tidak diulang.  
**Options compared:** menyalin prototipe sebagai spesifikasi wajib; memakai pola yang sesuai sebagai referensi berdasarkan kebutuhan.  
**Choice and why:** gunakan referensi saja; PRD, matriks akses, API dan aturan akademik tetap menentukan perilaku. Warna, ukuran, teks, state demo dan scaffold tidak otomatis diadopsi.  
**Tradeoffs for a11y, usability, mobile, performance:** komponen/pola yang cocok dapat digunakan kembali, tetapi izin server, race, keyboard, kontras aktual dan responsif tetap diuji pada build aplikasi terkait.  
**Validation signal and revisit condition:** antislop During memeriksa kualitas selama implementasi, taste-skill memakai alur app screens dan tinjauan screenshot, Ponytail full menghindari tambahan yang tidak diperlukan. Bukti baru dicatat sesuai revisi, tanpa menganggap hasil referensi sebagai hasil backend.  
**Affected specs/components:** [DESIGN_SYSTEM](DESIGN_SYSTEM.md), [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md), [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md) dan [ARCHITECTURE](ARCHITECTURE.md). Tidak mengubah file prototipe atau mengesahkan Q6/visual Q7/lifecycle.
