import React from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import {
  ShieldCheck,
  BookOpen,
  Calendar,
  Users,
  LogIn,
  ChevronRight,
  GraduationCap,
  ListChecks,
  Sliders,
  Award,
  Settings,
  ShieldAlert,
  ArrowRight,
  CheckCircle2,
  FileSpreadsheet,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";

export default function Welcome({
  appName,
  environment,
}: {
  appName: string;
  environment: string;
}) {
  const { auth, academicContext } = usePage<SharedProps>().props;
  const user = auth?.user;

  const roles = user?.roles || [];
  const isMaster = roles.includes("master_admin");
  const isAcademic = roles.includes("academic_admin");
  const isDataEntry = roles.includes("data_entry_admin");
  const isTeacher = roles.includes("teacher");
  const isPrincipal = roles.includes("principal");
  const isGuardian = roles.includes("guardian");
  const isHomeroom = user?.is_homeroom;

  const termText = academicContext?.activeTerm
    ? `${academicContext.activeTerm.semester === 1 ? "Ganjil" : "Genap"} ${
        academicContext.activeTerm.academic_year?.name || ""
      }`
    : "Tahun Ajaran Aktif";

  // If user is authenticated, render inside AppLayout with Dashboard view
  if (user) {
    return (
      <AppLayout title="Beranda Akademik">
        <div className="space-y-8">
          {/* Welcome Banner */}
          <div className="rounded-lg border border-border bg-white p-6 shadow-2xs">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <div className="flex items-center gap-2 mb-1.5">
                  <Badge
                    variant="secondary"
                    className="font-semibold text-xs text-primary"
                  >
                    {isMaster
                      ? "Master Admin"
                      : isAcademic
                        ? "Admin Akademik"
                        : isPrincipal
                          ? "Kepala Sekolah"
                          : isTeacher
                            ? isHomeroom
                              ? "Guru & Wali Kelas"
                              : "Guru Pengajar"
                            : isDataEntry
                              ? "Admin Data Entry"
                              : isGuardian
                                ? "Orang Tua / Wali Murid"
                                : "Pengguna Aktif"}
                  </Badge>
                  <span className="text-xs text-muted-foreground">•</span>
                  <span className="text-xs text-muted-foreground">
                    {termText}
                  </span>
                </div>
                <h1 className="text-2xl font-bold tracking-tight text-foreground">
                  Selamat Datang, {user.name}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                  Kelola aktivitas pembelajaran, administrasi kurikulum, dan
                  rekapitulasi penilaian pada portal register akademik.
                </p>
              </div>

              <div className="flex items-center gap-3">
                <Link href="/profile">
                  <Button variant="outline" size="sm">
                    Profil Mandiri
                  </Button>
                </Link>
                {isTeacher && (
                  <Link href="/academic/classrooms">
                    <Button variant="default" size="sm" className="gap-1.5">
                      <span>Mulai Mengajar</span>
                      <ArrowRight className="size-4" />
                    </Button>
                  </Link>
                )}
                {isGuardian && (
                  <Link href="/children">
                    <Button variant="default" size="sm" className="gap-1.5">
                      <span>Lihat Perkembangan Anak</span>
                      <ArrowRight className="size-4" />
                    </Button>
                  </Link>
                )}
                {(isMaster || isAcademic) && (
                  <Link href="/academic/students">
                    <Button variant="default" size="sm" className="gap-1.5">
                      <span>Data Siswa</span>
                      <ArrowRight className="size-4" />
                    </Button>
                  </Link>
                )}
              </div>
            </div>
          </div>

          {/* Role-Specific Quick Navigation Modules */}
          <div>
            <h2 className="text-base font-semibold text-foreground mb-4">
              Akses Cepat Tugas & Modul
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {/* Teaching Module (Teacher or Master) */}
              {(isTeacher || isMaster) && (
                <Link
                  href="/academic/classrooms"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <ListChecks className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Pekerjaan Mengajar
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Catat nilai ulangan harian, tugas, PTS/PAS dan pengesahan
                      presensi kehadiran per pertemuan.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Buka Rombel Mengajar</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Homeroom / Rapor Module */}
              {(isHomeroom || isMaster) && (
                <Link
                  href="/academic/classrooms"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <FileSpreadsheet className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Persiapan Rapor Binaan
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Tinjau kelengkapan nilai mapel, finalisasi snapshot rapor
                      semester, dan cetak PDF resmi.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Periksa Kelas Binaan</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Guardian Progress Module */}
              {isGuardian && (
                <Link
                  href="/children"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <BookOpen className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Perkembangan Belajar Anak
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Pantau aktivitas nilai asesmen yang dibagikan dan rekaman
                      kehadiran anak di madrasah.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Lihat Aktivitas Anak</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Students Directory (Admin / Data Entry / Master) */}
              {(isMaster || isAcademic || isDataEntry) && (
                <Link
                  href="/academic/students"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <GraduationCap className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Data Siswa & Wali Murid
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Pendaftaran siswa baru, pencatatan relasi orang tua/wali,
                      serta riwayat penempatan rombel.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Kelola Data Siswa</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Grading Policy Module */}
              {(isMaster || isAcademic || isPrincipal) && (
                <Link
                  href="/academic/grading-policies"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <Sliders className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Aturan Penilaian Global
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Komposisi bobot komponen nilai (tugas, ulangan, PTS/PAS,
                      kehadiran) dan KKM standar madrasah.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Lihat Kebijakan Nilai</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Progression / Graduation Module */}
              {(isMaster || isAcademic || isPrincipal) && (
                <Link
                  href="/academic/progressions"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <Award className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Kenaikan Kelas & Kelulusan
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Pengajuan usulan kenaikan rombel, review kelayakan nilai
                      akhir, dan persetujuan kepala sekolah.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Tinjau Kenaikan</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* User Accounts Management (Master only) */}
              {isMaster && (
                <Link
                  href="/admin/users"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <Users className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Akses & Akun Pengguna
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Pengelolaan akun guru, admin, kepala sekolah, dan orang
                      tua beserta pengaturan hak peran multi-role.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Kelola Akun</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}

              {/* Audit Logs (Master or Principal) */}
              {(isMaster || isPrincipal) && (
                <Link
                  href="/audit-logs"
                  className="group p-5 rounded-lg border border-border bg-white hover:border-primary/40 hover:bg-muted/40 transition-colors shadow-2xs flex flex-col justify-between"
                >
                  <div>
                    <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                      <ShieldAlert className="size-5" />
                    </div>
                    <h3 className="font-semibold text-sm text-foreground">
                      Riwayat & Audit Log
                    </h3>
                    <p className="text-xs text-muted-foreground mt-1">
                      Catatan jejak rekam perubahan nilai, finalisasi rapor, dan
                      aktivitas keamanan sistem.
                    </p>
                  </div>
                  <div className="mt-4 flex items-center gap-1 text-xs font-semibold text-primary">
                    <span>Lihat Jejak Audit</span>
                    <ChevronRight className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                  </div>
                </Link>
              )}
            </div>
          </div>

          {/* System Integrity Assurance */}
          <div className="rounded-lg border border-border bg-white p-5 flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="size-9 rounded-md bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <ShieldCheck className="size-5" />
              </div>
              <div>
                <p className="text-xs font-semibold text-foreground">
                  Integritas Akademik & Keamanan Data
                </p>
                <p className="text-[11px] text-muted-foreground mt-0.5">
                  Arsitektur atomic locking, audit snapshot deterministik, dan
                  otorisasi ketat berbasis peran madrasah.
                </p>
              </div>
            </div>
            <Badge variant="outline" className="text-xs font-medium shrink-0">
              {environment}
            </Badge>
          </div>
        </div>
      </AppLayout>
    );
  }

  // Guest Visitor Public Portal Landing
  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col justify-between">
      <Head title="Sistem Informasi Akademik — MI Alghivari" />

      {/* Top Header */}
      <header className="border-b border-border bg-white px-6 py-4">
        <div className="max-w-6xl mx-auto flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="size-9 rounded-md bg-primary flex items-center justify-center text-white font-bold text-base shadow-xs">
              MI
            </div>
            <div>
              <h1 className="text-base font-bold text-foreground leading-tight">
                {appName}
              </h1>
              <p className="text-[11px] text-muted-foreground">
                Register Akademik & Rapor Berbasis Integritas
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <Link href="/login">
              <Button
                variant="default"
                size="sm"
                className="gap-1.5 font-medium"
              >
                <LogIn className="size-4" />
                <span>Masuk ke Akun</span>
              </Button>
            </Link>
          </div>
        </div>
      </header>

      {/* Hero Section */}
      <main className="max-w-6xl mx-auto px-6 py-16 flex-1 w-full flex flex-col justify-center">
        <div className="max-w-2xl">
          <Badge
            variant="secondary"
            className="mb-4 gap-1.5 text-xs text-primary font-medium"
          >
            <ShieldCheck className="size-3.5" />
            <span>Fondasi Terverifikasi Mutlak · Register Akademik</span>
          </Badge>

          <h2 className="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl leading-tight">
            Pengelolaan Akademik & Rapor Madrasah Ibtidaiyah
          </h2>
          <p className="mt-4 text-base text-muted-foreground leading-relaxed">
            Sistem Informasi Akademik MI Alghivari menghadirkan pencatatan
            penilaian mata pelajaran, pengesahan presensi kehadiran, pemantauan
            perkembangan anak untuk orang tua, dan penerbitan rapor semester
            yang diaudit secara atomik.
          </p>

          <div className="mt-8 flex flex-wrap items-center gap-4">
            <Link href="/login">
              <Button variant="default" size="lg" className="gap-2">
                <span>Masuk dengan Akun Sekolah</span>
                <ArrowRight className="size-4" />
              </Button>
            </Link>
          </div>
        </div>

        {/* Feature Cards Grid */}
        <div className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="p-6 rounded-lg bg-white border border-border shadow-2xs">
            <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-4">
              <ListChecks className="size-5" />
            </div>
            <h3 className="text-base font-semibold text-foreground">
              Satu Layar Penilaian
            </h3>
            <p className="mt-2 text-sm text-muted-foreground">
              Input nilai yang tenang, perlakuan jelas antara nilai 0 dengan
              belum diisi (blank), serta validasi desimal terstandar.
            </p>
          </div>

          <div className="p-6 rounded-lg bg-white border border-border shadow-2xs">
            <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-4">
              <BookOpen className="size-5" />
            </div>
            <h3 className="text-base font-semibold text-foreground">
              Portal Perkembangan Orang Tua
            </h3>
            <p className="mt-2 text-sm text-muted-foreground">
              Wali murid dapat langsung memantau capaian belajar anak secara
              transparan tanpa menunggu terbitnya rapor akhir semester.
            </p>
          </div>

          <div className="p-6 rounded-lg bg-white border border-border shadow-2xs">
            <div className="size-10 rounded-md bg-secondary text-primary flex items-center justify-center mb-4">
              <ShieldAlert className="size-5" />
            </div>
            <h3 className="text-base font-semibold text-foreground">
              Audit & Kunci Atomik
            </h3>
            <p className="mt-2 text-sm text-muted-foreground">
              Penerbitan rapor terlindungi dari perubahan nilai tak terduga
              dengan snapshot versi deterministik dan jejak audit lengkap.
            </p>
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer className="border-t border-border bg-white py-6 text-center text-xs text-muted-foreground">
        <p>© 2026 {appName}. Seluruh hak cipta dilindungi.</p>
      </footer>
    </div>
  );
}
