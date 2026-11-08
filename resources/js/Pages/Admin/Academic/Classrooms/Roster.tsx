import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  ArrowRightLeft,
  Plus,
  Trash2,
  UserCheck,
  Users,
  ShieldAlert,
  UserPlus,
  BookOpen,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface Teacher {
  id: number;
  name: string;
  nip: string | null;
}

interface Student {
  id: number;
  nis: string;
  name: string;
  gender: "L" | "P";
}

interface Enrollment {
  id: number;
  student_id: number;
  classroom_id: number;
  term_id: number;
  starts_on: string;
  ends_on: string | null;
  status: "active" | "transferred" | "graduated" | "withdrawn";
  created_at: string;
  student: Student;
}

interface HomeroomAssignment {
  id: number;
  teacher_id: number;
  status: "active" | "inactive";
  teacher: Teacher;
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
  active_homeroom?: HomeroomAssignment | null;
}

interface Props {
  classroom: Classroom;
  enrollments: Enrollment[];
  availableStudents: Student[];
  otherClassrooms?: Array<{ id: number; name: string }>;
  teachers: Teacher[];
}

export default function ClassroomRoster({
  classroom,
  enrollments,
  availableStudents,
  otherClassrooms = [],
  teachers,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const roles = auth.user?.roles ?? [];
  const canManage = roles.some((role) =>
    ["master_admin", "academic_admin"].includes(role),
  );
  const canEnroll = canManage || roles.includes("data_entry_admin");
  const [showEnrollModal, setShowEnrollModal] = useState(false);
  const [showHomeroomModal, setShowHomeroomModal] = useState(false);
  const [transferModalEnrollment, setTransferModalEnrollment] =
    useState<Enrollment | null>(null);
  const [studentSearch, setStudentSearch] = useState("");

  const enrollForm = useForm({
    student_id: "",
    starts_on: new Date().toISOString().split("T")[0],
  });

  const homeroomForm = useForm({
    teacher_id: classroom.active_homeroom?.teacher_id?.toString() || "",
  });

  const transferForm = useForm({
    target_classroom_id: "",
    transfer_date: new Date().toISOString().split("T")[0],
    reason: "",
  });

  const openTransferModal = (enrollment: Enrollment) => {
    setTransferModalEnrollment(enrollment);
    transferForm.setData({
      target_classroom_id:
        otherClassrooms && otherClassrooms.length > 0
          ? otherClassrooms[0].id.toString()
          : "",
      transfer_date: new Date().toISOString().split("T")[0],
      reason: "",
    });
  };

  const handleTransfer = (e: React.FormEvent) => {
    e.preventDefault();
    if (!transferModalEnrollment) return;
    transferForm.post(
      `/academic/enrollments/${transferModalEnrollment.id}/transfer`,
      {
        onSuccess: () => {
          setTransferModalEnrollment(null);
          transferForm.reset();
        },
      },
    );
  };

  const handleEnroll = (e: React.FormEvent) => {
    e.preventDefault();
    enrollForm.post(`/academic/classrooms/${classroom.id}/enrollments`, {
      onSuccess: () => {
        enrollForm.reset("student_id");
        setShowEnrollModal(false);
      },
    });
  };

  const handleAssignHomeroom = (e: React.FormEvent) => {
    e.preventDefault();
    homeroomForm.post(`/academic/classrooms/${classroom.id}/homeroom`, {
      onSuccess: () => {
        setShowHomeroomModal(false);
      },
    });
  };

  const handleWithdraw = (enrollmentId: number, studentName: string) => {
    if (
      confirm(
        `Apakah Anda yakin ingin mengeluarkan siswa ${studentName} dari kelas ini?`,
      )
    ) {
      router.delete(`/academic/enrollments/${enrollmentId}`);
    }
  };

  const filteredStudents = availableStudents.filter(
    (s) =>
      s.name.toLowerCase().includes(studentSearch.toLowerCase()) ||
      s.nis.toLowerCase().includes(studentSearch.toLowerCase()),
  );

  const activeEnrollments = enrollments.filter((e) => e.status === "active");

  return (
    <AppLayout
      title={`Roster Anggota - ${classroom.full_name}`}
      contextHeader={{
        title: `Roster Anggota: ${classroom.full_name}`,
        badge: `Roster v${classroom.roster_version}`,
        description: `Tingkat ${classroom.grade_level} • Wali Kelas: ${classroom.active_homeroom?.teacher.name || "Belum Ditugaskan"} • Total ${activeEnrollments.length} siswa aktif`,
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
              href={`/academic/classrooms/${classroom.id}/subjects`}
              className="inline-flex items-center gap-1.5 text-xs text-primary hover:underline font-medium"
            >
              <BookOpen className="w-3.5 h-3.5" />
              Mapel & Jadwal
            </Link>
          </div>
          <div className="flex items-center gap-2">
            {canManage && (
              <Button
                variant="outline"
                type="button"
                onClick={() => setShowHomeroomModal(true)}
                className="inline-flex items-center gap-1.5"
              >
                <UserCheck className="h-4 w-4" />
                {classroom.active_homeroom
                  ? "Ganti Wali Kelas"
                  : "Tugaskan Wali Kelas"}
              </Button>
            )}
            {canEnroll && (
              <Button
                type="button"
                onClick={() => setShowEnrollModal(true)}
                className="inline-flex items-center gap-2"
              >
                <Plus className="h-4 w-4" />
                Daftarkan Siswa
              </Button>
            )}
          </div>
        </div>

        {/* Enrolled Students Table */}
        <div className="rounded-2xl border border-stone-200 bg-white shadow-xs overflow-hidden">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between p-5 border-b border-stone-200 gap-3">
            <div className="flex items-center gap-2">
              <Users className="h-5 w-5 text-emerald-700" />
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Daftar Siswa Kelas
                </h3>
                <p className="text-xs text-stone-500">
                  Total {activeEnrollments.length} siswa aktif terdaftar
                </p>
              </div>
            </div>

            {canEnroll && (
              <button
                type="button"
                onClick={() => setShowEnrollModal(true)}
                className="inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-800 self-start sm:self-auto"
              >
                <UserPlus className="h-4 w-4" />
                Daftarkan Siswa
              </button>
            )}
          </div>

          {enrollments.length === 0 ? (
            <div className="p-12 text-center">
              <Users className="mx-auto h-10 w-10 text-stone-300 mb-3" />
              <h4 className="text-sm font-semibold text-stone-800">
                Belum ada siswa terdaftar
              </h4>
              <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
                Daftarkan siswa ke rombongan belajar ini melalui tombol
                &quot;Daftarkan Siswa&quot;.
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b border-stone-200 bg-stone-50/70 text-xs font-semibold uppercase tracking-wider text-stone-600">
                  <tr>
                    <th className="px-5 py-3 w-12 text-center">No</th>
                    <th className="px-5 py-3">NIS</th>
                    <th className="px-5 py-3">Nama Siswa</th>
                    <th className="px-5 py-3">L/P</th>
                    <th className="px-5 py-3">Tanggal Terdaftar</th>
                    <th className="px-5 py-3">Status</th>
                    {canManage && (
                      <th className="px-5 py-3 text-right">Aksi</th>
                    )}
                  </tr>
                </thead>
                <tbody className="divide-y divide-stone-100">
                  {enrollments.map((enrollment, index) => (
                    <tr
                      key={enrollment.id}
                      className={`transition hover:bg-stone-50/60 ${
                        enrollment.status !== "active"
                          ? "opacity-60 bg-stone-50/30"
                          : ""
                      }`}
                    >
                      <td className="px-5 py-3 text-center text-xs text-stone-400">
                        {index + 1}
                      </td>
                      <td className="px-5 py-3 font-mono text-xs font-medium text-stone-700">
                        {enrollment.student.nis}
                      </td>
                      <td className="px-5 py-3 font-semibold text-stone-900">
                        {enrollment.student.name}
                      </td>
                      <td className="px-5 py-3 text-xs text-stone-600">
                        <span
                          className={`inline-block px-2 py-0.5 rounded font-bold ${
                            enrollment.student.gender === "L"
                              ? "bg-blue-50 text-blue-700"
                              : "bg-rose-50 text-rose-700"
                          }`}
                        >
                          {enrollment.student.gender === "L"
                            ? "Laki-laki"
                            : "Perempuan"}
                        </span>
                      </td>
                      <td className="px-5 py-3 text-xs text-stone-500">
                        {enrollment.starts_on}
                      </td>
                      <td className="px-5 py-3 text-xs">
                        <span
                          className={`inline-block px-2 py-0.5 rounded text-[11px] font-bold ${
                            enrollment.status === "active"
                              ? "bg-emerald-100 text-emerald-800"
                              : enrollment.status === "transferred"
                                ? "bg-amber-100 text-amber-800"
                                : "bg-stone-200 text-stone-600"
                          }`}
                        >
                          {enrollment.status === "active"
                            ? "Aktif"
                            : enrollment.status === "transferred"
                              ? "Pindah Kelas"
                              : "Nonaktif / Keluar"}
                        </span>
                      </td>
                      {canManage && (
                        <td className="px-5 py-3 text-right">
                          {enrollment.status === "active" && (
                            <div className="flex items-center justify-end gap-1.5">
                              {otherClassrooms &&
                                otherClassrooms.length > 0 && (
                                  <button
                                    type="button"
                                    onClick={() =>
                                      openTransferModal(enrollment)
                                    }
                                    className="inline-flex items-center gap-1 rounded px-2.5 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50 transition"
                                    title="Pindahkan ke rombel lain"
                                  >
                                    <ArrowRightLeft className="h-3.5 w-3.5" />
                                    Pindah
                                  </button>
                                )}
                              <button
                                type="button"
                                onClick={() =>
                                  handleWithdraw(
                                    enrollment.id,
                                    enrollment.student.name,
                                  )
                                }
                                className="inline-flex items-center gap-1 rounded px-2.5 py-1 text-xs font-medium text-red-700 hover:bg-red-50 transition"
                                title="Keluarkan dari rombel"
                              >
                                <Trash2 className="h-3.5 w-3.5" />
                                Keluarkan
                              </button>
                            </div>
                          )}
                        </td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Modal Daftarkan Siswa */}
      {canEnroll && showEnrollModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Daftarkan Siswa ke {classroom.full_name}
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Pilih siswa aktif yang belum terdaftar di kelas lain pada semester
              ini.
            </p>

            <form onSubmit={handleEnroll} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Cari & Pilih Siswa
                </label>
                <input
                  type="text"
                  value={studentSearch}
                  onChange={(e) => setStudentSearch(e.target.value)}
                  placeholder="Ketik nama atau NIS untuk memfilter..."
                  className="w-full mb-2 rounded-lg border border-stone-300 px-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                />
                <select
                  value={enrollForm.data.student_id}
                  onChange={(e) =>
                    enrollForm.setData("student_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  size={6}
                >
                  <option value="" disabled>
                    -- Pilih Siswa ({filteredStudents.length} tersedia) --
                  </option>
                  {filteredStudents.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.nis} - {s.name} ({s.gender})
                    </option>
                  ))}
                </select>
                {enrollForm.errors.student_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {enrollForm.errors.student_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Tanggal Terdaftar
                </label>
                <input
                  type="date"
                  value={enrollForm.data.starts_on}
                  onChange={(e) =>
                    enrollForm.setData("starts_on", e.target.value)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowEnrollModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={
                    enrollForm.processing || !enrollForm.data.student_id
                  }
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {enrollForm.processing
                    ? "Mendaftarkan..."
                    : "Daftarkan ke Kelas"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Tugaskan Wali Kelas */}
      {canManage && showHomeroomModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Penugasan Wali Kelas {classroom.full_name}
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Tiap rombel memiliki tepat 1 wali kelas aktif. Penugasan baru akan
              menonaktifkan wali kelas sebelumnya.
            </p>

            <form onSubmit={handleAssignHomeroom} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Pilih Guru
                </label>
                <select
                  value={homeroomForm.data.teacher_id}
                  onChange={(e) =>
                    homeroomForm.setData("teacher_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Pilih Guru Wali</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} {t.nip ? `(NIP: ${t.nip})` : ""}
                    </option>
                  ))}
                </select>
                {homeroomForm.errors.teacher_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {homeroomForm.errors.teacher_id}
                  </p>
                )}
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowHomeroomModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={
                    homeroomForm.processing || !homeroomForm.data.teacher_id
                  }
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {homeroomForm.processing
                    ? "Menyimpan..."
                    : "Simpan Wali Kelas"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
      {/* Modal Pindah Kelas Siswa */}
      {canManage && transferModalEnrollment && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Pindahkan Siswa Antarkelas (Transfer)
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Pindahkan keanggotaan {transferModalEnrollment.student.name} (
              {transferModalEnrollment.student.nis}) ke rombel lain pada
              semester yang sama.
            </p>

            <form onSubmit={handleTransfer} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Rombel Tujuan
                </label>
                <select
                  value={transferForm.data.target_classroom_id}
                  onChange={(e) =>
                    transferForm.setData("target_classroom_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="" disabled>
                    -- Pilih Rombel Tujuan --
                  </option>
                  {(otherClassrooms || []).map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
                </select>
                {transferForm.errors.target_classroom_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {transferForm.errors.target_classroom_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Tanggal Efektif Masuk Rombel Baru
                </label>
                <input
                  type="date"
                  value={transferForm.data.transfer_date}
                  onChange={(e) =>
                    transferForm.setData("transfer_date", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
                <p className="mt-1 text-[11px] text-stone-500">
                  Presensi dan nilai di rombel lama akan dibekukan hingga
                  tanggal sebelum transfer. Mulai tanggal ini, siswa resmi aktif
                  di rombel tujuan.
                </p>
                {transferForm.errors.transfer_date && (
                  <p className="mt-1 text-xs text-red-600">
                    {transferForm.errors.transfer_date}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Alasan Pemindahan (Opsional)
                </label>
                <textarea
                  value={transferForm.data.reason}
                  onChange={(e) =>
                    transferForm.setData("reason", e.target.value)
                  }
                  rows={3}
                  placeholder="Contoh: Penyesuaian kuota rombel atau arahan wali kelas..."
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
                {transferForm.errors.reason && (
                  <p className="mt-1 text-xs text-red-600">
                    {transferForm.errors.reason}
                  </p>
                )}
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setTransferModalEnrollment(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={
                    transferForm.processing ||
                    !transferForm.data.target_classroom_id ||
                    !transferForm.data.transfer_date
                  }
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {transferForm.processing
                    ? "Memindahkan..."
                    : "Konfirmasi Pindah Kelas"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
