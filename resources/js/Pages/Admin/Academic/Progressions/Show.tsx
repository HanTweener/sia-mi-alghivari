import React, { useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  CheckCircle2,
  AlertTriangle,
  XCircle,
  FileText,
  User,
  BookOpen,
  Calendar,
  School,
  ShieldCheck,
  ShieldAlert,
  GraduationCap,
  TrendingUp,
  RotateCcw,
  Clock,
  Send,
  Check,
  X,
  Play,
  Trash2,
  Edit,
  Download,
  AlertOctagon,
  ArrowRight,
  Info,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";

interface Student {
  id: number;
  nis: string;
  name: string;
  gender: string;
  status: string;
}

interface AcademicYear {
  id: number;
  name: string;
}

interface Classroom {
  id: number;
  grade_level: number;
  section: string;
  term_id: number;
  name?: string;
  term?: {
    id: number;
    name: string;
    academic_year?: {
      name: string;
    };
  };
}

interface SubjectItem {
  id: number;
  class_subject_id: number;
  subject_snapshot?: {
    code: string;
    name: string;
  };
  class_subject?: {
    subject?: {
      code: string;
      name: string;
    };
  };
  kkm_snapshot?: string;
  final_score: string | null;
  kkm_status: string;
}

interface ReportCard {
  id: number;
  status: string;
  availability: string;
  revision: number;
  version: number;
  homeroom_note: string | null;
  finalized_at: string | null;
  published_at: string | null;
  items?: SubjectItem[];
  attendance_summary?: {
    total_sessions: number;
    present: number;
    excused: number;
    sick: number;
    absent: number;
    rate: string | null;
  } | null;
  enrollment?: {
    id: number;
    classroom?: {
      id: number;
      grade_level: number;
      section: string;
      name?: string;
    };
  };
}

interface ProgressionDetail {
  id: number;
  student_id: number;
  source_academic_year_id: number;
  source_report_card_id: number | null;
  type: "promotion" | "graduation" | "retention";
  target_academic_year_id: number | null;
  target_classroom_id: number | null;
  status:
    | "draft"
    | "submitted"
    | "approved"
    | "rejected"
    | "applied"
    | "needs_review";
  version: number;
  reason: string | null;
  rejection_reason: string | null;
  created_at: string;
  student: Student;
  source_academic_year: AcademicYear;
  source_report_card: ReportCard | null;
  target_academic_year: AcademicYear | null;
  target_classroom: Classroom | null;
  proposed_by?: { id: number; name: string } | null;
  proposed_at?: string | null;
  approved_by?: { id: number; name: string } | null;
  approved_at?: string | null;
  applied_by?: { id: number; name: string } | null;
  applied_at?: string | null;
}

interface Props {
  progression: ProgressionDetail;
  targetClassrooms: Classroom[];
  targetAcademicYears: AcademicYear[];
}

export default function Show({
  progression,
  targetClassrooms,
  targetAcademicYears,
}: Props) {
  const pageProps = usePage().props as any;
  const userRoles: string[] = pageProps.auth?.user?.roles || [];
  const flash = pageProps.flash || {};

  const isPrincipal = userRoles.includes("principal");
  const isMasterAdmin = userRoles.includes("master_admin");
  const isAcademicAdmin = userRoles.includes("academic_admin");
  const isAdministrativeStaff = isMasterAdmin || isAcademicAdmin;

  // Edit Modal State
  const [showEditModal, setShowEditModal] = useState(false);
  const [editData, setEditData] = useState({
    type: progression.type,
    target_academic_year_id: progression.target_academic_year_id
      ? String(progression.target_academic_year_id)
      : "",
    target_classroom_id: progression.target_classroom_id
      ? String(progression.target_classroom_id)
      : "",
    reason: progression.reason || "",
  });
  const [isSubmittingEdit, setIsSubmittingEdit] = useState(false);

  // Reject Modal State
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectionReason, setRejectionReason] = useState("");
  const [isSubmittingReject, setIsSubmittingReject] = useState(false);

  // Apply Modal State
  const [showApplyModal, setShowApplyModal] = useState(false);
  const [isSubmittingApply, setIsSubmittingApply] = useState(false);

  // Submit / Delete Processing
  const [isProcessing, setIsProcessing] = useState(false);

  const report = progression.source_report_card;
  const sourceClassroomName =
    report?.enrollment?.classroom?.name ||
    (report?.enrollment?.classroom
      ? `Kelas ${report.enrollment.classroom.grade_level}-${report.enrollment.classroom.section}`
      : "Rombel Asal");

  // Handle Edit Submit
  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmittingEdit(true);

    router.patch(
      `/academic/progressions/${progression.id}`,
      {
        expected_version: progression.version,
        type: editData.type,
        target_academic_year_id: editData.target_academic_year_id || null,
        target_classroom_id: editData.target_classroom_id || null,
        reason: editData.reason || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => setShowEditModal(false),
        onFinish: () => setIsSubmittingEdit(false),
      },
    );
  };

  // Handle Submit to Principal
  const handleSubmitToPrincipal = () => {
    if (!confirm("Ajukan usulan ini ke Kepala Madrasah untuk ditinjau?"))
      return;

    setIsProcessing(true);
    router.post(
      `/academic/progressions/${progression.id}/submit`,
      {},
      {
        preserveScroll: true,
        onFinish: () => setIsProcessing(false),
      },
    );
  };

  // Handle Principal Approve
  const handleApprove = () => {
    if (
      !confirm("Setujui usulan kenaikan/kelulusan ini sebagai Kepala Madrasah?")
    )
      return;

    setIsProcessing(true);
    router.post(
      `/academic/progressions/${progression.id}/approve`,
      {
        expected_version: progression.version,
        expected_source_report_id: progression.source_report_card_id,
      },
      {
        preserveScroll: true,
        onFinish: () => setIsProcessing(false),
      },
    );
  };

  // Handle Principal Reject
  const handleRejectSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!rejectionReason.trim()) {
      alert("Alasan penolakan wajib diisi.");
      return;
    }

    setIsSubmittingReject(true);
    router.post(
      `/academic/progressions/${progression.id}/reject`,
      {
        expected_version: progression.version,
        reason: rejectionReason.trim(),
      },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsSubmittingReject(false);
          setShowRejectModal(false);
        },
      },
    );
  };

  // Handle Admin Apply
  const handleApply = () => {
    setIsSubmittingApply(true);
    router.post(
      `/academic/progressions/${progression.id}/apply`,
      {},
      {
        preserveScroll: true,
        onFinish: () => {
          setIsSubmittingApply(false);
          setShowApplyModal(false);
        },
      },
    );
  };

  // Handle Delete Draft
  const handleDeleteDraft = () => {
    if (!confirm("Hapus draf usulan kenaikan/kelulusan ini secara permanen?"))
      return;

    setIsProcessing(true);
    router.delete(`/academic/progressions/${progression.id}`);
  };

  const getTypeBadge = (type: string) => {
    switch (type) {
      case "promotion":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
            <TrendingUp className="h-3.5 w-3.5" />
            Kenaikan Kelas (Promotion)
          </span>
        );
      case "graduation":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
            <GraduationCap className="h-3.5 w-3.5" />
            Kelulusan (Graduation)
          </span>
        );
      case "retention":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
            <RotateCcw className="h-3.5 w-3.5" />
            Tinggal Kelas (Retention)
          </span>
        );
      default:
        return <span>{type}</span>;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "draft":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-700">
            <Clock className="h-3.5 w-3.5 text-stone-500" />
            Draf Usulan
          </span>
        );
      case "submitted":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-800">
            <Send className="h-3.5 w-3.5 text-sky-600" />
            Menunggu Persetujuan Principal
          </span>
        );
      case "approved":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
            <ShieldCheck className="h-3.5 w-3.5 text-emerald-600" />
            Telah Disetujui (Siap Diterapkan)
          </span>
        );
      case "rejected":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
            <XCircle className="h-3.5 w-3.5 text-rose-600" />
            Ditolak oleh Kepala Madrasah
          </span>
        );
      case "applied":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800">
            <CheckCircle2 className="h-3.5 w-3.5 text-teal-600" />
            Keputusan Telah Diterapkan
          </span>
        );
      case "needs_review":
        return (
          <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900 border border-amber-300">
            <AlertTriangle className="h-3.5 w-3.5 text-amber-600" />
            Perlu Tinjauan Ulang
          </span>
        );
      default:
        return <span>{status}</span>;
    }
  };

  return (
    <AppLayout
      title={`Rincian Usulan Mutasi: ${progression.student.name}`}
      contextHeader={{
        title: `${progression.student.name} (NIS: ${progression.student.nis})`,
        badge: `v${progression.version}`,
        description: `Usulan Mutasi Akademik #${progression.id} • Dibuat ${progression.created_at}`,
      }}
    >
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <Link
            href="/academic/progressions"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Ruang Kerja Kenaikan & Kelulusan
          </Link>
          <div className="flex items-center gap-2">
            {getTypeBadge(progression.type)}
            {getStatusBadge(progression.status)}
          </div>
        </div>
        {/* Flash Message */}
        {flash?.success && (
          <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <CheckCircle2 className="h-5 w-5 text-emerald-600 shrink-0" />
              <span>{flash.success}</span>
            </div>
          </div>
        )}
        {flash?.error && (
          <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <AlertOctagon className="h-5 w-5 text-rose-600 shrink-0" />
              <span>{flash.error}</span>
            </div>
          </div>
        )}

        {/* Needs Review Alert Banner */}
        {progression.status === "needs_review" && (
          <div className="rounded-xl border border-amber-300 bg-amber-50 p-5 shadow-sm">
            <div className="flex items-start gap-3">
              <AlertTriangle className="h-6 w-6 text-amber-700 shrink-0 mt-0.5" />
              <div className="text-xs text-amber-900 leading-relaxed">
                <h2 className="text-sm font-bold">
                  Perhatian: Usulan Memerlukan Tinjauan Ulang (needs_review)
                </h2>
                <p className="mt-1">
                  Rapor resmi siswa ini telah mengalami revisi baru atau
                  pencabutan (revocation) sejak usulan disetujui/diajukan.
                  Penerapan mutasi akademik diblokir demi menjamin integritas
                  data (AC-09).
                </p>
                <p className="mt-2 font-medium">
                  Tindakan yang diperlukan: Staf pengusul dapat memperbarui draf
                  tujuan usulan jika diperlukan, lalu mengajukan kembali untuk
                  disetujui ulang oleh Kepala Madrasah.
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Rejection Alert Banner */}
        {progression.status === "rejected" && (
          <div className="rounded-xl border border-rose-300 bg-rose-50 p-5 shadow-sm">
            <div className="flex items-start gap-3">
              <XCircle className="h-6 w-6 text-rose-700 shrink-0 mt-0.5" />
              <div className="text-xs text-rose-900 leading-relaxed">
                <h2 className="text-sm font-bold">
                  Usulan Ditolak oleh Kepala Madrasah
                </h2>
                <div className="mt-2 rounded-lg bg-white/80 border border-rose-200 p-3">
                  <p className="font-semibold text-rose-800">
                    Alasan Penolakan:
                  </p>
                  <p className="mt-0.5 text-stone-800 italic font-mono text-xs">
                    "
                    {progression.rejection_reason ||
                      "Tidak ada alasan spesifik yang dicatat."}
                    "
                  </p>
                </div>
                <p className="mt-2 text-stone-600">
                  Anda dapat memperbarui tipe atau rombel tujuan melalui tombol
                  Edit Usulan di bawah, lalu mengajukan kembali.
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Two-Column Grid: Left (Progression Spec), Right (Source Report Card) */}
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
          {/* Left Column: Detail Usulan (5 cols) */}
          <div className="space-y-6 lg:col-span-5">
            {/* Student & Mutation Card */}
            <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between border-b border-stone-100 pb-3">
                <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
                  Rencana Mutasi Akademik
                </span>
                <span className="rounded bg-stone-100 px-2 py-0.5 text-xs font-medium text-stone-600">
                  ID: #{progression.id}
                </span>
              </div>

              {/* Mutation Path Visual */}
              <div className="rounded-lg bg-stone-50 border border-stone-200 p-4 space-y-3">
                <div className="flex items-center justify-between text-xs">
                  <div>
                    <span className="text-[11px] font-semibold uppercase text-stone-400">
                      Asal
                    </span>
                    <p className="text-sm font-bold text-stone-900">
                      {sourceClassroomName}
                    </p>
                    <p className="text-xs text-stone-500">
                      {progression.source_academic_year?.name}
                    </p>
                  </div>
                  <ArrowRight className="h-5 w-5 text-emerald-600 shrink-0" />
                  <div className="text-right">
                    <span className="text-[11px] font-semibold uppercase text-stone-400">
                      Tujuan
                    </span>
                    {progression.type === "graduation" ? (
                      <div>
                        <p className="text-sm font-bold text-blue-700">
                          Lulus (Graduated)
                        </p>
                        <p className="text-xs text-stone-500">
                          Alumni Madrasah
                        </p>
                      </div>
                    ) : (
                      <div>
                        <p className="text-sm font-bold text-stone-900">
                          {progression.target_classroom
                            ? `Kelas ${progression.target_classroom.grade_level}-${progression.target_classroom.section}`
                            : "Belum Ditentukan"}
                        </p>
                        <p className="text-xs text-stone-500">
                          {progression.target_academic_year?.name || "-"}
                        </p>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Student Attributes */}
              <div className="space-y-2 text-xs">
                <div className="flex items-center justify-between py-1 border-b border-stone-100">
                  <span className="text-stone-500">Nama Siswa:</span>
                  <span className="font-semibold text-stone-900">
                    {progression.student.name}
                  </span>
                </div>
                <div className="flex items-center justify-between py-1 border-b border-stone-100">
                  <span className="text-stone-500">
                    Nomor Induk Siswa (NIS):
                  </span>
                  <span className="font-mono font-semibold text-stone-900">
                    {progression.student.nis}
                  </span>
                </div>
                <div className="flex items-center justify-between py-1 border-b border-stone-100">
                  <span className="text-stone-500">Jenis Kelamin:</span>
                  <span className="text-stone-900">
                    {progression.student.gender === "M"
                      ? "Laki-laki"
                      : "Perempuan"}
                  </span>
                </div>
                <div className="flex items-center justify-between py-1 border-b border-stone-100">
                  <span className="text-stone-500">Status Siswa Saat Ini:</span>
                  <span className="capitalize font-medium text-emerald-700">
                    {progression.student.status}
                  </span>
                </div>
              </div>

              {/* Reason / Catatan */}
              {progression.reason && (
                <div className="pt-2 text-xs">
                  <span className="font-semibold text-stone-600">
                    Catatan Pengusulan:
                  </span>
                  <p className="mt-1 rounded bg-stone-50 p-2.5 text-stone-800 border border-stone-100">
                    {progression.reason}
                  </p>
                </div>
              )}
            </div>

            {/* Audit Trail Card */}
            <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-3">
              <span className="text-xs font-semibold uppercase tracking-wider text-stone-400">
                Audit Trail & Jejak Keputusan
              </span>

              <div className="space-y-3 text-xs">
                {/* Proposer */}
                <div className="flex items-start gap-2.5">
                  <User className="h-4 w-4 text-stone-400 mt-0.5 shrink-0" />
                  <div>
                    <p className="font-semibold text-stone-900">
                      Diusulkan oleh:{" "}
                      {progression.proposed_by?.name || "Staf Pengusul"}
                    </p>
                    <p className="text-[11px] text-stone-500">
                      {progression.created_at}
                    </p>
                  </div>
                </div>

                {/* Approver */}
                <div className="flex items-start gap-2.5">
                  <ShieldCheck className="h-4 w-4 text-sky-600 mt-0.5 shrink-0" />
                  <div>
                    <p className="font-semibold text-stone-900">
                      Persetujuan Kepala Madrasah:{" "}
                      {progression.approved_by?.name
                        ? progression.approved_by.name
                        : "Belum Disetujui"}
                    </p>
                    {progression.approved_at && (
                      <p className="text-[11px] text-stone-500">
                        {progression.approved_at}
                      </p>
                    )}
                  </div>
                </div>

                {/* Applier */}
                <div className="flex items-start gap-2.5">
                  <CheckCircle2 className="h-4 w-4 text-teal-600 mt-0.5 shrink-0" />
                  <div>
                    <p className="font-semibold text-stone-900">
                      Penerapan Mutasi:{" "}
                      {progression.applied_by?.name
                        ? progression.applied_by.name
                        : "Belum Diterapkan"}
                    </p>
                    {progression.applied_at && (
                      <p className="text-[11px] text-stone-500">
                        {progression.applied_at}
                      </p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right Column: Source Report Card Review (7 cols) */}
          <div className="space-y-6 lg:col-span-7">
            <div className="rounded-xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
              <div className="flex items-center justify-between border-b border-stone-100 pb-3">
                <div className="flex items-center gap-2">
                  <FileText className="h-4 w-4 text-emerald-700" />
                  <h3 className="text-sm font-bold text-stone-900">
                    Tinjauan Rapor Resmi Siswa (Sumber Keputusan)
                  </h3>
                </div>
                {report && (
                  <div className="flex items-center gap-2">
                    <Link
                      href={`/academic/report-cards/${report.id}`}
                      className="inline-flex items-center gap-1 rounded border border-stone-200 bg-stone-50 px-2 py-1 text-xs font-semibold text-stone-700 hover:bg-stone-100 transition"
                    >
                      Buka Rapor
                    </Link>
                    <a
                      href={`/academic/report-cards/${report.id}/pdf`}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition"
                    >
                      <Download className="h-3 w-3" />
                      PDF
                    </a>
                  </div>
                )}
              </div>

              {!report ? (
                <div className="rounded-lg bg-stone-50 border border-stone-200 p-6 text-center text-xs text-stone-500">
                  Usulan ini dibuat tanpa tautan rapor resmi langsung.
                </div>
              ) : (
                <>
                  {/* Report Card Meta */}
                  <div className="grid grid-cols-2 gap-3 sm:grid-cols-4 rounded-lg bg-stone-50 border border-stone-200 p-3 text-xs">
                    <div>
                      <span className="text-[11px] text-stone-500">
                        ID Rapor:
                      </span>
                      <p className="font-bold text-stone-900">#{report.id}</p>
                    </div>
                    <div>
                      <span className="text-[11px] text-stone-500">
                        Status Rapor:
                      </span>
                      <p className="font-semibold text-emerald-700 uppercase">
                        {report.status}
                      </p>
                    </div>
                    <div>
                      <span className="text-[11px] text-stone-500">
                        Versi / Revisi:
                      </span>
                      <p className="font-bold text-stone-900">
                        v{report.version} (Rev {report.revision})
                      </p>
                    </div>
                    <div>
                      <span className="text-[11px] text-stone-500">
                        Tanggal Terbit:
                      </span>
                      <p className="text-stone-700">
                        {report.published_at || "-"}
                      </p>
                    </div>
                  </div>

                  {/* Attendance Summary */}
                  {report.attendance_summary && (
                    <div className="rounded-lg border border-stone-200 p-3 text-xs space-y-1.5">
                      <span className="font-semibold text-stone-700 block">
                        Rekapitulasi Presensi Semester:
                      </span>
                      <div className="grid grid-cols-5 gap-2 text-center text-[11px]">
                        <div className="rounded bg-emerald-50 p-1.5 text-emerald-800">
                          <span className="block text-[10px] text-stone-500">
                            Hadir
                          </span>
                          <span className="font-bold">
                            {report.attendance_summary.present}
                          </span>
                        </div>
                        <div className="rounded bg-blue-50 p-1.5 text-blue-800">
                          <span className="block text-[10px] text-stone-500">
                            Izin
                          </span>
                          <span className="font-bold">
                            {report.attendance_summary.excused}
                          </span>
                        </div>
                        <div className="rounded bg-amber-50 p-1.5 text-amber-800">
                          <span className="block text-[10px] text-stone-500">
                            Sakit
                          </span>
                          <span className="font-bold">
                            {report.attendance_summary.sick}
                          </span>
                        </div>
                        <div className="rounded bg-rose-50 p-1.5 text-rose-800">
                          <span className="block text-[10px] text-stone-500">
                            Alpa
                          </span>
                          <span className="font-bold">
                            {report.attendance_summary.absent}
                          </span>
                        </div>
                        <div className="rounded bg-stone-100 p-1.5 text-stone-800">
                          <span className="block text-[10px] text-stone-500">
                            Persentase
                          </span>
                          <span className="font-bold">
                            {report.attendance_summary.rate || "0"}%
                          </span>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Subject Scores & KKM Status Table */}
                  <div>
                    <span className="text-xs font-semibold text-stone-700 block mb-2">
                      Capaian Nilai & Status KKM Mata Pelajaran:
                    </span>
                    <div className="overflow-x-auto rounded-lg border border-stone-200">
                      <table className="min-w-full divide-y divide-stone-200 text-left text-xs">
                        <thead className="bg-stone-50 font-semibold text-stone-600">
                          <tr>
                            <th scope="col" className="px-3 py-2">
                              Mata Pelajaran
                            </th>
                            <th scope="col" className="px-3 py-2 text-center">
                              KKM
                            </th>
                            <th scope="col" className="px-3 py-2 text-center">
                              Nilai Akhir
                            </th>
                            <th scope="col" className="px-3 py-2 text-center">
                              Status KKM
                            </th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-stone-200 bg-white">
                          {!report.items || report.items.length === 0 ? (
                            <tr>
                              <td
                                colSpan={4}
                                className="px-3 py-4 text-center text-stone-400"
                              >
                                Belum ada rincian nilai mata pelajaran.
                              </td>
                            </tr>
                          ) : (
                            report.items.map((item) => {
                              const subjectName =
                                item.subject_snapshot?.name ||
                                item.class_subject?.subject?.name ||
                                `Mapel #${item.class_subject_id}`;
                              const kkmVal = item.kkm_snapshot || "70";
                              const isPassing =
                                item.kkm_status === "complete" ||
                                item.kkm_status === "passed";

                              return (
                                <tr
                                  key={item.id}
                                  className="hover:bg-stone-50/60"
                                >
                                  <td className="px-3 py-2 font-medium text-stone-900">
                                    {subjectName}
                                  </td>
                                  <td className="px-3 py-2 text-center text-stone-600">
                                    {kkmVal}
                                  </td>
                                  <td className="px-3 py-2 text-center font-bold text-stone-900">
                                    {item.final_score !== null
                                      ? item.final_score
                                      : "-"}
                                  </td>
                                  <td className="px-3 py-2 text-center">
                                    {isPassing ? (
                                      <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                        <CheckCircle2 className="h-3 w-3" />
                                        Tuntas
                                      </span>
                                    ) : (
                                      <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600">
                                        <XCircle className="h-3 w-3" />
                                        Belum Tuntas
                                      </span>
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

                  {/* Homeroom Note */}
                  {report.homeroom_note && (
                    <div className="rounded-lg bg-stone-50 border border-stone-200 p-3 text-xs">
                      <span className="font-semibold text-stone-700 block mb-1">
                        Catatan Wali Kelas:
                      </span>
                      <p className="text-stone-700 italic">
                        "{report.homeroom_note}"
                      </p>
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        </div>

        {/* Action Toolbar Footer */}
        <div className="sticky bottom-4 z-20 rounded-2xl border border-stone-200 bg-white/95 p-4 shadow-lg backdrop-blur-md">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-center gap-2 text-xs text-stone-600">
              <Info className="h-4 w-4 text-stone-400 shrink-0" />
              <span>
                Status Usulan Saat Ini: <strong>{progression.status}</strong>.
                Otorisasi disesuaikan dengan peran Anda.
              </span>
            </div>

            <div className="flex flex-wrap items-center gap-2">
              {/* Draft / Needs Review Actions */}
              {(progression.status === "draft" ||
                progression.status === "needs_review" ||
                progression.status === "rejected") && (
                <>
                  <button
                    type="button"
                    onClick={() => setShowEditModal(true)}
                    className="inline-flex items-center gap-1.5 rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50 transition"
                  >
                    <Edit className="h-3.5 w-3.5" />
                    Edit Usulan
                  </button>

                  <button
                    type="button"
                    disabled={isProcessing}
                    onClick={handleSubmitToPrincipal}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-sky-700 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-sky-800 disabled:opacity-50 transition"
                  >
                    <Send className="h-3.5 w-3.5" />
                    {isProcessing
                      ? "Mengajukan..."
                      : "Ajukan ke Kepala Madrasah"}
                  </button>

                  {progression.status === "draft" && (
                    <button
                      type="button"
                      disabled={isProcessing}
                      onClick={handleDeleteDraft}
                      className="inline-flex items-center gap-1.5 rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 transition"
                      title="Hapus draf usulan"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                      Hapus
                    </button>
                  )}
                </>
              )}

              {/* Principal Approval Actions */}
              {isPrincipal && progression.status === "submitted" && (
                <>
                  <button
                    type="button"
                    disabled={isProcessing}
                    onClick={() => setShowRejectModal(true)}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50 transition"
                  >
                    <X className="h-3.5 w-3.5" />
                    Tolak Usulan
                  </button>

                  <button
                    type="button"
                    disabled={isProcessing}
                    onClick={handleApprove}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:opacity-50 transition"
                  >
                    <Check className="h-3.5 w-3.5" />
                    {isProcessing
                      ? "Menyetujui..."
                      : "Setujui Usulan (Principal)"}
                  </button>
                </>
              )}

              {/* Administrative Apply Action */}
              {isAdministrativeStaff && progression.status === "approved" && (
                <button
                  type="button"
                  onClick={() => setShowApplyModal(true)}
                  className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-4 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 transition"
                >
                  <Play className="h-3.5 w-3.5" />
                  Terapkan Mutasi Akademik
                </button>
              )}

              {/* Applied Notice */}
              {progression.status === "applied" && (
                <span className="inline-flex items-center gap-1.5 text-xs font-bold text-teal-700">
                  <CheckCircle2 className="h-4 w-4" />
                  Mutasi Telah Diterapkan Secara Permanen
                </span>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* MODAL: Edit Usulan */}
      {showEditModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-lg rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
            <div className="flex items-center justify-between border-b border-stone-100 pb-3">
              <h3 className="text-sm font-bold text-stone-900">
                Pembaruan Usulan Mutasi
              </h3>
              <button
                type="button"
                onClick={() => setShowEditModal(false)}
                className="text-stone-400 hover:text-stone-600"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            <form
              onSubmit={handleEditSubmit}
              className="mt-4 space-y-4 text-xs"
            >
              <div>
                <label className="block font-semibold text-stone-700 mb-1">
                  Tipe Usulan
                </label>
                <select
                  required
                  value={editData.type}
                  onChange={(e) =>
                    setEditData({ ...editData, type: e.target.value as any })
                  }
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="promotion">Kenaikan Kelas (promotion)</option>
                  <option value="graduation">Kelulusan (graduation)</option>
                  <option value="retention">Tinggal Kelas (retention)</option>
                </select>
              </div>

              {editData.type !== "graduation" && (
                <>
                  <div>
                    <label className="block font-semibold text-stone-700 mb-1">
                      Tahun Ajaran Tujuan
                    </label>
                    <select
                      value={editData.target_academic_year_id}
                      onChange={(e) =>
                        setEditData({
                          ...editData,
                          target_academic_year_id: e.target.value,
                        })
                      }
                      className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                    >
                      <option value="">Pilih Tahun Ajaran</option>
                      {targetAcademicYears.map((ay) => (
                        <option key={ay.id} value={ay.id}>
                          {ay.name}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="block font-semibold text-stone-700 mb-1">
                      Rombel Tujuan
                    </label>
                    <select
                      value={editData.target_classroom_id}
                      onChange={(e) =>
                        setEditData({
                          ...editData,
                          target_classroom_id: e.target.value,
                        })
                      }
                      className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                    >
                      <option value="">Belum Ditentukan</option>
                      {targetClassrooms.map((c) => (
                        <option key={c.id} value={c.id}>
                          Kelas {c.grade_level}-{c.section}{" "}
                          {c.term?.name ? `(${c.term.name})` : ""}
                        </option>
                      ))}
                    </select>
                  </div>
                </>
              )}

              <div>
                <label className="block font-semibold text-stone-700 mb-1">
                  Catatan / Alasan
                </label>
                <textarea
                  rows={3}
                  value={editData.reason}
                  onChange={(e) =>
                    setEditData({ ...editData, reason: e.target.value })
                  }
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-stone-100">
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 font-semibold text-stone-600 hover:bg-stone-50 transition"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isSubmittingEdit}
                  className="rounded-lg bg-emerald-700 px-4 py-1.5 font-semibold text-white hover:bg-emerald-800 disabled:opacity-50 transition"
                >
                  {isSubmittingEdit ? "Menyimpan..." : "Simpan Perubahan"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Tolak Usulan (Principal) */}
      {showRejectModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
            <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                <XCircle className="h-4 w-4" />
              </div>
              <h3 className="text-sm font-bold text-stone-900">
                Tolak Usulan Siswa {progression.student.name}
              </h3>
            </div>

            <form
              onSubmit={handleRejectSubmit}
              className="mt-4 space-y-4 text-xs"
            >
              <div>
                <label className="block font-semibold text-stone-700 mb-1">
                  Alasan Penolakan <span className="text-rose-600">*</span>
                </label>
                <textarea
                  required
                  rows={3}
                  placeholder="Tuliskan alasan penolakan secara jelas untuk dicatat dalam log audit resmi..."
                  value={rejectionReason}
                  onChange={(e) => setRejectionReason(e.target.value)}
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-rose-600 focus:outline-none focus:ring-1 focus:ring-rose-600"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-stone-100">
                <button
                  type="button"
                  onClick={() => setShowRejectModal(false)}
                  className="rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 font-semibold text-stone-600 hover:bg-stone-50 transition"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={isSubmittingReject}
                  className="rounded-lg bg-rose-600 px-4 py-1.5 font-semibold text-white hover:bg-rose-700 disabled:opacity-50 transition"
                >
                  {isSubmittingReject ? "Menolak..." : "Konfirmasi Penolakan"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Konfirmasi Penerapan (Admin) */}
      {showApplyModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
          <div className="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
            <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                <Play className="h-4 w-4" />
              </div>
              <h3 className="text-sm font-bold text-stone-900">
                Konfirmasi Eksekusi Mutasi Akademik
              </h3>
            </div>

            <div className="mt-4 space-y-3 text-xs text-stone-700 leading-relaxed">
              <p>
                Anda akan menerapkan usulan <strong>{progression.type}</strong>{" "}
                untuk siswa <strong>{progression.student.name}</strong>.
              </p>
              <div className="rounded-lg bg-stone-50 border border-stone-200 p-3 text-[11px] text-stone-600 space-y-1">
                <p className="font-semibold text-stone-800">Efek Eksekusi:</p>
                <ul className="list-disc list-inside space-y-0.5">
                  {progression.type === "graduation" ? (
                    <li>
                      Status siswa diperbarui menjadi 'graduated' (Lulus).
                      Riwayat akademik masa lalu tetap utuh.
                    </li>
                  ) : (
                    <li>
                      Data pendaftaran (enrollment) baru dibuat di rombel &
                      tahun ajaran tujuan. Histori pendaftaran lama
                      dipertahankan.
                    </li>
                  )}
                  <li>Keputusan bersifat final dan terkunci (apply-once).</li>
                </ul>
              </div>
            </div>

            <div className="mt-5 flex items-center justify-end gap-2 pt-3 border-t border-stone-100 text-xs">
              <button
                type="button"
                onClick={() => setShowApplyModal(false)}
                className="rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 font-semibold text-stone-600 hover:bg-stone-50 transition"
              >
                Batal
              </button>
              <button
                type="button"
                disabled={isSubmittingApply}
                onClick={handleApply}
                className="rounded-lg bg-teal-700 px-4 py-1.5 font-semibold text-white hover:bg-teal-800 disabled:opacity-50 transition"
              >
                {isSubmittingApply ? "Mengeksekusi..." : "Eksekusi Sekarang"}
              </button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
