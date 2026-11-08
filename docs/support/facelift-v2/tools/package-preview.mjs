/** Package each Vite entry into a generated, self-contained HTML review file.
 * Run: node ../tools/package-preview.mjs (cwd: prototype/).
 * Editable source remains in prototype/src; these HTML files are build output.
 */
import { readFile, writeFile } from "node:fs/promises";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { build } from "../prototype/node_modules/vite/dist/node/index.js";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const prototype = resolve(root, "prototype");
const entries = [
  ["index.html", "facelift-sia-alghivari-v2.html"],
  ["studies.html", "kajian-layar-sia-alghivari-v2.html"],
];

for (const [entry, filename] of entries) {
  const directory = resolve(prototype, "dist", `standalone-${entry.split(".")[0]}`);
  await build({
    root: prototype,
    configFile: resolve(prototype, "vite.config.ts"),
    build: {
      outDir: directory,
      emptyOutDir: true,
      rollupOptions: {
        input: resolve(prototype, entry),
        output: { inlineDynamicImports: true, manualChunks: undefined },
      },
    },
  });
  let html = await readFile(resolve(directory, entry), "utf8");
  const cssLinks = [...html.matchAll(/<link\b[^>]*rel="stylesheet"[^>]*href="([^"]+)"[^>]*>/g)];
  for (const match of cssLinks) {
    const css = await readFile(resolve(directory, match[1]), "utf8");
    html = html.replace(match[0], () => `<style>${css}</style>`);
  }
  const scripts = [...html.matchAll(/<script\b[^>]*src="([^"]+)"[^>]*><\/script>/g)];
  if (scripts.length !== 1) throw new Error(`Expected one bundle for ${entry}`);
  for (const match of scripts) {
    let javascript = await readFile(resolve(directory, match[1]), "utf8");
    if (/\bfrom\s*["']\.\//.test(javascript)) {
      throw new Error(`Unexpected relative bundle import in ${entry}`);
    }
    javascript = javascript.replaceAll("./index.html", "./facelift-sia-alghivari-v2.html");
    html = html.replace(
      match[0],
      () => `<script type="module">${javascript.replaceAll("</script", "<\\/script")}</script>`,
    );
  }
  html = html.replace(/<link\b[^>]*rel="modulepreload"[^>]*>/g, "");
  html = html.replace(
    "<head>",
    "<head>\n<!-- GENERATED preview: edit prototype/src and rebuild; do not hand-edit. -->",
  );
  await writeFile(resolve(root, filename), html);
  console.log(`Packaged ${filename}`);
}
