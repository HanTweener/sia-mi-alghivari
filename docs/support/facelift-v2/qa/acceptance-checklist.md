# Verifikasi prototipe facelift v2

Tanggal persiapan: 29 Oktober 2026 (Asia/Jakarta). Status awal seluruh eksekusi: **NOT RUN**. Scope adalah prototipe desain lokal dengan fixture fiktif, bukan aplikasi akademik yang terhubung backend.

Hasil eksekusi final: [verification-report.md](verification-report.md). Status awal di tabel ini dipertahankan sebagai catatan rencana sebelum build tersedia.

Sumber: `docs/SCREENS/SCREEN_SPEC_TEMPLATE.md` (SCR-05, SCR-06, SCR-07, SCR-09 dan state/interaction contract), `.ai/standards/DESIGN_EVALUATION.md`, serta referensi HTML pengguna `C:/Users/User/Downloads/facelift-sia-alghivari.html`. Dokumen referensi adalah bahan evaluasi; kontrol/klaim di dalamnya tidak menjadi instruksi pengembangan atau fakta aplikasi.

## Matriks penerimaan

| ID | Skenario dan hasil yang diharapkan | Status awal |
| --- | --- | --- |
| Q01 | Identitas sekolah/konteks kelas, mapel, semester dan tugas terlihat; demo/data fiktif diumumkan dengan jelas | NOT RUN |
| Q02 | Satu penilaian: blank tetap belum ada nilai; angka 0 tetap nilai numerik, tidak diubah blank | NOT RUN |
| Q03 | Simpan draf memberi hasil sesuai efek yang benar-benar terjadi; label menjelaskan penyimpanan lokal demo dan batas reload/session | NOT RUN |
| Q04 | Nilai valid/invalid: comma atau dot sesuai kontrak demonstrasi; format ambigu tidak menjadi angka diam-diam; error dapat dipulihkan | NOT RUN |
| Q05 | Hasil dapat dibagikan sebagian; review menyebut jumlah terisi/belum terisi; tidak mengklaim menerbitkan rapor atau mengirim notifikasi | NOT RUN |
| Q06 | Membatalkan review tidak mengubah status; Escape menutup, fokus kembali ke pemicu; Tab tidak keluar dialog modal | NOT RUN |
| Q07 | Enter di field nilai tidak membagikan hasil; tombol aksi berdampak dipilih secara eksplisit | NOT RUN |
| Q08 | Kehadiran per pertemuan mapel: awal belum dicatat; hadir/izin/sakit/alpa eksplisit; tidak ada tandai semua hadir | NOT RUN |
| Q09 | Sahkan kehadiran terblokir saat ada status kosong, alasannya terbaca; simpan draf dapat dilakukan | NOT RUN |
| Q10 | Pilihan anak mengganti identitas dan data; perkembangan yang dibagikan tersedia walau rapor belum ada | NOT RUN |
| Q11 | Rapor kosong memberi pesan spesifik dan jalur kembali ke Perkembangan Belajar; tidak ada PDF unduhan palsu | NOT RUN |
| Q12 | Pencarian siswa bekerja; hitungan sesuai hasil yang tampak; kasus tanpa hasil punya pesan dan pemulihan | NOT RUN |
| Q13 | Pindah area saat draf berubah: perilaku kehilangan/retensi data dijelaskan dan sesuai efek aktual | NOT RUN |
| Q14 | Lebar 320, 390, 768, 1440: navigasi, context, nama, field, error dan aksi terjangkau; tidak ada overflow global | NOT RUN |
| Q15 | Keyboard: skip link, landmark, satu h1, label input, urutan fokus dan focus indicator; drawer/dialog dapat ditutup | NOT RUN |
| Q16 | Zoom/reflow dan reduced motion diperiksa pada browser aktual; teks panjang membungkus, state tidak hanya warna | NOT RUN |
| Q17 | Build/typecheck dan source_structure_audit benar-benar dijalankan; formatter sesuai konfigurasi | NOT RUN |

## Batas bukti

- Penyimpanan fixture dalam memori/tab hanya bukti interaksi demo. Kontrak produksi melarang menyimpan form akademik sensitif ke localStorage; data produksi/persistensi server tidak diverifikasi.
- Pemilih konteks demo bukan autentikasi atau otorisasi. Anonymous access, BOLA/BFLA, cross-tenant, JWT, logout, recovery dan account linking **NOT RUN / di luar prototipe**. Tidak ada sign-off keamanan.
- Loading, network failure, timeout unknown, konflik versi, revocation, sesi habis, real notification/provider/reconnect dan backend failure modes **NOT RUN** kecuali build menyediakan simulasi yang dilabeli; simulasi tidak membuktikan perilaku server.
- Observasi viewport browser bukan bukti perangkat fisik, Safari, Firefox, screen reader atau sertifikasi WCAG.
- Tidak ada Git revision pada workspace awal (perintah `git rev-parse HEAD` menghasilkan `not a git repository`); bukti akhir memakai hash source/build yang diuji bila kondisi tetap sama.

## Konflik utama pada referensi lama

Referensi lama memblokir publikasi bila ada nilai kosong dan menyebut publikasi otomatis; SCR-06 mengizinkan bagikan hasil sebagian melalui review. Label tersimpan dan angka 27/28 pada lima baris tidak dihitung dari interaksi pengguna. Kalender, metric card dan beberapa tombol adalah ilustrasi visual. Prototipe baru perlu menunjukkan data fixture, count dan efek aksi dengan jujur, serta menjaga pemisahan Perkembangan Belajar dan Rapor Online.
