# Rencana deployment demo - SIA MI Alghivari

Diperbarui: 28 Oktober 2026. Status: blueprint continuous deployment; semua langkah **NOT RUN**. Operator/pemilik rilis: pemilik proyek. Belum ada workflow, runner, container atau domain yang diaktifkan.

## Aktivasi pertama

Setelah implementasi diminta dan tersedia, konfigurasi proteksi main, CI, GHCR, runner terbatas, lingkungan/volume demo terpisah, secret dan tunnel. Pastikan build/runtime cocok dengan perangkat, backup/restore dapat dibuktikan, domain/TLS bekerja dan seluruh data sintetis. Uji rilis gagal dan pemulihan sebelum mengaktifkan CD publik. Semua prasyarat belum dikerjakan.

## Rilis otomatis dari main

1. CI/main lolos gate wajib; build image, verifikasi, lalu publish GHCR. Teruskan digest/commit dari job tersebut, bukan menebak versi dari tag terbaru.
2. Ambil kunci satu deployment per demo. Tolak revisi usang; periksa kapasitas, secret, storage, registry, database dan backup yang dapat digunakan.
3. Evaluasi migrasi. Migrasi harus diuji pada database sementara dengan engine sama. Utamakan penambahan kompatibel agar kode lama/baru dapat hidup selama pergantian. Perubahan destruktif tidak masuk CD biasa; perlu rencana pemulihan dan persetujuan spesifik.
4. Tarik image berdigest dengan konfigurasi deployment yang cocok. Untuk perubahan skema/kontrak job yang tidak kompatibel, drain worker dan hentikan penulisan sementara dalam jendela pemeliharaan. Migrasi menggunakan akun khusus, bukan runtime aplikasi.
5. Perbarui app, queue dan reverb ke digest sama secara terkendali; beri waktu shutdown dan penyelesaian job. Jaga mysql/volume persisten. Jangan reset, seed ulang atau hapus volume pada rilis rutin.
6. Verifikasi HTTP, database, job antrean, Reverb, private channel, reconnect/inbox dan domain HTTPS/WSS. Uji login/akses guru-wali dengan data sintetis, termasuk file rapor bila tersedia. Gate wajib tidak tersedia/gagal berarti rilis gagal.
7. Rekam commit/digest aktif, migrasi dan bukti smoke. Baru nyatakan berhasil setelah target terbukti berfungsi. Compose satu host tidak menjamin zero downtime.

Pull image dan pembaruan container adalah langkah terpisah; skrip final belum dibuat. [Docker pull](https://docs.docker.com/reference/cli/docker/compose/pull/), [Docker up](https://docs.docker.com/reference/cli/docker/compose/up/).

## Kegagalan dan pemulihan

| Keadaan | Tindakan yang direncanakan |
| --- | --- |
| CI/build/scan/push gagal | Tidak deploy; versi aktif tetap. |
| Host/runner offline atau pull gagal | Deployment belum berhasil; jangan hentikan versi aktif sebelum artefak tersedia. |
| Migrasi gagal | Hentikan rollout dan periksa skema/data; jangan menganggap semua DDL otomatis rollback. |
| App/queue/Reverb atau smoke gagal | Hentikan promosi, periksa log aman; kembali ke digest lama hanya bila skema dan format job kompatibel. |
| Pemulihan memerlukan pengembalian data | Restore/roll-forward melalui operator dan persetujuan terkait; perhitungkan data setelah backup. Bukan rollback database otomatis. |

Reverb dapat terputus saat diganti; klien reconnect/catch-up dari inbox persisten. Job lama/baru perlu kompatibel atau diselesaikan sebelum pergantian. Hentikan rilis bila scope bocor, audit gagal, login/file rapor rusak, job tidak diproses atau WSS gagal; halaman awal terbuka saja tidak cukup.

Ambang timeout/retry, masa pemeliharaan, retensi backup, tujuan pelaporan kegagalan dan target pemulihan ditentukan saat implementasi. Jangan menganggap kanal email/WhatsApp telah diotorisasi. Produksi sekolah mengikuti gate dan persetujuan rilis tersendiri.

Acuan: [pipeline](CICD_PIPELINE_SPEC.md), [arsitektur](DEPLOYMENT_ARCHITECTURE.md), [environment](ENVIRONMENT_SPEC.md).

Rincian urutan 7, 29 Oktober 2026: [RELEASE_PLAN](RELEASE_PLAN.md) menetapkan checklist RP sebelum/sesudah perubahan, [RELEASE_RUNBOOK](RELEASE_RUNBOOK.md) merinci rehearsal disposable serta rollout demo, dan [RUNBOOK](RUNBOOK.md) memandu diagnosis/pemulihan sesuai dampak. Backup DB+file, quiesce seluruh writer, rekonsiliasi izin/sesi/job dan cutover mengikuti [DATA_RECOVERY_PLAN](DATA_RECOVERY_PLAN.md). Tidak menambah staging permanen atau mengubah status eksekusi/otorisasi; seluruhnya tetap blueprint NOT RUN.

D25 menambah email akses/password dan OTP. Sebelum aktivasi: pilih SMTP/API provider/sender domain, secret runtime/queue encrypted, pastikan otp_log/debug mail tidak aktif publik dan vendor migration/binding hash sesuai schema. Smoke terotorisasi memeriksa forced gate/OTP/modal/Yes-No/login biasa melalui fake/penampung dahulu; provider nyata tidak diaktifkan oleh pembaruan dokumen ini. Sesudah rollback/restore karantina auth jobs lama. [Environment](ENVIRONMENT_SPEC.md).

D-30: [recovery](DATA_RECOVERY_PLAN.md) menetapkan arsip terenkripsi lokal lalu upload manual pemilik ke Google Drive. Preflight target berdata membedakan set lokal, upload_pending dan salinan offhost terverifikasi; jangan menganggap capture lokal sudah melindungi kehilangan komputer. Jika perubahan berisiko memerlukan set offhost baru, tunda perubahan sampai upload/verifikasi operator selesai; jangan menampilkan CD/backup sukses palsu. Setelah capture immutable, aplikasi tidak perlu ditahan sepanjang upload manual. Ruang SSD100GB bebas saat dilaporkan diperiksa ulang untuk build/capture/download/restore; tidak purge volume/cadangan otomatis. D-31 menetapkan jadwal/retensi3lokal/7harian+4mingguanDrive; target formal RPO/RTO dikeluarkan D-32, backup/restore fungsional tetap belum diuji dan tidak ada waktu pemulihan yang dijanjikan. Semua operasi NOT RUN.

D-31: ikuti [jadwal/retensi kanonis](DATA_RECOVERY_PLAN.md) setelah demo/hari aktif dan sebelum perubahan berisiko. Pertahankan set mingguan saat label harian bergeser; verifikasi pengganti/remote serta set recoverable sebelum rotasi. Tidak melakukan pembersihan otomatis atau menurunkan retensi ketika ruang penuh. Bukti backup/rotasi/restore masih NOT RUN.

## D-33 — aktivasi host dan jalur publik (rencana)

Host PC pemilik/Compose dan domain calon .dpdns.org/Cloudflare DNS + satu Tunnel dua hostname disepakati, sesuai [arsitektur](DEPLOYMENT_ARCHITECTURE.md). Ini pilihan rancangan, bukan otorisasi aktivasi sekarang. Pada tahap operasional terotorisasi: registrasi nama sebenarnya, tambah zone yang diterima Cloudflare, delegasikan NS pada pengelola domain, buat route app/ws, tunggu DNS/cert siap; jangan lanjut jika nama/zone/cert gagal.

Kunci nama publik sebelum CI build aset Echo; app/queue/Reverb harus memakai image/digest yang sama dari main tepercaya. Uji origin internal kemudian HTTPS/WSS publik, proxy/redirect/cookie/login, private file dan scope channel/reconnect. Domain ditandai verified hanya dari bukti aktual kedua host, bukan berdasarkan suffix/certificate coverage teoritis. Mode sleep off selama sesi demo; PC/Internet offline tetap berarti demo tidak tersedia. Tanpa 24/7 atau resource limit yang otomatis disetujui. Semua langkah NOT RUN.
