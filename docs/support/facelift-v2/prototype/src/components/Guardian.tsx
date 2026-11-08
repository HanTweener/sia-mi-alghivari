import type { GradeValues } from "../data";
import type { AttendanceValues } from "./Attendance";
import { useState } from "react";
import { Button } from "./ui/button";
import { Label } from "./ui/label";
import { NativeSelect } from "./ui/native-select";

export function Guardian({
  report,
  child,
  published,
  attendance,
  onProgress,
}: {
  report: boolean;
  child: string;
  published: GradeValues | null;
  attendance: AttendanceValues | null;
  onProgress: () => void;
}) {
  const [subject, setSubject] = useState("all");
  const name = child === "D-001" ? "Alya Contoh" : "Bagas Contoh";
  const currentGrade = published?.[child];
  const assessments = [
    {
      title: "PH-01 · Bilangan cacah",
      date: "8 Oktober 2026",
      grade: child === "D-001" ? "88" : "76",
      subject: "Matematika",
    },
    {
      title: "PH-02 · Operasi hitung",
      date: "15 Oktober 2026",
      grade: child === "D-001" ? "80" : "84",
      subject: "Matematika",
    },
    ...(currentGrade !== undefined && currentGrade !== ""
      ? [
          {
            title: "PH-03 · Pecahan sederhana",
            date: "Dibagikan pada sesi demo ini",
            grade: currentGrade,
            subject: "Matematika",
          },
        ]
      : []),
    {
      title: "Membaca nyaring",
      date: "20 Oktober 2026",
      grade: child === "D-001" ? "86" : "79",
      subject: "Bahasa Indonesia",
    },
    ...(attendance?.[child]
      ? [
          {
            title: "Presensi · Pertemuan 12",
            date: "22 Oktober 2026 · disahkan pada sesi demo",
            grade: attendance[child],
            subject: "Matematika",
          },
        ]
      : []),
  ];
  const visible = assessments.filter(
    (assessment) => subject === "all" || assessment.subject === subject,
  );
  return (
    <div>
      <div className="mb-5 flex items-start justify-between gap-4 max-[760px]:mb-4 max-[760px]:flex-col max-[760px]:gap-2">
        <div>
          <p className="mb-1 text-sm font-semibold text-muted-foreground">
            {report ? "Rapor semester" : "Perkembangan belajar"}
          </p>
          <h1>
            {report
              ? "Rapor belum tersedia"
              : `Catatan belajar ${name.split(" ")[0]}`}
          </h1>
          <p className="mt-1.5 text-sm text-muted-foreground">
            {name} · Kelas IV A · Semester ganjil 2026/2027
          </p>
        </div>
      </div>
      {report ? (
        <section
          className="rounded-lg border bg-background px-9 py-[52px] max-[760px]:px-6 max-[760px]:py-[30px]"
          aria-labelledby="report-empty-title"
        >
          <div
            className="mb-[30px] h-12 w-[39px] rounded-[2px] border border-input px-2 py-[13px] [&>span]:mb-[7px] [&>span]:block [&>span]:h-px [&>span]:bg-input"
            aria-hidden="true"
          >
            <span />
            <span />
            <span />
          </div>
          <h2 id="report-empty-title" className="mb-4 text-[19px]">
            Belum ada rapor yang diterbitkan
          </h2>
          <p className="mt-4 max-w-[540px] text-sm leading-[1.9]">
            Rapor {name} akan tampil di sini setelah diterbitkan oleh pihak
            sekolah. Nilai penilaian harian dapat dilihat melalui Perkembangan.
          </p>
          <p className="mt-4 max-w-[540px] text-sm leading-[1.9] text-muted-foreground">
            Prototipe hanya mencakup keadaan kosong. Tidak ada berkas PDF atau
            tombol unduh semu.
          </p>
          <Button className="mt-6" variant="outline" onClick={onProgress}>
            Lihat Perkembangan Belajar
          </Button>
        </section>
      ) : (
        <>
          <p className="max-w-[650px] text-sm leading-6 text-muted-foreground">
            Hasil yang sudah dibagikan guru dan presensi yang disahkan. Rapor
            semester tersedia secara terpisah.
          </p>
          <Label className="mt-4 flex flex-wrap items-center gap-3 text-sm font-normal leading-normal">
            Mata pelajaran
            <NativeSelect
              value={subject}
              onChange={(event) => setSubject(event.target.value)}
            >
              <option value="all">Semua mata pelajaran</option>
              <option>Matematika</option>
              <option>Bahasa Indonesia</option>
            </NativeSelect>
          </Label>
          <section
            className="mt-4 overflow-hidden rounded-lg border bg-background"
            aria-labelledby="progress-title"
          >
            <div className="flex min-h-16 items-center justify-between gap-4 px-5 py-3 max-[760px]:flex-col max-[760px]:items-stretch max-[760px]:gap-2 max-[760px]:px-4">
              <div>
                <h2 id="progress-title">Aktivitas yang dibagikan</h2>
                <p className="mt-1 text-sm text-muted-foreground">
                  {visible.length} aktivitas ditampilkan · data sintetis
                </p>
              </div>
              <span className="text-sm text-muted-foreground">
                Nilai / 100 atau kehadiran
              </span>
            </div>
            <ul>
              {visible.map((assessment) => (
                <li
                  key={assessment.title}
                  className="flex min-h-20 items-center justify-between gap-5 border-t px-5 py-4 max-[760px]:px-4"
                >
                  <div className="min-w-0">
                    <h3 className="text-sm font-semibold">
                      {assessment.title}
                    </h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {assessment.subject} · {assessment.date}
                    </p>
                  </div>
                  <strong className="shrink-0 text-right text-2xl font-semibold text-primary tabular-nums">
                    {assessment.grade}
                  </strong>
                </li>
              ))}
            </ul>
          </section>
          <p className="mt-5 text-sm text-muted-foreground">
            Nilai yang belum dibagikan tidak ditampilkan. Tidak ada peringkat
            atau rata-rata kelas pada tampilan orang tua.
          </p>
        </>
      )}
    </div>
  );
}
