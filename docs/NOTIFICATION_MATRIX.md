# Matriks notifikasi domain

Tanggal: 28 Oktober 2026. Status: blueprint. Kanal semua baris: in_app. Berlaku untuk C/U/D yang berhasil; calon penerima selalu dipotong oleh policy objek, field, keadaan publikasi, akun aktif dan hubungan saat ini.

Pengiriman realtime menggunakan Reverb/Echo setelah commit melalui database queue/worker, private channel per pengguna dan payload minimal. Tabel inbox tetap persisten; reconnect mengambil ulang data berizin. Penambahan WebSocket tidak mengubah matriks penerima di bawah.

| Event | Kandidat penerima | Batas isi/publikasi |
| --- | --- | --- |
| Konfigurasi sistem | Master | Tidak ada rahasia pada ringkasan. Profil sekolah yang memang boleh tampil bukan grant membuka menu konfigurasi. |
| Tahun/semester/kelas/mapel/jadwal | Master, Akademik, Principal; Data Entry sesuai pekerjaan; guru/wali kelas terkait | Wali hanya perubahan jadwal/konteks anak yang sudah berlaku dan boleh dibaca. Tidak menerima perubahan konfigurasi internal. |
| KKM/komponen/bobot global | Master/Akademik meninjau draft; aktivasi global memberi tahu Principal dan seluruh guru/wali kelas terkait | Data Entry hanya konteks pekerjaan; wali melalui publikasi/revisi hasil anak. Draft kebijakan tidak dibroadcast seolah sudah berlaku dan tidak ada bobot override mapel. |
| Penugasan guru/wali kelas dan delegasi Data Entry | Master, Akademik, Principal dan pengguna yang ditugaskan | Request penugasan belum aktif hanya pembuat dan peninjau; tidak mengirim kesan penugasan sudah disetujui. |
| Akun/role/jabatan | Master dan pengguna sasaran sesuai isi yang boleh diketahuinya | Tidak membagikan rincian akun admin lain kepada staf/wali. Perubahan jabatan profesional dapat muncul pada direktori yang diizinkan. |
| Biodata siswa/wali | Master, Akademik, Data Entry sesuai tugas; pemilik profil dan wali terkait | Guru/wali kelas hanya perubahan field yang memang boleh mereka lihat. Tidak broadcast alamat pribadi wali. |
| Nilai/absensi draft | Master, Akademik, Principal; guru mapel/wali kelas cakupan; Data Entry dengan delegasi | Tidak ke wali. Perubahan internal yang belum dipublikasikan tidak muncul lewat badge atau jumlah. |
| Nilai aktivitas dibagikan/absensi disahkan/dibatalkan | Kandidat akademik terkait dan wali anak sesuai visibilitas | Tautan menuju Perkembangan Belajar; tidak menunggu atau menyatakan rapor sudah final. Pembatalan netral tanpa membuka draft. |
| Catatan draft rapor berubah (Q7.1) | Master, Akademik, Principal dan wali kelas terkait menurut hak baca | Internal; tidak ke wali anak atau guru hanya satu mapel tanpa hak isi rapor. Payload realtime tetap minimal, tanpa teks catatan; membaca draft tidak menjadi publikasi rapor. |
| Rapor published/revised/needs_revision/revoked D-28 | Master, Akademik, Principal dan W/staf yang berhak; wali anak hanya semester yang memang pernah published | Published/current menuju Rapor Online aktif; held/revoked memberi notice aman tanpa snapshot, alasan internal atau link PDF versi lama. Koreksi aktivitas menuju Perkembangan Belajar. Draft/finalized belum pernah published tetap internal. Revoke kandidat internal tidak mengirim notice Dicabut bila versi aktif wali tetap sah. |
| Usulan naik/lulus submitted | Master, Akademik, Principal; wali kelas dalam cakupan baca | Wali tidak menerima kesan anak sudah naik/lulus. |
| Naik/lulus approved/rejected | Pengusul/pelaksana berhak, Principal, wali kelas terkait | Tidak menyatakan status siswa berubah sebelum apply. |
| Naik/lulus applied/corrected | Pihak akademik terkait dan wali anak | Pemberitahuan hasil yang sudah berlaku; batch hanya menghitung anak milik penerima. |
| Profil/password sendiri | Pengguna terkait; Master bila operasi akun memerlukan pengawasan | Tanpa isi password, hash, token, atau alamat sensitif dalam ringkasan. |
| Audit dibuat sistem / notifikasi turunan dibuat | Tidak ada notifikasi rekursif | Audit/notifikasi menunjuk operasi sumber. |
| Notifikasi ditandai dibaca | Tidak ada notifikasi baru | Aksi teknis tetap diaudit dengan metadata minimal. |

Jangan mengirim satu payload batch umum kepada semua role lalu menyembunyikan rinciannya di browser. Buat proyeksi ringkasan/jumlah per penerima di server. Email seluruh CUD/push/WhatsApp/preferensi kanal di luar lingkup; email auth D25 terpisah, provider SMTP/API belum dipilih.

Ini matriks rancangan, bukan bukti pengiriman. Seluruh tes notifikasi **NOT RUN**.

D26: handover/delegasi lama mengevaluasi penerima lama/baru secara scoped; pengguna lama boleh ringkasan aman perubahan dirinya tetapi tidak memperoleh akses histori dari tautan yang grant-nya berakhir. Worker memeriksa current scope sesudah cutover. D27 perubahan enrollment/draft roster internal bukan absensi finalized atau notifikasi alpa otomatis ke wali. Finalisasi tetap per anak yang benar-benar termasuk frozen roster. Email auth D25 terpisah dari kanal in_app matriks ini; email semua CUD/push/WhatsApp tetap di luar scope.
