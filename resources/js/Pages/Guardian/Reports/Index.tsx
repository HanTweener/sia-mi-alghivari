import React from "react";
import { Link } from "@inertiajs/react";
import {
  ArrowLeft,
  ArrowRight,
  BookOpen,
  Calendar,
  CheckCircle2,
  Download,
  FileText,
  GraduationCap,
  Sparkles,
  TrendingUp,
  User,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface SiblingChild {
  id: number;
  nis: string;
  name: string;
}

interface ReportItem {
  id: number;
  revision: number;
  published_at: string | null;
  published_at_raw: string | null;
  classroom_name: string;
  term: {
    id: number;
    name: string;
    semester: number;
    academic_year: string;
  };
  homeroom_teacher: string;
  summary: {
    total_subjects: number;
    passed_subjects: number;
    average_score: string | null;
  };
  has_file: boolean;
}

interface Props {
  student: {
    id: number;
    nis: string;
    name: string;
    gender: string;
  };
  classroom: {
    id: number;
    name: string;
    grade_level: number;
    section: string;
  } | null;
  reports: ReportItem[];
  siblingChildren: SiblingChild[];
}

export default function Index({
  student,
  classroom,
  reports = [],
  siblingChildren = [],
}: Props) {
  return (
    <AppLayout
      title={`Rapor Semester: ${student.name}`}
      contextHeader={{
        title: `Rapor Semester: ${student.name}`,
        badge: `NIS: ${student.nis}`,
        description: classroom ? `Kelas ${classroom.name}` : undefined,
      }}
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Link
            href="/children"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Daftar Anak
          </Link>
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
            <span className="flex items-center gap-2 border-b-2 border-teal-700 py-3 font-bold text-teal-800">
              <GraduationCap className="h-4 w-4" />
              Rapor Semester ({reports.length})
            </span>
          </div>
        </div>

        {/* Zero-Leak Empty State */}
        {reports.length === 0 ? (
          <div className="rounded-2xl border border-stone-200 bg-white p-8 sm:p-12 text-center shadow-sm max-w-xl mx-auto space-y-4">
            <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
              <FileText className="h-7 w-7" />
            </div>
            <div className="space-y-1.5">
              <h3 className="text-lg font-bold text-stone-900">
                Rapor Resmi Belum Tersedia
              </h3>
              <p className="text-xs text-stone-500 leading-relaxed max-w-md mx-auto">
                Dokumen rapor semester resmi belum dipublikasikan oleh pihak
                madrasah. Anda dapat memantau capaian nilai tugas, ulangan
                harian, dan rekap presensi secara langsung melalui portal
                perkembangan belajar harian.
              </p>
            </div>
            <div className="pt-2">
              <Link
                href={`/children/${student.id}/progress`}
                className="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-teal-800 transition"
              >
                <TrendingUp className="h-4 w-4" />
                Lihat Catatan Perkembangan Belajar
              </Link>
            </div>
          </div>
        ) : (
          <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <h2 className="text-lg font-bold text-stone-900">
                  Arsip Rapor Semester Resmi
                </h2>
                <p className="text-xs text-stone-500">
                  Dokumen laporan hasil belajar peserta didik yang telah
                  disahkan oleh madrasah.
                </p>
              </div>
              <span className="self-start sm:self-auto rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-800">
                {reports.length} Dokumen Tersedia
              </span>
            </div>

            <div className="grid grid-cols-1 gap-5">
              {reports.map((report) => (
                <div
                  key={report.id}
                  className="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm hover:border-teal-300 transition space-y-5"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-100 pb-4">
                    <div className="flex items-start gap-3.5">
                      <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-800 font-bold">
                        <GraduationCap className="h-6 w-6" />
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <h3 className="text-base font-bold text-stone-900">
                            {report.term.academic_year} — Semester{" "}
                            {report.term.semester}
                          </h3>
                          <span className="rounded bg-teal-50 border border-teal-200 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-teal-800">
                            {report.term.name}
                          </span>
                        </div>
                        <p className="text-xs text-stone-500 mt-0.5">
                          Rombel Kelas {report.classroom_name} • Wali Kelas:{" "}
                          <span className="font-medium text-stone-700">
                            {report.homeroom_teacher}
                          </span>
                        </p>
                      </div>
                    </div>

                    {report.published_at && (
                      <div className="text-left sm:text-right">
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800">
                          <CheckCircle2 className="h-3.5 w-3.5" />
                          Dipublikasikan: {report.published_at}
                        </span>
                      </div>
                    )}
                  </div>

                  {/* Summary Metric Badges */}
                  <div className="grid grid-cols-3 gap-3 rounded-xl bg-stone-50/80 p-3.5 border border-stone-100">
                    <div className="text-center">
                      <span className="text-[10px] uppercase font-bold text-stone-400">
                        Total Mapel
                      </span>
                      <p className="text-base font-bold text-stone-900">
                        {report.summary.total_subjects}
                      </p>
                    </div>
                    <div className="text-center border-x border-stone-200">
                      <span className="text-[10px] uppercase font-bold text-stone-400">
                        Tuntas KKM
                      </span>
                      <p className="text-base font-bold text-emerald-700">
                        {report.summary.passed_subjects} /{" "}
                        {report.summary.total_subjects}
                      </p>
                    </div>
                    <div className="text-center">
                      <span className="text-[10px] uppercase font-bold text-stone-400">
                        Rerata Nilai
                      </span>
                      <p className="text-base font-bold text-teal-800">
                        {report.summary.average_score || "-"}
                      </p>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2.5 pt-1">
                    <a
                      href={`/children/${student.id}/report-cards/${report.id}/pdf`}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex items-center justify-center gap-1.5 rounded-xl border border-stone-200 bg-white px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 transition"
                    >
                      <Download className="h-4 w-4 text-stone-500" />
                      Unduh Berkas PDF
                    </a>

                    <Link
                      href={`/children/${student.id}/report-cards/${report.id}`}
                      className="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-teal-800 transition"
                    >
                      Buka Rincian Rapor
                      <ArrowRight className="h-4 w-4" />
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
