import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  BookOpen,
  FileText,
  Layers,
  ListChecks,
  Plus,
  School,
  X,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface Term {
  id: number;
  semester: number;
  status: string;
  full_name: string;
  academic_year: {
    id: number;
    name: string;
  };
}

interface Classroom {
  id: number;
  term_id: number;
  grade_level: number;
  section: string;
  roster_version: number;
  full_name: string;
  term: Term;
  active_homeroom?: {
    teacher_id: number;
    teacher?: {
      id: number;
      name: string;
    };
  } | null;
}

interface Props {
  classrooms: Classroom[];
  terms: Term[];
  selectedTermId: number | null;
  activeTerm: Term | null;
  canCreate?: boolean;
}

export default function ClassroomsIndex({
  classrooms,
  terms,
  selectedTermId,
  activeTerm,
  canCreate = false,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const user = auth?.user;
  const roles = user?.roles || [];
  const isMaster = roles.includes("master_admin");
  const isAcademic = roles.includes("academic_admin");

  const [showModal, setShowModal] = useState(false);

  const form = useForm({
    term_id: selectedTermId?.toString() || activeTerm?.id?.toString() || "",
    grade_level: "1",
    section: "",
  });

  const handleTermFilter = (termId: string) => {
    router.get(
      "/academic/classrooms",
      { term_id: termId || undefined },
      { preserveState: true },
    );
  };

  const handleCreate = (e: React.FormEvent) => {
    e.preventDefault();
    form.post("/academic/classrooms", {
      onSuccess: () => {
        form.reset("section");
        setShowModal(false);
      },
    });
  };

  return (
    <AppLayout
      title="Rombongan Belajar (Kelas) — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-medium"
            >
              Struktur Akademik
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              {activeTerm?.full_name || "Semester Aktif"}
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Rombongan Belajar (Kelas)
          </h1>
        </div>
      }
      actions={
        canCreate || isMaster || isAcademic ? (
          <div className="flex items-center gap-2">
            {(isMaster || isAcademic) && (
              <Link href="/academic/teaching-assignments">
                <Button variant="outline" size="sm" className="gap-1.5 text-xs">
                  <ListChecks className="size-4" />
                  <span>Penugasan Mapel</span>
                </Button>
              </Link>
            )}
            {canCreate && (
              <Button
                onClick={() => setShowModal(true)}
                size="sm"
                className="gap-1.5 text-xs"
              >
                <Plus className="size-4" />
                <span>Tambah Rombel</span>
              </Button>
            )}
          </div>
        ) : undefined
      }
    >
      <div className="space-y-6">
        {/* Filter Term Toolbar */}
        <div className="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-border bg-white p-4 shadow-2xs">
          <div className="flex items-center gap-3">
            <span className="text-xs font-semibold text-foreground">
              Filter Semester:
            </span>
            <NativeSelect
              value={selectedTermId || ""}
              onChange={(e) => handleTermFilter(e.target.value)}
              className="text-xs"
            >
              <option value="">Semua Semester</option>
              {terms.map((t) => (
                <option key={t.id} value={t.id}>
                  {t.full_name} {t.status === "active" ? "(Aktif)" : ""}
                </option>
              ))}
            </NativeSelect>
          </div>

          <span className="text-xs text-muted-foreground">
            Total {classrooms.length} rombel terdaftar
          </span>
        </div>

        {/* Classrooms Grid */}
        {classrooms.length === 0 ? (
          <div className="rounded-lg border border-border bg-white p-12 text-center shadow-2xs">
            <div className="mx-auto size-12 rounded-full bg-secondary text-primary flex items-center justify-center mb-3">
              <School className="size-6" />
            </div>
            <h3 className="text-sm font-semibold text-foreground">
              Belum ada rombel di semester ini
            </h3>
            <p className="mt-1 text-xs text-muted-foreground max-w-sm mx-auto">
              {canCreate
                ? 'Klik "Tambah Rombel" untuk membuat kelas baru pada semester terpilih.'
                : "Tidak ada data rombongan belajar yang terhubung dengan akun Anda pada semester ini."}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {classrooms.map((c) => (
              <div
                key={c.id}
                className="group rounded-lg border border-border bg-white p-5 shadow-2xs hover:border-primary/40 transition-colors flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-3">
                    <span className="inline-flex items-center justify-center size-8 rounded-md bg-secondary text-primary font-bold text-sm">
                      {c.grade_level}
                    </span>
                    <Badge variant="outline" className="text-[10px] font-mono">
                      Roster v{c.roster_version}
                    </Badge>
                  </div>
                  <h4 className="text-base font-bold text-foreground">
                    {c.full_name}
                  </h4>
                  <p className="text-xs text-muted-foreground mt-0.5">
                    {c.term?.full_name || "Periode Aktif"}
                  </p>
                  {c.active_homeroom?.teacher && (
                    <p className="text-[11px] text-muted-foreground mt-1">
                      Wali Kelas:{" "}
                      <span className="text-foreground font-medium">
                        {c.active_homeroom.teacher.name}
                      </span>
                    </p>
                  )}
                </div>

                <div className="mt-5 flex items-center gap-2 border-t border-border pt-3">
                  <Link
                    href={`/academic/classrooms/${c.id}/roster`}
                    className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md bg-secondary px-3 py-1.5 text-xs font-semibold text-secondary-foreground hover:bg-secondary/80 transition-colors"
                  >
                    <Layers className="size-3.5" />
                    <span>Roster</span>
                  </Link>
                  <Link
                    href={`/academic/classrooms/${c.id}/subjects`}
                    className="inline-flex flex-1 items-center justify-center gap-1.5 rounded-md border border-border bg-white px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted transition-colors"
                  >
                    <BookOpen className="size-3.5" />
                    <span>Mapel</span>
                  </Link>
                  {(isMaster ||
                    isAcademic ||
                    c.active_homeroom?.teacher_id === user?.id) && (
                    <Link
                      href={`/academic/classrooms/${c.id}/report-preview`}
                      className="inline-flex items-center justify-center gap-1.5 rounded-md border border-primary/30 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/10 transition-colors"
                      title="Evaluasi & Preview Rapor Kelas"
                    >
                      <FileText className="size-3.5" />
                      <span>Rapor</span>
                    </Link>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Modal Tambah Rombel */}
        {showModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div className="w-full max-w-md rounded-lg border border-border bg-white p-6 shadow-xl">
              <div className="flex items-center justify-between pb-4 border-b border-border">
                <h3 className="text-base font-bold text-foreground">
                  Tambah Rombongan Belajar
                </h3>
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="rounded-md p-1 text-muted-foreground hover:text-foreground"
                >
                  <X className="size-4" />
                </button>
              </div>

              <form onSubmit={handleCreate} className="mt-4 space-y-4">
                <div>
                  <Label
                    htmlFor="term_id"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Semester
                  </Label>
                  <NativeSelect
                    id="term_id"
                    value={form.data.term_id}
                    onChange={(e) => form.setData("term_id", e.target.value)}
                    containerClassName="w-full"
                    className="w-full text-xs"
                    required
                  >
                    <option value="">Pilih Semester</option>
                    {terms.map((t) => (
                      <option key={t.id} value={t.id}>
                        {t.full_name} {t.status === "active" ? "(Aktif)" : ""}
                      </option>
                    ))}
                  </NativeSelect>
                  {form.errors.term_id && (
                    <p className="mt-1 text-[11px] text-destructive">
                      {form.errors.term_id}
                    </p>
                  )}
                </div>

                <div>
                  <Label
                    htmlFor="grade_level"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Tingkat Kelas (1-6)
                  </Label>
                  <NativeSelect
                    id="grade_level"
                    value={form.data.grade_level}
                    onChange={(e) =>
                      form.setData("grade_level", e.target.value)
                    }
                    containerClassName="w-full"
                    className="w-full text-xs"
                    required
                  >
                    {[1, 2, 3, 4, 5, 6].map((lvl) => (
                      <option key={lvl} value={lvl}>
                        Kelas {lvl}
                      </option>
                    ))}
                  </NativeSelect>
                </div>

                <div>
                  <Label
                    htmlFor="section"
                    className="text-xs font-semibold text-foreground mb-1 block"
                  >
                    Nama Rombel / Seksi
                  </Label>
                  <Input
                    id="section"
                    type="text"
                    value={form.data.section}
                    onChange={(e) =>
                      form.setData("section", e.target.value.toUpperCase())
                    }
                    placeholder="Contoh: A, B, atau Unggulan"
                    maxLength={10}
                    className="text-xs uppercase"
                    required
                  />
                  {form.errors.section && (
                    <p className="mt-1 text-[11px] text-destructive">
                      {form.errors.section}
                    </p>
                  )}
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-border">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setShowModal(false)}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="default"
                    size="sm"
                    disabled={form.processing}
                  >
                    {form.processing ? "Menyimpan..." : "Simpan Kelas"}
                  </Button>
                </div>
              </form>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
