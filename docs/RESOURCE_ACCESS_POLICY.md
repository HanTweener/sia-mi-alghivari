# Kebijakan akses sumber daya

Tanggal: 29 Oktober 2026. Status: rancangan, belum diimplementasikan. Acuan: [matriks izin](ROLE_PERMISSION_MATRIX.md), [PRD](PRD.md).

## Evaluasi dan sumber kebenaran

Akun aktif dan role dari database server; penugasan guru/wali kelas, hubungan wali-anak dan delegasi Data Entry juga dari database. Jangan memakai role dari form, URL, pilihan menu, atau user_id yang dikirim klien sebagai bukti izin.

Grant selalu mencakup action, resource, field dan keadaan. Role rangkap digabung menurut grant yang cocok; pembatasan domain seperti audit append-only, histori final dan larangan membaca password tetap berlaku pada semua role. Sekolah tunggal; tidak ada klaim isolasi banyak tenant.

Keputusan pemilik: principal boleh approval naik/lulus sebagai pengecualian baca; wali kelas boleh memublikasikan rapor binaan tanpa mengedit nilai mapel lain. Master saja mengatur role/jabatan. Data Entry C/U operasional tanpa D/konfigurasi, dan input pengganti guru hanya melalui delegasi khusus tercatat.

D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

Q6.1 Master akun wali/role/aktivasi dahulu lalu D biodata/link siswa. D-25 Master verified email administratif, password sementara emailed/forced change/modal; existing tambah role tidak direset. D tidak mengubah akun/username/email verified/role/password wali yang dilengkapi, hak profil/password mandiri D tetap. Relasi anak bukan profil sendiri. Allowlist lain Q6 tetap terbuka. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

| Resource | Jalur cakupan resmi |
| --- | --- |
| Credential awal, reset dan profil D-25 | Master trigger password sementara untuk akun baru, email langsung verified administratif; tanpa credential-read API. Forced login hanya setup/logout; OTP/proof self account/purpose/browser, tidak grant role/anak. Modal wajib setelah commit; reset login biasa/profil sesi kini tetap. |
| Data siswa | Enrollment -> classroom/term; guru -> assignment efektif -> class_subject kelas tersebut, homeroom aktif terpisah; wali -> student_guardians. Status anggota kini tidak menghapus hak histori yang sah; proyeksi minimum tetap. |
| Wali kelas dan publikasi rapor binaan | Guru -> homeroom_assignments aktif -> classroom -> enrollment/rapor pada kelas yang sama. Penugasan historis inactive tidak memberi hak akses aktif. |
| Nilai/absensi | Record -> assessment/session -> class_subject -> classroom/term sama dengan enrollment. G current saat request/commit, source assignment bukan grant. Draft menurut grant biasa; D-28 koreksi published/final MA atau G current term terbuka, closed MA saja. D delegasi tetap C/U draft tanpa koreksi. |
| Perkembangan wali | Hubungan wali-anak aktif -> nilai aktivitas yang dibagikan dan kehadiran yang disahkan; tidak memerlukan report_cards atau rapor published. Rekap sementara tidak memberi akses ke draft internal. |
| KKM/komponen/bobot | grading_policies global -> grading_policy_components -> scoring_components. Hanya Master/Akademik menulis; tidak ada override guru/mapel/kelas. Rapor final membaca snapshot versinya. |
| Catatan wali kelas rapor | W aktif -> homeroom_assignments -> kelas/enrollment rapor yang sama -> status draft; field homeroom_note saja. Master menurut hak domain. Finalized/published immutable, aktor tidak dikirim klien; hak baca tetap proyeksi rapor saat ini. |
| Rapor/file | Report -> siswa/periode/publikasi -> relasi dan scope pembaca saat ini. Storage private; izin dicek sebelum memberi isi/download. |
| Naik/lulus | Proposal -> siswa/tahun sumber, tujuan dan revision -> jabatan principal untuk approval -> pelaksana berhak untuk apply. |
| Audit | Scope stabil yang disimpan pada event ditambah hubungan/peran pembaca saat ini; proyeksi field dan visibilitas event ketika dibuat. |
| Notifikasi | recipient_id harus pengguna sesi; scope sumber diverifikasi lagi untuk daftar, badge, rincian dan tujuan. |
| Reverb/private channel | Subscription hanya channel pengguna sesi yang aktif. Worker memeriksa ulang penerima/scope, payload minimal menunjuk notification_id; isi inbox diambil lewat policy server, termasuk setelah reconnect. |
| Profil | User sesi, allowlist field; tidak ada role, penugasan, relasi anak atau is_active dalam payload edit mandiri. |

## Penerapan

Policy/action server digunakan pada daftar, objek, create/update/delete, bulk, print, file, audit dan notifikasi. Query daftar difilter sebelum pagination/count agar jumlah tidak membocorkan objek lain. Bulk memvalidasi setiap item sebelum transaksi.

Data Entry tidak boleh memanfaatkan input penugasan untuk memberi dirinya hak aktif. Penugasan yang disiapkan Data Entry tetap usulan sampai diaktifkan Akademik/Master. Delegasi harus aktif saat simpan, bukan hanya saat membuka form. Role/relasi dicabut -> permintaan berikutnya ditolak; jangan mengandalkan cache role tak berbatas.

Q6.3 dikonfirmasi 29 Oktober 2026: Master dan Admin Akademik dapat memberikan serta mencabut delegasi input nilai/kehadiran. Input tetap C/U draft pada petugas/capability/penugasan/masa berlaku yang sah, dengan audit actor asli. Hak tersebut bukan kewenangan dari role guru, wali kelas, kepala sekolah atau Data Entry semata; grant Master/Akademik dari role rangkap tetap berlaku. Mekanisme aktivasi usulan penugasan guru Data Entry belum disetujui melalui keputusan ini.

Field profesional wali kelas untuk wali: nama, foto yang diizinkan, serta kontak profesional yang memang dipilih sekolah. Jangan sertakan alamat rumah, nomor identitas, seluruh profil pribadi, atau hash password. Detail field biodata staf lintas admin masih perlu keputusan pemilik.

## Audit yang tidak dapat ditulis pengguna

Setiap perubahan domain berhasil mempunyai audit sistem: actor sebenarnya, action, target, scope, waktu server, batch/correlation, alasan bila wajib dan diff field yang diizinkan. Perubahan data dan audit berada dalam transaksi yang sama. Jika audit gagal, data tidak commit. Kegagalan request tidak ditulis sebagai perubahan berhasil.

Tidak ada endpoint/user action create/update/delete audit, termasuk Master. Credential database runtime tidak mempunyai UPDATE/DELETE audit atau hak DDL; migrasi memakai credential terpisah. Service audit internal saja yang memanggil INSERT. Perlindungan ini membatasi pengguna aplikasi; tidak mengklaim dapat mengalahkan administrator database/server yang menguasai storage.

Audit target menyimpan identitas/scope historis yang stabil dan tidak terhapus dengan cascade. Master menghapus data melalui operasi domain, bukan menghapus sejarah. Password/token/hash/nilai kredensial/isi sesi tidak disimpan pada diff. Koreksi audit berupa event tambahan; tidak menimpa log sebelumnya.

Setiap perubahan objek dalam batch tetap dicatat. Event draft ditandai internal saat dibuat sehingga wali tidak memperoleh log draft hanya karena hasilnya kemudian dipublikasikan. Tampilan log memproyeksikan field sesuai pembaca; row-level access saja tidak cukup.

Pembuatan audit dan notifikasi turunan terhubung ke sumber operasi, tanpa membangkitkan audit/notifikasi rekursif. Aksi pengguna seperti ubah read-state notifikasi tetap dicatat sebagai event teknis tanpa mengirim notifikasi baru. Data sesi/cache/pekerjaan framework bukan perubahan domain pengguna.

## Status verifikasi

Positive/negative policy tests, account recovery, file authorization, role revocation dan immutability database **NOT RUN**, belum ada aplikasi. Jalankan pada engine target dan akun runtime yang benar saat implementasi; menyembunyikan tombol atau lulus pemeriksaan dokumen tidak menjadi bukti keamanan.

### D-28: koreksi, penahanan dan riwayat rapor

Izin koreksi dipisahkan dari PATCH draft; alasan/versi/konfirmasi tidak menggantikan grant. W/principal/D tanpa role tambahan tidak mengoreksi sumber sah. Frozen attendance hanya status record existing; source assignment/creator dan roster tidak dipindah. Grant terbaru serta term diperiksa ulang saat commit.

MA/W aktif binaan boleh revisi/finalisasi/publikasi dan revoke rapor terkait termasuk term closed. Histori homeroom yang tercetak bukan grant guru lama. Akses detail/unduh wali mensyaratkan relasi anak aktif serta versi published/current aktif; needs_revision/revoked/superseded ditolak pada GET berikutnya, termasuk URL tersimpan. Proyeksi semester yang pernah published boleh memberitahu sedang direvisi/dicabut tanpa snapshot, alasan internal, count draft atau versi lama. Staf membaca histori hanya dalam proyeksi yang memang berhak; guru satu mapel tetap tidak otomatis membaca PDF gabungan.

Penahanan dilakukan bersama koreksi/audit/inbox dalam transaksi; job lama dan prepare-file tidak dapat mengaktifkan ulang versi tertahan. Satu versi aktif dijaga saat publish dengan lock/version. Pemeriksaan unduh membatasi request setelah commit penahanan; byte yang sudah dikirim/diterima tidak dapat dibatalkan.

D-29: school_timezone adalah konfigurasi Master saja. Pembaca lain memperoleh proyeksi id/label/version tanpa key settings lain. Mutasi akademik sensitif memeriksa grant/date terhadap setting terbaru di bawah mutex akademik -> key -> parent/objek; form stale ditolak school_timezone_changed. Satu timezone tidak memperluas grant atau mengubah timestamp UTC. Cap delegasi turunan assignment DATE saat zona berubah masih OPEN.
