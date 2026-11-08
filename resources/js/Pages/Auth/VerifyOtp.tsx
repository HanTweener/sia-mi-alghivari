import React from "react";
import { Head, useForm, Link } from "@inertiajs/react";
import { KeyRound, ArrowRight, ArrowLeft, Clock } from "lucide-react";

interface Props {
  email: string;
  status?: string;
}

export default function VerifyOtp({ email, status }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    email,
    otp: "",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post("/reset-password/verify");
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <Head title="Verifikasi Kode OTP" />

      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="flex justify-center">
          <Link
            href="/"
            className="w-12 h-12 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-2xl shadow-sm hover:bg-primary/90 transition"
          >
            MI
          </Link>
        </div>
        <h2 className="mt-4 text-center text-2xl font-extrabold tracking-tight text-foreground">
          Verifikasi Kode OTP
        </h2>
        <p className="mt-1 text-center text-xs text-muted-foreground">
          Masukkan 6 digit kode yang dikirimkan ke email Anda
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-8 border border-border rounded-xl shadow-xs space-y-6">
          <div className="p-3.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
            <Clock className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold">Kode berlaku selama 5 menit</p>
              <p className="mt-0.5 text-amber-800">
                Email tujuan:{" "}
                <span className="font-mono font-medium">{email}</span>
              </p>
            </div>
          </div>

          {status && (
            <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
              {status}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <input type="hidden" name="email" value={data.email} />

            <div>
              <label
                htmlFor="otp"
                className="block text-xs font-medium text-foreground mb-1"
              >
                Kode Verifikasi OTP (6 Digit)
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <KeyRound className="w-4 h-4" />
                </div>
                <input
                  id="otp"
                  type="text"
                  maxLength={10}
                  value={data.otp}
                  onChange={(e) => setData("otp", e.target.value.trim())}
                  placeholder="123456"
                  autoFocus
                  required
                  className="block w-full pl-9 pr-3 py-2 text-center tracking-widest text-lg font-mono font-bold rounded-md border border-border bg-canvas text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                />
              </div>
              {errors.otp && (
                <p className="mt-1 text-[11px] text-rose-600 font-medium">
                  {errors.otp}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={processing}
              className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-md bg-primary text-white text-xs font-medium hover:bg-primary/90 transition shadow-xs disabled:opacity-50"
            >
              <span>
                {processing ? "Memverifikasi..." : "Verifikasi & Lanjutkan"}
              </span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </form>

          <div className="pt-2 text-center text-xs text-muted-foreground">
            <span>Tidak menerima kode? </span>
            <Link
              href="/forgot-password"
              className="text-primary hover:underline font-medium"
            >
              Kirim ulang OTP
            </Link>
          </div>
        </div>

        <div className="mt-4 text-center">
          <Link
            href="/login"
            className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Kembali ke Halaman Masuk</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
