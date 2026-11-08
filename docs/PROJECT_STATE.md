# Project State & AI Handoff — SIA MI ALGHIVARI

**Owner:** pemilik proyek · **Updated:** 30 Oktober 2026 · **Branch/commit:** belum tersedia; root workspace belum memiliki `.git`.

Status dokumen: **PROPOSED / blueprint**. Ini ringkasan operasional singkat, bukan sumber aturan pengganti PRD. Pembaruan30Oktober2026 menyangkut rancangan dokumentasi; inventaris source/runtime terakhir yang dicatat tetap28-29Oktober sesuai tabel di bawah, bukan pemeriksaan runtime baru. Keputusan dan usulan dibedakan. Tidak memuat secret atau data pribadi nyata.

## Current outcome and milestone

- Product promise: satu sistem akademik sekolah untuk persiapan akademik, absensi per mapel, penilaian, pemantauan wali, rapor dan keputusan naik/lulus, dengan role rangkap, audit dan notifikasi berizin. Demo menggunakan data sintetis.
- Active milestone / feature: **M0 — pemantapan blueprint** pada [ROADMAP.md](ROADMAP.md). Urutan 1–8 tersedia pada [indeks](README.md); terbaru D-34: rancangan laporan relevan memuat hipotesis/expected dan kebutuhan bukti LOCAL/PUBLIC, actual belum diverifikasi; template event-only/di luar scope tetap sesuai penggunaannya. Prototipe frontend terisolasi adalah referensi desain; implementasi aplikasi inti M1–M5 tetap **NOT STARTED**.
- Explicit exclusions: tidak membangun aplikasi, memasang dependensi, membuat migration/config/workflow, mengubah DNS, mengaktifkan runner/tunnel atau melakukan deployment pada tahap ini. Banyak sekolah, social login, chat, AI, kanal pesan eksternal dan offline write belum termasuk kebutuhan.

## Confirmed current implementation

| Area | What exists in code | How confirmed | Relevant file |
| --- | --- | --- | --- |
| Aplikasi dan dependensi | Aplikasi inti/server belum ada. Prototipe terisolasi mempunyai package.json/lockfile, React/TypeScript/Tailwind/shadcn serta skrip build/test/format. | Inventaris root dan pembacaan source/manifest prototipe 29 Oktober 2026; bukan eksekusi runtime baru. | [TECH_STACK.md](TECH_STACK.md), [prototipe](support/facelift-v2/README.md) |
| Database, HTTP dan UI | Belum ada migration/endpoint/database aplikasi inti yang dibuktikan berjalan. Empat layar interaktif serta tujuh kajian visual prototipe memakai fixture/memori, bukan server. | Membaca App/styles/README dan dua screenshot nilai desktop/ponsel yang sudah tersedia; tidak menjalankan browser, database atau tes ulang. | [DATA_MODEL.md](DATA_MODEL.md), [API_CONTRACT.md](API_CONTRACT.md), [ARCHITECTURE.md](ARCHITECTURE.md) |
| Build, CI/CD dan demo | Belum ada Dockerfile, konfigurasi Compose atau workflow GitHub Actions aplikasi yang ditemukan. Domain, host dan deployment belum diverifikasi. Branch dev/main baru rencana. | Inventaris root/berkas; `.git` tidak ditemukan pada root. Tidak memeriksa akun atau layanan eksternal. | [CICD_PIPELINE_SPEC.md](CICD_PIPELINE_SPEC.md), [DEPLOYMENT_PLAN.md](DEPLOYMENT_PLAN.md) |
| Dokumentasi dan scaffold pengembangan | `docs/`, `.ai/`, `.codex/`, `.taste-skill/`, `tmp/` serta `AGENTS.md` terlihat di root. Dokumen konkret/template, artefak desain dan alat bantu bukan backend atau CI aplikasi. | Inventaris root 29 Oktober 2026 dan pembacaan acuan; tidak mengubah direktori prototipe/alat. | [README.md](README.md), [CODE_QUALITY_PLAN.md](CODE_QUALITY_PLAN.md) |

## Commands actually verified

| Command | Environment/date | Result | Notes |
| --- | --- | --- | --- |
| `Get-ChildItem -Force` | PowerShell, root proyek, 28 Oktober 2026 | PASS — inventaris terbaca | Hanya `.ai`, `.codex`, `docs` dan `AGENTS.md` pada root saat diperiksa; tidak membuktikan aplikasi berjalan. |
| `Get-ChildItem -Force` | PowerShell, root proyek, 29 Oktober 2026 | PASS, inventaris terbaca | Tambahan `.taste-skill` dan `tmp` sudah terlihat; prototipe berada di docs/support. Bukan tes aplikasi. |
| `Test-Path -LiteralPath .git` | PowerShell, root proyek, 29 Oktober 2026 | `False` | Bukti terbatas pada root workspace; bukan pemeriksaan GitHub atau direktori induk. |
| Command aplikasi inti belum dikonfigurasi; skrip prototipe ada pada package.json | Source/config prototipe dibaca; runtime aplikasi inti belum tersedia | NOT RUN pada tugas dokumentasi ini | Bukti prototipe terdahulu merujuk SKILL_REBUILD/qa/rebuild-report sesuai revisinya, bukan pengujian backend yang sudah dijalankan. |

## Accepted decisions

Keputusan berikut dikonfirmasi pemilik; tanggal pencatatan 28 Oktober 2026. Pencatatan bukan persetujuan seluruh dokumen atau izin implementasi.

- Q1–Q4: role dapat dirangkap; principal membaca dengan pengecualian approval naik/lulus; Data Entry C/U operasional tanpa hapus/konfigurasi dan bantuan input melalui delegasi; wali kelas boleh menerbitkan rapor binaan; hanya Master mengatur role/jabatan. Rincian pada [PRD](PRD.md) dan [matriks izin](ROLE_PERMISSION_MATRIX.md).
- Q5 dan keputusan lanjutannya: absensi per mapel; KKM/bobot global oleh Master/Akademik, total 100%; blank dilewati, 0 dihitung; komponen berbobot positif seluruhnya kosong menghalangi finalisasi. [ACADEMIC_FLOW.md](ACADEMIC_FLOW.md).
- Penugasan wali kelas melalui `homeroom_assignments`, tanpa foreign key guru pada `classrooms`. [DATA_MODEL.md](DATA_MODEL.md).
- Perkembangan Belajar memakai aktivitas yang dibagikan/disahkan dan dapat dibaca tanpa rapor; Rapor Online memerlukan snapshot finalized lalu published. Setiap CUD diaudit sistem; notifikasi hanya penerima berhak. [Spesifikasi fitur](FEATURES/FEATURE_SPEC_TEMPLATE.md).
- Tujuan React/TypeScript, Reverb/Echo, serta alur demo dev → PR/main → GitHub Actions → GHCR → runner lokal → Compose dikonfirmasi sebagai blueprint; Tunnel menjadi rencana akses publik. Bentuk Laravel/Inertia/React, MySQL dan baseline versi inti disepakati; paket tambahan/versi persis/konfigurasi mengikuti status masing-masing pada [TECH_STACK.md](TECH_STACK.md).

Catatan [ADR-SIA-001](DECISIONS/ADR_TEMPLATE.md), [DB-001](DATABASE_DECISION_RECORD.md) dan [ARC-001](ARCHITECTURE_REVIEW.md) membedakan keputusan terkonfirmasi dari usulan. Bentuk satu aplikasi Laravel + Inertia + React/TypeScript dikonfirmasi pemilik 29 Oktober 2026 (D-10/ARC-001). Engine MySQL juga dipilih pemilik 29 Oktober 2026 (DB-001/D-11). Baseline versi inti disepakati pemilik 29 Oktober 2026 (D-15); Docker Desktop/WSL2 dan FrankenPHP classic berbasis Debian juga disepakati pada tanggal tersebut (D-16). Dompdf melalui barryvdh/laravel-dompdf 3.1.x disepakati sebagai renderer PDF rapor pada tanggal yang sama (D-17). Konfigurasi, paket tambahan lain, versi persis dan detail struktur kode tetap mengikuti status masing-masing; susunan Q7.1 disepakati pada D-18; batas skor input Q7.2 diterima pada D-19; pembulatan Q7.3 disepakati pada D-20; pembanding Q7.4 diterima pada D-21; presisi konfigurasi Q7.5 diterima pada D-22; detail visual/render belum final.

Arahan pemilik 29 Oktober 2026: facelift-v2 dipakai sebagai **referensi desain, bukan panduan**; penggunaan antislop/taste-skill pada implementasi dicatat, serta Ponytail full diterapkan pada rencana kualitas/arsitektur. Antislop During dipilih untuk sesi ini, tanpa menyimpan preferensi global. Arahan tersebut bukan izin mengubah prototype, memasang plugin atau memulai backend/deployment.

D-25 menggantikan D-23/D-24: password sementara emailed/forced change dan OTP email Fouladgar. Email langsung verified oleh Master; modal pilihan sesi setelah password tersimpan wajib, reset login biasa/profil current device tetap login. Angka parameter/provider dan detail integrasi belum final. [Lifecycle kanonis](RECOVERY_AND_STEPUP_PLAN.md).

Keputusan lanjutan Q6.1, 29 Oktober 2026: Master membuat akun wali, role guardian dan aktivasi terlebih dahulu; Data Entry kemudian melengkapi biodata serta hubungan siswa. Akun yang sudah ada memakai role rangkap, tanpa profil wali pending baru. Allowlist biodata tetap Q6 dan D-25 temporary/email verified Master/OTP/modal dipilih; parameter/provider/integrasi belum final; tidak ada implementasi atau hasil tes baru.

Q6.3 dikonfirmasi 29 Oktober 2026: Master dan Admin Akademik boleh memberi dan mencabut delegasi input nilai/kehadiran kepada Data Entry. C/U tetap draft, scope/capability/masa berlaku tercatat dan actor audit petugas asli. Aktivasi usulan penugasan guru Data Entry serta detail mekanisme/DTO delegasi belum diputuskan melalui pilihan aktor ini.

## Active issues and uncertainty

| Risk / question | Impact | Owner | Planned action |
| --- | --- | --- | --- |
| Bentuk Laravel + Inertia + React/TypeScript serta MySQL dipilih; baseline versi inti disepakati; konfigurasi, paket tambahan dan versi persis belum final | Bootstrap, dependensi dan command belum dapat dikunci. | Pemilik proyek; pengembang menyiapkan opsi | Baseline versi inti D-15 dan runtime D-16 sudah disepakati; selesaikan keputusan tersisa pada TECH_STACK/ADR sebelum bootstrap yang bergantung padanya. |
| Q6 allowlist/penugasan/delegasi/apply; D-25 parameter password | Q6.1/Q6.3 dan Q6.2/Q6.4 D25 dipilih. TTL/limit/provider/hash adapter/middleware/bootstrap/fallback mailbox belum final | Pemilik proyek | Pilih parameter serta provider, buktikan hash/atomic proof/Yes-No sesi/mail failure; tidak mewarisi D24 |
| Q7.1-Q7.5 aturan disepakati; detail visual dan bukti implementasi belum diverifikasi | Expected angka akhir serta hasil visual/render belum boleh dianggap terverifikasi. | Pemilik proyek | KKM/bobot0-100 maksimal2desimal dan total aktivasi100 diterima pada Q7.5; uji format Q7.1 pada implementasi, tanpa menganggap susunan sebagai persetujuan angka atau sign-off visual. |
| D26/D27: pergantian guru dan keanggotaan efektif telah dipilih | Risiko implementasi salah parent/izin, duplikasi mapel atau penyebut berubah; aturan produk dikonfirmasi, runtime belum diverifikasi | Pengembang; pemilik menerima hasil | Implementasikan class_subject/histori/frozen roster dan uji DBV27/28/API-T52..54. Transfer tengah term/koreksi struktur final ditunda; skor/status D-28 dipilih, nilai awal timezone/rekonsiliasi cap delegasi OPEN |
| D-28 koreksi sah serta revisi/pencabutan rapor dikonfirmasi | Aturan aktor/lifecycle dipilih; risiko hold/race/akses/file/progression belum diverifikasi | Pengembang; pemilik menerima hasil | Implementasikan command terpisah, hold per siswa, policy asli, revisi immutable, MA/W revoke dan satu versi aktif. API-T55..57/DBV29..30 seluruhnya NOT RUN; struktur/roster final dan undo applied ditunda. |
| D-29 satu timezone sekolah dikonfirmasi | Master dinamis WIB/WITA/WIT untuk kalender/tampilan; UTC internal. Nilai awal setup dan cap delegasi dari DATE belum dipilih | Pemilik proyek; pengembang membuktikan | Pilih nilai awal saat setup; uji API-T58/DBV23, refresh tanpa restart dan grant/version consistency. Putuskan rekonsiliasi cap sebelum aktivasi terkait; tidak rebase otomatis UTC existing. |
| D-33 host demo dan DNS/TLS dipilih; implementasi belum diverifikasi | PC pemilik RAM24GB/SSD kosong100GB, DigitalPlat .dpdns.org belum dimiliki, Cloudflare authoritative NS + satu Tunnel dua hostname HTTPS/WSS. Backup D-30/31 tetap | Pemilik/operator proyek | Pilih nama aktual lalu registrasi/zone/NS/cert/config/dua-host smoke saat diotorisasi; kapasitas serta restore belum diuji. RPO/RTO formal di luar scope D-32, tanpa janji24/7; target arsitektur dipilih bukan bukti ready. |
| D-34 rancangan dokumen relevan dan pencatatan bukti bertahap | Hipotesis/expected dan kebutuhan bukti lokal/publik disiapkan; prototipe tidak membuktikan backend. Actual inti NOT RUN | Penyusun/pengembang/penguji; penerimaan oleh pemilik | Gunakan QA_EVIDENCE_REPORT dan laporan bidang, isi actual per fitur setelah eksekusi lokal lalu publik terotorisasi. Template insiden/approval/refactor/handoff hanya bila diperlukan; jangan menyatakan kesiapan dari kelengkapan dokumen. |

## Next slice

- User outcome / acceptance criteria: D-34 menyiapkan hipotesis/expected dan kebutuhan bukti per fitur LOCAL lalu PUBLIC terotorisasi; referensi prototipe terpisah. Actual hasil/skor/temuan/reviewer/GO tetap NOT RUN/NOT VERIFIED/PENDING sampai benar-benar diperiksa.
- Intended files/boundaries: laporan verifikasi inti/QA serta template relevan kesiapan demo/auth lokal/regresi, indeks/strategi/delivery/state/ADR/PRD/konteks. Tidak mengubah source/schema/config/prototype, menjalankan tests atau mengotorisasi registrasi/provider/backup/deploy.
- Dependencies / hazards: acuan PRD/ROADMAP/ADR/stack; jangan mengubah usulan menjadi keputusan, tes menjadi PASS, atau reviewer rencana menjadi review yang telah terjadi.
- Definition of completion and verification: struktur template/tautan/rujukan skenario dan hipotesis-expected versus evidence/actual LOCAL-PUBLIC konsisten; telaah dokumen bukan bukti aplikasi. Semua runtime NOT RUN, template event-only/di luar scope tidak diisi fiktif. Bukti aktual baru dicatat sesudah eksekusi pada revisi yang sah.

## Context refresh instruction for the next agent

Baca `AGENTS.md`, konteks singkat yang terisi, [PRD](PRD.md), keputusan relevan dan [DELIVERY_PLAN.md](DELIVERY_PLAN.md). Periksa ulang source, status keputusan dan command aktual sebelum bertindak; laporkan jika berbeda dari ringkasan ini. Dokumen ini menyimpan kondisi terakhir yang diperiksa, bukan jaminan kondisi masa depan. Jangan mengisi hasil, approval, commit atau deployment yang belum ada.
