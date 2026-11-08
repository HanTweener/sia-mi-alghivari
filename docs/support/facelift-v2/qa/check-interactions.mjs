import { chromium } from "file:///C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.mjs";
import assert from "node:assert/strict";
import { mkdir, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";

const output = fileURLToPath(new URL("./evidence/", import.meta.url));
const baseUrl =
  process.argv[2] || process.env.QA_BASE_URL || "http://127.0.0.1:5173/";
await mkdir(output, { recursive: true });
const browser = await chromium.launch({
  headless: true,
  executablePath:
    "C:/Users/User/AppData/Local/ms-playwright/chromium_headless_shell-1248/chrome-headless-shell-win64/chrome-headless-shell.exe",
});
const results = [];
const testedAssets = new Set();
async function test(
  id,
  description,
  check,
  viewport = { width: 1440, height: 1000 },
) {
  const page = await browser.newPage({ viewport });
  page.setDefaultTimeout(5000);
  const errors = [];
  page.on("pageerror", (error) => errors.push(error.message));
  try {
    await page.goto(baseUrl, { waitUntil: "networkidle" });
    for (const asset of await page
      .locator("script[src]")
      .evaluateAll((scripts) => scripts.map((script) => script.src))) {
      testedAssets.add(asset);
    }
    await check(page);
    assert.deepEqual(errors, [], "No browser runtime errors");
    results.push({ id, description, status: "PASS" });
    console.log(`${id} PASS`);
  } catch (error) {
    results.push({
      id,
      description,
      status: "FAIL",
      error: error.message,
      stack: error.stack,
      pageErrors: errors,
    });
    console.log(`${id} FAIL: ${error.message}`);
    await page.screenshot({
      path: `${output}failure-${id}.png`,
      fullPage: true,
    });
  } finally {
    await page.close();
  }
}
async function navigate(page, name) {
  const menu = page.getByRole("button", { name: "Pilih layar", exact: true });
  if (await menu.isVisible()) {
    await menu.click();
    const dialog = page.getByRole("dialog", {
      name: "Pilih layar tinjauan",
      exact: true,
    });
    await dialog.getByRole("button", { name: new RegExp(name) }).click();
    await dialog.waitFor({ state: "hidden" });
  } else {
    await page
      .getByRole("navigation", { name: "Layar prototipe", exact: true })
      .getByRole("button", { name: new RegExp(name) })
      .click();
  }
}
async function setScenario(page, value) {
  const details = page.locator("details").filter({
    has: page.getByText("Prototipe · Data sintetis · Memori sesi", {
      exact: true,
    }),
  });
  if ((await details.getAttribute("open")) === null) {
    await details.locator("summary").click();
  }
  await page
    .getByRole("combobox", { name: "Skenario demo" })
    .selectOption(value);
  await details.locator("summary").click();
}
const grade = (page, name) =>
  page.getByRole("textbox", { name: `Nilai ${name}`, exact: true });
const gradeFields = (page) => page.getByRole("textbox", { name: /^Nilai / });
const progress = (page) =>
  page.getByRole("region", { name: "Aktivitas yang dibagikan", exact: true });
async function settleFocus(page) {
  await page.evaluate(
    () =>
      new Promise((resolve) => {
        requestAnimationFrame(() => requestAnimationFrame(resolve));
      }),
  );
}
async function assertMainFocus(page) {
  await settleFocus(page);
  assert.equal(
    await page
      .getByRole("main")
      .evaluate((node) => node === document.activeElement),
    true,
    `Main should receive focus; actual: ${await page.evaluate(() => document.activeElement?.outerHTML.slice(0, 250))}`,
  );
}

await test(
  "T01",
  "Blank differs from 0 and fixture counters reflect all eight rows",
  async (page) => {
    assert.equal(await grade(page, "Bagas Contoh").inputValue(), "0");
    assert.equal(await grade(page, "Citra Contoh").inputValue(), "");
    assert.equal(await grade(page, "Damar Contoh").inputValue(), "78,5");
    assert.equal(await gradeFields(page).count(), 8);
    assert.equal(
      await page.getByText("5 dari 8 siswa", { exact: true }).isVisible(),
      true,
    );
    assert.equal(
      await page.getByText("3 siswa", { exact: true }).isVisible(),
      true,
    );
  },
);

await test(
  "T02",
  "Real search and blank filter preserve values and recover from no matches",
  async (page) => {
    await page.getByRole("textbox", { name: "Cari siswa" }).fill("Bagas");
    assert.equal(await gradeFields(page).count(), 1);
    assert.equal(await grade(page, "Bagas Contoh").inputValue(), "0");
    assert.equal(await page.getByText(/1 siswa ditampilkan/).isVisible(), true);
    await page
      .getByRole("textbox", { name: "Cari siswa" })
      .fill("tidak-ada-xyz");
    assert.equal(await gradeFields(page).count(), 0);
    assert.equal(
      await page
        .getByText("Tidak ada siswa yang cocok. Ubah kata pencarian.")
        .isVisible(),
      true,
    );
    await page.getByRole("textbox", { name: "Cari siswa" }).fill("");
    await page
      .getByRole("checkbox", { name: /Hanya yang belum diisi/ })
      .check();
    assert.equal(await gradeFields(page).count(), 3);
    await page
      .getByRole("checkbox", { name: /Hanya yang belum diisi/ })
      .uncheck();
    assert.equal(await grade(page, "Bagas Contoh").inputValue(), "0");
  },
);

await test(
  "T03",
  "Format/range negatives block save; comma/dot decimals and boundaries are accepted",
  async (page) => {
    const field = grade(page, "Alya Contoh");
    for (const invalid of [
      "-1",
      "101",
      "1,234",
      "1.000,00",
      "1e2",
      " ",
      "abc",
    ]) {
      await field.fill(invalid);
      assert.equal(await field.getAttribute("aria-invalid"), "true", invalid);
      assert.equal(
        await page
          .getByRole("button", { name: "Simpan draf demo", exact: true })
          .isDisabled(),
        true,
      );
      assert.equal(
        await page
          .getByRole("button", { name: /Tinjau pembagian/ })
          .isDisabled(),
        true,
      );
    }
    for (const valid of ["0", "100", "78,25", "78.25", ""]) {
      await field.fill(valid);
      assert.equal(await field.getAttribute("aria-invalid"), "false", valid);
    }
  },
);

await test(
  "T04",
  "Save is memory-only, partial publish reviewed, parent sees snapshot, draft locked",
  async (page) => {
    await grade(page, "Alya Contoh").fill("91,25");
    await page
      .getByRole("button", { name: "Simpan draf demo", exact: true })
      .click();
    assert.match(await page.locator("main").innerText(), /memori/);
    await page.getByRole("button", { name: /Tinjau pembagian/ }).click();
    const dialog = page.getByRole("dialog");
    assert.match(await dialog.innerText(), /5 siswa[\s\S]*3 siswa/);
    assert.match(
      await dialog.innerText(),
      /tidak.*rapor|tidak memfinalisasi rapor/i,
    );
    assert.equal(
      await dialog
        .getByRole("button", { name: "Bagikan hasil demo", exact: true })
        .isDisabled(),
      true,
    );
    await dialog.getByRole("checkbox").check();
    await dialog
      .getByRole("button", { name: "Bagikan hasil demo", exact: true })
      .click();
    assert.equal(await grade(page, "Alya Contoh").isEditable(), false);
    await navigate(page, "Perkembangan");
    assert.match(await page.locator("main").innerText(), /91[,.]25/);
    await page
      .getByRole("combobox", { name: "Pilih anak demo" })
      .selectOption("D-002");
    assert.match(await page.locator("main").innerText(), /Bagas/);
    assert.equal(
      await progress(page)
        .getByRole("listitem")
        .filter({ hasText: "PH-03" })
        .getByText("0", { exact: true })
        .isVisible(),
      true,
    );
    await navigate(page, "Rapor semester");
    assert.match(
      await page.locator("main").innerText(),
      /Rapor belum tersedia/,
    );
    assert.equal(await page.getByRole("button", { name: /unduh/i }).count(), 0);
    await page.reload({ waitUntil: "networkidle" });
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "85");
    assert.deepEqual(
      await page.evaluate(() => ({
        local: localStorage.length,
        session: sessionStorage.length,
      })),
      { local: 0, session: 0 },
    );
  },
);

await test(
  "T05",
  "Review traps keyboard, cancels without mutation and restores opener focus",
  async (page) => {
    const opener = page.getByRole("button", { name: /Tinjau pembagian/ });
    await opener.click();
    for (let index = 0; index < 12; index += 1) {
      await page.keyboard.press("Tab");
      assert.equal(
        await page.evaluate(() =>
          Boolean(document.activeElement?.closest('[role="dialog"]')),
        ),
        true,
      );
    }
    await page.keyboard.press("Escape");
    assert.equal(await page.getByRole("dialog").count(), 0);
    await settleFocus(page);
    assert.equal(
      await opener.evaluate((node) => node === document.activeElement),
      true,
    );
    assert.equal(await grade(page, "Alya Contoh").isEditable(), true);
    await grade(page, "Alya Contoh").focus();
    await page.keyboard.press("Enter");
    assert.equal(await page.getByRole("dialog").count(), 0);
  },
);

await test(
  "T06",
  "Dirty navigation cancel/discard behaves as stated",
  async (page) => {
    await grade(page, "Alya Contoh").fill("9");
    await navigate(page, "Presensi mapel");
    assert.match(
      await page.getByRole("dialog").innerText(),
      /Perubahan belum disimpan/,
    );
    await page
      .getByRole("button", { name: "Kembali mengisi", exact: true })
      .click();
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "9");
    await navigate(page, "Presensi mapel");
    await page
      .getByRole("button", { name: "Buang perubahan & pindah", exact: true })
      .click();
    await navigate(page, "Nilai penilaian");
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "85");
  },
);

await test(
  "T07",
  "Attendance explicit statuses, save then review/finalize and read-only snapshot",
  async (page) => {
    await navigate(page, "Presensi mapel");
    assert.equal(await page.locator('input[type="radio"]:checked').count(), 0);
    const review = page.getByRole("button", {
      name: "Tinjau pengesahan",
      exact: true,
    });
    assert.equal(await review.isDisabled(), true);
    assert.equal(
      await page.getByRole("button", { name: /semua hadir/i }).count(),
      0,
    );
    const rows = page.getByRole("group", { name: /^Kehadiran / });
    for (let index = 0; index < 8; index += 1) {
      await rows
        .nth(index)
        .getByRole("radio", {
          name: ["Hadir", "Izin", "Sakit", "Alpa"][index % 4],
          exact: true,
        })
        .check();
    }
    assert.equal(await review.isDisabled(), true);
    await page
      .getByRole("button", { name: "Simpan presensi demo", exact: true })
      .click();
    await review.click();
    assert.match(
      await page.getByRole("dialog").innerText(),
      /Matematika[\s\S]*IV A/,
    );
    await page
      .getByRole("button", { name: "Sahkan demo", exact: true })
      .click();
    assert.equal(
      await page.locator('input[type="radio"]:disabled').count(),
      32,
    );
    assert.equal(
      await page
        .getByRole("radio")
        .evaluateAll((radios) =>
          radios.every((radio) => getComputedStyle(radio).opacity === "0"),
        ),
      true,
    );
    await page.setViewportSize({ width: 390, height: 1000 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({
      path: `${output}attendance-signed-390.png`,
      fullPage: true,
    });
    await navigate(page, "Perkembangan");
    assert.match(
      await page.locator("main").innerText(),
      /pertemuan 12[\s\S]*Hadir/i,
    );
  },
);

await test(
  "T08",
  "Simulated loading/empty/error/conflict are labeled and recover without losing memory input",
  async (page) => {
    await grade(page, "Alya Contoh").fill("92");
    for (const state of ["loading", "empty", "error", "conflict"]) {
      await setScenario(page, state);
      assert.match(
        await page.getByRole("main").innerText(),
        /Simulasi|skenario prototipe/i,
      );
      assert.equal(await page.getByRole("heading", { level: 1 }).count(), 1);
      assert.equal(
        await page
          .getByRole("button", { name: "Simpan draf demo", exact: true })
          .count(),
        0,
      );
      await setScenario(page, "normal");
      assert.equal(await grade(page, "Alya Contoh").inputValue(), "92");
    }
  },
);

await test(
  "T09",
  "Progress mapel filter counts and report empty CTA operate on selected child",
  async (page) => {
    await navigate(page, "Perkembangan");
    assert.equal(await progress(page).getByRole("listitem").count(), 3);
    await page
      .getByRole("combobox", { name: "Mata pelajaran" })
      .selectOption("Bahasa Indonesia");
    assert.equal(await progress(page).getByRole("listitem").count(), 1);
    assert.equal(
      await progress(page)
        .getByText(/1 aktivitas ditampilkan/)
        .isVisible(),
      true,
    );
    assert.match(await progress(page).innerText(), /86/);
    await page
      .getByRole("combobox", { name: "Pilih anak demo" })
      .selectOption("D-002");
    assert.match(await progress(page).innerText(), /79/);
    assert.doesNotMatch(await progress(page).innerText(), /86/);
    await navigate(page, "Rapor semester");
    await page
      .getByRole("button", { name: "Lihat Perkembangan Belajar", exact: true })
      .click();
    assert.match(
      await page.getByRole("heading", { level: 1 }).innerText(),
      /Bagas/,
    );
  },
);

await test(
  "T10",
  "Skip link reaches main; small dialog fits viewport; reduced motion removes animation",
  async (page) => {
    await page.setViewportSize({ width: 320, height: 800 });
    await page.keyboard.press("Tab");
    assert.equal(
      await page
        .getByRole("link", { name: "Lewati ke isi utama" })
        .evaluate((node) => node === document.activeElement),
      true,
    );
    await page.keyboard.press("Enter");
    assert.equal(
      await page
        .locator("main")
        .evaluate((node) => node === document.activeElement),
      true,
    );
    await page.getByRole("button", { name: /Tinjau pembagian/ }).click();
    const rect = await page.getByRole("dialog").boundingBox();
    assert.ok(rect.x >= 0 && rect.width <= 320);
    assert.ok(rect.y >= 0 && rect.height <= 800);
    await page
      .getByRole("dialog")
      .getByRole("button", { name: "Kembali periksa" })
      .click();
    await page.emulateMedia({ reducedMotion: "reduce" });
    assert.equal(
      await page.evaluate(
        () => matchMedia("(prefers-reduced-motion: reduce)").matches,
      ),
      true,
    );
    const animations = await page
      .locator("main")
      .evaluate((node) => node.getAnimations({ subtree: true }).length);
    assert.equal(animations, 0);
  },
);

await test(
  "T11",
  "Typing multiple digits into blank-filter result keeps edited row until blur",
  async (page) => {
    await page
      .getByRole("checkbox", { name: /Hanya yang belum diisi/ })
      .check();
    const field = grade(page, "Citra Contoh");
    await field.focus();
    await field.pressSequentially("97", { delay: 80 });
    assert.equal(await field.inputValue(), "97");
    await page.getByRole("heading", { level: 1 }).click();
    assert.equal(await gradeFields(page).count(), 2);
    await page
      .getByRole("checkbox", { name: /Hanya yang belum diisi/ })
      .uncheck();
    assert.equal(await grade(page, "Citra Contoh").inputValue(), "97");
  },
);

await test(
  "T12",
  "Mobile menu Escape restores focus; navigation closes; dirty cancel/discard retains intent",
  async (page) => {
    const menu = page.getByRole("button", { name: "Pilih layar", exact: true });
    const menuDialog = page.getByRole("dialog", {
      name: "Pilih layar tinjauan",
      exact: true,
    });
    await menu.click();
    assert.equal(await menuDialog.isVisible(), true);
    await page.keyboard.press("Escape");
    await menuDialog.waitFor({ state: "hidden" });
    await settleFocus(page);
    assert.equal(
      await menu.evaluate((node) => node === document.activeElement),
      true,
      `Menu opener should regain focus; actual: ${await page.evaluate(() => document.activeElement?.outerHTML.slice(0, 250))}`,
    );

    await navigate(page, "Presensi mapel");
    assert.equal(
      await page.getByRole("heading", { level: 1 }).innerText(),
      "Presensi pertemuan 12",
    );
    assert.equal(await page.getByRole("dialog").count(), 0);
    await assertMainFocus(page);
    await navigate(page, "Nilai penilaian");
    await grade(page, "Alya Contoh").fill("94");
    await navigate(page, "Presensi mapel");
    const warning = page.getByRole("dialog", {
      name: "Perubahan belum disimpan",
      exact: true,
    });
    await warning.waitFor({ state: "visible" });
    assert.equal(await page.getByRole("dialog").count(), 1);
    await warning
      .getByRole("button", { name: "Kembali mengisi", exact: true })
      .click();
    await warning.waitFor({ state: "hidden" });
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "94");
    await assertMainFocus(page);
    await navigate(page, "Presensi mapel");
    await warning.waitFor({ state: "visible" });
    await page.keyboard.press("Escape");
    await warning.waitFor({ state: "hidden" });
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "94");
    await assertMainFocus(page);
    await navigate(page, "Presensi mapel");
    await warning
      .getByRole("button", { name: "Buang perubahan & pindah", exact: true })
      .click();
    await warning.waitFor({ state: "hidden" });
    await assertMainFocus(page);
    assert.equal(
      await page.getByRole("heading", { level: 1 }).innerText(),
      "Presensi pertemuan 12",
    );
    await navigate(page, "Nilai penilaian");
    assert.equal(await grade(page, "Alya Contoh").inputValue(), "85");
  },
  { width: 390, height: 844 },
);

await test(
  "T13",
  "Saved assessment and unsigned saved attendance remain absent from guardian progress",
  async (page) => {
    await grade(page, "Alya Contoh").fill("93");
    await page
      .getByRole("button", { name: "Simpan draf demo", exact: true })
      .click();
    await navigate(page, "Presensi mapel");
    await page
      .getByRole("group", { name: "Kehadiran Alya Contoh", exact: true })
      .getByRole("radio", { name: "Hadir", exact: true })
      .check();
    await page
      .getByRole("button", { name: "Simpan presensi demo", exact: true })
      .click();
    await navigate(page, "Perkembangan");
    assert.doesNotMatch(
      await progress(page).innerText(),
      /PH-03|Pertemuan 12|93/,
    );
  },
);

await writeFile(
  `${output}interaction-results.json`,
  JSON.stringify(
    {
      date: new Date().toISOString(),
      baseUrl,
      browser: await browser.version(),
      testedAssets: [...testedAssets],
      results,
    },
    null,
    2,
  ),
);
console.log(JSON.stringify(results, null, 2));
await browser.close();
process.exitCode = results.some((result) => result.status === "FAIL") ? 1 : 0;
