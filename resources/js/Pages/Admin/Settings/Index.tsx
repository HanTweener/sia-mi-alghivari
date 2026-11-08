import React from "react";
import { useForm, Link } from "@inertiajs/react";
import {
  Settings,
  Globe,
  Building2,
  Phone,
  ArrowLeft,
  Check,
  AlertCircle,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface Props {
  settings: {
    school_timezone: string;
    school_name: string;
    school_address: string;
    school_phone: string;
  };
  allowedTimezones: Array<{
    value: string;
    label: string;
  }>;
}

export default function Index({ settings, allowedTimezones }: Props) {
  const form = useForm({
    school_timezone: settings.school_timezone,
    school_name: settings.school_name,
    school_address: settings.school_address,
    school_phone: settings.school_phone,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    form.put("/admin/settings");
  };

  return (
    <AppLayout
      title="Pengaturan Sistem — SIA MI Alghivari"
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
              Parameter Sistem
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Pengaturan Global Sistem
          </h1>
        </div>
      }
    >
      <div className="max-w-3xl space-y-6">
        <div className="bg-white border border-border rounded-lg shadow-2xs overflow-hidden">
          <div className="border-b border-border px-6 py-4 bg-muted/40">
            <h2 className="text-base font-semibold text-foreground">
              Parameter Madrasah & Zona Waktu
            </h2>
            <p className="text-xs text-muted-foreground mt-0.5">
              Konfigurasi parameter madrasah dan zona waktu operasional
              (Keputusan D-29).
            </p>
          </div>

          <form onSubmit={handleSubmit} className="p-6 space-y-6">
            {/* School Timezone Configuration */}
            <div className="p-4 rounded-lg bg-muted/30 border border-border space-y-3">
              <div className="flex items-start gap-2.5">
                <Globe className="size-4 text-primary shrink-0 mt-0.5" />
                <div>
                  <Label
                    htmlFor="school_timezone"
                    className="block text-xs font-bold text-foreground"
                  >
                    Zona Waktu Sekolah (School Timezone)
                  </Label>
                  <p className="text-xs text-muted-foreground mt-0.5 leading-relaxed">
                    Satu acuan kalender dan tampilan waktu untuk seluruh
                    pengguna madrasah. Waktu internal server tetap UTC dan
                    diperbarui dinamis tanpa restart container.
                  </p>
                </div>
              </div>

              <NativeSelect
                id="school_timezone"
                value={form.data.school_timezone}
                onChange={(e) =>
                  form.setData("school_timezone", e.target.value)
                }
                containerClassName="w-full"
                className="w-full text-xs font-medium"
              >
                {allowedTimezones.map((tz) => (
                  <option key={tz.value} value={tz.value}>
                    {tz.label}
                  </option>
                ))}
              </NativeSelect>
              {form.errors.school_timezone && (
                <p className="text-xs text-destructive">
                  {form.errors.school_timezone}
                </p>
              )}
            </div>

            {/* School Identity */}
            <div className="space-y-4">
              <div>
                <Label
                  htmlFor="school_name"
                  className="block text-xs font-semibold text-foreground mb-1.5 flex items-center gap-1.5"
                >
                  <Building2 className="size-3.5 text-muted-foreground" />
                  <span>Nama Lembaga / Madrasah</span>
                </Label>
                <Input
                  id="school_name"
                  type="text"
                  required
                  value={form.data.school_name}
                  onChange={(e) => form.setData("school_name", e.target.value)}
                  className="text-xs"
                />
                {form.errors.school_name && (
                  <p className="mt-1 text-xs text-destructive">
                    {form.errors.school_name}
                  </p>
                )}
              </div>

              <div>
                <Label
                  htmlFor="school_phone"
                  className="block text-xs font-semibold text-foreground mb-1.5 flex items-center gap-1.5"
                >
                  <Phone className="size-3.5 text-muted-foreground" />
                  <span>Nomor Telepon Kantor</span>
                </Label>
                <Input
                  id="school_phone"
                  type="text"
                  value={form.data.school_phone}
                  onChange={(e) => form.setData("school_phone", e.target.value)}
                  className="text-xs"
                />
                {form.errors.school_phone && (
                  <p className="mt-1 text-xs text-destructive">
                    {form.errors.school_phone}
                  </p>
                )}
              </div>

              <div>
                <Label
                  htmlFor="school_address"
                  className="block text-xs font-semibold text-foreground mb-1.5"
                >
                  Alamat Resmi Sekolah
                </Label>
                <textarea
                  id="school_address"
                  rows={2}
                  value={form.data.school_address}
                  onChange={(e) =>
                    form.setData("school_address", e.target.value)
                  }
                  className="w-full px-3 py-2 text-xs rounded-md border border-input bg-background text-foreground focus:border-primary focus:outline-none"
                />
                {form.errors.school_address && (
                  <p className="mt-1 text-xs text-destructive">
                    {form.errors.school_address}
                  </p>
                )}
              </div>
            </div>

            <div className="pt-4 border-t border-border flex items-center justify-between">
              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <AlertCircle className="size-3.5" />
                <span>
                  Pengubahan pengaturan ini diaudit secara permanen di database.
                </span>
              </div>
              <Button
                type="submit"
                variant="default"
                size="sm"
                disabled={form.processing}
                className="gap-1.5 font-semibold"
              >
                <Check className="size-3.5" />
                <span>
                  {form.processing ? "Menyimpan..." : "Simpan Pengaturan"}
                </span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </AppLayout>
  );
}
