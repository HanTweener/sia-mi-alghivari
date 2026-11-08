import { chromium } from "file:///C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.mjs";
import { mkdir, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";

const output = fileURLToPath(new URL("../evidence/", import.meta.url));
await mkdir(output, { recursive: true });
const browser = await chromium.launch({
  headless: true,
  executablePath:
    "C:/Users/User/AppData/Local/ms-playwright/chromium_headless_shell-1248/chrome-headless-shell-win64/chrome-headless-shell.exe",
});
const page = await browser.newPage();
const errors = [];
page.on("pageerror", (error) => errors.push(error.message));
const results = [];

for (const width of [390, 1440]) {
  await page.setViewportSize({ width, height: 1050 });
  for (const screen of [
    "login", "jobs", "admin", "policy", "reports", "decisions", "audit",
  ]) {
    await page.goto(
      `http://127.0.0.1:5173/studies.html?screen=${screen}`,
      { waitUntil: "networkidle" },
    );
    await page.screenshot({
      path: `${output}study-${screen}-${width}.png`,
      fullPage: true,
    });
    results.push({
      screen,
      width,
      ...await page.evaluate(() => ({
        scrollWidth: document.documentElement.scrollWidth,
        clientWidth: document.documentElement.clientWidth,
        heading: document.querySelector("h1")?.textContent,
        headingCount: document.querySelectorAll("h1").length,
      })),
    });
  }
}

await writeFile(
  `${output}study-layout.json`,
  JSON.stringify({ results, errors }, null, 2),
);
console.log(JSON.stringify({ results, errors }));
await browser.close();
