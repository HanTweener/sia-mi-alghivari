import React from "react";
import { useForm, Link } from "@inertiajs/react";
import {
  User,
  Phone,
  MapPin,
  Mail,
  Shield,
  ArrowLeft,
  Check,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface Props {
  user: {
    id: number;
    name: string;
    username: string;
    email: string;
    phone: string | null;
    address: string | null;
    avatar_path: string | null;
    roles: string[];
  };
}

export default function Edit({ user }: Props) {
  const form = useForm({
    name: user.name,
    phone: user.phone || "",
    address: user.address || "",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    form.patch("/profile");
  };

  return (
    <AppLayout
      title="Profil Mandiri — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Akun Saya
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              Profil Mandiri
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Pengaturan Profil Pengguna
          </h1>
        </div>
      }
    >
      <div className="max-w-2xl space-y-6">
        <div className="flex gap-1.5 flex-wrap">
          {user.roles.map((r) => (
            <Badge
              key={r}
              variant="secondary"
              className="text-xs font-semibold uppercase tracking-wider text-primary"
            >
              {r.replace("_", " ")}
            </Badge>
          ))}
        </div>

        <div className="bg-white border border-border rounded-lg shadow-2xs overflow-hidden">
          <div className="border-b border-border px-6 py-4 bg-muted/40">
            <h2 className="text-base font-semibold text-foreground">
              Informasi Kontak & Biodata
            </h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Kelola informasi kontak dan identitas diri yang diizinkan
              (Allowlist FR-012).
            </p>
          </div>

          <form onSubmit={handleSubmit} className="p-6 space-y-5">
            {/* Read-Only Account Identifiers */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-lg bg-muted/30 border border-border">
              <div>
                <span className="text-[11px] font-semibold text-muted-foreground uppercase flex items-center gap-1.5">
                  <User className="size-3" />
                  <span>Nama Pengguna (Username)</span>
                </span>
                <p className="mt-1 text-sm font-semibold text-foreground">
                  {user.username}
                </p>
              </div>
              <div>
                <span className="text-[11px] font-semibold text-muted-foreground uppercase flex items-center gap-1.5">
                  <Mail className="size-3" />
                  <span>Alamat Email Terdaftar</span>
                </span>
                <p className="mt-1 text-sm font-semibold text-foreground">
                  {user.email}
                </p>
              </div>
            </div>

            {/* Editable Fields */}
            <div>
              <Label
                htmlFor="name"
                className="block text-xs font-semibold text-foreground mb-1.5"
              >
                Nama Lengkap
              </Label>
              <Input
                id="name"
                type="text"
                required
                value={form.data.name}
                onChange={(e) => form.setData("name", e.target.value)}
                className="text-xs"
              />
              {form.errors.name && (
                <p className="mt-1 text-xs text-destructive">
                  {form.errors.name}
                </p>
              )}
            </div>

            <div>
              <Label
                htmlFor="phone"
                className="block text-xs font-semibold text-foreground mb-1.5 flex items-center gap-1.5"
              >
                <Phone className="size-3.5 text-muted-foreground" />
                <span>Nomor Telepon / WhatsApp</span>
              </Label>
              <Input
                id="phone"
                type="text"
                value={form.data.phone}
                onChange={(e) => form.setData("phone", e.target.value)}
                placeholder="Contoh: 08123456789"
                className="text-xs"
              />
              {form.errors.phone && (
                <p className="mt-1 text-xs text-destructive">
                  {form.errors.phone}
                </p>
              )}
            </div>

            <div>
              <Label
                htmlFor="address"
                className="block text-xs font-semibold text-foreground mb-1.5 flex items-center gap-1.5"
              >
                <MapPin className="size-3.5 text-muted-foreground" />
                <span>Alamat Domisili</span>
              </Label>
              <textarea
                id="address"
                rows={3}
                value={form.data.address}
                onChange={(e) => form.setData("address", e.target.value)}
                placeholder="Alamat tempat tinggal lengkap"
                className="w-full px-3 py-2 text-xs rounded-md border border-input bg-background text-foreground focus:border-primary focus:outline-none"
              />
              {form.errors.address && (
                <p className="mt-1 text-xs text-destructive">
                  {form.errors.address}
                </p>
              )}
            </div>

            <div className="pt-3 border-t border-border flex items-center justify-between">
              <span className="text-xs text-muted-foreground">
                Perubahan profil dicatat otomatis ke audit log sistem.
              </span>
              <Button
                type="submit"
                variant="default"
                size="sm"
                disabled={form.processing}
                className="gap-1.5 font-semibold"
              >
                <Check className="size-3.5" />
                <span>
                  {form.processing ? "Menyimpan..." : "Simpan Profil"}
                </span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
