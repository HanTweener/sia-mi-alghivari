import assert from "node:assert/strict";
import { createHash } from "node:crypto";
import { readFile, writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { resolve } from "node:path";

const baseUrl = process.argv[2] || "http://127.0.0.1:5174/";
const qaDir = fileURLToPath(new URL("./", import.meta.url));
const dist = resolve(qaDir, "../prototype/dist");
const hash = (data) => createHash("sha256").update(data).digest("hex");
const index = await readFile(resolve(dist, "index.html"));
const assets = [...index.toString().matchAll(/(?:src|href)="([^"]+\.(?:js|css))"/g)]
  .map((match) => match[1]);
assert.ok(assets.length > 0, "Built entry references actual JS/CSS assets");
const results = [];
for (const path of ["index.html", ...assets]) {
  const url = new URL(path, baseUrl);
  const response = await fetch(url);
  assert.equal(response.status, 200, url.href);
  const received = Buffer.from(await response.arrayBuffer());
  const source = await readFile(resolve(dist, path.replace(/^\.\//, "")));
  const expectedHash = hash(source);
  const receivedHash = hash(received);
  assert.equal(receivedHash, expectedHash, `${path} served exactly from current dist`);
  results.push({ path, expectedHash, receivedHash, bytes: received.length, status: "PASS" });
}
const record = { capturedAt: new Date().toISOString(), baseUrl, results };
await writeFile(resolve(qaDir, "evidence/served-build-sha256.json"), JSON.stringify(record, null, 2));
console.log(JSON.stringify(record, null, 2));
