import React, { useState, useEffect, useRef, useCallback } from "react";
import { usePage, Link } from "@inertiajs/react";
import { Bell, Check, ExternalLink, Inbox } from "lucide-react";
import { createEchoClient } from "@/echo";

interface NotificationItem {
  id: string;
  event_category: string;
  scope_type?: string;
  sanitized_summary: string;
  read_at: string | null;
  created_at: string;
}

export const NotificationBell: React.FC = () => {
  const { auth, notifications: sharedNotifications } = usePage<any>().props;
  const user = auth?.user;

  const [isOpen, setIsOpen] = useState(false);
  const [unreadCount, setUnreadCount] = useState<number>(
    sharedNotifications?.unreadCount ?? 0,
  );
  const [items, setItems] = useState<NotificationItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [loadFailed, setLoadFailed] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const latestRequest = useRef(0);

  // Sync from Inertia props
  useEffect(() => {
    if (sharedNotifications?.unreadCount !== undefined) {
      setUnreadCount(sharedNotifications.unreadCount);
    }
  }, [sharedNotifications?.unreadCount]);

  const fetchRecentNotifications = useCallback(async () => {
    const requestId = ++latestRequest.current;
    setItems([]);
    setUnreadCount(0);
    setLoadFailed(false);
    if (!user?.id) return;
    setIsLoading(true);
    try {
      const res = await fetch("/notifications", {
        headers: { Accept: "application/json" },
        signal: AbortSignal.timeout(10000),
      });
      if (!res.ok) throw new Error("Inbox unavailable");
      if (res.ok && requestId === latestRequest.current) {
        const data = await res.json();
        if (requestId !== latestRequest.current) return;
        setItems(data.notifications || []);
        setUnreadCount(data.unread_count ?? 0);
      }
    } catch {
      // Keep stale private summaries cleared when revalidation fails.
      if (requestId === latestRequest.current) setLoadFailed(true);
    } finally {
      if (requestId === latestRequest.current) setIsLoading(false);
    }
  }, [user?.id]);

  useEffect(() => {
    const handleFocus = () => {
      void fetchRecentNotifications();
    };

    handleFocus();
    window.addEventListener("focus", handleFocus);
    return () => {
      latestRequest.current++;
      window.removeEventListener("focus", handleFocus);
    };
  }, [fetchRecentNotifications]);

  // WebSocket Realtime Echo Listener
  useEffect(() => {
    if (!user?.id) return;

    let echoInstance: any = null;
    try {
      echoInstance = createEchoClient();
      window.Echo = echoInstance;

      const channel = echoInstance.private(`users.${user.id}`);

      channel.listen(".inbox.changed.v1", () => {
        void fetchRecentNotifications();
      });
    } catch {
      // Echo connection failure must never crash UI
    }

    return () => {
      if (echoInstance && user?.id) {
        echoInstance.leave(`users.${user.id}`);
      }
    };
  }, [user?.id, fetchRecentNotifications]);

  // Close on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node)
      ) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isOpen]);

  const handleToggle = () => {
    if (!isOpen) {
      fetchRecentNotifications();
    }
    setIsOpen(!isOpen);
  };

  const handleMarkAsRead = async (id: string, e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      const res = await fetch(`/notifications/${id}/read-state`, {
        method: "PATCH",
        signal: AbortSignal.timeout(10000),
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN":
            (
              document.querySelector(
                'meta[name="csrf-token"]',
              ) as HTMLMetaElement
            )?.content || "",
        },
      });

      if (res.ok) {
        await fetchRecentNotifications();
      } else if (res.status === 403 || res.status === 404) {
        await fetchRecentNotifications();
      }
    } catch {
      // Ignore failure
    }
  };

  const handleMarkAllRead = async () => {
    try {
      const res = await fetch("/notifications/mark-all-read", {
        method: "POST",
        signal: AbortSignal.timeout(10000),
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-CSRF-TOKEN":
            (
              document.querySelector(
                'meta[name="csrf-token"]',
              ) as HTMLMetaElement
            )?.content || "",
        },
      });

      if (res.ok) {
        await fetchRecentNotifications();
      }
    } catch {
      // Ignore failure
    }
  };

  if (!user) return null;

  return (
    <div className="relative" ref={dropdownRef}>
      <button
        type="button"
        onClick={handleToggle}
        className="relative rounded-lg p-2 text-stone-600 transition hover:bg-emerald-50 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-600"
        aria-label="Notifikasi"
      >
        <Bell className="h-5 w-5" />
        {unreadCount > 0 && (
          <span className="absolute top-1 right-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-emerald-600 px-1 text-[10px] font-semibold text-white ring-2 ring-white">
            {unreadCount > 99 ? "99+" : unreadCount}
          </span>
        )}
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl border border-stone-200 bg-white shadow-xl z-50 overflow-hidden">
          <div className="flex items-center justify-between border-b border-stone-100 bg-stone-50/80 px-4 py-3">
            <div className="flex items-center gap-2">
              <span className="text-sm font-semibold text-stone-900">
                Notifikasi
              </span>
              {unreadCount > 0 && (
                <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                  {unreadCount} baru
                </span>
              )}
            </div>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={handleMarkAllRead}
                className="text-xs font-medium text-emerald-700 hover:text-emerald-800 transition"
              >
                Tandai semua dibaca
              </button>
            )}
          </div>

          <div className="max-h-80 overflow-y-auto divide-y divide-stone-100">
            {isLoading ? (
              <div className="p-6 text-center text-xs text-stone-500">
                Memuat notifikasi...
              </div>
            ) : loadFailed ? (
              <div
                className="p-6 text-center text-xs text-stone-600"
                role="status"
              >
                <p>Notifikasi belum dapat dimuat.</p>
                <button
                  type="button"
                  onClick={() => void fetchRecentNotifications()}
                  className="mt-2 rounded px-2 py-1 font-medium text-emerald-700 focus-visible:outline-2 focus-visible:outline-emerald-600"
                >
                  Coba lagi
                </button>
              </div>
            ) : items.length === 0 ? (
              <div className="p-6 text-center">
                <Inbox className="mx-auto h-8 w-8 text-stone-300 mb-2" />
                <p className="text-xs font-medium text-stone-600">
                  Tidak ada notifikasi baru
                </p>
                <p className="text-[11px] text-stone-400 mt-0.5">
                  Semua pesan penting akan muncul di sini.
                </p>
              </div>
            ) : (
              items.map((item) => (
                <div
                  key={item.id}
                  className={`p-3.5 transition flex items-start justify-between gap-3 ${
                    item.read_at === null
                      ? "bg-emerald-50/40"
                      : "bg-white hover:bg-stone-50"
                  }`}
                >
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-1.5 mb-1">
                      <span className="inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider bg-stone-100 text-stone-700">
                        {item.event_category}
                      </span>
                      {item.read_at === null && (
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                      )}
                    </div>
                    <p className="text-xs text-stone-800 leading-snug line-clamp-2">
                      {item.sanitized_summary}
                    </p>
                    <span className="text-[10px] text-stone-400 mt-1 block">
                      {new Date(item.created_at).toLocaleString("id-ID", {
                        day: "numeric",
                        month: "short",
                        hour: "2-digit",
                        minute: "2-digit",
                      })}
                    </span>
                  </div>
                  {item.read_at === null && (
                    <button
                      type="button"
                      onClick={(e) => handleMarkAsRead(item.id, e)}
                      className="shrink-0 p-1 text-stone-400 hover:text-emerald-700 rounded transition"
                      title="Tandai dibaca"
                    >
                      <Check className="h-4 w-4" />
                    </button>
                  )}
                </div>
              ))
            )}
          </div>

          <div className="border-t border-stone-100 bg-stone-50 p-2 text-center">
            <Link
              href="/inbox"
              onClick={() => setIsOpen(false)}
              className="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700 hover:text-emerald-800 transition"
            >
              Buka Kotak Masuk Lengkap
              <ExternalLink className="h-3.5 w-3.5" />
            </Link>
          </div>
        </div>
      )}
    </div>
  );
};
