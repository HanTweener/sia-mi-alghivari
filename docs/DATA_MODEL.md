# Model data SIA MI Alghivari

Versi 0.8, 30 Oktober 2026. Status: blueprint, tanpa DDL/migration/database berjalan. Pemilik menetapkan absensi per mapel, KKM/bobot global, rumus rata-rata berbobot dan perlakuan blank/nol; aturan Q7.1-Q7.5 diterima, detail visual/render belum diverifikasi. Hak akses mengikuti [PRD](PRD.md).

## 1. Batas dan konvensi

Satu sekolah dan satu database relasional. Pemilik memilih satu aplikasi Laravel + Inertia + React/TypeScript pada 29 Oktober 2026; engine MySQL juga dipilih 29 Oktober 2026; baseline MySQL 8.4 LTS/InnoDB dan versi inti pada TECH_STACK.md juga disepakati; patch, konfigurasi dan paket tambahan belum dikunci. Tidak ada data lama yang dimigrasikan. Semua seed/tes sintetis. ID internal terpisah dari NIS/nomor pegawai yang berupa teks.

Tabel domain memiliki timestamp yang relevan, foreign key bertipe cocok, dan indeks berdasarkan query nyata. Status final tidak ditulis ulang melalui CRUD biasa. Hapus data yang sudah dipakai histori menjadi arsip/pembatalan; tidak menghapus nilai, rapor, keputusan atau audit melalui cascade.

Rancangan fisik pendukung: [keputusan engine](DATABASE_DECISION_RECORD.md), [arsitektur database](DATABASE_ARCHITECTURE.md), [implementasi provider](DATABASE_PROVIDER_IMPLEMENTATION.md), [migrasi/pemulihan](DATABASE_MIGRATION_PLAN.md), dan [verifikasi](DATABASE_VERIFICATION_REPORT.md). Engine MySQL dipilih pemilik 29 Oktober 2026; baseline 8.4 LTS/InnoDB juga disepakati; patch/image dan konfigurasi belum dikunci. Generated column untuk unique bersyarat, version induk dan satu baris mutex akademik internal pada system_settings merupakan usulan implementasi fisik; belum ada DDL atau perubahan alur produk yang disetujui otomatis. Baris mutex tidak menjadi konfigurasi yang dapat diedit lewat UI.

## 2. Akun, profil dan konfigurasi

| Tabel | Kolom penting dan integritas |
| --- | --- |
| users | id, name, username unik, email wajib unik untuk akun baru D-25; email_verified_at, email_verified_by FK users nullable untuk bootstrap, email_verification_source master_attested. password hash(255), must_change_password boolean, temporary_password_expires_at nullable, password_changed_at nullable. Usulan teknis credential_version/session_generation integer nonnegatif default0, pending_session_choice_id UUID nullable, pending_session_choice_flow first_setup/password_reset/profile_password_change nullable. phone/address/avatar_path opsional, is_active. Field credential/role/status/verified/versi/pending bukan allowlist profil. |
| `user_roles` | user_id, role; unik pasangan. Role tetap master_admin/academic_admin/data_entry_admin/teacher/principal/guardian. Perubahan hanya Master dan diaudit. |
| `teachers` | id, user_id unik, staff_number opsional unik. Principal dan admin dapat mempunyai profil/penugasan guru; jabatan tidak otomatis memberi kelas mengajar. |
| `students` | id, nis unik, name, birth_date opsional DATE, status. Data minimum; tidak mewajibkan NIK/identitas keluarga sebagai bawaan. |
| `student_guardians` | student_id, guardian_user_id, relationship; unik pasangan. Scope anak diverifikasi server; bukan deklarasi mandiri dari wali. |
| `system_settings` | key unik, value terstruktur dengan schema allowlist. Hanya Master mengelola; profil sekolah yang boleh tampil merupakan proyeksi terbatas. Tidak menyimpan kredensial aplikasi/database di UI ini. |

D-25 menggantikan D-23/D-24: Master akun/email langsung verified administratif/password sementara acak emailed bersama username/URL biasa -> login terbatas -> forced change -> modal pilihan sesi wajib. Reset/profil memakai OTP email Fouladgar; reset login biasa, profil current device tetap login. Existing tambah role tidak direset; scope anak tetap relasi. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

### OTP dan metadata credential — blueprint tanpa migration

| Tabel/kolom | Rancangan dan integritas |
| --- | --- |
| otp_tokens.id / user_id | ID internal, FK users, target dari lookup server. Infrastruktur keamanan, tanpa CRUD admin/API baca token. |
| recipient / purpose | Snapshot email tercatat dan enum password_reset/profile_password_change dari server. Unique(user_id,purpose) untuk satu slot terbaru; reissue mengganti ID challenge agar proof lama tidak hidup lagi. |
| token_hash | VARCHAR(255), hash satu arah fasilitas framework; bukan token plaintext/query token=code. Repository aplikasi mengadaptasi kontrak paket. |
| sent_at / expires_at | Timestamp UTC penerbitan/expiry; sent_at bukan bukti delivered. TTL belum dipilih. |
| verified_at / proof_expires_at / completed_at / revoked_at | Timestamp nullable, kode terpakai -> proof terbatas -> password berhasil atau revoked. verified_at mencegah replay OTP; completed_at bersama password/audit. |
| credential_version / browser_binding_hash | Snapshot revisi credential dan hash binding browser/proof, bukan raw password/token/session ID. Format/binding akhir wajib diuji. |
| sessions | Sesi database Laravel dipilih; schema framework dan payload server berisi generation/scope normal/forced-change/proof/modal seperlunya, bukan grant dari browser. |

Indeks kandidat unique(user_id,purpose), expires_at untuk pruning dan FK RESTRICT user_id. Retensi/pruning metadata belum dipilih, tanpa cascade histori domain/audit. Tidak menambah tabel Spatie one_time_passwords atau broker password_reset_tokens kedua.

Fouladgar v6.2.0 mempunyai storage token mentah/validate-revoke terpisah. Target di atas memerlukan repository/binding aplikasi, penyesuaian migration auto-load/prune vendor dan hash/konsumsi atomik; bukan publish migration vendor apa adanya. Lock users -> challenge dengan urutan konsisten untuk verify/resend/complete/email-change, bukan mutex akademik. OTP dikonsumsi untuk proof terbatas; password/proof/pending/audit atomik. Cookie/email bukan transaksi DB.

credential_version meningkat pada perubahan password/reissue awal atau perubahan kontak yang membatalkan proof, tanpa otomatis mencabut sesi normal saat No. session_generation meningkat saat revocation sesuai pilihan/lifecycle; sesi normal memuat generation dan ditolak bila tak cocok. Sesi sementara/proof juga memeriksa credential_version. Ini usulan fisik eksplisit, bukan middleware terbukti. Users menyimpan satu pending operation terbaru/flow dan password_changed_at; audit menyimpan histori aman, sehingga tidak menambah tabel workflow/password-change history. Pilihan lama/duplicate tidak mencabut sesi baru.

Email_verified_at/by/source diisi Master sesuai keputusan, tanpa mengklaim pembuktian mailbox. OTP/email edit tidak boleh otomatis mewarisi verifikasi atau proof lama; alur perubahan kontak/fallback belum final. Durasi password sementara/OTP/proof, panjang/limit/hash/binding, retensi serta provider email masih OPEN.

## 3. Struktur akademik dan penugasan

| Tabel | Kolom penting dan integritas |
| --- | --- |
| `academic_years` | id, name unik, starts_on, ends_on. Awal tidak melewati akhir. |
| `terms` | id, academic_year_id, semester 1/2, dates, status draft/active/closed; unik tahun/semester. Aktivasi semester sekolah diserialisasi dalam transaksi. |
| classrooms | id, term_id, grade_level 1-6, section; unik periode/tingkat/section. Rombel per semester, tanpa teacher_id/homeroom_teacher_id. Usulan teknis roster_version integer meningkat pada perubahan enrollment untuk mengusangkan form/preview peserta. |
| `homeroom_assignments` | id, classroom_id, teacher_id, status active/inactive, assigned_by, created_at, updated_at. Penugasan guru menjadi wali kelas; maksimal satu penugasan active per rombel. Periode mengikuti classrooms.term_id. |
| enrollments | id, student_id, classroom_id, term_id, starts_on DATE, ends_on DATE nullable, status. Unik siswa/semester tetap termasuk anggota berakhir; FK(classroom_id,term_id) -> classrooms(id,term_id). Rentang [starts_on,ends_on), akhir eksklusif; NULL sampai akhir term. Status siswa kini tidak menghapus histori. Tanpa transfer/re-enroll antarkelas term sama. |
| `subjects` | id, code unik, name, is_active. Tidak memuat guru/kelas/jadwal. |
| class_subjects | id, classroom_id, subject_id, timestamps; unique(classroom_id,subject_id), periode dari classroom.term_id. Identitas mapel kelas tetap meskipun pengajar berganti; bukan duplikasi subjects. Data yang telah dipakai tidak dipindah kelas/mapel lewat PATCH. |
| teaching_assignments | id, class_subject_id, teacher_id, starts_on DATE, ends_on DATE nullable, status assigned/cancelled, assigned_by, timestamps. Riwayat penanggung jawab bertanggal, rentang [starts_on,ends_on), satu yang berlaku pada satu tanggal per class_subject. Grant saat ini dari assigned + rentang tanggal kalender sekolah, tanpa scheduler pengubah flag active harian. Unique(id,class_subject_id) mendukung FK asal kegiatan. MA saja menetapkan; D usulan tidak langsung memberi grant. |
| schedule_slots | id, class_subject_id, weekday 1-7, starts_at, ends_at. Jadwal tetap pada mapel kelas; guru efektif dari histori assignment. Validasi overlap jam/hari/rentang tanggal guru dan kelas, termasuk saat handover. |
| `scoring_components` | id, code unik, name, source_type score/attendance, sort_order, is_active, created_at, updated_at. Katalog pusat komponen: tugas, ujian harian, UTS, UAS, serta kehadiran otomatis. Tidak memiliki subject_id/classroom_id/teacher_id. Hanya Master/Akademik mengelola. |
| `grading_policies` | id, revision unik, kkm DECIMAL(5,2), status draft/active/retired, created_by, activated_by opsional, activated_at opsional, created_at, updated_at. Satu kebijakan global active untuk seluruh mapel; KKM 0-100 inklusif, maksimal dua desimal (Q7.5); validasi sebelum cast/insert tanpa pembulatan input. Tidak terikat penugasan/mapel/kelas. Versi yang pernah diaktifkan dipertahankan. |
| `grading_policy_components` | id, grading_policy_id, scoring_component_id, weight_percentage DECIMAL(5,2), created_at, updated_at. Unik kebijakan/komponen; bobot 0-100 inklusif/maksimal dua desimal (Q7.5), tanpa pembulatan input. Total seluruh bobot versi kebijakan, termasuk kehadiran, tepat 100 sebelum aktivasi. |
| `data_entry_delegations` | id, user_id, teaching_assignment_id, capability grade_entry/attendance_entry, valid_from, valid_until, granted_by, reason, revoked_at. Q6.3 mengonfirmasi Master/Akademik sebagai pemberi/pencabut delegasi; mekanisme permintaan/perubahan dan DTO rinci belum final. Hak Data Entry tidak dapat digunakan untuk memberi delegasi kepada diri sendiri. |
| `assignment_requests` | id, requested_by, assignment_type mapel/homeroom, classroom_id, subject_id opsional, proposed_teacher_id, action, status pending/approved/rejected, reviewed_by, reviewed_at. Rekomendasi kontrol input penugasan Data Entry, belum aturan pemilik yang eksplisit. Aktivasi perubahan resmi dan penutupan request atomik. |

Foreign key/jadwal/kelas yang sama tidak membuktikan hak guru; gunakan policy sumber daya. Kecocokan guru profile dengan role aktif dan penugasan periode diperiksa server. Penugasan mapel dan wali kelas terpisah dari role akun principal.

### Komponen, KKM dan bobot terpusat

Sebelum versi ini belum ada tabel scoring_components: komponen masih diwakili kolom bobot tetap pada grading_policies per penugasan. Rancangan tersebut diganti katalog scoring_components dan baris bobot grading_policy_components, dengan grading_policies sebagai kepala versi KKM/kebijakan global. Tidak ada override mapel, guru atau kelas pada tahap inti.

| Kolom scoring_components | Tipe konseptual | Makna |
| --- | --- | --- |
| `id` | ID internal | Primary key komponen yang stabil. |
| `code` | Teks unik | Contoh assignment, daily_test, midterm, final, attendance; tidak diubah setelah dipakai. |
| `name` | Teks | Label seperti Tugas, Ujian Harian, UTS, UAS, Kehadiran. |
| `source_type` | Kode tetap | score untuk nilai input; attendance untuk hasil sesi kehadiran. Tidak menjadi pembuat rumus bebas. |
| `sort_order` | Integer | Urutan tampilan komponen. |
| `is_active` | Boolean | Ketersediaan komponen untuk konfigurasi baru; tidak menghapus histori. |
| `created_at` / `updated_at` | Timestamp | Waktu pembuatan/perubahan. |

Bobot fleksibel ada pada grading_policy_components.weight_percentage, bukan kolom tetap assignment_weight dan seterusnya. Pemisahan ini menjaga identitas komponen stabil saat bobot direvisi. Kehadiran hanya satu komponen otomatis; tidak boleh dibuat sebagai assessment manual atau dihitung dua kali. Bobot 0 mengizinkan komponen tidak menyumbang nilai akhir, tetapi total seluruh komponen tetap 100.

Master/Akademik menyiapkan satu draft kebijakan berisi KKM dan seluruh bobot, lalu mengaktifkannya secara atomik setelah validasi total 100. Jumlah lintas baris bukan CHECK satu baris: validasi seluruh set dan serialisasi aktivasi diperlukan pada engine target. Guru, wali kelas, Principal dan Data Entry tidak memperoleh hak konfigurasi dari penugasan mereka; pengguna dengan role admin yang berhak dapat mengubahnya melalui hak admin tersebut.

Versi active tidak ditimpa. Perubahan KKM/bobot menjadi revisi global baru; rapor pertama yang belum final memakai versi active yang ditampilkan pada preview. Revisi rapor D-28 memakai grading_policy_id/calculation_version asli meski policy retired, bukan otomatis active terbaru. Finalisasi memeriksa versi belum berubah dan membekukannya pada rapor. Rapor final lama tetap memakai snapshot versi sebelumnya.

### Penugasan wali kelas

Keputusan pemilik: relasi wali kelas melalui `homeroom_assignments`, bukan foreign key guru pada `classrooms`.

| Kolom | Tipe konseptual | Keterangan |
| --- | --- | --- |
| `id` | ID internal | Primary key penugasan. |
| `classroom_id` | ID internal | Foreign key ke classrooms.id. |
| `teacher_id` | ID internal | Foreign key ke teachers.id. |
| `status` | Kode tetap | active atau inactive. Hanya penugasan active memberi hak wali kelas. |
| `assigned_by` | ID internal | Foreign key ke users.id, pengguna yang menetapkan penugasan resmi. |
| `created_at` | Timestamp | Waktu pembuatan penugasan. |
| `updated_at` | Timestamp | Waktu perubahan terakhir. |

Satu rombel dapat memiliki beberapa penugasan historis, tetapi maksimal satu active. Penggantian wali kelas menonaktifkan penugasan lama dan membuat penugasan baru dalam satu transaksi bersama audit/notifikasi. Cegah dua penugasan active serentak dengan constraint yang sesuai engine dan penguncian rombel saat pergantian; tidak memakai unique(classroom_id) pada seluruh riwayat.

Tidak menggandakan term_id atau academic_year_id pada tabel ini karena periodenya diperoleh dari classroom. Tidak menetapkan unique(teacher_id) tanpa aturan produk tambahan; penugasan wali kelas tetap independen dari penugasan mapel.

Master/Akademik menetapkan penugasan sesuai matriks. Mekanisme request Data Entry yang masih diusulkan menghasilkan perubahan pada homeroom_assignments saat disetujui, bukan mengubah kolom guru di classroom. Hak baca binaan dan publikasi rapor ditentukan melalui penugasan aktif tersebut; penugasan inactive tidak memberi hak tambahan.

Keputusan Q6.1 dikonfirmasi pemilik 29 Oktober 2026: Master membuat `users` wali, menetapkan `user_roles` guardian dan mengaktifkan akun dahulu; Data Entry kemudian melengkapi biodata yang diizinkan serta hubungan `student_guardians` ke siswa. Target hubungan harus akun wali yang sudah sah, bukan pembuatan akun/role terselubung lewat biodata. Wali tanpa hubungan anak tidak memperoleh akses siswa. Tidak menambah tabel profil wali pending atau biodata wali sebelum akun tersedia. Metode credential awal kemudian ditetapkan D-25 pada bagian2; allowlist biodata Q6 tetap terbuka. Keputusan urutan Q6.1 sendiri tidak memberi grant akun kepada D.

## 4. Kehadiran dan penilaian

| Tabel | Kolom penting dan integritas |
| --- | --- |
| attendance_sessions | id, class_subject_id, teaching_assignment_id asal, created_by, meeting_number, held_on, topic opsional, status draft/finalized, finalized_at/by. Unique(class_subject_id,meeting_number), nomor berlanjut lintas guru. Usulan reviewed_roster_version pada draft. FK gabungan asal (teaching_assignment_id,class_subject_id) -> teaching_assignments(id,class_subject_id). Asal tidak menentukan grant terkini. |
| attendance_records | id, attendance_session_id, enrollment_id, status present/excused/sick/absent, recorded_by; unik sesi/anggota. Draft tanpa baris berarti belum dicatat; finalized mempunyai tepat seluruh peserta yang berlaku beserta status lengkap, menjadi frozen roster tanpa tabel peserta baru. |
| assessments | id, class_subject_id, teaching_assignment_id asal, created_by, scoring_component_id, title, assessed_on, status draft/published, published_at/by. Komponen score pusat; FK asal gabungan cocok dengan class_subject. Pemilik izin adalah pengajar efektif saat ini pada parent, creator/asal tidak ditimpa saat handover. Tidak ada bobot per guru atau assessment duplikat untuk memindah pemilik. |
| `scores` | id, assessment_id, enrollment_id, score DECIMAL(5,2), feedback opsional, graded_by, delegation_id opsional; unik penilaian/anggota. Q7.2 skor input 0-100 inklusif/maksimal dua desimal disepakati; validasi sebelum cast/insert tanpa pembulatan input berlebih. Nilai 0 sah; blank tidak mempunyai baris skor numerik. |

Kedua jenis input menyimpan actor asli pada audit. Untuk absensi delegasi, konteks delegation_id dicatat pada audit operasi. Record siswa harus berasal dari rombel/term class_subject yang sama serta roster yang berlaku; rentang skor, komponen dari katalog sah, tanggal dalam semester dan delegasi aktif divalidasi dalam transaksi. Input satu kelas atomik dan mendeteksi konflik edit melalui versi/updated_at.

Absensi per mapel telah ditetapkan pemilik: guru membuka sesi pada class_subject berdasarkan assignment efektifnya, mengisi daftar siswa, lalu mengesahkan setelah semua status terisi. Hanya sesi finalized yang menjadi pertemuan terlaksana dalam perhitungan; draft/dibatalkan tidak menjadi penyebut. Tidak ada pengisian alpa otomatis untuk status yang kosong.

Untuk setiap siswa/mapel/semester, nilai komponen input = jumlah nilai numerik pada assessment published dibagi jumlah nilai numerik tersebut. Blank ditampilkan Belum ada nilai dan dilewati; 0 tetap masuk jumlah/pembagi. Blank sebagian tidak menghalangi perhitungan atau finalisasi hanya karena ada assessment belum dinilai. Assessment boleh published dengan blank yang tetap ditandai; draft tidak masuk hasil publik.

Kontribusi komponen = rata-rata tersebut dikali weight_percentage/100. Kontribusi kehadiran = (hadir / total pertemuan finalized yang berlaku untuk siswa) * 100 * bobot_kehadiran/100. Izin/sakit/alpa tetap tercatat tetapi bukan hadir dalam pembilang; rekapan menggunakan satuan pertemuan, bukan hari sekolah. Jumlah kontribusi semua komponen menjadi nilai akhir mapel.

Pemilik mengonfirmasi: komponen berbobot positif yang seluruh nilainya blank membuat hasil Belum ada nilai dan mencegah finalisasi. Kehadiran dengan bobot positif dan nol pertemuan juga belum dapat dihitung. Komponen berbobot 0 tidak menghalangi finalisasi. Jangan mengisi 0 palsu atau membagi ulang bobot komponen lain untuk menutup kekosongan.

Aturan rinci dan contoh hitung ada pada [alur akademik](ACADEMIC_FLOW.md). Q7.3 dikonfirmasi pemilik 29 Oktober 2026: pertahankan presisi rata-rata/kontribusi sampai total akhir, lalu bulatkan dua desimal half-up. Nilai akhir snapshot dan penyajian UI/PDF memakai hasil yang sama; Q7.4 memakai nilai akhir yang sudah dibulatkan >= kkm_snapshot untuk Mencapai KKM, selainnya Belum mencapai KKM; hasil belum tersedia tidak dibandingkan sebagai0.

AR-04/D-26 dan AR-05/D-27 dikonfirmasi 29 Oktober 2026. class_subjects memisahkan identitas mapel kelas dari histori pengajar; nilai/pertemuan/jadwal/item rapor mengikuti parent stabil. Guru baru efektif berhak histori dan draft pada parent, bukan menimpa asal/creator. Guru lama tidak memperoleh grant aktif dari provenance; role lain tetap. Handover beralasan oleh MA menutup rentang lama/membuat baru, mengecek bentrok jadwal dan mengakhiri delegasi lama dalam transaksi audit/inbox. Published/final sumber operasional hanya melalui command koreksi D-28; isi rapor final tetap immutable. [Aturan kanonis](ACADEMIC_FLOW.md).

### Batas tanggal, roster dan histori D-26/D-27

Usulan representasi tanggal [starts_on,ends_on): awal termasuk, akhir tidak. starts_on < ends_on bila akhir ada; seluruh rentang dalam semester (ends_on dapat batas hari setelah hari terakhir semester). NULL dibatasi akhir term, bukan grant lintas periode. Contoh A [1 Nov,15 Nov), B mulai15 Nov: tanggal15 milik B. Dua handover tanggal sama yang menghasilkan rentang nol/overlap ditolak; tidak mengarang urutan per jam. D-29 menetapkan satu school_timezone dengan pilihan WIB/WITA/WIT; nilai awal perlu dipilih saat setup, bukan dari host.

Rentang assignment assigned tidak tumpang tindih. Serialisasi memakai mutex akademik -> class_subject -> objek terkait; cek overlap terhadap seluruh histori/rencana relevan, bukan unique flag active saja. Effective read/grant memakai tanggal sekolah dan status assigned. Cancelled bukan celah menghapus histori yang dipakai. Handover masa depan tidak perlu cron; predikat tanggal menentukan hak. Batas delegasi tidak melebihi assignment efektif; delegasi lama tidak dibawa ke pengajar baru, regrant oleh MA sesuai Q6.3. Detail koreksi/undo pergantian tetap lifecycle tersendiri.

Assessment/session menyimpan penugasan asal sebagai provenance saat dibuat, bukan klaim guru asal selalu mengajar pada held_on/assessed_on. Draft yang tanggalnya melewati cutover tetap dapat dilanjutkan guru baru; jangan mewajibkan tanggal kegiatan berada pada rentang assignment asal. Tanggal kegiatan tetap dalam term dan enrollment harus kelas/term sama; FK asal tidak membuktikan hubungan enrollment. Skor sebelum siswa masuk/setelah keluar tetap tidak dibuat/dianggap0, blank tidak dihitung sesuai aturan yang ada. Tidak ada impor nilai sekolah/kelas asal.

Saat enrollment berubah: periksa bahwa frozen roster final tidak berubah, naikkan roster_version, audit/inbox satu transaksi. Session draft menyajikan peserta eligible pada held_on dan reviewed_roster_version. Jika versi berubah, finalisasi menolak stale/error form roster_changed dan meminta review; perubahan draft record tidak disembunyikan sebagai auto-delete atau memberi D hak hapus baru. Finalisasi menserialkan enrollment, memeriksa ulang roster dan memastikan set records tepat lengkap H/I/S/A, lalu membekukan dalam satu transaksi. Tidak perlu tabel session_participants.

Penyebut siswa = jumlah attendance_records miliknya pada session finalized parent yang relevan; pembilang hanya present. Jangan memfilter ulang frozen record memakai status/rentang enrollment kini. Draft/batal dan pertemuan sebelum masuk/setelah keluar bukan penyebut/alpa. Backdate yang menambah/menghapus peserta final ditolak; mengubah tanggal sesi final atau data final juga tidak lewat PATCH biasa. Isi snapshot/item/PDF rapor finalized/published tetap immutable; metadata akses mengikuti D-28. Rekap anggota berakhir tetap tersedia sesuai izin histori, tanpa modul rapor pindahan.

Unique(student_id,term_id) tetap; mengakhiri enrollment tidak mengizinkan create enrollment kelas lain pada term yang sama. Pergantian periode melalui alur semester/naik kelas yang sudah dirancang, bukan transfer tengah semester. Aktor penempatan oleh D/allowlist Q6 tidak diputuskan oleh aturan tanggal ini.

## 5. Rapor dan perkembangan siswa

Q7.1 disepakati 29 Oktober 2026: PDF ringkas; rincian komponen di detail aplikasi dari snapshot yang sama. Kehadiran per mapel menyimpan H/S/I/A/total pertemuan/persentase yang berlaku, bukan hari. Catatan wali kelas adalah field tingkat rapor (`homeroom_note`), bukan catatan kompetensi per mapel. Wali kelas aktif binaan dapat menulisnya saat draft, Master tetap hak domain; role lain tidak mendapat hak tulis baru dari format. Catatan opsional dan dibekukan saat finalized, tidak diedit pada finalized/published. Tanpa catatan tidak menghalangi finalisasi. Ini pembaruan model logis, bukan migration; batas skor input Q7.2 diterima; pembulatan akhir Q7.3 juga diterima; pembanding KKM Q7.4 juga diterima; presisi konfigurasi Q7.5 juga diterima; implementasi belum diverifikasi.

| Tabel | Kolom penting dan integritas |
| --- | --- |
| `report_cards` | id, enrollment_id, term_id, grading_policy_id, homeroom_assignment_id, revision, supersedes_id nullable, status draft/finalized/published/revoked; usulan availability current/needs_revision/superseded, version, source_version, lifecycle_reason, revoked_at/by nullable. Identitas/school/class/term/homeroom/principal snapshots, homeroom_note TEXT nullable, attendance_summary, calculation_version, generated_at, finalized_at/by, published_at/by, private_file_path nullable. Unique(enrollment_id,revision). Policy/calculation versi asli saat revisi. Isi snapshot/item/file frozen; metadata lifecycle diaudit. |
| report_card_items | id, report_card_id, class_subject_id, subject_snapshot, teaching_history_snapshot, component_breakdown_snapshot JSON, kkm_snapshot, final_score, kkm_status; unique(report_card_id,class_subject_id). Satu item per mapel kelas, menghitung published scores dan finalized pertemuan dari semua assignment asal yang cocok, tanpa merata-ratakan rekap guru. Snapshot menyimpan sumber/riwayat pengajar relevan, counts/blank dan attendance roster berlaku; bukan FK guru terakhir sebagai identitas item. |
| `student_progressions` | id, student_id, source_academic_year_id, source_report_card_id, type promotion/graduation, target_academic_year_id opsional, target_classroom_id opsional, status draft/submitted/approved/rejected/applied/cancelled/needs_review, version, proposed_by, approved_by/at, applied_by/at, reason. Unik siswa/tahun sumber untuk keputusan aktif; cegah penerapan ganda. |

Guru mapel memublikasikan hasil mapelnya. Master, Akademik dan wali kelas yang ditugaskan dapat memfinalisasi/memublikasikan rapor gabungan setelah data yang diperlukan tersedia; wali kelas hanya kelas binaan. Blank sebagian dilewati sesuai aturan, bukan dianggap semua penilaian wajib terisi. Principal tidak mendapat P dari jabatan saja, tetapi boleh jika juga mempunyai penugasan wali kelas sesuai keputusan pemilik.

Rapor yang diunduh adalah versi published yang diizinkan untuk anak terkait. File private; jangan menerima path dari klien atau memakai URL publik tetap. Bila pembentukan file gagal, tampilkan kegagalan dan retry, bukan keberhasilan unduh. Renderer Dompdf melalui barryvdh/laravel-dompdf 3.1.x disepakati 29 Oktober 2026 (D-17); susunan Q7.1 disepakati, tetapi aturan angka Q7, detail visual dan implementasi/render belum diverifikasi. Format tidak diklaim sebagai integrasi e-Rapor pemerintah.

Pemantauan wali berbeda dari rapor: query aktivitas mengambil assessments/scores yang sudah dibagikan serta attendance_sessions/records yang disahkan untuk anaknya, tanpa syarat adanya report_cards. Rekapnya sementara dan boleh tersedia ketika sebagian komponen belum mempunyai nilai. Tidak perlu tabel pemantauan tambahan atau menyalin data aktivitas menjadi rapor sementara.

Status finalized pada report_cards hanya diberikan setelah seluruh mapel yang diikuti masuk rekap dan syarat data/kehadiran/konfigurasi lolos; published hanya boleh berasal dari finalized. Assessment published atau satu sesi finalized tetap boleh ditampilkan pada perkembangan meski rapor masih draft atau belum dibuat. Status finalized rapor belum memberi hak baca/unduh wali; publikasi rapor adalah langkah berikutnya.

Report term harus sama dengan term enrollment. Setiap report item harus berasal dari class_subject rombel enrollment tersebut dan versi grading_policy global yang dibekukan pada report. Pemublikasian lintas kelas ditolak sebelum snapshot/file dibuat; file dan item tidak boleh dipercaya hanya karena ID rapor valid. Kebijakan, label komponen, KKM dan bobot terbaru tidak dibaca ulang untuk mengganti isi rapor final lama.

Saat finalisasi, homeroom_assignment_id wajib menunjuk penugasan wali kelas active pada rombel enrollment yang sama. Snapshot identitas wali kelas disimpan bersama rapor; penggantian wali kelas sesudahnya tidak mengubah identitas pada versi rapor final lama. Referensi penugasan historis pada rapor tidak memberikan hak akses aktif kepada guru lama.

Naik/lulus: siapkan proposal -> principal approve/reject (individual atau batch) -> admin pelaksana menerapkan pada waktu berlaku. Master/Akademik menyiapkan/menerapkan sebagai rekomendasi proses; persetujuan resmi tetap hak principal, bukan efek CRUD Master. Kenaikan tingkat 1-5 ke tingkat berikutnya; kelulusan tingkat akhir perlu data lengkap. Tidak ada promosi otomatis hanya dari KKM.

Approve mengunci version/sumber rapor dan tujuan; perubahan sumber sesudahnya menjadi needs_review dan tidak boleh diterapkan tanpa persetujuan ulang. Apply membuat enrollment tujuan atau status kelulusan; histori asal tetap. Copy semester ganjil ke genap tidak menaikkan tingkat; kenaikan hanya pada pergantian tahun yang disetujui. Batch validasi dahulu; kegagalan objek/izin membatalkan seluruh batch dan dilaporkan jelas.

## 6. Audit dan notifikasi

| Tabel | Kolom penting dan integritas |
| --- | --- |
| `audit_logs` | id, actor_user_id opsional untuk sistem, action, entity_type/id, scope student/class/assignment/term/user, event_visibility internal/subject_visible, allowed_changes, reason, batch_id, created_at server. Append-only; tidak ada CUD pengguna, termasuk Master. Scope historis tidak cascade; log target bukan FK yang menghilang ketika objek diarsipkan. |
| `notifications` | id, recipient_id, source_audit_id, batch_id opsional, event_category, scope_type/id, sanitized_summary, visible_item_count, read_at opsional, created_at. Unique dedup key per operasi/penerima/kategori/cakupan. Penerima dan isi dihitung server sesuai izin. |

Data bisnis + audit + inbox notifikasi dicommit bersama; gagal persist salah satunya menggagalkan transaksi. Pemilik memilih Reverb/Echo: setelah commit, broadcast diproses database queue dan worker melalui private channel per pengguna. Gagal broadcast sesudah commit tidak membatalkan data tersimpan; inbox tetap sumber riwayat dan catch-up. Sinyal socket bukan jaminan exactly-once dan celah gagal dispatch dipulihkan lewat pembacaan inbox. Tabel jobs/failed_jobs adalah infrastruktur framework; tidak menambah broker eksternal atau Redis pada rancangan satu instance.

Setiap objek domain dalam batch tetap memiliki audit, tetapi notification dikelompokkan per penerima dan cakupan yang aman. Audit bukan sumber yang bebas dibaca semua pengguna; event draft tetap internal, diff diproyeksikan sesuai field yang boleh dilihat.

Pencatatan password hanya fakta perubahan, bukan hash/nilai lama/baru. Audit dibuat service sistem tanpa form/API pengguna; credential runtime tidak punya UPDATE/DELETE audit atau DDL. Administrator database/server di luar aplikasi tidak dianggap mustahil mengubah storage. Jangan menjanjikan immutability terhadap DBA.

Aksi read-state notifikasi diaudit sebagai teknis dan tidak menghasilkan notifikasi lagi. Pembuatan audit/notifikasi turunan menunjuk operasi sumber tanpa rekursi. Sistem sesi/cache bukan perubahan domain yang membuat spam notifikasi.

## 7. Integritas dan akses

- Gunakan PK/FK/unique/check untuk aturan dalam satu baris; DECIMAL untuk skor/bobot/KKM. INT(3) bukan batas skor. Aturan lintas tabel dan izin tetap diperiksa aplikasi.
- Serialisasi benturan jadwal pada semester dan serialisasi perubahan semester aktif; jangan mengandalkan validasi sebelum transaksi saja. Tetapkan dan uji locking nyata pada MySQL target sebelum implementasi terkait diterima.
- Semester closed/hasil published tidak diedit lewat update biasa. D-28 koreksi sumber melalui command MA (G current hanya term terbuka); revisi rapor baru MA/W binaan, audit dan snapshot final tetap.
- Filter sebelum pagination/count pada daftar, audit dan notifikasi. Periksa kembali izin download serta perubahan relasi/role/delegasi pada permintaan berikutnya.
- Semua pembaruan profil memakai allowlist; username unik, password melalui sesi aktif + OTP email D-25 dan modal sesudah commit; foto diverifikasi dan disimpan aman. Tidak ada self-escalation dari profil.
- D-29 menggantikan asumsi zona historis: school_timezone pilihan WIB/WITA/WIT oleh Master untuk kalender dan tampilan. Nilai awal belum dipilih; timestamp UTC. Retensi akademik/audit belum diputuskan.

## 8. Tahapan dan bukti

Buat tabel bertahap sesuai fitur, bukan semua sekaligus. Akun/izin/master/audit/notifikasi dahulu, kemudian kegiatan akademik, rapor, dan perkembangan. `assignment_requests` adalah rekomendasi sempit untuk mengendalikan pemberian akses; tidak menjadi engine workflow umum.

Seluruh migration, policy tests, concurrency tests, file access, kalkulasi, audit dan notifikasi **NOT RUN** karena aplikasi belum ada. Tidak ada klaim performa, deployment, adopsi sekolah atau perlindungan database yang sudah terbukti.

Referensi kebutuhan: [PRD](PRD.md), [izin](ROLE_PERMISSION_MATRIX.md), [akses objek](RESOURCE_ACCESS_POLICY.md), [notifikasi](NOTIFICATION_REQUIREMENTS.md).

Integritas tanggal tambahan D27: perubahan enrollment tidak boleh membuat skor published yang sudah sah tiba-tiba di luar rentang keanggotaan; tolak; D-28 tidak membuka koreksi struktur/tanggal/roster final, tanpa menghapus atau menafsirkan skor lama sebagai0. Foreign-key asal assignment juga tidak boleh dipakai sebagai alasan menolak draft yang tanggal kegiatannya melewati handover. Jadwal guru pengganti dan batas waktu delegasi dievaluasi pada rentang yang relevan; konversi memakai school_timezone terkonfigurasi; perlakuan cap assignment terhadap delegasi UTC existing saat zona berubah masih OPEN.

### D-28 — integritas koreksi dan lineage rapor

Tidak menambah tabel koreksi/workflow: histori before/after beralasan memakai audit append-only yang ada. scores tetap absence-of-row untuk blank; koreksi null menghapus baris secara eksplisit dan diaudit,0 tetap angka. attendance_records finalized hanya boleh status H/I/S/A melalui command koreksi, set peserta/parent/enrollment/tanggal dibekukan. Version assessment/session naik; origin assignment/created_by tidak berubah.

Usulan metadata report_cards di atas membedakan isi immutable dari status akses. needs_revision menahan finalized/published tanpa mengubah snapshots/file; superseded menutup versi published sebelumnya ketika revisi baru terbit; revoked ditarik eksplisit. Query guardian mensyaratkan published/current aktif + relasi sah, bukan status published saja. Histori stale/revoked/superseded tetap staf-scope, bukan cascade delete. lifecycle_reason hanya alasan aman dan bukan props wali; histori penuh di audit.

Di bawah mutex akademik -> parent akademik/enrollment -> report/progression dengan urutan deterministik, koreksi memutakhirkan sumber/version dan semua snapshot terdampak per siswa secara atomik dengan audit/inbox. Token source mencakup nilai blank/numerik dan frozen status/counter yang relevan, bukan hanya final_score. Draft meminta preview baru; finalized tidak refresh snapshot. Satu revisi unfinished per enrollment/term dan satu published/current aktif diperiksa lock/constraint yang akan diuji, bukan hanya unique revision; representasi fisik conditional unique masih rancangan.

Revisi baru supersedes latest immutable pada enrollment/term sama, revision meningkat, policy/calculation asli tetap. Publish baru mengubah metadata versi sebelumnya dalam transaksi yang sama; revoked tidak diaktifkan lagi. Source yang berubah saat render/publish menahan versi dan memerlukan revisi baru; PDF lama tidak ditimpa. Progression belum applied kehilangan approval/current-source dan menjadi needs_review; applied tetap dengan proyeksi tindak lanjut dari mismatch sumber/audit, tanpa rollback enrollment otomatis. [Lifecycle](ACADEMIC_FLOW.md). Semua DDL/constraint/race NOT RUN.

Slot revisi belum selesai D-28 berarti status draft atau finalized dengan availability current; finalized/needs_revision, revoked dan superseded tidak menempati slot kandidat. Saat membuat revisi eksplisit dari latest finalized/current, atomik ubah metadata asal menjadi needs_revision + naikkan version bersama create/audit/inbox agar tidak dapat publish bersaing. Membuat draft pengganti dari published/current tanpa koreksi sumber atau revoke tidak otomatis menahan versi terbit lama; ia tetap aktif sampai supersede saat revisi baru published. Pemeriksaan satu kandidat dan satu versi published/current adalah dua invariant terpisah.

Penahanan D-28 hanya mengubah availability versi finalized/published yang masih current dan terdampak; versi revoked/superseded tetap tertutup, tidak diaktifkan atau dikembalikan ke status sebelumnya. Revoke kandidat revisi yang belum pernah published tidak mencabut versi published/current lain yang masih sah. Notice wali mengikuti perubahan ketersediaan rapor publik pada semester terkait; mutasi versi internal tidak memunculkan notice “Dicabut” bila rapor aktif wali tidak berubah.

### D-29 — school_timezone pada system_settings

Tidak menambah tabel. Key wajib school_timezone menyimpan satu string IANA allowlist Asia/Jakarta, Asia/Makassar atau Asia/Jayapura; label UI WIB/WITA/WIT. expected_version memakai versi key menurut kontrak settings yang ada, bukan tabel riwayat timezone baru. Key tidak boleh dihapus setelah dikonfigurasi; timezone browser/host bukan default. Nilai awal belum dipilih, sehingga bootstrap harus meminta konfigurasi sah sebelum akademik berjalan.

Semua timestamp kejadian tetap instant UTC, koneksi DB/runtime UTC. DATE dan TIME domain tetap literal kalender/jam sekolah; API mengirim YYYY-MM-DD/HH:mm tanpa konversi via JavaScript Date yang menggeser tanggal. Backend mengonversi instant ke school_timezone untuk tanggal efektif dan tampilan; props hanya proyeksi id/label/setting-version yang diperlukan.

Penulis setting dan operasi akademik sensitif memakai protokol mutex akademik -> key school_timezone -> parent/objek dengan urutan konsisten. Setelah lock, baca nilai/version terbaru dan periksa grant/state terhadap tanggal sekolah terbaru. Jika form/preview memakai versi lama, tolak dengan school_timezone_changed dan minta review/refresh tanpa menulis sebagian. Version setting ikut token preview sumber bila relevan, bukan izin klien.

Perubahan tidak melakukan UPDATE massal tanggal/timestamp, tidak menimpa snapshot/PDF atau menghitung ulang nilai rapor final. Timestamp UTC yang sama boleh terlihat sebagai jam/tanggal berbeda pada UI setelah refresh, dengan label zona eksplisit. Rendering ulang PDF final memakai snapshot zona/label yang dibekukan bersama snapshot laporan ketika mengandung waktu lokal, bukan setting terbaru; metadata snapshot ini dirancang tanpa mengubah file lama.

valid_from/valid_until delegasi berupa UTC tetap instant asal; tidak otomatis digeser. Handover/assignment DATE dievaluasi memakai school_timezone terkini. Rekonsiliasi cap delegasi yang sebelumnya diturunkan dari tanggal assignment ketika zona berubah tetap OPEN sebelum implementasi terkait; jangan menambahkan rebase otomatis atau menyatakan semua delegasi aman hanya karena timestamp UTC.

Versi timezone adalah precondition konteks form/preview, terpisah dari fingerprint isi snapshot rapor final. Perubahan school_timezone saja tidak menandai rapor final needs_revision, tidak mengganti source_version rekap frozen atau membatalkan progression yang sudah sah. Publish/render tetap memakai zona/label snapshot bila dicetak; request usang meminta refresh lalu memeriksa ulang grant terkini, tanpa mewajibkan revisi rapor hanya karena zona berubah.
