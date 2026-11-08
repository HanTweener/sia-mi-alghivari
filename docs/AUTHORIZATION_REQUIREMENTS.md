# Authorization requirements — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status: **PROPOSED / blueprint**; implementasi **NOT STARTED**, pengujian **NOT RUN**. Struktur template dipertahankan. Sumber grant kanonis adalah [ROLE_PERMISSION_MATRIX.md](ROLE_PERMISSION_MATRIX.md) dan [RESOURCE_ACCESS_POLICY.md](RESOURCE_ACCESS_POLICY.md), berdasarkan [PRD](PRD.md). Dokumen ini menjelaskan penerapan dan verifikasi, bukan menyalin matriks atau memberi hak baru.

## Subjects, actions, resources and denied-by-default rules

Subjek manusia: akun aktif dengan role tetap `master_admin`, `academic_admin`, `data_entry_admin`, `teacher`, `principal`, `guardian`. Wali kelas dan guru mapel berasal dari penugasan, bukan role hierarkis tambahan. Identitas worker/runtime/migrator adalah pelaksana infrastruktur dengan hak minimum, bukan akun Master pengguna.

Evaluasi server: sesi sah → akun aktif → tindakan → objek dan relasi parent → field → keadaan/periode → grant yang cocok. Tolak bila tidak ada grant eksplisit atau rincian Q6/lifecycle yang masih terbuka belum tersedia. Peran dan relasi diambil dari database tepercaya; `user_id`, `role`, `classroom_id`, pilihan konteks menu dan capability props klien tidak menjadi bukti izin.

C/R/U/D domain dibedakan dari finalisasi/publikasi, approval, dan apply. Nilai D mencakup efek hapus/arsip/pelepasan hubungan, walau HTTP memakai PATCH. `score:null` yang membersihkan skor existing memerlukan D; omitted tidak berubah dan 0 tetap angka, sesuai [API_CONTRACT](API_CONTRACT.md).

Role rangkap menggabungkan grant yang cocok untuk objek tersebut: principal+teacher menulis hanya mapel penugasannya; Data Entry+teacher bisa mendapat D/P dari hak guru pada mapel sendiri, bukan melalui delegasi draft Data Entry. Semua role tetap tunduk pada audit append-only, keadaan final, versi sumber dan integritas histori.

| Permukaan | Enforcement yang diharapkan; seluruhnya belum diterapkan |
| --- | --- |
| Halaman/JSON/query | Policy dan filter scope sebelum select/proyeksi, pencarian, pagination/count dan badge. Jangan mengirim data terlarang lalu menyembunyikannya di React. |
| Nested object | Parent/child benar-benar terhubung dan berada pada kelas/periode/anak yang diizinkan. `exists` masing-masing ID belum cukup. |
| Form dan command | Allowlist input/proyeksi; unknown/privileged field ditolak sesuai kontrak. Periksa keadaan, capability dan version pada objek yang tepat di server. |
| Bulk | Validasi seluruh item dan grant; satu objek invalid/tidak berhak menolak seluruh operasi sebelum commit. Error per baris tidak membocorkan identitas objek lain. |
| Print/file/foto | Periksa hak saat request, bukan hanya saat tautan dibuat. PDF gabungan memerlukan hak membaca keseluruhan isi; grant satu mapel tidak otomatis mencakup seluruh rapor. Storage privat, path bukan input tepercaya. |
| Audit/inbox | Pembaca saat ini harus masih berhak atas event/field/sumber; inbox milik sendiri saja belum cukup bila scope sumber dicabut. Event draft historis tidak berubah menjadi publik ketika assessment dipublikasikan. |
| Queue/channel | Actor/penerima/sumber berasal dari konteks server; periksa ulang saat job dijalankan. Subscribe hanya channel akun sesi sendiri; hint minimal, fetch tetap memakai policy. Job tidak menyamar menjadi guru pemberi delegasi. |

Usulan Laravel Policies, scoped query dan action dipakai bersama oleh route/worker, mengikuti [arsitektur](ARCHITECTURE.md). Tidak menggunakan bypass `before` global yang membuat Master melewati state, field rahasia, audit atau persetujuan principal. Model mass-assignment protection bukan satu-satunya kontrol field, dan UUID/ID sulit ditebak bukan pengganti policy.

## Business ownership and account administration boundaries

- **Master:** hanya Master mengubah role/jabatan kepala sekolah dan konfigurasi sistem. CRUD domain tidak memberi hak membaca password/hash/token, menulis audit manual, mengubah snapshot final atau memberi approval naik/lulus tanpa role principal.
- **Akademik:** konfigurasi/operasi akademik dan penugasan sesuai matriks. Tidak otomatis mengelola akun admin lain atau memberi role. Detail field profesional staf tetap Q6; urutan onboarding wali mengikuti keputusan Q6.1.
- **Data Entry:** C/U operasional yang sudah disebut pemilik; setelah Master membuat akun wali beserta role dan aktivasi, melengkapi biodata serta menghubungkannya ke siswa sesuai Q6.1 dan field berhak. Tanpa hapus, konfigurasi, aktivasi hak sendiri, publish atau approval. Input nilai/absensi hanya draft dalam delegasi aktif dengan scope/waktu/capability; Q6.3 menetapkan Master/Akademik sebagai pemberi dan pencabut. Mekanisme permintaan/perubahan delegasi dan aktivasi usulan penugasan masih terbuka.
- **Guru mapel/wali kelas:** penugasan aktif menentukan siswa/kelas/periode. Wali kelas membaca perkembangan binaan, boleh mengisi catatan opsional draft rapor binaan (Q7.1), serta finalisasi/publikasi rapor lengkap binaan, tanpa mengedit nilai guru lain. Field catatan dibekukan saat finalized; role lain tidak mendapat grant tulis tambahan melalui format. Penugasan inactive tidak memberi hak aktif karena pernah menjabat.
- **Principal:** baca domain/field yang diizinkan dan approve/reject naik/lulus sebagai pengecualian; bukan hak apply, konfigurasi, input nilai atau publish dari jabatan itu sendiri. Profil sendiri dan grant role/penugasan lain tetap berlaku.
- **Wali:** hanya anak yang terhubung serta kontak profesional wali kelas yang diizinkan. Perkembangan published/finalized tidak menunggu rapor; rapor hanya published/current aktif. Tidak ada akses isi ke draft/rapor finalized belum published, versi needs_revision/revoked/superseded, akun admin atau biodata keluarga/staf lain.
- **Profil sendiri:** identitas objek adalah user sesi dan field allowlist. Mengubah alamat/username tidak mengubah relasi anak/role/assignment/is_active. Email kontak belum merupakan alamat recovery terverifikasi; jangan mengangkatnya menjadi bukti kepemilikan akun.

Relasi domain bukan kepemilikan credential. Q6.1 M akun wali dahulu/D biodata/link tetap. D-25 Master-only akun/email verified administratif/temporary emailed; pengguna hanya credential dirinya dengan OTP/proof yang tepat. Restricted login/proof/modal tidak memberi hak domain/role/anak; objek/field server tetap. Password/OTP/hash tidak dibaca ulang lewat administrasi. Existing tambah role tidak direset; fallback/bootstrap Master terakhir OPEN. [Lifecycle](RECOVERY_AND_STEPUP_PLAN.md).

## Delegated provider grant separate from app policy

**N/A:** tidak ada OAuth login/provider API connect pada lingkup ini. GitHub Actions/GHCR, Cloudflare dan registry adalah layanan deployment/operator, bukan grant login pengguna sekolah.

Delegasi input Data Entry adalah grant internal berbasis `data_entry_delegations`, bukan OAuth consent atau impersonation. Q6.3 mengizinkan pemberian/pencabutan hanya oleh Master/Akademik; Data Entry tidak memperoleh hak ini dari role inputnya. Periksa `user_id`, teaching assignment, capability, masa berlaku/revoked dan keadaan draft di server; perubahan delegasi juga memiliki audit domain. Audit input mencatat petugas asli, bukan guru pengganti. Mekanisme permintaan, detail perubahan/DTO dan aktivasi usulan penugasan yang belum disepakati tetap terbuka; tidak ditutup oleh keputusan aktor ini.

## Identity lifecycle and revocation consistency

[SESSION_TOKEN_SECURITY_SPEC](SESSION_TOKEN_SECURITY_SPEC.md) mengatur keabsahan sesi; policy ini mengatur apa yang boleh dilakukan sesi tersebut. Akun nonaktif ditolak pada request berikutnya. Role, hubungan anak, assignment dan delegasi dibaca ulang; tidak menyimpan grant tetap dalam session/props/cache tanpa invalidasi. Tidak diperlukan logout seluruh akun hanya karena satu relasi anak dicabut; scope lain yang sah boleh tetap tersedia.

Untuk mutasi sensitif, validasi grant/state/version dilakukan kembali dalam protokol transaksi/lock yang sama dengan perubahan. Pencabutan dan penggunaan grant harus memakai urutan locking yang konsisten agar hasil konkuren mempunyai urutan jelas: bila revoke commit dahulu, mutasi kemudian ditolak; bila mutasi sah commit dahulu, audit menunjukkan urutannya sebelum revoke. Hindari deadlock dengan urutan lock tetap; gunakan mutex akademik yang sudah diusulkan bila relevan, bukan mengklaim session lock menyelesaikan race domain.

Strategi fisik lock untuk role/relasi/delegasi dan credential/session revocation belum diimplementasikan; harus ditetapkan dan diuji pada engine target sebelum invariant tersebut diterima. Pemeriksaan middleware saja tidak cukup jika grant dapat dicabut setelah pengecekan namun sebelum write. Jangan memegang lock selama render PDF atau pengiriman jaringan.

Read/list/download sesudah pencabutan menilai grant terkini pada awal operasi. Data/byte yang sudah disampaikan sebelum revoke tidak dapat ditarik kembali; tidak menjanjikan pembatalan retroaktif request/stream yang telah berjalan. Socket lama juga tidak otomatis berhenti: worker recheck dan hint minimal membatasi paparan, fetch berikutnya ditolak. Batas ini harus diuji dan dicatat, bukan disebut pencabutan socket instan.

Usulan step-up password pada operasi sensitif tidak mengesahkan objek yang sebelumnya tidak berhak. Konfirmasi harus terikat akun/sesi sekarang dan memiliki masa berlaku server; setelah perubahan credential/recovery/logout, bukti lama tidak berlaku. Rincian dan bagian terbukanya pada [RECOVERY_AND_STEPUP_PLAN](RECOVERY_AND_STEPUP_PLAN.md).

## Audit requirements and release blockers

Semua CUD domain berhasil, termasuk profil/password/role, mencatat audit sistem per objek dalam transaksi yang sama dengan bisnis dan inbox penerima yang berhak. Tidak ada route CUD audit untuk pengguna termasuk Master; akun runtime tidak mendapat UPDATE/DELETE audit atau DDL. Jejak historis tidak terhapus bersama objek. Diff tidak memuat password/hash/token/session payload; credential change cukup metadata aman.

Login gagal, rate-limit dan penolakan izin masuk log keamanan teknis yang dibatasi akses/retensi, bukan audit perubahan domain yang seolah berhasil. Mutasi sesi/cache/job framework tidak menghasilkan audit/notifikasi domain rekursif. Tinjau minimisasi ID/konteks; jangan mencatat body request atau biodata yang ditolak. Skema log/retensi akhir masih rancangan, bukan layanan telemetry baru.

| ID | Hasil yang diharapkan, terkait CT/AV/DBV yang sudah ada | Status |
| --- | --- | --- |
| AZ-01 | Guest/nonaktif, nested ID lintas anak/kelas dan list/count terlarang ditolak tanpa isi atau identitas bocor; filter diterapkan sebelum pagination. | NOT RUN |
| AZ-02 | Role rangkap hanya menggabungkan grant yang cocok; principal tanpa teaching tidak menulis nilai, Master tanpa principal tidak approve, W hanya publish binaan. | NOT RUN |
| AZ-03 | Overposting profil/akun, aktivasi assignment sendiri dan PATCH yang berefek hapus oleh D ditolak atomik; D+G hanya menggunakan hak guru pada assignment sendiri. | NOT RUN |
| AZ-04 | Wali melihat perkembangan tanpa rapor; snapshot/PDF hanya published/current aktif anak. Draft/finalized/needs_revision/revoked/superseded dan count/detail internal tertutup; notice minimal hanya semester pernah terbit. Audit/inbox/PDF gabungan tetap scoped. | NOT RUN |
| AZ-05 | Relasi/role/delegasi dicabut saat form terbuka atau race write: hasil mengikuti urutan commit yang sah; tidak ada grant lama dari props/cache/channel. | NOT RUN |
| AZ-06 | Batch dengan satu item terlarang tidak mengubah item lain; audit/inbox gagal membatalkan bisnis; pengguna/SQL runtime tidak dapat memodifikasi audit. | NOT RUN |

Tidak adanya bukti untuk invariant kritis menghalangi penerimaan implementasi/rilis terkait. Kebocoran anak/kelas, privilege escalation, bypass principal, audit hilang atau snapshot rusak harus diperbaiki dan diuji ulang; bukan diselesaikan dengan menandai tes N/A. sisa Q6/visual Q7 dan lifecycle lain yang belum dipilih adalah keputusan belum selesai, bukan izin default.

Rujukan verifikasi: [kontrak API/CT](API_CONTRACT.md), [AV](ARCHITECTURE_REVIEW.md), [DBV](DATABASE_VERIFICATION_REPORT.md) dan [rencana kualitas](CODE_QUALITY_PLAN.md). Laporan security/identity faktual diisi setelah pengujian; review dokumentasi tidak menjadi sertifikasi keamanan. Referensi primer diperiksa 28 Oktober 2026: [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html), sebagai pedoman yang disesuaikan dengan satu sekolah, bukan klaim compliance.

D26/D27: otorisasi assessment/session memakai assignment efektif current pada class_subject, bukan creator/source assignment historis. Guru baru dapat draft lama tanpa mengubah provenance; guru lama tanpa grant lain ditolak pada list/count/detail/write/job/channel. D hanya delegasi efektif current parent/capability, old delegation tidak terbawa, tidak D/P. G satu mapel tetap tidak membuka PDF seluruh mapel. Perubahan enrollment/membership tidak memalsukan peserta final atau memberi transfer role. Uji race handover/revoke terhadap simpan draft, dengan urutan commit konsisten.

D-28: [matriks izin](ROLE_PERMISSION_MATRIX.md) menetapkan MA koreksi sumber sah termasuk term closed, G current hanya term terbuka; W/D/P tanpa grant tambahan tidak boleh. MA/W binaan aktif revisi/revoke termasuk closed. Guardian detail/PDF hanya published/current aktif, notice semester pernah terbit tanpa snapshot/reason internal. Command dan grant/state/version dicek saat commit; report/source provenance bukan grant historis. AZ-04/06 serta API-T55..57 menguji ini, seluruhnya NOT RUN.
