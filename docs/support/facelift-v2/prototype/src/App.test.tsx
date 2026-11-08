import { render, screen, within } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it } from "vitest";
import App from "./App";
import { gradeValid, pupils } from "./data";

describe("academic register prototype", () => {
  it("keeps zero distinct from blank and filters without losing edits", async () => {
    const user = userEvent.setup();
    render(<App />);
    expect(screen.getByLabelText("Nilai Bagas Contoh")).toHaveValue("0");
    expect(
      screen.getByLabelText("Nilai Bagas Contoh"),
    ).toHaveAccessibleDescription(/Nilai 0 dihitung/);
    expect(screen.getByLabelText("Nilai Citra Contoh")).toHaveValue("");
    await user.click(screen.getByLabelText("Hanya yang belum diisi"));
    expect(
      screen.queryByLabelText("Nilai Bagas Contoh"),
    ).not.toBeInTheDocument();
    expect(screen.getByLabelText("Nilai Citra Contoh")).toBeInTheDocument();
    await user.type(screen.getByLabelText("Nilai Citra Contoh"), "72,5");
    await user.click(screen.getByLabelText("Hanya yang belum diisi"));
    expect(screen.getByLabelText("Nilai Citra Contoh")).toHaveValue("72,5");
    await user.type(screen.getByLabelText("Cari siswa"), "no-match");
    expect(screen.getByText(/Tidak ada siswa yang cocok/)).toBeInTheDocument();
    await user.clear(screen.getByLabelText("Cari siswa"));
    expect(screen.getByLabelText("Nilai Citra Contoh")).toHaveValue("72,5");
  });

  it("validates decimal assumptions and blocks invalid saves", async () => {
    expect(["", "0", "100", "78,5", "78.50"].every(gradeValid)).toBe(true);
    expect(["101", "-1", "1,000", "1.2.3", "abc"].some(gradeValid)).toBe(false);
    const user = userEvent.setup();
    render(<App />);
    const grade = screen.getByLabelText("Nilai Alya Contoh");
    await user.clear(grade);
    await user.type(grade, "101");
    expect(grade).toHaveAttribute("aria-invalid", "true");
    expect(grade).toHaveAccessibleDescription(/Isi 0–100/);
    expect(
      screen.getByRole("button", { name: "Simpan draf demo" }),
    ).toBeDisabled();
    expect(screen.getByRole("alert")).toHaveTextContent("Alya Contoh");
  });

  it("restores focus after Escape and permits explicit partial publication", async () => {
    const user = userEvent.setup();
    render(<App />);
    const reviewButton = screen.getByRole("button", {
      name: "Tinjau pembagian",
    });
    await user.click(reviewButton);
    expect(screen.getByRole("dialog")).toHaveTextContent(
      "3 siswa belum memiliki nilai",
    );
    expect(screen.getByRole("dialog")).toHaveTextContent("IV A · Matematika");
    expect(screen.getByRole("dialog")).toHaveTextContent("Ganjil 2026/2027");
    expect(
      screen.getByRole("button", { name: "Bagikan hasil demo" }),
    ).toBeDisabled();
    await user.keyboard("{Escape}");
    expect(reviewButton).toHaveFocus();
    await user.click(reviewButton);
    await user.click(
      screen.getByRole("checkbox", { name: /Saya sudah meninjau/ }),
    );
    await user.click(
      screen.getByRole("button", { name: "Bagikan hasil demo" }),
    );
    expect(screen.getByLabelText("Nilai Alya Contoh")).toHaveAttribute(
      "readonly",
    );
    await user.click(
      screen.getByRole("button", { name: /Perkembangan Orang tua/ }),
    );
    expect(screen.getByText("PH-03 · Pecahan sederhana")).toBeInTheDocument();
    await user.selectOptions(screen.getByLabelText("Pilih anak demo"), "D-002");
    const publishedRow = screen
      .getByText("PH-03 · Pecahan sederhana")
      .closest("li")!;
    expect(within(publishedRow).getByText("0")).toBeInTheDocument();
  });

  it("protects dirty navigation and discards only unsaved changes", async () => {
    const user = userEvent.setup();
    render(<App />);
    await user.clear(screen.getByLabelText("Nilai Alya Contoh"));
    await user.type(screen.getByLabelText("Nilai Alya Contoh"), "99");
    await user.click(
      screen.getByRole("button", { name: /Presensi mapel Guru/ }),
    );
    expect(screen.getByRole("dialog")).toHaveAccessibleName(
      "Perubahan belum disimpan",
    );
    await user.click(screen.getByRole("button", { name: "Kembali mengisi" }));
    expect(screen.getByLabelText("Nilai Alya Contoh")).toHaveValue("99");
    await user.click(
      screen.getByRole("button", { name: /Presensi mapel Guru/ }),
    );
    await user.click(
      screen.getByRole("button", { name: "Buang perubahan & pindah" }),
    );
    await user.click(
      screen.getByRole("button", { name: /Nilai penilaian Guru/ }),
    );
    expect(screen.getByLabelText("Nilai Alya Contoh")).toHaveValue("85");
  });

  it("requires complete saved attendance then publishes the signed snapshot", async () => {
    const user = userEvent.setup();
    render(<App />);
    await user.click(
      screen.getByRole("button", { name: /Presensi mapel Guru/ }),
    );
    expect(
      screen.getByRole("button", { name: "Tinjau pengesahan" }),
    ).toBeDisabled();
    for (const pupil of pupils) {
      const group = screen.getByRole("group", {
        name: `Kehadiran ${pupil.name}`,
      });
      await user.click(within(group).getByRole("radio", { name: "Hadir" }));
    }
    expect(
      screen.getByRole("button", { name: "Tinjau pengesahan" }),
    ).toBeDisabled();
    await user.click(
      screen.getByRole("button", { name: "Simpan presensi demo" }),
    );
    await user.click(screen.getByRole("button", { name: "Tinjau pengesahan" }));
    await user.click(screen.getByRole("button", { name: "Sahkan demo" }));
    expect(screen.getAllByRole("radio")[0]).toBeDisabled();
    await user.click(
      screen.getByRole("button", { name: /Perkembangan Orang tua/ }),
    );
    expect(screen.getByText("Presensi · Pertemuan 12")).toBeInTheDocument();
    expect(screen.getByText("Hadir")).toBeInTheDocument();
  });

  it("switches child fixtures, filters actual subjects and opens progress from empty report", async () => {
    const user = userEvent.setup();
    render(<App />);
    await user.click(
      screen.getByRole("button", { name: /Perkembangan Orang tua/ }),
    );
    expect(screen.getByText("88")).toBeInTheDocument();
    await user.selectOptions(screen.getByLabelText("Pilih anak demo"), "D-002");
    expect(screen.getByText("76")).toBeInTheDocument();
    expect(screen.queryByText("88")).not.toBeInTheDocument();
    await user.selectOptions(
      screen.getByLabelText("Mata pelajaran"),
      "Bahasa Indonesia",
    );
    expect(screen.getByText("Membaca nyaring")).toBeInTheDocument();
    expect(
      screen.queryByText("PH-01 · Bilangan cacah"),
    ).not.toBeInTheDocument();
    await user.click(
      screen.getByRole("button", { name: /Rapor semester Orang tua/ }),
    );
    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent(
      "Rapor belum tersedia",
    );
    await user.click(
      screen.getByRole("button", { name: "Lihat Perkembangan Belajar" }),
    );
    expect(screen.getByRole("heading", { level: 1 })).toHaveTextContent(
      "Bagas",
    );
  });

  it("preserves edits through explicitly simulated failure and conflict states", async () => {
    const user = userEvent.setup();
    render(<App />);
    await user.clear(screen.getByLabelText("Nilai Alya Contoh"));
    await user.type(screen.getByLabelText("Nilai Alya Contoh"), "92");
    const summary = screen.getByText("Prototipe · Data sintetis · Memori sesi");
    expect(summary.closest("details")).not.toHaveAttribute("open");
    await user.click(summary);
    expect(summary.closest("details")).toHaveAttribute("open");
    for (const scenario of ["loading", "empty", "error", "conflict"]) {
      await user.selectOptions(
        screen.getByLabelText("Skenario demo"),
        scenario,
      );
      expect(
        screen.queryByLabelText("Nilai Alya Contoh"),
      ).not.toBeInTheDocument();
      expect(screen.getByRole("heading", { level: 1 })).toBeInTheDocument();
      await user.selectOptions(
        screen.getByLabelText("Skenario demo"),
        "normal",
      );
      expect(screen.getByLabelText("Nilai Alya Contoh")).toHaveValue("92");
    }
    await user.click(summary);
    expect(summary.closest("details")).not.toHaveAttribute("open");
  });

  it("keeps saved assessment and unsigned attendance out of guardian progress", async () => {
    const user = userEvent.setup();
    render(<App />);
    await user.clear(screen.getByLabelText("Nilai Alya Contoh"));
    await user.type(screen.getByLabelText("Nilai Alya Contoh"), "93");
    await user.click(screen.getByRole("button", { name: "Simpan draf demo" }));
    await user.click(
      screen.getByRole("button", { name: /Presensi mapel Guru/ }),
    );
    await user.click(
      within(
        screen.getByRole("group", { name: "Kehadiran Alya Contoh" }),
      ).getByRole("radio", { name: "Hadir" }),
    );
    await user.click(
      screen.getByRole("button", { name: "Simpan presensi demo" }),
    );
    await user.click(
      screen.getByRole("button", { name: /Perkembangan Orang tua/ }),
    );
    expect(
      screen.queryByText("PH-03 · Pecahan sederhana"),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByText("Presensi · Pertemuan 12"),
    ).not.toBeInTheDocument();
  });
});
