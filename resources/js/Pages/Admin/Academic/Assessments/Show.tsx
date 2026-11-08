import React, { useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import {
  AlertCircle,
  ArrowLeft,
  Award,
  Calendar,
  CheckCircle2,
  Clock,
  Download,
  FileSpreadsheet,
  HelpCircle,
  Save,
  Send,
  Upload,
  User,
  XCircle,
  X,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Alert } from "@/Components/ui/alert";

interface StudentRow {
  enrollment_id: number;
  student: {
    id: number;
    nis: string;
    name: string;
    gender: string;
  };
  score: string | null;
  feedback: string | null;
  has_score: boolean;
}

interface Props {
  assessment: {
    id: number;
    title: string;
    assessed_on: string;
    status: "draft" | "published";
    version: number;
    published_at: string | null;
    published_by: {
      id: number;
      name: string;
    } | null;
    creator: {
      id: number;
      name: string;
    };
    scoring_component: {
      id: number;
      code: string;
      name: string;
    };
  };
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
  rows: StudentRow[];
  stats: {
    total_students: number;
    recorded_count: number;
    blank_count: number;
    zero_count: number;
    average_score: string | null;
  };
  capabilities: {
    can_update: boolean;
    can_publish: boolean;
    can_delete: boolean;
    is_data_entry: boolean;
  };
}

export default function Show({
  assessment,
  classSubject,
  rows,
  stats,
  capabilities,
}: Props) {
  const [entries, setEntries] = useState<
    Record<number, { score: string; feedback: string }>
  >(() => {
    const initial: Record<number, { score: string; feedback: string }> = {};
    rows.forEach((r) => {
      initial[r.enrollment_id] = {
        score: r.score ?? "",
        feedback: r.feedback ?? "",
      };
    });
    return initial;
  });

  const [showPublishModal, setShowPublishModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [importing, setImporting] = useState(false);
  const [inputErrors, setInputErrors] = useState<Record<number, string>>({});
  const [savingScores, setSavingScores] = useState(false);
  const [publishing, setPublishing] = useState(false);

  const { errors: pageErrors } = usePage<{ errors: Record<string, string> }>()
    .props;

  const isReadOnly =
    assessment.status === "published" || !capabilities.can_update;

  const handleScoreChange = (enrollmentId: number, rawValue: string) => {
    const normalized = rawValue.replace(",", ".");

    setEntries((prev) => ({
      ...prev,
      [enrollmentId]: {
        ...prev[enrollmentId],
        score: normalized,
      },
    }));

    if (normalized.trim() === "") {
      setInputErrors((prev) => {
        const next = { ...prev };
        delete next[enrollmentId];
        return next;
      });
      return;
    }

    const num = parseFloat(normalized);
    if (isNaN(num) || num < 0 || num > 100) {
      setInputErrors((prev) => ({
        ...prev,
        [enrollmentId]: "Skor harus 0 - 100",
      }));
    } else {
      setInputErrors((prev) => {
        const next = { ...prev };
        delete next[enrollmentId];
        return next;
      });
    }
  };

  const handleFeedbackChange = (enrollmentId: number, feedback: string) => {
    setEntries((prev) => ({
      ...prev,
      [enrollmentId]: {
        ...prev[enrollmentId],
        feedback,
      },
    }));
  };

  const handleClearScore = (enrollmentId: number) => {
    if (capabilities.is_data_entry) return;

    setEntries((prev) => ({
      ...prev,
      [enrollmentId]: {
        ...prev[enrollmentId],
        score: "",
      },
    }));

    setInputErrors((prev) => {
      const next = { ...prev };
      delete next[enrollmentId];
      return next;
    });
  };

  const handleSaveDraft = (e: React.FormEvent) => {
    e.preventDefault();
    setSavingScores(true);

    const formattedEntries = rows.map((r) => {
      const current = entries[r.enrollment_id];
      const rawScore = current?.score.trim();
      return {
        enrollment_id: r.enrollment_id,
        score: rawScore === "" ? null : parseFloat(rawScore),
        feedback: current?.feedback || null,
      };
    });

    router.patch(
      `/academic/assessments/${assessment.id}/scores`,
      {
        expected_version: assessment.version,
        entries: formattedEntries,
      },
      {
        onFinish: () => setSavingScores(false),
      },
    );
  };

  const handlePublishConfirm = () => {
    setPublishing(true);
    router.post(
      `/academic/assessments/${assessment.id}/publish`,
      {
        expected_version: assessment.version,
      },
      {
        onSuccess: () => setShowPublishModal(false),
        onFinish: () => setPublishing(false),
      },
    );
  };

  const handleImportSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!importFile) return;

    setImporting(true);
    const formData = new FormData();
    formData.append("expected_version", assessment.version.toString());
    formData.append("file", importFile);

    router.post(`/academic/assessments/${assessment.id}/import`, formData, {
      forceFormData: true,
      onSuccess: () => {
        setShowImportModal(false);
        setImportFile(null);
      },
      onFinish: () => setImporting(false),
    });
  };

  const currentTotal = rows.length;
  const currentRecorded = rows.filter((r) => {
    const val = entries[r.enrollment_id]?.score.trim();
    return val !== undefined && val !== "";
  }).length;
  const currentBlank = currentTotal - currentRecorded;
  const currentZero = rows.filter((r) => {
    const val = entries[r.enrollment_id]?.score.trim();
    return val !== undefined && parseFloat(val) === 0;
  }).length;

  return (
    <AppLayout
      title={`Lembar Nilai: ${assessment.title} - ${classSubject.classroom.name}`}
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Link
              href={`/academic/class-subjects/${classSubject.id}/assessments`}
              className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-primary transition-colors"
            >
              <ArrowLeft className="size-3.5" />
              <span>Aktivitas Penilaian</span>
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
              {classSubject.subject.name} [{assessment.scoring_component.code}]
            </span>
          </div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl font-bold tracking-tight text-foreground">
              {assessment.title}
            </h1>
            {assessment.status === "published" ? (
              <Badge variant="success" className="gap-1 text-xs">
                <CheckCircle2 className="size-3" />
                <span>Diterbitkan (Terkunci)</span>
              </Badge>
            ) : (
              <Badge variant="warning" className="gap-1 text-xs">
                <Clock className="size-3" />
                <span>Draf Penilaian</span>
              </Badge>
            )}
          </div>
        </div>
      }
    >
      <div className="space-y-6 pb-20">
        {/* Errors Alert */}
        {pageErrors.roster_changed && (
          <Alert variant="destructive">
            <strong>Peringatan Perubahan Rombel:</strong>{" "}
            {pageErrors.roster_changed}
          </Alert>
        )}
        {pageErrors.entries && (
          <Alert variant="destructive">
            <strong>Kesalahan Isian Nilai:</strong> {pageErrors.entries}
          </Alert>
        )}

        {/* Top Summary Cards */}
        <div className="grid grid-cols-2 md:grid-cols-5 gap-3">
          <div className="rounded-lg border border-border bg-white p-3.5 shadow-2xs">
            <span className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
              Total Siswa
            </span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-xl font-bold text-foreground">
                {currentTotal}
              </span>
              <span className="text-[11px] text-muted-foreground">Peserta</span>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-3.5 shadow-2xs">
            <span className="text-[11px] font-bold uppercase tracking-wider text-primary">
              Terisi (Ternilai)
            </span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-xl font-bold text-primary">
                {currentRecorded}
              </span>
              <span className="text-[11px] font-semibold text-primary">
                {currentTotal > 0
                  ? Math.round((currentRecorded / currentTotal) * 100)
                  : 0}
                %
              </span>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-3.5 shadow-2xs">
            <span className="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
              Kosong (Blank)
            </span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-xl font-bold text-foreground">
                {currentBlank}
              </span>
              <span className="text-[11px] text-muted-foreground">
                Belum Nilai
              </span>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-3.5 shadow-2xs">
            <span className="text-[11px] font-bold uppercase tracking-wider text-warning-foreground">
              Skor 0 (Nol)
            </span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-xl font-bold text-warning-foreground">
                {currentZero}
              </span>
              <span className="text-[11px] font-semibold text-warning-foreground">
                Sah
              </span>
            </div>
          </div>

          <div className="rounded-lg border border-border bg-white p-3.5 shadow-2xs col-span-2 md:col-span-1">
            <span className="text-[11px] font-bold uppercase tracking-wider text-primary">
              Rerata Skor
            </span>
            <div className="mt-1 flex items-baseline justify-between">
              <span className="text-xl font-bold text-primary">
                {stats.average_score ?? "-"}
              </span>
              <span className="text-[11px] text-muted-foreground">
                {stats.recorded_count} Ternilai
              </span>
            </div>
          </div>
        </div>

        {/* Rules Information Box */}
        <div className="rounded-lg border border-border bg-muted/30 p-4 text-xs text-foreground flex items-start gap-3">
          <HelpCircle className="size-4 text-primary shrink-0 mt-0.5" />
          <div className="space-y-1">
            <p className="font-semibold text-foreground">
              Panduan Register Nilai (Q7.2 / D-19 / F-06):
            </p>
            <p className="text-muted-foreground">
              • <strong>Nilai Kosong (Blank)</strong>: Dibiarkan kosong jika
              siswa belum dinilai. Nilai kosong tidak otomatis diubah menjadi 0
              dan tidak mendistribusikan ulang bobot komponen lain.
            </p>
            <p className="text-muted-foreground">
              • <strong>Skor 0 (Nol Sah)</strong>: Jika siswa mendapat nilai
              nol, ketik angka{" "}
              <code className="bg-background border border-border px-1 py-0.5 rounded font-mono">
                0
              </code>
              . Skor 0 sah dan masuk perhitungan rata-rata.
            </p>
            <p className="text-muted-foreground">
              • <strong>Publikasi Nilai</strong>: Diizinkan meskipun terdapat
              nilai kosong. Nilai yang telah diterbitkan akan terkunci secara
              atomik.
            </p>
          </div>
        </div>

        {/* Grading Form Table */}
        <form onSubmit={handleSaveDraft}>
          <div className="rounded-lg border border-border bg-white shadow-2xs overflow-hidden">
            <div className="border-b border-border bg-muted/40 px-6 py-3.5 flex items-center justify-between">
              <h2 className="text-sm font-semibold text-foreground flex items-center gap-2">
                <Award className="size-4 text-primary" />
                <span>Lembar Input Nilai Siswa</span>
              </h2>
              <span className="text-xs text-muted-foreground">
                Tanggal Penilaian: <strong>{assessment.assessed_on}</strong>
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm border-collapse">
                <thead>
                  <tr className="border-b border-border bg-muted/20 text-xs font-semibold text-muted-foreground">
                    <th className="py-3 px-4 w-12 text-center">No</th>
                    <th className="py-3 px-4 w-28">NIS</th>
                    <th className="py-3 px-4">Nama Siswa</th>
                    <th className="py-3 px-4 w-16 text-center">L/P</th>
                    <th className="py-3 px-4 w-36 text-center">Skor (0-100)</th>
                    <th className="py-3 px-4 w-64">Catatan Guru (Opsional)</th>
                    <th className="py-3 px-4 w-32 text-center">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {rows.map((row, index) => {
                    const currentEntry = entries[row.enrollment_id] || {
                      score: "",
                      feedback: "",
                    };
                    const hasError = inputErrors[row.enrollment_id];
                    const isBlank = currentEntry.score.trim() === "";
                    const isZero =
                      !isBlank && parseFloat(currentEntry.score) === 0;

                    return (
                      <tr
                        key={row.enrollment_id}
                        className={`hover:bg-muted/30 transition-colors ${
                          !isBlank ? "bg-muted/15" : ""
                        }`}
                      >
                        <td className="py-3 px-4 text-center text-xs font-medium text-muted-foreground">
                          {index + 1}
                        </td>
                        <td className="py-3 px-4 font-mono text-xs font-medium text-muted-foreground">
                          {row.student.nis}
                        </td>
                        <td className="py-3 px-4 font-semibold text-foreground">
                          {row.student.name}
                        </td>
                        <td className="py-3 px-4 text-center text-xs text-muted-foreground">
                          {row.student.gender}
                        </td>
                        <td className="py-2.5 px-4">
                          <div className="flex items-center gap-1.5 justify-center">
                            <input
                              type="text"
                              inputMode="decimal"
                              placeholder="Blank"
                              value={currentEntry.score}
                              disabled={isReadOnly}
                              onChange={(e) =>
                                handleScoreChange(
                                  row.enrollment_id,
                                  e.target.value,
                                )
                              }
                              className={`w-24 text-center rounded-md border px-2.5 py-1.5 font-mono text-sm font-semibold outline-none transition-colors ${
                                isReadOnly
                                  ? "bg-muted text-muted-foreground border-border cursor-not-allowed"
                                  : hasError
                                    ? "border-destructive bg-destructive/10 text-destructive focus:border-destructive"
                                    : !isBlank
                                      ? "border-primary bg-secondary/30 text-foreground focus:border-primary"
                                      : "border-input bg-background text-foreground focus:border-primary"
                              }`}
                            />
                            {!isReadOnly &&
                              !isBlank &&
                              !capabilities.is_data_entry && (
                                <button
                                  type="button"
                                  onClick={() =>
                                    handleClearScore(row.enrollment_id)
                                  }
                                  className="text-muted-foreground hover:text-destructive p-1 rounded transition-colors"
                                  title="Kosongkan Nilai (Blank)"
                                >
                                  &times;
                                </button>
                              )}
                          </div>
                          {hasError && (
                            <p className="mt-1 text-[10px] text-destructive text-center font-medium">
                              {hasError}
                            </p>
                          )}
                        </td>
                        <td className="py-2.5 px-4">
                          <input
                            type="text"
                            placeholder="Catatan perkembangan..."
                            value={currentEntry.feedback}
                            disabled={isReadOnly}
                            onChange={(e) =>
                              handleFeedbackChange(
                                row.enrollment_id,
                                e.target.value,
                              )
                            }
                            className={`w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs text-foreground outline-none transition-colors ${
                              isReadOnly
                                ? "bg-muted text-muted-foreground cursor-not-allowed"
                                : "focus:border-primary"
                            }`}
                          />
                        </td>
                        <td className="py-3 px-4 text-center">
                          {isBlank ? (
                            <Badge
                              variant="outline"
                              className="text-[11px] text-muted-foreground"
                            >
                              Blank
                            </Badge>
                          ) : isZero ? (
                            <Badge
                              variant="warning"
                              className="text-[11px] font-bold"
                            >
                              Skor 0
                            </Badge>
                          ) : (
                            <Badge
                              variant="success"
                              className="text-[11px] font-bold"
                            >
                              Tercatat
                            </Badge>
                          )}
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          </div>

          {/* Bottom Action Bar */}
          {!isReadOnly && (
            <div className="fixed bottom-0 left-0 right-0 z-40 border-t border-border bg-white px-6 py-3.5 shadow-lg">
              <div className="mx-auto flex max-w-6xl items-center justify-between">
                <div className="flex items-center gap-4 text-xs">
                  <span className="font-semibold text-foreground">
                    Progres: <strong>{currentRecorded}</strong> /{" "}
                    <strong>{currentTotal}</strong> siswa
                  </span>
                  <span className="text-muted-foreground">|</span>
                  <span className="text-muted-foreground">
                    Kosong: <strong>{currentBlank}</strong>
                  </span>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  <a
                    href={`/academic/assessments/${assessment.id}/template`}
                    className="inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-3 py-2 text-xs font-semibold text-foreground hover:bg-muted transition-colors"
                  >
                    <Download className="size-3.5 text-primary" />
                    <span>Format CSV</span>
                  </a>

                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setShowImportModal(true)}
                    className="gap-1.5 text-xs"
                  >
                    <Upload className="size-3.5 text-primary" />
                    <span>Impor CSV</span>
                  </Button>

                  <Button
                    type="submit"
                    variant="secondary"
                    size="sm"
                    disabled={savingScores}
                    className="gap-1.5 text-xs font-semibold"
                  >
                    <Save className="size-3.5" />
                    <span>
                      {savingScores ? "Menyimpan..." : "Simpan Draf Nilai"}
                    </span>
                  </Button>

                  {capabilities.can_publish && (
                    <Button
                      type="button"
                      variant="default"
                      size="sm"
                      onClick={() => setShowPublishModal(true)}
                      className="gap-1.5 text-xs font-bold"
                    >
                      <Send className="size-3.5" />
                      <span>Publikasikan Nilai</span>
                    </Button>
                  )}
                </div>
              </div>
            </div>
          )}
        </form>

        {/* Modal Konfirmasi Publikasi */}
        {showPublishModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div className="w-full max-w-md rounded-lg border border-border bg-white p-6 shadow-xl">
              <div className="flex items-center gap-3 text-primary mb-4">
                <div className="flex size-11 items-center justify-center rounded-md bg-secondary text-primary">
                  <Send className="size-6" />
                </div>
                <div>
                  <h3 className="text-base font-bold text-foreground">
                    Publikasikan Nilai Aktivitas?
                  </h3>
                  <p className="text-xs text-muted-foreground">
                    Konfirmasi pengesahan nilai kegiatan {assessment.title}
                  </p>
                </div>
              </div>

              <div className="rounded-md bg-muted/40 p-4 border border-border text-xs text-foreground space-y-2 mb-6">
                <p>
                  • Nilai yang dipublikasikan akan <strong>terkunci</strong> dan
                  tidak dapat diedit bebas tanpa hak revisi.
                </p>
                <p>
                  • Terdapat <strong>{currentRecorded}</strong> siswa ternilai
                  dan <strong>{currentBlank}</strong> siswa dengan nilai kosong
                  (blank).
                </p>
                <p>
                  • Hasil penilaian yang dipublikasikan dapat dipantau oleh wali
                  murid pada portal perkembangan belajar.
                </p>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => setShowPublishModal(false)}
                >
                  Batal
                </Button>
                <Button
                  type="button"
                  variant="default"
                  size="sm"
                  onClick={handlePublishConfirm}
                  disabled={publishing}
                >
                  {publishing ? "Memproses..." : "Ya, Publikasikan Sekarang"}
                </Button>
              </div>
            </div>
          </div>
        )}

        {/* Modal Impor CSV */}
        {showImportModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div className="w-full max-w-md rounded-lg border border-border bg-white p-6 shadow-xl">
              <div className="flex items-center justify-between pb-3 border-b border-border">
                <h3 className="text-base font-bold text-foreground flex items-center gap-2">
                  <FileSpreadsheet className="size-5 text-primary" />
                  <span>Impor Nilai via CSV</span>
                </h3>
                <button
                  type="button"
                  onClick={() => {
                    setShowImportModal(false);
                    setImportFile(null);
                  }}
                  className="rounded-md p-1 text-muted-foreground hover:text-foreground"
                >
                  <X className="size-4" />
                </button>
              </div>

              <div className="my-4 rounded-md bg-muted/40 p-3.5 border border-border text-xs text-foreground space-y-1.5">
                <p className="font-semibold">Petunjuk Pengisian CSV:</p>
                <p className="text-muted-foreground">
                  • Format kolom:{" "}
                  <code className="bg-background px-1 py-0.5 rounded border border-border font-mono">
                    enrollment_id, score, feedback
                  </code>
                </p>
                <p className="text-muted-foreground">
                  • Rentang nilai sah: 0.00 – 100.00 (maks 2 desimal). Baris
                  kosong berarti belum dinilai.
                </p>
                <div className="pt-2">
                  <a
                    href={`/academic/assessments/${assessment.id}/template`}
                    className="inline-flex items-center gap-1 text-primary hover:underline font-semibold"
                  >
                    <Download className="size-3.5" />
                    <span>Unduh Berkas Format CSV</span>
                  </a>
                </div>
              </div>

              <form onSubmit={handleImportSubmit} className="space-y-4">
                <div>
                  <input
                    type="file"
                    accept=".csv,text/csv,text/plain"
                    onChange={(e) =>
                      setImportFile(e.target.files ? e.target.files[0] : null)
                    }
                    required
                    className="w-full text-xs text-foreground file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-secondary file:text-secondary-foreground hover:file:bg-secondary/80 cursor-pointer border border-border rounded-md p-1"
                  />
                  {pageErrors.file && (
                    <p className="mt-1 text-xs text-destructive">
                      {pageErrors.file}
                    </p>
                  )}
                </div>

                <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => {
                      setShowImportModal(false);
                      setImportFile(null);
                    }}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="default"
                    size="sm"
                    disabled={importing || !importFile}
                    className="gap-1.5"
                  >
                    <Upload className="size-3.5" />
                    <span>{importing ? "Mengimpor..." : "Mulai Impor"}</span>
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
