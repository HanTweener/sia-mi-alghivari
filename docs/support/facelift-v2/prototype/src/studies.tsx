import { useRef, useState } from "react";
import { createRoot } from "react-dom/client";
import { Button } from "./components/ui/button";
import { Input } from "./components/ui/input";
import { Textarea } from "./components/ui/textarea";
import { Label } from "./components/ui/label";
import { Badge } from "./components/ui/badge";
import {
  Table,
  TableCaption,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from "./components/ui/table";
import { cn } from "./components/ui/utils";
import "./styles.css";

const studies = [
  {
    id: "login",
    label: "Masuk",
    context: "Semua pengguna",
    title: "Masuk ke akun sekolah",
  },
  {
    id: "jobs",
    label: "Pekerjaan mengajar",
    context: "Guru mapel",
    title: "Pekerjaan mengajar",
  },
  {
    id: "admin",
    label: "Administrasi",
    context: "Admin Akademik",
    title: "Kelas & penugasan",
  },
  {
    id: "policy",
    label: "Aturan penilaian",
    context: "Admin Akademik / Master",
    title: "Aturan penilaian global",
  },
  {
    id: "reports",
    label: "Persiapan rapor",
    context: "Wali kelas · Kelas 4 A",
    title: "Tinjau kesiapan rapor",
  },
  {
    id: "decisions",
    label: "Keputusan naik / lulus",
    context: "Kepala sekolah",
    title: "Tinjau usulan kenaikan",
  },
  {
    id: "audit",
    label: "Audit & inbox",
    context: "Master · tampilan audit",
    title: "Riwayat perubahan",
  },
] as const;
type Study = (typeof studies)[number]["id"];

function Tag({
  children,
  warn = false,
}: {
  children: React.ReactNode;
  warn?: boolean;
}) {
  return (
    <Badge
      variant={warn ? "warning" : "secondary"}
      className={cn(
        "rounded px-2 py-1 text-sm font-medium",
        !warn && "text-primary",
      )}
    >
      {children}
    </Badge>
  );
}

function Section({
  title,
  children,
}: {
  title: string;
  children: React.ReactNode;
}) {
  return (
    <section className="my-7 [&>h2]:mb-4 [&>h2]:text-lg [&>h2]:font-[650] [&_p]:mt-2 [&_p]:mb-4 [&_p]:text-muted-foreground">
      <h2>{title}</h2>
      {children}
    </section>
  );
}

function StudyTable({ children }: { children: React.ReactNode }) {
  return (
    <Table
      className={cn(
        "caption-top [&_td]:whitespace-normal [&_th]:whitespace-normal w-full border-collapse text-left text-sm [&_th]:border-b [&_th]:p-4 [&_th]:align-top",
        "[&_th]:font-medium [&_th]:text-muted-foreground [&_td]:border-b [&_td]:p-4 [&_td]:align-top [&_thead]:bg-background [&_tbody_th]:min-w-[110px] max-[640px]:block",
        "max-[640px]:[&_tbody]:block max-[640px]:[&_thead]:sr-only max-[640px]:[&_tr]:block max-[640px]:[&_tr]:w-full max-[640px]:[&_tr]:border-b",
        "max-[640px]:[&_tr]:p-3 max-[640px]:[&_th]:block max-[640px]:[&_td]:block max-[640px]:[&_th]:w-full max-[640px]:[&_td]:w-full max-[640px]:[&_th]:border-0",
        "max-[640px]:[&_td]:border-0 max-[640px]:[&_th]:px-1 max-[640px]:[&_td]:px-1 max-[640px]:[&_th]:py-2 max-[640px]:[&_td]:py-2 max-[640px]:[&_td]:break-words",
        "max-[640px]:[&_td]:before:mb-1 max-[640px]:[&_td]:before:block max-[640px]:[&_td]:before:font-medium max-[640px]:[&_td]:before:text-muted-foreground",
        "max-[640px]:[&_td]:before:content-[attr(data-label)]",
      )}
    >
      {children}
    </Table>
  );
}

function Login() {
  return (
    <div className="grid grid-cols-2 gap-14 rounded-md border bg-background p-10 max-[900px]:gap-6 max-[900px]:p-6 max-[640px]:grid-cols-1 max-[640px]:gap-2">
      <div
        className={cn(
          "flex flex-col border-r pr-6 [&_h2]:my-5 [&_h2]:text-[30px] [&_h2]:leading-tight [&_h2]:font-[650] [&_h2]:tracking-[-0.035em]",
          "[&>p:not(:first-child)]:text-sm [&>p:not(:first-child)]:text-muted-foreground max-[640px]:border-r-0 max-[640px]:border-b max-[640px]:pr-0 max-[640px]:pb-6",
        )}
      >
        <p className="font-semibold text-primary">MI Alghivari</p>
        <h2>
          Sistem Informasi
          <br />
          Akademik
        </h2>
        <p>
          Akses kegiatan mengajar, data akademik, dan perkembangan belajar anak
          melalui akun yang disediakan sekolah.
        </p>
        <p className="mt-auto pt-8">
          Tidak ada pendaftaran mandiri.
          <br />
          Hubungi pengelola sekolah untuk bantuan akun.
        </p>
      </div>
      <section
        className={cn(
          "[&>h2]:mb-2 [&>h2]:text-xl [&>h2]:font-[650] [&>p]:text-sm [&>p]:text-muted-foreground [&_label]:mt-6 [&_label]:grid [&_label]:gap-2 [&_label]:text-sm",
          "[&>button]:mt-6 [&>button]:w-full",
        )}
        aria-labelledby="login-title"
      >
        <h2 id="login-title">Masuk ke akun Anda</h2>
        <p>Gunakan username dari sekolah.</p>
        <Label>
          Username
          <Input
            className="disabled:opacity-100"
            disabled
            autoComplete="username"
            placeholder="Username"
          />
        </Label>
        <Label>
          Kata sandi
          <Input
            className="disabled:opacity-100"
            disabled
            type="password"
            autoComplete="current-password"
            placeholder="Kata sandi"
          />
        </Label>
        <Button className="disabled:opacity-100" disabled>
          Masuk
        </Button>
        <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
          Form visual, tanpa proses autentikasi. Metode pemulihan akun belum
          ditetapkan.
        </p>
      </section>
    </div>
  );
}

function Jobs() {
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Lanjutkan pencatatan pada kelas yang Anda ampu.
      </p>
      <Section title="Perlu dilanjutkan">
        <div
          className={cn(
            "flex items-center justify-between gap-6 border-t py-[22px] last:border-b [&_h3]:mt-2.5 [&_h3]:font-semibold [&_p]:mt-1 [&_p]:mb-0 [&_p]:text-sm",
            "max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-4",
          )}
        >
          <div>
            <Tag warn>Draf</Tag>
            <h3>Ulangan Harian 1 · Matematika</h3>
            <p>Kelas 4 A · 2 dari 8 siswa belum memiliki nilai</p>
          </div>
          <Button className="disabled:opacity-100" disabled variant="outline">
            Lanjutkan nilai
          </Button>
        </div>
        <div
          className={cn(
            "flex items-center justify-between gap-6 border-t py-[22px] last:border-b [&_h3]:mt-2.5 [&_h3]:font-semibold [&_p]:mt-1 [&_p]:mb-0 [&_p]:text-sm",
            "max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-4",
          )}
        >
          <div>
            <Tag warn>Belum disahkan</Tag>
            <h3>Pertemuan 08 · Matematika</h3>
            <p>Kelas 4 A · 27 Oktober 2026 · 2 status belum dicatat</p>
          </div>
          <Button className="disabled:opacity-100" disabled variant="outline">
            Lanjutkan kehadiran
          </Button>
        </div>
      </Section>
      <Section title="Jadwal · Selasa, 27 Oktober 2026">
        <div
          className={cn(
            "grid grid-cols-[120px_1fr_auto] gap-6 border-t py-5 [&_time]:text-sm [&_time]:tabular-nums [&_h3]:font-semibold [&_p]:my-1 [&_p]:text-sm [&>span]:my-1",
            "[&>span]:text-sm [&>span]:text-muted-foreground max-[640px]:grid-cols-[100px_1fr] max-[640px]:gap-4 max-[640px]:[&>span]:col-start-2",
          )}
        >
          <time>07.30–08.40</time>
          <div>
            <h3>Matematika</h3>
            <p>Kelas 4 A · 2 jam pelajaran</p>
          </div>
          <span>Penugasan aktif</span>
        </div>
        <div
          className={cn(
            "grid grid-cols-[120px_1fr_auto] gap-6 border-t py-5 [&_time]:text-sm [&_time]:tabular-nums [&_h3]:font-semibold [&_p]:my-1 [&_p]:text-sm [&>span]:my-1",
            "[&>span]:text-sm [&>span]:text-muted-foreground max-[640px]:grid-cols-[100px_1fr] max-[640px]:gap-4 max-[640px]:[&>span]:col-start-2",
          )}
        >
          <time>09.00–10.10</time>
          <div>
            <h3>Matematika</h3>
            <p>Kelas 4 B · 2 jam pelajaran</p>
          </div>
          <span>Penugasan aktif</span>
        </div>
      </Section>
      <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
        Tanggal, penugasan dan jumlah di sini adalah fixture ilustrasi. Tidak
        mengikuti jam perangkat.
      </p>
    </>
  );
}

function Admin() {
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Pisahkan struktur kelas, penugasan mengajar, dan hak akses akun.
      </p>
      <div className="flex flex-wrap gap-6 border-b pb-4 [&_strong]:text-primary [&_span]:text-muted-foreground">
        <strong>Kelas</strong>
        <span>Mata pelajaran</span>
        <span>Penugasan guru</span>
      </div>
      <div className="my-6 flex flex-wrap items-end justify-between gap-6 [&_label]:grid [&_label]:w-[330px] [&_label]:max-w-full [&_label]:gap-2 [&_label]:text-sm">
        <Label>
          Cari kelas
          <Input
            className="disabled:opacity-100"
            disabled
            placeholder="Nama kelas atau wali kelas"
          />
        </Label>
        <Button className="disabled:opacity-100" disabled>
          Tambah kelas
        </Button>
      </div>
      <div className="overflow-x-auto rounded-md border bg-background">
        <StudyTable>
          <TableCaption className="mt-0 caption-top border-b p-4 text-left text-muted-foreground max-[640px]:block max-[640px]:w-full">
            Kelas aktif · ilustrasi data sintetis
          </TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead scope="col">Kelas</TableHead>
              <TableHead scope="col">Wali kelas</TableHead>
              <TableHead scope="col">Keadaan penugasan</TableHead>
              <TableHead scope="col">Tindakan</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableHead scope="row">4 A</TableHead>
              <TableCell data-label="Wali kelas">Guru Contoh A</TableCell>
              <TableCell data-label="Keadaan penugasan">
                <Tag>Aktif pada semester ini</Tag>
              </TableCell>
              <TableCell data-label="Tindakan">
                <Button
                  className="disabled:opacity-100"
                  disabled
                  variant="outline"
                >
                  Kelola
                </Button>
              </TableCell>
            </TableRow>
            <TableRow>
              <TableHead scope="row">4 B</TableHead>
              <TableCell data-label="Wali kelas">Belum ditetapkan</TableCell>
              <TableCell data-label="Keadaan penugasan">
                <Tag warn>Perlu penugasan wali kelas</Tag>
              </TableCell>
              <TableCell data-label="Tindakan">
                <Button
                  className="disabled:opacity-100"
                  disabled
                  variant="outline"
                >
                  Kelola
                </Button>
              </TableCell>
            </TableRow>
            <TableRow>
              <TableHead scope="row">5 A</TableHead>
              <TableCell data-label="Wali kelas">Guru Contoh B</TableCell>
              <TableCell data-label="Keadaan penugasan">
                <Tag>Aktif pada semester ini</Tag>
              </TableCell>
              <TableCell data-label="Tindakan">
                <Button
                  className="disabled:opacity-100"
                  disabled
                  variant="outline"
                >
                  Kelola
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </StudyTable>
      </div>
      <div className="grid grid-cols-2 gap-9 max-[640px]:grid-cols-1 max-[640px]:gap-2">
        <Section title="Admin Akademik">
          <p>
            Mengelola domain akademik sesuai izin dan keadaan data, termasuk
            nilai. Pemberian role akun tetap oleh Master.
          </p>
        </Section>
        <Section title="Varian Data Entry">
          <p>
            Tambah dan ubah operasional tanpa hapus atau konfigurasi. Input
            nilai dan kehadiran hanya melalui delegasi aktif; tidak ada aksi
            bagikan atau sahkan.
          </p>
        </Section>
      </div>
    </>
  );
}

function Policy() {
  const rows = [
    ["Tugas", "20%"],
    ["Ujian harian", "20%"],
    ["UTS", "25%"],
    ["UAS", "25%"],
    ["Kehadiran otomatis", "10%"],
  ];
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Satu aturan berlaku untuk seluruh mata pelajaran. Pengajar tidak
        mengubah bobot atau KKM per kelas.
      </p>
      <div className="my-6 border-l-[3px] border-warning-foreground bg-warning px-5 py-4 text-sm text-warning-foreground">
        Semua bobot dan KKM pada kajian ini adalah contoh. Bukan kebijakan
        akademik sekolah.
      </div>
      <div className="grid grid-cols-[1.3fr_1fr] gap-10 max-[640px]:grid-cols-1 max-[640px]:gap-2">
        <Section title="Komponen dan bobot">
          <div className="[&>div]:flex [&>div]:justify-between [&>div]:gap-6 [&>div]:border-b [&>div]:bg-background [&>div]:p-4">
            {rows.map(([name, weight]) => (
              <div key={name}>
                <span>{name}</span>
                <strong>{weight}</strong>
              </div>
            ))}
          </div>
          <div className="flex justify-between gap-6 border-b bg-accent p-4 text-primary">
            <strong>Total bobot</strong>
            <strong>100%</strong>
          </div>
        </Section>
        <Section title="Revisi yang ditinjau">
          <dl
            className={cn(
              "[&>div]:grid [&>div]:grid-cols-[120px_1fr] [&>div]:gap-4 [&>div]:border-b [&>div]:py-3 [&>div]:text-sm [&_dt]:text-muted-foreground [&_dd]:font-medium",
              "max-[640px]:[&>div]:grid-cols-[90px_1fr]",
            )}
          >
            <div>
              <dt>Cakupan</dt>
              <dd>Seluruh mapel</dd>
            </div>
            <div>
              <dt>KKM contoh</dt>
              <dd>75</dd>
            </div>
            <div>
              <dt>Keadaan</dt>
              <dd>Draf perubahan</dd>
            </div>
          </dl>
          <p>
            Aktivasi membuat revisi global baru. Rapor yang sudah difinalisasi
            tidak ditimpa.
          </p>
          <Button className="disabled:opacity-100" disabled>
            Tinjau aktivasi
          </Button>
        </Section>
      </div>
      <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
        Pada varian invalid, total 99% atau 101% menonaktifkan aktivasi dengan
        pesan jumlah yang harus diperbaiki. Batas nilai dan pembulatan Q7 masih
        terbuka.
      </p>
    </>
  );
}

function Reports() {
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Periksa seluruh komponen sebelum membekukan dan menerbitkan rapor kelas
        binaan.
      </p>
      <ol
        className={cn(
          "mb-6 flex flex-wrap gap-7 [&_li]:flex [&_li]:items-center [&_li]:gap-2.5 [&_li]:text-sm [&_b]:grid [&_b]:size-[30px] [&_b]:place-items-center [&_b]:rounded",
          "[&_b]:border [&_b]:border-input",
        )}
      >
        <li>
          <b>1</b>Finalisasi snapshot
        </li>
        <li>
          <b>2</b>Siapkan PDF
        </li>
        <li>
          <b>3</b>Publikasikan rapor
        </li>
      </ol>
      <div className="my-6 border-l-[3px] border-warning-foreground bg-warning px-5 py-4 text-sm text-warning-foreground">
        Belum siap difinalisasi: satu komponen berbobot positif belum mempunyai
        data. Nilai kosong tidak diubah menjadi 0.
      </div>
      <div className="overflow-x-auto rounded-md border bg-background">
        <StudyTable>
          <TableCaption className="mt-0 caption-top border-b p-4 text-left text-muted-foreground max-[640px]:block max-[640px]:w-full">
            Contoh ringkasan kesiapan satu siswa · Siswa Contoh 01
          </TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead scope="col">Mata pelajaran</TableHead>
              <TableHead scope="col">Keadaan</TableHead>
              <TableHead scope="col">Yang perlu ditindaklanjuti</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableHead scope="row">Matematika</TableHead>
              <TableCell data-label="Keadaan">
                <Tag warn>Belum lengkap</Tag>
              </TableCell>
              <TableCell data-label="Tindak lanjut">
                Komponen UAS belum memiliki nilai.
              </TableCell>
            </TableRow>
            <TableRow>
              <TableHead scope="row">Bahasa Indonesia</TableHead>
              <TableCell data-label="Keadaan">
                <Tag>Siap ditinjau</Tag>
              </TableCell>
              <TableCell data-label="Tindak lanjut">
                Komponen berbobot positif mempunyai data.
              </TableCell>
            </TableRow>
          </TableBody>
        </StudyTable>
      </div>
      <div
        className={cn(
          "flex items-center justify-between gap-6 border-b py-5 [&_p]:max-w-[65ch] [&_p]:text-sm [&_p]:text-muted-foreground max-[640px]:flex-col",
          "max-[640px]:items-start max-[640px]:gap-4",
        )}
      >
        <p>
          Wali kelas dapat memublikasikan rapor binaan setelah siap; tidak
          mengedit nilai guru mapel lain.
        </p>
        <Button className="disabled:opacity-100" disabled>
          Finalisasi rapor
        </Button>
      </div>
      <Section title="Jika snapshot sudah difinalisasi">
        <p>
          Rapor belum terlihat oleh wali. PDF yang gagal disiapkan ditampilkan
          sebagai file belum siap, dengan aksi ulang hanya bagi pengguna berhak.
          Publish baru tersedia setelah prasyarat lengkap.
        </p>
      </Section>
    </>
  );
}

function Decisions() {
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Persetujuan kepala sekolah dicatat terlebih dahulu. Penerapan pada tahun
        tujuan merupakan langkah terpisah.
      </p>
      <div className="my-6 border-l-[3px] border-warning-foreground bg-warning px-5 py-4 text-sm text-warning-foreground">
        Belum diterapkan · Menyetujui usulan tidak langsung memindahkan siswa.
      </div>
      <div className="grid grid-cols-2 gap-9 max-[640px]:grid-cols-1 max-[640px]:gap-2">
        <Section title="Usulan yang ditinjau">
          <dl
            className={cn(
              "[&>div]:grid [&>div]:grid-cols-[120px_1fr] [&>div]:gap-4 [&>div]:border-b [&>div]:py-3 [&>div]:text-sm [&_dt]:text-muted-foreground [&_dd]:font-medium",
              "max-[640px]:[&>div]:grid-cols-[90px_1fr]",
            )}
          >
            <div>
              <dt>Siswa</dt>
              <dd>Siswa Contoh 01</dd>
            </div>
            <div>
              <dt>Kelas asal</dt>
              <dd>4 A · 2026/2027</dd>
            </div>
            <div>
              <dt>Tujuan usulan</dt>
              <dd>5 A · 2027/2028</dd>
            </div>
            <div>
              <dt>Keadaan</dt>
              <dd>Menunggu keputusan</dd>
            </div>
          </dl>
        </Section>
        <Section title="Catatan keputusan">
          <Label className="grid gap-2 text-sm">
            Alasan penolakan / catatan
            <Textarea
              className="disabled:opacity-100"
              disabled
              rows={4}
              placeholder="Tuliskan alasan yang relevan dengan usulan"
            />
          </Label>
          <div className="mt-4 flex flex-wrap gap-3">
            <Button className="disabled:opacity-100" disabled variant="outline">
              Tolak usulan
            </Button>
            <Button className="disabled:opacity-100" disabled>
              Tinjau persetujuan
            </Button>
          </div>
        </Section>
      </div>
      <Section title="Penerapan oleh pelaksana yang berhak">
        <p>
          Setelah disetujui, tampilkan sumber, tujuan dan waktu berlaku dalam
          review terpisah. Tidak ada tombol Terapkan pada konteks kepala sekolah
          ini. Pelaksana dan rincian Q6 belum final.
        </p>
      </Section>
      <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
        Varian massal hanya memilih halaman aktif; daftar terpilih dan masalah
        terlihat sebelum konfirmasi. Satu objek invalid menolak seluruh batch,
        bukan sukses sebagian tersembunyi.
      </p>
    </>
  );
}

function Audit() {
  return (
    <>
      <p className="-mt-2 mb-7 max-w-[75ch] text-muted-foreground">
        Perubahan dicatat sistem. Tidak ada pengguna, termasuk Master, yang
        dapat mengubah atau menghapus riwayat.
      </p>
      <Section title="Riwayat perubahan akademik">
        <div
          className={cn(
            "grid grid-cols-[100px_1fr] gap-6 border-t py-6 [&_h3]:font-semibold [&_p]:my-1.5 [&_p]:text-sm [&_small]:text-sm [&_small]:text-muted-foreground",
            "max-[640px]:grid-cols-1 max-[640px]:gap-2",
          )}
        >
          <div className="text-sm text-muted-foreground">
            27 Okt 2026
            <br />
            09.24
          </div>
          <div>
            <h3>Nilai draf diperbarui</h3>
            <p>Guru Contoh A · Matematika · Kelas 4 A</p>
            <p>Siswa Contoh 01 · Ulangan Harian 1 · 78 → 84</p>
            <small>
              Actor asli dan alasan perubahan mengikuti event berizin.
            </small>
          </div>
        </div>
        <div
          className={cn(
            "grid grid-cols-[100px_1fr] gap-6 border-t py-6 [&_h3]:font-semibold [&_p]:my-1.5 [&_p]:text-sm [&_small]:text-sm [&_small]:text-muted-foreground",
            "max-[640px]:grid-cols-1 max-[640px]:gap-2",
          )}
        >
          <div className="text-sm text-muted-foreground">
            27 Okt 2026
            <br />
            08.10
          </div>
          <div>
            <h3>Penilaian dibagikan</h3>
            <p>Guru Contoh A · Matematika · Kelas 4 A</p>
            <p>Ulangan Harian 1 · Draf → Dibagikan</p>
          </div>
        </div>
      </Section>
      <Section title="Varian inbox pengguna">
        <div
          className={cn(
            "flex items-center justify-between gap-6 border-t py-[22px] last:border-b [&_h3]:mt-2.5 [&_h3]:font-semibold [&_p]:mt-1 [&_p]:mb-0 [&_p]:text-sm",
            "max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-4",
          )}
        >
          <div>
            <Tag>Belum dibaca</Tag>
            <h3>Hasil Matematika tersedia</h3>
            <p>
              Aktivitas anak yang sudah dibagikan menuju Perkembangan Belajar;
              rapor menuju Rapor Online.
            </p>
          </div>
        </div>
        <p>
          Inbox milik sendiri, count dan read-state dari server. Jika realtime
          terputus, riwayat tidak ditampilkan sebagai kosong; reconnect
          mengambil ulang inbox tersimpan.
        </p>
      </Section>
      <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
        Contoh audit khusus lingkup Master. Portal wali tidak menerima event
        nilai draf atau field internal staf.
      </p>
    </>
  );
}

const content = {
  login: Login,
  jobs: Jobs,
  admin: Admin,
  policy: Policy,
  reports: Reports,
  decisions: Decisions,
  audit: Audit,
};

function DesignStudies() {
  const [menuOpen, setMenuOpen] = useState(false);
  const initial = new URLSearchParams(window.location.search).get("screen");
  const [screen, setScreen] = useState<Study>(
    studies.some((s) => s.id === initial) ? (initial as Study) : "jobs",
  );
  const mainRef = useRef<HTMLElement>(null);
  const selected = studies.find((item) => item.id === screen)!;
  const Content = content[screen];
  return (
    <div className="text-base leading-normal">
      <a
        className="fixed -top-24 left-3 z-[60] bg-background p-3 focus:top-2"
        href="#study-main"
      >
        Lewati ke isi utama
      </a>
      <div
        className={cn(
          "flex flex-wrap items-center gap-x-6 gap-y-2 border-b bg-accent px-7 py-4 text-sm max-[640px]:p-4 [&_a]:ml-auto [&_a]:flex [&_a]:min-h-11 [&_a]:items-center",
          "[&_a]:underline [&_a]:underline-offset-4 max-[640px]:[&_a]:ml-0",
        )}
      >
        <strong>Kajian visual · Register Akademik Terang</strong>
        <span>
          Data sintetis. Form dan tindakan nonaktif; navigasi kajian dapat
          digunakan.
        </span>
        <a href="./index.html">Buka prototipe interaktif →</a>
      </div>
      <div className="grid min-h-[calc(100vh-77px)] grid-cols-[224px_minmax(0,1fr)] max-[900px]:grid-cols-1">
        <aside
          className={cn(
            "border-r border-sidebar-border bg-sidebar px-4 py-8 text-sidebar-foreground max-[900px]:border-r-0 max-[900px]:border-b max-[900px]:px-6 max-[900px]:py-4",
            "max-[640px]:p-4 [&>p:last-child]:mx-3 [&>p:last-child]:mt-9 [&>p:last-child]:border-t [&>p:last-child]:border-sidebar-border",
            "[&>p:last-child]:pt-5 [&>p:last-child]:text-sidebar-muted max-[900px]:[&>p:last-child]:hidden",
          )}
        >
          <div className="grid gap-1.5 px-3 pb-8 max-[900px]:px-0 max-[900px]:pb-4 [&_strong]:text-xl [&_strong]:tracking-[-0.03em] [&_span]:text-sm [&_span]:text-sidebar-muted">
            <strong>MI Alghivari</strong>
            <span>Sistem Informasi Akademik</span>
          </div>
          <p className="px-3 pb-3 text-sm text-sidebar-muted max-[900px]:hidden">
            Keluarga layar
          </p>
          <Button
            variant="ghost"
            type="button"
            className={cn(
              "hidden text-sidebar-foreground hover:bg-sidebar-hover hover:text-sidebar-foreground focus-visible:outline-sidebar-ring",
              "max-[900px]:block max-[900px]:min-h-11 max-[900px]:w-full max-[900px]:rounded-md max-[900px]:border max-[900px]:border-sidebar-border",
              "max-[900px]:bg-sidebar-hover max-[900px]:px-3 max-[900px]:py-2 max-[900px]:text-left",
            )}
            aria-expanded={menuOpen}
            aria-controls="study-navigation"
            onClick={() => setMenuOpen(!menuOpen)}
          >
            {menuOpen
              ? "Tutup pilihan layar"
              : `Pilih layar · ${selected.label}`}
          </Button>
          <nav
            id="study-navigation"
            className={cn(
              "grid gap-1 max-[900px]:hidden max-[900px]:flex-wrap",
              "[&_button]:min-h-12 [&_button]:rounded [&_button]:p-3 [&_button]:text-left [&_button]:text-sm",
              "[&_button]:justify-start",
              "[&_button[aria-current]]:bg-sidebar-accent",
              "[&_button[aria-current]]:font-[650] [&_button[aria-current]]:text-sidebar-accent-foreground",
              menuOpen && "max-[900px]:mt-3 max-[900px]:flex",
            )}
            aria-label="Kajian visual"
          >
            {studies.map((item) => (
              <Button
                variant="ghost"
                className="text-sidebar-foreground hover:bg-sidebar-hover hover:text-sidebar-foreground focus-visible:outline-sidebar-ring"
                key={item.id}
                type="button"
                aria-current={screen === item.id ? "page" : undefined}
                onClick={() => {
                  setScreen(item.id);
                  setMenuOpen(false);
                  history.replaceState(null, "", `?screen=${item.id}`);
                  requestAnimationFrame(() => mainRef.current?.focus());
                }}
              >
                {item.label}
              </Button>
            ))}
          </nav>
          <p className="mt-5 text-sm leading-relaxed text-muted-foreground">
            Pelengkap empat layar interaktif. Ini spesimen komposisi, bukan
            aplikasi yang telah dibangun.
          </p>
        </aside>
        <div
          className={cn(
            "min-w-0 [&_main]:mx-auto [&_main]:max-w-[1200px] [&_main]:px-9 [&_main]:pt-8 [&_main]:pb-14 [&_main]:outline-none max-[640px]:[&_main]:px-4",
            "max-[640px]:[&_main]:pt-6 max-[640px]:[&_main]:pb-10",
          )}
        >
          <header className="flex min-h-14 flex-wrap items-center justify-between gap-3 px-9 py-3 text-sm text-muted-foreground max-[640px]:p-4">
            <span>{selected.context}</span>
            <span>Ganjil · 2026/2027</span>
          </header>
          <main ref={mainRef} id="study-main" tabIndex={-1}>
            <div className="mb-6 [&_p]:mb-1.5 [&_p]:text-sm [&_p]:text-muted-foreground">
              <p>Register Akademik</p>
              <h1>{selected.title}</h1>
            </div>
            <Content />
          </main>
        </div>
      </div>
    </div>
  );
}

createRoot(document.getElementById("root")!).render(<DesignStudies />);
