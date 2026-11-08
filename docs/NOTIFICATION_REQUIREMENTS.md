# Kebutuhan notifikasi

Tanggal: 28 Oktober 2026. Status: blueprint, belum diimplementasikan. Sumber: kebutuhan pemilik dan [PRD](PRD.md).

## Lingkup minimum

Notifikasi dalam aplikasi: lonceng dengan jumlah belum dibaca, daftar berpaginasi, penanda dibaca dan tautan berizin. Pemilik memilih Laravel Reverb + Laravel Echo untuk pembaruan WebSocket. D-25 memilih email khusus akses awal/OTP/password melalui Laravel Mail/queue; bukan seluruh CUD via email. Browser push/SMS/WhatsApp tetap di luar core; provider mail belum dipilih. WebSocket tidak berarti notifikasi dapat diterima saat browser ditutup.

Setiap CREATE/UPDATE/DELETE domain yang berhasil mengevaluasi siapa yang berhak diberi tahu. Bukan berarti semua role menerima semua peristiwa. Draft dan data internal tidak dikirim ke wali. Aktivitas massal dikelompokkan per operasi, penerima, jenis dan scope yang aman.

Semua penerima ditentukan server, bukan dari daftar recipient_id bebas di request. Keputusan tidak mengirim kepada pengguna yang tidak berhak merupakan hasil evaluasi sah, bukan notifikasi hilang.

## Penyimpanan dan konsistensi

Satu tabel `notifications`, referensi audit sumber, recipient, scope, kategori, ringkasan minimal, batch dan read_at. Perubahan domain, audit dan inbox disimpan dalam transaksi database yang sama. Setelah commit, database queue menjadwalkan worker untuk broadcast lewat Reverb; Echo pada React menerima sinyal dan mengambil ulang data dari server. Tabel jobs/failed_jobs adalah infrastruktur framework; tidak perlu broker eksternal atau Redis untuk satu instance Reverb.

Rollback tidak memancarkan event. Bila dispatch/broadcast gagal setelah commit, perubahan domain dan inbox tetap tersimpan; worker memakai retry terbatas jika job sudah terantre. Sinyal realtime dapat terlewat, termasuk celah kegagalan sebelum enqueue, sehingga reconnect/refresh/open inbox mengambil ulang data persisten. Tidak menjanjikan exactly-once atau penerimaan event oleh pengguna; fallback fetch/polling terbatas boleh digunakan saat socket terputus.

Dedup key unik mencakup operasi/batch, penerima, kategori, dan scope. Duplikasi request tidak boleh membuat data/notifikasi ganda. Satu batch mempunyai audit per objek dan satu notifikasi per penerima pada cakupan tersebut; jumlah yang ditampilkan hanya jumlah objek yang berhak dilihat penerima.

Pembuatan audit dan notifikasi turunan tidak membuat notifikasi/audit rekursif; referensi operasi menjadi jejak sebabnya. Mengubah read_at tetap diaudit sebagai aksi teknis, tetapi tidak memunculkan notifikasi baru. Penyimpanan sesi/cache bukan event domain.

## Penerima dan isi

Lihat [matriks notifikasi](NOTIFICATION_MATRIX.md). Akses saat event menentukan kandidat penerima; akses saat daftar, badge, baca detail dan membuka tujuan diperiksa lagi. Pencabutan role/relasi anak/penugasan membuat notifikasi yang sudah tidak berhak tidak ditampilkan dan tidak dihitung.

Private channel per pengguna memerlukan autentikasi dan otorisasi subscription di server. Worker memverifikasi akun aktif dan akses penerima saat broadcast, bukan hanya saat event dibuat. Kirim notification_id/jenis event saja, tanpa payload nilai/biodata; fetching server tetap memeriksa scope terkini. Batasi origin, gunakan WSS dan putuskan subscription saat logout.

Ringkasan tidak memuat skor rinci, alamat pribadi, password/hash, token, atau payload request. Gunakan kalimat seperti 'Nilai yang sudah dipublikasikan diperbarui' dengan tujuan aman. Batch yang mencakup kelas lain tidak boleh membocorkan nama atau jumlah dari scope lain.

Pengguna hanya mengubah status baca notifikasi miliknya; Master tidak memperoleh hak mengedit isi inbox pengguna lain. READ-only akademik tetap dapat menandai notifikasi miliknya dibaca, sama seperti pengecualian profil sendiri.

## UX dan penerimaan

Keadaan loading, kosong, gagal memuat, offline, tanpa akses dan tujuan sudah diarsipkan mempunyai pesan jelas. Badge bersumber dari server, bukan tambahan angka dari event lokal. Daftar dapat digunakan dengan keyboard; pembaruan tidak mengambil fokus tiba-tiba.

Reverb dan queue worker menjadi layanan Compose terpisah dengan image/digest aplikasi yang sama, restart policy dan shutdown terkendali. Browser demo memakai WSS melalui Cloudflare Tunnel; worker menghubungi Reverb internal. CD memperbarui keduanya bersama app tanpa menghapus jobs/failed_jobs atau inbox. Acuan: [TECH_STACK.md](TECH_STACK.md) dan [DEPLOYMENT_PLAN.md](DEPLOYMENT_PLAN.md). Koneksi terputus tidak ditampilkan sebagai inbox kosong; saat tersambung lagi lakukan catch-up dan dedup ID, tanpa toast berulang untuk item lama.

Rencana tes: CUD tunggal/bulk, rollback tanpa broadcast, dedup/retry, jumlah terfilter, wali tidak melihat draft, role dicabut, recipient_id dimanipulasi, private channel orang lain ditolak, mark-read orang lain ditolak, notifikasi delete tanpa sumber aktif, worker/Reverb mati, reconnect/catch-up, celah dispatch setelah commit, serta tidak ada lingkaran notifikasi-read-notifikasi.

Semua tes **NOT RUN**; belum ada aplikasi. Target latensi, batas batch/polling, dan retensi ditentukan saat spesifikasi operasional diperlukan; tidak ada SLO terukur yang diklaim.
