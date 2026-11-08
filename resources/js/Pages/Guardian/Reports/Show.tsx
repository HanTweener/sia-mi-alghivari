import React from "react";
import { Link } from "@inertiajs/react";
import {
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Clock,
  Download,
  GraduationCap,
  MessageSquare,
  ShieldCheck,
  TrendingUp,
  User,
  Users,
  XCircle,
  Award,
  Sparkles,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface SiblingChild {
  id: number;
  nis: string;
  name: string;
}

interface ComponentScore {
  component_id: number;
  code: string;
  name: string;
  weight_percentage: string;
  score_average: string | null;
  contribution_rounded: string | null;
}

interface ReportItem {
  id: number;
  subject: {
    id: number;
    name: string;
    code: string;
  };
  kkm: string;
  final_score: string | null;
  kkm_status: "tuntas" | "belum_tuntas";
  component_breakdown: ComponentScore[];
}

interface AttendanceSummary {
  present: number;
  excused: number;
  sick: number;
  absent: number;
  total_sessions: number;
  attendance_rate: string | null;
}

interface IdentitySnapshot {
  student: {
    id: number;
    name: string;
    nis: string;
    gender: string;
  };
  classroom: {
    id: number;
    name: string;
    grade_level: number;
    section: string;
  };
  term: {
    id: number;
    name: string;
    semester: number;
    academic_year: string;
  };
  homeroom_teacher: {
    id: number | null;
    name: string;
    nip: string | null;
  };
  principal: {
    id: number | null;
    name: string;
    nip: string | null;
  };
}

interface ExtracurricularItem {
  id: number;
  activity_name: string;
  predicate: "A" | "B" | "C" | "D";
  predicate_label: string;
  description: string;
}

interface P5Item {
  id: number;
  project_title: string;
  dimension: string;
  predicate: "BB" | "MB" | "BSH" | "SAB";
  predicate_label: string;
  description: string;
}

interface Props {
  student: {
    id: number;
    nis: string;
    name: string;
    gender: string;
  };
  report: {
    id: number;
    revision: number;
    status: string;
    published_at: string | null;
    published_at_raw: string | null;
    homeroom_note: string | null;
    attendance_summary: AttendanceSummary | null;
    identity_snapshot: IdentitySnapshot | null;
    items: ReportItem[];
    extracurriculars?: ExtracurricularItem[];
    p5_assessments?: P5Item[];
  };
  classroom: {
    id: number;
    name: string;
    grade_level: number;
    section: string;
  };
  term: {
    id: number;
    name: string;
    semester: number;
    academic_year: string;
  };
  siblingChildren: SiblingChild[];
}

export default function Show({
  student,
  report,
  classroom,
  term,
  siblingChildren = [],
}: Props) {
  const attendance = report.attendance_summary || {
    present: 0,
    excused: 0,
    sick: 0,
    absent: 0,
    total_sessions: 0,
    attendance_rate: "0.0",
  };

  const homeroomTeacher =
    report.identity_snapshot?.homeroom_teacher?.name || "Wali Kelas";
  const principalName =
    report.identity_snapshot?.principal?.name || "Kepala Madrasah";

  const totalItems = report.items.length;
  const passedItems = report.items.filter(
    (i) => i.kkm_status === "tuntas",
  ).length;

  return (
    <AppLayout
      title={`Rapor ${term.name} - ${student.name}`}
      contextHeader={{
        title: `Rapor Resmi: ${term.academic_year} Semester ${term.semester}`,
        badge: `NIS: ${student.nis}`,
        description: `Kelas ${classroom.name} • ${student.name}`,
      }}
    >
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <Link
            href={`/children/${student.id}/report-cards`}
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Daftar Rapor
          </Link>
          <a
            href={`/children/${student.id}/report-cards/${report.id}/pdf`}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary/90 transition"
          >
            <Download className="h-4 w-4" />
            Unduh Berkas PDF Resmi
          </a>
        </div>

        {/* Sibling Switcher */}
        {siblingChildren.length > 1 && (
          <div className="border-b border-stone-200 bg-white px-6 py-2">
            <div className="mx-auto flex max-w-5xl items-center gap-2 overflow-x-auto text-xs">
              <span className="font-semibold text-stone-400 uppercase tracking-wider text-[10px] shrink-0">
                Pilih Anak:
              </span>
              {siblingChildren.map((sib) => {
                const isCurrent = sib.id === student.id;
                return isCurrent ? (
                  <span
                    key={sib.id}
                    className="rounded-full bg-teal-700 px-3 py-1 font-bold text-white shrink-0"
                  >
                    {sib.name}
                  </span>
                ) : (
                  <Link
                    key={sib.id}
                    href={`/children/${sib.id}/report-cards`}
                    className="rounded-full bg-stone-100 px-3 py-1 font-medium text-stone-600 hover:bg-stone-200 shrink-0 transition"
                  >
                    {sib.name}
                  </Link>
                );
              })}
            </div>
          </div>
        )}

        {/* Navigation Sub-Tabs */}
        <div className="border-b border-stone-200 bg-white px-6">
          <div className="mx-auto flex max-w-5xl gap-6 text-sm">
            <Link
              href={`/children/${student.id}/progress`}
              className="flex items-center gap-2 border-b-2 border-transparent py-3 font-medium text-stone-500 hover:text-stone-800 transition"
            >
              <TrendingUp className="h-4 w-4" />
              Perkembangan Belajar
            </Link>
            <Link
              href={`/children/${student.id}/report-cards`}
              className="flex items-center gap-2 border-b-2 border-teal-700 py-3 font-bold text-teal-800"
            >
              <GraduationCap className="h-4 w-4" />
              Rapor Semester
            </Link>
          </div>
        </div>

        {/* Verification Banner */}
        <div className="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 sm:p-5 text-emerald-950 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
          <div className="flex items-start gap-3">
            <ShieldCheck className="h-5 w-5 text-emerald-700 shrink-0 mt-0.5" />
            <div>
              <p className="font-bold text-sm">Dokumen Rapor Resmi Disahkan</p>
              <p className="text-xs text-emerald-800 mt-0.5">
                Rapor ini telah diverifikasi dan dipublikasikan secara resmi
                oleh madrasah pada {report.published_at}. Seluruh nilai dan
                catatan tersimpan permanen.
              </p>
            </div>
          </div>
          <a
            href={`/children/${student.id}/report-cards/${report.id}/pdf`}
            target="_blank"
            rel="noreferrer"
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 shrink-0 transition"
          >
            <Download className="h-4 w-4" />
            Unduh Berkas Resmi (PDF)
          </a>
        </div>

        {/* Identity & Metadata Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-2">
            <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
              Identitas Peserta Didik
            </span>
            <div className="flex items-center gap-3 pt-1">
              <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-800 font-bold text-lg">
                {student.name.charAt(0)}
              </div>
              <div>
                <p className="text-sm font-bold text-stone-900">
                  {student.name}
                </p>
                <p className="text-xs text-stone-500">
                  NIS: {student.nis} •{" "}
                  {student.gender === "L" ? "Laki-laki" : "Perempuan"}
                </p>
              </div>
            </div>
          </div>

          <div className="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-2">
            <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
              Periode Akademik & Rombel
            </span>
            <div className="space-y-1 text-xs pt-1">
              <div className="flex justify-between">
                <span className="text-stone-500">Tahun Ajaran:</span>
                <span className="font-semibold text-stone-900">
                  {term.academic_year}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Semester:</span>
                <span className="font-semibold text-stone-900">
                  Semester {term.semester} ({term.name})
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Rombongan Belajar:</span>
                <span className="font-semibold text-stone-900">
                  Kelas {classroom.name}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Wali Kelas:</span>
                <span className="font-semibold text-stone-900">
                  {homeroomTeacher}
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Academic Performance Table */}
        <div className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
          <div className="border-b border-stone-200 bg-stone-50/70 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h3 className="text-sm font-bold text-stone-900">
                Tabel Capaian Nilai Hasil Belajar
              </h3>
              <p className="text-xs text-stone-500">
                Ringkasan nilai akhir per mata pelajaran dan perbandingan batas
                ketuntasan (KKM).
              </p>
            </div>
            <span className="text-xs font-semibold text-stone-600">
              Ketuntasan:{" "}
              <strong className="text-emerald-700">
                {passedItems} dari {totalItems} Mapel
              </strong>
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-stone-700">
              <thead className="border-b border-stone-200 bg-stone-50/50 text-[11px] font-bold uppercase tracking-wider text-stone-500">
                <tr>
                  <th className="px-5 py-3.5 text-center w-12">No</th>
                  <th className="px-5 py-3.5">Mata Pelajaran</th>
                  <th className="px-5 py-3.5 text-center w-20">KKM</th>
                  <th className="px-5 py-3.5 text-center w-24">Nilai Akhir</th>
                  <th className="px-5 py-3.5 text-center w-32">Status</th>
                  <th className="px-5 py-3.5">Rincian Komponen Penilaian</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {report.items.map((item, idx) => {
                  const isPassed = item.kkm_status === "tuntas";
                  return (
                    <tr
                      key={item.id}
                      className="hover:bg-stone-50/60 transition"
                    >
                      <td className="px-5 py-3.5 text-center text-stone-400 font-medium">
                        {idx + 1}
                      </td>
                      <td className="px-5 py-3.5">
                        <p className="font-bold text-stone-900">
                          {item.subject.name}
                        </p>
                        <p className="text-[10px] text-stone-400">
                          Kode: {item.subject.code}
                        </p>
                      </td>
                      <td className="px-5 py-3.5 text-center font-mono font-semibold text-stone-600">
                        {Number(item.kkm).toFixed(0)}
                      </td>
                      <td className="px-5 py-3.5 text-center">
                        <span
                          className={`font-mono text-sm font-bold ${
                            isPassed ? "text-emerald-700" : "text-rose-600"
                          }`}
                        >
                          {item.final_score
                            ? Number(item.final_score).toFixed(2)
                            : "-"}
                        </span>
                      </td>
                      <td className="px-5 py-3.5 text-center">
                        {isPassed ? (
                          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                            <CheckCircle2 className="h-3 w-3" />
                            Tuntas
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-[11px] font-semibold text-rose-700">
                            <XCircle className="h-3 w-3" />
                            Belum Tuntas
                          </span>
                        )}
                      </td>
                      <td className="px-5 py-3.5">
                        <div className="flex flex-wrap gap-1.5">
                          {item.component_breakdown.map((c, cIdx) => (
                            <span
                              key={cIdx}
                              className="inline-flex items-center rounded-md bg-stone-100 px-2 py-0.5 text-[10px] text-stone-700"
                              title={`${c.name} (${Number(c.weight_percentage)}%): Rerata ${c.score_average || "0.00"}, Kontribusi ${c.contribution_rounded || "0.00"}`}
                            >
                              <span className="font-semibold mr-1">
                                {c.name}:
                              </span>
                              {c.score_average || "0.00"}
                            </span>
                          ))}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>

        {/* Attendance Recap */}
        <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm space-y-4">
          <div className="flex items-center justify-between border-b border-stone-100 pb-3">
            <div>
              <h3 className="text-sm font-bold text-stone-900">
                Rekapitulasi Kehadiran Siswa
              </h3>
              <p className="text-xs text-stone-500">
                Rekap akumulasi kehadiran selama periode semester.
              </p>
            </div>
            <span className="rounded-full bg-teal-50 border border-teal-200 px-3 py-1 text-xs font-bold text-teal-800">
              Persentase Kehadiran: {attendance.attendance_rate || "0.0"}%
            </span>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
            <div className="rounded-xl bg-stone-50 p-3 border border-stone-100">
              <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Hadir (H)
              </span>
              <p className="text-lg font-bold text-emerald-700 mt-0.5">
                {attendance.present}
              </p>
            </div>
            <div className="rounded-xl bg-stone-50 p-3 border border-stone-100">
              <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Izin (I)
              </span>
              <p className="text-lg font-bold text-blue-700 mt-0.5">
                {attendance.excused}
              </p>
            </div>
            <div className="rounded-xl bg-stone-50 p-3 border border-stone-100">
              <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Sakit (S)
              </span>
              <p className="text-lg font-bold text-amber-700 mt-0.5">
                {attendance.sick}
              </p>
            </div>
            <div className="rounded-xl bg-stone-50 p-3 border border-stone-100">
              <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Alpa (A)
              </span>
              <p className="text-lg font-bold text-rose-700 mt-0.5">
                {attendance.absent}
              </p>
            </div>
            <div className="col-span-2 sm:col-span-1 rounded-xl bg-stone-50 p-3 border border-stone-100">
              <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                Total Pertemuan
              </span>
              <p className="text-lg font-bold text-stone-900 mt-0.5">
                {attendance.total_sessions}
              </p>
            </div>
          </div>
        </div>

        {/* Extracurricular Activities */}
        {report.extracurriculars && report.extracurriculars.length > 0 && (
          <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm space-y-4">
            <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
              <Award className="h-4 w-4 text-emerald-700" />
              <div>
                <h3 className="text-sm font-bold text-stone-900">
                  Kegiatan Ekstrakurikuler
                </h3>
                <p className="text-xs text-stone-500">
                  Capaian dan keaktifan kegiatan non-akademik siswa.
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-stone-700">
                <thead className="border-b border-stone-200 bg-stone-50/70 text-[11px] font-semibold uppercase tracking-wider text-stone-500">
                  <tr>
                    <th className="py-2.5 px-3 w-10 text-center">No</th>
                    <th className="py-2.5 px-3 w-48">Kegiatan</th>
                    <th className="py-2.5 px-3 w-32 text-center">Predikat</th>
                    <th className="py-2.5 px-3">Capaian & Catatan Pembina</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-stone-100">
                  {report.extracurriculars.map((extra, idx) => (
                    <tr key={extra.id} className="hover:bg-stone-50/50">
                      <td className="py-3 px-3 text-center text-stone-400 font-mono">
                        {idx + 1}
                      </td>
                      <td className="py-3 px-3 font-bold text-stone-900">
                        {extra.activity_name}
                      </td>
                      <td className="py-3 px-3 text-center">
                        <span
                          className={`inline-block rounded px-2.5 py-0.5 text-xs font-bold ${
                            extra.predicate === "A"
                              ? "bg-emerald-100 text-emerald-800"
                              : extra.predicate === "B"
                                ? "bg-teal-100 text-teal-800"
                                : extra.predicate === "C"
                                  ? "bg-amber-100 text-amber-800"
                                  : "bg-rose-100 text-rose-800"
                          }`}
                        >
                          {extra.predicate} ({extra.predicate_label})
                        </span>
                      </td>
                      <td className="py-3 px-3 text-stone-700 leading-relaxed">
                        {extra.description}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* P5-PRA Kokurikuler Section */}
        {report.p5_assessments && report.p5_assessments.length > 0 && (
          <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm space-y-4">
            <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
              <Sparkles className="h-4 w-4 text-amber-600" />
              <div>
                <h3 className="text-sm font-bold text-stone-900">
                  Projek Penguatan Profil Pelajar Pancasila & Rahmatan Lil
                  Alamin (P5-PRA)
                </h3>
                <p className="text-xs text-stone-500">
                  Penilaian kokurikuler tematik Kurikulum Merdeka.
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-stone-700">
                <thead className="border-b border-stone-200 bg-stone-50/70 text-[11px] font-semibold uppercase tracking-wider text-stone-500">
                  <tr>
                    <th className="py-2.5 px-3 w-10 text-center">No</th>
                    <th className="py-2.5 px-3 w-52">Projek P5-PRA</th>
                    <th className="py-2.5 px-3 w-48">Dimensi Profil</th>
                    <th className="py-2.5 px-3 w-36 text-center">Capaian</th>
                    <th className="py-2.5 px-3">Catatan Perkembangan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-stone-100">
                  {report.p5_assessments.map((p5, idx) => (
                    <tr key={p5.id} className="hover:bg-stone-50/50">
                      <td className="py-3 px-3 text-center text-stone-400 font-mono">
                        {idx + 1}
                      </td>
                      <td className="py-3 px-3 font-bold text-stone-900">
                        {p5.project_title}
                      </td>
                      <td className="py-3 px-3 text-stone-600 font-medium">
                        {p5.dimension}
                      </td>
                      <td className="py-3 px-3 text-center">
                        <span
                          className={`inline-block rounded px-2.5 py-0.5 text-xs font-bold ${
                            p5.predicate === "SAB"
                              ? "bg-purple-100 text-purple-800"
                              : p5.predicate === "BSH"
                                ? "bg-emerald-100 text-emerald-800"
                                : p5.predicate === "MB"
                                  ? "bg-amber-100 text-amber-800"
                                  : "bg-stone-200 text-stone-700"
                          }`}
                        >
                          {p5.predicate} ({p5.predicate_label})
                        </span>
                      </td>
                      <td className="py-3 px-3 text-stone-700 leading-relaxed">
                        {p5.description}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Homeroom Teacher's Note */}
        <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm space-y-3">
          <div className="flex items-center gap-2">
            <MessageSquare className="h-4 w-4 text-teal-700" />
            <h3 className="text-sm font-bold text-stone-900">
              Catatan & Masukan Wali Kelas
            </h3>
          </div>
          <div className="rounded-xl border border-stone-200 bg-stone-50/70 p-4 text-xs leading-relaxed text-stone-800">
            {report.homeroom_note ? (
              <p className="italic">"{report.homeroom_note}"</p>
            ) : (
              <p className="text-stone-400 italic">
                Tidak ada catatan khusus dari wali kelas.
              </p>
            )}
          </div>
        </div>

        {/* Formal Endorsement & Signatures */}
        <div className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 text-center text-xs text-stone-700">
            <div className="space-y-16">
              <p className="font-medium text-stone-500">
                Mengetahui,
                <br />
                <strong>Wali Kelas</strong>
              </p>
              <div>
                <p className="font-bold underline text-stone-900">
                  {homeroomTeacher}
                </p>
                <p className="text-[11px] text-stone-400">
                  NIP: {report.identity_snapshot?.homeroom_teacher?.nip || "-"}
                </p>
              </div>
            </div>

            <div className="space-y-16">
              <p className="font-medium text-stone-500">
                Mengetahui,
                <br />
                <strong>Kepala Madrasah</strong>
              </p>
              <div>
                <p className="font-bold underline text-stone-900">
                  {principalName}
                </p>
                <p className="text-[11px] text-stone-400">
                  NIP: {report.identity_snapshot?.principal?.nip || "-"}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
