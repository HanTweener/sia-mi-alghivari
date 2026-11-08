# Roadmap blueprint dan implementasi

Tanggal: 28 Oktober 2026. Status saat ini: **M0 - memantapkan blueprint; jangan mulai implementasi**, sesuai instruksi pemilik.

| Tahap | Hasil yang dituju | Ketergantungan dan bukti yang kelak diperlukan |
| --- | --- | --- |
| M0 - Blueprint | Kebutuhan, peran rangkap, scope izin, skema, alur rapor/naik-lulus, audit, notifikasi dan UX disepakati | Q1-Q5 aturan inti sudah dikonfirmasi, termasuk absensi per mapel, KKM/bobot global dan blank/nol. Susunan rapor Q7.1 dan batas skor input Q7.2 sudah dikonfirmasi; pembulatan akhir Q7.3 juga diterima; pembanding KKM Q7.4 juga diterima; presisi konfigurasi Q7.5 juga diterima; selesaikan batas biodata staf, mekanisme input penugasan Data Entry serta detail stack yang masih terbuka. Belum ada persetujuan menyeluruh blueprint. |
| M1 - Fondasi | Compose dev, CI dasar, akun, profil, role/penugasan, master data, audit atomik, inbox persisten dan Reverb/Echo | Hanya setelah instruksi pemilik. Kunci runtime/lockfile, startup/healthcheck, isolasi dev/demo dan pemeriksaan PR/main. Tes izin/role/profil/audit, private channel, rollback tanpa broadcast, worker/retry dan reconnect. |
| M2 - Operasional akademik | Jadwal, katalog scoring_components, KKM/bobot global, sesi per mapel, nilai dan pemantauan wali selama periode berjalan | Tes blank/nol, seluruh komponen kosong, bobot 0/total 100, nol pertemuan, input kelompok, delegasi, benturan dan konflik edit. Perkembangan tetap bisa dibuka tanpa rapor, draft internal tidak bocor. |
| M3 - Rapor | Rekap, kelengkapan, snapshot/revisi, publikasi wali kelas/admin dan unduh wali | Tes hitungan serta izin kelas/anak/file; perubahan konfigurasi tidak mengubah rapor final lama. |
| M4 - Naik kelas/lulus | Usulan, keputusan principal, penerapan individual/batch | Tes version stale, klik ganda, transisi tahun, rollback batch dan kelestarian histori. |
| M5 - Portofolio | Demo sintetis, GHCR, runner CD terbatas, Compose demo/Tunnel, dokumentasi keputusan, screenshot dan bukti pengujian | Alur otomatis main ke demo disepakati sebagai blueprint; implementasi/aktivasi menunggu instruksi. Verifikasi digest, isolasi dev/demo, secret, DNS/TLS/WSS, migrasi/backup/restore dan rilis gagal. Bukan bukti pemakaian sekolah resmi. Lihat [pipeline](CICD_PIPELINE_SPEC.md). |

Seluruh tahap implementasi berstatus **NOT STARTED**. Semua tes aplikasi **NOT RUN**. Tidak ada tanggal selesai atau hasil performa yang diinventarisasi.

Fitur sekunder seperti CSV, pengumuman, AI, pesan eksternal dan banyak sekolah ditunda. Pertahankan role tetap, satu database dan workflow khusus kebutuhan sekolah, tanpa platform izin/workflow generik.
