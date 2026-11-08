import { useRef, useState } from "react";
import {
  BookOpen,
  CalendarDays,
  FileText,
  ListChecks,
  Menu,
} from "lucide-react";
import { GradeRegister } from "./components/GradeRegister";
import { Attendance, type AttendanceValues } from "./components/Attendance";
import { Guardian } from "./components/Guardian";
import { StatePanel } from "./components/StatePanel";
import { Button } from "./components/ui/button";
import { Dialog } from "./components/ui/dialog";
import { Label } from "./components/ui/label";
import { NativeSelect } from "./components/ui/native-select";
import { Alert } from "./components/ui/alert";
import { cn } from "./components/ui/utils";
import {
  initialGrades,
  type GradeValues,
  type Scenario,
  type Screen,
} from "./data";

const screens = [
  { id: "grades", label: "Nilai penilaian", role: "Guru", icon: ListChecks },
  {
    id: "attendance",
    label: "Presensi mapel",
    role: "Guru",
    icon: CalendarDays,
  },
  { id: "progress", label: "Perkembangan", role: "Orang tua", icon: BookOpen },
  { id: "report", label: "Rapor semester", role: "Orang tua", icon: FileText },
] as const;

export default function App() {
  const [screen, setScreen] = useState<Screen>("grades");
  const [scenario, setScenario] = useState<Scenario>("normal");
  const [grades, setGrades] = useState<GradeValues>({ ...initialGrades });
  const [savedGrades, setSavedGrades] = useState<GradeValues>({
    ...initialGrades,
  });
  const [gradeSavedAt, setGradeSavedAt] = useState("");
  const [published, setPublished] = useState<GradeValues | null>(null);
  const [attendance, setAttendance] = useState<AttendanceValues>({});
  const [savedAttendance, setSavedAttendance] = useState<AttendanceValues>({});
  const [attendanceSavedAt, setAttendanceSavedAt] = useState("");
  const [finalAttendance, setFinalAttendance] =
    useState<AttendanceValues | null>(null);
  const [child, setChild] = useState("D-001");
  const [pendingScreen, setPendingScreen] = useState<Screen | null>(null);
  const [notice, setNotice] = useState("");
  const [menuOpen, setMenuOpen] = useState(false);
  const menuSelected = useRef(false);
  const headingRef = useRef<HTMLElement>(null);
  const gradeDirty = JSON.stringify(grades) !== JSON.stringify(savedGrades);
  const attendanceDirty =
    JSON.stringify(attendance) !== JSON.stringify(savedAttendance);
  const guardian = screen === "progress" || screen === "report";

  function move(next: Screen) {
    menuSelected.current = menuOpen;
    setMenuOpen(false);
    if (next === screen) return;
    if (
      (screen === "grades" && gradeDirty) ||
      (screen === "attendance" && attendanceDirty)
    ) {
      setPendingScreen(next);
      return;
    }
    navigate(next);
  }

  function navigate(next: Screen) {
    setScreen(next);
    setScenario("normal");
    setNotice("");
    requestAnimationFrame(() => headingRef.current?.focus());
  }

  function discardAndMove() {
    if (screen === "grades") setGrades({ ...savedGrades });
    if (screen === "attendance") setAttendance({ ...savedAttendance });
    if (pendingScreen) navigate(pendingScreen);
    setPendingScreen(null);
  }

  return (
    <>
      <a
        className="fixed -top-[100px] left-3 z-[60] bg-background p-3 focus:top-2"
        href="#main"
      >
        Lewati ke isi utama
      </a>
      <details className="border-b border-toolbar-border bg-toolbar px-6 text-sm max-[760px]:px-4">
        <summary className="min-h-11 content-center py-2 font-medium">
          Prototipe · Data sintetis · Memori sesi
        </summary>
        <div className="flex flex-wrap items-center justify-between gap-3 pb-3">
          <p>Tanpa akun atau server. Muat ulang menghapus perubahan.</p>
          <Label className="gap-2.5 font-normal leading-normal whitespace-nowrap max-[760px]:w-full max-[760px]:justify-between">
            Skenario demo
            <NativeSelect
              className="bg-muted"
              containerClassName="shrink-0"
              aria-label="Skenario demo"
              value={scenario}
              onChange={(event) => setScenario(event.target.value as Scenario)}
            >
              <option value="normal">Data normal</option>
              <option value="loading">Memuat</option>
              <option value="empty">Kosong</option>
              <option value="error">Gagal memuat</option>
              <option value="conflict">Konflik versi</option>
            </NativeSelect>
          </Label>
        </div>
      </details>
      <div className="grid min-h-[calc(100dvh-44px)] grid-cols-[240px_minmax(0,1fr)] max-[1100px]:block">
        <aside
          className={cn(
            "flex flex-col border-r border-sidebar-border bg-sidebar px-4 py-8 text-sidebar-foreground",
            "max-[1100px]:flex-row max-[1100px]:items-center max-[1100px]:justify-between max-[1100px]:border-r-0 max-[1100px]:border-b max-[1100px]:px-4 max-[1100px]:py-2",
            "[&_button]:focus-visible:outline-sidebar-ring",
          )}
        >
          <div className="px-3 pb-8 max-[1100px]:px-0 max-[1100px]:pb-0">
            <p className="text-[19px] font-[650] tracking-[-0.04em] max-[760px]:text-lg">
              SIA Alghivari
            </p>
            <span className="text-sm text-sidebar-muted max-[1100px]:hidden">
              Register Akademik
            </span>
          </div>
          <Button
            variant="default"
            size="sm"
            className="hidden max-[1100px]:inline-flex"
            onClick={() => {
              menuSelected.current = false;
              setMenuOpen(true);
            }}
            aria-haspopup="dialog"
          >
            <Menu size={18} aria-hidden="true" /> Pilih layar
          </Button>
          <p className="px-3 pb-3 text-sm text-sidebar-muted max-[1100px]:hidden">
            Layar tinjauan
          </p>
          <nav
            aria-label="Layar prototipe"
            className="grid gap-1 max-[1100px]:hidden"
          >
            {screens.map((item) => (
              <Button
                variant="ghost"
                key={item.id}
                className={cn(
                  "min-h-12 w-full justify-start gap-3 rounded-md px-3 py-2 text-left text-sm font-normal text-sidebar-foreground hover:bg-sidebar-hover",
                  "aria-[current=page]:bg-sidebar-accent aria-[current=page]:font-semibold aria-[current=page]:text-sidebar-accent-foreground",
                )}
                aria-current={screen === item.id ? "page" : undefined}
                onClick={() => move(item.id)}
              >
                <item.icon size={18} aria-hidden="true" />
                <span>
                  {item.label}
                  <small className="mt-[3px] block text-sm font-normal text-inherit">
                    {item.role}
                  </small>
                </span>
              </Button>
            ))}
          </nav>
          <div className="mx-3 mt-auto border-t border-sidebar-border pt-5 text-sm max-[1100px]:hidden">
            <p className="mb-2 font-semibold">
              {guardian ? "Konteks orang tua" : "Konteks mengajar"}
            </p>
            <span className="block leading-[1.8] text-sidebar-muted">
              {guardian ? "Dua anak sintetis" : "Matematika · IV A"}
            </span>
            <span className="block leading-[1.8] text-sidebar-muted">
              Ganjil 2026/2027
            </span>
          </div>
        </aside>
        <div className="min-w-0">
          <header className="mx-auto flex min-h-16 max-w-[1040px] items-center justify-between gap-4 px-8 text-sm max-[760px]:min-h-12 max-[760px]:px-4">
            <p>
              <span className="text-sm text-muted-foreground">
                {guardian ? "Orang tua" : "Guru mata pelajaran"}
              </span>
            </p>
            {guardian ? (
              <Label className="gap-3 font-normal leading-normal text-muted-foreground max-[760px]:flex-col max-[760px]:items-start max-[760px]:gap-0.5">
                Anak
                <NativeSelect
                  className="max-[760px]:max-w-[145px]"
                  aria-label="Pilih anak demo"
                  value={child}
                  onChange={(event) => setChild(event.target.value)}
                >
                  <option value="D-001">Alya Contoh</option>
                  <option value="D-002">Bagas Contoh</option>
                </NativeSelect>
              </Label>
            ) : (
              <span className="text-right text-sm text-muted-foreground">
                Ganjil · 2026/2027
              </span>
            )}
          </header>
          <main
            id="main"
            ref={headingRef}
            tabIndex={-1}
            className={cn(
              "mx-auto max-w-[1040px] px-8 pt-2 pb-6 outline-none",
              "max-[760px]:px-4 max-[760px]:pt-2",
            )}
          >
            {notice && (
              <Alert role="status" variant="success" className="mb-[25px]">
                {notice}
              </Alert>
            )}
            {scenario !== "normal" ? (
              <>
                <h1>
                  {screen === "grades"
                    ? "Nilai pecahan sederhana"
                    : screen === "attendance"
                      ? "Presensi pertemuan 12"
                      : screen === "progress"
                        ? "Perkembangan Belajar"
                        : "Rapor semester"}
                </h1>
                <StatePanel
                  scenario={scenario}
                  onRetry={() => setScenario("normal")}
                />
              </>
            ) : screen === "grades" ? (
              <GradeRegister
                values={grades}
                onChange={(id, value) => {
                  setGrades((current) => ({ ...current, [id]: value }));
                  setNotice("");
                }}
                dirty={gradeDirty}
                savedAt={gradeSavedAt}
                published={published}
                onSave={() => {
                  setSavedGrades({ ...grades });
                  setGradeSavedAt("Draf tersimpan di memori sesi ini.");
                  setNotice("Draf demo tersimpan di memori. Belum dibagikan.");
                }}
                onPublish={() => {
                  setPublished({ ...grades });
                  setNotice(
                    "Nilai demo dibagikan. Buka Perkembangan untuk melihat nilai anak yang terisi.",
                  );
                }}
              />
            ) : screen === "attendance" ? (
              <Attendance
                finalized={finalAttendance !== null}
                onFinalize={() => {
                  setFinalAttendance({ ...attendance });
                  setNotice(
                    "Presensi disahkan dalam memori demo. Isian kini dikunci.",
                  );
                }}
                values={attendance}
                onChange={(id, value) => {
                  setAttendance((current) => ({ ...current, [id]: value }));
                  setNotice("");
                }}
                dirty={attendanceDirty}
                savedAt={attendanceSavedAt}
                onSave={() => {
                  setSavedAttendance({ ...attendance });
                  setAttendanceSavedAt(
                    "Presensi tersimpan di memori sesi ini.",
                  );
                  setNotice(
                    "Presensi demo tersimpan di memori untuk pertemuan Matematika ini.",
                  );
                }}
              />
            ) : (
              <Guardian
                report={screen === "report"}
                child={child}
                published={published}
                attendance={finalAttendance}
                onProgress={() => move("progress")}
              />
            )}
            <footer className="mt-9 border-t pt-[18px] text-sm leading-[1.8] text-muted-foreground">
              Tinjauan facelift v2 · Ruang lingkup prototipe: 4 layar · Seluruh
              nama dan nilai adalah contoh sintetis.
            </footer>
          </main>
        </div>
      </div>
      <Dialog
        open={menuOpen}
        onOpenChange={setMenuOpen}
        title="Pilih layar tinjauan"
        description="Guru dan orang tua adalah skenario tinjauan. Ini bukan perpindahan hak akses akun."
        onReturnFocus={(opener) => {
          if (pendingScreen) return;
          (menuSelected.current ? headingRef.current : opener)?.focus();
        }}
      >
        <nav aria-label="Layar prototipe ponsel" className="mt-4 grid gap-2">
          {screens.map((item) => (
            <Button
              key={item.id}
              variant={screen === item.id ? "secondary" : "ghost"}
              className="justify-start"
              aria-current={screen === item.id ? "page" : undefined}
              onClick={() => move(item.id)}
            >
              <item.icon size={18} aria-hidden="true" />
              {item.label} · {item.role}
            </Button>
          ))}
        </nav>
      </Dialog>
      <Dialog
        open={pendingScreen !== null}
        onOpenChange={(open) => {
          if (!open) setPendingScreen(null);
        }}
        title="Perubahan belum disimpan"
        description="Ada isian yang belum disimpan ke draf demo. Kembali untuk menyimpannya, atau buang perubahan terakhir dan pindah layar."
        onReturnFocus={(opener) => {
          const connectedControl =
            opener?.isConnected &&
            opener !== document.body &&
            opener !== document.documentElement;
          (connectedControl ? opener : headingRef.current)?.focus();
        }}
      >
        <div className="mt-6 flex flex-wrap justify-end gap-2.5 max-[760px]:flex-col max-[760px]:[&>button]:w-full">
          <Button variant="outline" onClick={() => setPendingScreen(null)}>
            Kembali mengisi
          </Button>
          <Button onClick={discardAndMove}>Buang perubahan & pindah</Button>
        </div>
      </Dialog>
    </>
  );
}
