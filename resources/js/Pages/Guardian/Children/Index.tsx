import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import { ArrowRight, GraduationCap, Heart, Search, Users } from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { Input } from "@/Components/ui/input";

interface ChildItem {
  id: number;
  nis: string;
  name: string;
  gender: string;
  relationship: string;
}

interface StudentItem {
  id: number;
  nis: string;
  name: string;
  gender: string;
  status: string;
}

interface Props {
  children?: ChildItem[];
  students?: {
    data: StudentItem[];
    links: { url: string | null; label: string; active: boolean }[];
  };
  is_admin: boolean;
  search?: string;
}

export default function Index({
  children = [],
  students,
  is_admin,
  search: initialSearch = "",
}: Props) {
  const [searchTerm, setSearchTerm] = useState(initialSearch);

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/children",
      { search: searchTerm },
      { preserveState: true, replace: true },
    );
  };

  return (
    <AppLayout
      title={
        is_admin ? "Pengawasan Perkembangan Siswa" : "Perkembangan Belajar Anak"
      }
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              {is_admin ? "Pengawasan Siswa" : "Portal Wali Murid"}
            </Badge>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            {is_admin
              ? "Pengawasan Perkembangan Siswa"
              : "Perkembangan Belajar Anak"}
          </h1>
        </div>
      }
    >
      <div className="space-y-6">
        {!is_admin ? (
          <div>
            <div className="mb-4">
              <p className="text-sm text-muted-foreground">
                Pilih anak untuk memantau catatan kehadiran dan nilai aktivitas
                belajar yang telah dibagikan oleh guru mata pelajaran.
              </p>
            </div>

            {children.length === 0 ? (
              <div className="rounded-lg border border-border bg-white p-12 text-center shadow-2xs">
                <Users className="mx-auto size-12 text-muted-foreground/40 mb-3" />
                <h3 className="text-base font-bold text-foreground">
                  Belum Ada Data Anak Terhubung
                </h3>
                <p className="mt-1 text-xs text-muted-foreground max-w-sm mx-auto">
                  Akun Anda belum ditautkan ke data siswa mana pun. Silakan
                  hubungi bagian Tata Usaha / Admin Madrasah untuk menghubungkan
                  data anak Anda.
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {children.map((child) => (
                  <div
                    key={child.id}
                    className="group rounded-lg border border-border bg-white p-5 shadow-2xs hover:border-primary/40 transition-colors flex flex-col justify-between"
                  >
                    <div>
                      <div className="flex items-center gap-3.5 mb-3">
                        <div className="flex size-11 items-center justify-center rounded-md bg-secondary text-primary font-bold text-sm">
                          {child.gender === "L" ? "L" : "P"}
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <Badge
                              variant="secondary"
                              className="text-[10px] font-bold uppercase tracking-wider text-primary flex items-center gap-1"
                            >
                              <Heart className="size-3" />
                              <span>{child.relationship}</span>
                            </Badge>
                            <span className="text-xs font-mono text-muted-foreground">
                              NIS: {child.nis}
                            </span>
                          </div>
                          <h2 className="mt-1 text-base font-bold text-foreground">
                            {child.name}
                          </h2>
                        </div>
                      </div>
                    </div>

                    <div className="mt-4 border-t border-border pt-4 flex items-center justify-between">
                      <span className="text-xs text-muted-foreground font-medium">
                        Kehadiran & Nilai
                      </span>
                      <Link href={`/children/${child.id}/progress`}>
                        <Button
                          variant="default"
                          size="sm"
                          className="gap-1.5 text-xs font-semibold"
                        >
                          <span>Lihat Perkembangan</span>
                          <ArrowRight className="size-3.5" />
                        </Button>
                      </Link>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        ) : (
          <div className="space-y-4">
            <div className="flex items-center justify-between gap-4 flex-wrap">
              <p className="text-xs text-muted-foreground">
                Pencarian siswa aktif untuk melihat tampilan proyeksi portal
                wali murid.
              </p>
              <form
                onSubmit={handleSearchSubmit}
                className="flex items-center gap-2"
              >
                <div className="relative">
                  <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                  <Input
                    type="text"
                    placeholder="Cari nama atau NIS..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-9 text-xs w-64"
                  />
                </div>
                <Button type="submit" variant="default" size="sm">
                  Cari
                </Button>
              </form>
            </div>

            <div className="rounded-lg border border-border bg-white shadow-2xs overflow-hidden">
              <table className="w-full text-left text-sm border-collapse">
                <thead>
                  <tr className="border-b border-border bg-muted/40 text-xs font-semibold text-muted-foreground">
                    <th className="py-3 px-4">NIS</th>
                    <th className="py-3 px-4">Nama Siswa</th>
                    <th className="py-3 px-4 text-center">L/P</th>
                    <th className="py-3 px-4 text-right">Aksi</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {students?.data.map((stu) => (
                    <tr
                      key={stu.id}
                      className="hover:bg-muted/30 transition-colors"
                    >
                      <td className="py-3 px-4 font-mono text-xs font-semibold text-muted-foreground">
                        {stu.nis}
                      </td>
                      <td className="py-3 px-4 font-bold text-foreground">
                        {stu.name}
                      </td>
                      <td className="py-3 px-4 text-center text-xs text-muted-foreground">
                        {stu.gender}
                      </td>
                      <td className="py-3 px-4 text-right">
                        <Link href={`/children/${stu.id}/progress`}>
                          <Button
                            variant="secondary"
                            size="sm"
                            className="gap-1.5 text-xs"
                          >
                            <span>Portal Wali</span>
                            <ArrowRight className="size-3" />
                          </Button>
                        </Link>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
