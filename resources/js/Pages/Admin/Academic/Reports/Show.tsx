import React, { useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  CheckCircle2,
  AlertTriangle,
  Lock,
  Save,
  ShieldCheck,
  User,
  School,
  Calendar,
  BookOpen,
  FileText,
  Clock,
  Layers,
  Sparkles,
  Download,
  Globe,
  History,
  RotateCcw,
  AlertOctagon,
  GitBranch,
  Ban,
  Award,
  Plus,
  Trash2,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface StudentInfo {
  id: number;
  nis: string;
  name: string;
  gender: string;
}

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

interface SubjectItem {
  id: number;
  class_subject_id: number;
  subject_snapshot: {
    id: number;
    code: string;
    name: string;
  };
  teaching_history_snapshot: {
    teacher_name: string;
    teacher_nip: string | null;
  }[];
  component_breakdown_snapshot: Record<
    string,
    {
      code: string;
      name: string;
      weight_percentage: string;
      score_average: string | null;
      contribution: string | null;
      contribution_rounded: string | null;
      is_empty: boolean;
    }
  >;
  kkm_snapshot: string;
  final_score: string | null;
  kkm_status: string;
}

interface LineageItem {
  id: number;
  revision: number;
  status: string;
  availability: string;
  is_current: boolean;
  published_at: string | null;
  revoked_at: string | null;
  lifecycle_reason: string | null;
}

interface ReportDetail {
  id: number;
  enrollment_id: number;
  revision: number;
  supersedes_id: number | null;
  status: string; // draft, finalized, published, revoked
  availability: string;
  version: number;
  source_version: string;
  homeroom_note: string | null;
  lifecycle_reason: string | null;
  calculation_version: string;
  generated_at: string | null;
  finalized_at: string | null;
  finalized_by: { id: number; name: string } | null;
  published_at: string | null;
  published_by: { id: number; name: string } | null;
  revoked_at: string | null;
  revoked_by: { id: number; name: string } | null;
  attendance_summary: {
    total_sessions: number;
    present: number;
    excused: number;
    sick: number;
    absent: number;
    rate: string | null;
  } | null;
  identity_snapshot: any | null;
  is_source_stale: boolean;
  fresh_source_version: string;
  can_finalize: boolean;
  blocked_reasons: string[];
  items: SubjectItem[];
  extracurriculars?: ExtracurricularItem[];
  p5_assessments?: P5Item[];
}

export interface ExtracurricularItem {
  id: number;
  activity_name: string;
  predicate: "A" | "B" | "C" | "D";
  predicate_label: string;
  description: string;
}

export interface P5Item {
  id: number;
  project_title: string;
  dimension: string;
  predicate: "BB" | "MB" | "BSH" | "SAB";
  predicate_label: string;
  description: string;
}

interface Props {
  report: ReportDetail;
  student: StudentInfo;
  classroom: ClassroomInfo;
  lineage?: LineageItem[];
  canUpdateNote: boolean;
  canManageReport: boolean;
  canDownloadPdf: boolean;
  canPrepareFile: boolean;
}

export default function Show({
  report,
  student,
  classroom,
  lineage = [],
  canUpdateNote,
  canManageReport,
  canDownloadPdf,
  canPrepareFile,
}: Props) {
  const pageErrors = usePage().props.errors as Record<string, string>;
  const [homeroomNote, setHomeroomNote] = useState(report.homeroom_note || "");
  const [isSavingNote, setIsSavingNote] = useState(false);
  const [isFinalizing, setIsFinalizing] = useState(false);
  const [showFinalizeModal, setShowFinalizeModal] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);
  const [showPublishModal, setShowPublishModal] = useState(false);
  const [isCreatingRevision, setIsCreatingRevision] = useState(false);
  const [showRevisionModal, setShowRevisionModal] = useState(false);
  const [revisionReason, setRevisionReason] = useState("");
  const [isRevoking, setIsRevoking] = useState(false);
  const [showRevokeModal, setShowRevokeModal] = useState(false);
  const [revokeReason, setRevokeReason] = useState("");

  const [showExtraModal, setShowExtraModal] = useState(false);
  const [extraActivity, setExtraActivity] = useState("");
  const [extraPredicate, setExtraPredicate] = useState<"A" | "B" | "C" | "D">(
    "A",
  );
  const [extraDescription, setExtraDescription] = useState("");
  const [isSavingExtra, setIsSavingExtra] = useState(false);

  const [showP5Modal, setShowP5Modal] = useState(false);
  const [p5Title, setP5Title] = useState("");
  const [p5Dimension, setP5Dimension] = useState(
    "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
  );
  const [p5Predicate, setP5Predicate] = useState<"BB" | "MB" | "BSH" | "SAB">(
    "BSH",
  );
  const [p5Description, setP5Description] = useState("");
  const [isSavingP5, setIsSavingP5] = useState(false);

  const isDraft = report.status === "draft";
  const canEditExtras = isDraft && canManageReport;
  const isFinalized = report.status === "finalized";
  const isPublished = report.status === "published";
  const isRevoked = report.status === "revoked";
  const needsRevision = report.availability === "needs_revision";
  const isSuperseded = report.availability === "superseded";

  const handleAddExtra = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSavingExtra(true);
    router.post(
      `/academic/report-cards/${report.id}/extracurricular`,
      {
        activity_name: extraActivity,
        predicate: extraPredicate,
        description: extraDescription,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setShowExtraModal(false);
          setExtraActivity("");
          setExtraPredicate("A");
          setExtraDescription("");
        },
        onFinish: () => setIsSavingExtra(false),
      },
    );
  };

  const handleDeleteExtra = (id: number, name: string) => {
    if (confirm(`Hapus penilaian ekstrakurikuler "${name}"?`)) {
      router.delete(`/academic/report-cards/extracurricular/${id}`, {
        preserveScroll: true,
      });
    }
  };

  const handleAddP5 = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSavingP5(true);
    router.post(
      `/academic/report-cards/${report.id}/p5`,
      {
        project_title: p5Title,
        dimension: p5Dimension,
        predicate: p5Predicate,
        description: p5Description,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          setShowP5Modal(false);
          setP5Title("");
          setP5Dimension(
            "Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia",
          );
          setP5Predicate("BSH");
          setP5Description("");
        },
        onFinish: () => setIsSavingP5(false),
      },
    );
  };

  const handleDeleteP5 = (id: number, title: string) => {
    if (confirm(`Hapus penilaian P5-PRA "${title}"?`)) {
      router.delete(`/academic/report-cards/p5/${id}`, {
        preserveScroll: true,
      });
    }
  };

  const handleSaveNote = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSavingNote(true);

    router.patch(
      `/academic/report-cards/${report.id}`,
      {
        homeroom_note: homeroomNote,
        expected_version: report.version,
      },
      {
        preserveScroll: true,
        onFinish: () => setIsSavingNote(false),
      },
    );
  };

  const handleFinalize = () => {
    setIsFinalizing(true);

    router.post(
      `/academic/report-cards/${report.id}/finalize`,
      {
        expected_version: report.version,
        expected_source_version: report.fresh_source_version,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsFinalizing(false);
          setShowFinalizeModal(false);
        },
      },
    );
  };

  const handlePublish = () => {
    setIsPublishing(true);

    router.post(
      `/academic/report-cards/${report.id}/publish`,
      {
        expected_version: report.version,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsPublishing(false);
          setShowPublishModal(false);
        },
      },
    );
  };

  const handleCreateRevision = (e: React.FormEvent) => {
    e.preventDefault();
    setIsCreatingRevision(true);

    router.post(
      `/academic/report-cards/${report.id}/revisions`,
      {
        reason: revisionReason,
        expected_version: report.version,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsCreatingRevision(false);
          setShowRevisionModal(false);
        },
      },
    );
  };

  const handleRevoke = (e: React.FormEvent) => {
    e.preventDefault();
    if (!revokeReason.trim() || revokeReason.length < 5) return;
    setIsRevoking(true);

    router.post(
      `/academic/report-cards/${report.id}/revoke`,
      {
        reason: revokeReason,
        expected_version: report.version,
      },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsRevoking(false);
          setShowRevokeModal(false);
        },
      },
    );
  };

  return (
    <AppLayout
      title={`Rapor: ${student.name} (${classroom.name})`}
      contextHeader={{
        title: student.name,
        badge: `${report.status.toUpperCase()} (Rev #${report.revision})`,
        description: `NIS: ${student.nis} • Kelas ${classroom.name} • ${classroom.term.academic_year || ""} Semester ${classroom.term.semester}`,
      }}
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Link
            href={`/academic/classrooms/${classroom.id}/report-preview`}
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Preview Rombel
          </Link>

          <div className="flex items-center gap-2">
            {needsRevision && (
              <span className="rounded-full bg-amber-100 border border-amber-300 px-2 py-0.5 text-[10px] font-bold text-amber-800">
                Dalam Revisi
              </span>
            )}
            {isSuperseded && (
              <span className="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                Tersupersede
              </span>
            )}
            <span className="rounded bg-muted px-2 py-0.5 text-[11px] font-mono text-muted-foreground">
              v{report.version}
            </span>
          </div>
        </div>
        {/* Revision Lineage Bar */}
        {lineage && lineage.length > 1 && (
          <div className="rounded-xl border border-stone-200 bg-white p-3.5 shadow-sm">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
              <div className="flex items-center gap-2 text-xs font-semibold text-stone-500">
                <History className="h-4 w-4 text-stone-600 shrink-0" />
                <span>Riwayat Silsilah Revisi Rapor:</span>
              </div>
              <div className="flex items-center gap-2 overflow-x-auto text-xs pb-1 sm:pb-0">
                {lineage.map((rev) => {
                  const isCurrentRev = rev.id === report.id;
                  let badgeStyle =
                    "bg-stone-100 text-stone-600 border-stone-200";
                  if (rev.status === "published")
                    badgeStyle =
                      "bg-emerald-50 text-emerald-800 border-emerald-200 font-bold";
                  if (rev.status === "revoked")
                    badgeStyle =
                      "bg-rose-50 text-rose-700 border-rose-200 line-through";
                  if (rev.status === "draft")
                    badgeStyle = "bg-amber-50 text-amber-800 border-amber-200";

                  return isCurrentRev ? (
                    <span
                      key={rev.id}
                      className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-1 text-xs shadow-sm ring-2 ring-stone-800 ${badgeStyle}`}
                    >
                      <span>Revisi {rev.revision}</span>
                      <span className="text-[10px] uppercase font-bold">
                        ({rev.status})
                      </span>
                      <span className="rounded bg-stone-900 px-1.5 py-0.5 text-[9px] text-white font-bold">
                        Aktif
                      </span>
                    </span>
                  ) : (
                    <Link
                      key={rev.id}
                      href={`/academic/report-cards/${rev.id}`}
                      className={`inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs hover:opacity-80 transition ${badgeStyle}`}
                    >
                      <span>Revisi {rev.revision}</span>
                      <span className="text-[10px] uppercase">
                        ({rev.status})
                      </span>
                    </Link>
                  );
                })}
              </div>
            </div>
          </div>
        )}

        {/* Error Banners */}
        {pageErrors.version && (
          <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
            <span>{pageErrors.version}</span>
          </div>
        )}
        {pageErrors.source_changed && (
          <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
            <span>{pageErrors.source_changed}</span>
          </div>
        )}
        {pageErrors._form && (
          <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-semibold text-rose-800 flex items-center gap-2">
            <AlertTriangle className="h-4 w-4 shrink-0 text-rose-600" />
            <span>{pageErrors._form}</span>
          </div>
        )}

        {/* Source Stale Warning */}
        {isDraft && report.is_source_stale && (
          <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm flex items-start gap-3">
            <AlertTriangle className="h-5 w-5 shrink-0 text-amber-600 mt-0.5" />
            <div className="text-xs leading-relaxed">
              <p className="font-bold text-amber-950">
                Pemberitahuan: Sumber Nilai/Presensi Telah Berubah (Source
                Changed)
              </p>
              <p className="mt-0.5 text-amber-800">
                Terdapat pembaruan data nilai atau presensi sejak draf rapor ini
                dibuat. Silakan perbarui draf sebelum melakukan finalisasi agar
                nilai snapshot sesuai dengan data terbaru.
              </p>
            </div>
          </div>
        )}

        {/* Finalized Notice */}
        {isFinalized && (
          <div className="rounded-xl border border-blue-200 bg-blue-50/70 p-4 text-blue-900 shadow-sm flex items-start gap-3">
            <ShieldCheck className="h-5 w-5 shrink-0 text-blue-700 mt-0.5" />
            <div className="text-xs leading-relaxed">
              <p className="font-bold text-blue-950">
                Rapor Telah Difinalisasi (Snapshot Frozen)
              </p>
              <p className="mt-0.5 text-blue-800">
                Dokumen rapor telah dibekukan pada {report.finalized_at} oleh{" "}
                {report.finalized_by?.name || "Petugas"}. Seluruh nilai, catatan
                wali kelas, dan riwayat presensi bersifat permanen (immutable).
                Rapor siap dipublikasikan ke portal wali murid.
              </p>
            </div>
          </div>
        )}

        {/* Published Notice */}
        {isPublished && (
          <div className="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-emerald-950 shadow-sm flex items-start gap-3">
            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-700 mt-0.5" />
            <div className="text-xs leading-relaxed">
              <p className="font-bold text-emerald-950">
                Rapor Resmi Telah Dipublikasikan (Aktif di Portal Wali Murid)
              </p>
              <p className="mt-0.5 text-emerald-800">
                Dokumen rapor ini telah resmi dipublikasikan pada{" "}
                {report.published_at} oleh{" "}
                {report.published_by?.name || "Petugas"}. Wali murid dapat
                langsung mengakses rincian nilai dan mengunduh berkas PDF resmi
                melalui portal orang tua.
              </p>
            </div>
          </div>
        )}

        {/* Needs Revision Notice */}
        {needsRevision && (
          <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 shadow-sm flex items-start gap-3">
            <RotateCcw className="h-5 w-5 shrink-0 text-amber-700 mt-0.5" />
            <div className="text-xs leading-relaxed">
              <p className="font-bold text-amber-950">
                Rapor Sedang Dalam Proses Revisi
              </p>
              <p className="mt-0.5 text-amber-800">
                Telah dibuat draf revisi baru untuk dokumen ini. Begitu draf
                revisi baru selesai difinalisasi dan dipublikasikan, versi ini
                akan otomatis disupersede.
              </p>
            </div>
          </div>
        )}

        {/* Revoked Notice */}
        {isRevoked && (
          <div className="rounded-xl border border-rose-300 bg-rose-50 p-4 text-rose-950 shadow-sm flex items-start gap-3">
            <AlertOctagon className="h-5 w-5 shrink-0 text-rose-600 mt-0.5" />
            <div className="text-xs leading-relaxed space-y-1">
              <p className="font-bold text-rose-950 text-sm">
                Dokumen Rapor Telah Ditarik (Revoked)
              </p>
              <p className="text-rose-800">
                Rapor ini resmi ditarik pada {report.revoked_at || "sebelumnya"}{" "}
                oleh {report.revoked_by?.name || "Petugas"}. Dokumen ini telah
                dicabut dan sama sekali tidak dapat diakses lagi oleh wali
                murid.
              </p>
              {report.lifecycle_reason && (
                <div className="rounded-lg bg-white/80 border border-rose-200 p-2.5 text-rose-900 mt-1">
                  <span className="font-bold">Alasan Penarikan:</span> "
                  {report.lifecycle_reason}"
                </div>
              )}
            </div>
          </div>
        )}

        {/* Info Grid */}
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {/* Student & Class Info */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-3">
            <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
              Identitas Siswa
            </span>
            <div className="flex items-center gap-3">
              <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800 font-bold text-base">
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
                <p className="text-xs text-stone-500">
                  Rombel: Kelas {classroom.name}
                </p>
              </div>
            </div>
          </div>

          {/* Academic Period & Homeroom */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-3">
            <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
              Periode & Wali Kelas
            </span>
            <div className="space-y-1.5 text-xs text-stone-700">
              <div className="flex justify-between">
                <span className="text-stone-500">Tahun Ajaran:</span>
                <span className="font-semibold text-stone-900">
                  {classroom.term.academic_year || "Aktif"}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Semester:</span>
                <span className="font-semibold text-stone-900">
                  Semester {classroom.term.semester} ({classroom.term.name})
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Wali Kelas:</span>
                <span className="font-semibold text-stone-900">
                  {classroom.homeroom?.name || "Belum Ditugaskan"}
                </span>
              </div>
            </div>
          </div>

          {/* Attendance Summary */}
          <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-3">
            <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
              Rekapitulasi Kehadiran
            </span>
            {report.attendance_summary ? (
              <div className="grid grid-cols-4 gap-2 text-center pt-1">
                <div className="rounded-lg bg-emerald-50 p-2 text-xs">
                  <p className="text-[10px] text-emerald-700 font-semibold">
                    Hadir (H)
                  </p>
                  <p className="text-base font-bold text-emerald-900">
                    {report.attendance_summary.present}
                  </p>
                </div>
                <div className="rounded-lg bg-blue-50 p-2 text-xs">
                  <p className="text-[10px] text-blue-700 font-semibold">
                    Izin (I)
                  </p>
                  <p className="text-base font-bold text-blue-900">
                    {report.attendance_summary.excused}
                  </p>
                </div>
                <div className="rounded-lg bg-amber-50 p-2 text-xs">
                  <p className="text-[10px] text-amber-700 font-semibold">
                    Sakit (S)
                  </p>
                  <p className="text-base font-bold text-amber-900">
                    {report.attendance_summary.sick}
                  </p>
                </div>
                <div className="rounded-lg bg-rose-50 p-2 text-xs">
                  <p className="text-[10px] text-rose-700 font-semibold">
                    Alpa (A)
                  </p>
                  <p className="text-base font-bold text-rose-900">
                    {report.attendance_summary.absent}
                  </p>
                </div>
              </div>
            ) : (
              <p className="text-xs text-stone-400">
                Belum ada rekap presensi.
              </p>
            )}
          </div>
        </div>

        {/* Homeroom Note Form (PATCH /academic/report-cards/{report}) */}
        <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between border-b border-stone-100 pb-3">
            <div className="flex items-center gap-2">
              <FileText className="h-4 w-4 text-emerald-700" />
              <h3 className="text-sm font-bold text-stone-900">
                Catatan Wali Kelas
              </h3>
            </div>
            {canUpdateNote ? (
              <span className="rounded bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                Dapat Diubah (Mode Draf)
              </span>
            ) : (
              <span className="inline-flex items-center gap-1 rounded bg-stone-100 px-2 py-0.5 text-[11px] font-medium text-stone-600">
                <Lock className="h-3 w-3" />{" "}
                {isDraft ? "Hanya Baca" : "Dibekukan"}
              </span>
            )}
          </div>

          <form onSubmit={handleSaveNote} className="space-y-3">
            <textarea
              value={homeroomNote}
              onChange={(e) => setHomeroomNote(e.target.value)}
              disabled={!canUpdateNote || isSavingNote}
              aria-label="Catatan wali kelas"
              placeholder="Tuliskan catatan perkembangan belajar, motivasi, atau pesan wali kelas untuk siswa ini..."
              rows={3}
              className={`w-full rounded-xl border p-3 text-xs leading-relaxed focus:outline-none focus:ring-1 ${
                canUpdateNote
                  ? "border-stone-200 bg-white text-stone-900 focus:border-emerald-600 focus:ring-emerald-600"
                  : "border-stone-200 bg-stone-50 text-stone-600 cursor-not-allowed"
              }`}
            />
            {canUpdateNote && (
              <div className="flex items-center justify-between">
                <span className="text-[11px] text-stone-400">
                  Maksimal 2.000 karakter • Catatan akan dibekukan permanen saat
                  difinalisasi.
                </span>
                <button
                  type="submit"
                  disabled={isSavingNote}
                  className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:opacity-50 transition"
                >
                  <Save className="h-3.5 w-3.5" />
                  {isSavingNote ? "Menyimpan..." : "Simpan Catatan"}
                </button>
              </div>
            )}
          </form>
        </div>

        {/* Subjects & Components Breakdown */}
        <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
          <div className="border-b border-stone-200 bg-stone-50/75 px-5 py-3.5 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <BookOpen className="h-4 w-4 text-emerald-800" />
              <h3 className="text-xs font-bold uppercase tracking-wider text-stone-900">
                Rincian Nilai Per Mata Pelajaran (Snapshot)
              </h3>
            </div>
            <span className="text-xs font-semibold text-stone-600">
              {report.items.length} Mata Pelajaran
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-stone-200 bg-stone-50/50 text-[10px] font-semibold uppercase tracking-wider text-stone-400">
                <tr>
                  <th className="py-3 px-4 sm:px-6">Mata Pelajaran</th>
                  <th className="py-3 px-3">Guru Pengampu</th>
                  <th className="py-3 px-3">KKM</th>
                  <th className="py-3 px-3">Nilai Akhir</th>
                  <th className="py-3 px-3">Status KKM</th>
                  <th className="py-3 px-4 sm:px-6">Rincian Komponen</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {report.items.map((item) => {
                  const teacher =
                    item.teaching_history_snapshot?.[0]?.teacher_name || "-";
                  return (
                    <tr key={item.id} className="hover:bg-stone-50/50">
                      <td className="py-3.5 px-4 sm:px-6 font-semibold text-stone-900">
                        {item.subject_snapshot.name}
                        <span className="ml-1 text-[11px] font-normal text-stone-400">
                          ({item.subject_snapshot.code})
                        </span>
                      </td>
                      <td className="py-3.5 px-3 text-stone-600 text-xs">
                        {teacher}
                      </td>
                      <td className="py-3.5 px-3 font-mono font-medium text-stone-700">
                        {item.kkm_snapshot}
                      </td>
                      <td className="py-3.5 px-3 font-mono font-bold text-sm">
                        {item.final_score !== null ? (
                          <span className="text-emerald-800">
                            {item.final_score}
                          </span>
                        ) : (
                          <span className="text-stone-400 font-normal italic text-xs">
                            Belum tersedia
                          </span>
                        )}
                      </td>
                      <td className="py-3.5 px-3">
                        <span
                          className={`inline-block rounded px-2 py-0.5 text-[10px] font-semibold ${
                            item.kkm_status === "Mencapai KKM"
                              ? "bg-emerald-100 text-emerald-800"
                              : item.kkm_status === "Belum mencapai KKM"
                                ? "bg-rose-100 text-rose-800"
                                : "bg-stone-100 text-stone-500"
                          }`}
                        >
                          {item.kkm_status}
                        </span>
                      </td>
                      <td className="py-3.5 px-4 sm:px-6">
                        <div className="flex flex-wrap gap-1.5">
                          {Object.values(
                            item.component_breakdown_snapshot || {},
                          ).map((c) => (
                            <span
                              key={c.code}
                              className="inline-flex items-center rounded bg-stone-100 px-2 py-0.5 text-[10px] text-stone-700"
                              title={`${c.name} (${Number(c.weight_percentage)}%): Rerata ${c.score_average || "0.00"}, Kontribusi ${c.contribution_rounded || "0.00"}`}
                            >
                              <span className="font-semibold">{c.name}</span>:{" "}
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

        {/* Extracurricular Assessments Section */}
        <div className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 bg-stone-50/60 px-6 py-4">
            <div>
              <div className="flex items-center gap-2">
                <Award className="h-4 w-4 text-emerald-700" />
                <h3 className="text-sm font-bold text-stone-900">
                  Kegiatan Ekstrakurikuler
                </h3>
              </div>
              <p className="text-xs text-stone-500 mt-0.5">
                Penilaian kualitatif kegiatan kepanduan, keagamaan, olahraga,
                dan seni.
              </p>
            </div>

            {canEditExtras && (
              <button
                type="button"
                onClick={() => setShowExtraModal(true)}
                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 transition"
              >
                <Plus className="h-3.5 w-3.5" />
                Tambah Ekstrakurikuler
              </button>
            )}
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-stone-700">
              <thead className="border-b border-stone-200 bg-stone-50/70 text-[11px] font-semibold uppercase tracking-wider text-stone-500">
                <tr>
                  <th className="py-3 px-4 sm:px-6 w-12 text-center">No</th>
                  <th className="py-3 px-4 w-48">Kegiatan</th>
                  <th className="py-3 px-3 w-32 text-center">Predikat</th>
                  <th className="py-3 px-4">Keterangan / Capaian</th>
                  {canEditExtras && (
                    <th className="py-3 px-4 w-20 text-right">Aksi</th>
                  )}
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {!report.extracurriculars ||
                report.extracurriculars.length === 0 ? (
                  <tr>
                    <td
                      colSpan={canEditExtras ? 5 : 4}
                      className="py-8 text-center text-stone-400 italic"
                    >
                      Belum ada catatan kegiatan ekstrakurikuler yang
                      ditambahkan.
                    </td>
                  </tr>
                ) : (
                  report.extracurriculars.map((extra, idx) => (
                    <tr
                      key={extra.id}
                      className="hover:bg-stone-50/50 transition"
                    >
                      <td className="py-3 px-4 sm:px-6 text-center text-stone-400 font-mono">
                        {idx + 1}
                      </td>
                      <td className="py-3 px-4 font-bold text-stone-900">
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
                      <td className="py-3 px-4 text-stone-700 leading-relaxed">
                        {extra.description}
                      </td>
                      {canEditExtras && (
                        <td className="py-3 px-4 text-right">
                          <button
                            type="button"
                            onClick={() =>
                              handleDeleteExtra(extra.id, extra.activity_name)
                            }
                            className="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 p-1 transition"
                            title="Hapus penilaian"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </button>
                        </td>
                      )}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* P5-PRA Kokurikuler Section */}
        <div className="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-200 bg-stone-50/60 px-6 py-4">
            <div>
              <div className="flex items-center gap-2">
                <Sparkles className="h-4 w-4 text-amber-600" />
                <h3 className="text-sm font-bold text-stone-900">
                  Projek Penguatan Profil Pelajar Pancasila & Rahmatan Lil
                  Alamin (P5-PRA)
                </h3>
              </div>
              <p className="text-xs text-stone-500 mt-0.5">
                Asesmen capaian kokurikuler tematik berbasis rubrik naratif
                Kurikulum Merdeka.
              </p>
            </div>

            {canEditExtras && (
              <button
                type="button"
                onClick={() => setShowP5Modal(true)}
                className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-teal-800 transition"
              >
                <Plus className="h-3.5 w-3.5" />
                Tambah Capaian P5-PRA
              </button>
            )}
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs text-stone-700">
              <thead className="border-b border-stone-200 bg-stone-50/70 text-[11px] font-semibold uppercase tracking-wider text-stone-500">
                <tr>
                  <th className="py-3 px-4 sm:px-6 w-12 text-center">No</th>
                  <th className="py-3 px-4 w-52">Projek P5-PRA</th>
                  <th className="py-3 px-4 w-48">Dimensi Profil</th>
                  <th className="py-3 px-3 w-36 text-center">Capaian</th>
                  <th className="py-3 px-4">Catatan Perkembangan</th>
                  {canEditExtras && (
                    <th className="py-3 px-4 w-20 text-right">Aksi</th>
                  )}
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100">
                {!report.p5_assessments ||
                report.p5_assessments.length === 0 ? (
                  <tr>
                    <td
                      colSpan={canEditExtras ? 6 : 5}
                      className="py-8 text-center text-stone-400 italic"
                    >
                      Belum ada catatan asesmen P5-PRA yang ditambahkan.
                    </td>
                  </tr>
                ) : (
                  report.p5_assessments.map((p5, idx) => (
                    <tr key={p5.id} className="hover:bg-stone-50/50 transition">
                      <td className="py-3 px-4 sm:px-6 text-center text-stone-400 font-mono">
                        {idx + 1}
                      </td>
                      <td className="py-3 px-4 font-bold text-stone-900">
                        {p5.project_title}
                      </td>
                      <td className="py-3 px-4 text-stone-600 font-medium">
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
                      <td className="py-3 px-4 text-stone-700 leading-relaxed">
                        {p5.description}
                      </td>
                      {canEditExtras && (
                        <td className="py-3 px-4 text-right">
                          <button
                            type="button"
                            onClick={() =>
                              handleDeleteP5(p5.id, p5.project_title)
                            }
                            className="inline-flex items-center gap-1 text-xs text-rose-600 hover:text-rose-800 p-1 transition"
                            title="Hapus penilaian"
                          >
                            <Trash2 className="h-3.5 w-3.5" />
                          </button>
                        </td>
                      )}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* PDF Document Management for Finalized / Published Reports */}
        {(canDownloadPdf || canPrepareFile) && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div>
              <div className="flex items-center gap-2">
                <FileText className="h-4 w-4 text-emerald-800" />
                <h4 className="text-sm font-bold text-stone-900">
                  Dokumen Cetak Rapor Resmi
                </h4>
              </div>
              <p className="text-xs text-stone-500 mt-0.5">
                Format resmi madrasah (Q7.1) dengan kop, tabel capaian,
                presensi, catatan wali kelas dan tanda tangan.
              </p>
            </div>

            <div className="flex items-center gap-2">
              {canPrepareFile && (
                <button
                  type="button"
                  onClick={() =>
                    router.post(
                      `/academic/report-cards/${report.id}/prepare-file`,
                      { expected_version: report.version },
                      { preserveScroll: true },
                    )
                  }
                  className="inline-flex items-center gap-1.5 rounded-lg border border-stone-200 bg-white px-3.5 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 transition"
                >
                  <Sparkles className="h-3.5 w-3.5 text-amber-600" />
                  Render Ulang PDF
                </button>
              )}

              {canDownloadPdf && (
                <a
                  href={`/academic/report-cards/${report.id}/pdf`}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-800 transition"
                >
                  <Download className="h-4 w-4" />
                  Unduh Rapor PDF
                </a>
              )}
            </div>
          </div>
        )}

        {/* Publication Footer for Finalized Reports */}
        {isFinalized && canManageReport && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div>
              <h4 className="text-sm font-bold text-stone-900">
                Publikasi Rapor Resmi ke Portal Wali Murid
              </h4>
              <p className="text-xs text-stone-500 mt-0.5">
                Membuka akses dokumen rapor semester dan unduhan berkas PDF
                resmi untuk wali murid melalui portal orang tua.
              </p>
            </div>

            <button
              type="button"
              onClick={() => setShowPublishModal(true)}
              disabled={isPublishing}
              className="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-teal-800 disabled:opacity-50 transition"
            >
              <Globe className="h-4 w-4" />
              Publikasikan Rapor ke Portal Wali
            </button>
          </div>
        )}

        {/* Revision & Revocation Lifecycle Management (D-28) */}
        {!isDraft && canManageReport && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div>
              <div className="flex items-center gap-2">
                <GitBranch className="h-4 w-4 text-stone-700" />
                <h4 className="text-sm font-bold text-stone-900">
                  Manajemen Silsilah & Revisi Rapor (D-28)
                </h4>
              </div>
              <p className="text-xs text-stone-500 mt-0.5">
                {isRevoked
                  ? "Rapor ini telah ditarik. Anda dapat membuat draf revisi pembetulan baru dari riwayat ini."
                  : "Buat draf revisi baru (revision + 1) jika ada pembetulan nilai, atau tarik rapor jika ditemukan kekeliruan fatal."}
              </p>
            </div>

            <div className="flex items-center gap-2 flex-wrap">
              <button
                type="button"
                onClick={() => setShowRevisionModal(true)}
                disabled={isCreatingRevision}
                className="inline-flex items-center gap-1.5 rounded-xl border border-stone-300 bg-white px-3.5 py-2 text-xs font-semibold text-stone-700 hover:bg-stone-50 transition"
              >
                <RotateCcw className="h-3.5 w-3.5 text-stone-600" />
                Buat Draf Revisi Baru
              </button>

              {!isRevoked && (
                <button
                  type="button"
                  onClick={() => setShowRevokeModal(true)}
                  disabled={isRevoking}
                  className="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-bold text-rose-700 hover:bg-rose-100 transition"
                >
                  <Ban className="h-3.5 w-3.5 text-rose-600" />
                  Tarik Rapor (Revoke)
                </button>
              )}
            </div>
          </div>
        )}

        {/* Finalization Footer for Draft Reports */}
        {isDraft && canManageReport && (
          <div className="flex flex-col sm:flex-row items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm">
            <div>
              <h4 className="text-sm font-bold text-stone-900">
                Finalisasi Dokumen Rapor Semester
              </h4>
              <p className="text-xs text-stone-500 mt-0.5">
                Membekukan seluruh nilai dan catatan menjadi immutable snapshot
                permanen.
              </p>
            </div>

            <button
              type="button"
              onClick={() => setShowFinalizeModal(true)}
              disabled={isFinalizing}
              className="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-emerald-800 disabled:opacity-50 transition"
            >
              <Lock className="h-4 w-4" />
              Finalisasi Snapshot Rapor
            </button>
          </div>
        )}
      </div>

      {/* Confirmation Modal for Publication */}
      {canManageReport && showPublishModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <Globe className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Publikasikan Rapor Siswa
                </h3>
                <p className="text-xs text-stone-500">
                  Akses portal orang tua / wali murid
                </p>
              </div>
            </div>

            <div className="rounded-xl border border-stone-200 bg-stone-50 p-3.5 text-xs text-stone-700 space-y-2">
              <p>
                Anda akan mempublikasikan dokumen rapor resmi untuk{" "}
                <strong>{student.name}</strong>.
              </p>
              <ul className="list-disc pl-4 space-y-1 text-stone-600 text-[11px]">
                <li>
                  Rapor dan berkas PDF resmi dapat langsung diakses dan diunduh
                  oleh wali murid.
                </li>
                <li>
                  Jika terdapat versi publikasi terdahulu, versi lama akan
                  otomatis disupersede (*superseded*).
                </li>
                <li>
                  Integritas data nilai dan presensi akan divalidasi ulang
                  sebelum dipublikasikan.
                </li>
              </ul>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowPublishModal(false)}
                disabled={isPublishing}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handlePublish}
                disabled={isPublishing}
                className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-4 py-2 text-xs font-bold text-white hover:bg-teal-800 disabled:opacity-50 transition"
              >
                <CheckCircle2 className="h-4 w-4" />
                {isPublishing ? "Mempublikasikan..." : "Ya, Publikasikan Rapor"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Confirmation Modal for Finalization */}
      {canManageReport && showFinalizeModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                <Lock className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Konfirmasi Finalisasi Rapor
                </h3>
                <p className="text-xs text-stone-500">
                  Tindakan ini tidak dapat dibatalkan
                </p>
              </div>
            </div>

            <div className="rounded-xl border border-stone-200 bg-stone-50 p-3.5 text-xs text-stone-700 space-y-2">
              <p>
                Anda akan memfinalisasi rapor untuk{" "}
                <strong>{student.name}</strong>.
              </p>
              <ul className="list-disc pl-4 space-y-1 text-stone-600 text-[11px]">
                <li>
                  Seluruh nilai per mapel dan catatan wali kelas akan dibekukan
                  (*immutable*).
                </li>
                <li>
                  Identitas siswa, rombel, wali kelas, dan kepala sekolah akan
                  diarsip permanen.
                </li>
                <li>
                  Rapor belum akan tampil di portal wali murid sebelum
                  dipublikasikan.
                </li>
              </ul>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowFinalizeModal(false)}
                disabled={isFinalizing}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="button"
                onClick={handleFinalize}
                disabled={isFinalizing}
                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50 transition"
              >
                <CheckCircle2 className="h-4 w-4" />
                {isFinalizing ? "Memproses..." : "Ya, Finalisasi Sekarang"}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal for Creating Revision */}
      {canManageReport && showRevisionModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <form
            onSubmit={handleCreateRevision}
            className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4"
          >
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <RotateCcw className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Buat Draf Revisi Rapor
                </h3>
                <p className="text-xs text-stone-500">
                  Nomor revisi baru: #{report.revision + 1}
                </p>
              </div>
            </div>

            <div className="rounded-xl border border-stone-200 bg-stone-50 p-3.5 text-xs text-stone-700 space-y-2">
              <p>
                Tindakan ini akan membuat salinan draf revisi baru untuk{" "}
                <strong>{student.name}</strong> tanpa menghapus riwayat versi
                terdahulu.
              </p>
              <ul className="list-disc pl-4 space-y-1 text-stone-600 text-[11px]">
                <li>
                  Data nilai dan presensi akan dihitung ulang dari data terbaru.
                </li>
                <li>
                  Catatan wali kelas terdahulu akan disalin sebagai awalan.
                </li>
              </ul>
            </div>

            <div className="space-y-1.5">
              <label
                htmlFor="revision_reason"
                className="block text-xs font-semibold text-stone-700"
              >
                Alasan / Catatan Revisi (Opsional)
              </label>
              <textarea
                id="revision_reason"
                rows={3}
                value={revisionReason}
                onChange={(e) => setRevisionReason(e.target.value)}
                placeholder="Contoh: Perbaikan nilai susulan asesmen PAS Matematika"
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowRevisionModal(false)}
                disabled={isCreatingRevision}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={isCreatingRevision}
                className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-4 py-2 text-xs font-bold text-white hover:bg-teal-800 disabled:opacity-50 transition"
              >
                <CheckCircle2 className="h-4 w-4" />
                {isCreatingRevision ? "Memproses..." : "Buat Draf Revisi"}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Modal for Revocation */}
      {canManageReport && showRevokeModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <form
            onSubmit={handleRevoke}
            className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4"
          >
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-700">
                <Ban className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Tarik Rapor Resmi (Revoke)
                </h3>
                <p className="text-xs text-rose-600 font-semibold">
                  Tindakan berisiko tinggi
                </p>
              </div>
            </div>

            <div className="rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-900 space-y-2">
              <p>
                Rapor resmi siswa <strong>{student.name}</strong> akan segera
                dicabut dan ditarik dari peredaran.
              </p>
              <ul className="list-disc pl-4 space-y-1 text-rose-800 text-[11px]">
                <li>
                  Wali murid tidak akan dapat lagi melihat atau mengunduh rapor
                  ini.
                </li>
                <li>
                  Pencabutan ini akan dicatat permanen dalam audit log sistem.
                </li>
              </ul>
            </div>

            <div className="space-y-1.5">
              <label
                htmlFor="revoke_reason"
                className="block text-xs font-semibold text-stone-700"
              >
                Alasan Pencabutan / Penarikan{" "}
                <span className="text-rose-600">*</span>
              </label>
              <textarea
                id="revoke_reason"
                rows={3}
                required
                value={revokeReason}
                onChange={(e) => setRevokeReason(e.target.value)}
                placeholder="Jelaskan alasan kekeliruan fatal penarikan dokumen rapor (minimal 5 karakter)..."
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-rose-600 focus:ring-1 focus:ring-rose-600 outline-none"
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowRevokeModal(false)}
                disabled={isRevoking}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={isRevoking || revokeReason.trim().length < 5}
                className="inline-flex items-center gap-1.5 rounded-lg bg-rose-700 px-4 py-2 text-xs font-bold text-white hover:bg-rose-800 disabled:opacity-50 transition"
              >
                <Ban className="h-4 w-4" />
                {isRevoking ? "Menarik..." : "Ya, Tarik Rapor Sekarang"}
              </button>
            </div>
          </form>
        </div>
      )}
      {/* Modal for Adding Extracurricular */}
      {canEditExtras && showExtraModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <form
            onSubmit={handleAddExtra}
            className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4"
          >
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                <Award className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Tambah Penilaian Ekstrakurikuler
                </h3>
                <p className="text-xs text-stone-500">
                  Catat capaian kegiatan non-akademik siswa
                </p>
              </div>
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Nama Kegiatan Ekstrakurikuler{" "}
                <span className="text-rose-600">*</span>
              </label>
              <input
                type="text"
                required
                value={extraActivity}
                onChange={(e) => setExtraActivity(e.target.value)}
                placeholder="Contoh: Pramuka Siaga, Tahfidz Al-Qur'an, Futsal"
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none"
              />
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Predikat Capaian <span className="text-rose-600">*</span>
              </label>
              <select
                value={extraPredicate}
                onChange={(e) => setExtraPredicate(e.target.value as any)}
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none"
              >
                <option value="A">A - Sangat Baik</option>
                <option value="B">B - Baik</option>
                <option value="C">C - Cukup</option>
                <option value="D">D - Kurang</option>
              </select>
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Keterangan / Deskripsi Capaian{" "}
                <span className="text-rose-600">*</span>
              </label>
              <textarea
                rows={3}
                required
                value={extraDescription}
                onChange={(e) => setExtraDescription(e.target.value)}
                placeholder="Tuliskan catatan kualitatif nyata tentang keaktifan dan penguasaan keterampilan siswa..."
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-emerald-600 focus:ring-1 focus:ring-emerald-600 outline-none"
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowExtraModal(false)}
                disabled={isSavingExtra}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={
                  isSavingExtra ||
                  !extraActivity.trim() ||
                  !extraDescription.trim()
                }
                className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800 disabled:opacity-50 transition"
              >
                <Plus className="h-4 w-4" />
                {isSavingExtra ? "Menyimpan..." : "Simpan Ekstrakurikuler"}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Modal for Adding P5-PRA */}
      {canEditExtras && showP5Modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-950/40 p-4 backdrop-blur-sm">
          <form
            onSubmit={handleAddP5}
            className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl space-y-4"
          >
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                <Sparkles className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Tambah Asesmen Kokurikuler P5-PRA
                </h3>
                <p className="text-xs text-stone-500">
                  Rubrik capaian Projek Penguatan Profil Pelajar Pancasila &
                  Rahmatan Lil Alamin
                </p>
              </div>
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Judul Projek P5-PRA <span className="text-rose-600">*</span>
              </label>
              <input
                type="text"
                required
                value={p5Title}
                onChange={(e) => setP5Title(e.target.value)}
                placeholder="Contoh: Gaya Hidup Berkelanjutan: Pengolahan Sampah Madrasah"
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
              />
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Dimensi Profil <span className="text-rose-600">*</span>
              </label>
              <select
                value={p5Dimension}
                onChange={(e) => setP5Dimension(e.target.value)}
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
              >
                <option value="Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia">
                  Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia
                </option>
                <option value="Gotong Royong">Gotong Royong</option>
                <option value="Mandiri">Mandiri</option>
                <option value="Kreatif">Kreatif</option>
                <option value="Bernalar Kritis">Bernalar Kritis</option>
                <option value="Berkebinekaan Global">
                  Berkebinekaan Global
                </option>
                <option value="Berkeadaban (Ta'addub)">
                  Berkeadaban (Ta'addub)
                </option>
                <option value="Keteladanan (Qudwah)">
                  Keteladanan (Qudwah)
                </option>
              </select>
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Kategori Capaian Rubrik <span className="text-rose-600">*</span>
              </label>
              <select
                value={p5Predicate}
                onChange={(e) => setP5Predicate(e.target.value as any)}
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
              >
                <option value="BB">BB - Belum Berkembang</option>
                <option value="MB">MB - Mulai Berkembang</option>
                <option value="BSH">BSH - Berkembang Sesuai Harapan</option>
                <option value="SAB">SAB - Sangat Berkembang</option>
              </select>
            </div>

            <div className="space-y-1.5">
              <label className="block text-xs font-semibold text-stone-700">
                Catatan Naratif Perkembangan{" "}
                <span className="text-rose-600">*</span>
              </label>
              <textarea
                rows={3}
                required
                value={p5Description}
                onChange={(e) => setP5Description(e.target.value)}
                placeholder="Deskripsikan peran konkret, kepedulian, dan inisiatif nyata siswa dalam projek ini..."
                className="w-full rounded-xl border border-stone-300 p-2.5 text-xs text-stone-800 placeholder-stone-400 focus:border-teal-600 focus:ring-1 focus:ring-teal-600 outline-none"
              />
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setShowP5Modal(false)}
                disabled={isSavingP5}
                className="rounded-lg border border-stone-200 px-4 py-2 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="submit"
                disabled={
                  isSavingP5 || !p5Title.trim() || !p5Description.trim()
                }
                className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-4 py-2 text-xs font-bold text-white hover:bg-teal-800 disabled:opacity-50 transition"
              >
                <Sparkles className="h-4 w-4" />
                {isSavingP5 ? "Menyimpan..." : "Simpan Capaian P5-PRA"}
              </button>
            </div>
          </form>
        </div>
      )}
    </AppLayout>
  );
}
