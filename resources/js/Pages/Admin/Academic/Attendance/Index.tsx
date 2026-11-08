import React, { useState } from "react";
import { Link, useForm, router } from "@inertiajs/react";
import {
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Clock,
  Plus,
  Trash2,
  Users,
  AlertCircle,
  FileCheck,
  ShieldCheck,
  UserCheck,
  BookOpen,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { NativeSelect } from "@/Components/ui/native-select";

interface Teacher {
  id: number;
  name: string;
  nip: string | null;
}

interface Subject {
  id: number;
  code: string;
  name: string;
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

interface ClassSubject {
  id: number;
  classroom_id: number;
  subject_id: number;
  classroom: Classroom;
  subject: Subject;
  active_teaching_assignment?: {
    id: number;
    teacher: Teacher;
    starts_on: string;
    ends_on: string | null;
  } | null;
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
  created_by?: { id: number; name: string };
  finalized_by?: { id: number; name: string } | null;
  present_count: number;
  excused_count: number;
  sick_count: number;
  absent_count: number;
  total_recorded: number;
}

interface Delegation {
  id: number;
  user_id: number;
  capability: string;
  valid_from: string;
  valid_until: string;
  reason: string | null;
  revoked_at: string | null;
  user: { id: number; name: string; username: string };
  granted_by: { id: number; name: string };
}

interface DataEntryUser {
  id: number;
  name: string;
  username: string;
}

interface Props {
  classSubject: ClassSubject;
  sessions: AttendanceSession[];
  nextMeetingNumber: number;
  canCreate: boolean;
  canManageDelegations: boolean;
  delegations: Delegation[];
  dataEntryUsers: DataEntryUser[];
}

export default function AttendanceIndex({
  classSubject,
  sessions,
  nextMeetingNumber,
  canCreate,
  canManageDelegations,
  delegations,
  dataEntryUsers,
}: Props) {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showDelegationModal, setShowDelegationModal] = useState(false);
  const [sessionToDelete, setSessionToDelete] =
    useState<AttendanceSession | null>(null);

  const createForm = useForm({
    meeting_number: nextMeetingNumber,
    held_on: new Date().toISOString().split("T")[0],
    topic: "",
  });

  const delegationForm = useForm({
    user_id: dataEntryUsers[0]?.id || "",
    valid_from: new Date().toISOString().split("T")[0],
    valid_until: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
      .toISOString()
      .split("T")[0],
    reason: "",
  });

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post(
      `/academic/class-subjects/${classSubject.id}/attendance-sessions`,
      {
        onSuccess: () => {
          setShowCreateModal(false);
          createForm.reset();
        },
      },
    );
  };

  const handleDelegationSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    delegationForm.post(
      `/academic/class-subjects/${classSubject.id}/delegations`,
      {
        onSuccess: () => {
          setShowDelegationModal(false);
          delegationForm.reset();
        },
      },
    );
  };

  const handleRevokeDelegation = (delegationId: number) => {
    if (
      confirm("Apakah Anda yakin ingin mencabut delegasi input presensi ini?")
    ) {
      router.post(`/academic/delegations/${delegationId}/revoke`);
    }
  };

  const handleDeleteSession = (session: AttendanceSession) => {
    setSessionToDelete(session);
  };

  const confirmDeleteSession = () => {
    if (!sessionToDelete) return;
    router.delete(`/academic/attendance-sessions/${sessionToDelete.id}`, {
      onSuccess: () => setSessionToDelete(null),
    });
  };

  const finalizedSessionsCount = sessions.filter(
    (s) => s.status === "finalized",
  ).length;
  const draftSessionsCount = sessions.filter(
    (s) => s.status === "draft",
  ).length;

  return (
    <AppLayout
      title={`Presensi: ${classSubject.subject.name} - ${classSubject.classroom.full_name}`}
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              href={`/academic/classrooms/${classSubject.classroom_id}/subjects`}
              className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary transition-colors"
            >
              <ArrowLeft className="size-3.5" />
              <span>Mapel Kelas</span>
            </Link>
            <span className="text-xs text-muted-foreground">•</span>
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              {classSubject.classroom.full_name}
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              {classSubject.classroom.term?.academic_year?.name} (
              {classSubject.classroom.term?.full_name})
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Presensi: {classSubject.subject.name}
          </h1>
        </div>
      }
    >
      <div className="space-y-6">
        {/* Context Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div className="bg-card border border-border rounded-xl p-5 shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">
                Guru Pengampu
              </span>
              <UserCheck className="w-4 h-4 text-primary" />
            </div>
            <div className="text-base font-bold text-foreground">
              {classSubject.active_teaching_assignment?.teacher.name ||
                "Belum ditugaskan"}
            </div>
            <div className="text-xs text-muted-foreground mt-1">
              {classSubject.active_teaching_assignment?.teacher.nip
                ? `NIP: ${classSubject.active_teaching_assignment.teacher.nip}`
                : "Pengampu aktif kelas"}
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-5 shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">
                Pertemuan Sah (Finalized)
              </span>
              <FileCheck className="w-4 h-4 text-emerald-600" />
            </div>
            <div className="text-2xl font-bold text-emerald-700">
              {finalizedSessionsCount}{" "}
              <span className="text-sm font-normal text-muted-foreground">
                pertemuan
              </span>
            </div>
            <div className="text-xs text-muted-foreground mt-1">
              Masuk sebagai penyebut rasio kehadiran resmi
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-5 shadow-sm">
            <div className="flex items-center justify-between text-muted-foreground mb-2">
              <span className="text-xs font-semibold uppercase tracking-wider">
                Sesi Draf Berjalan
              </span>
              <Clock className="w-4 h-4 text-amber-600" />
            </div>
            <div className="text-2xl font-bold text-amber-700">
              {draftSessionsCount}{" "}
              <span className="text-sm font-normal text-muted-foreground">
                sesi
              </span>
            </div>
            <div className="text-xs text-muted-foreground mt-1">
              Draf tidak dihitung sebagai pertemuan resmi
            </div>
          </div>

          <div className="bg-card border border-border rounded-xl p-5 shadow-sm flex flex-col justify-between">
            <div className="flex items-center justify-between text-muted-foreground mb-1">
              <span className="text-xs font-semibold uppercase tracking-wider">
                Aksi & Delegasi
              </span>
              <ShieldCheck className="w-4 h-4 text-primary" />
            </div>
            <div className="space-y-2 mt-2">
              {canCreate && (
                <button
                  onClick={() => setShowCreateModal(true)}
                  className="w-full inline-flex items-center justify-center space-x-2 px-3 py-2 bg-primary text-primary-foreground text-xs font-semibold rounded-lg hover:bg-primary/90 transition shadow-sm"
                >
                  <Plus className="w-4 h-4" />
                  <span>Buka Pertemuan Baru</span>
                </button>
              )}
              {canManageDelegations && (
                <button
                  onClick={() => setShowDelegationModal(true)}
                  className="w-full inline-flex items-center justify-center space-x-2 px-3 py-2 bg-muted text-foreground text-xs font-semibold rounded-lg hover:bg-muted/80 border border-border transition"
                >
                  <Users className="w-3.5 h-3.5" />
                  <span>Kelola Delegasi Input (Q6.3)</span>
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Sessions List Section */}
        <div className="bg-card border border-border rounded-xl overflow-hidden shadow-sm">
          <div className="px-6 py-4 border-b border-border flex items-center justify-between">
            <div>
              <h2 className="text-base font-bold text-foreground">
                Daftar Pertemuan Presensi
              </h2>
              <p className="text-xs text-muted-foreground">
                Urutan pertemuan berlanjut secara berkesinambungan lintas
                penugasan guru.
              </p>
            </div>
            <span className="text-xs bg-muted px-2.5 py-1 rounded-full text-muted-foreground font-medium">
              Total: {sessions.length} pertemuan
            </span>
          </div>

          {sessions.length === 0 ? (
            <div className="p-12 text-center">
              <Calendar className="w-12 h-12 text-muted-foreground mx-auto mb-3 opacity-40" />
              <h3 className="text-sm font-semibold text-foreground">
                Belum ada pertemuan dibuka
              </h3>
              <p className="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
                Silakan buka sesi pertemuan baru untuk mulai mencatat kehadiran
                siswa pada mata pelajaran ini.
              </p>
              {canCreate && (
                <button
                  onClick={() => setShowCreateModal(true)}
                  className="mt-4 inline-flex items-center space-x-2 px-4 py-2 bg-primary text-primary-foreground text-xs font-semibold rounded-lg hover:bg-primary/90 transition"
                >
                  <Plus className="w-4 h-4" />
                  <span>Buka Pertemuan Pertama</span>
                </button>
              )}
            </div>
          ) : (
            <div className="divide-y divide-border">
              {sessions.map((session) => (
                <div
                  key={session.id}
                  className="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-muted/30 transition-colors"
                >
                  <div className="flex items-start space-x-4">
                    <div className="w-12 h-12 rounded-xl bg-primary/10 text-primary flex flex-col items-center justify-center font-bold shrink-0">
                      <span className="text-[10px] uppercase tracking-wider font-semibold text-muted-foreground">
                        Ke
                      </span>
                      <span className="text-base leading-none">
                        {session.meeting_number}
                      </span>
                    </div>

                    <div>
                      <div className="flex items-center space-x-2.5">
                        <h3 className="text-base font-bold text-foreground">
                          Pertemuan Ke-{session.meeting_number}
                        </h3>
                        {session.status === "finalized" ? (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <CheckCircle2 className="w-3 h-3 mr-1" />
                            Disahkan (Finalized)
                          </span>
                        ) : (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                            <Clock className="w-3 h-3 mr-1" />
                            Draf (Belum Sah)
                          </span>
                        )}
                      </div>

                      <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground mt-1">
                        <span className="flex items-center">
                          <Calendar className="w-3.5 h-3.5 mr-1 opacity-70" />
                          {session.held_on}
                        </span>
                        {session.topic && (
                          <span className="flex items-center font-medium text-foreground/80">
                            <BookOpen className="w-3.5 h-3.5 mr-1 opacity-70" />
                            {session.topic}
                          </span>
                        )}
                        <span>Oleh: {session.created_by?.name || "Staf"}</span>
                        {session.finalized_by && (
                          <span>
                            Disahkan oleh: {session.finalized_by.name}
                          </span>
                        )}
                      </div>

                      {/* Presence breakdown badge row */}
                      <div className="flex items-center space-x-2 mt-2 text-xs">
                        <span className="px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-medium border border-emerald-100">
                          Hadir: {session.present_count}
                        </span>
                        <span className="px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-medium border border-blue-100">
                          Izin: {session.excused_count}
                        </span>
                        <span className="px-2 py-0.5 rounded bg-amber-50 text-amber-700 font-medium border border-amber-100">
                          Sakit: {session.sick_count}
                        </span>
                        <span className="px-2 py-0.5 rounded bg-rose-50 text-rose-700 font-medium border border-rose-100">
                          Alpa: {session.absent_count}
                        </span>
                        {session.status === "draft" && (
                          <span className="text-[11px] text-muted-foreground">
                            (Belum dicatat bukan alpa)
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  <div className="flex items-center space-x-2.5 self-end md:self-center">
                    <Link
                      href={`/academic/attendance-sessions/${session.id}`}
                      className="px-4 py-2 bg-primary text-primary-foreground text-xs font-semibold rounded-lg hover:bg-primary/90 transition shadow-sm"
                    >
                      {session.status === "finalized"
                        ? "Lihat Presensi"
                        : "Buka Lembar Presensi"}
                    </Link>

                    {session.status === "draft" && (
                      <button
                        onClick={() => handleDeleteSession(session)}
                        className="p-2 text-muted-foreground hover:text-destructive hover:bg-destructive/10 rounded-lg transition"
                        title="Hapus Sesi Draf"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Modal: Buka Pertemuan Baru */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl max-w-md w-full p-6 shadow-xl">
            <h3 className="text-lg font-bold text-foreground mb-1">
              Buka Sesi Pertemuan Baru
            </h3>
            <p className="text-xs text-muted-foreground mb-4">
              Nomor pertemuan diurutkan secara berkelanjutan untuk menjaga
              integritas rekap absensi.
            </p>

            <form onSubmit={handleCreateSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Nomor Pertemuan
                </label>
                <input
                  type="number"
                  min="1"
                  value={createForm.data.meeting_number}
                  onChange={(e) =>
                    createForm.setData(
                      "meeting_number",
                      parseInt(e.target.value) || 1,
                    )
                  }
                  className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                  required
                />
                {createForm.errors.meeting_number && (
                  <p className="text-xs text-destructive mt-1">
                    {createForm.errors.meeting_number}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Tanggal Pertemuan
                </label>
                <input
                  type="date"
                  value={createForm.data.held_on}
                  onChange={(e) =>
                    createForm.setData("held_on", e.target.value)
                  }
                  className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                  required
                />
                {createForm.errors.held_on && (
                  <p className="text-xs text-destructive mt-1">
                    {createForm.errors.held_on}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Topik / Pokok Bahasan (Opsional)
                </label>
                <input
                  type="text"
                  placeholder="Misal: Bab 1 Pengenalan Konsep"
                  value={createForm.data.topic}
                  onChange={(e) => createForm.setData("topic", e.target.value)}
                  className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                />
                {createForm.errors.topic && (
                  <p className="text-xs text-destructive mt-1">
                    {createForm.errors.topic}
                  </p>
                )}
              </div>

              <div className="flex items-center justify-end space-x-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted transition"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="px-4 py-2 bg-primary text-primary-foreground text-xs font-semibold rounded-lg hover:bg-primary/90 transition shadow-sm disabled:opacity-50"
                >
                  {createForm.processing ? "Menyimpan..." : "Buat Pertemuan"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal: Kelola Delegasi Input (Q6.3) */}
      {showDelegationModal && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl max-w-xl w-full p-6 shadow-xl max-h-[90vh] overflow-y-auto">
            <h3 className="text-lg font-bold text-foreground mb-1">
              Kelola Delegasi Input Presensi (Q6.3)
            </h3>
            <p className="text-xs text-muted-foreground mb-4">
              Master Admin / Academic Admin dapat mendelegasikan bantuan entri
              draf presensi kepada staf Data Entry.
            </p>

            {/* Existing Delegations Table */}
            <div className="mb-6">
              <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-2">
                Daftar Riwayat Delegasi
              </h4>
              {delegations.length === 0 ? (
                <p className="text-xs text-muted-foreground italic bg-muted/40 p-3 rounded-lg border border-border">
                  Belum ada delegasi yang diberikan pada mata pelajaran kelas
                  ini.
                </p>
              ) : (
                <div className="border border-border rounded-lg overflow-hidden divide-y divide-border">
                  {delegations.map((del) => {
                    const isRevoked = del.revoked_at !== null;
                    return (
                      <div
                        key={del.id}
                        className="p-3 flex items-center justify-between text-xs"
                      >
                        <div>
                          <div className="font-semibold text-foreground flex items-center space-x-2">
                            <span>
                              {del.user.name} ({del.user.username})
                            </span>
                            {isRevoked ? (
                              <span className="px-1.5 py-0.5 rounded text-[10px] bg-rose-100 text-rose-800">
                                Dicabut
                              </span>
                            ) : (
                              <span className="px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-800 font-medium">
                                Aktif
                              </span>
                            )}
                          </div>
                          <div className="text-muted-foreground text-[11px] mt-0.5">
                            Berlaku: {del.valid_from} s.d. {del.valid_until}
                          </div>
                          {del.reason && (
                            <div className="text-muted-foreground text-[11px] italic mt-0.5">
                              Alasan: {del.reason}
                            </div>
                          )}
                        </div>
                        {!isRevoked && (
                          <button
                            type="button"
                            onClick={() => handleRevokeDelegation(del.id)}
                            className="px-2.5 py-1 text-xs text-destructive hover:bg-destructive/10 rounded transition"
                          >
                            Cabut
                          </button>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            {/* Grant New Delegation Form */}
            <form
              onSubmit={handleDelegationSubmit}
              className="space-y-4 pt-4 border-t border-border"
            >
              <h4 className="text-xs font-semibold uppercase tracking-wider text-primary">
                Beri Delegasi Baru
              </h4>

              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Pilih Staf Data Entry
                </label>
                <select
                  value={delegationForm.data.user_id}
                  onChange={(e) =>
                    delegationForm.setData("user_id", e.target.value)
                  }
                  className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                  required
                >
                  <option value="" disabled>
                    Pilih staf data entry...
                  </option>
                  {dataEntryUsers.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} ({u.username})
                    </option>
                  ))}
                </select>
                {delegationForm.errors.user_id && (
                  <p className="text-xs text-destructive mt-1">
                    {delegationForm.errors.user_id}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Berlaku Mulai
                  </label>
                  <input
                    type="date"
                    value={delegationForm.data.valid_from}
                    onChange={(e) =>
                      delegationForm.setData("valid_from", e.target.value)
                    }
                    className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    required
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Berlaku Sampai
                  </label>
                  <input
                    type="date"
                    value={delegationForm.data.valid_until}
                    onChange={(e) =>
                      delegationForm.setData("valid_until", e.target.value)
                    }
                    className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Alasan Penugasan Delegasi (Opsional)
                </label>
                <input
                  type="text"
                  placeholder="Misal: Bantuan entri absensi saat guru berhalangan"
                  value={delegationForm.data.reason}
                  onChange={(e) =>
                    delegationForm.setData("reason", e.target.value)
                  }
                  className="w-full px-3 py-2 bg-background border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:outline-none"
                />
              </div>

              <div className="flex items-center justify-end space-x-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setShowDelegationModal(false)}
                  className="px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted transition"
                >
                  Tutup
                </button>
                <button
                  type="submit"
                  disabled={delegationForm.processing}
                  className="px-4 py-2 bg-primary text-primary-foreground text-xs font-semibold rounded-lg hover:bg-primary/90 transition shadow-sm disabled:opacity-50"
                >
                  {delegationForm.processing
                    ? "Menyimpan..."
                    : "Berikan Delegasi"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Confirmation Modal: Hapus Sesi */}
      {sessionToDelete && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl max-w-sm w-full p-6 shadow-xl">
            <div className="flex items-center space-x-3 text-destructive mb-3">
              <AlertCircle className="w-6 h-6" />
              <h3 className="text-base font-bold text-foreground">
                Hapus Sesi Draf?
              </h3>
            </div>
            <p className="text-xs text-muted-foreground mb-5 leading-relaxed">
              Apakah Anda yakin ingin menghapus draf pertemuan ke-
              {sessionToDelete.meeting_number}? Tindakan ini tidak dapat
              dibatalkan.
            </p>
            <div className="flex items-center justify-end space-x-2">
              <button
                type="button"
                onClick={() => setSessionToDelete(null)}
                className="px-4 py-2 border border-border text-foreground text-xs font-medium rounded-lg hover:bg-muted transition"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={confirmDeleteSession}
                className="px-4 py-2 bg-destructive text-destructive-foreground text-xs font-semibold rounded-lg hover:bg-destructive/90 transition"
              >
                Hapus Sesi
              </button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
