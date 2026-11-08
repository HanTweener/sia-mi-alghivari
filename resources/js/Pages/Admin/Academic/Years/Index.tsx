import React, { useState } from "react";
import { Link, useForm, usePage } from "@inertiajs/react";
import {
  Calendar,
  CheckCircle2,
  Clock,
  Plus,
  ShieldCheck,
  ChevronRight,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";

interface Term {
  id: number;
  academic_year_id: number;
  semester: number;
  starts_on: string;
  ends_on: string;
  status: "draft" | "active" | "closed";
  full_name: string;
  semester_name: string;
}

interface AcademicYear {
  id: number;
  name: string;
  starts_on: string;
  ends_on: string;
  terms: Term[];
}

interface Props {
  academicYears: AcademicYear[];
  activeTerm: Term | null;
}

export default function YearsIndex({ academicYears, activeTerm }: Props) {
  const { auth } = usePage<SharedProps>().props;
  const canManage =
    auth.user?.roles.some((role) =>
      ["master_admin", "academic_admin"].includes(role),
    ) ?? false;
  const [showYearModal, setShowYearModal] = useState(false);
  const [selectedYearForTerm, setSelectedYearForTerm] =
    useState<AcademicYear | null>(null);

  const yearForm = useForm({
    name: "",
    starts_on: "",
    ends_on: "",
  });

  const termForm = useForm({
    semester: "1",
    starts_on: "",
    ends_on: "",
  });

  const activateForm = useForm({});

  const handleCreateYear = (e: React.FormEvent) => {
    e.preventDefault();
    yearForm.post("/academic/years", {
      onSuccess: () => {
        yearForm.reset();
        setShowYearModal(false);
      },
    });
  };

  const handleCreateTerm = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedYearForTerm) return;

    termForm.post(`/academic/years/${selectedYearForTerm.id}/terms`, {
      onSuccess: () => {
        termForm.reset();
        setSelectedYearForTerm(null);
      },
    });
  };

  const handleActivateTerm = (termId: number) => {
    if (
      confirm(
        "Aktifkan semester ini sebagai periode aktif resmi sekolah? Semester lain yang aktif akan ditutup secara otomatis.",
      )
    ) {
      activateForm.patch(`/academic/terms/${termId}/activate`);
    }
  };

  return (
    <AppLayout
      title="Tahun Ajaran & Semester"
      contextHeader={{
        title: "Tahun Ajaran & Semester",
        badge: "Kalender Akademik",
        description:
          "Kelola tahun ajaran dan tetapkan semester resmi sekolah yang aktif.",
      }}
    >
      <div className="space-y-6">
        {/* Active Term Status Banner */}
        <div className="rounded-xl border border-emerald-200 bg-emerald-50/80 p-5 shadow-xs">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div className="flex items-start gap-3.5">
              <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-xs">
                <ShieldCheck className="h-5 w-5" />
              </div>
              <div>
                <span className="text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                  Periode Akademik Aktif Sekolah
                </span>
                <h3 className="text-lg font-bold text-stone-900 mt-0.5">
                  {activeTerm
                    ? activeTerm.full_name
                    : "Belum ada semester aktif"}
                </h3>
                <p className="text-xs text-stone-600 mt-0.5">
                  {activeTerm
                    ? `Berlaku dari ${activeTerm.starts_on} s/d ${activeTerm.ends_on}`
                    : "Silakan aktifkan salah satu semester di bawah untuk memulai siklus pembelajaran."}
                </p>
              </div>
            </div>
            {canManage && (
              <button
                type="button"
                onClick={() => setShowYearModal(true)}
                className="inline-flex items-center gap-2 self-start sm:self-auto rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white shadow-xs transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600"
              >
                <Plus className="h-4 w-4" />
                Tambah Tahun Ajaran
              </button>
            )}
          </div>
        </div>

        {/* Academic Years List */}
        <div className="space-y-6">
          {academicYears.length === 0 ? (
            <div className="rounded-xl border border-stone-200 bg-white p-12 text-center shadow-xs">
              <Calendar className="mx-auto h-10 w-10 text-stone-300 mb-3" />
              <h3 className="text-sm font-semibold text-stone-800">
                Belum ada tahun ajaran
              </h3>
              <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
                Klik tombol &quot;Tambah Tahun Ajaran&quot; untuk menambahkan
                periode baru.
              </p>
            </div>
          ) : (
            academicYears.map((year) => (
              <div
                key={year.id}
                className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden"
              >
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-stone-100 bg-stone-50/60 px-5 py-3.5 gap-2">
                  <div>
                    <h3 className="text-base font-bold text-stone-900">
                      Tahun Ajaran {year.name}
                    </h3>
                    <p className="text-xs text-stone-500">
                      Rentang kalender: {year.starts_on} s/d {year.ends_on}
                    </p>
                  </div>
                  {canManage && (
                    <button
                      type="button"
                      onClick={() => setSelectedYearForTerm(year)}
                      className="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 shadow-xs hover:border-emerald-600 hover:text-emerald-700 transition"
                    >
                      <Plus className="h-3.5 w-3.5" />
                      Tambah Semester
                    </button>
                  )}
                </div>

                <div className="divide-y divide-stone-100">
                  {year.terms.length === 0 ? (
                    <div className="p-4 text-center text-xs text-stone-400">
                      Belum ada semester pada tahun ajaran ini.
                    </div>
                  ) : (
                    year.terms.map((term) => (
                      <div
                        key={term.id}
                        className={`flex items-center justify-between p-4 transition ${
                          term.status === "active"
                            ? "bg-emerald-50/40"
                            : "bg-white"
                        }`}
                      >
                        <div className="flex items-center gap-3">
                          <div
                            className={`flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold ${
                              term.status === "active"
                                ? "bg-emerald-600 text-white"
                                : "bg-stone-100 text-stone-600"
                            }`}
                          >
                            {term.semester}
                          </div>
                          <div>
                            <div className="flex items-center gap-2">
                              <span className="text-sm font-semibold text-stone-800">
                                Semester {term.semester_name}
                              </span>
                              <span
                                className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider ${
                                  term.status === "active"
                                    ? "bg-emerald-100 text-emerald-800"
                                    : term.status === "closed"
                                      ? "bg-stone-100 text-stone-600"
                                      : "bg-amber-100 text-amber-800"
                                }`}
                              >
                                {term.status === "active"
                                  ? "Aktif"
                                  : term.status === "closed"
                                    ? "Selesai"
                                    : "Draf"}
                              </span>
                            </div>
                            <span className="text-xs text-stone-500">
                              {term.starts_on} s/d {term.ends_on}
                            </span>
                          </div>
                        </div>

                        {canManage && term.status !== "active" && (
                          <button
                            type="button"
                            onClick={() => handleActivateTerm(term.id)}
                            className="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-xs hover:bg-emerald-50 transition"
                          >
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            Aktifkan Semester
                          </button>
                        )}
                      </div>
                    ))
                  )}
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {/* Modal Tambah Tahun Ajaran */}
      {canManage && showYearModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Tambah Tahun Ajaran Baru
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Format tahun ajaran menggunakan format umum, contoh: 2025/2026.
            </p>

            <form onSubmit={handleCreateYear} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Nama Tahun Ajaran
                </label>
                <input
                  type="text"
                  value={yearForm.data.name}
                  onChange={(e) => yearForm.setData("name", e.target.value)}
                  placeholder="2025/2026"
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
                {yearForm.errors.name && (
                  <p className="mt-1 text-xs text-red-600">
                    {yearForm.errors.name}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tanggal Mulai
                  </label>
                  <input
                    type="date"
                    value={yearForm.data.starts_on}
                    onChange={(e) =>
                      yearForm.setData("starts_on", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tanggal Selesai
                  </label>
                  <input
                    type="date"
                    value={yearForm.data.ends_on}
                    onChange={(e) =>
                      yearForm.setData("ends_on", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
              </div>
              {yearForm.errors.ends_on && (
                <p className="text-xs text-red-600">
                  {yearForm.errors.ends_on}
                </p>
              )}

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowYearModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={yearForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {yearForm.processing ? "Menyimpan..." : "Simpan Tahun Ajaran"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Tambah Semester */}
      {canManage && selectedYearForTerm && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Tambah Semester ({selectedYearForTerm.name})
            </h3>

            <form onSubmit={handleCreateTerm} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Pilih Semester
                </label>
                <select
                  value={termForm.data.semester}
                  onChange={(e) => termForm.setData("semester", e.target.value)}
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="1">Semester 1 (Ganjil)</option>
                  <option value="2">Semester 2 (Genap)</option>
                </select>
                {termForm.errors.semester && (
                  <p className="mt-1 text-xs text-red-600">
                    {termForm.errors.semester}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tanggal Mulai
                  </label>
                  <input
                    type="date"
                    value={termForm.data.starts_on}
                    onChange={(e) =>
                      termForm.setData("starts_on", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tanggal Selesai
                  </label>
                  <input
                    type="date"
                    value={termForm.data.ends_on}
                    onChange={(e) =>
                      termForm.setData("ends_on", e.target.value)
                    }
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
              </div>
              {termForm.errors.ends_on && (
                <p className="text-xs text-red-600">
                  {termForm.errors.ends_on}
                </p>
              )}

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setSelectedYearForTerm(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={termForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {termForm.processing ? "Menyimpan..." : "Simpan Semester"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
