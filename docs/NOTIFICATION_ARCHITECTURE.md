# Notification Architecture — SIA MI ALGHIVARI

Diperbarui: 29 Oktober 2026. Status **PROPOSED / blueprint**; Reverb/Echo telah dipilih pada tingkat rancangan. Backend notifikasi **NOT IMPLEMENTED**, seluruh hasil uji server/antrean **NOT RUN**. Prototipe [facelift-v2](support/facelift-v2/README.md) hanya referensi tampilan dengan fixture di memori, bukan bukti transaksi, otorisasi, persistensi atau pengiriman backend.

## Decision

Gunakan inbox persisten pada database aplikasi, Laravel Broadcasting/Reverb, Echo di React dan satu database queue dengan worker. App/queue/Reverb adalah proses Compose terpisah pada versi image yang sama. [Kebutuhan](NOTIFICATION_REQUIREMENTS.md), [matriks penerima](NOTIFICATION_MATRIX.md), [model data](DATA_MODEL.md) dan [stack](TECH_STACK.md) menjadi sumber kanonis; pilihan backend/engine/paket yang belum final tetap mengikuti statusnya.

Inbox memenuhi kebutuhan membaca riwayat saat socket gagal; WebSocket mempercepat petunjuk pembaruan. Setiap C/U/D domain mengevaluasi penerima, bukan broadcast ke seluruh akun. Jangan menambah Redis, outbox, broker, custom DLQ, delivery-service atau engine preferensi. D-25 menambah email khusus akses/password, terpisah dari inbox/Reverb. SMS/WhatsApp/browser push, consent pemasaran, jadwal dan quiet hours tetap di luar core.

Usulan ini memakai kemampuan framework yang diperlukan, payload kecil dan kegagalan yang terlihat sesuai panduan Anti Slop/Ponytail. Referensi visual Taste/facelift membantu hierarki inbox; tidak menentukan grant, event atau jaminan pengiriman baru. Biaya/kapasitas/latensi belum diukur.

## Event lifecycle

1. Action domain memvalidasi aktor, objek, field, state dan versi; memilih kandidat penerima menurut matriks dan visibilitas event yang akan disimpan.
2. Dalam satu transaksi: tulis bisnis, audit tiap objek dan inbox terkelompok per penerima/kategori/cakupan. Satu kegagalan membatalkan seluruh operasi/batch; tidak ada inbox atau broadcast untuk perubahan yang rollback.
3. Commit dahulu; kemudian dispatch sinyal melalui database queue. Worker membaca inbox yang telah commit, memeriksa akun/penerima/scope kembali dan mengirim hint minimal ke private channel pengguna yang sah.
4. Echo menerima hint dan mengambil ulang daftar/count melalui HTTP berizin. Membuka inbox, refresh atau reconnect melakukan catch-up dari inbox persisten; tidak replay mutasi domain.
5. Pengguna boleh menandai inbox sendiri dibaca. Perubahan read-state dicatat sebagai event teknis aman tanpa membuat notifikasi baru; audit/inbox turunan tidak membangkitkan rekursi.

Commit DB dan enqueue setelahnya bukan satu transaksi. Crash sebelum enqueue dapat menghilangkan sinyal live; job yang sudah terantre pun dapat gagal/retry. Data bisnis/inbox yang commit tetap sah dan dapat dibaca kembali; jangan mengembalikan simpan gagal hanya karena socket gagal. Tidak menjanjikan exactly-once, urutan broadcast, penerimaan pengguna atau tidak ada sinyal hilang. Pilih fasilitas after-commit pada framework yang benar-benar dipasang; lihat [Broadcasting dan transaksi](https://laravel.com/framework/docs/13.x/broadcasting#broadcasting-and-database-transactions).

## Data contracts

| Data | Kontrak minimum |
| --- | --- |
| Audit sumber | Actor asli, target/action, scope historis stabil, visibility, batch/alasan dan diff aman sesuai DATA_MODEL. Setiap objek batch memiliki audit; draft tetap internal meski sumber kemudian dipublikasikan. |
| Inbox | `notifications`: recipient_id, source_audit_id, batch_id bila ada, kategori/scope, ringkasan aman, jumlah objek yang berhak, read_at dan waktu server. Bukan salinan seluruh model atau native Notification Laravel yang dianggap identik dengan schema proyek. |
| Identitas operasi/dedup | Unique logis mencakup operasi yang sama, penerima, kategori dan scope. Representasi fisik mengikuti rancangan model sebelum migration; satu batch tidak menghasilkan inbox ulang untuk penerima/cakupan sama. |
| Job/hint | Job merujuk ID inbox minimum; bukan payload kredensial/izin lama atau model bisnis yang harus masih hidup untuk deserialize. Event `inbox.changed.v1` berisi notification_id/type, tanpa nilai/biodata/jumlah batch umum. |
| Sumber diarsipkan/dihapus | Scope/visibility pada audit/inbox tetap dapat dievaluasi tanpa row target aktif. Gunakan konteks historis minimal sebagai tombstone; hubungan/peran pembaca saat ini tetap menentukan apakah ringkasan netral boleh terlihat. Tidak membuat tabel tombstone baru atau cascade audit. |

Identitas operasi dipertahankan untuk replay operasi/batch yang memang sama; kategori/scope berbeda tidak digabung hanya karena berdekatan waktunya. `request_id` adalah korelasi, bukan identitas dedup global. Version/state/constraint bisnis mengendalikan command kritis; POST create tanpa natural unique yang timeout tetap hasil belum pasti, tanpa auto-retry atau jaminan Idempotency-Key generik. [API_CONTRACT](API_CONTRACT.md).

Inbox menyimpan fakta peristiwa, tetapi bukan grant permanen. Query daftar/badge/detail memeriksa current policy, field dan visibilitas sebelum count/pagination; target yang tak lagi tersedia tidak membocorkan data lama. Urutan daftar stabil memakai waktu server dan ID pembeda; hint terlambat/duplikat hanya memicu pembacaan keadaan terkini, tidak menulis ulang isi atau menaikkan badge lokal. Hindari respons fetch konteks lama menimpa konteks/hasil baru. Beberapa request daftar/count tidak diklaim satu snapshot atomik lintas HTTP.

## APIs/clients

- HTTP-29: GET /inbox untuk halaman; HTTP-30: GET /notifications, /notifications/unread-count dan /notifications/{notification} memakai JSON sesi dengan proyeksi/count scoped.
- HTTP-31: PATCH /notifications/{notification}/read-state, `read=true`; set read_at server sekali. No-op pengulangan tidak mengganti waktu/membuat audit baru. Tidak menambah mark-unread, mark-all-read atau endpoint CUD isi/penerima.
- HTTP-32: POST /broadcasting/auth oleh Echo; sesi aktif, channel pengguna sendiri dan adapter framework menentukan respons. Tidak menerima recipient/role dari form sebagai grant.
- Channel server usulan `users.{user_id}` private, bentuk wire mengikuti adapter. Event hanya hint; badge berasal dari server. Reconnect/open inbox fetch ulang dengan pagination yang sudah dirancang, bukan cursor/protokol replay baru.

Shell mengikuti [kontrak layar SCR-11](SCREENS/SCREEN_SPEC_TEMPLATE.md) dan [copy](CONTENT_STYLE.md): koneksi gagal berbeda dari inbox kosong; pembaruan tidak merebut fokus. Klien membuang state akun/anak lama saat logout/pergantian konteks dan ketika pencabutan diketahui. Koneksi lama tidak otomatis terputus akibat revoke; hint yang sudah terbang tidak dapat ditarik kembali, sementara fetch berikutnya tetap berizin.

## Safety

Sesi/policy berlaku pada subscription, dispatch, daftar, jumlah, read-state dan tujuan. Master tidak dapat mengubah inbox pengguna lain; draft nilai tidak sampai ke wali melalui ringkasan/count. Input batch menghasilkan proyeksi per penerima, bukan satu payload umum yang disembunyikan React. Tidak ada token, cookie/session ID, password/hash, alamat pribadi atau skor rinci pada hint/log.

Gunakan WSS pada demo, origin aplikasi yang diizinkan dan secret Reverb hanya server. Reverb app key publik bukan bukti login. Perlindungan forgery mengikuti middleware aktual; jangan mengklaim default Laravel 13 /broadcasting/auth selalu CSRF-protected karena ada pengecualian framework yang dibahas pada [sesi](SESSION_TOKEN_SECURITY_SPEC.md) dan API. Tidak menambah JWT, wildcard origin atau bypass policy untuk membuat socket berhasil.

Inbox tidak menjadi pengiriman kredensial/kanal recovery atau satu-satunya peringatan kompromi. Link tujuan berupa route aplikasi allowlist dan diperiksa ulang; event delete boleh ringkasan netral hanya bila scope historis/current masih sah. Revisi/revoke rapor dan koreksi aktivitas mengikuti lifecycle yang disepakati, bukan fitur baru dari notifikasi.

## Scaling and operations

Compose menjaga proses app/queue/Reverb dan volume DB persisten; HTTP serta worker mengakses DB sama per lingkungan, dev/CI/demo terisolasi. Deploy memakai digest kompatibel dan restart terkendali agar worker tidak memakai kode lama; tidak menghapus inbox/jobs/failed_jobs sebagai cara membersihkan rilis. [Rencana deployment](DEPLOYMENT_PLAN.md).

Sebelum aktivasi, tetapkan timeout jaringan/job, retry_after, attempts, backoff dan umur maksimum hint yang semuanya terbatas. Timeout worker harus lebih pendek daripada retry_after dengan margin yang diuji, agar pekerjaan lama dihentikan sebelum diproses ulang. Strategi batas jumlah+umur belum dipilih: Laravel memberi precedence retryUntil terhadap tries; jangan menganggap memasang keduanya otomatis menegakkan dua batas. Nilai dan penerapannya **TERBUKA**, bukan default yang telah disetujui. [Laravel Queues](https://laravel.com/framework/docs/13.x/queues#job-expirations-and-timeouts).

Retry hanya hint job yang aman, tanpa membuat inbox/audit/domain lagi; job memeriksa current scope saat setiap eksekusi. Permanen tidak berhak tidak diretry menjadi eskalasi. Jika layanan/DB gagal sementara, retry terbatas; job terminal yang gagal tercatat melalui failed_jobs framework dan ditinjau sebelum retry manual. Hint terlalu tua tidak dikirim ulang membabi buta; inbox tetap dapat dibaca. Tidak membangun DLQ/operator UI baru.

Pantau error dispatch, jumlah/umur jobs, kegagalan worker/Reverb, lock/koneksi DB dan disk. Hipotesis: DB queue berbagi kapasitas dengan input akademik dan burst setelah reconnect dapat menambah beban; ukur dengan fixture sebelum menambah worker/broker. Target latensi, polling cadangan, jumlah worker, retensi inbox/failed_jobs dan alert belum diputuskan. Tidak membuat SLA24/7 untuk host demo yang dapat mati/tidur.

| ID | Hasil yang diharapkan / hipotesis yang perlu dibuktikan | Rujukan risiko | Aktual |
| --- | --- | --- | --- |
| NAR-01 | Gagal bisnis/audit/inbox membatalkan seluruh batch dan tidak menyiarkan rollback; operasi valid memberi audit per objek/inbox per grup. | transaction-rollback; DBV-13 | NOT RUN |
| NAR-02 | Replay job/command sah tidak menggandakan inbox/domain; create timeout tak pasti tidak auto-retry; hint duplikat/terlambat hanya refetch. | duplicate-write, queue-replay, stale-version; CT-06/11 | NOT RUN |
| NAR-03 | Anak/kelas/channel/inbox orang lain dan draft terlarang ditolak; revoke membatasi count/detail/tujuan serta tombstone event. | auth-boundary, cache-isolation-staleness; CT-03/10 | NOT RUN |
| NAR-04 | Worker/Reverb/dispatch setelah commit gagal tidak menghilangkan inbox atau membatalkan bisnis; reconnect membaca terkini tanpa janji live delivery. | downstream-timeout, queue-replay; DBV-21 | NOT RUN |
| NAR-05 | Timeout/retry/umur terbatas, DB contention dan restart/restore menjaga inbox/current scope; restore tidak merilis hint lama tanpa pemeriksaan. | db-pool-exhaustion, restore-consistency; DBV-22/24/25 | NOT RUN |
| NAR-06 | Mark-read tanpa rekursi, link rapor tidak membuka file belum published, dan ringkasan tidak membawa credential/data yang dilarang. | file-publication, auth-boundary; CT-07/08/10 | NOT RUN |

ID risiko merujuk [BACKEND_FAILURE_MODE_PLAN](BACKEND_FAILURE_MODE_PLAN.md). Gunakan kembali CT/DBV/AV dan [rencana tes notifikasi](NOTIFICATION_TEST_PLAN.md) saat dirinci; NAR bukan enam suite tambahan. Belum ada command, output job, layanan hidup atau hasil benchmark backend yang dicatat.

## Dependencies

Laravel Broadcasting/Reverb, Echo, driver database queue dan library/rilis sesuai [TECH_STACK](TECH_STACK.md); konfigurasi berasal dari environment per lingkungan. Browser HTTP/WSS melalui Tunnel bergantung app/DB/worker/Reverb/cloudflared/host aktif; domain/DNS/TLS dan kesiapan layanan belum diverifikasi oleh dokumen ini. Email auth D-25 memerlukan SMTP/API provider yang belum dipilih, konfigurasi secret/runtime/biaya diverifikasi sebelum aktivasi; tidak ada provider diprovisi. Push/SMS/WhatsApp tetap tidak dipilih.

Rujukan framework diperiksa 29 Oktober 2026; penerapan tetap harus diverifikasi terhadap lockfile/runtime yang kelak dipilih. Prototipe tidak mengaktifkan server notifikasi. Tugas ini hanya dokumentasi; tidak menginstal paket, mengirim notifikasi nyata, menjalankan worker atau mengubah konfigurasi/deployment.

D-28: hold/revoke/supersede adalah mutasi domain metadata, bukan efek worker. Source correction + hold per siswa + audit/inbox commit bersama; Reverb mati tidak boleh menunda penolakan PDF. Published revised memberi tautan ke versi aktif saja; notif tertunda harus fetch ulang state, tidak menghidupkan URL lama. Scope/jumlah per penerima, alasan internal dan data lama tidak dikirim wali; finalized belum pernah terbit tetap internal. N-T15/API-T55..57 masih NOT RUN.
