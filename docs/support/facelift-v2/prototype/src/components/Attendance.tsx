import { pupils } from "../data";
import { Button } from "./ui/button";
import { useState } from "react";
import { Dialog } from "./ui/dialog";
import { Badge } from "./ui/badge";
import { Alert } from "./ui/alert";
import { Input } from "./ui/input";
import { Label } from "./ui/label";
import { PupilIdentity } from "./PupilIdentity";
import { cn } from "./ui/utils";

export type AttendanceValues = Record<string, string>;
const options = ["Hadir", "Izin", "Sakit", "Alpa"];

export function Attendance({
  values,
  onChange,
  dirty,
  onSave,
  savedAt,
  finalized,
  onFinalize,
}: {
  values: AttendanceValues;
  onChange: (id: string, value: string) => void;
  dirty: boolean;
  onSave: () => void;
  savedAt: string;
  finalized: boolean;
  onFinalize: () => void;
}) {
  const [review, setReview] = useState(false);
  const recorded = Object.values(values).filter(Boolean).length;
  return (
    <>
      <div className="mb-5 flex items-start justify-between gap-4 max-[760px]:mb-4 max-[760px]:flex-col max-[760px]:gap-2">
        <div>
          <p className="mb-1 text-sm font-semibold text-muted-foreground">
            Presensi mata pelajaran
          </p>
          <h1>Presensi pertemuan 12</h1>
          <p className="mt-1.5 text-sm text-muted-foreground">
            Matematika · Kelas IV A · 22 Oktober 2026, 08.00–09.10
          </p>
        </div>
        <Badge variant="outline" className="mt-6 font-normal max-[760px]:mt-0">
          {finalized ? "Disahkan dalam demo" : "Draf presensi"}
        </Badge>
      </div>
      <Alert variant="warning" role="note">
        Khusus pertemuan Matematika, terpisah dari presensi harian kelas.
      </Alert>
      <section
        className="mt-4 overflow-hidden rounded-lg border bg-background"
        aria-labelledby="attendance-title"
      >
        <div className="flex min-h-16 items-center justify-between gap-4 px-5 py-3 max-[760px]:flex-col max-[760px]:items-stretch max-[760px]:gap-1 max-[760px]:px-4">
          <h2 id="attendance-title">Catat kehadiran</h2>
          <span className="text-sm text-muted-foreground">
            {recorded} dari {pupils.length} siswa dicatat
          </span>
        </div>
        <ul>
          {pupils.map((pupil, index) => (
            <li
              key={pupil.id}
              className={cn(
                "flex min-h-16 items-center justify-between gap-5 border-t border-row-border px-5 py-2 max-[900px]:flex-col max-[900px]:items-start max-[900px]:gap-2",
                "max-[760px]:px-4 max-[760px]:py-3",
              )}
            >
              <PupilIdentity
                number={index + 1}
                name={pupil.name}
                id={pupil.id}
              />
              <fieldset className="flex gap-[5px] max-[760px]:w-full max-[760px]:gap-1.5">
                <legend className="sr-only">Kehadiran {pupil.name}</legend>
                {options.map((option) => (
                  <Label
                    key={option}
                    className="relative font-normal leading-normal max-[760px]:flex-1"
                  >
                    <Input
                      variant="choice"
                      type="radio"
                      name={`attendance-${pupil.id}`}
                      value={option}
                      checked={values[pupil.id] === option}
                      disabled={finalized}
                      onChange={() => onChange(pupil.id, option)}
                    />
                    <span
                      className={cn(
                        "inline-flex min-h-11 cursor-pointer items-center rounded-[5px] border border-input px-3 text-sm",
                        "peer-checked:border-primary peer-checked:bg-accent peer-checked:font-semibold peer-checked:text-primary",
                        "peer-focus-visible:outline-2 peer-focus-visible:outline-offset-3 peer-focus-visible:outline-ring peer-disabled:cursor-default",
                        "max-[760px]:w-full max-[760px]:justify-center max-[760px]:px-1.5",
                      )}
                    >
                      {option}
                    </span>
                  </Label>
                ))}
              </fieldset>
            </li>
          ))}
        </ul>
      </section>
      <div className="mt-6 flex items-center justify-between gap-5 max-[1100px]:flex-col max-[1100px]:items-start max-[760px]:gap-[15px]">
        <p className="text-sm text-muted-foreground" role="status">
          {dirty
            ? "Ada perubahan presensi yang belum disimpan."
            : savedAt ||
              "Pilih status kehadiran setiap siswa. Tidak ada status otomatis."}
        </p>
        <div className="flex shrink-0 gap-2.5 max-[760px]:w-full max-[760px]:flex-col">
          <Button
            onClick={onSave}
            variant={dirty ? "default" : "outline"}
            disabled={!dirty || finalized}
          >
            Simpan presensi demo
          </Button>
          <Button
            variant={dirty ? "outline" : "default"}
            disabled={dirty || recorded !== pupils.length || finalized}
            onClick={() => setReview(true)}
          >
            Tinjau pengesahan
          </Button>
        </div>
      </div>
      <p className="mt-3 text-sm text-muted-foreground">
        {finalized
          ? "Isian dikunci setelah pengesahan. Koreksi sesi disahkan belum termasuk prototipe."
          : "Simpan draf dan isi semua siswa sebelum pengesahan. Draf belum tampil pada Perkembangan."}
      </p>
      <Dialog
        open={review}
        onOpenChange={setReview}
        title="Sahkan presensi pertemuan ini?"
        description="Simulasi pengesahan untuk Matematika, IV A, pertemuan 12. Status kehadiran anak akan muncul pada Perkembangan."
      >
        <dl className="my-6 text-sm [&>div]:flex [&>div]:justify-between [&>div]:gap-5 [&>div]:border-b [&>div]:py-2.5 [&_dt]:text-muted-foreground [&_dd]:text-right">
          {options.map((option) => (
            <div key={option}>
              <dt>{option}</dt>
              <dd>
                {
                  Object.values(values).filter((value) => value === option)
                    .length
                }{" "}
                siswa
              </dd>
            </div>
          ))}
        </dl>
        <div className="mt-6 flex flex-wrap justify-end gap-2.5 max-[760px]:flex-col max-[760px]:[&>button]:w-full">
          <Button variant="outline" onClick={() => setReview(false)}>
            Kembali periksa
          </Button>
          <Button
            onClick={() => {
              onFinalize();
              setReview(false);
            }}
          >
            Sahkan demo
          </Button>
        </div>
      </Dialog>
    </>
  );
}
