import React from "react";
import { Head, useForm } from "@inertiajs/react";
import { LogOut, CheckCircle2, ShieldCheck, Laptop } from "lucide-react";

interface Props {
  email: string;
}

export default function ResetSessionChoice({ email }: Props) {
  const { data, setData, post, processing } = useForm({
    choice: "revoke_others",
  });

  const handleChoiceSubmit = (
    selectedChoice: "revoke_others" | "keep_others",
  ) => {
    setData("choice", selectedChoice);
    post("/reset-password/session-choice");
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <Head title="Konfirmasi Sesi Perangkat" />

      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center">
          <div className="w-12 h-12 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-2xl shadow-sm">
            MI
          </div>
        </div>
        <h2 className="mt-4 text-center text-2xl font-extrabold tracking-tight text-foreground">
          Kata Sandi Berhasil Disimpan
        </h2>
        <p className="mt-1 text-center text-xs text-muted-foreground">
          Konfirmasi Keamanan Sesi Perangkat (D-25)
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-8 border border-border rounded-xl shadow-xs space-y-6">
          <div className="p-3.5 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-900 flex items-start gap-2.5">
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold">Pembaruan Kata Sandi Selesai</p>
              <p className="mt-0.5 text-emerald-800">
                Akun <span className="font-mono font-medium">{email}</span> kini
                telah memiliki kata sandi baru.
              </p>
            </div>
          </div>

          <div className="space-y-3">
            <p className="text-xs text-muted-foreground leading-relaxed">
              Demi keamanan akun madrasah Anda, silakan tentukan apakah Anda
              ingin mencabut akses sesi pada perangkat lain:
            </p>

            <button
              type="button"
              disabled={processing}
              onClick={() => handleChoiceSubmit("revoke_others")}
              className="w-full text-left p-3.5 rounded-xl border border-primary bg-primary/5 hover:bg-primary/10 transition group"
            >
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-lg bg-primary text-white flex items-center justify-center shrink-0 mt-0.5">
                  <LogOut className="w-4 h-4" />
                </div>
                <div>
                  <div className="text-xs font-bold text-foreground flex items-center gap-1.5">
                    <span>Cabut Sesi di Perangkat Lain</span>
                    <span className="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded">
                      Disarankan
                    </span>
                  </div>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    Keluarkan akun ini secara paksa dari browser dan perangkat
                    lain yang sedang login.
                  </p>
                </div>
              </div>
            </button>

            <button
              type="button"
              disabled={processing}
              onClick={() => handleChoiceSubmit("keep_others")}
              className="w-full text-left p-3.5 rounded-xl border border-border bg-canvas hover:bg-muted transition group"
            >
              <div className="flex items-start gap-3">
                <div className="w-8 h-8 rounded-lg bg-muted text-muted-foreground flex items-center justify-center shrink-0 mt-0.5">
                  <Laptop className="w-4 h-4" />
                </div>
                <div>
                  <div className="text-xs font-bold text-foreground">
                    Pertahankan Sesi yang Ada
                  </div>
                  <p className="text-[11px] text-muted-foreground mt-0.5">
                    Biarkan sesi login di perangkat lain tetap aktif tanpa
                    melakukan logout otomatis.
                  </p>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
