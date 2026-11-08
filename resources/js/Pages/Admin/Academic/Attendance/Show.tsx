import React, { useState } from "react";
import { Link, useForm, router } from "@inertiajs/react";
import {
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Clock,
  Save,
  ShieldCheck,
  AlertCircle,
  FileCheck,
  UserCheck,
  Check,
  Users,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Alert } from "@/Components/ui/alert";

interface StudentItem {
  enrollment_id: number;
  student_id: number;
  nis: string;
  name: string;
  gender: string;
  status: string | null; // null means unrecorded (not absent!)
  recorded_at: string | null;
}

interface Classroom {
  id: number;
  grade_level: number;
  section: string;
  full_name: string;
  roster_version: number;
  term?: {
    id: number;
    semester: number;
    full_name: string;
    academic_year?: {
      id: number;
      name: string;
    };
  };
}

interface AttendanceSession {
  id: number;
  class_subject_id: number;
  meeting_number: number;
  held_on: string;
  topic: string | null;
  status: "draft" | "finalized";
  finalized_at: string | null;
  version: number;
  class_subject: {
    id: number;
    subject: { id: number; name: string };
    active_teaching_assignment?: {
      teacher: { id: number; name: string };
    } | null;
  };
  created_by?: { id: number; name: string };
  finalized_by?: { id: number; name: string } | null;
}

interface Props {
  session: AttendanceSession;
  classroom: Classroom;
  students: StudentItem[];
  stats: {
    total_eligible: number;
    present_count: number;
    excused_count: number;
    sick_count: number;
    absent_count: number;
    unrecorded_count: number;
  };
  permissions: {
    can_manage_draft: boolean;
    can_finalize: boolean;
    can_delete: boolean;
    is_data_entry: boolean;
  };
}

export default function AttendanceShow({
  session,
  classroom,
  students: initialStudents,
  stats: initialStats,
  permissions,
}: Props) {
  // Local state for interactive editing of attendance sheet
  const [entries, setEntries] = useState<Record<number, string | null>>(() => {
    const initial: Record<number, string | null> = {};
    initialStudents.forEach((s) => {
      initial[s.enrollment_id] = s.status;
    });
    return initial;
  });

  const [isDirty, setIsDirty] = useState(false);
  const [showFinalizeModal, setShowFinalizeModal] = useState(false);

  const isFinalized = session.status === "finalized";

  // Calculate live counts from local state
  const totalEligible = initialStudents.length;
  let presentCount = 0;
  let excusedCount = 0;
  let sickCount = 0;
  let absentCount = 0;
  let unrecordedCount = 0;

  initialStudents.forEach((s) => {
    const st = entries[s.enrollment_id];
    if (st === "present") presentCount++;
    else if (st === "excused") excusedCount++;
    else if (st === "sick") sickCount++;
    else if (st === "absent") absentCount++;
    else unrecordedCount++;
  });

  const handleStatusChange = (
    enrollmentId: number,
    newStatus: string | null,
  ) => {
    if (isFinalized) return;

    // Data Entry cannot clear existing records
    if (permissions.is_data_entry && newStatus === null) {
      const originalRecord = initialStudents.find(
        (s) => s.enrollment_id === enrollmentId,
      );
      if (originalRecord && originalRecord.status !== null) {
        alert(
          "Petugas Data Entry tidak diizinkan untuk mengosongkan rekaman yang sudah ada.",
        );
        return;
      }
    }

    setEntries((prev) => ({
      ...prev,
      [enrollmentId]: newStatus,
    }));
    setIsDirty(true);
  };

  const handleSaveDraft = () => {
    const payloadEntries = Object.entries(entries).map(
      ([enrollmentId, status]) => ({
        enrollment_id: parseInt(enrollmentId, 10),
        status: status,
      }),
    );

    router.patch(
      `/academic/attendance-sessions/${session.id}/records`,
      {
        expected_version: session.version,
        expected_roster_version: classroom.roster_version,
        entries: payloadEntries,
      },
      {
        preserveScroll: true,
        onSuccess: () => setIsDirty(false),
      },
    );
  };

  const handleConfirmFinalize = () => {
    router.post(
      `/academic/attendance-sessions/${session.id}/finalize`,
      {
        expected_version: session.version,
        expected_roster_version: classroom.roster_version,
      },
      {
        onSuccess: () => setShowFinalizeModal(false),
      },
    );
  };

  return (
    <AppLayout
      title={`Lembar Presensi: Pertemuan Ke-${session.meeting_number} - ${session.class_subject.subject.name}`}
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              href={`/academic/class-subjects/${session.class_subject_id}/attendance-sessions`}
              className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary transition-colors"
            >
              <ArrowLeft className="size-3.5" />
              <span>Daftar Pertemuan</span>
            </Link>
            <span className="text-xs text-muted-foreground">•</span>
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              {classroom.full_name}
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              {classroom.term?.academic_year?.name} ({classroom.term?.full_name}
              )
            </span>
          </div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold tracking-tight text-foreground">
              Pertemuan Ke-{session.meeting_number}:{" "}
              {session.class_subject.subject.name}
            </h1>
            {isFinalized ? (
              <Badge variant="success" className="gap-1 text-xs">
                <CheckCircle2 className="size-3" />
                <span>Disahkan</span>
              </Badge>
            ) : (
              <Badge variant="warning" className="gap-1 text-xs">
                <Clock className="size-3" />
                <span>Draf</span>
              </Badge>
            )}
          </div>
        </div>
      }
    >
      <div className="space-y-6 pb-20">
        {/* Banner Status & Metadata */}
        <div className="bg-card border border-border rounded-xl p-6 shadow-sm mb-6">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <div className="flex items-center space-x-3 mb-2">
                <span className="text-2xl font-black text-foreground">
                  Pertemuan Ke-{session.meeting_number}
                </span>
                {isFinalized ? (
                  <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <CheckCircle2 className="w-4 h-4 mr-1.5" />
                    Telah Disahkan (Finalized)
                  </span>
                ) : (
                  <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    <Clock className="w-4 h-4 mr-1.5" />
                    Draf Presensi (Dapat Diubah)
                  </span>
                )}
              </div>

              <div className="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-muted-foreground">
                <span className="flex items-center">
                  <Calendar className="w-4 h-4 mr-1.5 text-primary" />
                  Tanggal:{" "}
                  <strong className="ml-1 text-foreground">
                    {session.held_on}
                  </strong>
                </span>
                {session.topic && (
                  <span>
                    Topik:{" "}
                    <strong className="text-foreground">{session.topic}</strong>
                  </span>
                )}
                <span>
                  Guru:{" "}
                  <strong className="text-foreground">
                    {session.class_subject.active_teaching_assignment?.teacher
                      .name || "Belum ditugaskan"}
                  </strong>
                </span>
                <span>
                  Roster Version:{" "}
                  <strong className="text-foreground">
                    v{classroom.roster_version}
                  </strong>
                </span>
              </div>
            </div>

            {/* Status explanation note */}
            <div className="text-xs text-muted-foreground md:text-right max-w-sm">
              {isFinalized ? (
                <p className="bg-emerald-50 text-emerald-900 border border-emerald-200 p-2.5 rounded-lg">
                  Sesi ini telah disahkan resmi oleh{" "}
                  <strong>{session.finalized_by?.name || "Staf"}</strong> pada{" "}
                  {session.finalized_at}. Roster dibekukan dan menjadi penyebut
                  rasio kehadiran resmi.
                </p>
              ) : (
                <p className="bg-amber-50 text-amber-900 border border-amber-200 p-2.5 rounded-lg">
                  Status kosong dianggap <strong>belum dicatat</strong> (bukan
                  alpa). Untuk mengesahkan pertemuan, seluruh siswa wajib
                  memiliki status kehadiran.
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Live Statistics Cards */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
          <div className="bg-card border border-border rounded-xl p-4 shadow-sm text-center">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground block">
              Total Siswa
            </span>
            <span className="text-2xl font-bold text-foreground mt-1 block">
              {totalEligible}
            </span>
          </div>

          <div className="bg-card border border-border rounded-xl p-4 shadow-sm text-center">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-emerald-700 block">
              Hadir
            </span>
            <span className="text-2xl font-bold text-emerald-700 mt-1 block">
              {presentCount}
            </span>
          </div>

          <div className="bg-card border border-border rounded-xl p-4 shadow-sm text-center">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-blue-700 block">
              Izin
            </span>
            <span className="text-2xl font-bold text-blue-700 mt-1 block">
              {excusedCount}
            </span>
          </div>

          <div className="bg-card border border-border rounded-xl p-4 shadow-sm text-center">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-amber-700 block">
              Sakit
            </span>
            <span className="text-2xl font-bold text-amber-700 mt-1 block">
              {sickCount}
            </span>
          </div>

          <div className="bg-card border border-border rounded-xl p-4 shadow-sm text-center">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-rose-700 block">
              Alpa
            </span>
            <span className="text-2xl font-bold text-rose-700 mt-1 block">
              {absentCount}
            </span>
          </div>

          <div
            className={`border rounded-xl p-4 shadow-sm text-center ${
              unrecordedCount > 0
                ? "bg-amber-50/70 border-amber-300 text-amber-900"
                : "bg-emerald-50/70 border-emerald-300 text-emerald-900"
            }`}
          >
            <span className="text-[11px] font-semibold uppercase tracking-wider block">
              Belum Dicatat
            </span>
            <span className="text-2xl font-bold mt-1 block">
              {unrecordedCount}
            </span>
          </div>
        </div>

        {/* Student Attendance Table */}
        <div className="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
          <div className="px-6 py-4 border-b border-border flex items-center justify-between">
            <h2 className="text-base font-bold text-foreground">
              Daftar Peserta Pertemuan
            </h2>
            <span className="text-xs text-muted-foreground">
              Siswa aktif pada tanggal {session.held_on}
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="border-b border-border bg-muted/40 text-muted-foreground text-xs font-semibold uppercase tracking-wider">
                  <th className="py-3 px-4 w-12 text-center">No</th>
                  <th className="py-3 px-4 w-32">NIS</th>
                  <th className="py-3 px-4">Nama Siswa</th>
                  <th className="py-3 px-4 w-24 text-center">L/P</th>
                  <th className="py-3 px-4 w-[420px] text-center">
                    Status Kehadiran
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border text-sm">
                {initialStudents.length === 0 ? (
                  <tr>
                    <td
                      colSpan={5}
                      className="py-12 text-center text-muted-foreground"
                    >
                      Tidak ada siswa aktif yang terdaftar pada tanggal
                      pertemuan ini.
                    </td>
                  </tr>
                ) : (
                  initialStudents.map((student, idx) => {
                    const currentStatus = entries[student.enrollment_id];

                    return (
                      <tr
                        key={student.enrollment_id}
                        className={`hover:bg-muted/30 transition-colors ${
                          currentStatus === null ? "bg-amber-50/20" : ""
                        }`}
                      >
                        <td className="py-3 px-4 text-center text-xs text-muted-foreground font-mono">
                          {idx + 1}
                        </td>
                        <td className="py-3 px-4 font-mono text-xs text-foreground">
                          {student.nis}
                        </td>
                        <td className="py-3 px-4">
                          <div className="font-semibold text-foreground">
                            {student.name}
                          </div>
                          {currentStatus === null && !isFinalized && (
                            <span className="text-[11px] text-amber-700 italic">
                              Belum dicatat
                            </span>
                          )}
                        </td>
                        <td className="py-3 px-4 text-center text-xs font-medium text-muted-foreground">
                          {student.gender === "female" ? "P" : "L"}
                        </td>
                        <td className="py-3 px-4">
                          {isFinalized ? (
                            <div className="flex justify-center">
                              {currentStatus === "present" && (
                                <span className="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                  Hadir
                                </span>
                              )}
                              {currentStatus === "excused" && (
                                <span className="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-300">
                                  Izin
                                </span>
                              )}
                              {currentStatus === "sick" && (
                                <span className="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                  Sakit
                                </span>
                              )}
                              {currentStatus === "absent" && (
                                <span className="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                  Alpa
                                </span>
                              )}
                            </div>
                          ) : (
                            <div className="flex items-center justify-center space-x-1 sm:space-x-1.5">
                              {/* Hadir */}
                              <button
                                type="button"
                                onClick={() =>
                                  handleStatusChange(
                                    student.enrollment_id,
                                    "present",
                                  )
                                }
                                className={`px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition border min-w-[56px] ${
                                  currentStatus === "present"
                                    ? "bg-emerald-600 text-white border-emerald-700 shadow-sm"
                                    : "bg-background text-muted-foreground border-border hover:bg-emerald-50 hover:text-emerald-700"
                                }`}
                              >
                                Hadir
                              </button>

                              {/* Izin */}
                              <button
                                type="button"
                                onClick={() =>
                                  handleStatusChange(
                                    student.enrollment_id,
                                    "excused",
                                  )
                                }
                                className={`px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition border min-w-[56px] ${
                                  currentStatus === "excused"
                                    ? "bg-blue-600 text-white border-blue-700 shadow-sm"
                                    : "bg-background text-muted-foreground border-border hover:bg-blue-50 hover:text-blue-700"
                                }`}
                              >
                                Izin
                              </button>

                              {/* Sakit */}
                              <button
                                type="button"
                                onClick={() =>
                                  handleStatusChange(
                                    student.enrollment_id,
                                    "sick",
                                  )
                                }
                                className={`px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition border min-w-[56px] ${
                                  currentStatus === "sick"
                                    ? "bg-amber-600 text-white border-amber-700 shadow-sm"
                                    : "bg-background text-muted-foreground border-border hover:bg-amber-50 hover:text-amber-700"
                                }`}
                              >
                                Sakit
                              </button>

                              {/* Alpa */}
                              <button
                                type="button"
                                onClick={() =>
                                  handleStatusChange(
                                    student.enrollment_id,
                                    "absent",
                                  )
                                }
                                className={`px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition border min-w-[56px] ${
                                  currentStatus === "absent"
                                    ? "bg-rose-600 text-white border-rose-700 shadow-sm"
                                    : "bg-background text-muted-foreground border-border hover:bg-rose-50 hover:text-rose-700"
                                }`}
                              >
                                Alpa
                              </button>

                              {/* Kosongkan (Belum Dicatat) - only if not Data Entry */}
                              {!permissions.is_data_entry && (
                                <button
                                  type="button"
                                  onClick={() =>
                                    handleStatusChange(
                                      student.enrollment_id,
                                      null,
                                    )
                                  }
                                  className={`px-2 py-1.5 rounded-lg text-[11px] font-medium transition border ${
                                    currentStatus === null
                                      ? "bg-muted text-foreground border-border font-bold"
                                      : "bg-transparent text-muted-foreground/60 border-transparent hover:text-muted-foreground"
                                  }`}
                                  title="Kosongkan status (belum dicatat)"
                                >
                                  Reset
                                </button>
                              )}
                            </div>
                          )}
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Sticky Bottom Action Bar for Draft Session */}
      {!isFinalized && (
        <div className="fixed bottom-0 inset-x-0 bg-card/95 backdrop-blur border-t border-border py-3.5 px-4 sm:px-6 z-20 shadow-lg">
          <div className="max-w-7xl mx-auto flex items-center justify-between">
            <div className="flex items-center space-x-3 text-xs">
              <span className="font-semibold text-foreground">
                Progress Pengisian: {totalEligible - unrecordedCount} /{" "}
                {totalEligible} siswa
              </span>
              {unrecordedCount > 0 ? (
                <span className="text-amber-700 bg-amber-100 px-2.5 py-0.5 rounded-full font-medium">
                  {unrecordedCount} siswa belum dicatat
                </span>
              ) : (
                <span className="text-emerald-700 bg-emerald-100 px-2.5 py-0.5 rounded-full font-medium flex items-center">
                  <Check className="w-3 h-3 mr-1" /> Siap disahkan
                </span>
              )}
              {isDirty && (
                <span className="text-primary font-medium italic">
                  Ada perubahan belum disimpan
                </span>
              )}
            </div>

            <div className="flex items-center space-x-3">
              {permissions.can_manage_draft && (
                <button
                  type="button"
                  onClick={handleSaveDraft}
                  className="px-4 py-2 bg-muted text-foreground border border-border text-xs font-semibold rounded-lg hover:bg-muted/80 transition flex items-center space-x-1.5 shadow-sm"
                >
                  <Save className="w-4 h-4" />
                  <span>Simpan Draf</span>
                </button>
              )}

              {permissions.can_finalize && (
                <button
                  type="button"
                  disabled={unrecordedCount > 0}
                  onClick={() => setShowFinalizeModal(true)}
                  className="px-5 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition flex items-center space-x-1.5 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>Sahkan Kehadiran</span>
                </button>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Finalize Confirmation Modal */}
      {showFinalizeModal && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl max-w-md w-full p-6 shadow-xl">
            <div className="flex items-center space-x-3 text-emerald-700 mb-3">
              <ShieldCheck className="w-7 h-7" />
              <h3 className="text-base font-bold text-foreground">
                Sahkan Pertemuan Ke-{session.meeting_number}?
              </h3>
            </div>
            <p className="text-xs text-muted-foreground mb-4 leading-relaxed">
              Pengesahan kehadiran akan <strong>membekukan daftar hadir</strong>{" "}
              ({totalEligible} siswa). Sesi ini akan menjadi pertemuan sah resmi
              yang dihitung sebagai penyebut rasio kehadiran di rapor dan
              kalkulasi nilai mapel.
            </p>
            <div className="bg-muted/50 p-3 rounded-lg border border-border text-xs space-y-1 mb-5">
              <div className="flex justify-between">
                <span>Hadir:</span> <strong>{presentCount} siswa</strong>
              </div>
              <div className="flex justify-between">
                <span>Izin:</span> <strong>{excusedCount} siswa</strong>
              </div>
              <div className="flex justify-between">
                <span>Sakit:</span> <strong>{sickCount} siswa</strong>
              </div>
              <div className="flex justify-between">
                <span>Alpa:</span> <strong>{absentCount} siswa</strong>
              </div>
            </div>
            <div className="flex items-center justify-end space-x-2">
              <button
                type="button"
                onClick={() => setShowFinalizeModal(false)}
                className="px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted transition"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleConfirmFinalize}
                className="px-4 py-2 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition"
              >
                Ya, Sahkan Resmi
              </button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
