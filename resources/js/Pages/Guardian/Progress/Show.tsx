import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import {
  ArrowLeft,
  Award,
  Calendar,
  CheckCircle2,
  Clock,
  Filter,
  GraduationCap,
  Mail,
  MessageSquare,
  Sparkles,
  TrendingUp,
  User,
  Users,
  XCircle,
  FileText,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";

interface ActivityItem {
  type: "assessment" | "attendance";
  id: number;
  title: string;
  date: string;
  subject_id: number;
  subject_name: string;
  subject_code: string;
  component_name?: string;
  component_code?: string;
  score?: string;
  feedback?: string | null;
  topic?: string | null;
  status?: "present" | "excused" | "sick" | "absent";
}

interface SubjectSummary {
  subject_id: number;
  subject_name: string;
  subject_code: string;
  attendance: {
    total_sessions: number;
    present: number;
    excused: number;
    sick: number;
    absent: number;
    rate: string | null;
  };
  assessments: {
    count: number;
    average: string | null;
  };
}

interface SiblingChild {
  id: number;
  nis: string;
  name: string;
}

interface Props {
  student: {
    id: number;
    nis: string;
    name: string;
    gender: string;
  };
  term: {
    id: number;
    name: string;
    semester: number;
    academic_year?: string;
  } | null;
  availableTerms: {
    id: number;
    name: string;
    is_active: boolean;
  }[];
  hasEnrollment: boolean;
  classroom: {
    id: number;
    name: string;
    grade_level: number;
    section: string;
  } | null;
  homeroom: {
    name: string;
    email: string | null;
  } | null;
  classSubjects?: {
    id: number;
    name: string;
    code: string;
  }[];
  selectedSubjectId?: number | null;
  subjectSummaries: SubjectSummary[];
  activities: ActivityItem[];
  siblingChildren?: SiblingChild[];
}

export default function Show({
  student,
  term,
  availableTerms,
  hasEnrollment,
  classroom,
  homeroom,
  classSubjects = [],
  selectedSubjectId = null,
  subjectSummaries,
  activities,
  siblingChildren = [],
}: Props) {
  const [activeTab, setActiveTab] = useState<"activities" | "recap">(
    "activities",
  );
  const [subjectFilter, setSubjectFilter] = useState<string>(
    selectedSubjectId ? String(selectedSubjectId) : "all",
  );

  const handleSubjectChange = (val: string) => {
    setSubjectFilter(val);
    router.get(
      `/children/${student.id}/progress`,
      {
        term_id: term?.id,
        subject_id: val === "all" ? undefined : val,
      },
      { preserveState: true, replace: true },
    );
  };

  const handleTermChange = (termId: string) => {
    router.get(
      `/children/${student.id}/progress`,
      {
        term_id: termId,
        subject_id: subjectFilter === "all" ? undefined : subjectFilter,
      },
      { preserveState: true },
    );
  };

  // Compute overall stats across all visible subject summaries
  const totalAttSessions = subjectSummaries.reduce(
    (sum, s) => sum + s.attendance.total_sessions,
    0,
  );
  const totalPresent = subjectSummaries.reduce(
    (sum, s) => sum + s.attendance.present,
    0,
  );
  const totalExcused = subjectSummaries.reduce(
    (sum, s) => sum + s.attendance.excused,
    0,
  );
  const totalSick = subjectSummaries.reduce(
    (sum, s) => sum + s.attendance.sick,
    0,
  );
  const totalAbsent = subjectSummaries.reduce(
    (sum, s) => sum + s.attendance.absent,
    0,
  );

  const overallAttRate =
    totalAttSessions > 0
      ? ((totalPresent / totalAttSessions) * 100).toFixed(1)
      : null;

  const totalAssessmentsCount = activities.filter(
    (a) => a.type === "assessment",
  ).length;

  return (
    <AppLayout
      title={`Perkembangan Belajar: ${student.name}`}
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              href="/children"
              className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary transition-colors"
            >
              <ArrowLeft className="size-3.5" />
              <span>Daftar Anak</span>
            </Link>
            <span className="text-xs text-muted-foreground">•</span>
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              NIS: {student.nis}
            </Badge>
            {classroom && (
              <>
                <span className="text-xs text-muted-foreground">•</span>
                <span className="text-xs font-semibold text-foreground">
                  Kelas {classroom.name}
                </span>
              </>
            )}
            {term && (
              <>
                <span className="text-xs text-muted-foreground">•</span>
                <span className="text-xs text-muted-foreground">
                  {term.academic_year} (Sem. {term.semester})
                </span>
              </>
            )}
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Catatan Belajar: {student.name}
          </h1>
        </div>
      }
    >
      <div className="space-y-6">
        {/* Sibling Children Switcher */}
        {siblingChildren.length > 0 && (
          <div className="border-b border-stone-200 bg-white px-6 py-2">
            <div className="mx-auto flex max-w-5xl items-center gap-2 overflow-x-auto text-xs">
              <span className="font-semibold text-stone-400 uppercase tracking-wider text-[10px] shrink-0">
                Pilih Anak:
              </span>
              <span className="rounded-full bg-teal-700 px-3 py-1 font-bold text-white shrink-0">
                {student.name}
              </span>
              {siblingChildren.map((sib) => (
                <Link
                  key={sib.id}
                  href={`/children/${sib.id}/progress`}
                  className="rounded-full bg-stone-100 px-3 py-1 font-semibold text-stone-700 hover:bg-stone-200 shrink-0 transition"
                >
                  {sib.name}
                </Link>
              ))}
            </div>
          </div>
        )}

        {/* Navigation Sub-Tabs */}
        <div className="border-b border-stone-200 bg-white px-6">
          <div className="mx-auto flex max-w-5xl gap-6 text-sm">
            <span className="flex items-center gap-2 border-b-2 border-teal-700 py-3 font-bold text-teal-800">
              <TrendingUp className="h-4 w-4" />
              Perkembangan Belajar
            </span>
            <Link
              href={`/children/${student.id}/report-cards`}
              className="flex items-center gap-2 border-b-2 border-transparent py-3 font-medium text-stone-500 hover:text-stone-800 transition"
            >
              <GraduationCap className="h-4 w-4" />
              Rapor Semester
            </Link>
          </div>
        </div>

        {/* Term & Subject Filters */}
        <div className="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
          <div className="flex items-center gap-3 flex-wrap">
            <div className="flex items-center gap-2 text-xs font-semibold text-stone-600">
              <Calendar className="h-4 w-4 text-teal-700" />
              <span>Semester:</span>
            </div>
            <select
              value={term?.id ?? ""}
              onChange={(e) => handleTermChange(e.target.value)}
              className="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-900 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
            >
              {availableTerms.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.name} {t.is_active ? "(Aktif)" : ""}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center gap-3 flex-wrap">
            <div className="flex items-center gap-2 text-xs font-semibold text-stone-600">
              <Filter className="h-4 w-4 text-teal-700" />
              <span>Mata Pelajaran:</span>
            </div>
            <select
              value={subjectFilter}
              onChange={(e) => handleSubjectChange(e.target.value)}
              className="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-900 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
            >
              <option value="all">Semua Mata Pelajaran</option>
              {classSubjects.map((cs) => (
                <option key={cs.id} value={cs.id}>
                  {cs.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        {!hasEnrollment ? (
          /* Empty Enrollment Notice */
          <div className="rounded-2xl border border-stone-200 bg-white p-12 text-center shadow-sm">
            <GraduationCap className="mx-auto h-12 w-12 text-stone-300" />
            <h3 className="mt-4 text-base font-bold text-stone-900">
              Belum Terdaftar pada Semester Ini
            </h3>
            <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
              Siswa tidak memiliki catatan pendaftaran kelas aktif pada periode
              semester yang dipilih. Silakan pilih semester lain dari menu di
              atas.
            </p>
          </div>
        ) : (
          <div>
            {/* Top Stat Cards */}
            <div className="mb-6 grid grid-cols-2 md:grid-cols-4 gap-3">
              <div className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                <span className="text-[11px] font-bold uppercase tracking-wider text-stone-400">
                  Kehadiran Tatap Muka
                </span>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl font-bold text-stone-900">
                    {overallAttRate !== null ? `${overallAttRate}%` : "-"}
                  </span>
                  <span className="text-xs font-medium text-emerald-700">
                    {totalPresent} Hadir / {totalAttSessions} Pertemuan
                  </span>
                </div>
              </div>

              <div className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                <span className="text-[11px] font-bold uppercase tracking-wider text-stone-400">
                  Izin & Sakit
                </span>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl font-bold text-stone-800">
                    {totalExcused + totalSick}
                  </span>
                  <span className="text-xs text-stone-500">
                    {totalSick} Sakit, {totalExcused} Izin
                  </span>
                </div>
              </div>

              <div className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                <span className="text-[11px] font-bold uppercase tracking-wider text-stone-400">
                  Alpa / Tanpa Keterangan
                </span>
                <div className="mt-2 flex items-baseline justify-between">
                  <span
                    className={`text-2xl font-bold ${totalAbsent > 0 ? "text-red-600" : "text-stone-800"}`}
                  >
                    {totalAbsent}
                  </span>
                  <span className="text-xs text-stone-500">Pertemuan</span>
                </div>
              </div>

              <div className="rounded-xl border border-teal-200 bg-teal-50/50 p-4 shadow-sm">
                <span className="text-[11px] font-bold uppercase tracking-wider text-teal-800">
                  Aktivitas Nilai
                </span>
                <div className="mt-2 flex items-baseline justify-between">
                  <span className="text-2xl font-bold text-teal-900">
                    {totalAssessmentsCount}
                  </span>
                  <span className="text-xs font-medium text-teal-700">
                    Tugas & Ulangan
                  </span>
                </div>
              </div>
            </div>

            {/* Homeroom Teacher Contact Card */}
            {homeroom && (
              <div className="mb-6 rounded-xl border border-stone-200 bg-white p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <User className="h-5 w-5" />
                  </div>
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-stone-400">
                      Wali Kelas {classroom?.name}
                    </span>
                    <h3 className="font-bold text-stone-900 text-sm">
                      {homeroom.name}
                    </h3>
                  </div>
                </div>

                {homeroom.email && (
                  <div className="flex items-center gap-2 text-xs font-semibold text-stone-600 bg-stone-50 px-3 py-1.5 rounded-lg border border-stone-200/80">
                    <Mail className="h-3.5 w-3.5 text-teal-700" />
                    <span>{homeroom.email}</span>
                  </div>
                )}
              </div>
            )}

            {/* Tab Navigation */}
            <div className="mb-4 flex items-center gap-2 border-b border-stone-200 pb-2">
              <button
                onClick={() => setActiveTab("activities")}
                className={`inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-bold transition ${
                  activeTab === "activities"
                    ? "bg-teal-700 text-white shadow-sm"
                    : "text-stone-600 hover:bg-stone-100"
                }`}
              >
                <Sparkles className="h-3.5 w-3.5" />
                <span>Riwayat Aktivitas ({activities.length})</span>
              </button>
              <button
                onClick={() => setActiveTab("recap")}
                className={`inline-flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-bold transition ${
                  activeTab === "recap"
                    ? "bg-teal-700 text-white shadow-sm"
                    : "text-stone-600 hover:bg-stone-100"
                }`}
              >
                <Award className="h-3.5 w-3.5" />
                <span>
                  Rekap per Mata Pelajaran ({subjectSummaries.length})
                </span>
              </button>
            </div>

            {/* Tab 1: Activities Feed */}
            {activeTab === "activities" && (
              <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
                <div className="border-b border-stone-100 bg-stone-50/70 px-6 py-3.5 flex items-center justify-between">
                  <h2 className="text-sm font-bold text-stone-900">
                    Aktivitas yang Dibagikan Guru
                  </h2>
                  <span className="text-xs text-stone-500 font-medium">
                    Hasil Penilaian Resmi & Presensi Sah
                  </span>
                </div>

                {activities.length === 0 ? (
                  <div className="py-12 text-center px-4">
                    <Clock className="mx-auto h-10 w-10 text-stone-300" />
                    <p className="mt-3 text-sm font-semibold text-stone-800">
                      Belum Ada Aktivitas Dibagikan
                    </p>
                    <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
                      Aktivitas penilaian atau presensi akan tampil di sini
                      setelah disahkan dan dipublikasikan oleh guru mata
                      pelajaran.
                    </p>
                  </div>
                ) : (
                  <div className="divide-y divide-stone-100">
                    {activities.map((act, index) => (
                      <div
                        key={`${act.type}-${act.id}-${index}`}
                        className="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-stone-50/60 transition"
                      >
                        <div className="flex items-start gap-3.5">
                          {act.type === "assessment" ? (
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 border border-teal-200 text-teal-800 font-bold text-xs uppercase">
                              {act.component_code ?? "NIL"}
                            </div>
                          ) : (
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs">
                              ABS
                            </div>
                          )}

                          <div>
                            <div className="flex flex-wrap items-center gap-2">
                              <h3 className="font-bold text-stone-900 text-sm">
                                {act.title}
                              </h3>
                              <span className="text-xs text-stone-400">•</span>
                              <span className="rounded bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-700">
                                {act.subject_name}
                              </span>
                            </div>

                            <div className="mt-1 flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-stone-500">
                              <span className="flex items-center gap-1">
                                <Calendar className="h-3 w-3 text-stone-400" />
                                <span>{act.date}</span>
                              </span>
                              {act.topic && (
                                <>
                                  <span>•</span>
                                  <span className="italic">
                                    Materi: {act.topic}
                                  </span>
                                </>
                              )}
                            </div>

                            {act.feedback && (
                              <div className="mt-2 flex items-start gap-1.5 rounded-lg bg-teal-50/60 p-2 text-xs text-teal-900 border border-teal-100">
                                <MessageSquare className="h-3.5 w-3.5 text-teal-700 shrink-0 mt-0.5" />
                                <span>
                                  <strong>Catatan Guru:</strong> {act.feedback}
                                </span>
                              </div>
                            )}
                          </div>
                        </div>

                        {/* Right: Score or Attendance Badge */}
                        <div className="self-end sm:self-center shrink-0 text-right">
                          {act.type === "assessment" ? (
                            <div>
                              <div className="text-2xl font-bold text-teal-800 font-mono tracking-tight">
                                {act.score}
                              </div>
                              <span className="text-[10px] font-semibold uppercase text-stone-400">
                                Skor / 100
                              </span>
                            </div>
                          ) : (
                            <div>
                              {act.status === "present" && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                  <CheckCircle2 className="h-3.5 w-3.5" />
                                  <span>Hadir</span>
                                </span>
                              )}
                              {act.status === "excused" && (
                                <span className="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">
                                  Izin
                                </span>
                              )}
                              {act.status === "sick" && (
                                <span className="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">
                                  Sakit
                                </span>
                              )}
                              {act.status === "absent" && (
                                <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-800">
                                  <XCircle className="h-3.5 w-3.5" />
                                  <span>Alpa</span>
                                </span>
                              )}
                            </div>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Tab 2: Subject Summaries */}
            {activeTab === "recap" && (
              <div className="space-y-3">
                {subjectSummaries.map((summary) => (
                  <div
                    key={summary.subject_id}
                    className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-100 pb-3">
                      <div>
                        <span className="text-[10px] font-bold uppercase tracking-wider text-teal-800 bg-teal-50 px-2 py-0.5 rounded">
                          {summary.subject_code}
                        </span>
                        <h3 className="mt-1 font-bold text-stone-900 text-base">
                          {summary.subject_name}
                        </h3>
                      </div>
                      <div className="flex items-center gap-4 text-xs">
                        <div className="text-right">
                          <span className="text-[10px] uppercase font-bold text-stone-400 block">
                            Rerata Penilaian
                          </span>
                          <span className="font-bold text-stone-900 text-sm">
                            {summary.assessments.average ?? "-"}
                          </span>
                        </div>
                        <div className="text-right">
                          <span className="text-[10px] uppercase font-bold text-stone-400 block">
                            Tingkat Kehadiran
                          </span>
                          <span className="font-bold text-emerald-800 text-sm">
                            {summary.attendance.rate !== null
                              ? `${summary.attendance.rate}%`
                              : "-"}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div className="mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                      <div className="rounded-lg bg-stone-50 p-2 text-center border border-stone-100">
                        <span className="text-stone-400 font-semibold block text-[10px] uppercase">
                          Hadir
                        </span>
                        <span className="font-bold text-stone-800">
                          {summary.attendance.present} pertemuan
                        </span>
                      </div>
                      <div className="rounded-lg bg-stone-50 p-2 text-center border border-stone-100">
                        <span className="text-stone-400 font-semibold block text-[10px] uppercase">
                          Izin / Sakit
                        </span>
                        <span className="font-bold text-stone-800">
                          {summary.attendance.excused + summary.attendance.sick}{" "}
                          pertemuan
                        </span>
                      </div>
                      <div className="rounded-lg bg-stone-50 p-2 text-center border border-stone-100">
                        <span className="text-stone-400 font-semibold block text-[10px] uppercase">
                          Alpa
                        </span>
                        <span className="font-bold text-red-600">
                          {summary.attendance.absent} pertemuan
                        </span>
                      </div>
                      <div className="rounded-lg bg-stone-50 p-2 text-center border border-stone-100">
                        <span className="text-stone-400 font-semibold block text-[10px] uppercase">
                          Tugas & Ujian
                        </span>
                        <span className="font-bold text-teal-800">
                          {summary.assessments.count} aktivitas
                        </span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Privacy & Rapor Policy Notice */}
            <div className="mt-8 rounded-xl border border-stone-200 bg-white p-4 text-xs text-stone-500 shadow-sm leading-relaxed space-y-1">
              <p className="font-bold text-stone-700">
                Ketentuan Tampilan Portal:
              </p>
              <p>
                • Data di atas hanya mencakup aktivitas yang telah
                dipublikasikan guru dan sesi presensi yang telah disahkan. Nilai
                draf internal tidak ditampilkan.
              </p>
              <p>
                • Demi privasi siswa, portal ini tidak menampilkan peringkat,
                skor siswa lain, atau rata-rata kelas.
              </p>
              <p>
                • Rapor semester resmi dengan pembobotan nilai akhir dan tanda
                tangan sekolah diterbitkan secara terpisah pada akhir semester.
              </p>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
