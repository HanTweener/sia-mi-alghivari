import React, { useState, useMemo } from "react";
import { Link, router } from "@inertiajs/react";
import {
  ArrowLeft,
  CheckCircle2,
  AlertTriangle,
  XCircle,
  FileText,
  User,
  BookOpen,
  Filter,
  Search,
  ChevronDown,
  ChevronRight,
  ShieldAlert,
  Info,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface TermInfo {
  id: number;
  name: string;
  semester: number;
  academic_year?: string;
}

interface HomeroomInfo {
  id: number;
  name: string;
  nip: string | null;
}

interface ClassroomInfo {
  id: number;
  name: string;
  grade_level: number;
  section: string;
  term: TermInfo;
  homeroom: HomeroomInfo | null;
}

interface PolicyComponent {
  id: number;
  code: string;
  name: string;
  weight_percentage: string;
}

interface PolicyInfo {
  id: number;
  revision: number;
  kkm: string;
  components: PolicyComponent[];
}

interface SubjectItem {
  class_subject_id: number;
  subject_id: number;
  subject_name: string;
  subject_code: string;
  kkm: string;
  final_score: string | null;
  kkm_status: string;
  is_complete: boolean;
  missing_components: string[];
  attendance: {
    total_sessions: number;
    present: number;
    excused: number;
    sick: number;
    absent: number;
    rate: string | null;
  };
}

interface ExistingReport {
  id: number;
  status: string;
  availability: string;
  revision: number;
  version: number;
  source_version: string | null;
  homeroom_note: string | null;
  finalized_at: string | null;
  published_at: string | null;
}

interface StudentEvaluation {
  enrollment_id: number;
  student: {
    id: number;
    nis: string;
    name: string;
    gender: string;
  };
  is_complete: boolean;
  can_finalize: boolean;
  source_version: string;
  blocked_reasons: string[];
  attendance_summary: {
    total_sessions: number;
    present: number;
    excused: number;
    sick: number;
    absent: number;
    rate: string | null;
  };
  subjects: SubjectItem[];
  existing_report: ExistingReport | null;
}

interface Props {
  classroom: ClassroomInfo;
  policy: PolicyInfo | null;
  summary: {
    total_students: number;
    complete_students: number;
    incomplete_students: number;
    is_all_complete: boolean;
  };
  classSubjects: {
    id: number;
    subject_id: number;
    code: string;
    name: string;
  }[];
  students: StudentEvaluation[];
}

export default function ClassroomPreview({
  classroom,
  policy,
  summary,
  classSubjects,
  students,
}: Props) {
  const [search, setSearch] = useState("");
  const [filterMode, setFilterMode] = useState<
    "all" | "incomplete" | "complete"
  >("all");
  const [expandedStudents, setExpandedStudents] = useState<
    Record<number, boolean>
  >({});

  const toggleExpand = (enrollmentId: number) => {
    setExpandedStudents((prev) => ({
      ...prev,
      [enrollmentId]: !prev[enrollmentId],
    }));
  };

  const filteredStudents = useMemo(() => {
    return students.filter((s) => {
      const matchSearch =
        s.student.name.toLowerCase().includes(search.toLowerCase()) ||
        s.student.nis.toLowerCase().includes(search.toLowerCase());

      if (!matchSearch) return false;

      if (filterMode === "complete") return s.is_complete;
      if (filterMode === "incomplete") return !s.is_complete;
      return true;
    });
  }, [students, search, filterMode]);

  return (
    <AppLayout
      title={`Pratinjau Rapor: ${classroom.name}`}
      contextHeader={{
        title: `Pratinjau Rapor: ${classroom.name}`,
        badge: "SCR-08",
        description: `Wali Kelas: ${classroom.homeroom?.name || "Belum Ditugaskan"} • Semester ${classroom.term.semester} (${classroom.term.academic_year || ""})`,
      }}
    >
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <Link
            href="/academic/classrooms"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Daftar Rombel
          </Link>

          {summary.complete_students > 0 && (
            <Button
              type="button"
              onClick={() =>
                router.post(
                  `/academic/classrooms/${classroom.id}/report-cards`,
                  {},
                  { preserveScroll: true },
                )
              }
              className="inline-flex items-center gap-1.5"
            >
              <FileText className="h-4 w-4" />
              Buat Draf Rapor ({summary.complete_students} Siswa Lengkap)
            </Button>
          )}
        </div>
        {/* Class Overview & Policy Cards */}
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {/* Classroom & Homeroom Info */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
                Informasi Rombel
              </span>
              <span className="rounded bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">
                Kelas {classroom.grade_level}-{classroom.section}
              </span>
            </div>
            <div className="mt-3 flex items-start gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                <User className="h-5 w-5" />
              </div>
              <div>
                <p className="text-xs text-stone-500">Wali Kelas</p>
                <p className="text-sm font-bold text-stone-900">
                  {classroom.homeroom?.name || "Belum Ditugaskan"}
                </p>
                {classroom.homeroom?.nip && (
                  <p className="text-xs text-stone-400">
                    NIP: {classroom.homeroom.nip}
                  </p>
                )}
              </div>
            </div>
            <div className="mt-4 pt-3 border-t border-stone-100 flex items-center justify-between text-xs text-stone-600">
              <span>Mata Pelajaran Aktif:</span>
              <span className="font-semibold text-stone-800">
                {classSubjects.length} Mapel
              </span>
            </div>
          </div>

          {/* Active Grading Policy */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
                Kebijakan Penilaian Aktif
              </span>
              {policy ? (
                <span className="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                  Revisi #{policy.revision}
                </span>
              ) : (
                <span className="rounded bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">
                  Tidak Ada
                </span>
              )}
            </div>

            {policy ? (
              <div className="mt-3 space-y-2">
                <div className="flex items-baseline justify-between">
                  <span className="text-xs text-stone-500">
                    Standar KKM Sekolah:
                  </span>
                  <span className="text-base font-bold text-emerald-800">
                    {policy.kkm}
                  </span>
                </div>
                <div className="flex flex-wrap gap-1.5 pt-1">
                  {policy.components.map((comp) => (
                    <span
                      key={comp.id}
                      className="inline-flex items-center rounded-md bg-stone-100 px-2 py-0.5 text-[11px] font-medium text-stone-700"
                    >
                      {comp.name}: {Number(comp.weight_percentage)}%
                    </span>
                  ))}
                </div>
              </div>
            ) : (
              <div className="mt-3 flex items-center gap-2 text-xs text-rose-600">
                <AlertTriangle className="h-4 w-4" />
                <span>
                  Belum ada grading policy aktif. Rapor tidak dapat
                  difinalisasi.
                </span>
              </div>
            )}
          </div>

          {/* Readiness Status */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div className="flex items-center justify-between">
              <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
                Kesiapan Rapor
              </span>
              <span
                className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold ${
                  summary.is_all_complete
                    ? "bg-emerald-100 text-emerald-800"
                    : "bg-amber-100 text-amber-800"
                }`}
              >
                {summary.is_all_complete ? (
                  <>
                    <CheckCircle2 className="h-3.5 w-3.5" /> Siap Finalisasi
                  </>
                ) : (
                  <>
                    <AlertTriangle className="h-3.5 w-3.5" /> Perlu Kelengkapan
                  </>
                )}
              </span>
            </div>

            <div className="mt-4 grid grid-cols-3 gap-2 text-center">
              <div className="rounded-lg bg-stone-50 p-2.5">
                <p className="text-xs text-stone-500">Total Siswa</p>
                <p className="text-lg font-bold text-stone-900">
                  {summary.total_students}
                </p>
              </div>
              <div className="rounded-lg bg-emerald-50/70 p-2.5">
                <p className="text-xs text-emerald-700">Lengkap</p>
                <p className="text-lg font-bold text-emerald-800">
                  {summary.complete_students}
                </p>
              </div>
              <div className="rounded-lg bg-rose-50/70 p-2.5">
                <p className="text-xs text-rose-700">Belum</p>
                <p className="text-lg font-bold text-rose-800">
                  {summary.incomplete_students}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Notice for Incomplete Classrooms */}
        {!summary.is_all_complete && (
          <div className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/80 p-4 text-amber-900">
            <Info className="h-5 w-5 shrink-0 text-amber-600 mt-0.5" />
            <div className="text-xs leading-relaxed">
              <p className="font-semibold text-amber-950">
                Perhatian: {summary.incomplete_students} siswa memiliki komponen
                penilaian yang belum lengkap.
              </p>
              <p className="mt-0.5 text-amber-800">
                Sesuai aturan kelengkapan rapor (Q7), seluruh komponen berbobot
                positif harus memiliki minimal satu nilai numerik sah
                (published) atau sesi presensi valid (finalized) sebelum rapor
                semester dapat difinalisasi.
              </p>
            </div>
          </div>
        )}

        {/* Filter and Search Bar */}
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
          <div className="flex items-center gap-1 rounded-lg bg-stone-100 p-1">
            <button
              onClick={() => setFilterMode("all")}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                filterMode === "all"
                  ? "bg-white text-stone-900 shadow-sm"
                  : "text-stone-600 hover:text-stone-900"
              }`}
            >
              Semua ({summary.total_students})
            </button>
            <button
              onClick={() => setFilterMode("incomplete")}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                filterMode === "incomplete"
                  ? "bg-white text-rose-700 shadow-sm"
                  : "text-stone-600 hover:text-stone-900"
              }`}
            >
              Belum Lengkap ({summary.incomplete_students})
            </button>
            <button
              onClick={() => setFilterMode("complete")}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition ${
                filterMode === "complete"
                  ? "bg-white text-emerald-800 shadow-sm"
                  : "text-stone-600 hover:text-stone-900"
              }`}
            >
              Lengkap ({summary.complete_students})
            </button>
          </div>

          <div className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama atau NIS siswa..."
              className="w-full rounded-lg border border-stone-200 py-1.5 pl-9 pr-3 text-xs text-stone-900 placeholder:text-stone-400 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
            />
          </div>
        </div>

        {/* Students Evaluation Table */}
        <div className="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-stone-700">
              <thead className="border-b border-stone-200 bg-stone-50/75 text-[11px] font-semibold uppercase tracking-wider text-stone-500">
                <tr>
                  <th className="py-3 pl-4 pr-3 sm:pl-6 w-10"></th>
                  <th className="py-3 px-3">Siswa</th>
                  <th className="py-3 px-3">Status Kelengkapan</th>
                  <th className="py-3 px-3">Status Draf Rapor</th>
                  <th className="py-3 px-3">Kehadiran Total</th>
                  <th className="py-3 px-3">Kelayakan Finalisasi</th>
                  <th className="py-3 pr-4 pl-3 sm:pr-6 text-right">Rincian</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {filteredStudents.length === 0 ? (
                  <tr>
                    <td
                      colSpan={7}
                      className="py-12 text-center text-stone-400"
                    >
                      Tidak ada data siswa yang cocok dengan filter.
                    </td>
                  </tr>
                ) : (
                  filteredStudents.map((s) => {
                    const isExpanded = !!expandedStudents[s.enrollment_id];
                    return (
                      <React.Fragment key={s.enrollment_id}>
                        <tr
                          className={`hover:bg-stone-50/60 transition cursor-pointer ${
                            isExpanded ? "bg-stone-50/80" : ""
                          }`}
                          onClick={() => toggleExpand(s.enrollment_id)}
                        >
                          <td className="py-3.5 pl-4 pr-3 sm:pl-6 text-stone-400">
                            {isExpanded ? (
                              <ChevronDown className="h-4 w-4 text-emerald-700" />
                            ) : (
                              <ChevronRight className="h-4 w-4" />
                            )}
                          </td>
                          <td className="py-3.5 px-3">
                            <div className="font-semibold text-stone-900">
                              {s.student.name}
                            </div>
                            <div className="text-[11px] text-stone-400">
                              NIS: {s.student.nis} •{" "}
                              {s.student.gender === "L"
                                ? "Laki-laki"
                                : "Perempuan"}
                            </div>
                          </td>
                          <td className="py-3.5 px-3">
                            {s.is_complete ? (
                              <span className="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-800 border border-emerald-200">
                                <CheckCircle2 className="h-3 w-3" /> Lengkap
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-semibold text-rose-800 border border-rose-200">
                                <XCircle className="h-3 w-3" /> Belum Lengkap
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-3">
                            {s.existing_report ? (
                              <span className="inline-flex items-center gap-1 rounded-md bg-stone-100 px-2 py-0.5 text-[11px] font-medium text-stone-700">
                                {s.existing_report.status.toUpperCase()} (Rev #
                                {s.existing_report.revision})
                              </span>
                            ) : (
                              <span className="text-[11px] text-stone-400">
                                Belum Ada
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-3">
                            <div className="font-medium text-stone-800">
                              {s.attendance_summary.present} /{" "}
                              {s.attendance_summary.total_sessions} Sesi
                            </div>
                            <div className="text-[10px] text-stone-400">
                              {s.attendance_summary.rate
                                ? `${s.attendance_summary.rate}%`
                                : "0%"}{" "}
                              Hadir
                            </div>
                          </td>
                          <td className="py-3.5 px-3">
                            {s.can_finalize ? (
                              <span className="text-emerald-700 font-medium text-[11px]">
                                Siap Difinalisasi
                              </span>
                            ) : (
                              <span className="text-rose-600 font-medium text-[11px]">
                                {s.blocked_reasons.length} Kendala Pemblokir
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 pr-4 pl-3 sm:pr-6 text-right space-x-2">
                            {s.existing_report ? (
                              <Link
                                href={`/academic/report-cards/${s.existing_report.id}`}
                                onClick={(e) => e.stopPropagation()}
                                className="inline-flex items-center gap-1 rounded-md bg-stone-100 px-2.5 py-1 text-[11px] font-semibold text-stone-700 hover:bg-stone-200 transition"
                              >
                                <FileText className="h-3 w-3" />
                                Rapor
                              </Link>
                            ) : s.is_complete ? (
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  router.post(
                                    `/academic/classrooms/${classroom.id}/report-cards`,
                                    { enrollment_id: s.enrollment_id },
                                    { preserveScroll: true },
                                  );
                                }}
                                className="inline-flex items-center gap-1 rounded-md bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-[11px] font-semibold text-emerald-800 hover:bg-emerald-100 transition"
                              >
                                Buat Draf
                              </button>
                            ) : null}

                            <button
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation();
                                toggleExpand(s.enrollment_id);
                              }}
                              className="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline"
                            >
                              {isExpanded ? "Tutup" : "Rincian"}
                            </button>
                          </td>
                        </tr>

                        {/* Expanded Detail Row */}
                        {isExpanded && (
                          <tr className="bg-stone-50/50">
                            <td colSpan={7} className="p-4 sm:px-6">
                              <div className="rounded-xl border border-stone-200 bg-white p-4 shadow-inner space-y-4">
                                <div className="flex items-center justify-between border-b border-stone-100 pb-3">
                                  <div className="flex items-center gap-2">
                                    <BookOpen className="h-4 w-4 text-emerald-700" />
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-stone-900">
                                      Evaluasi Kelengkapan Nilai Per Mata
                                      Pelajaran
                                    </h4>
                                  </div>
                                  <span className="text-[11px] text-stone-400 font-mono">
                                    Token Sumber:{" "}
                                    {s.source_version.substring(0, 16)}...
                                  </span>
                                </div>

                                {/* Blocked Reasons Alert */}
                                {s.blocked_reasons.length > 0 && (
                                  <div className="rounded-lg border border-rose-200 bg-rose-50/80 p-3 space-y-1">
                                    <div className="flex items-center gap-1.5 text-xs font-bold text-rose-900">
                                      <ShieldAlert className="h-4 w-4 text-rose-600" />
                                      Alasan Pemblokiran Kelengkapan Rapor:
                                    </div>
                                    <ul className="list-disc pl-5 text-[11px] text-rose-800 space-y-0.5">
                                      {s.blocked_reasons.map((reason, idx) => (
                                        <li key={idx}>{reason}</li>
                                      ))}
                                    </ul>
                                  </div>
                                )}

                                {/* Subjects Table */}
                                <div className="overflow-x-auto">
                                  <table className="w-full text-left text-xs">
                                    <thead className="border-b border-stone-200 text-[10px] font-semibold uppercase tracking-wider text-stone-400">
                                      <tr>
                                        <th className="py-2 pr-3">
                                          Kode & Mapel
                                        </th>
                                        <th className="py-2 px-3">
                                          Standar KKM
                                        </th>
                                        <th className="py-2 px-3">
                                          Nilai Akhir
                                        </th>
                                        <th className="py-2 px-3">
                                          Status KKM
                                        </th>
                                        <th className="py-2 px-3">
                                          Kehadiran Mapel
                                        </th>
                                        <th className="py-2 px-3">
                                          Kelengkapan
                                        </th>
                                        <th className="py-2 pl-3">
                                          Komponen Kosong
                                        </th>
                                      </tr>
                                    </thead>
                                    <tbody className="divide-y divide-stone-100">
                                      {s.subjects.map((sub) => (
                                        <tr
                                          key={sub.class_subject_id}
                                          className="hover:bg-stone-50/50"
                                        >
                                          <td className="py-2.5 pr-3 font-semibold text-stone-900">
                                            {sub.subject_name}
                                            <span className="ml-1.5 text-[10px] font-normal text-stone-400">
                                              ({sub.subject_code})
                                            </span>
                                          </td>
                                          <td className="py-2.5 px-3 text-stone-600 font-mono">
                                            {sub.kkm}
                                          </td>
                                          <td className="py-2.5 px-3 font-bold font-mono">
                                            {sub.final_score !== null ? (
                                              <span className="text-emerald-800">
                                                {sub.final_score}
                                              </span>
                                            ) : (
                                              <span className="text-stone-400 font-normal italic">
                                                Belum tersedia
                                              </span>
                                            )}
                                          </td>
                                          <td className="py-2.5 px-3">
                                            <span
                                              className={`inline-block rounded px-2 py-0.5 text-[10px] font-semibold ${
                                                sub.kkm_status ===
                                                "Mencapai KKM"
                                                  ? "bg-emerald-100 text-emerald-800"
                                                  : sub.kkm_status ===
                                                      "Belum mencapai KKM"
                                                    ? "bg-rose-100 text-rose-800"
                                                    : "bg-stone-100 text-stone-500"
                                              }`}
                                            >
                                              {sub.kkm_status}
                                            </span>
                                          </td>
                                          <td className="py-2.5 px-3 text-[11px] text-stone-600">
                                            {sub.attendance.present}/
                                            {sub.attendance.total_sessions} Sesi
                                            (
                                            {sub.attendance.rate
                                              ? `${sub.attendance.rate}%`
                                              : "-"}
                                            )
                                          </td>
                                          <td className="py-2.5 px-3">
                                            {sub.is_complete ? (
                                              <span className="text-emerald-700 font-semibold text-[11px]">
                                                ✓ Lengkap
                                              </span>
                                            ) : (
                                              <span className="text-rose-700 font-semibold text-[11px]">
                                                ✗ Belum
                                              </span>
                                            )}
                                          </td>
                                          <td className="py-2.5 pl-3">
                                            {sub.missing_components.length >
                                            0 ? (
                                              <div className="flex flex-wrap gap-1">
                                                {sub.missing_components.map(
                                                  (mc, i) => (
                                                    <span
                                                      key={i}
                                                      className="rounded bg-rose-50 px-1.5 py-0.2 text-[10px] font-medium text-rose-700 border border-rose-200"
                                                    >
                                                      {mc}
                                                    </span>
                                                  ),
                                                )}
                                              </div>
                                            ) : (
                                              <span className="text-stone-400 text-[11px]">
                                                -
                                              </span>
                                            )}
                                          </td>
                                        </tr>
                                      ))}
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </td>
                          </tr>
                        )}
                      </React.Fragment>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
