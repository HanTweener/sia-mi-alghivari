import { chromium } from "file:///C:/Users/User/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright/index.mjs";
import assert from "node:assert/strict";
import { writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";

const root = new URL("../", import.meta.url);
const browser = await chromium.launch({
  headless: true,
  executablePath:
    "C:/Users/User/AppData/Local/ms-playwright/chromium_headless_shell-1248/chrome-headless-shell-win64/chrome-headless-shell.exe",
});
const context = await browser.newContext({ offline: true });
const errors = [];
const remoteRequests = [];
const page = await context.newPage();
page.on("pageerror", (error) => errors.push(error.message));
page.on("request", (request) => {
  if (/^https?:/.test(request.url())) remoteRequests.push(request.url());
});
const results = [];

for (const width of [390, 1440]) {
  await page.setViewportSize({ width, height: 1000 });
  for (const filename of [
    "facelift-sia-alghivari-v2.html",
    "kajian-layar-sia-alghivari-v2.html",
  ]) {
    await page.goto(new URL(filename, root).href);
    await page.locator("h1").waitFor();
    const dimensions = await page.evaluate(() => ({
      scrollWidth: document.documentElement.scrollWidth,
      clientWidth: document.documentElement.clientWidth,
    }));
    assert.equal(dimensions.scrollWidth, dimensions.clientWidth);
    if (filename.startsWith("facelift")) {
      const input = page.getByLabel(/Nilai Alya/);
      await input.fill("91");
      await page.getByRole("button", { name: "Simpan draf demo", exact: true }).click();
      assert.equal(await input.inputValue(), "91");
    }
    results.push({ filename, width, result: "PASS", ...dimensions });
  }
}

assert.equal(errors.length, 0, JSON.stringify(errors));
assert.equal(remoteRequests.length, 0, JSON.stringify(remoteRequests));
const report = { results, errors, remoteRequests, offline: true };
await writeFile(
  fileURLToPath(new URL("evidence/packaging-check.json", root)),
  JSON.stringify(report, null, 2),
);
console.log(JSON.stringify(report));
await browser.close();
