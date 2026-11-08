import { chromium } from "file:///C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.mjs";
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
const page = await browser.newPage();
const results = [];
for (const width of [320, 390, 768, 1440]) {
  await page.setViewportSize({ width, height: width === 390 ? 844 : 1000 });
  await page.goto(baseUrl, { waitUntil: "networkidle" });
  for (const [name, button] of [
    ["grades", "Nilai penilaian"],
    ["attendance", "Presensi mapel"],
    ["progress", "Perkembangan"],
    ["report", "Rapor semester"],
  ]) {
    const menu = page.getByRole("button", { name: "Pilih layar", exact: true });
    if (await menu.isVisible()) {
      await menu.click();
      const dialog = page.getByRole("dialog", {
        name: "Pilih layar tinjauan",
        exact: true,
      });
      await dialog.getByRole("button", { name: new RegExp(button) }).click();
      await dialog.waitFor({ state: "hidden" });
    } else {
      await page
        .getByRole("navigation", { name: "Layar prototipe", exact: true })
        .getByRole("button", { name: new RegExp(button) })
        .click();
    }
    await page.evaluate(
      () => new Promise((resolve) => requestAnimationFrame(resolve)),
    );
    await page.evaluate(() => {
      document.activeElement?.blur();
      window.scrollTo(0, 0);
    });
    await page.evaluate(
      () => new Promise((resolve) => requestAnimationFrame(resolve)),
    );
    const details = page.locator("details");
    await details.locator("summary").click();
    const scenarioSelect = await page
      .getByRole("combobox", { name: "Skenario demo" })
      .evaluate((select) => {
        const style = getComputedStyle(select);
        const canvas = document.createElement("canvas");
        const context = canvas.getContext("2d");
        context.font = style.font;
        const textWidth = context.measureText(
          select.selectedOptions[0].label,
        ).width;
        const availableWidth =
          select.getBoundingClientRect().width -
          parseFloat(style.paddingLeft) -
          parseFloat(style.paddingRight) -
          parseFloat(style.borderLeftWidth) -
          parseFloat(style.borderRightWidth);
        return {
          textWidth,
          availableWidth,
          textFits: textWidth <= availableWidth,
        };
      });
    await details.locator("summary").click();
    await page.evaluate(() => {
      document.activeElement?.blur();
      window.scrollTo(0, 0);
    });
    await page.screenshot({
      path: `${output}${name}-${width}.png`,
      fullPage: true,
    });
    results.push({
      width,
      screen: name,
      scenarioSelect,
      ...(await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        h1Count: document.querySelectorAll("h1").length,
        viewportHeight: innerHeight,
        firstGradeInput: (() => {
          const field = document.querySelector('input[id^="grade-"]');
          if (!field) return null;
          const { y, height } = field.getBoundingClientRect();
          return {
            y,
            height,
            viewportRatio: y / innerHeight,
            withinFirst60Percent: y <= innerHeight * 0.6,
          };
        })(),
        tinyText: [
          ...document.querySelectorAll(
            "main p, main span, main label, main button, main input, main strong",
          ),
        ]
          .filter(
            (node) =>
              node.getBoundingClientRect().width &&
              parseFloat(getComputedStyle(node).fontSize) < 14,
          )
          .map((node) => ({
            text: node.textContent?.slice(0, 70),
            size: getComputedStyle(node).fontSize,
          })),
      }))),
    });
  }
}
await writeFile(
  `${output}layout-inspection.json`,
  JSON.stringify(results, null, 2),
);
console.log(JSON.stringify(results));
await browser.close();
process.exitCode = results.some(
  (result) =>
    result.scrollWidth > result.clientWidth ||
    result.h1Count !== 1 ||
    !result.scenarioSelect.textFits ||
    result.tinyText.length > 0,
)
  ? 1
  : 0;
