# Design Brief — SIA MI ALGHIVARI

**Owner:** pemilik proyek · **Status:** PROPOSED / blueprint · **Date:** 30 Oktober 2026 · **Approver:** belum ada persetujuan seluruh desain implementasi.

Implementasi aplikasi inti/server **NOT STARTED**. [Prototipe facelift-v2](support/facelift-v2/README.md) sudah tersedia sebagai referensi desain dari pemilik, **bukan panduan wajib**. Bukti tinjauan prototipe mempunyai lingkup/revisi sendiri; riset pengguna dan pengujian backend tetap NOT RUN.

## Product experience thesis

**One-sentence experience goal:** pengguna menyelesaikan pekerjaan akademik dalam konteks kelas/periode/anak yang jelas, memahami apa yang tersimpan atau dibagikan, serta dapat membedakan perkembangan berjalan dari rapor semester resmi.

**Primary user and context:** guru mengisi nilai/kehadiran satu kelas; admin menyiapkan konfigurasi dan penugasan; wali membaca perkembangan anak. Principal/wali kelas meninjau keputusan/rapor sesuai izin. Desktop dan ponsel online menjadi sasaran rancangan, belum bukti perangkat dominan di sekolah.

**Most important job-to-be-done:** catat satu penilaian/pertemuan tanpa salah siswa, blank/0 atau status publikasi; siapkan data pusat; kemudian pantau dan terbitkan hasil melalui jalur berizin.

**Success action and how observed:** kelak buktikan melalui hasil transaksi server dan perjalanan browser: draft tersimpan, aktivitas dibagikan/disahkan, rapor lengkap published dapat diunduh wali terkait, approval tercatat tanpa apply otomatis. Tidak mengarang waktu penghematan atau persentase kepuasan. Ukur keterpahaman konteks/status, keberhasilan tugas dan pemulihan kesalahan setelah implementasi/prototipe tersedia.

## Requirements and evidence

| Input | Known evidence (source/date) | Assumption to validate |
| --- | --- | --- |
| User needs | Arahan pemilik dicatat pada [PRD](PRD.md), Q1–Q5; 28 Oktober 2026. | Susunan navigasi dan kepadatan yang membantu pengguna nyata belum diteliti. |
| Primary workflow | [USER_FLOWS](USER_FLOWS.md), J-01..J-11 termasuk J-07B; [fitur dan AC](FEATURES/FEATURE_SPEC_TEMPLATE.md). | Form satu aktivitas dan review dampak dapat mengurangi salah konteks; belum diuji. |
| Brand constraints | Nama proyek/sekolah diketahui; belum ada palette, logo, foto atau font merek yang disetujui. | Warna hijau pada usulan bukan identitas resmi sekolah. |
| Current UI problems | Aplikasi inti dimulai dari nol; prototipe React di facelift-v2 dan hasil tinjauannya sudah ada. Laporan KP tetap sumber historis. | Perilaku fixture/hasil tinjauan bukan bukti izin, transaksi, rumus final atau kebutuhan pengguna sekolah sudah teruji. |
| Platform and devices | [Stack](TECH_STACK.md): tujuan React/TypeScript dan browser desktop/ponsel; online. | Browser minimum, perangkat assistive dan kondisi jaringan pengguna harus divalidasi. |
| Technical/content limitations | [API](API_CONTRACT.md), [akses](RESOURCE_ACCESS_POLICY.md), [sesi](SESSION_TOKEN_SECURITY_SPEC.md); Q7.1–5/D-18..22 serta lifecycle D-25..28 sudah dipilih. | Detail visual/render, durasi sesi, parameter/provider/integrasi password D-25 dan sisa Q6 masih terbuka; keputusan domain belum membuktikan implementasi. |

## Trust and identity

- **Teratur:** judul konteks, urutan kolom dan tindakan konsisten untuk mengurangi salah kelas/anak. **Tenang:** elemen status jelas tanpa mengalihkan perhatian dari input. **Terbuka tentang keadaan:** draft, belum lengkap, tersimpan dan dipublikasikan mempunyai arti berbeda; kegagalan tidak disebut sukses.
- Interaksi awal menjelaskan siapa yang masuk, konteks kerja yang tersedia dan periode/anak yang dipilih. Peran rangkap mengubah navigasi berdasarkan tugas, tanpa menambah hak server.
- Rekomendasi visual adalah arah A, **Register Akademik**, pada [DESIGN_DIRECTION](DESIGN_DIRECTION.md): latar terang hangat, aksen hijau gelap, daftar/form berstruktur, angka mudah dibandingkan, status berteks dan halaman portal lebih lapang. Ini usulan, belum pilihan pemilik.
- Referensi pemilik terbaru: [facelift-v2](support/facelift-v2/README.md), terutama [SKILL_REBUILD](support/facelift-v2/SKILL_REBUILD.md), source dan tangkapan hasil yang disertakan. Pertimbangkan hierarki konteks, register, status simpan dan menu dialog ponsel; sesuaikan berdasarkan tugas/akses. Warna, ukuran, susunan dan detail interaksinya tidak wajib ditiru; aturan angka/rapor mengikuti Q7.1–5 yang diterima, bukan asumsi demo.
- Referensi pola yang diperiksa: [WAI tabel](https://www.w3.org/WAI/ARIA/apg/patterns/table/) untuk hubungan baris/kolom dan input standar; [WAI dialog](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/) untuk review dampak/fokus; [WCAG 2.2](https://www.w3.org/TR/WCAG22/) untuk sasaran aksesibilitas. Bukan menyalin tampilan produk lain.
- Beranda berisi pekerjaan/data yang benar-benar dapat di-query dan dilihat pengguna. Hindari angka dummy, grafik dekoratif, headline pemasaran, ilustrasi siswa buatan atau tumpukan kartu yang menggeser pekerjaan utama. Tidak menambahkan KPI baru hanya untuk mengisi beranda.
- Gunakan nama teks sekolah sebagai identitas awal. Asset logo/font dari laporan atau contoh rapor tidak dianggap berlisensi/disetujui. Usulan font sistem tanpa unduhan; asset resmi dan hak penggunaannya perlu konfirmasi sebelum dipakai. Tidak membuat logo/Figma atau gambar baru pada pekerjaan ini.

## Surface inventory

| Surface/flow | User priority | Data/content | Most likely failure | Device/input |
| --- | --- | --- | --- | --- |
| SCR-01 login/profil; SCR-02 beranda/konteks | P0 | Identitas sesi, konteks berhak, perubahan profil sendiri | Bocor credential, recovery palsu, konteks berubah dengan isian belum disimpan | Keyboard, password manager, ponsel |
| SCR-03 administrasi/akademik/penugasan | P0 | Periode, direktori, kelas, mapel, relasi/penugasan dan jadwal; konfigurasi sistem khusus Master | Memberi akses lewat biodata, jadwal bentrok atau field staf berlebih | Desktop form/daftar; ponsel satu kolom |
| SCR-04 konfigurasi penilaian | P0 | KKM global, katalog komponen, seluruh bobot dan revisi | Total bukan100, guru mengira ada override mapel | Tabel pendek/form, review sebelum aktivasi |
| SCR-05 kehadiran; SCR-06 nilai | P0 | Satu pertemuan/assessment, roster berizin dan status draft/publikasi | Blank menjadi0/alpa, salah siswa, stale version atau timeout dianggap batal | Desktop tabel input; ponsel daftar siswa |
| SCR-07 Perkembangan Belajar | P0 | Anak terkait, aktivitas published/finalized dan rekap sementara | Disamakan dengan rapor atau draft internal bocor | Ponsel prioritas baca, filter sederhana |
| SCR-08 rapor staf; SCR-09 Rapor Online wali | P0 | Kelengkapan, snapshot/revisi/file dan daftar semester published | Finalized dikira sudah diterima wali, file gagal atau kelas/anak salah | Review lintas mapel desktop; ringkasan/unduh ponsel |
| SCR-10 naik/lulus | P0 | Sumber/tujuan, usulan, pilihan batch, keputusan dan apply terpisah | Approve langsung memindahkan siswa, batch parsial atau pilihan tersembunyi | Daftar seleksi/review; ponsel detail bertahap |
| SCR-11 audit/inbox | P1, tetap bagian core | Event/field berizin dan inbox pengguna | Riwayat membuka scope lama, realtime gagal dianggap data hilang | Daftar, badge dan detail yang masih berhak |

ID layar didefinisikan lebih rinci pada [spesifikasi layar](SCREENS/SCREEN_SPEC_TEMPLATE.md); P1 berarti urutan desain, bukan fitur opsional yang dibuang. Audit/inbox menyertai mutasi sejak fondasi implementasi.

## Quality boundaries

Sasaran usulan adalah WCAG 2.2 AA; penerimaan menunggu pemeriksaan nyata. Keyboard, pembaca layar, label, error, reflow, zoom dan kontras mengikuti [DESIGN_SYSTEM](DESIGN_SYSTEM.md). Verifikasi mencakup 360×800, tablet 768×1024 dan desktop 1440×900, ditambah reflow pada lebar 320 CSS px/zoom yang relevan. Ini matriks rencana, bukan hasil uji.

Bahasa Indonesia, angka desimal lokal dan label pertemuan yang tepat; D-29 memakai satu school_timezone dengan pilihan WIB/WITA/WIT, label zona eksplisit; nilai awal setup belum dipilih. Form panjang tidak mengandalkan drag/hover atau spreadsheet keyboard engine. Tidak membuat autosave, offline queue, impor clipboard atau penyimpanan biodata/nilai di browser sebagai fitur baru. Motion singkat hanya umpan balik, dengan reduced-motion yang setara.

Sisa Q6, parameter/integrasi password D-25, pilihan visual, asset resmi, batas perangkat dan hipotesis kegunaan diselesaikan pada bagian yang bergantung padanya. Q7.1–5/D-18..22 sudah diterima; detail visual/render belum diverifikasi. Pemilik memutuskan desain implementasi; prototipe tidak mengesahkan seluruhnya. Bukti screenshot/tinjauan prototipe tersedia dalam paket referensi, bukan hasil riset pengguna, sertifikasi aksesibilitas atau tes server. Panduan penggunaan antislop/taste-skill ada pada [DESIGN_SYSTEM](DESIGN_SYSTEM.md) dan [CODE_QUALITY_PLAN](CODE_QUALITY_PLAN.md); tidak menjalankan redesign atau mengubah prototipe pada tugas dokumen ini.

D-26/AR-04 dan D-27/AR-05 diterima: handover bertanggal/jadwal/delegasi terdampak, daftar peserta efektif dan review roster berubah harus terlihat jelas; histori/hasil final tidak ditimpa. Rapor satu mapel walau pengajar berganti. Koreksi hasil sah serta revisi/revoke rapor mengikuti D-28 yang diterima; transfer antarkelas dalam semester sama tetap ditunda. Implementasi inti NOT STARTED dan seluruh pengujian NOT RUN. [Alur](ACADEMIC_FLOW.md).
