# Design Direction Selection — SIA MI ALGHIVARI

**Status:** PROPOSED / blueprint · **Owner:** pemilik proyek · **Review date:** 28 Oktober 2026, telaah rancangan; belum persetujuan visual atau review UI berjalan.

Pembaruan 29 Oktober 2026: implementasi aplikasi inti **NOT STARTED**; [prototipe facelift-v2](support/facelift-v2/README.md) dan bukti tinjauannya sudah tersedia. Pemilik menetapkannya sebagai **referensi, bukan panduan**: perbandingan A/B tetap bahan pertimbangan, tidak mewajibkan menyalin prototipe atau menganggap hasilnya riset pengguna. Acuan perilaku tetap [brief](DESIGN_BRIEF.md), [alur](USER_FLOWS.md) dan [PRD](PRD.md).

## Invariants

Primary user job: catat nilai/kehadiran satu aktivitas dalam kelas/periode yang benar; siapkan konfigurasi pusat; baca perkembangan; terbitkan/terima rapor; putuskan naik/lulus dengan langkah apply terpisah.

Approved brand/product constraints: role/penugasan mengikuti matriks; perkembangan berbeda dari rapor; blank bukan0, status belum dicatat bukan alpa; KKM/bobot global. Nama sekolah diketahui, tetapi palette/logo/font dan komposisi visual belum disetujui. Batas hak tidak berubah karena memilih konsep.

Target platforms and content density: browser online desktop/ponsel, tablet sebagai lebar perantara. Guru/admin membutuhkan pemindaian daftar dan input berulang; wali membaca aktivitas satu anak. Belum ada bukti perangkat/frekuensi dominan; keduanya harus diujikan dengan data sintetis yang panjang/kosong/gagal.

## Concept A — Register Akademik

- **Experience thesis and task rationale:** susunan seperti register kerja sekolah: konteks tetap, daftar mudah dibandingkan, detail terbuka ketika diperlukan. Satu halaman input untuk satu assessment/pertemuan, tanpa alur wizard rutin.
- **Layout grammar and content priority:** sidebar tugas pada desktop → header konteks tahun/semester/kelas atau anak → judul/status → filter lokal → daftar/form utama → tindakan jelas. Beranda menautkan pekerjaan nyata yang berhak; bukan deretan KPI dekoratif. Portal wali memisahkan Perkembangan Belajar dan Rapor Online.
- **Type scale / contrast / spacing / density:** font sistem, judul moderat 24–28px, isi16px, tabel14–16px dengan tinggi kontrol44px. Angka tabular dan rata kanan. Latar #F7F8F5, permukaan putih, teks #17231D, aksi #176B4B; pasangan semantik/kontras pada DESIGN_SYSTEM. Warna ini kandidat, bukan merek sekolah.
- **Interaction patterns and responsive adaptation:** desktop memakai tabel HTML dengan input/select/radio native; ponsel memakai daftar siswa berlabel dan drawer navigasi. Simpan eksplisit, badge status dan ringkasan perubahan yang berasal dari data sebenarnya. Review/konfirmasi untuk publish/approve/apply, tidak untuk setiap simpan rutin.
- **Distinctive element with a functional reason:** baris konteks akademik dan ringkasan kelengkapan berteks dekat tindakan, agar kelas/periode, status simpan dan hambatan finalisasi mudah terlihat. Tidak menambah fitur notifikasi atau data baru demi penampilan.
- **Accessibility, engineering, and cost implications:** tabel/form/daftar serta dialog yang sama dipakai ulang. Fokus/label/overflow masih perlu diuji; tabel berinput panjang dapat membuat Tab lama sehingga batasi satu aktivitas/roster dan sediakan lompat ke bagian penting. Tidak membuat widget spreadsheet khusus.
- **Risks and reference sources (no cloning):** kepadatan bisa terlalu tinggi di ponsel; nama panjang dan kolom angka berpotensi bertabrakan. Dasar pola [WAI tabel](https://www.w3.org/WAI/ARIA/apg/patterns/table/), bukan tiruan merek produk lain; kesesuaian visual belum diuji.

## Concept B — Panduan Tugas

- **Experience thesis and task rationale:** pengguna memulai dari antrean tugas besar dan dipandu satu tahap per halaman, misalnya pilih kelas → isi → periksa → bagikan. Cocok bila penelitian kelak menunjukkan konteks/filter sulit dipahami pengguna awal.
- **Layout grammar and content priority:** navigasi atas ringkas, satu kolom utama dengan panel tugas/stepper, judul32px dan ruang vertikal lebih besar. Daftar siswa diproses sebagai kelompok/halaman bertahap; ringkasan lintas baris baru muncul pada tahap review.
- **Type scale / contrast / spacing / density:** isi16–18px, kontrol44px, jarak antarbagian32px, latar putih dengan aksen biru gelap netral; bukan sekadar mengganti warna A. Hierarki menonjolkan langkah saat ini, mengurangi informasi serentak dan kepadatan tabel.
- **Interaction patterns and responsive adaptation:** tombol Kembali/Lanjut dan indikator tahap eksplisit; ponsel mengikuti satu kolom yang sama. Perlu menjaga state antar-langkah, back-navigation dan invalidasi konteks saat sumber/izin berubah. Tidak menyimpan data sensitif di browser demi wizard.
- **Distinctive element with a functional reason:** panduan tahap yang menjelaskan apa yang disimpan dan apa yang dibagikan, sebelum tindakan final. Potensial membantu tugas jarang seperti konfigurasi awal/persetujuan.
- **Accessibility, engineering, and cost implications:** perlu announcer perubahan tahap, pemulihan fokus, state lintas halaman dan resume yang aman. Menambah jumlah perpindahan pada input rutin; tidak ada pengukuran waktu/biaya yang membuktikannya lebih cepat/lambat.
- **Risks and reference sources (no cloning):** perbandingan banyak siswa dan koreksi mundur lebih tersembunyi. Risiko duplikasi form/review jika semua flow diwajibkan wizard; rujuk kebutuhan J-04/J-05 serta prinsip label/status [WCAG 2.2](https://www.w3.org/TR/WCAG22/), bukan klaim pola wizard diwajibkan standar.

## Optional Concept C — N/A

Konsep ketiga belum diperlukan: dua pendekatan sudah berbeda dalam hirarki, kepadatan, navigasi dan pengelolaan state. Jangan membuat alternatif kosmetik hanya untuk mengisi template. Konsep tambahan baru berguna jika A/B gagal memenuhi tugas yang diuji.

## Comparison and decision

| Criterion | A | B | C (optional) | Evidence, not invented research |
| --- | --- | --- | --- | --- |
| Primary task clarity | Kelas/periode dan roster tetap terlihat; mendukung koreksi lintas siswa | Tahap saat ini kuat, tetapi konteks/perbandingan lintas baris lebih terpisah | N/A | Kebutuhan input satu kelas pada PRD/USER_FLOWS; belum ada usability test |
| Visual identity fit | Teratur dan tenang melalui tipografi, baris data dan warna terbatas | Lebih mengarahkan melalui panel tugas dan judul besar | N/A | Penilaian penyusun dari brief; tidak ada brand guide/approval |
| Accessibility risk | Tabel panjang dan kontrol rapat perlu validasi; native semantics membantu | Perubahan tahap/fokus dan state tersembunyi perlu validasi | N/A | Pola WAI/WCAG menjadi rujukan, bukan bukti dua konsep lulus |
| Mobile/content robustness | Perlu adaptasi daftar siswa serta navigasi drawer | Satu kolom mudah dipetakan, lebih banyak langkah pada roster panjang | N/A | Hipotesis layout; nama panjang, zoom dan ponsel belum diuji |
| Implementation cost | Memakai ulang daftar/form standar; kandidat biaya implementasi lebih rendah | State/routing tahap dan pemulihan konteks lebih banyak | N/A | Perbandingan tanggung jawab teknis, tanpa estimasi jam/anggaran |

**Selected:** A direkomendasikan penyusun sebagai dasar rancangan lanjutan, **belum dipilih/disetujui pemilik**.

**Rationale and decision maker:** A paling dekat dengan kebutuhan pencatatan/pemindaian satu kelas dan batas sederhana proyek. Pemilik tetap pengambil keputusan. Prinsip review dampak B dapat dipakai untuk aksi berisiko tanpa mengubah semua form menjadi wizard.

**Elements explicitly rejected:** untuk usulan saat ini, wizard wajib setiap input, dashboard metrik dummy, dark mode tambahan, grafik dekoratif dan animasi yang tidak mendukung tugas. Ini batas desain sementara, bukan larangan mengkaji ulang jika ada kebutuhan.

**Signal that would change the decision:** pengguna tidak mampu mengenali konteks/status, input roster sulit dilakukan di ponsel/keyboard, atau prototype B menunjukkan manfaat nyata. Uji dahulu, jangan mengarang skor perbandingan.

**Approved date/status:** belum ada persetujuan seluruh desain implementasi; PROPOSED. Facelift memakai pengembangan arah A untuk tinjauan, tetapi bukan kewajiban gaya atau pengesahan pola lainnya. Detail terkini referensi pada [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md); token awal dokumen ini tetap kandidat historis. Antislop dan taste-skill membantu menilai tujuan/hierarki berdasarkan tugas, bukan memilihkan ulang warna/layout tanpa kebutuhan; penggunaannya mengikuti [DESIGN_SYSTEM](DESIGN_SYSTEM.md). Tidak membuat mockup/screenshot baru atau menjalankan uji UI pada pembaruan dokumen ini.
