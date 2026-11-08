import React from "react";
import { Link, router } from "@inertiajs/react";
import {
  Check,
  CheckCheck,
  Filter,
  Inbox,
  ArrowLeft,
  Bell,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";

interface NotificationItem {
  id: string;
  event_category: string;
  scope_type?: string;
  scope_id?: string;
  sanitized_summary: string;
  visible_item_count: number;
  read_at: string | null;
  created_at: string;
}

interface PaginationLinks {
  url: string | null;
  label: string;
  active: boolean;
}

interface Props {
  notifications: {
    data: NotificationItem[];
    links: PaginationLinks[];
    total: number;
    current_page: number;
    last_page: number;
  };
  unreadCount: number;
  filters: {
    unread_only?: boolean;
    category?: string;
  };
}

export default function NotificationsIndex({
  notifications,
  unreadCount,
  filters,
}: Props) {
  const handleFilterChange = (key: string, value: any) => {
    router.get(
      "/inbox",
      {
        ...filters,
        [key]: value || undefined,
      },
      {
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  const handleMarkAsRead = (id: string) => {
    router.patch(
      `/notifications/${id}/read-state`,
      {},
      {
        preserveScroll: true,
      },
    );
  };

  const handleMarkAllRead = () => {
    router.post(
      "/notifications/mark-all-read",
      {},
      {
        preserveScroll: true,
      },
    );
  };

  return (
    <AppLayout
      title="Kotak Masuk Notifikasi — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Utilitas Pengguna
            </Badge>
            {unreadCount > 0 && (
              <>
                <span className="text-xs text-muted-foreground">•</span>
                <Badge variant="destructive" className="text-[10px]">
                  {unreadCount} belum dibaca
                </Badge>
              </>
            )}
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Kotak Masuk Notifikasi
          </h1>
        </div>
      }
      actions={
        unreadCount > 0 ? (
          <Button
            variant="outline"
            size="sm"
            onClick={handleMarkAllRead}
            className="gap-1.5 text-xs font-semibold"
          >
            <CheckCheck className="size-4 text-primary" />
            <span>Tandai Semua Dibaca</span>
          </Button>
        ) : undefined
      }
    >
      <div className="space-y-6">
        {/* Filter Tabs */}
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-stone-200 pb-3">
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => handleFilterChange("unread_only", false)}
              className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                !filters.unread_only
                  ? "bg-[#123b30] text-white shadow-sm"
                  : "text-stone-600 hover:bg-stone-100"
              }`}
            >
              Semua ({notifications.total})
            </button>
            <button
              type="button"
              onClick={() => handleFilterChange("unread_only", true)}
              className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                filters.unread_only
                  ? "bg-[#123b30] text-white shadow-sm"
                  : "text-stone-600 hover:bg-stone-100"
              }`}
            >
              Belum Dibaca ({unreadCount})
            </button>
          </div>

          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-stone-400" />
            <select
              value={filters.category || ""}
              onChange={(e) => handleFilterChange("category", e.target.value)}
              className="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs text-stone-700 shadow-sm focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
            >
              <option value="">Semua Kategori</option>
              <option value="USER_MANAGEMENT">Manajemen Akun</option>
              <option value="SYSTEM_SETTING">Pengaturan Sistem</option>
              <option value="ACADEMIC">Akademik</option>
              <option value="ATTENDANCE">Kehadiran</option>
              <option value="GRADING">Penilaian</option>
            </select>
          </div>
        </div>

        {/* Notifications List */}
        <div className="rounded-xl border border-stone-200 bg-white shadow-sm overflow-hidden">
          {notifications.data.length === 0 ? (
            <div className="px-6 py-16 text-center">
              <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 mb-3">
                <Inbox className="h-6 w-6" />
              </div>
              <h3 className="text-sm font-semibold text-stone-800">
                Tidak ada notifikasi
              </h3>
              <p className="mt-1 text-xs text-stone-500 max-w-sm mx-auto">
                {filters.unread_only
                  ? "Hebat! Semua notifikasi telah selesai Anda baca."
                  : "Belum ada catatan aktivitas untuk akun Anda saat ini."}
              </p>
            </div>
          ) : (
            <div className="divide-y divide-stone-100">
              {notifications.data.map((item) => (
                <div
                  key={item.id}
                  className={`flex items-start justify-between gap-4 p-4 transition ${
                    item.read_at === null
                      ? "bg-emerald-50/30 hover:bg-emerald-50/50"
                      : "bg-white hover:bg-stone-50"
                  }`}
                >
                  <div className="flex items-start gap-3 min-w-0">
                    <div className="mt-1 flex h-2 w-2 shrink-0 items-center justify-center">
                      {item.read_at === null ? (
                        <span
                          className="h-2 w-2 rounded-full bg-emerald-600"
                          title="Belum dibaca"
                        ></span>
                      ) : (
                        <span
                          className="h-1.5 w-1.5 rounded-full bg-stone-300"
                          title="Sudah dibaca"
                        ></span>
                      )}
                    </div>

                    <div className="space-y-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="inline-flex items-center rounded px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-stone-100 text-stone-700">
                          {item.event_category}
                        </span>
                        <span className="text-xs text-stone-400">
                          {new Date(item.created_at).toLocaleString("id-ID", {
                            day: "numeric",
                            month: "long",
                            year: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                          })}
                        </span>
                      </div>

                      <p className="text-sm text-stone-800 font-normal leading-relaxed">
                        {item.sanitized_summary}
                      </p>
                    </div>
                  </div>

                  {item.read_at === null && (
                    <button
                      type="button"
                      onClick={() => handleMarkAsRead(item.id)}
                      className="shrink-0 inline-flex items-center gap-1 rounded-lg border border-stone-200 bg-white px-2.5 py-1 text-xs font-medium text-stone-600 shadow-sm transition hover:border-emerald-500 hover:text-emerald-700"
                      title="Tandai telah dibaca"
                    >
                      <Check className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Selesai</span>
                    </button>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Pagination */}
        {notifications.last_page > 1 && (
          <div className="mt-6 flex items-center justify-center gap-1">
            {notifications.links.map((link, idx) => (
              <Link
                key={idx}
                href={link.url || "#"}
                preserveScroll
                className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                  link.active
                    ? "bg-[#123b30] text-white shadow-sm"
                    : link.url
                      ? "bg-white text-stone-700 hover:bg-stone-100 border border-stone-200"
                      : "text-stone-300 cursor-not-allowed"
                }`}
                dangerouslySetInnerHTML={{ __html: link.label }}
              />
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
