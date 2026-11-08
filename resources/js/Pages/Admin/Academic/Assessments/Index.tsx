import React, { useState } from "react";
import { Link, useForm } from "@inertiajs/react";
import {
  AlertCircle,
  ArrowLeft,
  Award,
  Calendar,
  CheckCircle2,
  Clock,
  FileSpreadsheet,
  HelpCircle,
  Plus,
  Trash2,
  User,
  X,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface ScoringComponent {
  id: number;
  code: string;
  name: string;
  source_type: string;
}

interface AssessmentItem {
  id: number;
  title: string;
  assessed_on: string;
  status: "draft" | "published";
  version: number;
  scoring_component: {
    id: number;
    code: string;
    name: string;
  };
  creator: {
    id: number;
    name: string;
  };
  published_by: {
    id: number;
    name: string;
  } | null;
  published_at: string | null;
  scores_count: number;
  average_score: string | null;
}

interface Props {
  classSubject: {
    id: number;
    classroom: {
      id: number;
      name: string;
      term: {
        name: string;
        academic_year: {
          name: string;
        };
      };
    };
    subject: {
      id: number;
      code: string;
      name: string;
    };
    teacher: {
      id: number;
      name: string;
    } | null;
    roster_version: number;
  };
  assessments: AssessmentItem[];
  scoringComponents: ScoringComponent[];
  capabilities: {
    can_create: boolean;
    can_publish: boolean;
    is_data_entry: boolean;
  };
}

export default function Index({
  classSubject,
  assessments,
  scoringComponents,
  capabilities,
}: Props) {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const { data, setData, post, processing, errors, reset } = useForm({
    scoring_component_id:
      scoringComponents.length > 0 ? String(scoringComponents[0].id) : "",
    title: "",
    assessed_on: new Date().toISOString().split("T")[0],
  });

  const deleteForm = useForm({});

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(`/academic/class-subjects/${classSubject.id}/assessments`, {
      onSuccess: () => {
        setShowCreateModal(false);
        reset();
      },
    });
  };

  const handleDelete = (id: number) => {
    deleteForm.delete(`/academic/assessments/${id}`, {
      onSuccess: () => setDeletingId(null),
    });
  };

  return (
    <AppLayout
      title={`Aktivitas Penilaian: ${classSubject.subject.name} - ${classSubject.classroom.name}`}
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              href={`/academic/classrooms/${classSubject.classroom.id}/subjects`}
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
              {classSubject.classroom.name}
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              {classSubject.classroom.term.academic_year.name} (
              {classSubject.classroom.term.name})
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Aktivitas Penilaian: {classSubject.subject.name}
          </h1>
        </div>
      }
      actions={
        capabilities.can_create && (
          <Button
            onClick={() => setShowCreateModal(true)}
            size="sm"
            className="gap-1.5"
          >
            <Plus className="size-4" />
            <span>Buat Penilaian</span>
          </Button>
        )
      }
    >
      <div className="space-y-6">
        {/* Context Information Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="rounded-lg border border-border bg-white p-4 shadow-2xs">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              Guru Pengampu
            </span>
            <div className="mt-1 flex items-center gap-2">
              <User className="size-4 text-primary" />
              <p className="font-semibold text-foreground text-sm">
                {classSubject.teacher
                  ? classSubject.teacher.name
                  : "Belum Ditugaskan"}
              </p>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-4 shadow-2xs">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              Total Penilaian
            </span>
            <div className="mt-1 flex items-center gap-2">
              <Award className="size-4 text-primary" />
              <p className="font-semibold text-foreground text-sm">
                {assessments.length} Kegiatan Terdaftar
              </p>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-4 shadow-2xs">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              Status Publikasi
            </span>
            <div className="mt-1 flex items-center gap-2">
              <CheckCircle2 className="size-4 text-emerald-600" />
              <p className="font-semibold text-foreground text-sm">
                {assessments.filter((a) => a.status === "published").length}{" "}
                Diterbitkan /{" "}
                {assessments.filter((a) => a.status === "draft").length} Draf
              </p>
            </div>
          </div>
        </div>

        {/* Notice for Data Entry Admins */}
        {capabilities.is_data_entry && (
          <div className="rounded-lg border border-warning-foreground/30 bg-warning p-4 text-xs text-warning-foreground flex items-start gap-3">
            <HelpCircle className="size-4 text-warning-foreground shrink-0 mt-0.5" />
            <div>
              <p className="font-bold">Akses Delegasi Petugas (Q6.3)</p>
              <p className="mt-0.5 opacity-90">
                Anda memiliki hak input delegasi untuk membuat dan menyimpan
                draf nilai siswa. Publikasi nilai resmi dan penghapusan
                penilaian tetap menjadi wewenang penuh guru pengampu atau
                Master/Akademik.
              </p>
            </div>
          </div>
        )}

        {/* Assessments List Table */}
        <div className="rounded-lg border border-border bg-white shadow-2xs overflow-hidden">
          <div className="border-b border-border bg-muted/40 px-6 py-4 flex items-center justify-between">
            <h2 className="text-sm font-semibold text-foreground flex items-center gap-2">
              <FileSpreadsheet className="size-4 text-primary" />
              <span>Daftar Aktivitas Penilaian</span>
            </h2>
            <span className="text-xs text-muted-foreground font-medium">
              Komponen Kategori Penilaian Sesuai Kebijakan Aktif
            </span>
          </div>

          {assessments.length === 0 ? (
            <div className="py-14 text-center px-4">
              <Award className="mx-auto size-10 text-muted-foreground/40 mb-3" />
              <p className="text-base font-semibold text-foreground">
                Belum Ada Aktivitas Penilaian
              </p>
              <p className="mt-1 text-xs text-muted-foreground max-w-md mx-auto">
                Silakan buat penilaian baru untuk mencatat nilai tugas, kuis,
                atau ulangan siswa pada mata pelajaran ini.
              </p>
              {capabilities.can_create && (
                <Button
                  onClick={() => setShowCreateModal(true)}
                  size="sm"
                  className="mt-4 gap-1.5"
                >
                  <Plus className="size-4" />
                  <span>Buat Penilaian Pertama</span>
                </Button>
              )}
            </div>
          ) : (
            <div className="divide-y divide-border">
              {assessments.map((item) => (
                <div
                  key={item.id}
                  className="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-muted/30 transition-colors"
                >
                  <div className="flex items-start gap-4">
                    <div className="flex size-11 shrink-0 items-center justify-center rounded-md bg-secondary text-secondary-foreground font-bold text-xs uppercase border border-border">
                      {item.scoring_component.code}
                    </div>
                    <div>
                      <div className="flex flex-wrap items-center gap-2">
                        <h3 className="font-bold text-foreground text-sm">
                          {item.title}
                        </h3>
                        {item.status === "published" ? (
                          <Badge
                            variant="success"
                            className="text-xs font-semibold gap-1"
                          >
                            <CheckCircle2 className="size-3" />
                            <span>Diterbitkan</span>
                          </Badge>
                        ) : (
                          <Badge
                            variant="warning"
                            className="text-xs font-semibold gap-1"
                          >
                            <Clock className="size-3" />
                            <span>Draf</span>
                          </Badge>
                        )}
                        <span className="text-xs text-muted-foreground">•</span>
                        <span className="text-xs font-medium text-muted-foreground bg-muted px-2 py-0.5 rounded">
                          {item.scoring_component.name}
                        </span>
                      </div>

                      <div className="mt-1 flex flex-wrap items-center gap-y-1 gap-x-3 text-xs text-muted-foreground">
                        <span className="flex items-center gap-1">
                          <Calendar className="size-3.5" />
                          <span>Tanggal: {item.assessed_on}</span>
                        </span>
                        <span>•</span>
                        <span>
                          Dibuat oleh:{" "}
                          <strong className="text-foreground">
                            {item.creator.name}
                          </strong>
                        </span>
                        {item.published_by && (
                          <>
                            <span>•</span>
                            <span>
                              Diterbitkan oleh:{" "}
                              <strong className="text-foreground">
                                {item.published_by.name}
                              </strong>
                            </span>
                          </>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Right: Stats & Actions */}
                  <div className="flex items-center gap-4 self-end md:self-center">
                    <div className="text-right">
                      <div className="text-xs font-medium text-muted-foreground">
                        {item.scores_count} Siswa Ternilai
                      </div>
                      <div className="text-sm font-bold text-foreground">
                        {item.average_score !== null ? (
                          <span>
                            Rerata:{" "}
                            <strong className="text-primary">
                              {item.average_score}
                            </strong>
                          </span>
                        ) : (
                          <span className="text-muted-foreground italic font-normal text-xs">
                            Belum ada skor
                          </span>
                        )}
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <Link href={`/academic/assessments/${item.id}`}>
                        <Button
                          variant="default"
                          size="sm"
                          className="gap-1.5 text-xs"
                        >
                          <FileSpreadsheet className="size-3.5" />
                          <span>Lembar Nilai</span>
                        </Button>
                      </Link>

                      {item.status === "draft" &&
                        !capabilities.is_data_entry && (
                          <Button
                            variant="outline"
                            size="icon-sm"
                            onClick={() => setDeletingId(item.id)}
                            disabled={deleteForm.processing}
                            className="text-destructive hover:bg-destructive/10 hover:border-destructive/30"
                            title="Hapus Draf Penilaian"
                          >
                            <Trash2 className="size-4" />
                          </Button>
                        )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Modal: Buat Penilaian Baru */}
        {showCreateModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div className="w-full max-w-md rounded-lg border border-border bg-white p-6 shadow-xl">
              <div className="flex items-center justify-between border-b border-border pb-3">
                <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                  <Award className="size-5 text-primary" />
                  <span>Buat Aktivitas Penilaian Baru</span>
                </h3>
                <button
                  type="button"
                  onClick={() => {
                    setShowCreateModal(false);
                    reset();
                  }}
                  className="rounded-md p-1 text-muted-foreground hover:text-foreground"
                >
                  <X className="size-4" />
                </button>
              </div>

              <form onSubmit={handleCreateSubmit} className="mt-4 space-y-4">
                <div>
                  <Label
                    htmlFor="scoring_component_id"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Komponen Penilaian{" "}
                    <span className="text-destructive">*</span>
                  </Label>
                  <NativeSelect
                    id="scoring_component_id"
                    value={data.scoring_component_id}
                    onChange={(e) =>
                      setData("scoring_component_id", e.target.value)
                    }
                    containerClassName="w-full"
                    className="w-full text-xs"
                    required
                  >
                    {scoringComponents.map((comp) => (
                      <option key={comp.id} value={comp.id}>
                        [{comp.code}] {comp.name}
                      </option>
                    ))}
                  </NativeSelect>
                  {errors.scoring_component_id && (
                    <p className="mt-1 text-[11px] text-destructive">
                      {errors.scoring_component_id}
                    </p>
                  )}
                </div>

                <div>
                  <Label
                    htmlFor="title"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Judul Penilaian <span className="text-destructive">*</span>
                  </Label>
                  <Input
                    id="title"
                    type="text"
                    placeholder="Contoh: Ulangan Harian 1, Tugas Akhir Bab 2"
                    value={data.title}
                    onChange={(e) => setData("title", e.target.value)}
                    className="text-xs"
                    required
                  />
                  {errors.title && (
                    <p className="mt-1 text-[11px] text-destructive">
                      {errors.title}
                    </p>
                  )}
                </div>

                <div>
                  <Label
                    htmlFor="assessed_on"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Tanggal Penilaian{" "}
                    <span className="text-destructive">*</span>
                  </Label>
                  <Input
                    id="assessed_on"
                    type="date"
                    value={data.assessed_on}
                    onChange={(e) => setData("assessed_on", e.target.value)}
                    className="text-xs"
                    required
                  />
                  {errors.assessed_on && (
                    <p className="mt-1 text-[11px] text-destructive">
                      {errors.assessed_on}
                    </p>
                  )}
                </div>

                <div className="mt-6 flex items-center justify-end gap-2 pt-3 border-t border-border">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setShowCreateModal(false);
                      reset();
                    }}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="default"
                    size="sm"
                    disabled={processing}
                  >
                    {processing ? "Memproses..." : "Buat Draf Penilaian"}
                  </Button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Modal Konfirmasi Hapus */}
        {deletingId && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div className="w-full max-w-sm rounded-lg border border-border bg-white p-6 shadow-xl text-center">
              <div className="mx-auto flex size-11 items-center justify-center rounded-full bg-destructive/10 text-destructive mb-3">
                <AlertCircle className="size-6" />
              </div>
              <h3 className="text-base font-bold text-foreground">
                Hapus Draf Penilaian?
              </h3>
              <p className="mt-2 text-xs text-muted-foreground">
                Draf penilaian ini beserta seluruh skor yang belum
                dipublikasikan akan dihapus permanen. Tindakan ini tidak dapat
                dibatalkan.
              </p>
              <div className="mt-6 flex items-center justify-center gap-3">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setDeletingId(null)}
                >
                  Batal
                </Button>
                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  onClick={() => handleDelete(deletingId)}
                  disabled={deleteForm.processing}
                >
                  {deleteForm.processing ? "Menghapus..." : "Ya, Hapus Draf"}
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
