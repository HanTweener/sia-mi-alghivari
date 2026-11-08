import type { Scenario } from "../data";
import { Button } from "./ui/button";

export function StatePanel({
  scenario,
  onRetry,
}: {
  scenario: Scenario;
  onRetry: () => void;
}) {
  const content = {
    normal: ["", ""],
    loading: [
      "Memuat daftar siswa…",
      "Simulasi pemuatan. Tidak ada permintaan jaringan.",
    ],
    empty: [
      "Belum ada siswa dalam konteks ini",
      "Daftar kosong adalah skenario prototipe. Tidak ada data yang dihapus.",
    ],
    error: [
      "Daftar belum berhasil dimuat",
      "Simulasi gangguan koneksi. Perubahan yang sudah diisi tetap ada di memori.",
    ],
    conflict: [
      "Versi data telah berubah",
      "Simulasi konflik: penyimpanan diblokir. Pada aplikasi, bandingkan versi server sebelum mengirim ulang.",
    ],
  }[scenario];

  return (
    <section
      className="mt-[30px] rounded-lg border bg-background px-8 py-12"
      role={
        scenario === "error" || scenario === "conflict" ? "alert" : "status"
      }
    >
      <h2>{content[0]}</h2>
      <p className="mt-2 max-w-lg text-sm text-muted-foreground">
        {content[1]}
      </p>
      {scenario !== "loading" && (
        <Button variant="outline" className="mt-5" onClick={onRetry}>
          Kembali ke data demo
        </Button>
      )}
    </section>
  );
}
