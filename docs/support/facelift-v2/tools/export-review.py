"""Export inspected prototype captures to a readable, screen-sized review PDF.

Run from the primary repository with the bundled Python (reportlab + Pillow).
This creates a design review document, not an academic report-card template.
"""

from pathlib import Path
import re

from PIL import Image
from reportlab.lib.colors import HexColor
from reportlab.pdfgen.canvas import Canvas
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "sia-alghivari-facelift-v2.pdf"
FONT_ROOT = Path("C:/Windows/Fonts")
pdfmetrics.registerFont(TTFont("Review", str(FONT_ROOT / "segoeui.ttf")))
pdfmetrics.registerFont(TTFont("ReviewBold", str(FONT_ROOT / "segoeuib.ttf")))

TOKENS = dict(re.findall(
    r"--([a-z-]+): (#[0-9a-fA-F]{6});",
    (ROOT / "prototype/src/styles.css").read_text(encoding="utf-8"),
))
INK = HexColor(TOKENS["foreground"])
MUTED = HexColor(TOKENS["muted-foreground"])
GREEN = HexColor(TOKENS["primary"])
RULE = HexColor(TOKENS["border"])
PAPER = HexColor(TOKENS["canvas"])


def text(canvas, x, y, value, size=20, bold=False, color=INK):
    canvas.setFillColor(color)
    canvas.setFont("ReviewBold" if bold else "Review", size)
    canvas.drawString(x, y, value)


def base_page(canvas, width, height, label, page):
    canvas.setPageSize((width, height))
    canvas.setFillColor(PAPER)
    canvas.rect(0, 0, width, height, fill=1, stroke=0)
    text(canvas, 64, height - 50, "MI ALGHIVARI / REGISTER AKADEMIK", 17, True, GREEN)
    text(canvas, 64, height - 98, label, 34, True)
    canvas.setStrokeColor(RULE)
    canvas.line(64, 64, width - 64, 64)
    text(canvas, 64, 34, "Usulan visual / data sintetis / 29 Oktober 2026", 15, color=MUTED)
    text(canvas, width - 98, 34, f"{page:02d}", 15, True, GREEN)


def cover(canvas):
    width, height = 1568, 1120
    base_page(canvas, width, height, "Register Akademik Terang - rebuild skill", 1)
    text(canvas, 64, 906, "Konteks jelas. Satu kegiatan. Keadaan data terbaca.", 34, True)
    paragraphs = [
        "Arah Register Akademik menggunakan daftar berbaris, tipografi yang terbaca,",
        "dan tindakan simpan, bagikan, serta sahkan yang memiliki akibat berbeda.",
        "Tidak memakai slogan, kalender dekoratif atau angka dashboard tanpa keputusan terkait.",
        "",
        "Empat layar interaktif: penilaian, kehadiran, perkembangan anak dan rapor kosong.",
        "Tujuh kajian visual melengkapi login, pekerjaan guru, administrasi, aturan global,",
        "persiapan rapor, keputusan kenaikan dan audit. Form pada kajian tersebut nonaktif.",
        "",
        "Anti Slop DURING / Taste Accent ladder / Ponytail full; tanpa dependency baru.",
        "React/TypeScript + Tailwind CSS + komponen lokal shadcn/ui.",
        "Semua data contoh sintetis. Simpan hanya dalam memori dan hilang saat muat ulang.",
        "Belum ada server, autentikasi, transaksi, otorisasi objek atau PDF rapor resmi.",
        "",
        "Arah visual masih PROPOSED. Batas Q6/Q7 dan keputusan kanonis tetap terbuka.",
        "Buka README.md untuk prototipe, spesifikasi, source dan laporan QA beserta batasnya.",
    ]
    y = 820
    for paragraph in paragraphs:
        text(canvas, 64, y, paragraph, 23, color=MUTED)
        y -= 42
    text(canvas, 64, 140, "Dokumen ini adalah bahan tinjauan desain, bukan bukti aplikasi produksi siap.", 21, True)
    canvas.showPage()


def capture_page(canvas, title, note, relative, page):
    source = ROOT / relative
    with Image.open(source) as image:
        image_width, image_height = image.size
    width = 1568
    shown_width = width - 128
    shown_height = shown_width * image_height / image_width
    height = shown_height + 246
    base_page(canvas, width, height, title, page)
    text(canvas, 64, height - 140, note, 18, color=MUTED)
    canvas.drawImage(
        str(source), 64, 82, width=shown_width, height=shown_height,
        preserveAspectRatio=True, mask="auto",
    )
    canvas.showPage()


def mobile_page(canvas, page, items):
    width, height = 1568, 1760
    base_page(canvas, width, height, "Adaptasi ponsel - dua tugas inti", page)
    text(canvas, 64, height - 144, "Cuplikan atas viewport 390 CSS px; daftar lengkap tetap dapat digulir.", 19, color=MUTED)
    for index, (label, name) in enumerate(items):
        source = ROOT / "qa" / "evidence" / f"{name}-390.png"
        with Image.open(source) as image:
            crop = image.crop((0, 0, image.width, min(image.height, 900)))
            crop_path = ROOT / "evidence" / f"mobile-{name}-crop.png"
            crop.save(crop_path)
        x = 110 + index * 740
        y_top = 1520
        text(canvas, x, y_top + 38, label, 22, True)
        shown_width = 560
        shown_height = shown_width * crop.height / crop.width
        canvas.drawImage(str(crop_path), x, y_top - shown_height,
                         width=shown_width, height=shown_height)
    canvas.showPage()


if __name__ == "__main__":
    document = Canvas(str(OUTPUT), pageCompression=1)
    document.setTitle("SIA MI Alghivari - Register Akademik Terang")
    document.setAuthor("SIA MI Alghivari - bahan tinjauan proyek")
    cover(document)
    pages = [
        ("Penilaian - satu kegiatan", "Interaktif: blank dan 0 berbeda; simpan dan bagikan terpisah.", "qa/evidence/grades-1440.png"),
        ("Kehadiran - satu pertemuan mapel", "Interaktif: status eksplisit; draf dan pengesahan terpisah.", "qa/evidence/attendance-1440.png"),
        ("Perkembangan Belajar", "Interaktif: pilih anak dan mapel; aktivitas yang dibagikan/disahkan.", "qa/evidence/progress-1440.png"),
        ("Rapor Online", "Interaktif: rapor belum tersedia; akses perkembangan tetap terbuka.", "qa/evidence/report-1440.png"),
        ("Masuk", "Kajian visual: form nonaktif, akun disediakan sekolah.", "evidence/study-login-1440.png"),
        ("Pekerjaan mengajar", "Kajian visual: lanjutkan kegiatan dan baca jadwal; tanpa kartu KPI dekoratif.", "evidence/study-jobs-1440.png"),
        ("Administrasi akademik", "Kajian visual: struktur kelas/penugasan dan batas varian Data Entry.", "evidence/study-admin-1440.png"),
        ("Aturan penilaian global", "Kajian visual: semua angka kebijakan adalah contoh, bukan aturan sekolah.", "evidence/study-policy-1440.png"),
        ("Persiapan rapor", "Kajian visual: finalisasi, siapkan PDF, publish; hak wali kelas binaan.", "evidence/study-reports-1440.png"),
        ("Keputusan kenaikan", "Kajian visual: persetujuan kepala sekolah tidak langsung menerapkan keputusan.", "evidence/study-decisions-1440.png"),
        ("Audit dan inbox", "Kajian visual: histori ditulis sistem; tanpa aksi ubah/hapus audit.", "evidence/study-audit-1440.png"),
    ]
    for page_number, (title, note, relative) in enumerate(pages, start=2):
        capture_page(document, title, note, relative, page_number)
    mobile_page(document, len(pages) + 2, [
        ("Penilaian", "grades"), ("Kehadiran", "attendance"),
    ])
    mobile_page(document, len(pages) + 3, [
        ("Perkembangan", "progress"), ("Rapor Online", "report"),
    ])
    document.save()
    print(OUTPUT)
