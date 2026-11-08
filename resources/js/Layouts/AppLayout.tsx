import React, { useRef, useState } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import {
  BookOpen,
  Calendar,
  FileText,
  ListChecks,
  Menu,
  Users,
  Settings,
  ShieldAlert,
  Sliders,
  Award,
  Inbox,
  User as UserIcon,
  LogOut,
  Home,
  X,
  GraduationCap,
  School,
  FileSpreadsheet,
  UserCheck,
} from "lucide-react";
import { NotificationBell } from "@/Components/NotificationBell";
import { Alert } from "@/Components/ui/alert";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { cn } from "@/Components/ui/utils";

export interface UserProfile {
  id: number;
  name: string;
  username: string;
  email?: string;
  roles: string[];
  is_homeroom?: boolean;
}

export interface ActiveTerm {
  id: number;
  name?: string;
  semester?: number;
  academic_year?: {
    id: number;
    name: string;
  };
}

export interface SharedProps extends Record<string, unknown> {
  appName: string;
  auth: {
    user: UserProfile | null;
  };
  notifications?: {
    unreadCount: number;
  };
  flash?: {
    success: string | null;
    error: string | null;
  };
  academicContext?: {
    activeTerm: ActiveTerm | null;
  };
}

interface NavItem {
  id: string;
  label: string;
  href: string;
  icon: React.ComponentType<{ className?: string; size?: number }>;
  badge?: number;
  match?: (url: string) => boolean;
}

interface NavSection {
  title: string;
  items: NavItem[];
}

export interface ContextHeaderDescriptor {
  title: string;
  badge?: string;
  description?: string;
  totalCount?: number;
}

interface AppLayoutProps {
  title?: string;
  children: React.ReactNode;
  contextHeader?: React.ReactNode | ContextHeaderDescriptor;
  actions?: React.ReactNode;
}

export default function AppLayout({
  title,
  children,
  contextHeader,
  actions,
}: AppLayoutProps) {
  const { auth, notifications, flash, academicContext } =
    usePage<SharedProps>().props;
  const user = auth?.user;
  const url = usePage().url;
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const mainRef = useRef<HTMLElement>(null);

  // Derive role flags
  const roles = user?.roles || [];
  const isMaster = roles.includes("master_admin");
  const isAcademic = roles.includes("academic_admin");
  const isDataEntry = roles.includes("data_entry_admin");
  const isTeacher = roles.includes("teacher");
  const isPrincipal = roles.includes("principal");
  const isGuardian = roles.includes("guardian");
  const isHomeroom = user?.is_homeroom;

  // Academic Period display
  const termName = academicContext?.activeTerm
    ? `${academicContext.activeTerm.semester === 1 ? "Ganjil" : "Genap"} ${
        academicContext.activeTerm.academic_year?.name || ""
      }`
    : "Tahun Ajaran Aktif";

  // Context Role Label
  const roleLabel = isMaster
    ? "Master Admin"
    : isAcademic
      ? "Admin Akademik"
      : isPrincipal
        ? "Kepala Sekolah"
        : isTeacher
          ? isHomeroom
            ? "Guru · Wali Kelas"
            : "Guru Pengajar"
          : isDataEntry
            ? "Admin Data Entry"
            : isGuardian
              ? "Orang Tua / Wali"
              : "Pengguna";

  // Build role-based navigation sections
  const sections: NavSection[] = [];

  // 1. Overview Section
  sections.push({
    title: "Navigasi",
    items: [
      {
        id: "home",
        label: "Beranda Utama",
        href: "/",
        icon: Home,
        match: (u) => u === "/" || u === "",
      },
    ],
  });

  // 2. Guardian Specific Menu
  if (isGuardian && !isMaster) {
    sections.push({
      title: "Portal Akademik Anak",
      items: [
        {
          id: "guardian-children",
          label: "Perkembangan Belajar",
          href: "/children",
          icon: BookOpen,
          match: (u) =>
            u.startsWith("/children") && !u.includes("report-cards"),
        },
        {
          id: "guardian-reports",
          label: "Rapor Semester",
          href: "/children",
          icon: FileText,
          match: (u) => u.includes("report-cards"),
        },
      ],
    });
  }

  // 3. Teacher / Teaching Menu
  if (isTeacher) {
    const teacherItems: NavItem[] = [
      {
        id: "teacher-classrooms",
        label: "Pekerjaan Mengajar",
        href: "/academic/classrooms",
        icon: ListChecks,
        match: (u) =>
          u.startsWith("/academic/classrooms") &&
          (u.includes("assessments") || u.includes("attendance-sessions")),
      },
    ];

    if (isHomeroom) {
      teacherItems.push({
        id: "teacher-reports",
        label: "Rapor Kelas Binaan",
        href: "/academic/classrooms",
        icon: FileSpreadsheet,
        match: (u) => u.includes("report"),
      });
    }

    sections.push({
      title: "Aktivitas Mengajar",
      items: teacherItems,
    });
  }

  // 4. Dedicated Teaching & Subject Assignment Section (Master Admin & Admin Akademik)
  if (isMaster || isAcademic) {
    sections.push({
      title: "Penugasan Mata Pelajaran",
      items: [
        {
          id: "admin-teaching-assignments",
          label: "Penugasan Mata Pelajaran",
          href: "/academic/teaching-assignments",
          icon: UserCheck,
          match: (u) => u.startsWith("/academic/teaching-assignments"),
        },
      ],
    });
  }

  // 5. Academic Administration Menu
  if (isMaster || isAcademic || isDataEntry || isPrincipal) {
    const adminItems: NavItem[] = [];

    if (isMaster || isAcademic || isDataEntry) {
      adminItems.push({
        id: "admin-students",
        label: "Data Siswa & Wali",
        href: "/academic/students",
        icon: GraduationCap,
        match: (u) => u.startsWith("/academic/students"),
      });
    }

    if (isMaster || isAcademic) {
      adminItems.push(
        {
          id: "admin-classrooms",
          label: "Rombongan Kelas",
          href: "/academic/classrooms",
          icon: School,
          match: (u) =>
            u.startsWith("/academic/classrooms") &&
            !u.includes("report-preview") &&
            !u.includes("assessments") &&
            !u.includes("attendance-sessions"),
        },
        {
          id: "admin-years",
          label: "Tahun & Semester",
          href: "/academic/years",
          icon: Calendar,
          match: (u) => u.startsWith("/academic/years"),
        },
        {
          id: "admin-subjects",
          label: "Mata Pelajaran",
          href: "/academic/subjects",
          icon: BookOpen,
          match: (u) => u.startsWith("/academic/subjects"),
        },
      );
    }

    if (isMaster || isAcademic || isPrincipal) {
      adminItems.push({
        id: "admin-policies",
        label: "Aturan Penilaian Global",
        href: "/academic/grading-policies",
        icon: Sliders,
        match: (u) => u.startsWith("/academic/grading-policies"),
      });

      adminItems.push({
        id: "admin-progressions",
        label: isPrincipal
          ? "Tinjau Kenaikan / Kelulusan"
          : "Kenaikan & Kelulusan",
        href: "/academic/progressions",
        icon: Award,
        match: (u) => u.startsWith("/academic/progressions"),
      });
    }

    if (adminItems.length > 0) {
      sections.push({
        title: "Administrasi Akademik",
        items: adminItems,
      });
    }
  }

  // 5. System Administration (Master only)
  if (isMaster) {
    sections.push({
      title: "Kelola Sistem",
      items: [
        {
          id: "admin-users",
          label: "Akses & Akun Pengguna",
          href: "/admin/users",
          icon: Users,
          match: (u) => u.startsWith("/admin/users"),
        },
        {
          id: "admin-settings",
          label: "Pengaturan Sistem",
          href: "/admin/settings",
          icon: Settings,
          match: (u) => u.startsWith("/admin/settings"),
        },
        {
          id: "admin-audit",
          label: "Riwayat & Audit Log",
          href: "/audit-logs",
          icon: ShieldAlert,
          match: (u) => u.startsWith("/audit-logs"),
        },
      ],
    });
  }

  // 6. User Utilities (All roles)
  sections.push({
    title: "Akun & Utilitas",
    items: [
      {
        id: "util-inbox",
        label: "Kotak Masuk",
        href: "/inbox",
        icon: Inbox,
        badge: notifications?.unreadCount,
        match: (u) => u.startsWith("/inbox"),
      },
      {
        id: "util-profile",
        label: "Profil Mandiri",
        href: "/profile",
        icon: UserIcon,
        match: (u) => u.startsWith("/profile"),
      },
    ],
  });

  const checkActive = (item: NavItem) => {
    if (item.match) return item.match(url);
    return url === item.href;
  };

  return (
    <div className="min-h-screen bg-canvas text-foreground flex flex-col">
      {title && <Head title={title} />}

      {/* Skip to Main Content Link for A11y */}
      <a
        className="fixed -top-[100px] left-3 z-[100] bg-background border border-border px-4 py-2 text-sm font-semibold text-primary transition-all focus:top-3 rounded-md shadow-md"
        href="#main"
      >
        Lewati ke isi utama
      </a>

      {/* Main Responsive Grid Layout */}
      <div className="grid min-h-screen grid-cols-[240px_minmax(0,1fr)] max-[1100px]:block">
        {/* DESKTOP SIDEBAR */}
        <aside
          className={cn(
            "sticky top-0 h-screen flex flex-col border-r border-sidebar-border bg-sidebar px-4 py-6 text-sidebar-foreground overflow-y-auto",
            "max-[1100px]:hidden",
          )}
          aria-label="Navigasi Utama"
        >
          {/* School Brand Identity */}
          <div className="px-3 pb-6">
            <Link href="/" className="flex items-center gap-2.5 group">
              <div className="size-9 rounded-md bg-primary flex items-center justify-center text-primary-foreground font-bold text-sm tracking-tight shadow-xs transition-transform group-hover:scale-105">
                MI
              </div>
              <div>
                <p className="text-[17px] font-[650] tracking-[-0.035em] leading-tight text-sidebar-foreground">
                  SIA Alghivari
                </p>
                <span className="text-xs text-sidebar-muted">
                  Register Akademik
                </span>
              </div>
            </Link>
          </div>

          {/* Role Context Pill */}
          <div className="mx-3 mb-5 px-3 py-1.5 rounded-md bg-background border border-sidebar-border text-xs flex items-center justify-between">
            <span className="text-sidebar-muted font-medium">Peran:</span>
            <span
              className="font-semibold text-primary truncate max-w-[130px]"
              title={roleLabel}
            >
              {roleLabel}
            </span>
          </div>

          {/* Navigation Sections */}
          <nav className="flex-1 space-y-6">
            {sections.map((sec, idx) => (
              <div key={idx} className="space-y-1">
                <p className="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-sidebar-muted">
                  {sec.title}
                </p>
                <div className="grid gap-1">
                  {sec.items.map((item) => {
                    const active = checkActive(item);
                    const Icon = item.icon;
                    return (
                      <Link
                        key={item.id}
                        href={item.href}
                        className={cn(
                          "min-h-11 w-full flex items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-normal text-sidebar-foreground transition-colors",
                          "hover:bg-sidebar-hover",
                          active &&
                            "bg-sidebar-accent font-semibold text-sidebar-accent-foreground shadow-2xs hover:bg-sidebar-accent",
                        )}
                        aria-current={active ? "page" : undefined}
                      >
                        <Icon className="size-4 shrink-0" aria-hidden="true" />
                        <span className="truncate flex-1">{item.label}</span>
                        {item.badge !== undefined && item.badge > 0 && (
                          <span className="inline-flex size-5 items-center justify-center rounded-full bg-destructive text-[11px] font-bold text-white">
                            {item.badge > 99 ? "99+" : item.badge}
                          </span>
                        )}
                      </Link>
                    );
                  })}
                </div>
              </div>
            ))}
          </nav>

          {/* Sidebar Footer Context */}
          <div className="mt-6 border-t border-sidebar-border pt-4 text-xs space-y-3">
            <div className="px-3 py-2 rounded-md bg-background/50 border border-sidebar-border/60">
              <p className="font-semibold text-sidebar-foreground truncate">
                {user?.name || "Tamu"}
              </p>
              <span className="block text-sidebar-muted font-mono text-[11px] truncate">
                @{user?.username || "guest"}
              </span>
              <span className="block text-[11px] text-primary font-medium mt-1">
                {termName}
              </span>
            </div>

            {user && (
              <Link
                href="/logout"
                method="post"
                as="button"
                className="w-full flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
              >
                <LogOut className="size-4 shrink-0" />
                <span>Keluar Akun</span>
              </Link>
            )}
          </div>
        </aside>

        {/* MOBILE & TABLET HEADER BAR (≤1100px) */}
        <header className="hidden max-[1100px]:flex items-center justify-between border-b border-sidebar-border bg-sidebar px-4 py-3 sticky top-0 z-40">
          <Link href="/" className="flex items-center gap-2">
            <div className="size-7 rounded bg-primary flex items-center justify-center text-primary-foreground font-bold text-xs">
              MI
            </div>
            <div>
              <p className="text-base font-semibold leading-none text-sidebar-foreground">
                SIA Alghivari
              </p>
              <span className="text-[11px] text-sidebar-muted">
                {roleLabel}
              </span>
            </div>
          </Link>

          <div className="flex items-center gap-2">
            <NotificationBell />
            <Button
              variant="default"
              size="sm"
              onClick={() => setMobileMenuOpen(true)}
              className="gap-1.5 text-xs font-medium"
              aria-label="Buka Menu Navigasi"
            >
              <Menu size={16} />
              <span>Menu</span>
            </Button>
          </div>
        </header>

        {/* MOBILE DRAWER / DIALOG */}
        {mobileMenuOpen && (
          <div className="fixed inset-0 z-50 flex max-[1100px]:block">
            {/* Backdrop */}
            <div
              className="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
              onClick={() => setMobileMenuOpen(false)}
            />

            {/* Sheet Content */}
            <div className="fixed inset-y-0 right-0 w-full max-w-xs bg-sidebar p-6 shadow-xl flex flex-col z-50 overflow-y-auto">
              <div className="flex items-center justify-between pb-6 border-b border-sidebar-border">
                <div>
                  <p className="text-lg font-bold text-sidebar-foreground">
                    Menu SIA Alghivari
                  </p>
                  <p className="text-xs text-sidebar-muted">{roleLabel}</p>
                </div>
                <Button
                  variant="ghost"
                  size="icon-sm"
                  onClick={() => setMobileMenuOpen(false)}
                  aria-label="Tutup Menu"
                >
                  <X size={18} />
                </Button>
              </div>

              <nav className="flex-1 space-y-6 py-6">
                {sections.map((sec, idx) => (
                  <div key={idx} className="space-y-1">
                    <p className="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wider text-sidebar-muted">
                      {sec.title}
                    </p>
                    <div className="grid gap-1">
                      {sec.items.map((item) => {
                        const active = checkActive(item);
                        const Icon = item.icon;
                        return (
                          <Link
                            key={item.id}
                            href={item.href}
                            onClick={() => setMobileMenuOpen(false)}
                            className={cn(
                              "min-h-11 w-full flex items-center gap-3 rounded-md px-3 py-2 text-left text-sm font-normal text-sidebar-foreground transition-colors",
                              active &&
                                "bg-sidebar-accent font-semibold text-sidebar-accent-foreground",
                            )}
                          >
                            <Icon className="size-4 shrink-0" />
                            <span className="truncate flex-1">
                              {item.label}
                            </span>
                            {item.badge !== undefined && item.badge > 0 && (
                              <span className="inline-flex size-5 items-center justify-center rounded-full bg-destructive text-[11px] font-bold text-white">
                                {item.badge}
                              </span>
                            )}
                          </Link>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </nav>

              <div className="border-t border-sidebar-border pt-4">
                <div className="mb-3 px-3 py-2 rounded bg-background border border-sidebar-border text-xs">
                  <p className="font-semibold text-sidebar-foreground">
                    {user?.name}
                  </p>
                  <p className="text-sidebar-muted font-mono">
                    @{user?.username}
                  </p>
                  <p className="text-primary font-medium mt-1">{termName}</p>
                </div>
                {user && (
                  <Link
                    href="/logout"
                    method="post"
                    as="button"
                    onClick={() => setMobileMenuOpen(false)}
                    className="w-full flex items-center gap-2 px-3 py-2 rounded text-sm text-destructive font-medium hover:bg-destructive/10"
                  >
                    <LogOut className="size-4 shrink-0" />
                    <span>Keluar Akun</span>
                  </Link>
                )}
              </div>
            </div>
          </div>
        )}

        {/* MAIN APPLICATION BODY */}
        <div className="min-w-0 flex flex-col">
          {/* Top Navbar Header */}
          <header className="border-b border-border bg-white px-6 py-4 max-[760px]:px-4">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-4">
              <div className="min-w-0">
                {contextHeader ? (
                  React.isValidElement(contextHeader) ? (
                    contextHeader
                  ) : (
                    <div>
                      <div className="flex items-center gap-2">
                        <h1 className="text-xl font-bold tracking-tight text-foreground truncate max-[760px]:text-lg">
                          {(contextHeader as ContextHeaderDescriptor).title}
                        </h1>
                        {(contextHeader as ContextHeaderDescriptor).badge && (
                          <Badge
                            variant="outline"
                            className="text-[11px] font-semibold"
                          >
                            {(contextHeader as ContextHeaderDescriptor).badge}
                          </Badge>
                        )}
                        {(contextHeader as ContextHeaderDescriptor)
                          .totalCount !== undefined && (
                          <span className="text-xs px-2 py-0.5 rounded-md bg-muted text-muted-foreground font-medium">
                            {(
                              contextHeader as ContextHeaderDescriptor
                            ).totalCount?.toLocaleString("id-ID")}
                          </span>
                        )}
                      </div>
                      {(contextHeader as ContextHeaderDescriptor)
                        .description && (
                        <p className="text-xs text-muted-foreground mt-0.5">
                          {
                            (contextHeader as ContextHeaderDescriptor)
                              .description
                          }
                        </p>
                      )}
                    </div>
                  )
                ) : (
                  <div>
                    <h1 className="text-xl font-bold tracking-tight text-foreground truncate max-[760px]:text-lg">
                      {title || "SIA MI Alghivari"}
                    </h1>
                    <p className="text-xs text-muted-foreground">
                      {termName} · Register Akademik
                    </p>
                  </div>
                )}
              </div>

              <div className="flex items-center gap-3 shrink-0">
                {actions}
                <div className="max-[1100px]:hidden">
                  <NotificationBell />
                </div>
              </div>
            </div>
          </header>

          {/* Flash Feedback Notifications */}
          <div className="mx-auto w-full max-w-6xl px-6 pt-4 max-[760px]:px-4">
            {flash?.success && (
              <Alert variant="success" className="mb-4">
                {flash.success}
              </Alert>
            )}
            {flash?.error && (
              <Alert variant="destructive" className="mb-4">
                {flash.error}
              </Alert>
            )}
          </div>

          {/* Primary Viewport */}
          <main
            id="main"
            ref={mainRef}
            tabIndex={-1}
            className="mx-auto w-full max-w-6xl flex-1 px-6 py-6 outline-none max-[760px]:px-4 max-[760px]:py-4"
          >
            {children}
          </main>
        </div>
      </div>
    </div>
  );
}
