import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  BookOpen,
  Edit2,
  Filter,
  ListChecks,
  Plus,
  Search,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";

interface Subject {
  id: number;
  code: string;
  name: string;
  category: "umum" | "agama" | "muatan_lokal";
  is_active: boolean;
}

interface PaginationLinks {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  subjects: {
    data: Subject[];
    links: PaginationLinks[];
    total: number;
  };
  filters: {
    search?: string;
    category?: string;
  };
}

export default function SubjectsIndex({ subjects, filters }: Props) {
  const { auth } = usePage<SharedProps>().props;
  const canManage =
    auth.user?.roles.some((role) =>
      ["master_admin", "academic_admin"].includes(role),
    ) ?? false;
  const [showModal, setShowModal] = useState(false);
  const [editingSubject, setEditingSubject] = useState<Subject | null>(null);
  const [search, setSearch] = useState(filters.search || "");

  const form = useForm({
    code: "",
    name: "",
    category: "umum",
    is_active: true,
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/academic/subjects",
      { ...filters, search: search || undefined },
      { preserveState: true },
    );
  };

  const handleCategoryFilter = (cat: string) => {
    router.get(
      "/academic/subjects",
      { ...filters, category: cat || undefined },
      { preserveState: true },
    );
  };

  const openCreateModal = () => {
    setEditingSubject(null);
    form.reset();
    setShowModal(true);
  };

  const openEditModal = (subject: Subject) => {
    setEditingSubject(subject);
    form.setData({
      code: subject.code,
      name: subject.name,
      category: subject.category,
      is_active: subject.is_active,
    });
    setShowModal(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (editingSubject) {
      form.put(`/academic/subjects/${editingSubject.id}`, {
        onSuccess: () => setShowModal(false),
      });
    } else {
      form.post("/academic/subjects", {
        onSuccess: () => setShowModal(false),
      });
    }
  };

  return (
    <AppLayout
      title="Katalog Mata Pelajaran"
      contextHeader={{
        title: "Katalog Mata Pelajaran",
        badge: "FR-003",
        description:
          "Daftar mata pelajaran umum, keagamaan madrasah, dan muatan lokal.",
        totalCount: subjects.total,
      }}
    >
      <div className="space-y-6">
        {canManage && (
          <div className="flex justify-end gap-2">
            <Link href="/academic/teaching-assignments">
              <Button
                variant="outline"
                className="inline-flex items-center gap-2 text-xs"
              >
                <ListChecks className="h-4 w-4" />
                Penugasan Mapel
              </Button>
            </Link>
            <Button
              type="button"
              onClick={openCreateModal}
              className="inline-flex items-center gap-2 text-xs"
            >
              <Plus className="h-4 w-4" />
              Tambah Mapel
            </Button>
          </div>
        )}

        {/* Filters */}
        <div className="mb-6 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-3 rounded-xl border border-stone-200 shadow-2xs">
          <form onSubmit={handleSearch} className="relative w-full sm:w-72">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-stone-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari kode atau nama mapel..."
              className="w-full rounded-lg border border-stone-300 pl-9 pr-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
            />
          </form>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Filter className="h-4 w-4 text-stone-400" />
            <select
              value={filters.category || ""}
              onChange={(e) => handleCategoryFilter(e.target.value)}
              className="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-700 focus:border-emerald-600 focus:outline-none"
            >
              <option value="">Semua Kategori</option>
              <option value="umum">Umum</option>
              <option value="agama">Pendidikan Agama</option>
              <option value="muatan_lokal">Muatan Lokal</option>
            </select>
          </div>
        </div>

        {/* Subjects Table */}
        <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
          <table className="w-full text-left text-sm">
            <thead className="bg-stone-50 border-b border-stone-100 text-xs font-semibold uppercase tracking-wider text-stone-600">
              <tr>
                <th className="px-5 py-3">Kode</th>
                <th className="px-5 py-3">Nama Mata Pelajaran</th>
                <th className="px-5 py-3">Kategori</th>
                <th className="px-5 py-3">Status</th>
                {canManage && <th className="px-5 py-3 text-right">Aksi</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-stone-100 text-xs text-stone-800">
              {subjects.data.length === 0 ? (
                <tr>
                  <td
                    colSpan={canManage ? 5 : 4}
                    className="p-8 text-center text-stone-400"
                  >
                    Tidak ada mata pelajaran yang ditemukan.
                  </td>
                </tr>
              ) : (
                subjects.data.map((s) => (
                  <tr key={s.id} className="hover:bg-stone-50/80 transition">
                    <td className="px-5 py-3.5 font-bold font-mono text-emerald-800">
                      {s.code}
                    </td>
                    <td className="px-5 py-3.5 font-medium text-stone-900">
                      {s.name}
                    </td>
                    <td className="px-5 py-3.5">
                      <span className="capitalize px-2 py-0.5 rounded bg-stone-100 text-stone-700 text-[10px] font-semibold">
                        {s.category.replace("_", " ")}
                      </span>
                    </td>
                    <td className="px-5 py-3.5">
                      <span
                        className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ${
                          s.is_active
                            ? "bg-emerald-100 text-emerald-800"
                            : "bg-stone-100 text-stone-500"
                        }`}
                      >
                        {s.is_active ? "Aktif" : "Non-aktif"}
                      </span>
                    </td>
                    {canManage && (
                      <td className="px-5 py-3.5 text-right">
                        <button
                          type="button"
                          onClick={() => openEditModal(s)}
                          className="inline-flex items-center gap-1 text-emerald-700 hover:text-emerald-800 font-semibold"
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

      {/* Modal Tambah / Edit Mapel */}
      {canManage && showModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900">
              {editingSubject
                ? `Edit Mapel (${editingSubject.code})`
                : "Tambah Mata Pelajaran Baru"}
            </h3>

            <form onSubmit={handleSubmit} className="mt-4 space-y-4">
              {!editingSubject && (
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Kode Mapel
                  </label>
                  <input
                    type="text"
                    value={form.data.code}
                    onChange={(e) =>
                      form.setData("code", e.target.value.toUpperCase())
                    }
                    placeholder="Contoh: MTK atau PAI"
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600 font-mono"
                  />
                  {form.errors.code && (
                    <p className="mt-1 text-xs text-red-600">
                      {form.errors.code}
                    </p>
                  )}
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Nama Mata Pelajaran
                </label>
                <input
                  type="text"
                  value={form.data.name}
                  onChange={(e) => form.setData("name", e.target.value)}
                  placeholder="Contoh: Matematika"
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                />
                {form.errors.name && (
                  <p className="mt-1 text-xs text-red-600">
                    {form.errors.name}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  Kategori
                </label>
                <select
                  value={form.data.category}
                  onChange={(e) =>
                    form.setData("category", e.target.value as any)
                  }
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                >
                  <option value="umum">Mata Pelajaran Umum</option>
                  <option value="agama">Pendidikan Agama Islam</option>
                  <option value="muatan_lokal">Muatan Lokal</option>
                </select>
              </div>

              {editingSubject && (
                <div className="flex items-center gap-2 pt-1">
                  <input
                    type="checkbox"
                    id="is_active"
                    checked={form.data.is_active}
                    onChange={(e) =>
                      form.setData("is_active", e.target.checked)
                    }
                    className="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500"
                  />
                  <label
                    htmlFor="is_active"
                    className="text-xs font-semibold text-stone-700"
                  >
                    Mata pelajaran aktif diajarkan
                  </label>
                </div>
              )}

              <div className="mt-6 flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={form.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {form.processing ? "Menyimpan..." : "Simpan Mapel"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
