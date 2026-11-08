import React from "react";
import { Head, useForm, Link } from "@inertiajs/react";
import {
  ShieldCheck,
  User,
  Lock,
  ArrowRight,
  Sparkles,
  ArrowLeft,
  CheckCircle2,
} from "lucide-react";

interface DemoAccount {
  role: string;
  username: string;
  name: string;
  desc: string;
}

interface Props {
  status?: string;
  demoAccounts: DemoAccount[];
}

export default function Login({ status, demoAccounts }: Props) {
  const { data, setData, post, processing, errors, reset } = useForm({
    username: "",
    password: "",
    remember: true,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post("/login");
  };

  const selectDemoAccount = (username: string) => {
    setData({
      username,
      password: "Password123!",
      remember: true,
    });
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <Head title="Masuk ke Sistem" />

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
          SIA MI Alghivari
        </h2>
        <p className="mt-1 text-center text-xs text-muted-foreground">
          Sistem Informasi Akademik & Rapor Berbasis Integritas
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div className="bg-white py-8 px-6 sm:px-8 border border-border rounded-xl shadow-xs space-y-6">
          {status && (
            <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-800">
              {status}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label
                htmlFor="username"
                className="block text-xs font-medium text-foreground mb-1"
              >
                Nama Pengguna / Email
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <User className="w-4 h-4" />
                </div>
                <input
                  id="username"
                  type="text"
                  value={data.username}
                  onChange={(e) => setData("username", e.target.value)}
                  placeholder="admin, akademik, atau guru.ahmad"
                  autoComplete="username"
                  required
                  className="block w-full pl-9 pr-3 py-2 text-xs rounded-md border border-border bg-canvas text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                />
              </div>
              {errors.username && (
                <p className="mt-1 text-[11px] text-rose-600 font-medium">
                  {errors.username}
                </p>
              )}
            </div>

            <div>
              <div className="flex items-center justify-between mb-1">
                <label
                  htmlFor="password"
                  className="block text-xs font-medium text-foreground"
                >
                  Kata Sandi
                </label>
                <Link
                  href="/forgot-password"
                  className="text-[11px] text-primary hover:underline font-medium"
                >
                  Lupa Kata Sandi?
                </Link>
              </div>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground">
                  <Lock className="w-4 h-4" />
                </div>
                <input
                  id="password"
                  type="password"
                  value={data.password}
                  onChange={(e) => setData("password", e.target.value)}
                  placeholder="••••••••••••"
                  autoComplete="current-password"
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

            <div className="flex items-center justify-between text-xs">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={data.remember}
                  onChange={(e) => setData("remember", e.target.checked)}
                  className="rounded border-border text-primary focus:ring-primary text-xs"
                />
                <span className="text-muted-foreground">Ingat saya</span>
              </label>

              <span className="text-[11px] text-muted-foreground">
                Sandi demo:{" "}
                <code className="font-mono font-semibold text-foreground bg-muted px-1 py-0.5 rounded">
                  Password123!
                </code>
              </span>
            </div>

            <button
              type="submit"
              disabled={processing}
              className="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-md bg-primary text-white text-xs font-medium hover:bg-primary/90 transition shadow-xs disabled:opacity-50"
            >
              <span>{processing ? "Memproses..." : "Masuk ke Sistem"}</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </button>
          </form>

          {/* Quick Demo Switcher */}
          <div className="pt-4 border-t border-border">
            <div className="flex items-center gap-1.5 text-xs font-bold text-foreground mb-3">
              <Sparkles className="w-3.5 h-3.5 text-primary" />
              <span>Akun Demo Portofolio (Klik Cepat)</span>
            </div>
            <div className="grid grid-cols-2 gap-2">
              {demoAccounts.map((acc) => (
                <button
                  key={acc.username}
                  type="button"
                  onClick={() => selectDemoAccount(acc.username)}
                  className={`text-left p-2 rounded-lg border transition text-xs flex flex-col justify-between ${
                    data.username === acc.username
                      ? "border-primary bg-primary/5 text-foreground ring-1 ring-primary"
                      : "border-border bg-canvas hover:bg-muted text-foreground"
                  }`}
                >
                  <div className="flex items-center justify-between gap-1 mb-1">
                    <span className="font-semibold text-[11px] truncate">
                      {acc.role}
                    </span>
                    {data.username === acc.username && (
                      <CheckCircle2 className="w-3 h-3 text-primary shrink-0" />
                    )}
                  </div>
                  <div className="text-[10px] text-muted-foreground font-mono truncate">
                    @{acc.username}
                  </div>
                </button>
              ))}
            </div>
          </div>
        </div>

        <div className="mt-4 text-center">
          <Link
            href="/"
            className="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition"
          >
            <ArrowLeft className="w-3.5 h-3.5" />
            <span>Kembali ke Halaman Beranda</span>
          </Link>
        </div>
      </div>
    </div>
  );
}
