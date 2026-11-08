import React, { useState } from "react";
import { Link, useForm, router, usePage } from "@inertiajs/react";
import {
  ArrowLeft,
  Award,
  CheckCircle2,
  Clock,
  FileText,
  History,
  Layers,
  Plus,
  Scale,
  ShieldAlert,
  Sliders,
  ToggleLeft,
  ToggleRight,
  AlertCircle,
  Check,
} from "lucide-react";
import AppLayout, { SharedProps } from "@/Layouts/AppLayout";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { NativeSelect } from "@/Components/ui/native-select";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface ScoringComponent {
  id: number;
  code: string;
  name: string;
  source_type: "score" | "attendance";
  sort_order: number;
  is_active: boolean;
}

interface PolicyComponent {
  id: number;
  grading_policy_id: number;
  scoring_component_id: number;
  weight_percentage: string;
  scoring_component?: ScoringComponent;
}

interface User {
  id: number;
  name: string;
}

interface GradingPolicy {
  id: number;
  revision: number;
  kkm: string;
  status: "draft" | "active" | "retired";
  created_at: string;
  activated_at: string | null;
  created_by?: User;
  activated_by?: User;
  components: PolicyComponent[];
}

interface Props {
  activePolicy: GradingPolicy | null;
  draftPolicies: GradingPolicy[];
  retiredPolicies: GradingPolicy[];
  scoringComponents: ScoringComponent[];
}

export default function GradingPoliciesIndex({
  activePolicy,
  draftPolicies,
  retiredPolicies,
  scoringComponents,
}: Props) {
  const { auth } = usePage<SharedProps>().props;
  const canManage =
    auth.user?.roles.some((role) =>
      ["master_admin", "academic_admin"].includes(role),
    ) ?? false;
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showComponentModal, setShowComponentModal] = useState(false);
  const [editingPolicy, setEditingPolicy] = useState<GradingPolicy | null>(
    null,
  );

  // Filter active components available for policy composition
  const availableComponents = scoringComponents.filter((c) => c.is_active);

  const initialComponentsData = availableComponents.map((c) => ({
    scoring_component_id: c.id,
    weight_percentage: c.code === "attendance" ? "10.00" : "22.50",
  }));

  const createForm = useForm({
    kkm: "70.00",
    components: initialComponentsData,
  });

  const editForm = useForm({
    kkm: "70.00",
    components: [] as {
      scoring_component_id: number;
      weight_percentage: string;
    }[],
  });

  const componentForm = useForm({
    code: "",
    name: "",
    source_type: "score",
    sort_order: (scoringComponents.length + 1).toString(),
  });

  // Helper to calculate total weight dynamically
  const calculateTotalWeight = (
    components: { scoring_component_id: number; weight_percentage: string }[],
  ) => {
    return components
      .reduce((sum, c) => {
        const val = parseFloat(c.weight_percentage);
        return sum + (isNaN(val) ? 0 : val);
      }, 0)
      .toFixed(2);
  };

  const createTotal = calculateTotalWeight(createForm.data.components);
  const editTotal = calculateTotalWeight(editForm.data.components);

  const handleCreatePolicy = (e: React.FormEvent) => {
    e.preventDefault();
    createForm.post("/academic/grading-policies", {
      onSuccess: () => {
        setShowCreateModal(false);
        createForm.reset();
      },
    });
  };

  const handleOpenEdit = (policy: GradingPolicy) => {
    setEditingPolicy(policy);
    editForm.setData({
      kkm: policy.kkm,
      components: policy.components.map((c) => ({
        scoring_component_id: c.scoring_component_id,
        weight_percentage: c.weight_percentage,
      })),
    });
  };

  const handleUpdatePolicy = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingPolicy) return;

    editForm.put(`/academic/grading-policies/${editingPolicy.id}`, {
      onSuccess: () => {
        setEditingPolicy(null);
      },
    });
  };

  const handleActivatePolicy = (policy: GradingPolicy) => {
    const total = calculateTotalWeight(
      policy.components.map((c) => ({
        scoring_component_id: c.scoring_component_id,
        weight_percentage: c.weight_percentage,
      })),
    );

    if (total !== "100.00") {
      alert(
        `Total bobot draf ini adalah ${total}%. Syarat aktivasi F-04: total bobot seluruh komponen harus tepat 100.00%.`,
      );
      return;
    }

    if (
      confirm(
        `Aktifkan Kebijakan Penilaian Revisi ke-${policy.revision} (KKM: ${policy.kkm})?\n\nKebijakan aktif saat ini akan otomatis diarsipkan (retired). Perubahan ini berlaku global untuk seluruh mata pelajaran.`,
      )
    ) {
      router.patch(`/academic/grading-policies/${policy.id}/activate`);
    }
  };

  const handleCreateComponent = (e: React.FormEvent) => {
    e.preventDefault();
    componentForm.post("/academic/scoring-components", {
      onSuccess: () => {
        setShowComponentModal(false);
        componentForm.reset();
      },
    });
  };

  const handleToggleComponent = (componentId: number) => {
    router.patch(`/academic/scoring-components/${componentId}/toggle`);
  };

  return (
    <AppLayout
      title="Kebijakan Penilaian Global — SIA MI Alghivari"
      contextHeader={
        <div>
          <div className="flex items-center gap-2 mb-1">
            <Badge
              variant="secondary"
              className="text-xs text-primary font-semibold"
            >
              Standar Penilaian F-04
            </Badge>
            <span className="text-xs text-muted-foreground">•</span>
            <span className="text-xs text-muted-foreground">
              Konfigurasi Terpusat Tanpa Override Kelas (Q7.5)
            </span>
          </div>
          <h1 className="text-xl font-bold tracking-tight text-foreground">
            Kebijakan Penilaian & Komponen Global
          </h1>
        </div>
      }
      actions={
        canManage && (
          <div className="flex items-center gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => setShowComponentModal(true)}
              className="gap-1.5 text-xs"
            >
              <Sliders className="size-3.5 text-primary" />
              <span>Kelola Komponen</span>
            </Button>
            <Button
              type="button"
              variant="default"
              size="sm"
              onClick={() => setShowCreateModal(true)}
              className="gap-1.5 text-xs font-semibold"
            >
              <Plus className="size-3.5" />
              <span>Draf Kebijakan Baru</span>
            </Button>
          </div>
        )
      }
    >
      <div className="space-y-6">
        {/* Section 1: Active Policy Banner */}
        <div className="mb-8 rounded-2xl border border-emerald-900/15 bg-white p-6 shadow-xs relative overflow-hidden">
          <div className="absolute top-0 right-0 h-2 w-full bg-emerald-600"></div>
          <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-800">
                  <CheckCircle2 className="h-3.5 w-3.5" />
                  Kebijakan Aktif Sekolah
                </span>
                {activePolicy && (
                  <span className="text-xs font-semibold text-stone-500">
                    Revisi ke-{activePolicy.revision}
                  </span>
                )}
              </div>

              {activePolicy ? (
                <div>
                  <div className="flex items-baseline gap-3 mb-4">
                    <h3 className="text-3xl font-extrabold text-[#123b30]">
                      KKM Global: {activePolicy.kkm}
                    </h3>
                    <span className="text-xs text-stone-500">
                      (Ambang batas ketuntasan nilai akhir)
                    </span>
                  </div>

                  {/* Breakdown Komponen Aktif */}
                  <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    {activePolicy.components.map((comp) => (
                      <div
                        key={comp.id}
                        className="rounded-xl border border-stone-200 bg-stone-50/70 p-3 text-center"
                      >
                        <p className="text-xs text-stone-500 font-semibold mb-1">
                          {comp.scoring_component?.name}
                        </p>
                        <p className="text-xl font-black text-emerald-900">
                          {comp.weight_percentage}%
                        </p>
                        <span className="text-[10px] uppercase font-bold text-stone-400">
                          {comp.scoring_component?.source_type === "attendance"
                            ? "Otomatis"
                            : "Skor Input"}
                        </span>
                      </div>
                    ))}
                  </div>

                  <div className="mt-4 flex flex-wrap items-center gap-4 text-xs text-stone-500 border-t border-stone-100 pt-3">
                    <span>
                      Total Bobot:{" "}
                      <strong className="text-emerald-800 font-bold">
                        100.00%
                      </strong>
                    </span>
                    <span>•</span>
                    <span>Diaktifkan: {activePolicy.activated_at || "-"}</span>
                    <span>•</span>
                    <span>
                      Oleh: {activePolicy.activated_by?.name || "Administrator"}
                    </span>
                  </div>
                </div>
              ) : (
                <div className="py-6 text-center">
                  <AlertCircle className="mx-auto h-8 w-8 text-amber-500 mb-2" />
                  <h4 className="text-sm font-bold text-stone-800">
                    Belum Ada Kebijakan Penilaian yang Aktif
                  </h4>
                  <p className="text-xs text-stone-500 max-w-md mx-auto mt-1">
                    Sistem belum memiliki kebijakan penilaian aktif. Buat draf
                    kebijakan baru di bawah dan aktifkan setelah total bobot
                    mencapai tepat 100%.
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Section 2: Draft Policies */}
        <div className="mb-8 rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
          <div className="flex items-center justify-between mb-4 border-b border-stone-100 pb-3">
            <div className="flex items-center gap-2">
              <FileText className="h-5 w-5 text-emerald-700" />
              <h3 className="text-base font-bold text-stone-900">
                Draf Usulan Kebijakan Penilaian ({draftPolicies.length})
              </h3>
            </div>
          </div>

          {draftPolicies.length === 0 ? (
            <p className="text-xs text-stone-400 italic py-4">
              Tidak ada draf kebijakan penilaian saat ini. Klik &quot;Buat Draf
              Kebijakan&quot; jika ingin mengajukan perubahan bobot atau KKM.
            </p>
          ) : (
            <div className="space-y-4">
              {draftPolicies.map((draft) => {
                const total = calculateTotalWeight(
                  draft.components.map((c) => ({
                    scoring_component_id: c.scoring_component_id,
                    weight_percentage: c.weight_percentage,
                  })),
                );
                const isReady = total === "100.00";

                return (
                  <div
                    key={draft.id}
                    className="rounded-xl border border-stone-200 bg-stone-50/50 p-4 transition hover:border-emerald-300"
                  >
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
                      <div>
                        <div className="flex items-center gap-2 mb-1">
                          <span className="font-bold text-sm text-stone-900">
                            Draf Revisi ke-{draft.revision}
                          </span>
                          <span className="text-xs font-semibold px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                            Draf
                          </span>
                          <span className="text-xs text-stone-500 font-mono">
                            KKM: {draft.kkm}
                          </span>
                        </div>
                        <p className="text-xs text-stone-500">
                          Dibuat oleh{" "}
                          {draft.created_by?.name || "Administrator"} pada{" "}
                          {draft.created_at}
                        </p>
                      </div>

                      {canManage && (
                        <div className="flex items-center gap-2">
                          <button
                            type="button"
                            onClick={() => handleOpenEdit(draft)}
                            className="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-50"
                          >
                            Ubah Komponen
                          </button>
                          <button
                            type="button"
                            onClick={() => handleActivatePolicy(draft)}
                            className={`inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-bold text-white transition ${
                              isReady
                                ? "bg-emerald-700 hover:bg-emerald-800"
                                : "bg-stone-400 cursor-not-allowed opacity-70"
                            }`}
                          >
                            <Check className="h-3.5 w-3.5" />
                            Aktifkan Kebijakan
                          </button>
                        </div>
                      )}
                    </div>

                    {/* Components Breakdown */}
                    <div className="flex flex-wrap items-center gap-2 pt-2 border-t border-stone-200/60">
                      {draft.components.map((c) => (
                        <span
                          key={c.id}
                          className="inline-flex items-center gap-1.5 rounded-md bg-white px-2.5 py-1 text-xs border border-stone-200 text-stone-700"
                        >
                          <span className="font-medium text-stone-600">
                            {c.scoring_component?.name}:
                          </span>
                          <strong className="font-bold text-emerald-800">
                            {c.weight_percentage}%
                          </strong>
                        </span>
                      ))}
                      <span
                        className={`ml-auto text-xs font-bold px-2.5 py-1 rounded-md ${
                          isReady
                            ? "bg-emerald-100 text-emerald-900"
                            : "bg-rose-100 text-rose-800"
                        }`}
                      >
                        Total Bobot: {total}% {isReady ? "✓" : "(Wajib 100%)"}
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        {/* Section 3: Retired / Historical Policies */}
        {retiredPolicies.length > 0 && (
          <div className="mb-8 rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
            <div className="flex items-center gap-2 mb-4 border-b border-stone-100 pb-3">
              <History className="h-5 w-5 text-stone-500" />
              <h3 className="text-base font-bold text-stone-900">
                Riwayat Kebijakan Penilaian Sebelumnya ({retiredPolicies.length}
                )
              </h3>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="border-b border-stone-200 bg-stone-50/70 text-xs font-semibold uppercase tracking-wider text-stone-600">
                  <tr>
                    <th className="px-4 py-3">Revisi</th>
                    <th className="px-4 py-3">KKM</th>
                    <th className="px-4 py-3">Komponen & Bobot</th>
                    <th className="px-4 py-3">Periode Berlaku</th>
                    <th className="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-stone-100">
                  {retiredPolicies.map((p) => (
                    <tr key={p.id} className="text-xs text-stone-600">
                      <td className="px-4 py-3 font-bold text-stone-900">
                        Revisi ke-{p.revision}
                      </td>
                      <td className="px-4 py-3 font-semibold text-emerald-900">
                        {p.kkm}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex flex-wrap gap-1.5">
                          {p.components.map((c) => (
                            <span
                              key={c.id}
                              className="rounded bg-stone-100 px-1.5 py-0.5 text-[11px]"
                            >
                              {c.scoring_component?.name}: {c.weight_percentage}
                              %
                            </span>
                          ))}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-stone-500">
                        Aktif: {p.activated_at || "-"}
                      </td>
                      <td className="px-4 py-3">
                        <span className="rounded bg-stone-200 px-2 py-0.5 text-[11px] font-bold text-stone-700">
                          Diarsipkan (Retired)
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </div>

      {/* Modal Buat Draf Kebijakan Baru */}
      {canManage && showCreateModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <div className="flex items-center justify-between border-b border-stone-100 pb-3 mb-4">
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Buat Draf Kebijakan Penilaian Baru
                </h3>
                <p className="text-xs text-stone-500 mt-0.5">
                  Atur KKM global dan persentase bobot setiap komponen penilaian
                  (F-04).
                </p>
              </div>
            </div>

            <form onSubmit={handleCreatePolicy} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  KKM Global Sekolah (0 - 100, maks 2 desimal)
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value={createForm.data.kkm}
                  onChange={(e) => createForm.setData("kkm", e.target.value)}
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none"
                />
                {createForm.errors.kkm && (
                  <p className="mt-1 text-xs text-red-600">
                    {createForm.errors.kkm}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-2">
                  Persentase Bobot Komponen (%)
                </label>
                <div className="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                  {availableComponents.map((comp, idx) => {
                    const currentVal =
                      createForm.data.components.find(
                        (c) => c.scoring_component_id === comp.id,
                      )?.weight_percentage || "0.00";

                    return (
                      <div
                        key={comp.id}
                        className="flex items-center justify-between gap-3 rounded-lg border border-stone-200 bg-stone-50/50 p-2.5 text-xs"
                      >
                        <div className="flex-1">
                          <p className="font-bold text-stone-800">
                            {comp.name}
                          </p>
                          <span className="text-[10px] text-stone-400 uppercase font-bold">
                            {comp.source_type === "attendance"
                              ? "Kehadiran Otomatis"
                              : "Skor Input"}
                          </span>
                        </div>
                        <div className="flex items-center gap-1.5 w-32">
                          <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            value={currentVal}
                            onChange={(e) => {
                              const newComps = createForm.data.components.map(
                                (c) =>
                                  c.scoring_component_id === comp.id
                                    ? {
                                        ...c,
                                        weight_percentage: e.target.value,
                                      }
                                    : c,
                              );
                              createForm.setData("components", newComps);
                            }}
                            className="w-full rounded-md border border-stone-300 px-2.5 py-1 text-xs text-right font-mono font-bold focus:border-emerald-600 focus:outline-none"
                          />
                          <span className="font-bold text-stone-600">%</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Total Weight Counter */}
              <div className="flex items-center justify-between rounded-xl bg-stone-100 p-3 text-xs">
                <span className="font-semibold text-stone-700">
                  Total Akumulasi Bobot:
                </span>
                <span
                  className={`font-black text-sm px-2.5 py-0.5 rounded ${
                    createTotal === "100.00"
                      ? "bg-emerald-100 text-emerald-800"
                      : "bg-rose-100 text-rose-800"
                  }`}
                >
                  {createTotal}%{" "}
                  {createTotal === "100.00" ? "(Tepat 100%)" : "(Belum 100%)"}
                </span>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-stone-100">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {createForm.processing
                    ? "Menyimpan..."
                    : "Simpan Draf Kebijakan"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Edit Draf Kebijakan */}
      {canManage && editingPolicy && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <h3 className="text-base font-bold text-stone-900 mb-1">
              Ubah Draf Kebijakan (Revisi ke-{editingPolicy.revision})
            </h3>
            <p className="text-xs text-stone-500 mb-4">
              Hanya draf yang belum aktif yang dapat dimodifikasi.
            </p>

            <form onSubmit={handleUpdatePolicy} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-1">
                  KKM Global Sekolah (0 - 100)
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value={editForm.data.kkm}
                  onChange={(e) => editForm.setData("kkm", e.target.value)}
                  required
                  className="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 mb-2">
                  Persentase Bobot Komponen (%)
                </label>
                <div className="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                  {availableComponents.map((comp) => {
                    const currentVal =
                      editForm.data.components.find(
                        (c) => c.scoring_component_id === comp.id,
                      )?.weight_percentage || "0.00";

                    return (
                      <div
                        key={comp.id}
                        className="flex items-center justify-between gap-3 rounded-lg border border-stone-200 bg-stone-50/50 p-2.5 text-xs"
                      >
                        <div className="flex-1">
                          <p className="font-bold text-stone-800">
                            {comp.name}
                          </p>
                          <span className="text-[10px] text-stone-400 uppercase font-bold">
                            {comp.source_type === "attendance"
                              ? "Kehadiran Otomatis"
                              : "Skor Input"}
                          </span>
                        </div>
                        <div className="flex items-center gap-1.5 w-32">
                          <input
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            value={currentVal}
                            onChange={(e) => {
                              const exists = editForm.data.components.some(
                                (c) => c.scoring_component_id === comp.id,
                              );
                              let newComps;
                              if (exists) {
                                newComps = editForm.data.components.map((c) =>
                                  c.scoring_component_id === comp.id
                                    ? {
                                        ...c,
                                        weight_percentage: e.target.value,
                                      }
                                    : c,
                                );
                              } else {
                                newComps = [
                                  ...editForm.data.components,
                                  {
                                    scoring_component_id: comp.id,
                                    weight_percentage: e.target.value,
                                  },
                                ];
                              }
                              editForm.setData("components", newComps);
                            }}
                            className="w-full rounded-md border border-stone-300 px-2.5 py-1 text-xs text-right font-mono font-bold focus:border-emerald-600 focus:outline-none"
                          />
                          <span className="font-bold text-stone-600">%</span>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="flex items-center justify-between rounded-xl bg-stone-100 p-3 text-xs">
                <span className="font-semibold text-stone-700">
                  Total Akumulasi Bobot:
                </span>
                <span
                  className={`font-black text-sm px-2.5 py-0.5 rounded ${
                    editTotal === "100.00"
                      ? "bg-emerald-100 text-emerald-800"
                      : "bg-rose-100 text-rose-800"
                  }`}
                >
                  {editTotal}%{" "}
                  {editTotal === "100.00" ? "(Tepat 100%)" : "(Belum 100%)"}
                </span>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-stone-100">
                <button
                  type="button"
                  onClick={() => setEditingPolicy(null)}
                  className="rounded-lg border border-stone-300 px-4 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={editForm.processing}
                  className="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  {editForm.processing ? "Menyimpan..." : "Perbarui Draf"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal Katalog Komponen Penilaian */}
      {canManage && showComponentModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/40 backdrop-blur-xs p-4">
          <div className="w-full max-w-xl rounded-2xl bg-white p-6 shadow-xl border border-stone-200">
            <div className="flex items-center justify-between border-b border-stone-100 pb-3 mb-4">
              <div>
                <h3 className="text-base font-bold text-stone-900">
                  Katalog Komponen Penilaian Sekolah
                </h3>
                <p className="text-xs text-stone-500 mt-0.5">
                  Daftar komponen penilaian yang dapat digunakan pada kebijakan
                  penilaian sekolah.
                </p>
              </div>
            </div>

            {/* List Existing Components */}
            <div className="mb-5 space-y-2 max-h-52 overflow-y-auto pr-1">
              {scoringComponents.map((comp) => (
                <div
                  key={comp.id}
                  className="flex items-center justify-between rounded-lg border border-stone-200 p-2.5 text-xs bg-stone-50/50"
                >
                  <div>
                    <div className="flex items-center gap-1.5 mb-0.5">
                      <span className="font-bold text-stone-900">
                        {comp.name}
                      </span>
                      <span className="font-mono text-[10px] bg-stone-200 px-1.5 py-0.5 rounded text-stone-700">
                        {comp.code}
                      </span>
                    </div>
                    <span className="text-[11px] text-stone-500">
                      Tipe:{" "}
                      {comp.source_type === "attendance"
                        ? "Kehadiran"
                        : "Nilai Input"}
                    </span>
                  </div>

                  <button
                    type="button"
                    onClick={() => handleToggleComponent(comp.id)}
                    className={`inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-bold transition ${
                      comp.is_active
                        ? "bg-emerald-100 text-emerald-800 hover:bg-emerald-200"
                        : "bg-stone-200 text-stone-600 hover:bg-stone-300"
                    }`}
                  >
                    {comp.is_active ? "Aktif" : "Nonaktif"}
                  </button>
                </div>
              ))}
            </div>

            {/* Form Tambah Komponen Baru */}
            <form
              onSubmit={handleCreateComponent}
              className="border-t border-stone-200 pt-4 space-y-3"
            >
              <h4 className="text-xs font-bold uppercase tracking-wider text-stone-700">
                Tambah Komponen Baru
              </h4>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Kode (Unik)
                  </label>
                  <input
                    type="text"
                    value={componentForm.data.code}
                    onChange={(e) =>
                      componentForm.setData("code", e.target.value)
                    }
                    placeholder="contoh: project_score"
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-stone-700 mb-1">
                    Nama Komponen
                  </label>
                  <input
                    type="text"
                    value={componentForm.data.name}
                    onChange={(e) =>
                      componentForm.setData("name", e.target.value)
                    }
                    placeholder="contoh: Nilai Proyek / Portofolio"
                    required
                    className="w-full rounded-lg border border-stone-300 px-3 py-1.5 text-xs focus:border-emerald-600 focus:outline-none"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setShowComponentModal(false)}
                  className="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-50"
                >
                  Tutup
                </button>
                <button
                  type="submit"
                  disabled={
                    componentForm.processing ||
                    !componentForm.data.code ||
                    !componentForm.data.name
                  }
                  className="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50"
                >
                  Tambah Komponen
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </AppLayout>
  );
}
