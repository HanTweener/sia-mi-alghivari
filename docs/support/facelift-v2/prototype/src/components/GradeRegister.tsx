import { useMemo, useState } from "react";
import { Search } from "lucide-react";
import { gradeValid, pupils, type GradeValues } from "../data";
import { Button } from "./ui/button";
import { Dialog } from "./ui/dialog";
import { Input } from "./ui/input";
import { Label } from "./ui/label";
import { Alert } from "./ui/alert";
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from "./ui/table";
import { PupilIdentity } from "./PupilIdentity";
import { cn } from "./ui/utils";

export function GradeRegister({
  values,
  onChange,
  onSave,
  dirty,
  savedAt,
  published,
  onPublish,
}: {
  values: GradeValues;
  onChange: (id: string, value: string) => void;
  onSave: () => void;
  dirty: boolean;
  savedAt: string;
  published: GradeValues | null;
  onPublish: () => void;
}) {
  const [query, setQuery] = useState("");
  const [onlyBlank, setOnlyBlank] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [review, setReview] = useState(false);
  const [confirmed, setConfirmed] = useState(false);
  const visible = useMemo(
    () =>
      pupils.filter(
        (pupil) =>
          `${pupil.name} ${pupil.id}`
            .toLowerCase()
            .includes(query.toLowerCase()) &&
          (!onlyBlank || values[pupil.id] === "" || editingId === pupil.id),
      ),
    [query, onlyBlank, values, editingId],
  );
  const filled = pupils.filter((pupil) => values[pupil.id] !== "").length;
  const invalid = pupils.filter((pupil) => !gradeValid(values[pupil.id]));
  const blank = pupils.length - filled;

  return (
    <>
      <div className="mb-5 flex items-start justify-between gap-4 max-[760px]:mb-4 max-[760px]:flex-col max-[760px]:gap-2">
        <div>
          <div className="mb-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
            <p className="font-semibold">Penilaian harian · PH-03</p>
            <span>{published ? "Dibagikan dalam demo" : "Draf penilaian"}</span>
          </div>
          <h1>Nilai pecahan sederhana</h1>
          <p className="mt-1.5 text-sm text-muted-foreground">
            Matematika · Kelas IV A · 22 Oktober 2026
          </p>
        </div>
      </div>

      <div className="flex items-center gap-6 pb-4 max-[1100px]:flex-wrap max-[1100px]:gap-x-6 max-[1100px]:gap-y-2">
        <div className="flex items-baseline gap-2 text-sm">
          <span className="text-muted-foreground">Terisi</span>
          <strong className="font-semibold">
            {filled} dari {pupils.length} siswa
          </strong>
        </div>
        <div className="flex items-baseline gap-2 text-sm">
          <span className="text-muted-foreground">Belum diisi</span>
          <strong className="font-semibold">{blank} siswa</strong>
        </div>
      </div>

      <section
        aria-labelledby="roster-title"
        className="overflow-hidden rounded-lg border bg-background"
      >
        <div className="flex min-h-16 items-center justify-between gap-4 px-5 py-3 max-[760px]:flex-col max-[760px]:items-stretch max-[760px]:gap-2 max-[760px]:px-4">
          <h2 id="roster-title">Daftar nilai siswa</h2>
          <Label className="min-h-11 gap-2.5 rounded-md border px-3 font-normal text-muted-foreground">
            <Search size={17} aria-hidden="true" />
            <span className="sr-only">Cari siswa</span>
            <Input
              variant="search"
              className="w-[195px] max-[760px]:w-full"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              placeholder="Cari nama atau ID demo"
            />
          </Label>
        </div>
        <Label className="gap-2.5 px-5 pb-2 font-normal leading-normal max-[760px]:px-4">
          <Input
            variant="checkbox"
            type="checkbox"
            checked={onlyBlank}
            onChange={(event) => setOnlyBlank(event.target.checked)}
          />
          Hanya yang belum diisi
        </Label>
        <p className="px-5 pb-3 text-sm text-muted-foreground max-[760px]:px-4">
          {visible.length} siswa ditampilkan
        </p>
        <Table
          className="w-full border-collapse text-left"
          aria-label="Daftar nilai"
        >
          <TableHeader>
            <TableRow
              className={cn(
                "grid min-h-10 grid-cols-[minmax(0,1fr)_120px_130px] items-center gap-5 border-y px-5 py-2 text-sm text-muted-foreground hover:bg-transparent",
                "max-[1100px]:grid-cols-[minmax(0,1fr)_110px_95px] max-[1100px]:gap-3.5 max-[760px]:hidden",
              )}
            >
              <TableHead
                className="h-auto p-0 text-muted-foreground"
                scope="col"
              >
                Siswa
              </TableHead>
              <TableHead
                className="h-auto p-0 text-right text-muted-foreground"
                scope="col"
              >
                Nilai / 100
              </TableHead>
              <TableHead
                className="h-auto p-0 text-muted-foreground"
                scope="col"
              >
                Status isian
              </TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {visible.map((pupil) => {
              const valid = gradeValid(values[pupil.id]);
              return (
                <TableRow
                  key={pupil.id}
                  className={cn(
                    "grid min-h-16 grid-cols-[minmax(0,1fr)_120px_130px] items-center gap-5 border-row-border px-5 py-2 text-sm last:border-b-0",
                    "max-[1100px]:grid-cols-[minmax(0,1fr)_110px_95px] max-[1100px]:gap-3.5",
                    "max-[760px]:grid-cols-[minmax(0,1fr)_auto] max-[760px]:gap-x-3 max-[760px]:gap-y-1 max-[760px]:border-t max-[760px]:px-4 max-[760px]:py-3",
                  )}
                >
                  <TableCell className="min-w-0 p-0 whitespace-normal">
                    <PupilIdentity
                      number={pupils.indexOf(pupil) + 1}
                      name={pupil.name}
                      id={pupil.id}
                    />
                  </TableCell>
                  <TableCell
                    className={cn(
                      "min-w-0 p-0 text-right whitespace-normal",
                      "max-[760px]:col-start-2 max-[760px]:row-start-1 max-[760px]:row-span-2 max-[760px]:max-w-[115px] max-[760px]:text-right",
                    )}
                  >
                    <Label className="sr-only" htmlFor={`grade-${pupil.id}`}>
                      Nilai {pupil.name}
                    </Label>
                    <Input
                      id={`grade-${pupil.id}`}
                      variant="score"
                      className="max-[760px]:w-[76px]"
                      inputMode="decimal"
                      readOnly={published !== null}
                      value={values[pupil.id]}
                      onFocus={() => setEditingId(pupil.id)}
                      onBlur={() => setEditingId(null)}
                      onChange={(event) =>
                        onChange(pupil.id, event.target.value)
                      }
                      aria-invalid={!valid}
                      aria-describedby={
                        !valid ? `grade-help error-${pupil.id}` : "grade-help"
                      }
                    />
                    {!valid && (
                      <span
                        className="mt-[5px] block text-sm text-destructive"
                        id={`error-${pupil.id}`}
                      >
                        Isi 0–100, maksimal 2 angka desimal, atau kosongkan.
                      </span>
                    )}
                  </TableCell>
                  <TableCell
                    className={cn(
                      "min-w-0 p-0 whitespace-normal max-[760px]:col-start-1 max-[760px]:row-start-2 max-[760px]:pl-[33px]",
                      !valid
                        ? "text-destructive"
                        : values[pupil.id] === ""
                          ? "text-muted-foreground"
                          : "text-primary",
                    )}
                  >
                    {!valid
                      ? "Perlu diperbaiki"
                      : values[pupil.id] === ""
                        ? "Belum diisi"
                        : "Terisi"}
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
        {visible.length === 0 && (
          <p className="p-8 text-muted-foreground" role="status">
            Tidak ada siswa yang cocok. Ubah kata pencarian.
          </p>
        )}
      </section>
      <p id="grade-help" className="mt-3 text-sm text-muted-foreground">
        Nilai 0 dihitung; kosong berarti belum dinilai. Asumsi demo: 0–100,
        maksimal 2 desimal; koma atau titik.
      </p>

      {invalid.length > 0 && (
        <p role="alert" className="mt-4 block text-sm text-destructive">
          Perbaiki nilai untuk {invalid.map((pupil) => pupil.name).join(", ")}{" "}
          sebelum menyimpan.
        </p>
      )}
      {published && (
        <p className="mt-4 text-sm text-muted-foreground">
          Hasil telah dibagikan. Isian dikunci; alur koreksi hasil dibagikan
          belum termasuk prototipe.
        </p>
      )}
      <div className="mt-6 flex items-center justify-between gap-5 max-[1100px]:flex-col max-[1100px]:items-start max-[760px]:gap-[15px]">
        <p role="status" className="text-sm text-muted-foreground">
          {dirty
            ? "Ada perubahan yang belum disimpan."
            : savedAt || "Draf contoh dimuat. Belum ada penyimpanan sesi ini."}
        </p>
        <div className="flex shrink-0 gap-2.5 max-[760px]:w-full max-[760px]:flex-col">
          <Button
            variant={dirty ? "default" : "outline"}
            disabled={published !== null || !dirty || invalid.length > 0}
            onClick={onSave}
          >
            Simpan draf demo
          </Button>
          <Button
            variant={dirty ? "outline" : "default"}
            disabled={
              published !== null || invalid.length > 0 || filled === 0 || dirty
            }
            onClick={() => {
              setConfirmed(false);
              setReview(true);
            }}
          >
            Tinjau pembagian
          </Button>
        </div>
      </div>
      {dirty && (
        <p className="mt-2 text-sm text-muted-foreground">
          Simpan draf sebelum meninjau pembagian. Penyimpanan hanya berlaku
          sampai halaman dimuat ulang.
        </p>
      )}
      <Dialog
        open={review}
        onOpenChange={setReview}
        title="Bagikan hasil penilaian?"
        description="Simulasi pembagian satu penilaian. Ini tidak memfinalisasi rapor dan tidak mengirim notifikasi."
      >
        <dl className="my-6 text-sm [&>div]:flex [&>div]:justify-between [&>div]:gap-5 [&>div]:border-b [&>div]:py-2.5 [&_dt]:text-muted-foreground [&_dd]:text-right">
          <div>
            <dt>Kelas / mapel</dt>
            <dd>IV A · Matematika</dd>
          </div>
          <div>
            <dt>Periode</dt>
            <dd>Ganjil 2026/2027</dd>
          </div>
          <div>
            <dt>Penilaian</dt>
            <dd>PH-03 · Pecahan sederhana</dd>
          </div>
          <div>
            <dt>Nilai terisi</dt>
            <dd>{filled} siswa</dd>
          </div>
          <div>
            <dt>Belum diisi</dt>
            <dd>{blank} siswa</dd>
          </div>
        </dl>
        {blank > 0 && (
          <Alert role="note" variant="warning">
            {blank} siswa belum memiliki nilai. Nilai yang kosong tetap kosong;
            pembagian sebagian diperbolehkan dalam demo.
          </Alert>
        )}
        <Label className="my-4 gap-3 font-normal leading-[1.7]">
          <Input
            variant="checkbox"
            className="w-[19px]"
            type="checkbox"
            checked={confirmed}
            onChange={(event) => setConfirmed(event.target.checked)}
          />
          Saya sudah meninjau cakupan nilai yang akan dibagikan.
        </Label>
        <div className="mt-6 flex flex-wrap justify-end gap-2.5 max-[760px]:flex-col max-[760px]:[&>button]:w-full">
          <Button variant="outline" onClick={() => setReview(false)}>
            Kembali periksa
          </Button>
          <Button
            disabled={!confirmed}
            onClick={() => {
              onPublish();
              setReview(false);
            }}
          >
            Bagikan hasil demo
          </Button>
        </div>
      </Dialog>
    </>
  );
}
