import React from "react";
import { Head, useForm } from "@inertiajs/react";
import { KeyRound, ShieldAlert, LogOut, CheckCircle2 } from "lucide-react";

interface Props {
  mustChangePassword: boolean;
  pendingSessionChoice: boolean;
}

export default function SetupPassword({
  mustChangePassword,
  pendingSessionChoice,
}: Props) {
  const passwordForm = useForm({
    password: "",
    password_confirmation: "",
  });

  const sessionForm = useForm({
    choice: "revoke_others",
  });

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    passwordForm.post("/auth/setup-password");
  };

  const handleSessionChoiceSubmit = (
    choice: "revoke_others" | "keep_others",
  ) => {
    sessionForm.setData("choice", choice);
    sessionForm.post("/auth/setup-password/session-choice");
  };

  return (
    <div className="min-h-screen bg-canvas flex items-center justify-center p-4">
      <Head title="Pembaruan Kata Sandi Awal" />

      {/* Main Card */}
      <div className="w-full max-w-md bg-white border border-border rounded-xl shadow-xs overflow-hidden p-6 sm:p-8">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 rounded-lg bg-muted text-primary flex items-center justify-center">
            <KeyRound className="w-5 h-5" />
          </div>
          <div>
            <h1 className="text-xl font-bold text-foreground">
              Aktivasi Kata Sandi
            </h1>
            <p className="text-xs text-muted-foreground">
              Kebijakan Keamanan Akun Baru (D-25)
            </p>
          </div>
        </div>

        {mustChangePassword && !pendingSessionChoice && (
          <form onSubmit={handlePasswordSubmit} className="space-y-4">
            <div className="p-3.5 rounded-lg bg-warning/20 border border-toolbar-border text-xs text-warning-foreground flex items-start gap-2.5">
              <ShieldAlert className="w-4 h-4 shrink-0 mt-0.5" />
              <span>
                Akun Anda dibuat dengan kata sandi sementara. Anda wajib
                menggantinya sebelum mengakses fitur madrasah.
              </span>
            </div>

            <div>
              <label className="block text-xs font-semibold text-foreground mb-1.5">
                Kata Sandi Baru
              </label>
              <input
                type="password"
                required
                value={passwordForm.data.password}
                onChange={(e) =>
                  passwordForm.setData("password", e.target.value)
                }
                placeholder="Minimal 8 karakter"
                className="w-full px-3 py-2 text-sm rounded-md border border-border bg-input-surface text-foreground focus:outline-hidden focus:ring-2 focus:ring-primary focus:border-primary"
              />
              {passwordForm.errors.password && (
                <p className="mt-1 text-xs text-destructive">
                  {passwordForm.errors.password}
                </p>
              )}
            </div>

            <div>
              <label className="block text-xs font-semibold text-foreground mb-1.5">
                Konfirmasi Kata Sandi Baru
              </label>
              <input
                type="password"
                required
                value={passwordForm.data.password_confirmation}
                onChange={(e) =>
                  passwordForm.setData("password_confirmation", e.target.value)
                }
                placeholder="Ulangi kata sandi baru"
                className="w-full px-3 py-2 text-sm rounded-md border border-border bg-input-surface text-foreground focus:outline-hidden focus:ring-2 focus:ring-primary focus:border-primary"
              />
            </div>

            <button
              type="submit"
              disabled={passwordForm.processing}
              className="w-full mt-2 py-2.5 px-4 bg-primary text-white rounded-md text-sm font-semibold hover:bg-primary/90 transition disabled:opacity-50"
            >
              {passwordForm.processing ? "Menyimpan..." : "Simpan & Lanjutkan"}
            </button>
          </form>
        )}

        {/* Mandatory Post-Commit Session Modal (D-25) */}
        {pendingSessionChoice && (
          <div className="space-y-5">
            <div className="p-4 rounded-lg bg-success/20 border border-success-foreground/30 text-xs text-success-foreground flex items-start gap-2.5">
              <CheckCircle2 className="w-4 h-4 shrink-0 mt-0.5 text-primary" />
              <span>
                Kata sandi baru berhasil disimpan ke server. Konfirmasikan
                pengelolaan sesi perangkat Anda.
              </span>
            </div>

            <div className="text-sm text-foreground">
              <p className="font-semibold text-base mb-1">
                Apakah Anda ingin keluar dari perangkat lain?
              </p>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Pilih <strong>Ya</strong> untuk mencabut seluruh sesi login yang
                ada di perangkat lain demi keamanan, atau <strong>Tidak</strong>{" "}
                untuk tetap mempertahankan sesi aktif di perangkat lain.
                Perangkat saat ini akan tetap login.
              </p>
            </div>

            <div className="grid grid-cols-2 gap-3 pt-2">
              <button
                type="button"
                disabled={sessionForm.processing}
                onClick={() => handleSessionChoiceSubmit("revoke_others")}
                className="flex items-center justify-center gap-2 py-2.5 px-3 rounded-md bg-destructive text-white text-xs font-semibold hover:bg-destructive/90 transition shadow-xs disabled:opacity-50"
              >
                <LogOut className="w-3.5 h-3.5" />
                <span>Ya, Keluar Perangkat Lain</span>
              </button>

              <button
                type="button"
                disabled={sessionForm.processing}
                onClick={() => handleSessionChoiceSubmit("keep_others")}
                className="py-2.5 px-3 rounded-md bg-white border border-border text-foreground text-xs font-semibold hover:bg-muted transition shadow-xs disabled:opacity-50"
              >
                Tidak, Pertahankan
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
