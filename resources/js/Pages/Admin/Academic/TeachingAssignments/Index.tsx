import React, { useState, useMemo } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  BookOpen,
  Calendar,
  Check,
  CheckSquare,
  Clock,
  Filter,
  GraduationCap,
  ListChecks,
  Plus,
  RefreshCw,
  School,
  Search,
  Square,
  Trash2,
  UserCheck,
  Users,
  AlertCircle,
  ArrowRight,
  ShieldAlert,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";

interface Term {
  id: number;
  name: string;
  status: string;
  academic_year?: string;
}

interface Classroom {
  id: number;
  name: string;
  grade_level: number;
  section: string;
}

interface Subject {
  id: number;
  code: string;
  name: string;
  category: string;
}

interface Teacher {
  id: number;
  name: string;
}

interface ClassSubjectItem {
  id: number;
  classroom_id: number;
  classroom: {
    id: number;
    name: string;
    grade_level: number;
    section: string;
  };
  subject_id: number;
  subject: {
    id: number;
    code: string;
    name: string;
    category: string;
  };
  teacher: {
    id: number;
    name: string;
  } | null;
  assignment: {
    id: number;
    starts_on: string;
    status: string;
  } | null;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginatedData<T> {
  data: T[];
  links: PaginationLink[];
  current_page: number;
  last_page: number;
  from: number | null;
  to: number | null;
  total: number;
  per_page: number;
}

interface Props {
  terms: Term[];
  selectedTermId: number | null;
  selectedTerm: Term | null;
  classrooms: Classroom[];
  subjects: Subject[];
  teachers: Teacher[];
  classSubjects: PaginatedData<ClassSubjectItem>;
  filters: {
    term_id: number | null;
    subject_id: string;
    grade_level: string;
    teacher_id: string;
    search?: string;
    per_page?: number;
  };
  stats: {
    total_classrooms: number;
    total_assignments: number;
    with_teacher_count: number;
    vacant_count: number;
  };
}

export default function TeachingAssignmentsIndex({
  terms,
  selectedTermId,
  selectedTerm,
  classrooms,
  subjects,
  teachers,
  classSubjects,
  filters,
  stats,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const canManage =
    auth.user?.roles.some((role) =>
      ["master_admin", "academic_admin"].includes(role),
    ) ?? false;

  // View Mode: 'list' or 'by_subject'
  const [viewMode, setViewMode] = useState<"list" | "by_subject">("list");
  const [searchInput, setSearchInput] = useState(filters.search || "");

  // Modals state
  const [isBulkModalOpen, setIsBulkModalOpen] = useState(false);
  const [singleAssignModal, setSingleAssignModal] =
    useState<ClassSubjectItem | null>(null);
  const [deleteConfirmTarget, setDeleteConfirmTarget] =
    useState<ClassSubjectItem | null>(null);

  // Form for Bulk Assignment (boleh untuk beberapa kelas)
  const bulkForm = useForm<{
    subject_id: string;
    classroom_ids: number[];
    teacher_id: string;
    starts_on: string;
  }>({
    subject_id: "",
    classroom_ids: [],
    teacher_id: "",
    starts_on: new Date().toISOString().split("T")[0],
  });

  // Form for Single Reassignment
  const singleForm = useForm({
    teacher_id: "",
    starts_on: new Date().toISOString().split("T")[0],
  });

  // Group classrooms by grade_level for easier multi-selection in modal
  const classroomsByGrade = useMemo(() => {
    const map = new Map<number, Classroom[]>();
    classrooms.forEach((c) => {
      const list = map.get(c.grade_level) || [];
      list.push(c);
      map.set(c.grade_level, list);
    });
    return Array.from(map.entries()).sort(([a], [b]) => a - b);
  }, [classrooms]);

  // Current paginated items
  const items = classSubjects.data;

  // Format pagination label helper
  const cleanPaginationLabel = (label: string) => {
    if (
      label.includes("previous") ||
      label.includes("&laquo;") ||
      label.includes("«")
    ) {
      return "&laquo; Sebelumnya";
    }
    if (
      label.includes("next") ||
      label.includes("&raquo;") ||
      label.includes("»")
    ) {
      return "Berikutnya &raquo;";
    }
    return label;
  };

  // Grouping by Subject for "by_subject" view
  const groupedBySubject = useMemo(() => {
    const map = new Map<
      number,
      {
        subject: Subject;
        assignments: ClassSubjectItem[];
      }
    >();

    items.forEach((cs) => {
      const entry = map.get(cs.subject_id) || {
        subject: cs.subject,
        assignments: [],
      };
      entry.assignments.push(cs);
      map.set(cs.subject_id, entry);
    });

    return Array.from(map.values()).sort((a, b) =>
      a.subject.name.localeCompare(b.subject.name),
    );
  }, [items]);

  // Filter change handler
  const handleFilterChange = (
    key: "term_id" | "subject_id" | "grade_level" | "teacher_id" | "per_page",
    value: string,
  ) => {
    router.get(
      "/academic/teaching-assignments",
      {
        ...filters,
        [key]: value || undefined,
        page: 1,
      },
      { preserveState: true, preserveScroll: true },
    );
  };

  // Search submit handler
  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/academic/teaching-assignments",
      {
        ...filters,
        search: searchInput.trim() || undefined,
        page: 1,
      },
      { preserveState: true, preserveScroll: true },
    );
  };

  // Clear search
  const handleClearSearch = () => {
    setSearchInput("");
    router.get(
      "/academic/teaching-assignments",
      {
        ...filters,
        search: undefined,
        page: 1,
      },
      { preserveState: true, preserveScroll: true },
    );
  };

  // Toggle single classroom selection in bulk form
  const toggleClassroom = (id: number) => {
    const current = bulkForm.data.classroom_ids;
    if (current.includes(id)) {
      bulkForm.setData(
        "classroom_ids",
        current.filter((item) => item !== id),
      );
    } else {
      bulkForm.setData("classroom_ids", [...current, id]);
    }
  };

  // Select / Deselect all classrooms in a grade
  const toggleGradeClassrooms = (grade: number) => {
    const gradeClassrooms = classrooms.filter((c) => c.grade_level === grade);
    const gradeIds = gradeClassrooms.map((c) => c.id);
    const allSelected = gradeIds.every((id) =>
      bulkForm.data.classroom_ids.includes(id),
    );

    if (allSelected) {
      bulkForm.setData(
        "classroom_ids",
        bulkForm.data.classroom_ids.filter((id) => !gradeIds.includes(id)),
      );
    } else {
      const merged = Array.from(
        new Set([...bulkForm.data.classroom_ids, ...gradeIds]),
      );
      bulkForm.setData("classroom_ids", merged);
    }
  };

  // Select all classrooms across all grades
  const selectAllClassrooms = () => {
    bulkForm.setData(
      "classroom_ids",
      classrooms.map((c) => c.id),
    );
  };

  // Clear classroom selection
  const clearClassroomSelection = () => {
    bulkForm.setData("classroom_ids", []);
  };

  // Submit Bulk Assignment
  const handleBulkSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    bulkForm.post("/academic/teaching-assignments", {
      preserveScroll: true,
      onSuccess: () => {
        setIsBulkModalOpen(false);
        bulkForm.reset("classroom_ids", "subject_id", "teacher_id");
      },
    });
  };

  // Open single assign modal
  const openSingleAssignModal = (cs: ClassSubjectItem) => {
    setSingleAssignModal(cs);
    singleForm.setData({
      teacher_id: cs.teacher ? String(cs.teacher.id) : "",
      starts_on: new Date().toISOString().split("T")[0],
    });
  };

  // Submit Single Assignment (Handover)
  const handleSingleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!singleAssignModal) return;

    singleForm.post(
      `/academic/class-subjects/${singleAssignModal.id}/teachers`,
      {
        preserveScroll: true,
        onSuccess: () => {
          setSingleAssignModal(null);
        },
      },
    );
  };

  // Delete Assignment
  const handleDeleteAssignment = () => {
    if (!deleteConfirmTarget) return;

    router.delete(`/academic/teaching-assignments/${deleteConfirmTarget.id}`, {
      preserveScroll: true,
      onSuccess: () => setDeleteConfirmTarget(null),
    });
  };

  return (
    <AppLayout
      title="Penugasan Mata Pelajaran"
      contextHeader={{
        title: "Penugasan Mata Pelajaran & Guru Pengampu",
        badge: selectedTerm?.name || "Periode Akademik",
        description:
          "Konfigurasi dan penetapan guru pengampu mata pelajaran untuk beberapa rombel kelas sekaligus.",
      }}
      actions={
        canManage && (
          <Button
            onClick={() => setIsBulkModalOpen(true)}
            className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-sm transition"
          >
            <Plus className="w-4 h-4" />
            <span>Buat Penugasan Mapel</span>
          </Button>
        )
      }
    >
      <div className="space-y-6">
        {/* Top Stat Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="p-4 rounded-xl border border-border bg-card shadow-xs">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-lg bg-primary/10 text-primary">
                <School className="w-5 h-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">
                  Rombongan Kelas
                </p>
                <p className="text-xl font-bold tracking-tight text-foreground">
                  {stats.total_classrooms}
                </p>
              </div>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-border bg-card shadow-xs">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-lg bg-blue-500/10 text-blue-600">
                <BookOpen className="w-5 h-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">
                  Total Mapel di Kelas
                </p>
                <p className="text-xl font-bold tracking-tight text-foreground">
                  {stats.total_assignments}
                </p>
              </div>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-border bg-card shadow-xs">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-600">
                <UserCheck className="w-5 h-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">
                  Sudah Ada Pengampu
                </p>
                <p className="text-xl font-bold tracking-tight text-emerald-600">
                  {stats.with_teacher_count}
                </p>
              </div>
            </div>
          </div>

          <div className="p-4 rounded-xl border border-border bg-card shadow-xs">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-lg bg-amber-500/10 text-amber-600">
                <AlertCircle className="w-5 h-5" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground font-medium">
                  Belum Ada Pengampu
                </p>
                <p className="text-xl font-bold tracking-tight text-amber-600">
                  {stats.vacant_count}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Filter and Control Bar */}
        <div className="p-4 rounded-xl border border-border bg-card space-y-4 shadow-xs">
          <div className="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
            {/* Left Filter Controls */}
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 flex-1">
              {/* Semester Selector */}
              <div>
                <label className="block text-xs font-semibold text-muted-foreground mb-1">
                  Periode / Semester
                </label>
                <select
                  value={selectedTermId || ""}
                  onChange={(e) =>
                    handleFilterChange("term_id", e.target.value)
                  }
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  {terms.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name} {t.status === "active" ? "★ (Aktif)" : ""}
                    </option>
                  ))}
                </select>
              </div>

              {/* Grade Level Selector */}
              <div>
                <label className="block text-xs font-semibold text-muted-foreground mb-1">
                  Tingkat Kelas
                </label>
                <select
                  value={filters.grade_level}
                  onChange={(e) =>
                    handleFilterChange("grade_level", e.target.value)
                  }
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Semua Tingkat</option>
                  {[1, 2, 3, 4, 5, 6].map((g) => (
                    <option key={g} value={g}>
                      Kelas Tingkat {g}
                    </option>
                  ))}
                </select>
              </div>

              {/* Subject Selector */}
              <div>
                <label className="block text-xs font-semibold text-muted-foreground mb-1">
                  Mata Pelajaran
                </label>
                <select
                  value={filters.subject_id}
                  onChange={(e) =>
                    handleFilterChange("subject_id", e.target.value)
                  }
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Semua Mata Pelajaran</option>
                  {subjects.map((s) => (
                    <option key={s.id} value={s.id}>
                      [{s.code}] {s.name}
                    </option>
                  ))}
                </select>
              </div>

              {/* Teacher Selector */}
              <div>
                <label className="block text-xs font-semibold text-muted-foreground mb-1">
                  Guru Pengampu
                </label>
                <select
                  value={filters.teacher_id}
                  onChange={(e) =>
                    handleFilterChange("teacher_id", e.target.value)
                  }
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Semua Guru</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Right: Server Search and Per-Page Selector */}
            <div className="flex flex-wrap items-center gap-2 pt-2 lg:pt-5">
              <form
                onSubmit={handleSearchSubmit}
                className="relative flex-1 sm:w-64"
              >
                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  type="text"
                  placeholder="Cari mapel, kelas, guru..."
                  value={searchInput}
                  onChange={(e) => setSearchInput(e.target.value)}
                  className="pl-8 pr-14 h-9 text-xs"
                />
                {searchInput ? (
                  <button
                    type="button"
                    onClick={handleClearSearch}
                    className="absolute right-7 top-2 text-[11px] text-muted-foreground hover:text-foreground p-0.5"
                  >
                    ✕
                  </button>
                ) : null}
                <button
                  type="submit"
                  className="absolute right-1.5 top-1.5 px-1.5 py-0.5 rounded bg-muted text-[10px] font-semibold text-foreground hover:bg-muted/80"
                >
                  Cari
                </button>
              </form>

              {/* Per-Page Selector */}
              <select
                value={filters.per_page || 20}
                onChange={(e) => handleFilterChange("per_page", e.target.value)}
                className="text-xs h-9 rounded-lg border border-input bg-background px-2.5 focus:outline-none focus:ring-1 focus:ring-primary"
                title="Jumlah data per halaman"
              >
                <option value="15">15 / hal</option>
                <option value="20">20 / hal</option>
                <option value="50">50 / hal</option>
                <option value="100">100 / hal</option>
              </select>

              {/* View Switcher */}
              <div className="flex items-center border border-input rounded-lg p-0.5 bg-muted/40">
                <button
                  type="button"
                  onClick={() => setViewMode("list")}
                  className={`px-3 py-1.5 text-xs font-medium rounded-md transition ${
                    viewMode === "list"
                      ? "bg-background text-foreground shadow-xs"
                      : "text-muted-foreground hover:text-foreground"
                  }`}
                >
                  Tabel Rombel
                </button>
                <button
                  type="button"
                  onClick={() => setViewMode("by_subject")}
                  className={`px-3 py-1.5 text-xs font-medium rounded-md transition ${
                    viewMode === "by_subject"
                      ? "bg-background text-foreground shadow-xs"
                      : "text-muted-foreground hover:text-foreground"
                  }`}
                >
                  Per Mapel
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Content Section */}
        {items.length === 0 ? (
          <div className="text-center py-16 px-4 border border-dashed border-border rounded-xl bg-card">
            <BookOpen className="w-12 h-12 mx-auto text-muted-foreground/40 mb-3" />
            <h3 className="text-base font-semibold text-foreground">
              Belum Ada Penugasan Mata Pelajaran
            </h3>
            <p className="text-xs text-muted-foreground max-w-sm mx-auto mt-1 mb-4">
              {filters.subject_id ||
              filters.grade_level ||
              filters.teacher_id ||
              filters.search
                ? "Tidak ada penugasan yang sesuai dengan filter atau pencarian Anda. Coba atur ulang filter pencarian Anda."
                : "Rombongan kelas di semester ini belum memiliki penugasan mata pelajaran. Klik tombol di bawah untuk membuat penugasan ke beberapa kelas sekaligus."}
            </p>
            {canManage && (
              <Button
                onClick={() => setIsBulkModalOpen(true)}
                className="bg-primary text-primary-foreground text-xs"
              >
                <Plus className="w-4 h-4 mr-1.5" />
                Tugaskan Mapel ke Kelas
              </Button>
            )}
          </div>
        ) : viewMode === "list" ? (
          /* View 1: Flat Comprehensive Paginated Table */
          <div className="border border-border rounded-xl bg-card overflow-hidden shadow-xs">
            <div className="overflow-x-auto">
              <table className="w-full text-xs text-left">
                <thead className="bg-muted/50 border-b border-border text-muted-foreground font-semibold uppercase tracking-wider text-[11px]">
                  <tr>
                    <th className="px-4 py-3.5">Rombel Kelas</th>
                    <th className="px-4 py-3.5">Mata Pelajaran</th>
                    <th className="px-4 py-3.5">Kategori</th>
                    <th className="px-4 py-3.5">Guru Pengampu</th>
                    <th className="px-4 py-3.5">TMT Mulai</th>
                    <th className="px-4 py-3.5">Status</th>
                    {canManage && (
                      <th className="px-4 py-3.5 text-right">Aksi</th>
                    )}
                  </tr>
                </thead>
                <tbody className="divide-y divide-border font-medium">
                  {items.map((cs) => (
                    <tr
                      key={cs.id}
                      className="hover:bg-muted/30 transition-colors"
                    >
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-foreground">
                            {cs.classroom.name}
                          </span>
                          <span className="text-[10px] text-muted-foreground px-1.5 py-0.5 rounded bg-muted">
                            Tingkat {cs.classroom.grade_level}
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <div>
                          <span className="font-semibold text-foreground">
                            {cs.subject.name}
                          </span>
                          <span className="text-muted-foreground ml-1.5 font-mono text-[11px]">
                            ({cs.subject.code})
                          </span>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <Badge
                          variant="secondary"
                          className="capitalize text-[11px] font-normal"
                        >
                          {cs.subject.category.replace("_", " ")}
                        </Badge>
                      </td>
                      <td className="px-4 py-3">
                        {cs.teacher ? (
                          <div className="flex items-center gap-1.5 text-foreground font-medium">
                            <UserCheck className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                            <span>{cs.teacher.name}</span>
                          </div>
                        ) : (
                          <span className="text-amber-600 italic flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                            Belum Ada Pengampu
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-muted-foreground font-mono text-[11px]">
                        {cs.assignment?.starts_on || "-"}
                      </td>
                      <td className="px-4 py-3">
                        {cs.teacher ? (
                          <Badge className="bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-none font-semibold text-[10px]">
                            Aktif Mengajar
                          </Badge>
                        ) : (
                          <Badge className="bg-amber-500/15 text-amber-700 dark:text-amber-400 border-none font-semibold text-[10px]">
                            Perlu Guru
                          </Badge>
                        )}
                      </td>
                      {canManage && (
                        <td className="px-4 py-3 text-right">
                          <div className="flex items-center justify-end gap-1.5">
                            <Button
                              variant="outline"
                              size="sm"
                              onClick={() => openSingleAssignModal(cs)}
                              className="h-7 text-xs px-2.5 text-primary hover:bg-primary/10"
                            >
                              {cs.teacher ? "Ganti Guru" : "Tugaskan Guru"}
                            </Button>
                            <Link
                              href={`/academic/classrooms/${cs.classroom_id}/subjects`}
                              className="text-xs text-muted-foreground hover:text-foreground p-1.5 rounded hover:bg-muted"
                              title="Buka Jadwal & Detail Rombel"
                            >
                              <ArrowRight className="w-3.5 h-3.5" />
                            </Link>
                            <button
                              type="button"
                              onClick={() => setDeleteConfirmTarget(cs)}
                              className="text-red-500 hover:text-red-700 p-1.5 rounded hover:bg-red-50 transition"
                              title="Hapus Penugasan Mapel"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </button>
                          </div>
                        </td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Pagination Controls */}
            {classSubjects.links && classSubjects.links.length > 3 ? (
              <div className="p-3.5 bg-muted/20 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span className="text-muted-foreground">
                  Menampilkan{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.from ?? 0}
                  </strong>{" "}
                  sampai{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.to ?? 0}
                  </strong>{" "}
                  dari{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.total}
                  </strong>{" "}
                  penugasan mapel
                </span>
                <div className="flex items-center gap-1 flex-wrap">
                  {classSubjects.links.map((link, idx) => (
                    <button
                      key={idx}
                      disabled={!link.url || link.active}
                      onClick={() =>
                        link.url &&
                        router.get(
                          link.url,
                          {},
                          { preserveState: true, preserveScroll: true },
                        )
                      }
                      dangerouslySetInnerHTML={{
                        __html: cleanPaginationLabel(link.label),
                      }}
                      className={`px-2.5 py-1 text-xs rounded-md border transition-colors ${
                        link.active
                          ? "bg-primary text-primary-foreground border-primary font-bold shadow-2xs"
                          : link.url
                            ? "bg-background border-border text-foreground hover:bg-muted font-medium"
                            : "bg-muted/40 text-muted-foreground/40 border-transparent cursor-not-allowed"
                      }`}
                    />
                  ))}
                </div>
              </div>
            ) : (
              <div className="p-3 bg-muted/20 border-t border-border flex items-center justify-between text-xs text-muted-foreground">
                <span>Menampilkan {items.length} penugasan mata pelajaran</span>
                <span>
                  {stats.with_teacher_count} aktif · {stats.vacant_count} belum
                  ditugaskan
                </span>
              </div>
            )}
          </div>
        ) : (
          /* View 2: Grouped by Subject */
          <div className="space-y-4">
            {groupedBySubject.map(({ subject, assignments }) => (
              <div
                key={subject.id}
                className="border border-border rounded-xl bg-card overflow-hidden shadow-xs"
              >
                <div className="px-4 py-3 bg-muted/30 border-b border-border flex items-center justify-between">
                  <div className="flex items-center gap-2.5">
                    <div className="p-1.5 rounded bg-primary/10 text-primary">
                      <BookOpen className="w-4 h-4" />
                    </div>
                    <div>
                      <h4 className="text-sm font-bold text-foreground">
                        {subject.name}
                        <span className="text-xs text-muted-foreground font-mono ml-2 font-normal">
                          ({subject.code})
                        </span>
                      </h4>
                      <p className="text-[11px] text-muted-foreground capitalize">
                        Kategori: {subject.category.replace("_", " ")} ·
                        Ditugaskan pada {assignments.length} kelas rombel
                        (halaman ini)
                      </p>
                    </div>
                  </div>
                  <Badge variant="outline" className="text-xs font-semibold">
                    {assignments.filter((a) => a.teacher !== null).length} /{" "}
                    {assignments.length} Terisi
                  </Badge>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4">
                  {assignments.map((cs) => (
                    <div
                      key={cs.id}
                      className="p-3 rounded-lg border border-border bg-background flex flex-col justify-between hover:border-primary/50 transition-colors"
                    >
                      <div>
                        <div className="flex items-center justify-between mb-2">
                          <span className="font-bold text-xs text-foreground">
                            {cs.classroom.name}
                          </span>
                          <span className="text-[10px] text-muted-foreground">
                            Tingkat {cs.classroom.grade_level}
                          </span>
                        </div>

                        <div className="mb-3">
                          <p className="text-[11px] text-muted-foreground">
                            Guru Pengampu:
                          </p>
                          {cs.teacher ? (
                            <p className="text-xs font-semibold text-foreground flex items-center gap-1 mt-0.5">
                              <UserCheck className="w-3.5 h-3.5 text-emerald-600 inline shrink-0" />
                              <span className="truncate">
                                {cs.teacher.name}
                              </span>
                            </p>
                          ) : (
                            <p className="text-xs font-medium text-amber-600 flex items-center gap-1 mt-0.5">
                              <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                              Belum Ada Guru
                            </p>
                          )}
                        </div>
                      </div>

                      {canManage && (
                        <div className="flex items-center justify-between pt-2 border-t border-border/60 text-xs">
                          <button
                            type="button"
                            onClick={() => openSingleAssignModal(cs)}
                            className="text-primary hover:underline font-medium text-[11px]"
                          >
                            {cs.teacher ? "Ganti Guru" : "Tugaskan Guru"}
                          </button>
                          <button
                            type="button"
                            onClick={() => setDeleteConfirmTarget(cs)}
                            className="text-red-500 hover:text-red-700 p-1"
                            title="Hapus penugasan"
                          >
                            <Trash2 className="w-3 h-3" />
                          </button>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            ))}

            {/* Pagination Controls in Grouped View */}
            {classSubjects.links && classSubjects.links.length > 3 && (
              <div className="p-3.5 bg-card border border-border rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span className="text-muted-foreground">
                  Menampilkan{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.from ?? 0}
                  </strong>{" "}
                  sampai{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.to ?? 0}
                  </strong>{" "}
                  dari{" "}
                  <strong className="text-foreground font-semibold">
                    {classSubjects.total}
                  </strong>{" "}
                  penugasan mapel
                </span>
                <div className="flex items-center gap-1 flex-wrap">
                  {classSubjects.links.map((link, idx) => (
                    <button
                      key={idx}
                      disabled={!link.url || link.active}
                      onClick={() =>
                        link.url &&
                        router.get(
                          link.url,
                          {},
                          { preserveState: true, preserveScroll: true },
                        )
                      }
                      dangerouslySetInnerHTML={{
                        __html: cleanPaginationLabel(link.label),
                      }}
                      className={`px-2.5 py-1 text-xs rounded-md border transition-colors ${
                        link.active
                          ? "bg-primary text-primary-foreground border-primary font-bold shadow-2xs"
                          : link.url
                            ? "bg-background border-border text-foreground hover:bg-muted font-medium"
                            : "bg-muted/40 text-muted-foreground/40 border-transparent cursor-not-allowed"
                      }`}
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </div>

      {/* ========================================================================= */}
      {/* MODAL 1: BULK MULTI-CLASSROOM SUBJECT ASSIGNMENT (Boleh beberapa kelas) */}
      {/* ========================================================================= */}
      {isBulkModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            {/* Header */}
            <div className="px-6 py-4 border-b border-border bg-muted/30 flex items-center justify-between">
              <div>
                <h3 className="text-base font-bold text-foreground">
                  Buat Penugasan Mata Pelajaran Lintas Rombel
                </h3>
                <p className="text-xs text-muted-foreground mt-0.5">
                  Tugaskan satu mata pelajaran ke beberapa kelas rombel
                  sekaligus beserta guru pengampunya.
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsBulkModalOpen(false)}
                className="text-muted-foreground hover:text-foreground text-sm font-bold p-1 rounded"
              >
                ✕
              </button>
            </div>

            {/* Form */}
            <form
              onSubmit={handleBulkSubmit}
              className="flex-1 overflow-y-auto p-6 space-y-5"
            >
              {/* Subject Selection */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1.5">
                  Mata Pelajaran <span className="text-red-500">*</span>
                </label>
                <select
                  value={bulkForm.data.subject_id}
                  onChange={(e) =>
                    bulkForm.setData("subject_id", e.target.value)
                  }
                  required
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">-- Pilih Mata Pelajaran --</option>
                  {subjects.map((s) => (
                    <option key={s.id} value={s.id}>
                      [{s.code}] {s.name} ({s.category.replace("_", " ")})
                    </option>
                  ))}
                </select>
                {bulkForm.errors.subject_id && (
                  <p className="text-red-500 text-xs mt-1">
                    {bulkForm.errors.subject_id}
                  </p>
                )}
              </div>

              {/* Teacher Selection (Optional) */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1.5">
                  Guru Pengampu (Opsional)
                </label>
                <select
                  value={bulkForm.data.teacher_id}
                  onChange={(e) =>
                    bulkForm.setData("teacher_id", e.target.value)
                  }
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">
                    -- Kosongkan Jika Belum Ditentukan --
                  </option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name}
                    </option>
                  ))}
                </select>
                <p className="text-[11px] text-muted-foreground mt-1">
                  Guru yang dipilih akan otomatis ditugaskan ke seluruh kelas
                  yang dicentang di bawah. Jika kelas sudah memiliki guru lain,
                  sistem akan melakukan pergantian penugasan (handover).
                </p>
                {bulkForm.errors.teacher_id && (
                  <p className="text-red-500 text-xs mt-1">
                    {bulkForm.errors.teacher_id}
                  </p>
                )}
              </div>

              {/* TMT Date */}
              {bulkForm.data.teacher_id && (
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1.5">
                    Tanggal Mulai Berlaku (TMT)
                  </label>
                  <Input
                    type="date"
                    value={bulkForm.data.starts_on}
                    onChange={(e) =>
                      bulkForm.setData("starts_on", e.target.value)
                    }
                    className="h-9 text-xs"
                  />
                </div>
              )}

              {/* Multi-Classroom Selection */}
              <div>
                <div className="flex items-center justify-between mb-2">
                  <label className="text-xs font-semibold text-foreground">
                    Pilih Rombongan Kelas Sasaran{" "}
                    <span className="text-red-500">*</span>
                  </label>
                  <div className="flex items-center gap-2">
                    <button
                      type="button"
                      onClick={selectAllClassrooms}
                      className="text-[11px] text-primary hover:underline font-medium"
                    >
                      Pilih Semua ({classrooms.length})
                    </button>
                    <span className="text-muted-foreground text-[11px]">·</span>
                    <button
                      type="button"
                      onClick={clearClassroomSelection}
                      className="text-[11px] text-muted-foreground hover:text-foreground font-medium"
                    >
                      Batalkan Pilihan
                    </button>
                  </div>
                </div>

                {classrooms.length === 0 ? (
                  <p className="text-xs text-amber-600 p-3 rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-950/20">
                    Tidak ada rombongan kelas aktif di periode semester yang
                    dipilih.
                  </p>
                ) : (
                  <div className="space-y-3 max-h-56 overflow-y-auto p-3 rounded-lg border border-border bg-muted/20">
                    {classroomsByGrade.map(([grade, gradeClassrooms]) => {
                      const allGradeSelected = gradeClassrooms.every((c) =>
                        bulkForm.data.classroom_ids.includes(c.id),
                      );

                      return (
                        <div key={grade} className="space-y-1.5">
                          <div className="flex items-center justify-between pb-1 border-b border-border/40">
                            <span className="text-[11px] font-bold text-muted-foreground uppercase tracking-wide">
                              Tingkat {grade}
                            </span>
                            <button
                              type="button"
                              onClick={() => toggleGradeClassrooms(grade)}
                              className="text-[10px] text-primary hover:underline font-semibold"
                            >
                              {allGradeSelected
                                ? "Batal Tingkat " + grade
                                : "Pilih Semua Tingkat " + grade}
                            </button>
                          </div>

                          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            {gradeClassrooms.map((c) => {
                              const isSelected =
                                bulkForm.data.classroom_ids.includes(c.id);

                              return (
                                <button
                                  key={c.id}
                                  type="button"
                                  onClick={() => toggleClassroom(c.id)}
                                  className={`flex items-center gap-2 p-2 rounded-md border text-xs text-left transition ${
                                    isSelected
                                      ? "border-primary bg-primary/10 text-primary font-bold shadow-2xs"
                                      : "border-border bg-background text-foreground hover:border-muted-foreground/40 font-medium"
                                  }`}
                                >
                                  {isSelected ? (
                                    <CheckSquare className="w-4 h-4 text-primary shrink-0" />
                                  ) : (
                                    <Square className="w-4 h-4 text-muted-foreground shrink-0" />
                                  )}
                                  <span className="truncate">{c.name}</span>
                                </button>
                              );
                            })}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}

                <div className="flex items-center justify-between text-xs mt-2 px-1">
                  <span className="text-muted-foreground">
                    Terpilih:{" "}
                    <strong className="text-foreground">
                      {bulkForm.data.classroom_ids.length}
                    </strong>{" "}
                    dari {classrooms.length} kelas rombel
                  </span>
                  {bulkForm.errors.classroom_ids && (
                    <span className="text-red-500 font-medium">
                      Pilih minimal satu kelas rombel
                    </span>
                  )}
                </div>
              </div>

              {/* Footer */}
              <div className="pt-4 border-t border-border flex items-center justify-end gap-2.5">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setIsBulkModalOpen(false)}
                  className="text-xs"
                >
                  Batal
                </Button>
                <Button
                  type="submit"
                  disabled={
                    bulkForm.processing ||
                    !bulkForm.data.subject_id ||
                    bulkForm.data.classroom_ids.length === 0
                  }
                  className="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold"
                >
                  {bulkForm.processing
                    ? "Menyimpan..."
                    : `Simpan Penugasan (${bulkForm.data.classroom_ids.length} Kelas)`}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 2: GANTI / TUGASKAN GURU UNTUK SATU KELAS TERTENTU */}
      {/* ========================================================================= */}
      {singleAssignModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl shadow-xl max-w-md w-full p-6 animate-in fade-in zoom-in-95 duration-150">
            <h3 className="text-base font-bold text-foreground">
              Tugaskan Guru Pengampu
            </h3>
            <p className="text-xs text-muted-foreground mt-1 mb-4">
              Mata Pelajaran:{" "}
              <strong className="text-foreground">
                {singleAssignModal.subject.name}
              </strong>{" "}
              pada kelas{" "}
              <strong className="text-foreground">
                {singleAssignModal.classroom.name}
              </strong>
            </p>

            <form onSubmit={handleSingleSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Pilih Guru Pengampu <span className="text-red-500">*</span>
                </label>
                <select
                  value={singleForm.data.teacher_id}
                  onChange={(e) =>
                    singleForm.setData("teacher_id", e.target.value)
                  }
                  required
                  className="w-full text-xs font-medium h-9 rounded-lg border border-input bg-background px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">-- Pilih Guru --</option>
                  {teachers.map((t) => (
                    <option key={t.id} value={t.id}>
                      {t.name}
                    </option>
                  ))}
                </select>
                {singleForm.errors.teacher_id && (
                  <p className="text-red-500 text-xs mt-1">
                    {singleForm.errors.teacher_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Tanggal Mulai Mengampu (TMT)
                </label>
                <Input
                  type="date"
                  value={singleForm.data.starts_on}
                  onChange={(e) =>
                    singleForm.setData("starts_on", e.target.value)
                  }
                  required
                  className="h-9 text-xs"
                />
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setSingleAssignModal(null)}
                  className="text-xs"
                >
                  Batal
                </Button>
                <Button
                  type="submit"
                  disabled={
                    singleForm.processing || !singleForm.data.teacher_id
                  }
                  className="text-xs bg-primary text-primary-foreground font-semibold"
                >
                  {singleForm.processing ? "Menyimpan..." : "Simpan Penugasan"}
                </Button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ========================================================================= */}
      {/* MODAL 3: KONFIRMASI HAPUS PENUGASAN MAPEL */}
      {/* ========================================================================= */}
      {deleteConfirmTarget && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-xl shadow-xl max-w-sm w-full p-6 animate-in fade-in zoom-in-95 duration-150">
            <div className="w-10 h-10 rounded-full bg-red-100 dark:bg-red-950/40 text-red-600 flex items-center justify-center mb-3">
              <ShieldAlert className="w-5 h-5" />
            </div>
            <h3 className="text-sm font-bold text-foreground">
              Hapus Penugasan Mata Pelajaran?
            </h3>
            <p className="text-xs text-muted-foreground mt-1 mb-4 leading-relaxed">
              Anda akan menghapus mata pelajaran{" "}
              <strong className="text-foreground">
                {deleteConfirmTarget.subject.name}
              </strong>{" "}
              dari rombel{" "}
              <strong className="text-foreground">
                {deleteConfirmTarget.classroom.name}
              </strong>
              . Tindakan ini hanya dapat dilakukan jika belum ada nilai asesmen
              maupun presensi pertemuan yang tercatat.
            </p>

            <div className="flex items-center justify-end gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setDeleteConfirmTarget(null)}
                className="text-xs"
              >
                Batal
              </Button>
              <Button
                variant="destructive"
                size="sm"
                onClick={handleDeleteAssignment}
                className="text-xs"
              >
                Hapus Penugasan
              </Button>
            </div>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
