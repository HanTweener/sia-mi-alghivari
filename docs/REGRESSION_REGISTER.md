# Regression and Flake Register

Status rancangan 30 Oktober 2026. Register ini menampung temuan regresi/flake yang benar-benar direproduksi pada aplikasi inti. Belum ada record aktual karena pengujian inti belum dijalankan; tabel kosong tidak berarti 0 bug atau bebas flake. Risiko/hipotesis tetap direncanakan pada [TEST_PLAN](TEST_PLAN.md), [ACCEPTANCE_TEST_MATRIX](ACCEPTANCE_TEST_MATRIX.md), CT/API-T/DBV/AS/N-T, bukan dimasukkan sebagai insiden aktual.

| Issue/bug ID | Triggering change | Impact/priority | Reproduction & root cause | Retained test ID | Owner | Status / expiry |
| --- | --- | --- | --- | --- | --- | --- |

Hipotesis yang perlu dijaga: perubahan role/assignment tidak membuka scope lain; blank/0 dan hitungan frozen roster tetap benar; koreksi tidak menimpa PDF; retry/reconnect/deploy tidak menggandakan bisnis/inbox. Rujuk testcase kanonis, tanpa menciptakan issue/severity/reproduction/root cause dari perkiraan.

Ketika ada temuan lokal atau publik, buat row dengan ID nyata, perubahan/revisi pemicu yang diketahui, tahap/environment/build/config, langkah serta expected/actual dan bukti aman; root cause yang belum terbukti ditulis belum diketahui. Hubungkan retained test nyata, owner yang ditugaskan, status/perbaikan/recheck beserta revisi baru. Reproduksi publik tidak memberi izin fault/load/destructive test pada target aktif; isolasikan dahulu. Format bukti mengikuti [QA_EVIDENCE_REPORT](QA_EVIDENCE_REPORT.md).

Catat baseline failure/retry/CI/quarantine hanya dari run nyata; hasil gagal dan rerun dipertahankan, flaky retry tidak mengubah percobaan pertama menjadi PASS. Jangan menghapus test atau melewati regresi kritis tanpa keputusan risiko bernama yang benar-benar diberikan. Tidak ada test quarantine/exception/expiry yang disetujui sekarang. Semua pengujian aplikasi inti NOT RUN.
