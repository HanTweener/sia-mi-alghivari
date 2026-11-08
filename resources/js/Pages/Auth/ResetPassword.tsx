import React from "react";
import { Head, useForm, Link } from "@inertiajs/react";
import { Lock, ArrowRight, ArrowLeft, ShieldCheck } from "lucide-react";

interface Props {
  token: string;
  email: string;
}

export default function ResetPassword({ token, email }: Props) {
  const { data, setData, post, processing, errors } = useForm({
    token,
    email,
    password: "",
    password_confirmation: "",
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post("/reset-password");
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <Head title="Atur Ulang Kata Sandi" />

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
          Atur Ulang Kata Sandi
        </h2>
        <p className="mt-1 text-center text-xs text-muted-foreground">
          Masukkan kata sandi baru untuk akun Anda
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-8 border border-border rounded-xl shadow-xs space-y-6">
          <div className="p-3.5 rounded-lg bg-primary/5 border border-primary/20 text-xs text-foreground flex items-start gap-2.5">
            <ShieldCheck className="w-4 h-4 text-primary shrink-0 mt-0.5" />
            <div>
              <p className="font-semibold">Otorisasi Berhasil Divalidasi</p>
              <p className="mt-0.5 text-muted-foreground">
                Memperbarui akun untuk:{" "}
                <span className="font-mono font-medium text-foreground">
                  {email}
                </span>
              </p>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <input type="hidden" name="token" value={data.token} />
            <input type="hidden" name="email" value={data.email} />

            <div>
              <label
                htmlFor="password"
                className="block text-xs font-medium text-foreground mb-1"
              >
                Kata Sandi Baru
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <Lock className="w-4 h-4" />
                </div>
                <input
                  id="password"
                  type="password"
                  value={data.password}
                  onChange={(e) => setData("password", e.target.value)}
                  placeholder="Minimal 8 karakter"
                  autoComplete="new-password"
                  required
                  className="block w-full pl-9 pr-3 py-2 text-xs rounded-md border border-border bg-canvas text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                />
              </div>
              {errors.password && (
                <p className="mt-1 text-[11px] text-rose-600 font-medium">
                  {errors.password}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="password_confirmation"
                className="block text-xs font-medium text-foreground mb-1"
              >
                Konfirmasi Kata Sandi Baru
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <Lock className="w-4 h-4" />
                </div>
                <input
                  id="password_confirmation"
                  type="password"
                  value={data.password_confirmation}
                  onChange={(e) =>
                    setData("password_confirmation", e.target.value)
                  }
                  placeholder="Ulangi kata sandi baru"
                  autoComplete="new-password"
                  required
                  className="block w-full pl-9 pr-3 py-2 text-xs rounded-md border border-border bg-canvas text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                />
              </div>
              {errors.password_confirmation && (
                <p className="mt-1 text-[11px] text-rose-600 font-medium">
                  {errors.password_confirmation}
                </p>
              )}
            </div>

            {errors.token && (
              <p className="text-[11px] text-rose-600 font-medium">
                {errors.token}
              </p>
            )}

            <button
              type="submit"
              disabled={processing}
              className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-md bg-primary text-white text-xs font-medium hover:bg-primary/90 transition shadow-xs disabled:opacity-50"
            >
              <span>
                {processing ? "Menyimpan..." : "Simpan Kata Sandi Baru"}
              </span>
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
