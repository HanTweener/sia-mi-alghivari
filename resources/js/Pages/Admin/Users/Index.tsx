import React, { useState } from "react";
import { useForm, router, Link } from "@inertiajs/react";
import {
  Users,
  UserPlus,
  Search,
  ArrowLeft,
  Shield,
  AlertTriangle,
  CheckCircle,
  X,
  Key,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface UserItem {
  id: number;
  name: string;
  username: string;
  email: string;
  is_active: boolean;
  must_change_password: boolean;
  roles: string[];
  created_at: string;
}

interface Props {
  users: {
    data: UserItem[];
    current_page: number;
    last_page: number;
    total: number;
  };
  filters: {
    search?: string;
    role?: string;
  };
  availableRoles: Array<{
    value: string;
    label: string;
  }>;
  flash?: {
    success?: string;
    temporary_credentials?: {
      username: string;
      temporary_password: string;
    };
  };
}

export default function Index({
  users,
  filters,
  availableRoles,
  flash,
}: Props) {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState(filters.search || "");
  const [selectedRole, setSelectedRole] = useState(filters.role || "");

  const createForm = useForm({
    name: "",
    username: "",
    email: "",
    phone: "",
    address: "",
    roles: [] as string[],
  });

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/admin/users",
      { search: searchTerm, role: selectedRole },
      { preserveState: true },
    );
  };

  const handleRoleToggle = (roleValue: string) => {
    const current = [...createForm.data.roles];
    const index = current.indexOf(roleValue);
    if (index > -1) {
      current.splice(index, 1);
    } else {
      current.push(roleValue);
    }
    createForm.setData("roles", current);
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post("/admin/users", {
      onSuccess: () => {
        setShowCreateModal(false);
        createForm.reset();
      },
    });
  };

  const handleDeactivate = (userId: number, name: string) => {
    if (confirm(`Yakin ingin menonaktifkan akun ${name}?`)) {
      router.delete(`/admin/users/${userId}`);
    }
  };

  return (
    <AppLayout
      title="Manajemen Pengguna & Peran"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Master Admin
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              Otorisasi & Akun
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Pengelolaan Akun & Peran
          </h1>
        </div>
      }
      actions={
        <Button
          onClick={() => setShowCreateModal(true)}
          size="sm"
          className="gap-1.5"
        >
          <UserPlus className="size-4" />
          <span>Tambah Akun Baru</span>
        </Button>
      }
    >
      <div className="space-y-6">
        {/* Temporary Credentials Banner if Created */}
        {flash?.temporary_credentials && (
          <div className="p-4 rounded-xl bg-warning/20 border border-toolbar-border text-xs text-warning-foreground flex items-start gap-3 shadow-xs">
            <Key className="w-5 h-5 text-warning-foreground shrink-0 mt-0.5" />
            <div className="space-y-1">
              <p className="font-bold text-sm">
                Akun Berhasil Didaftarkan — Kredensial Sementara (D-25):
              </p>
              <p>
                Username:{" "}
                <code className="font-mono bg-white px-1.5 py-0.5 rounded border border-toolbar-border text-foreground font-semibold">
                  {flash.temporary_credentials.username}
                </code>
              </p>
              <p>
                Kata Sandi Sementara:{" "}
                <code className="font-mono bg-white px-1.5 py-0.5 rounded border border-toolbar-border text-destructive font-bold">
                  {flash.temporary_credentials.temporary_password}
                </code>
              </p>
              <p className="text-[11px] text-muted-foreground mt-1">
                Kredensial ini hanya ditampilkan sekali. Pengguna wajib
                mengganti kata sandi pada saat pertama kali login.
              </p>
            </div>
          </div>
        )}

        {/* Filters */}
        <form
          onSubmit={handleSearch}
          className="bg-white border border-border p-4 rounded-lg flex flex-wrap gap-3 items-center shadow-xs"
        >
          <div className="relative flex-1 min-w-[200px]">
            <Search className="w-4 h-4 text-muted-foreground absolute left-3 top-2.5" />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Cari nama, username, atau email..."
              className="w-full pl-9 pr-3 py-1.5 text-xs rounded-md border border-border bg-input-surface text-foreground focus:outline-hidden focus:ring-1 focus:ring-primary"
            />
          </div>

          <select
            value={selectedRole}
            onChange={(e) => setSelectedRole(e.target.value)}
            className="px-3 py-1.5 text-xs rounded-md border border-border bg-input-surface text-foreground focus:outline-hidden focus:ring-1 focus:ring-primary"
          >
            <option value="">Semua Peran</option>
            {availableRoles.map((r) => (
              <option key={r.value} value={r.value}>
                {r.label}
              </option>
            ))}
          </select>

          <button
            type="submit"
            className="px-4 py-1.5 bg-secondary text-secondary-foreground text-xs font-semibold rounded-md border border-border hover:bg-secondary/80 transition"
          >
            Terapkan Filter
          </button>
        </form>

        {/* Table */}
        <div className="bg-white border border-border rounded-xl shadow-xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-muted/50 border-b border-border text-muted-foreground uppercase text-[11px] font-bold">
                <tr>
                  <th className="px-5 py-3.5">Nama & Identitas</th>
                  <th className="px-4 py-3.5">Peran Aktif</th>
                  <th className="px-4 py-3.5">Status Akun</th>
                  <th className="px-4 py-3.5">Status Sandi</th>
                  <th className="px-4 py-3.5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {users.data.length === 0 ? (
                  <tr>
                    <td
                      colSpan={5}
                      className="px-5 py-8 text-center text-muted-foreground"
                    >
                      Tidak ada pengguna yang cocok dengan kriteria pencarian.
                    </td>
                  </tr>
                ) : (
                  users.data.map((u) => (
                    <tr key={u.id} className="hover:bg-canvas/50 transition">
                      <td className="px-5 py-3.5">
                        <p className="font-semibold text-foreground text-sm">
                          {u.name}
                        </p>
                        <p className="text-[11px] text-muted-foreground">
                          @{u.username} &middot; {u.email}
                        </p>
                      </td>
                      <td className="px-4 py-3.5">
                        <div className="flex gap-1 flex-wrap">
                          {u.roles.map((r) => (
                            <span
                              key={r}
                              className="px-2 py-0.5 rounded-full bg-secondary text-secondary-foreground text-[10px] font-bold uppercase"
                            >
                              {r.replace("_", " ")}
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-4 py-3.5">
                        {u.is_active ? (
                          <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-primary">
                            <CheckCircle className="w-3.5 h-3.5" /> Aktif
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-destructive">
                            <AlertTriangle className="w-3.5 h-3.5" /> Nonaktif
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3.5">
                        {u.must_change_password ? (
                          <span className="px-2 py-0.5 rounded bg-warning/20 border border-toolbar-border text-warning-foreground text-[10px] font-medium">
                            Perlu Ganti Sandi
                          </span>
                        ) : (
                          <span className="text-muted-foreground text-[11px]">
                            Sandi Permanen
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3.5 text-right">
                        {u.is_active && (
                          <button
                            onClick={() => handleDeactivate(u.id, u.name)}
                            className="text-destructive hover:underline text-[11px] font-medium"
                          >
                            Nonaktifkan
                          </button>
                        )}
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {/* Create User Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
          <div className="bg-white border border-border rounded-xl shadow-lg w-full max-w-lg overflow-hidden">
            <div className="border-b border-border px-6 py-4 flex items-center justify-between bg-muted/30">
              <h2 className="text-base font-bold text-foreground">
                Tambah Pengguna Baru
              </h2>
              <button
                onClick={() => setShowCreateModal(false)}
                className="text-muted-foreground hover:text-foreground p-1"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="p-6 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Nama Lengkap
                </label>
                <input
                  type="text"
                  required
                  value={createForm.data.name}
                  onChange={(e) => createForm.setData("name", e.target.value)}
                  className="w-full px-3 py-1.5 text-sm rounded-md border border-border bg-input-surface text-foreground"
                />
                {createForm.errors.name && (
                  <p className="text-xs text-destructive mt-0.5">
                    {createForm.errors.name}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Username
                  </label>
                  <input
                    type="text"
                    required
                    value={createForm.data.username}
                    onChange={(e) =>
                      createForm.setData("username", e.target.value)
                    }
                    placeholder="nama_pengguna"
                    className="w-full px-3 py-1.5 text-sm rounded-md border border-border bg-input-surface text-foreground"
                  />
                  {createForm.errors.username && (
                    <p className="text-xs text-destructive mt-0.5">
                      {createForm.errors.username}
                    </p>
                  )}
                </div>

                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Email
                  </label>
                  <input
                    type="email"
                    required
                    value={createForm.data.email}
                    onChange={(e) =>
                      createForm.setData("email", e.target.value)
                    }
                    placeholder="email@sekolah.sch.id"
                    className="w-full px-3 py-1.5 text-sm rounded-md border border-border bg-input-surface text-foreground"
                  />
                  {createForm.errors.email && (
                    <p className="text-xs text-destructive mt-0.5">
                      {createForm.errors.email}
                    </p>
                  )}
                </div>
              </div>

              {/* Roles Multi-Select Checkboxes */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-2">
                  Tetapkan Peran Pengguna (Multi-Role)
                </label>
                <div className="grid grid-cols-2 gap-2 p-3 rounded-lg border border-border bg-canvas">
                  {availableRoles.map((r) => (
                    <label
                      key={r.value}
                      className="flex items-center gap-2 text-xs text-foreground cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        checked={createForm.data.roles.includes(r.value)}
                        onChange={() => handleRoleToggle(r.value)}
                        className="rounded border-border text-primary focus:ring-primary"
                      />
                      <span>{r.label}</span>
                    </label>
                  ))}
                </div>
                {createForm.errors.roles && (
                  <p className="text-xs text-destructive mt-1">
                    {createForm.errors.roles}
                  </p>
                )}
              </div>

              <div className="pt-3 border-t border-border flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 rounded-md border border-border text-xs font-medium text-foreground hover:bg-muted"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="px-5 py-2 rounded-md bg-primary text-white text-xs font-semibold hover:bg-primary/90 disabled:opacity-50"
                >
                  {createForm.processing ? "Menyimpan..." : "Buat Akun"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
