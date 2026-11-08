import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import {
  ShieldCheck,
  Search,
  Filter,
  RotateCcw,
  Clock,
  User,
  Layers,
  ChevronRight,
  Eye,
  Calendar,
  Activity,
  ArrowLeft,
  FileText,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";

interface Actor {
  id: number;
  name: string;
  username: string;
}

interface ScopeOption {
  value: string;
  label: string;
}

interface AuditLogItem {
  id: number;
  actor_user_id: number | null;
  action: string;
  entity_type: string;
  entity_id: string;
  scope: string;
  event_visibility: string;
  allowed_changes: Record<string, unknown> | null;
  reason: string | null;
  batch_id: string | null;
  created_at: string;
  actor: Actor | null;
}

interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

interface PaginatedLogs {
  data: AuditLogItem[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  links: PaginationLink[];
}

interface Props {
  logs: PaginatedLogs;
  filters: {
    scope?: string;
    action?: string;
    actor_user_id?: string | number;
    entity_type?: string;
    starts_on?: string;
    ends_on?: string;
  };
  actors: Actor[];
  scopes: ScopeOption[];
}

export default function Index({ logs, filters, actors, scopes }: Props) {
  const [scope, setScope] = useState(filters.scope || "");
  const [action, setAction] = useState(filters.action || "");
  const [actorUserId, setActorUserId] = useState(
    filters.actor_user_id ? String(filters.actor_user_id) : "",
  );
  const [entityType, setEntityType] = useState(filters.entity_type || "");
  const [startsOn, setStartsOn] = useState(filters.starts_on || "");
  const [endsOn, setEndsOn] = useState(filters.ends_on || "");

  const handleFilter = (e: React.FormEvent) => {
    e.preventDefault();
    router.get(
      "/audit-logs",
      {
        scope: scope || undefined,
        action: action || undefined,
        actor_user_id: actorUserId || undefined,
        entity_type: entityType || undefined,
        starts_on: startsOn || undefined,
        ends_on: endsOn || undefined,
      },
      {
        preserveState: true,
        replace: true,
      },
    );
  };

  const handleReset = () => {
    setScope("");
    setAction("");
    setActorUserId("");
    setEntityType("");
    setStartsOn("");
    setEndsOn("");
    router.get("/audit-logs", {}, { replace: true });
  };

  const getScopeBadge = (logScope: string) => {
    switch (logScope) {
      case "progression":
        return "bg-purple-100 text-purple-800 border-purple-200";
      case "report":
        return "bg-blue-100 text-blue-800 border-blue-200";
      case "assessment":
        return "bg-cyan-100 text-cyan-800 border-cyan-200";
      case "attendance":
        return "bg-emerald-100 text-emerald-800 border-emerald-200";
      case "user":
      case "security":
        return "bg-rose-100 text-rose-800 border-rose-200";
      case "system":
        return "bg-amber-100 text-amber-800 border-amber-200";
      default:
        return "bg-gray-100 text-gray-800 border-gray-200";
    }
  };

  const getActionBadge = (logAction: string) => {
    const act = logAction.toUpperCase();
    if (
      act.includes("CREATE") ||
      act.includes("STORE") ||
      act.includes("APPLY")
    ) {
      return "bg-emerald-50 text-emerald-700 border-emerald-300";
    }
    if (
      act.includes("APPROVE") ||
      act.includes("PUBLISH") ||
      act.includes("FINALIZE")
    ) {
      return "bg-blue-50 text-blue-700 border-blue-300";
    }
    if (
      act.includes("REJECT") ||
      act.includes("REVOKE") ||
      act.includes("DELETE") ||
      act.includes("DESTROY")
    ) {
      return "bg-rose-50 text-rose-700 border-rose-300";
    }
    if (
      act.includes("UPDATE") ||
      act.includes("REVISE") ||
      act.includes("SUBMIT")
    ) {
      return "bg-amber-50 text-amber-700 border-amber-300";
    }
    return "bg-muted text-foreground border-border";
  };

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleString("id-ID", {
      year: "numeric",
      month: "short",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
  };

  const simplifyEntityType = (rawType: string) => {
    if (!rawType) return "-";
    const parts = rawType.split("\\");
    return parts[parts.length - 1];
  };

  return (
    <AppLayout
      title="Riwayat Audit Sistem"
      contextHeader={{
        title: "Riwayat Audit Sistem",
        badge: "Append-Only (F-10)",
        description:
          "Audit trail domain resmi untuk akuntabilitas operasional madrasah (HTTP-28, SCR-11).",
        totalCount: logs.total,
      }}
    >
      <div className="space-y-6">
        {/* Filter Section */}
        <div className="bg-white border border-border rounded-xl p-5 shadow-xs">
          <form onSubmit={handleFilter} className="space-y-4">
            <div className="flex items-center gap-2 text-xs font-semibold text-foreground uppercase tracking-wider">
              <Filter className="w-3.5 h-3.5 text-primary" />
              <span>Penyaringan Log Audit</span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
              {/* Scope */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Cakupan (Scope)
                </label>
                <select
                  value={scope}
                  onChange={(e) => setScope(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Semua Cakupan</option>
                  {scopes.map((s) => (
                    <option key={s.value} value={s.value}>
                      {s.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Actor */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Aktor / Pengguna
                </label>
                <select
                  value={actorUserId}
                  onChange={(e) => setActorUserId(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option value="">Semua Aktor</option>
                  {actors.map((a) => (
                    <option key={a.id} value={a.id}>
                      {a.name} ({a.username})
                    </option>
                  ))}
                </select>
              </div>

              {/* Action */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Aksi (Action)
                </label>
                <input
                  type="text"
                  placeholder="e.g. APPLY, UPDATE"
                  value={action}
                  onChange={(e) => setAction(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              {/* Entity Type */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Jenis Entitas
                </label>
                <input
                  type="text"
                  placeholder="e.g. Student, ReportCard"
                  value={entityType}
                  onChange={(e) => setEntityType(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              {/* Starts On */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Dari Tanggal
                </label>
                <input
                  type="date"
                  value={startsOn}
                  onChange={(e) => setStartsOn(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              {/* Ends On */}
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  Sampai Tanggal
                </label>
                <input
                  type="date"
                  value={endsOn}
                  onChange={(e) => setEndsOn(e.target.value)}
                  className="w-full text-xs rounded-md border border-border bg-canvas px-2.5 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
              <button
                type="button"
                onClick={handleReset}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-border bg-white text-xs font-medium text-muted-foreground hover:text-foreground transition"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                <span>Reset</span>
              </button>
              <button
                type="submit"
                className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-md bg-primary text-white text-xs font-medium hover:bg-primary/90 transition shadow-xs"
              >
                <Search className="w-3.5 h-3.5" />
                <span>Terapkan Filter</span>
              </button>
            </div>
          </form>
        </div>

        {/* Audit Log Table */}
        <div className="bg-white border border-border rounded-xl shadow-xs overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-muted/40 border-b border-border text-muted-foreground font-semibold">
                <tr>
                  <th className="py-3 px-4">Stempel Waktu</th>
                  <th className="py-3 px-4">Aktor Pelaksana</th>
                  <th className="py-3 px-4">Cakupan</th>
                  <th className="py-3 px-4">Aksi</th>
                  <th className="py-3 px-4">Entitas Target</th>
                  <th className="py-3 px-4">Alasan / Catatan</th>
                  <th className="py-3 px-4 text-right">Rincian</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {logs.data.length === 0 ? (
                  <tr>
                    <td
                      colSpan={7}
                      className="py-12 text-center text-muted-foreground"
                    >
                      <div className="flex flex-col items-center justify-center gap-2">
                        <Activity className="w-8 h-8 text-muted-foreground/50" />
                        <span className="font-medium">
                          Tidak ada catatan log audit yang sesuai dengan filter.
                        </span>
                        <span className="text-[11px]">
                          Coba ubah rentang tanggal atau bersihkan filter
                          pencarian.
                        </span>
                      </div>
                    </td>
                  </tr>
                ) : (
                  logs.data.map((log) => (
                    <tr key={log.id} className="hover:bg-muted/20 transition">
                      {/* Timestamp */}
                      <td className="py-3 px-4 font-mono text-muted-foreground whitespace-nowrap">
                        <div className="flex items-center gap-1.5">
                          <Clock className="w-3.5 h-3.5 text-muted-foreground/60" />
                          <span>{formatDateTime(log.created_at)}</span>
                        </div>
                      </td>

                      {/* Actor */}
                      <td className="py-3 px-4 whitespace-nowrap">
                        {log.actor ? (
                          <div className="flex items-center gap-2">
                            <div className="w-6 h-6 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-[10px]">
                              {log.actor.name.charAt(0)}
                            </div>
                            <div>
                              <div className="font-semibold text-foreground">
                                {log.actor.name}
                              </div>
                              <div className="text-[10px] text-muted-foreground font-mono">
                                @{log.actor.username}
                              </div>
                            </div>
                          </div>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-muted-foreground italic font-mono text-[11px]">
                            <User className="w-3 h-3" />
                            Sistem / Otomatis
                          </span>
                        )}
                      </td>

                      {/* Scope */}
                      <td className="py-3 px-4 whitespace-nowrap">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border ${getScopeBadge(
                            log.scope,
                          )}`}
                        >
                          {log.scope.toUpperCase()}
                        </span>
                      </td>

                      {/* Action */}
                      <td className="py-3 px-4 whitespace-nowrap">
                        <span
                          className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-mono font-medium border ${getActionBadge(
                            log.action,
                          )}`}
                        >
                          {log.action}
                        </span>
                      </td>

                      {/* Target Entity */}
                      <td className="py-3 px-4 whitespace-nowrap">
                        <div className="font-mono text-[11px] text-foreground">
                          {simplifyEntityType(log.entity_type)}
                          <span className="text-muted-foreground ml-1">
                            #{log.entity_id}
                          </span>
                        </div>
                      </td>

                      {/* Reason / Note */}
                      <td className="py-3 px-4 max-w-xs truncate text-muted-foreground">
                        {log.reason ? (
                          <span title={log.reason}>{log.reason}</span>
                        ) : (
                          <span className="text-muted-foreground/40 italic">
                            -
                          </span>
                        )}
                      </td>

                      {/* Detail Link */}
                      <td className="py-3 px-4 text-right whitespace-nowrap">
                        <Link
                          href={`/audit-logs/${log.id}`}
                          className="inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-border bg-canvas text-foreground hover:bg-muted font-medium text-[11px] transition shadow-2xs"
                        >
                          <Eye className="w-3 h-3 text-muted-foreground" />
                          <span>Tinjau Diff</span>
                        </Link>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {logs.links && logs.links.length > 3 && (
            <div className="p-4 border-t border-border flex items-center justify-between">
              <span className="text-xs text-muted-foreground">
                Halaman {logs.current_page} dari {logs.last_page} ({logs.total}{" "}
                total baris)
              </span>
              <div className="flex items-center gap-1">
                {logs.links.map((link, idx) => (
                  <button
                    key={idx}
                    disabled={!link.url || link.active}
                    onClick={() => link.url && router.get(link.url)}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    className={`px-2.5 py-1 text-xs rounded-md border ${
                      link.active
                        ? "bg-primary text-white border-primary font-semibold"
                        : link.url
                          ? "bg-white border-border text-foreground hover:bg-muted"
                          : "bg-muted text-muted-foreground/40 border-border cursor-not-allowed"
                    }`}
                  />
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
