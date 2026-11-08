# Project Brief - SIA MI Alghivari

Diperbarui: 29 Oktober 2026. Pemilik produk: pemilik proyek. Status: blueprint sedang dimantapkan; Q1-Q5 untuk aturan inti dikonfirmasi, detail Q6 dan detail visual Q7 belum diverifikasi; presisi konfigurasi Q7.5 diterima; pembanding KKM Q7.4 diterima; Q7.3 pembulatan akhir diterima; skor input Q7.2 diterima; susunan rapor ringkas Q7.1 diterima 29 Oktober 2026. Pemilik meminta tidak memulai implementasi.

## Produk dan tujuan

Sistem akademik satu madrasah yang dibangun ulang dari laporan KP untuk portofolio: pengelolaan data akademik, kegiatan guru, pemantauan wali, rapor unduhan, dan persetujuan kenaikan/kelulusan.

Masalah historis: pencatatan buku lalu Excel, pekerjaan berulang, serta keterlambatan informasi hasil belajar kepada keluarga. Tidak ada pengukuran terkini atau bukti adopsi yang diklaim.

## Lingkup yang diminta pemilik

- Tiga tingkat admin: Master, Akademik, Data Entry; batas berbeda dan dapat dirangkap dengan penugasan guru.
- Guru mapel, wali kelas, kepala sekolah, dan orang tua/wali; satu pengguna dapat memegang beberapa peran.
- Tahun ajaran, semester, kelas, mapel, jadwal; KKM dan bobot global untuk katalog komponen tugas, ujian harian, UTS, UAS dan kehadiran otomatis, diatur Master/Akademik. Total bobot 100%, tanpa override per mapel.
- Absensi per mapel; nilai numerik dirata-rata lalu dikali bobot, blank dilewati dan 0 dihitung. Komponen berbobot positif yang seluruh nilainya blank mencegah finalisasi.
- Nilai dan absensi, rekap menjadi rapor, unduh rapor oleh wali, serta naik kelas/lulus dengan persetujuan kepala sekolah.
- Pemantauan perkembangan nilai/kehadiran anak tersedia sepanjang periode tanpa menunggu rapor. Rapor Online adalah alur terpisah, hanya tersedia setelah seluruh rekap difinalisasi dan dipublikasikan.
- Setiap CUD mempunyai audit sistem yang tidak dapat diubah melalui aplikasi, termasuk oleh Master.
- Notifikasi perubahan menurut penerima yang berhak, dengan pengelompokan aktivitas massal.
- Semua pengguna dapat mengubah profil sendiri pada field yang diizinkan.
- Susunan rapor ringkas Q7.1: hasil/KKM/status per mapel, kehadiran per mapel dalam pertemuan, catatan wali kelas opsional pada draft binaan dan pengesahan; rincian komponen di aplikasi dari snapshot yang sama. Batas skor input Q7.2: 0-100 inklusif, maksimal dua desimal; pembulatan akhir Q7.3 dua desimal half-up diterima; pembanding KKM Q7.4 memakai hasil akhir dibulatkan >= KKM; presisi konfigurasi Q7.5 juga diterima; detail visual belum final.

## Batas kesederhanaan

Satu aplikasi, satu database, role tetap, penugasan eksplisit, inbox persisten serta Reverb/Echo untuk notifikasi realtime dengan database queue. Tidak ada permission builder, banyak sekolah, notifikasi CUD umum melalui WhatsApp/email/push, chat, pembayaran, aplikasi mobile terpisah atau AI. Email akses/password sementara dan OTP reset/profil termasuk core D-25; provider/parameter/integrasinya belum final. Reverb dibatasi pada notifikasi; tidak menambah fitur sekunder karena WebSocket tersedia.

Pemilik mengonfirmasi satu aplikasi Laravel + Inertia + React/TypeScript pada 29 Oktober 2026, sesuai tujuan portofolio React/TypeScript. [TECH_STACK.md](TECH_STACK.md) memuat detailnya; engine MySQL juga dipilih pemilik 29 Oktober 2026; baseline versi inti juga disepakati pada tanggal tersebut (D-15); konfigurasi, paket tambahan dan versi persis belum dikunci. Rekomendasi Livewire pada kajian awal menjadi alternatif historis. Tidak ada provisioning atau deployment yang diotorisasi oleh dokumen ini.

Alur demo disepakati sebagai blueprint: dev -> PR/main -> GitHub Actions CI/build -> GHCR -> self-hosted deployment runner -> Docker Compose, dengan Cloudflare Tunnel untuk akses publik. Dev/demo memakai data, volume dan environment terpisah. Rincian: [arsitektur deployment](DEPLOYMENT_ARCHITECTURE.md) dan [pipeline](CICD_PIPELINE_SPEC.md). Ini belum mengotorisasi instalasi, penyediaan secret, perubahan DNS atau deployment nyata.

## Hasil penerimaan

Demo sintetis dapat menjalankan alur persiapan akademik -> pencatatan guru -> publikasi rapor -> unduh wali -> keputusan kenaikan/kelulusan, dengan pembatasan data, audit dan notifikasi yang dapat diuji. Efisiensi, performa, dan kepuasan belum diukur.

Rincian: [PRD](PRD.md), [alur akademik](ACADEMIC_FLOW.md), [matriks izin](ROLE_PERMISSION_MATRIX.md), [model data](DATA_MODEL.md), [notifikasi](NOTIFICATION_REQUIREMENTS.md).

Keputusan password D-25 (29 Oktober 2026): Master membuat akun/email langsung verified administratif; sistem mengirim URL biasa/username/password sementara, login awal wajib mengganti password sebelum aplikasi. Fouladgar untuk OTP email reset/profil; password berhasil disimpan lalu modal pilihan sesi wajib, reset berakhir login biasa dan profil current device tetap login. D23/D24 digantikan. Email auth terpisah dari inbox/Reverb; provider/parameter/integrasi belum final dan seluruh runtime NOT RUN. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

D-29 dikonfirmasi 30 Oktober 2026: satu konfigurasi timezone sekolah untuk tampilan dan kalender akademik, Master boleh mengubah dinamis dengan pilihan WIB/WITA/WIT. Backend/database UTC; refresh tanpa restart, histori final tetap. Nilai awal belum dipilih; cap delegasi turunan DATE ketika zona berubah masih keputusan terbuka. [Rincian PRD](PRD.md).

D-32: pemilik melewatkan target formal RPO/RTO untuk demo portofolio pada 30 Oktober 2026. Tidak menjanjikan waktu pemulihan atau batas kehilangan data; backup manual/jadwal-retensi yang sudah dipilih dan uji restore fungsional tetap berlaku. Ini pembatasan scope, bukan bukti kesiapan rilis.

D-33 memilih host demo PC pemilik (generasi13/RAM24GB/SSD kosong100GB), domain calon .dpdns.org/Cloudflare DNS + satu Tunnel dua hostname aplikasi-Reverb, HTTPS/WSS dan origin Compose privat. Nama lengkap belum dimiliki, setup/uji publik belum dilakukan; tanpa janji24/7. [Rincian](DEPLOYMENT_ARCHITECTURE.md).
