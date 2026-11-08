# Load / Performance Test Plan - SIA MI Alghivari

Revisi: 29 Oktober 2026. Status PROPOSED blueprint; aplikasi inti NOT STARTED, seluruh load/benchmark/fault/restore NOT RUN. Owner pelaksana/operator dan reviewer belum ditunjuk. Dokumen mengikuti [PERFORMANCE_BUDGET](PERFORMANCE_BUDGET.md), [TEST_STRATEGY](TEST_STRATEGY.md), [TEST_PLAN T-19/20](TEST_PLAN.md), [TEST_DATA_PLAN](TEST_DATA_PLAN.md), [SLO_SLI](SLO_SLI.md) serta [failure plan](BACKEND_FAILURE_MODE_PLAN.md).

## Target revision and safe authorized environment

SHA/image digest/runtime/DB version, command, report path dan spesifikasi host belum tersedia. Saat implementasi, pin revisi aplikasi/worker/Reverb, engine target, CPU/RAM/container limits/disk, OS/WSL/Docker, browser, tool version, clock/locale, network profile dan kegiatan host. Jalankan mode release-like dengan aset terkompilasi/debug mati; Vite dev/prototipe bukan baseline backend.

Target awal localhost pada resource disposable yang terverifikasi, data sintetis, credential/session/DB/storage/queue terpisah dari dev/demo aktif. Validasi host/base URL/database/volume/manifest run sebelum seed, injeksi atau teardown; target tidak dikenal berarti berhenti. Uji Tunnel/WSS publik hanya sebagai percobaan terpisah dengan cakupan, resource dan otorisasi operasi yang sesuai. Tidak menjalankan load terhadap layanan gratis/domain orang lain dari dokumen ini.

Kandidat satu load harness adalah k6 atau alat HTTP ekuivalen yang sudah dipilih saat bootstrap; tidak menginstal dua alat untuk fungsi sama. Harness harus menangani sesi/CSRF/origin/Inertia redirect atau JSON sesuai [API_CONTRACT](API_CONTRACT.md), bukan menganggap POST200 sebagai mutasi berhasil. Playwright melengkapi browser/aksi interaktif/realtime, bukan generator throughput utama. Database/queue/Reverb nyata diperlukan untuk hasil batas layanan; fault doubles diberi label.

Gunakan manifest seed/cleanup actual dan deadline run; cleanup hanya resource milik run setelah evidence aman tersimpan. Berkas tes/harness/config belum dibuat dan tidak ada command yang diklaim dapat dijalankan sekarang.

## Scenarios

Seluruh volume/durasi/beban usulan pilot, bukan kapasitas sekolah. Baseline load boleh memakai anggota tetap; skenario fungsional D26/27 handover/membership/frozen roster kini mempunyai aturan dan diperiksa dahulu melalui DBV27/28. Detail Q6/visual/keamanan dan parameter kapasitas yang masih terbuka diselesaikan untuk jalur terkait; Q7.1-Q7.5 serta lifecycle koreksi/revisi/pencabutan rapor D-28 telah dipilih dan perlu bukti implementasi; tidak menciptakan angka hasil ukur.

Dataset yang diusulkan:

- D0 smoke fungsional: satu kelas berisi 25 siswa, dua mapel, satu term, role fixture FIX-01/02, aktivitas draft/published/finalized, rapor dan relasi wali sintetis. Pastikan expected/policy benar sebelum menambah beban.
- D1 volume pilot: 300 siswa, 12 kelas berisi 25 siswa per term, 10 mapel per kelas, dua term satu tahun dengan histori terpisah dan satu term aktif. Ada 240 teaching assignments; 10 assessment per assignment menghasilkan 2.400 assessment dan 60.000 slot skor. Sebanyak 10% blank yang terdistribusi deterministik menghasilkan 54.000 skor numerik termasuk nilai 0. Sebanyak 20 sesi finalized per assignment menghasilkan 4.800 sesi dan 120.000 attendance records; tambahan draft dicatat terpisah. Rapor contoh terdiri dari 600 snapshot, 6.000 item dan file privat terkait; hanya versi sah yang published. Guru/wali/akun, audit/inbox, bytes/halaman PDF dan rasio unread dituliskan dalam manifest aktual, bukan hasil generate yang diklaim sudah ada.
- D2 eksplorasi ukuran: variasi D1 pada kedalaman histori atau jumlah kelas, satu variabel setiap kali; ukuran dan batas resource dipilih setelah baseline. Tidak ada kapasitas maksimum atau janji melayani300/600 pengguna simultan dari jumlah row D1.

Beban closed-model P1/P5/P10 berarti1/5/10 virtual user yang menunggu respons lalu think time usulan3-8 detik dengan seed tercatat. Beban bukan arrival rate tetap; ukur throughput aktual, response time dan error. Jika meneliti arrival model kelak, catat target/achieved rate dan dropped iterations agar generator jenuh tidak menyembunyikan saturasi.

Urutan setiap run pilot: health dan correctness D0 → warm-up1 menit → P1 selama3 menit → ramp30 detik keP5 selama5 menit → ramp30 detik keP10 selama5 menit → hentikan arrival lalu ukur drain. Tiga run independen diusulkan untuk variasi; reset dataset/sesi/queue sesuai manifest, pisahkan cache warm/cold, jangan melakukan tiga run paralel pada host sama. Angka durasi bukan kewajiban mengabaikan stop condition. Minimal100 eligible operasi per journey/kondisi untuk pembandingan p95; jika mix belum mencukupi, lakukan run fokus lebih panjang dengan batas yang disepakati atau laporkan INCONCLUSIVE. Jangan menggabungkan P1/P5/P10 hanya demi jumlah sampel.

Mix awal untuk run umum:50% baca authorized register/perkembangan,20% simpan roster draft nilai/absensi,20% inbox/count/read-state dan10% baca daftar/detail rapor published. Persentase adalah alokasi operasi pengguna, bukan hitungan tiap subrequest redirect/count. Login sekali per virtual user; login/logout/throttling dan same-object writes diuji pada skenario tersendiri. Writer umum memakai objek berbeda untuk membedakan baseline dari contention; tidak mempublikasikan/menyetujui ulang data yang telah final demi menghasilkan request sukses palsu.

| ID | Workload dan payload pilot | Expected/hipotesis | Ukuran dan batas evaluasi |
| --- | --- | --- | --- |
| LT-01 | D0 lalu D1, P1/P5/P10, daftar/perkembangan/rapor/inbox dengan pagination sesuai kontrak | PB-01/05/06; izin sebelum query/count, published/finalized benar, waktu tidak tumbuh karena N+1/props seluruh histori | N/route/kondisi, throughput, timing/serverSQL/bytes; PB-01≤1s, catch-up≤3s usulan, bukan hasil |
| LT-02 | Simpan satu roster25 siswa pada objek berbeda; numeric/blank/0, satu mutasi per akun saat itu | PB-02≤2s usulan; domain+audit per objek+inboxgroup tetap atomik, versi benar; database queue berbagi DB tak mengubah hasil | Assertion row/diff/count dan commit correlation, conflict sah dipisahkan;0 pelanggaran akses/integritas |
| LT-03 | Dua writer terkoordinasi pada assessment/session sama, aktivasi policy, finalize vs sumber; batch proposal5 item | Sesuai BF-01/07/08 dan T-15: satu sumber konsisten, stale ditolak, no silent overwrite/partial; bukan error operasional yang disembunyikan | Dua koneksi nyata, lock/deadlock/response dan DB effects; batch5 hanya contoh beban, bukan batas API final |
| LT-04 | Preview/finalize satu siswa lengkap dan prepare PDF sequential; unduh file ready dengan ukuran aktual | PB-03≤2s/PB-04≤10s usulan; lock tak menunggu renderer, snapshot/file sah, finalize belum published | Satu render aktif; pisahkan cold font/render/storage/queue wait bila renderer async dipilih; susunan Q7.1 disepakati; aturan angka Q7.2-Q7.5 diterima; detail visual terbuka, fixture catatan kosong/panjang dan sesi per mapel; Dompdf/wrapper 3.1.x dipilih pada D-17, render NOT RUN |
| LT-05 | Inbox + worker/Reverb/Echo aktif, hint batch dan reconnect satu/multiuser | PB-07≥95% eligible queued-path≤5s; catch-up PB-06≤3s; current grants dan dedup benar | Timestamp server/client sinkron atau metode elapsed terkalibrasi, queue age, lost/late/duplicate, no badge increment per hint |
| LT-06 | Worker/Reverb mati/lambat, gap commit-beforeenqueue, DB/lock/disk pressure terkendali; revoke saat job menunggu | BF-03/04/06: inbox commit tetap, failed/timeout bukan sukses palsu; fetch fallback memulihkan tanpa replay domain | Injeksi terisolasi dengan deadline, p95 baseline tidak dicampur fault run; no job promise untuk gap |
| LT-07 | Batas pagination/roster/body/batch/upload/rate setelah nilai dipilih, input valid/invalid dan foreign ID | CT-02/03/04/12;422/413/429 atau403/404 sesuai kontrak, error aman dan tidak membuat domain/audit perubahan berhasil | Tepat bawah/pada/atas batas konfigurasi actual; tanpa angka hard cap rekaan atau JWT/provider tests |
| LT-08 | Browser register/absensi/perkembangan/rapor pada360×800 dan1440×900, keyboard/error states | PB-08: usable≤3s dan local feedback≤200ms usulan; tidak memadatkan DOM/source atau mengorbankan fokus demi cepat | Network/CPU tetap, interaksi/cold/warm dicatat, Playwright/manual; bukan field INP/CWV |
| LT-09 | Backup saat jendela tanpa writer, restore/recreate isolated dan ulang smoke/baseline | DBV-24/25, T-19 dan [DATA_RECOVERY_PLAN](DATA_RECOVERY_PLAN.md): data/file/job konsisten dan layanan terverifikasi; biaya freeze/backup diukur | Tidak load serentak dengan backup konsisten awal; record waktu freeze, resource backup, restore dan replay review, actual NOT RUN |

## Bottleneck hypotheses and instrumentation

Hipotesis berikut belum terbukti, bukan temuan audit. Instrumentasi awal cukup timer/log teredaksi, query plan dan counter DB/container/browser yang benar-benar tersedia; tidak menambah APM/Prometheus/provider baru secara otomatis.

| ID | Hipotesis hambatan | Sinyal pembuktian/penolakan | Perbaikan minimum bila terbukti |
| --- | --- | --- | --- |
| BH-01 | List/progress melakukan N+1 atau menghitung seluruh histori sebelum scope/pagination | SQL count/rows examined/EXPLAIN per AP-02/03/06/07/08; bandingkan ukuran roster/history, periksa payload/count authorized | Query scoped/bounded, eager load/aggregate yang tepat; indeks hanya setelah plan aktual |
| BH-02 | Mutex akademik/versi memanjangkan transaksi saat bulk/finalize/policy | Lock wait/deadlock/transaction duration dan dua writer; bedakan serialisasi yang menjaga integritas dari lock tak perlu | Perpendek transaksi, urut lock konsisten, render/network di luar lock; jangan menghapus validasi/source consistency |
| BH-03 | Queue database dan request bersaing koneksi/IO saat batch menghasilkan inbox | Koneksi aktif/wait, enqueue delay/job age, CPU/disk, jumlah recipient/audit/inbox per operasi | Group inbox sesuai desain, bounded batch/worker dan query plan; tidak menambah pool/broker/Redis sebelum bukti |
| BH-04 | PDF/font/storage memakan CPU/memori/disk dan menahan request lain | Timing render/write, peak RSS, bytes/halaman, file checksum, concurrent read latency | Satu render awal, aset/font lokal terpilih dan template wajar; jangan render ulang GET atau cache file tanpa izin |
| BH-05 | React register/props/aset terlalu besar atau interaction render berulang | Network transfer/parse, browser trace, rerender/DOM dan input feedback pada roster25 | Props minimum scoped, komponen/shadcn reuse, optimasi terukur; virtualisasi/dependency hanya bila perlu |
| BH-06 | Host tidur, Docker/WSL, internet/Tunnel mendominasi latensi dan availability | Local versus Tunnel measurements, timestamps host sleep/disconnect/restart; bandingkan CPU/network | Tandai penyebab dan jendela layanan; konfigurasi operasi wajar atau hosting lain jika kebutuhan berubah, tanpa mengklaim24/7 |

## Exit criteria and validity

Sebelum run, setujui parameter pilot/target, manifest resource dan guardrail menurut host; sebelum itu status tetap rencana. Usulan batas generator10 VU, satu render aktif, deadline tiap operasi/total run ditetapkan dan dicatat. Guardrail awal yang diusulkan: hentikan penambahan beban jika CPU atau RAM teralokasi melewati85% selama60 detik, disk tidak cukup untuk proyeksi DB+file+backup+headroom, atau queue tidak lagi drain dalam batas yang dipilih. Angka guardrail harus dikalibrasi sebelum run; bukan ambang kapasitas produksi.

Hentikan segera bila data/secret bocor, hitungan/snapshot rusak, mutasi tanpa audit/inbox, target isolasi salah, resource host terganggu atau batas aman terlewati. Simpan evidence aman, hentikan generator, periksa transaksi/job tersisa dan cleanup target terverifikasi. Tidak memaksa run sampai crash/kehilangan data demi mencari batas maksimum.

Evaluasi setiap journey/kondisi terhadap PB/SLI yang disepakati sebelum run: p50/p95/p99, N, success/failure/timeouts, throughput, bytes, queue/lock/CPU/RAM/disk dan assertion correctness. Target pilot unexpected failure≤1%; zero pelanggaran akses/integritas. Good/eligible latency≥95% sesuai SLO; timeout dan unexpected403/409/419/422/429/5xx tidak dibuang dari denominator. Negatif yang memang dirancang, contention dan fault run dilaporkan terpisah, tidak membuat run normal terlihat lebih cepat.

p95 completed-response saja belum membuktikan target jika request lain timeout; laporkan censored duration/deadline serta good/eligible. Durasi/test terlalu singkat, generator bottleneck, N<100 per kondisi, clock tak sinkron, fixture palsu, login redirect yang salah dikira sukses, implementasi renderer belum tersedia atau sample dominan read membuat kesimpulan terbatas/INCONCLUSIVE. p99 dengan sampel kecil tidak menjadi klaim stabil. Jangan merata-ratakan percentile lintas skenario; distribusi/raw histogram dan replikasi disimpan.

Setelah perbaikan lakukan before/after dengan variabel lain sama, ulang correctness/akses/failure yang terdampak. CI fokus pada pemeriksaan deterministik; load host lokal bukan gate angka lintas runner yang tak sebanding. Rilis tetap butuh seluruh gate relevan dan approval yang berlaku, bukan hanya latency hijau.

## Baseline, observed results and links

| Evidence | Expected setelah implementasi/eksekusi | Actual sekarang |
| --- | --- | --- |
| Baseline dan perbandingan | SHA/digest, manifest D0/D1/P, host/tool versions, argv, waktu/run, raw output/histogram, screenshot/trace teredaksi | NOT RUN; file/perintah/run link belum tersedia |
| Hipotesis BH-01..06 | Query plans/counters/traces dan apakah hipotesis didukung/ditolak, batas pengukuran serta perbaikan terukur | NOT RUN; belum ada bottleneck terkonfirmasi |
| LT-01..09, PB/SLI terkait | Expected/actual, testcase dan validasi row/audit/inbox/snapshot/file, N/error/censoring, status dan tindak lanjut | NOT RUN; tidak ada kapasitas/SLO terbukti |
| Recovery/drill | Manifest backup/restore, waktu deteksi/respons/operator/pulih, kehilangan committed data dan checksum konsisten | NOT RUN; lihat drill log recovery, bukan artefak yang sudah ada |

Artefak hanya data sintetis tanpa password/cookie/token/isi nilai pada telemetry; simpan akses/retensi sesuai keputusan sebelum publikasi CI. Belum menjalankan server/harness/load/backup/restore, menambah package atau mengubah konfigurasi. Hasil prototipe historis tidak dipindahkan ke baseline aplikasi inti.
