import React, { useState } from "react";
import { Link } from "@inertiajs/react";
import {
  ArrowLeft,
  ShieldCheck,
  Clock,
  User,
  Layers,
  FileCode,
  Tag,
  CheckCircle2,
  AlertCircle,
  Hash,
  Eye,
  GitCommit,
} from "lucide-react";
import AppLayout from "@/Layouts/AppLayout";

interface Actor {
  id: number;
  name: string;
  username: string;
  email?: string;
}

interface AuditLogDetail {
  id: number;
  actor_user_id: number | null;
  action: string;
  entity_type: string;
  entity_id: string;
  scope: string;
  event_visibility: string;
  allowed_changes: Record<string, any> | null;
  reason: string | null;
  batch_id: string | null;
  created_at: string;
  actor: Actor | null;
}

interface BatchLogItem {
  id: number;
  actor_user_id: number | null;
  action: string;
  entity_type: string;
  entity_id: string;
  scope: string;
  reason: string | null;
  created_at: string;
  actor: { id: number; name: string } | null;
}

interface Props {
  auditLog: AuditLogDetail;
  batchLogs: BatchLogItem[];
}

export default function Show({ auditLog, batchLogs }: Props) {
  const [viewMode, setViewMode] = useState<"formatted" | "raw">("formatted");

  const formatDateTime = (dateStr: string) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return date.toLocaleString("id-ID", {
      year: "numeric",
      month: "long",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    });
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

  const renderValue = (val: any) => {
    if (val === null || val === undefined) {
      return <span className="text-muted-foreground/40 italic">null</span>;
    }
    if (typeof val === "boolean") {
      return (
        <span
          className={`px-1.5 py-0.5 rounded text-[10px] font-mono ${
            val
              ? "bg-emerald-100 text-emerald-800"
              : "bg-rose-100 text-rose-800"
          }`}
        >
          {val ? "true" : "false"}
        </span>
      );
    }
    if (typeof val === "string" && val === "[REDACTED]") {
      return (
        <span className="px-1.5 py-0.5 rounded bg-rose-100 border border-rose-200 text-rose-800 font-mono text-[10px] font-semibold">
          [REDACTED SENSITIVE DATA]
        </span>
      );
    }
    if (typeof val === "object") {
      return (
        <pre className="font-mono text-[11px] bg-canvas p-1.5 rounded border border-border overflow-x-auto">
          {JSON.stringify(val, null, 2)}
        </pre>
      );
    }
    return (
      <span className="font-mono text-[11px] text-foreground">
        {String(val)}
      </span>
    );
  };

  // Determine if allowed_changes is a before/after diff structure
  const changes = auditLog.allowed_changes;
  const isBeforeAfter =
    changes &&
    typeof changes === "object" &&
    ("before" in changes || "after" in changes);

  const isFromTo =
    changes &&
    typeof changes === "object" &&
    ("from" in changes || "to" in changes);

  return (
    <AppLayout
      title={`Rincian Audit #${auditLog.id}`}
      contextHeader={{
        title: `Entri Audit #${auditLog.id}`,
        badge: auditLog.scope.toUpperCase(),
        description: `Aksi: ${auditLog.action} • Stempel waktu: ${formatDateTime(auditLog.created_at)}`,
      }}
    >
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <Link
            href="/audit-logs"
            className="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground font-medium"
          >
            <ArrowLeft className="w-4 h-4" />
            Kembali ke Daftar Log
          </Link>
          <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
            <ShieldCheck className="w-3.5 h-3.5" />
            Append-Only Immutability
          </span>
        </div>

        {/* Metadata Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          {/* Actor */}
          <div className="bg-white border border-border rounded-xl p-4 shadow-xs">
            <div className="flex items-center gap-2 text-xs font-semibold text-muted-foreground mb-2">
              <User className="w-3.5 h-3.5 text-primary" />
              <span>Aktor Pelaksana</span>
            </div>
            {auditLog.actor ? (
              <div>
                <div className="font-semibold text-sm text-foreground">
                  {auditLog.actor.name}
                </div>
                <div className="text-xs text-muted-foreground font-mono">
                  @{auditLog.actor.username}
                </div>
                {auditLog.actor.email && (
                  <div className="text-[11px] text-muted-foreground mt-1">
                    {auditLog.actor.email}
                  </div>
                )}
              </div>
            ) : (
              <div className="text-xs text-muted-foreground italic font-mono">
                Sistem Otomatis (Cron / Daemon)
              </div>
            )}
          </div>

          {/* Target Entity */}
          <div className="bg-white border border-border rounded-xl p-4 shadow-xs">
            <div className="flex items-center gap-2 text-xs font-semibold text-muted-foreground mb-2">
              <Tag className="w-3.5 h-3.5 text-primary" />
              <span>Entitas Sasaran</span>
            </div>
            <div className="font-mono text-xs text-foreground font-semibold">
              {auditLog.entity_type}
            </div>
            <div className="text-xs text-muted-foreground mt-1 font-mono">
              ID Entitas: #{auditLog.entity_id}
            </div>
          </div>

          {/* Visibility & Reason */}
          <div className="bg-white border border-border rounded-xl p-4 shadow-xs">
            <div className="flex items-center gap-2 text-xs font-semibold text-muted-foreground mb-2">
              <AlertCircle className="w-3.5 h-3.5 text-primary" />
              <span>Alasan / Catatan</span>
            </div>
            <div className="text-xs text-foreground">
              {auditLog.reason ? (
                <span>{auditLog.reason}</span>
              ) : (
                <span className="text-muted-foreground italic text-xs">
                  Tidak ada catatan alasan eksplisit.
                </span>
              )}
            </div>
            <div className="text-[11px] text-muted-foreground mt-2">
              Visibilitas:{" "}
              <span className="font-mono font-semibold">
                {auditLog.event_visibility}
              </span>
            </div>
          </div>

          {/* Batch Tracking */}
          <div className="bg-white border border-border rounded-xl p-4 shadow-xs">
            <div className="flex items-center gap-2 text-xs font-semibold text-muted-foreground mb-2">
              <GitCommit className="w-3.5 h-3.5 text-primary" />
              <span>Grup Batch Atomik</span>
            </div>
            {auditLog.batch_id ? (
              <div>
                <div className="font-mono text-[11px] text-foreground bg-canvas px-2 py-1 rounded border border-border break-all">
                  {auditLog.batch_id}
                </div>
                <div className="text-[11px] text-muted-foreground mt-1">
                  {batchLogs.length} operasi concurrent dalam batch
                </div>
              </div>
            ) : (
              <div className="text-xs text-muted-foreground italic">
                Operasi individual tunggal.
              </div>
            )}
          </div>
        </div>

        {/* Diff Viewer Card */}
        <div className="bg-white border border-border rounded-xl shadow-xs overflow-hidden">
          <div className="border-b border-border px-6 py-4 bg-muted/30 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <FileCode className="w-4 h-4 text-primary" />
              <h2 className="text-sm font-bold text-foreground">
                Perubahan Data (Before vs After Diff)
              </h2>
            </div>
            <div className="flex items-center gap-1 bg-white border border-border rounded-lg p-0.5 text-xs">
              <button
                type="button"
                onClick={() => setViewMode("formatted")}
                className={`px-3 py-1 rounded-md font-medium transition ${
                  viewMode === "formatted"
                    ? "bg-primary text-white shadow-2xs"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Tampilan Diff
              </button>
              <button
                type="button"
                onClick={() => setViewMode("raw")}
                className={`px-3 py-1 rounded-md font-medium transition ${
                  viewMode === "raw"
                    ? "bg-primary text-white shadow-2xs"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Payload JSON
              </button>
            </div>
          </div>

          <div className="p-6">
            {!changes || Object.keys(changes).length === 0 ? (
              <div className="p-8 text-center text-muted-foreground text-xs italic">
                Tidak ada perubahan atribut yang dicatat pada entri ini.
              </div>
            ) : viewMode === "raw" ? (
              <div className="bg-canvas border border-border rounded-lg p-4 overflow-x-auto font-mono text-xs">
                <pre>{JSON.stringify(changes, null, 2)}</pre>
              </div>
            ) : isBeforeAfter ? (
              /* Before vs After Side-by-side or stacked diff */
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {/* Before */}
                <div className="border border-rose-200 bg-rose-50/30 rounded-lg p-4 space-y-3">
                  <div className="flex items-center gap-1.5 text-xs font-bold text-rose-800 uppercase tracking-wider pb-2 border-b border-rose-200">
                    <span className="w-2 h-2 rounded-full bg-rose-500"></span>
                    Sebelumnya (Before)
                  </div>
                  {changes.before && typeof changes.before === "object" ? (
                    <div className="space-y-2">
                      {Object.entries(changes.before).map(([k, v]) => (
                        <div
                          key={k}
                          className="flex flex-col sm:flex-row sm:items-center justify-between text-xs py-1 border-b border-rose-100 gap-1"
                        >
                          <span className="font-mono text-rose-900 font-medium">
                            {k}
                          </span>
                          <span className="text-right">{renderValue(v)}</span>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-xs text-muted-foreground italic">
                      Tidak ada kondisi sebelum operasi.
                    </div>
                  )}
                </div>

                {/* After */}
                <div className="border border-emerald-200 bg-emerald-50/30 rounded-lg p-4 space-y-3">
                  <div className="flex items-center gap-1.5 text-xs font-bold text-emerald-800 uppercase tracking-wider pb-2 border-b border-emerald-200">
                    <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Sesudahnya (After)
                  </div>
                  {changes.after && typeof changes.after === "object" ? (
                    <div className="space-y-2">
                      {Object.entries(changes.after).map(([k, v]) => (
                        <div
                          key={k}
                          className="flex flex-col sm:flex-row sm:items-center justify-between text-xs py-1 border-b border-emerald-100 gap-1"
                        >
                          <span className="font-mono text-emerald-900 font-medium">
                            {k}
                          </span>
                          <span className="text-right">{renderValue(v)}</span>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-xs text-muted-foreground italic">
                      Tidak ada kondisi sesudah operasi.
                    </div>
                  )}
                </div>
              </div>
            ) : isFromTo ? (
              /* From -> To Transition */
              <div className="border border-border rounded-lg p-4 space-y-3 bg-canvas">
                <div className="text-xs font-semibold text-muted-foreground pb-2 border-b border-border">
                  Transisi Status / Nilai
                </div>
                <div className="flex items-center gap-4 text-xs font-mono">
                  <div className="p-3 rounded-md bg-rose-50 border border-rose-200 flex-1">
                    <div className="text-[10px] text-rose-700 uppercase font-bold mb-1">
                      Nilai Awal (From)
                    </div>
                    <div>{renderValue(changes.from)}</div>
                  </div>
                  <div className="text-muted-foreground font-bold">&rarr;</div>
                  <div className="p-3 rounded-md bg-emerald-50 border border-emerald-200 flex-1">
                    <div className="text-[10px] text-emerald-700 uppercase font-bold mb-1">
                      Nilai Baru (To)
                    </div>
                    <div>{renderValue(changes.to)}</div>
                  </div>
                </div>
              </div>
            ) : (
              /* Generic Key-Value Table */
              <div className="border border-border rounded-lg overflow-hidden">
                <table className="w-full text-left text-xs">
                  <thead className="bg-muted/40 border-b border-border text-muted-foreground font-semibold">
                    <tr>
                      <th className="py-2.5 px-4 w-1/3">Kunci Atribut</th>
                      <th className="py-2.5 px-4">Nilai Tercatat</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border">
                    {Object.entries(changes).map(([k, v]) => (
                      <tr key={k} className="hover:bg-muted/20 transition">
                        <td className="py-2.5 px-4 font-mono font-medium text-foreground">
                          {k}
                        </td>
                        <td className="py-2.5 px-4">{renderValue(v)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </div>

        {/* Batch Operations Section */}
        {batchLogs.length > 0 && (
          <div className="bg-white border border-border rounded-xl shadow-xs overflow-hidden">
            <div className="border-b border-border px-6 py-4 bg-muted/30">
              <h2 className="text-sm font-bold text-foreground">
                Operasi Terkait Dalam Batch Yang Sama ({auditLog.batch_id})
              </h2>
              <p className="text-xs text-muted-foreground mt-0.5">
                Daftar mutasi yang dieksekusi secara atomik dalam satu transaksi
                basis data.
              </p>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-muted/40 border-b border-border text-muted-foreground font-semibold">
                  <tr>
                    <th className="py-2.5 px-4">Log ID</th>
                    <th className="py-2.5 px-4">Aksi</th>
                    <th className="py-2.5 px-4">Entitas Target</th>
                    <th className="py-2.5 px-4">Aktor</th>
                    <th className="py-2.5 px-4">Alasan</th>
                    <th className="py-2.5 px-4 text-right">Rincian</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {batchLogs.map((item) => (
                    <tr key={item.id} className="hover:bg-muted/20 transition">
                      <td className="py-2.5 px-4 font-mono text-muted-foreground">
                        #{item.id}
                      </td>
                      <td className="py-2.5 px-4 font-mono font-semibold">
                        {item.action}
                      </td>
                      <td className="py-2.5 px-4 font-mono text-muted-foreground">
                        {item.entity_type} #{item.entity_id}
                      </td>
                      <td className="py-2.5 px-4">
                        {item.actor ? item.actor.name : "Sistem"}
                      </td>
                      <td className="py-2.5 px-4 text-muted-foreground">
                        {item.reason || "-"}
                      </td>
                      <td className="py-2.5 px-4 text-right">
                        <Link
                          href={`/audit-logs/${item.id}`}
                          className="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-border bg-canvas text-foreground hover:bg-muted text-[11px] transition"
                        >
                          <Eye className="w-3 h-3 text-muted-foreground" />
                          <span>Tinjau</span>
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
