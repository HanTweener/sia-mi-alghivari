import React, { useState, useMemo } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
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
  ChevronRight,
  ShieldCheck,
  ShieldAlert,
  GraduationCap,
  TrendingUp,
  RotateCcw,
  Clock,
  Plus,
  Send,
  Check,
  X,
  Play,
  Trash2,
  HelpCircle,
  AlertOctagon,
  Layers,
  ArrowRight,
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

interface ReportCard {
  id: number;
  status: string;
  availability: string;
  revision: number;
  version: number;
  finalized_at: string | null;
  published_at: string | null;
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

interface ProgressionItem {
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
  approved_by?: { id: number; name: string } | null;
}

interface PaginatedResponse {
  data: ProgressionItem[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
}

interface FilterOption {
  value: string;
  label: string;
}

interface Props {
  progressions: PaginatedResponse;
  filters: {
    source_academic_year_id?: string | number;
    classroom_id?: string | number;
    type?: string;
    status?: string;
  };
  academicYears: AcademicYear[];
  classrooms: Classroom[];
  types: FilterOption[];
  statuses: FilterOption[];
}

export default function Index({
  progressions,
  filters,
  academicYears,
  classrooms,
  types,
  statuses,
}: Props) {
  const pageProps = usePage().props as any;
  const userRoles: string[] = pageProps.auth?.user?.roles || [];
  const flash = pageProps.flash || {};

  const isPrincipal = userRoles.includes("principal");
  const isMasterAdmin = userRoles.includes("master_admin");
  const isAcademicAdmin = userRoles.includes("academic_admin");
  const isAdministrativeStaff = isMasterAdmin || isAcademicAdmin;

  // Local filter states
  const [sourceYear, setSourceYear] = useState<string>(
    filters.source_academic_year_id
      ? String(filters.source_academic_year_id)
      : "",
  );
  const [selectedClassroomId, setSelectedClassroomId] = useState<string>(
    filters.classroom_id ? String(filters.classroom_id) : "",
  );
  const [selectedType, setSelectedType] = useState<string>(filters.type || "");
  const [selectedStatus, setSelectedStatus] = useState<string>(
    filters.status || "",
  );
  const [searchStudent, setSearchStudent] = useState<string>("");

  // Batch selection
  const [selectedIds, setSelectedIds] = useState<number[]>([]);

  // Modals state
  const [showBatchCreateModal, setShowBatchCreateModal] =
    useState<boolean>(false);
  const [batchCreateData, setBatchCreateData] = useState({
    classroom_id: classrooms[0]?.id ? String(classrooms[0].id) : "",
    type: "promotion",
    source_academic_year_id: academicYears[0]?.id
      ? String(academicYears[0].id)
      : "",
    target_academic_year_id: academicYears[0]?.id
      ? String(academicYears[0].id)
      : "",
    target_classroom_id: "",
    reason: "",
  });
  const [isSubmittingBatchCreate, setIsSubmittingBatchCreate] = useState(false);

  // Reject modal state
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectingItem, setRejectingItem] = useState<ProgressionItem | null>(
    null,
  );
  const [isBatchReject, setIsBatchReject] = useState(false);
  const [rejectionReason, setRejectionReason] = useState("");
  const [isSubmittingReject, setIsSubmittingReject] = useState(false);

  // Apply confirmation modal state
  const [showApplyModal, setShowApplyModal] = useState(false);
  const [applyingItem, setApplyingItem] = useState<ProgressionItem | null>(
    null,
  );
  const [isBatchApply, setIsBatchApply] = useState(false);
  const [isSubmittingApply, setIsSubmittingApply] = useState(false);

  // Quick action processing tracking
  const [processingId, setProcessingId] = useState<number | null>(null);

  // Apply filters
  const applyFilters = (newFilters: {
    source_academic_year_id?: string;
    classroom_id?: string;
    type?: string;
    status?: string;
  }) => {
    const params: Record<string, string> = {};
    if (newFilters.source_academic_year_id)
      params.source_academic_year_id = newFilters.source_academic_year_id;
    if (newFilters.classroom_id) params.classroom_id = newFilters.classroom_id;
    if (newFilters.type) params.type = newFilters.type;
    if (newFilters.status) params.status = newFilters.status;

    router.get("/academic/progressions", params, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleResetFilters = () => {
    setSourceYear("");
    setSelectedClassroomId("");
    setSelectedType("");
    setSelectedStatus("");
    setSearchStudent("");
    router.get(
      "/academic/progressions",
      {},
      {
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  // Filtered items on client for instant search
  const filteredItems = useMemo(() => {
    return progressions.data.filter((item) => {
      if (!searchStudent.trim()) return true;
      const q = searchStudent.toLowerCase();
      return (
        item.student.name.toLowerCase().includes(q) ||
        item.student.nis.toLowerCase().includes(q)
      );
    });
  }, [progressions.data, searchStudent]);

  // Statistics calculation
  const stats = useMemo(() => {
    const list = progressions.data;
    return {
      total: list.length,
      draft: list.filter((i) => i.status === "draft").length,
      submitted: list.filter((i) => i.status === "submitted").length,
      approved: list.filter((i) => i.status === "approved").length,
      applied: list.filter((i) => i.status === "applied").length,
      needs_review: list.filter((i) => i.status === "needs_review").length,
      rejected: list.filter((i) => i.status === "rejected").length,
    };
  }, [progressions.data]);

  // Selection handlers
  const handleToggleSelectAll = () => {
    if (selectedIds.length === filteredItems.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(filteredItems.map((i) => i.id));
    }
  };

  const handleToggleSelectItem = (id: number) => {
    if (selectedIds.includes(id)) {
      setSelectedIds(selectedIds.filter((item) => item !== id));
    } else {
      setSelectedIds([...selectedIds, id]);
    }
  };

  // Principal batch approve
  const handlePrincipalBatchApprove = () => {
    const candidateItems = progressions.data.filter(
      (item) => selectedIds.includes(item.id) && item.status === "submitted",
    );

    if (candidateItems.length === 0) {
      alert("Pilih setidaknya satu usulan berstatus 'Diajukan' (submitted).");
      return;
    }

    if (
      !confirm(
        `Setujui ${candidateItems.length} usulan kenaikan/kelulusan terpilih sebagai Kepala Madrasah?`,
      )
    ) {
      return;
    }

    router.post(
      "/academic/progressions/batch-approve",
      {
        items: candidateItems.map((item) => ({
          progression_id: item.id,
          expected_version: item.version,
          expected_source_report_id: item.source_report_card_id,
        })),
      },
      {
        preserveScroll: true,
        onSuccess: () => setSelectedIds([]),
      },
    );
  };

  // Open batch reject modal
  const handleOpenBatchRejectModal = () => {
    const candidateItems = progressions.data.filter(
      (item) => selectedIds.includes(item.id) && item.status === "submitted",
    );

    if (candidateItems.length === 0) {
      alert(
        "Pilih setidaknya satu usulan berstatus 'Diajukan' (submitted) untuk ditolak.",
      );
      return;
    }

    setIsBatchReject(true);
    setRejectingItem(null);
    setRejectionReason("");
    setShowRejectModal(true);
  };

  // Open single reject modal
  const handleOpenSingleRejectModal = (item: ProgressionItem) => {
    setIsBatchReject(false);
    setRejectingItem(item);
    setRejectionReason("");
    setShowRejectModal(true);
  };

  // Submit reject (single or batch)
  const handleSubmitReject = (e: React.FormEvent) => {
    e.preventDefault();
    if (!rejectionReason.trim()) {
      alert("Alasan penolakan wajib diisi.");
      return;
    }

    setIsSubmittingReject(true);

    if (isBatchReject) {
      const candidateItems = progressions.data.filter(
        (item) => selectedIds.includes(item.id) && item.status === "submitted",
      );

      router.post(
        "/academic/progressions/batch-reject",
        {
          items: candidateItems.map((item) => ({
            progression_id: item.id,
            expected_version: item.version,
          })),
          reason: rejectionReason.trim(),
        },
        {
          preserveScroll: true,
          onFinish: () => {
            setIsSubmittingReject(false);
            setShowRejectModal(false);
            setSelectedIds([]);
          },
        },
      );
    } else if (rejectingItem) {
      router.post(
        `/academic/progressions/${rejectingItem.id}/reject`,
        {
          expected_version: rejectingItem.version,
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
    }
  };

  // Principal single approve
  const handleSingleApprove = (item: ProgressionItem) => {
    if (!confirm(`Setujui usulan untuk siswa ${item.student.name}?`)) return;

    setProcessingId(item.id);
    router.post(
      `/academic/progressions/${item.id}/approve`,
      {
        expected_version: item.version,
        expected_source_report_id: item.source_report_card_id,
      },
      {
        preserveScroll: true,
        onFinish: () => setProcessingId(null),
      },
    );
  };

  // Proposer submit draft to principal
  const handleSingleSubmit = (item: ProgressionItem) => {
    if (
      !confirm(
        `Ajukan usulan ${item.student.name} ke Kepala Madrasah untuk ditinjau?`,
      )
    ) {
      return;
    }

    setProcessingId(item.id);
    router.post(
      `/academic/progressions/${item.id}/submit`,
      {},
      {
        preserveScroll: true,
        onFinish: () => setProcessingId(null),
      },
    );
  };

  // Open apply confirmation modal
  const handleOpenApplyModal = (item?: ProgressionItem) => {
    if (item) {
      setIsBatchApply(false);
      setApplyingItem(item);
    } else {
      const candidateItems = progressions.data.filter(
        (i) => selectedIds.includes(i.id) && i.status === "approved",
      );
      if (candidateItems.length === 0) {
        alert(
          "Pilih setidaknya satu usulan berstatus 'Disetujui' (approved) untuk diterapkan.",
        );
        return;
      }
      setIsBatchApply(true);
      setApplyingItem(null);
    }
    setShowApplyModal(true);
  };

  // Submit apply (single or batch)
  const handleSubmitApply = () => {
    setIsSubmittingApply(true);

    if (isBatchApply) {
      const candidateIds = progressions.data
        .filter((i) => selectedIds.includes(i.id) && i.status === "approved")
        .map((i) => i.id);

      router.post(
        "/academic/progressions/batch-apply",
        { progression_ids: candidateIds },
        {
          preserveScroll: true,
          onFinish: () => {
            setIsSubmittingApply(false);
            setShowApplyModal(false);
            setSelectedIds([]);
          },
        },
      );
    } else if (applyingItem) {
      router.post(
        `/academic/progressions/${applyingItem.id}/apply`,
        {},
        {
          preserveScroll: true,
          onFinish: () => {
            setIsSubmittingApply(false);
            setShowApplyModal(false);
          },
        },
      );
    }
  };

  // Batch create submit
  const handleBatchCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmittingBatchCreate(true);

    router.post("/academic/progressions", batchCreateData, {
      preserveScroll: true,
      onSuccess: () => {
        setShowBatchCreateModal(false);
      },
      onFinish: () => setIsSubmittingBatchCreate(false),
    });
  };

  // Delete draft progression
  const handleDeleteDraft = (item: ProgressionItem) => {
    if (!confirm(`Hapus draf usulan untuk siswa ${item.student.name}?`)) return;

    setProcessingId(item.id);
    router.delete(`/academic/progressions/${item.id}`, {
      preserveScroll: true,
      onFinish: () => setProcessingId(null),
    });
  };

  const getTypeBadge = (type: string) => {
    switch (type) {
      case "promotion":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
            <TrendingUp className="h-3 w-3" />
            Kenaikan Kelas
          </span>
        );
      case "graduation":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">
            <GraduationCap className="h-3 w-3" />
            Kelulusan
          </span>
        );
      case "retention":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
            <RotateCcw className="h-3 w-3" />
            Tinggal Kelas
          </span>
        );
      default:
        return (
          <span className="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">
            {type}
          </span>
        );
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "draft":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-medium text-stone-700">
            <Clock className="h-3 w-3 text-stone-500" />
            Draf
          </span>
        );
      case "submitted":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800">
            <Send className="h-3 w-3 text-sky-600" />
            Diajukan
          </span>
        );
      case "approved":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">
            <ShieldCheck className="h-3 w-3 text-emerald-600" />
            Disetujui
          </span>
        );
      case "rejected":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">
            <XCircle className="h-3 w-3 text-rose-600" />
            Ditolak
          </span>
        );
      case "applied":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
            <CheckCircle2 className="h-3 w-3 text-teal-600" />
            Diterapkan
          </span>
        );
      case "needs_review":
        return (
          <span className="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-900 border border-amber-300">
            <AlertTriangle className="h-3 w-3 text-amber-600" />
            Perlu Tinjauan
          </span>
        );
      default:
        return (
          <span className="rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600">
            {status}
          </span>
        );
    }
  };

  return (
    <AppLayout
      title="Ruang Kerja Kenaikan & Kelulusan — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Kenaikan & Kelulusan (SCR-10 / F-09)
            </Badge>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Ruang Kerja Kenaikan & Kelulusan
          </h1>
        </div>
      }
      actions={
        isAdministrativeStaff && (
          <Button
            type="button"
            variant="default"
            size="sm"
            onClick={() => setShowBatchCreateModal(true)}
            className="gap-1.5 text-xs font-semibold"
            title="Penyusunan usulan draf kenaikan/kelulusan massal per rombel"
          >
            <Plus className="size-3.5" />
            <span>Buat Draf Rombel</span>
          </Button>
        )
      }
    >
      <div className="space-y-6">
        <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 space-y-6">
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

          {/* Warning Alert if any item is needs_review */}
          {stats.needs_review > 0 && (
            <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-sm flex items-start gap-3">
              <AlertTriangle className="h-5 w-5 text-amber-700 shrink-0 mt-0.5" />
              <div className="text-xs text-amber-900 leading-relaxed">
                <p className="font-bold text-sm">
                  Perhatian: Terdapat {stats.needs_review} Usulan Berstatus
                  'Perlu Tinjauan' (needs_review)
                </p>
                <p className="mt-1">
                  Data rapor resmi siswa mengalami perubahan atau penarikan
                  (revocation) setelah usulan diajukan/disetujui. Penerapan
                  mutasi akademik diblokir hingga usulan diperbarui dan
                  disetujui ulang oleh Kepala Madrasah.
                </p>
              </div>
            </div>
          )}

          {/* Warning Alert if any item is rejected */}
          {stats.rejected > 0 && (
            <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm flex items-start gap-3">
              <XCircle className="h-5 w-5 text-rose-700 shrink-0 mt-0.5" />
              <div className="text-xs text-rose-900 leading-relaxed">
                <p className="font-bold text-sm">
                  Pemberitahuan: Terdapat {stats.rejected} Usulan Ditolak oleh
                  Kepala Madrasah
                </p>
                <p className="mt-1">
                  Silakan periksa alasan penolakan pada halaman rincian siswa,
                  perbarui rombel/tipe tujuan, lalu ajukan kembali.
                </p>
              </div>
            </div>
          )}

          {/* Quick KPI Stat Cards */}
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div className="rounded-xl border border-stone-200 bg-white p-3.5 shadow-sm">
              <p className="text-xs font-semibold text-stone-500 uppercase">
                Total
              </p>
              <p className="mt-1 text-2xl font-bold text-stone-900">
                {progressions.total}
              </p>
            </div>
            <div className="rounded-xl border border-stone-200 bg-white p-3.5 shadow-sm">
              <p className="text-xs font-semibold text-stone-500 uppercase">
                Draf
              </p>
              <p className="mt-1 text-2xl font-bold text-stone-700">
                {stats.draft}
              </p>
            </div>
            <div
              className={`rounded-xl border p-3.5 shadow-sm ${isPrincipal && stats.submitted > 0 ? "border-sky-300 bg-sky-50" : "border-stone-200 bg-white"}`}
            >
              <p className="text-xs font-semibold text-sky-700 uppercase">
                Menunggu
              </p>
              <p className="mt-1 text-2xl font-bold text-sky-800">
                {stats.submitted}
              </p>
            </div>
            <div
              className={`rounded-xl border p-3.5 shadow-sm ${isAdministrativeStaff && stats.approved > 0 ? "border-emerald-300 bg-emerald-50" : "border-stone-200 bg-white"}`}
            >
              <p className="text-xs font-semibold text-emerald-700 uppercase">
                Disetujui
              </p>
              <p className="mt-1 text-2xl font-bold text-emerald-800">
                {stats.approved}
              </p>
            </div>
            <div className="rounded-xl border border-stone-200 bg-white p-3.5 shadow-sm">
              <p className="text-xs font-semibold text-teal-700 uppercase">
                Diterapkan
              </p>
              <p className="mt-1 text-2xl font-bold text-teal-800">
                {stats.applied}
              </p>
            </div>
            <div
              className={`rounded-xl border p-3.5 shadow-sm ${stats.needs_review > 0 ? "border-amber-300 bg-amber-50" : "border-stone-200 bg-white"}`}
            >
              <p className="text-xs font-semibold text-amber-700 uppercase">
                Perlu Tinjau
              </p>
              <p className="mt-1 text-2xl font-bold text-amber-800">
                {stats.needs_review}
              </p>
            </div>
          </div>

          {/* Role-Specific Action Panels */}
          {/* Panel Kepala Madrasah */}
          {isPrincipal && (
            <div className="rounded-xl border border-sky-200 bg-gradient-to-r from-sky-50 to-white p-5 shadow-sm">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <ShieldCheck className="h-5 w-5 text-sky-700" />
                    <h2 className="text-sm font-bold text-stone-900">
                      Panel Otorisasi Kepala Madrasah (Exclusive Principal
                      Decision)
                    </h2>
                  </div>
                  <p className="mt-1 text-xs text-stone-600">
                    Keputusan persetujuan mengunci rapor sumber dan versi
                    usulan. Persetujuan terpisah dari mutasi (decoupled).
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    disabled={selectedIds.length === 0}
                    onClick={handlePrincipalBatchApprove}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-800 disabled:opacity-50 transition"
                    title="Setujui seluruh usulan terpilih yang berstatus diajukan"
                  >
                    <Check className="h-3.5 w-3.5" />
                    Setujui Massal ({selectedIds.length})
                  </button>
                  <button
                    type="button"
                    disabled={selectedIds.length === 0}
                    onClick={handleOpenBatchRejectModal}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 disabled:opacity-50 transition"
                    title="Tolak seluruh usulan terpilih disertai alasan"
                  >
                    <X className="h-3.5 w-3.5" />
                    Tolak Massal ({selectedIds.length})
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Panel Pelaksana Administrasi (Apply) */}
          {isAdministrativeStaff && (
            <div className="rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <Play className="h-5 w-5 text-emerald-700" />
                    <h2 className="text-sm font-bold text-stone-900">
                      Panel Pelaksana Mutasi Akademik (Apply Progression)
                    </h2>
                  </div>
                  <p className="mt-1 text-xs text-stone-600">
                    Eksekusi mutasi membuat pendaftaran baru di tahun ajaran
                    baru atau meluluskan siswa tanpa menghapus histori.
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    disabled={selectedIds.length === 0}
                    onClick={() => handleOpenApplyModal()}
                    className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-3.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-teal-800 disabled:opacity-50 transition"
                    title="Terapkan mutasi akademik massal untuk usulan yang telah disetujui"
                  >
                    <Play className="h-3.5 w-3.5" />
                    Terapkan Mutasi Massal ({selectedIds.length})
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* Filter Bar & Search */}
          <div className="rounded-xl border border-stone-200 bg-white p-4 shadow-sm space-y-3">
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
              {/* Source Academic Year */}
              <div>
                <label className="block text-xs font-semibold text-stone-600 mb-1">
                  Tahun Ajaran Asal
                </label>
                <select
                  value={sourceYear}
                  onChange={(e) => {
                    setSourceYear(e.target.value);
                    applyFilters({
                      source_academic_year_id: e.target.value,
                      classroom_id: selectedClassroomId,
                      type: selectedType,
                      status: selectedStatus,
                    });
                  }}
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Semua Tahun Ajaran</option>
                  {academicYears.map((ay) => (
                    <option key={ay.id} value={ay.id}>
                      {ay.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Classroom */}
              <div>
                <label className="block text-xs font-semibold text-stone-600 mb-1">
                  Rombel Asal
                </label>
                <select
                  value={selectedClassroomId}
                  onChange={(e) => {
                    setSelectedClassroomId(e.target.value);
                    applyFilters({
                      source_academic_year_id: sourceYear,
                      classroom_id: e.target.value,
                      type: selectedType,
                      status: selectedStatus,
                    });
                  }}
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Semua Rombel</option>
                  {classrooms.map((c) => (
                    <option key={c.id} value={c.id}>
                      Kelas {c.grade_level}-{c.section}{" "}
                      {c.term?.name ? `(${c.term.name})` : ""}
                    </option>
                  ))}
                </select>
              </div>

              {/* Type */}
              <div>
                <label className="block text-xs font-semibold text-stone-600 mb-1">
                  Tipe Mutasi
                </label>
                <select
                  value={selectedType}
                  onChange={(e) => {
                    setSelectedType(e.target.value);
                    applyFilters({
                      source_academic_year_id: sourceYear,
                      classroom_id: selectedClassroomId,
                      type: e.target.value,
                      status: selectedStatus,
                    });
                  }}
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Semua Tipe</option>
                  {types.map((t) => (
                    <option key={t.value} value={t.value}>
                      {t.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Status */}
              <div>
                <label className="block text-xs font-semibold text-stone-600 mb-1">
                  Status Usulan
                </label>
                <select
                  value={selectedStatus}
                  onChange={(e) => {
                    setSelectedStatus(e.target.value);
                    applyFilters({
                      source_academic_year_id: sourceYear,
                      classroom_id: selectedClassroomId,
                      type: selectedType,
                      status: e.target.value,
                    });
                  }}
                  className="w-full rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Semua Status</option>
                  {statuses.map((s) => (
                    <option key={s.value} value={s.value}>
                      {s.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Student Search */}
              <div>
                <label className="block text-xs font-semibold text-stone-600 mb-1">
                  Cari Siswa
                </label>
                <div className="relative">
                  <Search className="pointer-events-none absolute left-3 top-2.5 h-3.5 w-3.5 text-stone-400" />
                  <input
                    type="text"
                    placeholder="Ketik NIS atau Nama..."
                    value={searchStudent}
                    onChange={(e) => setSearchStudent(e.target.value)}
                    className="w-full rounded-lg border border-stone-300 bg-white pl-8 pr-3 py-1.5 text-xs text-stone-900 placeholder-stone-400 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
              </div>
            </div>

            {(sourceYear ||
              selectedClassroomId ||
              selectedType ||
              selectedStatus ||
              searchStudent) && (
              <div className="flex items-center justify-between pt-2 border-t border-stone-100 text-xs">
                <span className="text-stone-500">Filter aktif diterapkan</span>
                <button
                  type="button"
                  onClick={handleResetFilters}
                  className="font-medium text-emerald-700 hover:text-emerald-800"
                >
                  Reset Semua Filter
                </button>
              </div>
            )}
          </div>

          {/* Progressions Table */}
          <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-stone-200 text-left text-xs">
                <thead className="bg-stone-50 font-semibold text-stone-600 uppercase tracking-wider">
                  <tr>
                    <th scope="col" className="px-4 py-3 w-10 text-center">
                      <input
                        type="checkbox"
                        checked={
                          filteredItems.length > 0 &&
                          selectedIds.length === filteredItems.length
                        }
                        onChange={handleToggleSelectAll}
                        className="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                      />
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Siswa (NIS & Nama)
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Asal
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Tipe Mutasi
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Rapor Sumber
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Tujuan
                    </th>
                    <th scope="col" className="px-4 py-3">
                      Status
                    </th>
                    <th scope="col" className="px-4 py-3 text-right">
                      Aksi
                    </th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-stone-200 bg-white">
                  {filteredItems.length === 0 ? (
                    <tr>
                      <td
                        colSpan={8}
                        className="px-4 py-8 text-center text-stone-500"
                      >
                        Tidak ada data usulan kenaikan/kelulusan yang sesuai
                        dengan filter.
                      </td>
                    </tr>
                  ) : (
                    filteredItems.map((item) => {
                      const isSelected = selectedIds.includes(item.id);
                      const sourceClassroomName =
                        item.source_report_card?.enrollment?.classroom?.name ||
                        (item.source_report_card?.enrollment?.classroom
                          ? `Kelas ${item.source_report_card.enrollment.classroom.grade_level}-${item.source_report_card.enrollment.classroom.section}`
                          : "-");

                      return (
                        <tr
                          key={item.id}
                          className={`hover:bg-stone-50/80 transition ${
                            isSelected ? "bg-emerald-50/40" : ""
                          }`}
                        >
                          <td className="px-4 py-3 text-center">
                            <input
                              type="checkbox"
                              checked={isSelected}
                              onChange={() => handleToggleSelectItem(item.id)}
                              className="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500"
                            />
                          </td>
                          <td className="px-4 py-3">
                            <Link
                              href={`/academic/progressions/${item.id}`}
                              className="font-bold text-stone-900 hover:text-emerald-700 block"
                            >
                              {item.student.name}
                            </Link>
                            <span className="text-[11px] text-stone-500">
                              NIS: {item.student.nis} •{" "}
                              {item.student.gender === "M"
                                ? "Laki-laki"
                                : "Perempuan"}
                            </span>
                          </td>
                          <td className="px-4 py-3">
                            <p className="font-medium text-stone-900">
                              {sourceClassroomName}
                            </p>
                            <p className="text-[11px] text-stone-500">
                              {item.source_academic_year?.name || "-"}
                            </p>
                          </td>
                          <td className="px-4 py-3">
                            {getTypeBadge(item.type)}
                          </td>
                          <td className="px-4 py-3">
                            {item.source_report_card ? (
                              <div>
                                <Link
                                  href={`/academic/report-cards/${item.source_report_card.id}`}
                                  className="font-semibold text-emerald-700 hover:underline flex items-center gap-1"
                                >
                                  <FileText className="h-3 w-3" />
                                  Rapor #{item.source_report_card.id}
                                </Link>
                                <span className="text-[11px] text-stone-500">
                                  Versi {item.source_report_card.version} (Rev{" "}
                                  {item.source_report_card.revision})
                                </span>
                              </div>
                            ) : (
                              <span className="text-stone-400 italic">
                                Tanpa Rapor
                              </span>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            {item.type === "graduation" ? (
                              <span className="font-medium text-blue-700">
                                Lulus / Alumni
                              </span>
                            ) : (
                              <div>
                                <p className="font-medium text-stone-900">
                                  {item.target_classroom
                                    ? `Kelas ${item.target_classroom.grade_level}-${item.target_classroom.section}`
                                    : "-"}
                                </p>
                                <p className="text-[11px] text-stone-500">
                                  {item.target_academic_year?.name || "-"}
                                </p>
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            {getStatusBadge(item.status)}
                            {item.rejection_reason && (
                              <p
                                className="mt-1 text-[11px] text-rose-600 line-clamp-1"
                                title={item.rejection_reason}
                              >
                                Alasan: {item.rejection_reason}
                              </p>
                            )}
                          </td>
                          <td className="px-4 py-3 text-right whitespace-nowrap">
                            <div className="flex items-center justify-end gap-1.5">
                              {/* Proposer Submit */}
                              {item.status === "draft" && (
                                <button
                                  type="button"
                                  disabled={processingId === item.id}
                                  onClick={() => handleSingleSubmit(item)}
                                  className="inline-flex items-center gap-1 rounded bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-100 disabled:opacity-50 transition"
                                  title="Ajukan ke Kepala Madrasah"
                                >
                                  <Send className="h-3 w-3" />
                                  Ajukan
                                </button>
                              )}

                              {/* Principal Approve / Reject */}
                              {isPrincipal && item.status === "submitted" && (
                                <>
                                  <button
                                    type="button"
                                    disabled={processingId === item.id}
                                    onClick={() => handleSingleApprove(item)}
                                    className="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 disabled:opacity-50 transition"
                                    title="Setujui Usulan"
                                  >
                                    <Check className="h-3 w-3" />
                                    Setujui
                                  </button>
                                  <button
                                    type="button"
                                    disabled={processingId === item.id}
                                    onClick={() =>
                                      handleOpenSingleRejectModal(item)
                                    }
                                    className="inline-flex items-center gap-1 rounded bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition"
                                    title="Tolak Usulan"
                                  >
                                    <X className="h-3 w-3" />
                                    Tolak
                                  </button>
                                </>
                              )}

                              {/* Admin Apply */}
                              {isAdministrativeStaff &&
                                item.status === "approved" && (
                                  <button
                                    type="button"
                                    disabled={processingId === item.id}
                                    onClick={() => handleOpenApplyModal(item)}
                                    className="inline-flex items-center gap-1 rounded bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700 hover:bg-teal-100 disabled:opacity-50 transition"
                                    title="Terapkan Mutasi Siswa"
                                  >
                                    <Play className="h-3 w-3" />
                                    Terapkan
                                  </button>
                                )}

                              {/* Proposer Delete Draft */}
                              {item.status === "draft" && (
                                <button
                                  type="button"
                                  disabled={processingId === item.id}
                                  onClick={() => handleDeleteDraft(item)}
                                  className="inline-flex items-center gap-1 rounded bg-stone-100 px-2 py-1 text-xs font-medium text-stone-600 hover:bg-stone-200 disabled:opacity-50 transition"
                                  title="Hapus Draf"
                                >
                                  <Trash2 className="h-3 w-3" />
                                </button>
                              )}

                              {/* View detail */}
                              <Link
                                href={`/academic/progressions/${item.id}`}
                                className="inline-flex items-center gap-1 rounded border border-stone-200 bg-white px-2.5 py-1 text-xs font-medium text-stone-700 hover:bg-stone-50 transition"
                              >
                                Detail
                                <ChevronRight className="h-3 w-3" />
                              </Link>
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {progressions.links && progressions.links.length > 3 && (
              <div className="flex items-center justify-between border-t border-stone-200 px-4 py-3 bg-stone-50">
                <span className="text-xs text-stone-500">
                  Menampilkan {progressions.data.length} dari{" "}
                  {progressions.total} total usulan
                </span>
                <div className="flex items-center gap-1">
                  {progressions.links.map((link, idx) => (
                    <button
                      key={idx}
                      type="button"
                      disabled={!link.url}
                      onClick={() =>
                        link.url &&
                        router.get(
                          link.url,
                          {},
                          { preserveState: true, preserveScroll: true },
                        )
                      }
                      className={`rounded px-2.5 py-1 text-xs transition ${
                        link.active
                          ? "bg-emerald-700 font-bold text-white"
                          : "border border-stone-200 bg-white text-stone-700 hover:bg-stone-50"
                      } ${!link.url ? "opacity-40 cursor-not-allowed" : ""}`}
                      dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        </main>

        {/* MODAL: Buat Draf Usulan Rombel */}
        {showBatchCreateModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
            <div className="w-full max-w-lg rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
              <div className="flex items-center justify-between border-b border-stone-100 pb-3">
                <div className="flex items-center gap-2">
                  <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <Plus className="h-4 w-4" />
                  </div>
                  <h3 className="text-sm font-bold text-stone-900">
                    Buat Draf Usulan Mutasi per Rombel
                  </h3>
                </div>
                <button
                  type="button"
                  onClick={() => setShowBatchCreateModal(false)}
                  className="text-stone-400 hover:text-stone-600"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>

              <form
                onSubmit={handleBatchCreateSubmit}
                className="mt-4 space-y-4 text-xs"
              >
                <div>
                  <label className="block font-semibold text-stone-700 mb-1">
                    Rombel Asal Siswa
                  </label>
                  <select
                    required
                    value={batchCreateData.classroom_id}
                    onChange={(e) =>
                      setBatchCreateData({
                        ...batchCreateData,
                        classroom_id: e.target.value,
                      })
                    }
                    className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  >
                    {classrooms.map((c) => (
                      <option key={c.id} value={c.id}>
                        Kelas {c.grade_level}-{c.section}{" "}
                        {c.term?.name ? `(${c.term.name})` : ""}
                      </option>
                    ))}
                  </select>
                  <p className="mt-1 text-[11px] text-stone-500">
                    Hanya siswa aktif dengan rapor resmi berstatus published
                    yang akan dibuatkan usulan.
                  </p>
                </div>

                <div>
                  <label className="block font-semibold text-stone-700 mb-1">
                    Tipe Usulan Mutasi
                  </label>
                  <select
                    required
                    value={batchCreateData.type}
                    onChange={(e) =>
                      setBatchCreateData({
                        ...batchCreateData,
                        type: e.target.value,
                      })
                    }
                    className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  >
                    <option value="promotion">
                      Kenaikan Kelas (promotion)
                    </option>
                    <option value="graduation">Kelulusan (graduation)</option>
                    <option value="retention">Tinggal Kelas (retention)</option>
                  </select>
                </div>

                {batchCreateData.type !== "graduation" && (
                  <>
                    <div>
                      <label className="block font-semibold text-stone-700 mb-1">
                        Tahun Ajaran Tujuan
                      </label>
                      <select
                        value={batchCreateData.target_academic_year_id}
                        onChange={(e) =>
                          setBatchCreateData({
                            ...batchCreateData,
                            target_academic_year_id: e.target.value,
                          })
                        }
                        className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                      >
                        <option value="">Pilih Tahun Ajaran Baru</option>
                        {academicYears.map((ay) => (
                          <option key={ay.id} value={ay.id}>
                            {ay.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block font-semibold text-stone-700 mb-1">
                        Rombel Tujuan (Opsional)
                      </label>
                      <select
                        value={batchCreateData.target_classroom_id}
                        onChange={(e) =>
                          setBatchCreateData({
                            ...batchCreateData,
                            target_classroom_id: e.target.value,
                          })
                        }
                        className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                      >
                        <option value="">
                          Tentukan Nanti / Belum Ada Rombel Tujuan
                        </option>
                        {classrooms.map((c) => (
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
                    Catatan / Keterangan Tambahan
                  </label>
                  <textarea
                    rows={2}
                    placeholder="Keterangan opsional untuk usulan rombel..."
                    value={batchCreateData.reason}
                    onChange={(e) =>
                      setBatchCreateData({
                        ...batchCreateData,
                        reason: e.target.value,
                      })
                    }
                    className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>

                <div className="flex items-center justify-end gap-2 pt-3 border-t border-stone-100">
                  <button
                    type="button"
                    onClick={() => setShowBatchCreateModal(false)}
                    className="rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmittingBatchCreate}
                    className="rounded-lg bg-emerald-700 px-4 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50 transition"
                  >
                    {isSubmittingBatchCreate
                      ? "Menyusun Draf..."
                      : "Buat Draf Rombel"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL: Penolakan Usulan (Wajib Alasan) */}
        {showRejectModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
              <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                  <XCircle className="h-4 w-4" />
                </div>
                <h3 className="text-sm font-bold text-stone-900">
                  {isBatchReject
                    ? `Tolak ${selectedIds.length} Usulan Terpilih`
                    : `Tolak Usulan Siswa ${rejectingItem?.student.name}`}
                </h3>
              </div>

              <form
                onSubmit={handleSubmitReject}
                className="mt-4 space-y-4 text-xs"
              >
                <div>
                  <label className="block font-semibold text-stone-700 mb-1">
                    Alasan Penolakan <span className="text-rose-600">*</span>
                  </label>
                  <textarea
                    required
                    rows={3}
                    placeholder="Jelaskan alasan penolakan usulan untuk dicatat dalam log audit resmi..."
                    value={rejectionReason}
                    onChange={(e) => setRejectionReason(e.target.value)}
                    className="w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs text-stone-900 focus:border-rose-600 focus:outline-none focus:ring-1 focus:ring-rose-600"
                  />
                  <p className="mt-1 text-[11px] text-stone-500">
                    Alasan ini wajib diisi dan akan diteruskan kepada staf
                    pengusul untuk perbaikan.
                  </p>
                </div>

                <div className="flex items-center justify-end gap-2 pt-3 border-t border-stone-100">
                  <button
                    type="button"
                    onClick={() => setShowRejectModal(false)}
                    className="rounded-lg border border-stone-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-50 transition"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={isSubmittingReject}
                    className="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-50 transition"
                  >
                    {isSubmittingReject
                      ? "Memproses..."
                      : "Konfirmasi Penolakan"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL: Konfirmasi Penerapan Mutasi (Apply) */}
        {showApplyModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/50 p-4 backdrop-blur-sm">
            <div className="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-xl">
              <div className="flex items-center gap-2 border-b border-stone-100 pb-3">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                  <Play className="h-4 w-4" />
                </div>
                <h3 className="text-sm font-bold text-stone-900">
                  {isBatchApply
                    ? `Terapkan Mutasi ${selectedIds.length} Usulan Terpilih`
                    : `Terapkan Keputusan Mutasi Siswa`}
                </h3>
              </div>

              <div className="mt-4 space-y-3 text-xs text-stone-700 leading-relaxed">
                <p>
                  {isBatchApply
                    ? `Anda akan mengeksekusi mutasi akademik untuk seluruh usulan terpilih berstatus disetujui.`
                    : `Anda akan menerapkan usulan ${applyingItem?.type} untuk siswa ${applyingItem?.student.name}.`}
                </p>
                <div className="rounded-lg bg-stone-50 border border-stone-200 p-3 text-[11px] text-stone-600 space-y-1">
                  <p className="font-semibold text-stone-800">
                    Efek Eksekusi Mutasi:
                  </p>
                  <ul className="list-disc list-inside space-y-0.5">
                    <li>
                      <strong>Kenaikan/Tinggal Kelas:</strong> Membuat data
                      enrollment baru di rombel/tahun tujuan. Enrollment lama
                      tetap dipertahankan sebagai arsip histori.
                    </li>
                    <li>
                      <strong>Kelulusan:</strong> Mengubah status siswa menjadi
                      'graduated' (Lulus).
                    </li>
                    <li>
                      <strong>Atomik & Sekali Pakai:</strong> Eksekusi tidak
                      dapat diulang (apply-once guard).
                    </li>
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
                  onClick={handleSubmitApply}
                  className="rounded-lg bg-teal-700 px-4 py-1.5 font-semibold text-white hover:bg-teal-800 disabled:opacity-50 transition"
                >
                  {isSubmittingApply
                    ? "Menerapkan Mutasi..."
                    : "Eksekusi Penerapan"}
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
