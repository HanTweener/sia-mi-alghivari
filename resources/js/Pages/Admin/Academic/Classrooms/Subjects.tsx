import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  Award,
  BookOpen,
  Calendar,
  Clock,
  Plus,
  Trash2,
  UserCheck,
  Users,
  AlertCircle,
  History,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface Teacher {
  id: number;
  name: string;
  nip: string | null;
}

interface Subject {
  id: number;
  code: string;
  name: string;
  category: string;
}

interface TeachingAssignment {
  id: number;
  teacher_id: number;
  starts_on: string;
  ends_on: string | null;
  status: string;
  teacher: Teacher;
}

interface ScheduleSlot {
  id: number;
  weekday: number;
  starts_at: string;
  ends_at: string;
  weekday_name: string;
}

interface ClassSubject {
  id: number;
  classroom_id: number;
  subject_id: number;
  subject: Subject;
  active_teaching_assignment?: TeachingAssignment | null;
  teaching_assignments: TeachingAssignment[];
  schedule_slots: ScheduleSlot[];
}

interface Classroom {
  id: number;
  term_id: number;
  grade_level: number;
  section: string;
  roster_version: number;
  full_name: string;
  term?: {
    id: number;
    semester: number;
    full_name: string;
    academic_year?: {
      id: number;
      name: string;
    };
  };
  active_homeroom?: {
    teacher: Teacher;
  } | null;
}

interface Props {
  classroom: Classroom;
  classSubjects: ClassSubject[];
  availableSubjects: Subject[];
  teachers: Teacher[];
}

const WEEKDAYS = [
  { value: 1, label: "Senin" },
  { value: 2, label: "Selasa" },
  { value: 3, label: "Rabu" },
  { value: 4, label: "Kamis" },
  { value: 5, label: "Jumat" },
  { value: 6, label: "Sabtu" },
  { value: 7, label: "Ahad" },
];

export default function ClassroomSubjects({
  classroom,
  classSubjects,
  availableSubjects,
  teachers,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const canManage =
    auth.user?.roles.some((role) =>
      ["master_admin", "academic_admin"].includes(role),
    ) ?? false;
  const [showAddSubjectModal, setShowAddSubjectModal] = useState(false);
  const [selectedClassSubjectForTeacher, setSelectedClassSubjectForTeacher] =
    useState<ClassSubject | null>(null);
  const [selectedClassSubjectForSchedule, setSelectedClassSubjectForSchedule] =
    useState<ClassSubject | null>(null);
  const [historyClassSubject, setHistoryClassSubject] =
    useState<ClassSubject | null>(null);

  const addSubjectForm = useForm({
    subject_id: "",
    teacher_id: "",
    starts_on: new Date().toISOString().split("T")[0],
  });

  const teacherForm = useForm({
    teacher_id: "",
    starts_on: new Date().toISOString().split("T")[0],
  });

  const scheduleForm = useForm({
    weekday: "1",
    starts_at: "07:30",
    ends_at: "08:50",
  });

  const handleAddSubject = (e: React.FormEvent) => {
    e.preventDefault();
    addSubjectForm.post(`/academic/classrooms/${classroom.id}/subjects`, {
      onSuccess: () => {
        addSubjectForm.reset();
        setShowAddSubjectModal(false);
      },
    });
  };

  const handleAssignTeacher = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedClassSubjectForTeacher) return;

    teacherForm.post(
      `/academic/class-subjects/${selectedClassSubjectForTeacher.id}/teachers`,
      {
        onSuccess: () => {
          teacherForm.reset();
          setSelectedClassSubjectForTeacher(null);
        },
      },
    );
  };

  const handleAddSchedule = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedClassSubjectForSchedule) return;

    scheduleForm.post(
      `/academic/class-subjects/${selectedClassSubjectForSchedule.id}/schedules`,
      {
        onSuccess: () => {
          setSelectedClassSubjectForSchedule(null);
        },
      },
    );
  };

  const handleDeleteSchedule = (slotId: number, slotDesc: string) => {
    if (confirm(`Hapus jadwal ${slotDesc}?`)) {
      router.delete(`/academic/schedules/${slotId}`);
    }
  };

  return (
    <AppLayout
      title={`Mata Pelajaran & Jadwal - ${classroom.full_name}`}
      contextHeader={{
        title: `Mata Pelajaran & Jadwal: ${classroom.full_name}`,
        badge: `Tingkat ${classroom.grade_level}`,
        description: `Wali Kelas: ${classroom.active_homeroom?.teacher.name || "Belum Ditugaskan"} • ${classroom.term?.academic_year?.name || ""} Semester ${classroom.term?.semester || ""}`,
      }}
    >
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div className="flex items-center gap-2">
            <Link
              href="/academic/classrooms"
              className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
            >
              <ArrowLeft className="w-4 h-4" />
              Kembali ke Daftar Rombel
            </Link>
            <span className="text-border">|</span>
            <Link
              href={`/academic/classrooms/${classroom.id}/roster`}
              className="inline-flex items-center gap-1.5 text-xs text-primary hover:underline font-medium"
            >
              <Users className="w-3.5 h-3.5" />
              Lihat Roster Siswa
            </Link>
          </div>
          {canManage && (
            <Button
              type="button"
              onClick={() => setShowAddSubjectModal(true)}
              className="inline-flex items-center gap-2"
            >
              <Plus className="h-4 w-4" />
              Tambah Mata Pelajaran
            </Button>
          )}
        </div>

        {/* Class Subjects Grid / List */}
        {classSubjects.length === 0 ? (
          <div className="rounded-2xl border border-stone-200 bg-white p-12 text-center shadow-xs">
            <BookOpen className="mx-auto h-10 w-10 text-stone-300 mb-3" />
            <h4 className="text-sm font-semibold text-stone-800">
              Belum ada mata pelajaran untuk kelas ini
            </h4>
            <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
              Tambahkan mata pelajaran kurikulum dan tentukan guru pengampu
              serta jadwal belajar mengajarnya.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {classSubjects.map((cs) => {
              const activeTeacher = cs.active_teaching_assignment?.teacher;
              return (
                <div
                  key={cs.id}
                  className="rounded-2xl border border-stone-200 bg-white p-5 shadow-xs transition hover:border-emerald-500/50"
                >
                  <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4 border-b border-stone-100 pb-4">
                    <div>
                      <div className="flex items-center gap-2 mb-1">
                        <span className="font-mono text-xs font-bold px-2 py-0.5 rounded bg-stone-100 text-stone-700">
                          {cs.subject.code}
                        </span>
                        <span className="text-[11px] font-semibold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded uppercase">
                          {cs.subject.category}
                        </span>
                      </div>
                      <h3 className="text-lg font-bold text-stone-900">
                        {cs.subject.name}
                      </h3>
                      <div className="mt-2 flex flex-wrap gap-2">
                        <Link
                          href={`/academic/class-subjects/${cs.id}/attendance-sessions`}
                          className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 transition"
                        >
                          <Calendar className="h-3.5 w-3.5 text-emerald-700" />
                          <span>Sesi Presensi</span>
                        </Link>
                        <Link
                          href={`/academic/class-subjects/${cs.id}/assessments`}
                          className="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-800 hover:bg-teal-100 transition"
                        >
                          <Award className="h-3.5 w-3.5 text-teal-700" />
                          <span>Aktivitas Penilaian</span>
                        </Link>
                      </div>
                    </div>

                    {/* Teacher Info & Handover */}
                    <div className="flex items-center gap-3 bg-stone-50 p-3 rounded-xl border border-stone-200/80 min-w-[280px]">
                      <div className="flex-1">
                        <p className="text-[10px] uppercase font-bold tracking-wider text-stone-500">
                          Guru Pengampu (Aktif)
                        </p>
                        {activeTeacher ? (
                          <div>
                            <p className="text-sm font-bold text-stone-900">
                              {activeTeacher.name}
                            </p>
                            <p className="text-xs text-stone-500">
                              Mulai: {cs.active_teaching_assignment?.starts_on}
                            </p>
                          </div>
                        ) : (
                          <p className="text-xs italic text-stone-500 mt-0.5">
                            Belum ada guru pengampu
                          </p>
                        )}
                      </div>

                      <div className="flex flex-col gap-1">
                        {canManage && (
                          <button
                            type="button"
                            onClick={() => {
                              setSelectedClassSubjectForTeacher(cs);
                              teacherForm.setData(
                                "teacher_id",
                                cs.active_teaching_assignment?.teacher_id?.toString() ||
                                  "",
                              );
                            }}
                            className="rounded px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition whitespace-nowrap"
                          >
                            {activeTeacher ? "Ganti Guru" : "Tugaskan"}
                          </button>
                        )}
                        {cs.teaching_assignments.length > 1 && (
                          <button
                            type="button"
                            onClick={() => setHistoryClassSubject(cs)}
                            className="inline-flex items-center gap-1 text-[11px] text-stone-500 hover:text-stone-800"
                          >
                            <History className="h-3 w-3" />
                            Riwayat
                          </button>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Schedule Slots Section */}
                  <div className="pt-4">
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-stone-700">
                        <Clock className="h-3.5 w-3.5 text-emerald-700" />
                        Jadwal Pelajaran Mingguan
                      </div>
                      {canManage && (
                        <button
                          type="button"
                          onClick={() => setSelectedClassSubjectForSchedule(cs)}
                          className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800"
                        >
                          <Plus className="h-3.5 w-3.5" />
                          Tambah Slot Jam
                        </button>
                      )}
                    </div>

                    {cs.schedule_slots.length === 0 ? (
                      <p className="text-xs text-stone-400 italic">
                        Belum ada slot waktu jadwal yang diatur.
                      </p>
                    ) : (
                      <div className="flex flex-wrap gap-2">
                        {cs.schedule_slots.map((slot) => (
                          <div
                            key={slot.id}
                            className="inline-flex items-center gap-2 rounded-lg border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs text-stone-800"
                          >
                            <Calendar className="h-3.5 w-3.5 text-emerald-600" />
                            <span className="font-bold text-emerald-950">
                              {slot.weekday_name}
                            </span>
                            <span className="font-mono text-stone-600">
                              {slot.starts_at} - {slot.ends_at}
                            </span>
                            {canManage && (
                              <button
                                type="button"
                                onClick={() =>
                                  handleDeleteSchedule(
                                    slot.id,
                                    `${slot.weekday_name} (${slot.starts_at}-${slot.ends_at})`,
                                  )
                                }
                                className="text-stone-400 hover:text-red-600 transition ml-1"
                                title="Hapus slot jadwal"
                              >
                                <Trash2 className="h-3.5 w-3.5" />
                              </button>
                            )}
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>

      {/* Modal Tambah Mapel */}
      {canManage && showAddSubjectModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Tambah Mata Pelajaran ke {classroom.full_name}
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Pilih mata pelajaran yang diajarkan pada kelas ini.
            </p>

            <form onSubmit={handleAddSubject} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Mata Pelajaran
                </label>
                <select
                  value={addSubjectForm.data.subject_id}
                  onChange={(e) =>
                    addSubjectForm.setData("subject_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">-- Pilih Mata Pelajaran --</option>
                  {availableSubjects.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.code} - {s.name} ({s.category})
                    </option>
                  ))}
                </select>
                {addSubjectForm.errors.subject_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {addSubjectForm.errors.subject_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Guru Pengampu (Opsional)
                </label>
                <select
                  value={addSubjectForm.data.teacher_id}
                  onChange={(e) =>
                    addSubjectForm.setData("teacher_id", e.target.value)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Pilih Guru Pengampu Nanti</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} {t.nip ? `(NIP: ${t.nip})` : ""}
                    </option>
                  ))}
                </select>
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowAddSubjectModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={
                    addSubjectForm.processing || !addSubjectForm.data.subject_id
                  }
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {addSubjectForm.processing ? "Menyimpan..." : "Simpan Mapel"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Ganti/Tugaskan Guru (Handover D-26/D-27) */}
      {canManage && selectedClassSubjectForTeacher && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Penugasan / Serah Terima Guru Pengampu
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Mapel: {selectedClassSubjectForTeacher.subject.name} (
              {classroom.full_name})
            </p>

            <div className="mt-3 rounded-lg bg-emerald-50 p-3 text-xs text-emerald-900 border border-emerald-200">
              <span className="font-bold">Ketentuan D-26 / D-27:</span>{" "}
              Penugasan guru baru akan mengakhiri penugasan guru sebelumnya
              secara historis tanpa mengubah catatan nilai atau absensi yang
              telah diinput terdahulu.
            </div>

            <form onSubmit={handleAssignTeacher} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Pilih Guru Pengampu Baru
                </label>
                <select
                  value={teacherForm.data.teacher_id}
                  onChange={(e) =>
                    teacherForm.setData("teacher_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">-- Pilih Guru --</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} {t.nip ? `(NIP: ${t.nip})` : ""}
                    </option>
                  ))}
                </select>
                {teacherForm.errors.teacher_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {teacherForm.errors.teacher_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Tanggal Mulai Berlaku
                </label>
                <input
                  type="date"
                  value={teacherForm.data.starts_on}
                  onChange={(e) =>
                    teacherForm.setData("starts_on", e.target.value)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setSelectedClassSubjectForTeacher(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={
                    teacherForm.processing || !teacherForm.data.teacher_id
                  }
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {teacherForm.processing ? "Menyimpan..." : "Simpan Penugasan"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Tambah Slot Jadwal */}
      {canManage && selectedClassSubjectForSchedule && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Tambah Slot Waktu Jadwal
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Mapel: {selectedClassSubjectForSchedule.subject.name} di{" "}
              {classroom.full_name}
            </p>

            {(scheduleForm.errors as any).schedule_conflict && (
              <div className="mt-3 flex items-start gap-2 rounded-lg bg-red-50 p-3 text-xs text-red-800 border border-red-200">
                <AlertCircle className="h-4 w-4 shrink-0 text-red-600 mt-0.5" />
                <div>
                  <span className="font-bold">Bentrok Jadwal:</span>
                  <p className="mt-0.5">
                    {(scheduleForm.errors as any).schedule_conflict}
                  </p>
                </div>
              </div>
            )}

            <form onSubmit={handleAddSchedule} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Hari
                </label>
                <select
                  value={scheduleForm.data.weekday}
                  onChange={(e) =>
                    scheduleForm.setData("weekday", e.target.value)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  {WEEKDAYS.map((w) => (
                    <option key={w.value} value={w.value}>
                      {w.label}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Jam Mulai
                  </label>
                  <input
                    type="time"
                    value={scheduleForm.data.starts_at}
                    onChange={(e) =>
                      scheduleForm.setData("starts_at", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Jam Selesai
                  </label>
                  <input
                    type="time"
                    value={scheduleForm.data.ends_at}
                    onChange={(e) =>
                      scheduleForm.setData("ends_at", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setSelectedClassSubjectForSchedule(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={scheduleForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {scheduleForm.processing ? "Menyimpan..." : "Simpan Jadwal"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Riwayat Penugasan Guru (D-26) */}
      {historyClassSubject && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Riwayat Penugasan Guru
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Mapel: {historyClassSubject.subject.name} ({classroom.full_name})
            </p>

            <div className="mt-4 divide-y divide-stone-100 max-h-64 overflow-y-auto">
              {historyClassSubject.teaching_assignments.map((ta) => (
                <div
                  key={ta.id}
                  className="py-2.5 flex items-center justify-between text-xs"
                >
                  <div>
                    <p className="font-semibold text-stone-900">
                      {ta.teacher.name}
                    </p>
                    <p className="text-stone-500">
                      {ta.starts_on} s/d {ta.ends_on || "Sekarang"}
                    </p>
                  </div>
                  <span
                    className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                      !ta.ends_on
                        ? "bg-emerald-100 text-emerald-800"
                        : "bg-stone-100 text-stone-600"
                    }`}
                  >
                    {!ta.ends_on ? "Aktif" : "Selesai"}
                  </span>
                </div>
              ))}
            </div>

            <div className="mt-5 flex justify-end">
              <button
                type="button"
                onClick={() => setHistoryClassSubject(null)}
                className="rounded-lg bg-stone-100 px-4 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-200"
              >
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
