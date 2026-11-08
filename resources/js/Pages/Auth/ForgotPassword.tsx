import React from "react";
import { Head, useForm, Link } from "@inertiajs/react";
import { Mail, ArrowRight, ArrowLeft, ShieldCheck } from "lucide-react";

interface Props {
  status?: string;
}

export default function ForgotPassword({ status }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    email: "",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post("/forgot-password");
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <Head title="Lupa Kata Sandi" />

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
          Pemulihan Sandi Mandiri
        </h2>
        <p className="mt-1 text-center text-xs text-muted-foreground">
          Sistem Verifikasi Kode OTP Email Resmi (D-25)
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-8 border border-border rounded-xl shadow-xs space-y-6">
          <div className="p-3.5 rounded-lg bg-primary/5 border border-primary/20 text-xs text-foreground flex items-start gap-2.5">
            <ShieldCheck className="w-4 h-4 text-primary shrink-0 mt-0.5" />
            <span>
              Masukkan alamat email resmi Anda yang terdaftar pada sistem
              madrasah. Kami akan mengirimkan kode verifikasi OTP 6 digit yang
              berlaku selama 5 menit.
            </span>
          </div>

          {status && (
            <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
              {status}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label
                htmlFor="email"
                className="block text-xs font-medium text-foreground mb-1"
              >
                Alamat Email Terdaftar
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <Mail className="w-4 h-4" />
                </div>
                <input
                  id="email"
                  type="email"
                  value={data.email}
                  onChange={(e) => setData("email", e.target.value)}
                  placeholder="nama@alghivari.sch.id"
                  autoComplete="email"
                  required
                  className="block w-full pl-9 pr-3 py-2 text-xs rounded-md border border-border bg-canvas text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                />
              </div>
              {errors.email && (
                <p className="mt-1 text-[11px] text-rose-600 font-medium">
                  {errors.email}
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={processing}
              className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-md bg-primary text-white text-xs font-medium hover:bg-primary/90 transition shadow-xs disabled:opacity-50"
            >
              <span>{processing ? "Mengirim Kode..." : "Kirim Kode OTP"}</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </form>
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
