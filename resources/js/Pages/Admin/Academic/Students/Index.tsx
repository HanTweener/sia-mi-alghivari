import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  Download,
  Edit2,
  FileSpreadsheet,
  Filter,
  Link2,
  Plus,
  Search,
  Upload,
  UserCheck,
  Users,
  X,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface GuardianInfo {
  id: number;
  name: string;
  username: string;
  relationship?: string;
  is_primary?: boolean;
}

interface Student {
  id: number;
  nis: string;
  name: string;
  gender: "L" | "P";
  birth_date: string | null;
  birth_place: string | null;
  status: "active" | "graduated" | "transferred" | "inactive";
  guardians: GuardianInfo[];
}

interface AvailableGuardian {
  id: number;
  name: string;
  username: string;
}

interface PaginationLinks {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  students: {
    data: Student[];
    links: PaginationLinks[];
    total: number;
  };
  availableGuardians: AvailableGuardian[];
  filters: {
    search?: string;
    status?: string;
  };
}

export default function StudentsIndex({
  students,
  availableGuardians,
  filters,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const roles = auth.user?.roles ?? [];
  const canManage = roles.some((role) =>
    ["master_admin", "academic_admin", "data_entry_admin"].includes(role),
  );
  const canExport = canManage || roles.includes("principal");

  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showImportModal, setShowImportModal] = useState(false);
  const [editingStudent, setEditingStudent] = useState<Student | null>(null);
  const [linkingStudent, setLinkingStudent] = useState<Student | null>(null);
  const [search, setSearch] = useState(filters.search || "");

  const importForm = useForm<{ file: File | null }>({
    file: null,
  });

  const createForm = useForm({
    nis: "",
    name: "",
    gender: "L",
    birth_date: "",
    birth_place: "",
    guardian_user_id: "",
    guardian_relationship: "ayah",
  });

  const editForm = useForm({
    name: "",
    gender: "L",
    birth_date: "",
    birth_place: "",
    status: "active",
  });

  const linkForm = useForm({
    guardian_user_id: "",
    relationship: "ibu",
    is_primary: false,
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/academic/students",
      { ...filters, search: search || undefined },
      { preserveState: true },
    );
  };

  const handleStatusFilter = (status: string) => {
    router.get(
      "/academic/students",
      { ...filters, status: status || undefined },
      { preserveState: true },
    );
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post("/academic/students", {
      onSuccess: () => {
        createForm.reset();
        setShowCreateModal(false);
      },
    });
  };

  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingStudent) return;

    editForm.put(`/academic/students/${editingStudent.id}`, {
      onSuccess: () => setEditingStudent(null),
    });
  };

  const handleLinkSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!linkingStudent) return;

    linkForm.post(`/academic/students/${linkingStudent.id}/guardians`, {
      onSuccess: () => {
        linkForm.reset();
        setLinkingStudent(null);
      },
    });
  };

  const handleImportSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    importForm.post("/academic/students/import", {
      forceFormData: true,
      onSuccess: () => {
        importForm.reset();
        setShowImportModal(false);
      },
    });
  };

  const openEditModal = (student: Student) => {
    setEditingStudent(student);
    editForm.setData({
      name: student.name,
      gender: student.gender,
      birth_date: student.birth_date || "",
      birth_place: student.birth_place || "",
      status: student.status,
    });
  };

  return (
    <AppLayout
      title="Buku Induk Siswa & Relasi Wali — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Data Pokok Siswa
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              Buku Induk & Wali Santri (FR-003, Q6.1)
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Buku Induk Siswa
          </h1>
        </div>
      }
      actions={
        <div className="flex items-center gap-2">
          {canExport && (
            <a
              href="/academic/students/export"
              className="inline-flex items-center gap-1.5 rounded-md border border-border bg-white px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-muted transition-colors shadow-2xs"
              title="Unduh data siswa format CSV"
            >
              <Download className="size-3.5 text-primary" />
              <span>Ekspor CSV</span>
            </a>
          )}
          {canManage && (
            <>
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
                type="button"
                variant="default"
                size="sm"
                onClick={() => setShowCreateModal(true)}
                className="gap-1.5 text-xs font-semibold"
              >
                <Plus className="size-3.5" />
                <span>Daftar Siswa Baru</span>
              </Button>
            </>
          )}
        </div>
      }
    >
      <div className="space-y-6">
        {/* Filters */}
        <div className="mb-6 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-3 rounded-xl border border-stone-200 shadow-2xs">
          <form onSubmit={handleSearch} className="relative w-full sm:w-80">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari NIS atau nama siswa..."
              className="w-full rounded-lg border border-stone-300 pl-9 pr-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
            />
          </form>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Filter className="h-4 w-4 text-stone-400" />
            <select
              value={filters.status || ""}
              onChange={(e) => handleStatusFilter(e.target.value)}
              className="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-700 focus:border-emerald-600 focus:outline-none"
            >
              <option value="">Semua Status</option>
              <option value="active">Aktif Belajar</option>
              <option value="graduated">Lulus</option>
              <option value="transferred">Pindah Sekolah</option>
              <option value="inactive">Non-aktif</option>
            </select>
          </div>
        </div>

        {/* Students Table */}
        <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-sm min-w-[640px]">
              <thead className="bg-stone-50 border-b border-stone-100 text-xs font-semibold uppercase tracking-wider text-stone-600">
                <tr>
                  <th className="px-5 py-3">NIS</th>
                  <th className="px-5 py-3">Nama Siswa</th>
                  <th className="px-5 py-3">L/P</th>
                  {canExport && (
                    <th className="px-5 py-3">Wali Terdaftar (Q6.1)</th>
                  )}
                  <th className="px-5 py-3">Status</th>
                  {canManage && <th className="px-5 py-3 text-right">Aksi</th>}
                </tr>
              </thead>
              <tbody className="divide-y divide-stone-100 text-xs text-stone-800">
                {students.data.length === 0 ? (
                  <tr>
                    <td
                      colSpan={4 + Number(canExport) + Number(canManage)}
                      className="p-8 text-center text-stone-400"
                    >
                      Tidak ada data siswa yang sesuai pencarian.
                    </td>
                  </tr>
                ) : (
                  students.data.map((s) => (
                    <tr key={s.id} className="hover:bg-stone-50/80 transition">
                      <td className="px-5 py-3.5 font-bold font-mono text-emerald-800">
                        {s.nis}
                      </td>
                      <td className="px-5 py-3.5 font-medium text-stone-900">
                        <div>{s.name}</div>
                        {s.birth_place && s.birth_date && (
                          <span className="text-[11px] text-stone-400">
                            {s.birth_place}, {s.birth_date}
                          </span>
                        )}
                      </td>
                      <td className="px-5 py-3.5">
                        <span
                          className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-bold ${
                            s.gender === "L"
                              ? "bg-blue-100 text-blue-800"
                              : "bg-pink-100 text-pink-800"
                          }`}
                        >
                          {s.gender === "L" ? "L" : "P"}
                        </span>
                      </td>
                      {canExport && (
                        <td className="px-5 py-3.5">
                          {s.guardians.length === 0 ? (
                            <span className="text-stone-400 italic text-[11px]">
                              Belum ditautkan
                            </span>
                          ) : (
                            <div className="space-y-0.5">
                              {s.guardians.map((g) => (
                                <div
                                  key={g.id}
                                  className="flex items-center gap-1.5"
                                >
                                  <span className="capitalize font-semibold text-stone-700">
                                    {g.relationship}:
                                  </span>
                                  <span className="text-stone-800">
                                    {g.name}
                                  </span>
                                  <span className="text-[10px] text-stone-400">
                                    (@{g.username})
                                  </span>
                                </div>
                              ))}
                            </div>
                          )}
                        </td>
                      )}
                      <td className="px-5 py-3.5">
                        <span
                          className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                            s.status === "active"
                              ? "bg-emerald-100 text-emerald-800"
                              : s.status === "graduated"
                                ? "bg-blue-100 text-blue-800"
                                : "bg-stone-100 text-stone-600"
                          }`}
                        >
                          {s.status === "active"
                            ? "Aktif"
                            : s.status === "graduated"
                              ? "Lulus"
                              : s.status}
                        </span>
                      </td>
                      {canManage && (
                        <td className="px-5 py-3.5 text-right space-x-2">
                          <button
                            type="button"
                            onClick={() => setLinkingStudent(s)}
                            className="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 font-semibold"
                            title="Tautkan Akun Wali Santri"
                          >
                            <Link2 className="h-3.5 w-3.5" />
                            Tautkan Wali
                          </button>
                          <button
                            type="button"
                            onClick={() => openEditModal(s)}
                            className="inline-flex items-center gap-1 text-stone-600 hover:text-stone-800 font-semibold"
                          >
                            <Edit2 className="h-3 w-3" />
                            Edit
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
      </div>

      {/* Modal Tambah Siswa */}
      {canManage && showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Pendaftaran Biodata Siswa Baru
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Sesuai aturan Q6.1, akun wali santri disiapkan terlebih dahulu
              oleh Master Admin sebelum ditautkan.
            </p>

            <form onSubmit={handleCreateSubmit} className="mt-4 space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Nomor Induk Siswa (NIS)
                  </label>
                  <input
                    type="text"
                    value={createForm.data.nis}
                    onChange={(e) => createForm.setData("nis", e.target.value)}
                    placeholder="Contoh: 2026001"
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600 font-mono"
                  />
                  {createForm.errors.nis && (
                    <p className="mt-1 text-xs text-red-600">
                      {createForm.errors.nis}
                    </p>
                  )}
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Jenis Kelamin
                  </label>
                  <select
                    value={createForm.data.gender}
                    onChange={(e) =>
                      createForm.setData("gender", e.target.value as any)
                    }
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  >
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Nama Lengkap Siswa
                </label>
                <input
                  type="text"
                  value={createForm.data.name}
                  onChange={(e) => createForm.setData("name", e.target.value)}
                  placeholder="Contoh: Muhammad Fatih"
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
                {createForm.errors.name && (
                  <p className="mt-1 text-xs text-red-600">
                    {createForm.errors.name}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tempat Lahir
                  </label>
                  <input
                    type="text"
                    value={createForm.data.birth_place}
                    onChange={(e) =>
                      createForm.setData("birth_place", e.target.value)
                    }
                    placeholder="Bandung"
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Tanggal Lahir
                  </label>
                  <input
                    type="date"
                    value={createForm.data.birth_date}
                    onChange={(e) =>
                      createForm.setData("birth_date", e.target.value)
                    }
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                  />
                </div>
              </div>

              {/* Guardian linkage Q6.1 */}
              <div className="border-t border-stone-100 pt-3">
                <span className="block text-xs font-bold text-stone-800 mb-2">
                  Tautkan Akun Wali Santri (Opsional)
                </span>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[11px] font-medium text-stone-600 mb-1">
                      Pilih Akun Wali
                    </label>
                    <select
                      value={createForm.data.guardian_user_id}
                      onChange={(e) =>
                        createForm.setData("guardian_user_id", e.target.value)
                      }
                      className="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                    >
                      <option value="">Belum Ditautkan</option>
                      {availableGuardians.map((g) => (
                        <option key={g.id} value={g.id}>
                          {g.name} (@{g.username})
                        </option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-medium text-stone-600 mb-1">
                      Hubungan
                    </label>
                    <select
                      value={createForm.data.guardian_relationship}
                      onChange={(e) =>
                        createForm.setData(
                          "guardian_relationship",
                          e.target.value,
                        )
                      }
                      className="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                    >
                      <option value="ayah">Ayah</option>
                      <option value="ibu">Ibu</option>
                      <option value="wali">Wali Santri</option>
                    </select>
                  </div>
                </div>
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {createForm.processing ? "Menyimpan..." : "Simpan Siswa"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Tautkan Wali (Q6.1) */}
      {canManage && linkingStudent && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              Tautkan Akun Wali ({linkingStudent.name})
            </h3>
            <p className="mt-1 text-xs text-stone-500">
              Pilih akun wali santri resmi yang sudah terdaftar dalam sistem.
            </p>

            <form onSubmit={handleLinkSubmit} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Pilih Akun Wali Santri
                </label>
                <select
                  value={linkForm.data.guardian_user_id}
                  onChange={(e) =>
                    linkForm.setData("guardian_user_id", e.target.value)
                  }
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="">Pilih Akun Wali</option>
                  {availableGuardians.map((g) => (
                    <option key={g.id} value={g.id}>
                      {g.name} (@{g.username})
                    </option>
                  ))}
                </select>
                {linkForm.errors.guardian_user_id && (
                  <p className="mt-1 text-xs text-red-600">
                    {linkForm.errors.guardian_user_id}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Hubungan
                </label>
                <select
                  value={linkForm.data.relationship}
                  onChange={(e) =>
                    linkForm.setData("relationship", e.target.value)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="ayah">Ayah</option>
                  <option value="ibu">Ibu</option>
                  <option value="wali">Wali Santri</option>
                </select>
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setLinkingStudent(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={linkForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {linkForm.processing ? "Menautkan..." : "Tautkan Wali"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Impor CSV Siswa */}
      {canManage && showImportModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-100">
            <div className="flex items-center gap-3">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                <FileSpreadsheet className="h-5 w-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Impor Siswa Massal via CSV
                </h3>
                <p className="text-xs text-stone-500">
                  Unggah berkas spreadsheet berekstensi .csv
                </p>
              </div>
            </div>

            <div className="mt-4 rounded-lg bg-stone-50 p-3 border border-stone-200 text-xs text-stone-600">
              <p className="font-semibold text-stone-800 mb-1">
                Panduan Format Kolom:
              </p>
              <p>
                Wajib:{" "}
                <code className="bg-white px-1 py-0.5 rounded border border-stone-200 font-mono">
                  nis, name, gender
                </code>{" "}
                (L/P).
              </p>
              <p className="mt-1">
                Opsional:{" "}
                <code className="bg-white px-1 py-0.5 rounded border border-stone-200 font-mono">
                  nisn, birth_place, birth_date
                </code>{" "}
                (YYYY-MM-DD).
              </p>
              <div className="mt-2 pt-2 border-t border-stone-200">
                <a
                  href="/academic/students/template"
                  className="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 font-semibold text-xs"
                >
                  <Download className="h-3.5 w-3.5" />
                  Unduh Contoh Template CSV
                </a>
              </div>
            </div>

            <form onSubmit={handleImportSubmit} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Pilih Berkas CSV
                </label>
                <input
                  type="file"
                  accept=".csv,text/csv,text/plain"
                  onChange={(e) =>
                    importForm.setData(
                      "file",
                      e.target.files ? e.target.files[0] : null,
                    )
                  }
                  required
                  className="w-full text-xs text-stone-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 cursor-pointer border border-stone-200 rounded-lg p-1"
                />
                {importForm.errors.file && (
                  <p className="mt-2 text-xs text-red-600 font-medium whitespace-pre-wrap">
                    {importForm.errors.file}
                  </p>
                )}
              </div>

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => {
                    importForm.reset();
                    setShowImportModal(false);
                  }}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={importForm.processing || !importForm.data.file}
                  className="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  <Upload className="h-3.5 w-3.5" />
                  {importForm.processing ? "Mengimpor..." : "Mulai Impor"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
