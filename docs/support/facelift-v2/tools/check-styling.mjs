/** Verify the owner's styling boundary against human-maintained source.
 * This checks source structure, not visual correctness or accessibility.
 */
import assert from "node:assert/strict";
import { readdir, readFile, writeFile } from "node:fs/promises";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import postcss from "../prototype/node_modules/postcss/lib/postcss.mjs";
import ts from "../prototype/node_modules/typescript/lib/typescript.js";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const source = join(root, "prototype", "src");
const files = [];
async function visit(directory) {
  for (const item of await readdir(directory, { withFileTypes: true })) {
    const path = join(directory, item.name);
    if (item.isDirectory()) await visit(path);
    else files.push(path);
  }
}
await visit(source);

const cssFiles = files.filter((path) => path.endsWith(".css"));
assert.deepEqual(cssFiles.map((path) => relative(source, path)), ["styles.css"]);
const stylesheet = await readFile(cssFiles[0], "utf8");
const ast = postcss.parse(stylesheet);
const selectors = [];
const manualDeclarations = [];
ast.walkRules((rule) => {
  selectors.push(rule.selector);
  const isTheme = rule.selector === ":root";
  const inBaseline = rule.parent.type === "atrule"
    && rule.parent.name === "layer" && rule.parent.params === "base";
  const baselineSelectors = new Set([
    "*", "body", "h1", "h2", ":focus-visible",
    'a[href],button,summary,select,label:has(input[type="checkbox"],input[type="radio"])',
    "button:disabled,select:disabled,label:has(input:disabled)",
  ]);
  const normalized = rule.selector.replace(/\s+/g, "");
  assert.ok(
    isTheme || (inBaseline && baselineSelectors.has(normalized)),
    `Unexpected component CSS: ${rule.selector}`,
  );
});
ast.walkDecls((declaration) => {
  if (!declaration.prop.startsWith("--")) manualDeclarations.push(declaration.prop);
});
assert.deepEqual(manualDeclarations, [], "Use Tailwind utilities for baseline properties");
assert.match(stylesheet, /@import\s+["']tailwindcss["']/);
assert.match(stylesheet, /@theme inline/);

const inlineStyles = [];
for (const file of files.filter((path) => path.endsWith(".tsx"))) {
  const parsed = ts.createSourceFile(file, await readFile(file, "utf8"), ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
  function inspect(node) {
    if (ts.isJsxAttribute(node) && node.name.getText(parsed) === "style") {
      inlineStyles.push(relative(source, file));
    }
    ts.forEachChild(node, inspect);
  }
  inspect(parsed);
}
assert.deepEqual(inlineStyles, [], "Inline manual styles require a documented exception");

const report = {
  status: "PASS",
  cssFiles: cssFiles.map((path) => relative(source, path)),
  globalCssLines: stylesheet.trimEnd().split(/\r?\n/).length,
  selectors,
  componentCssSelectors: 0,
  manualDeclarations,
  inlineStyles,
  note: "Theme variables and Tailwind @apply baseline only; no custom component CSS exception.",
};
await writeFile(join(root, "evidence", "source-styling-check.json"), JSON.stringify(report, null, 2));
console.log(JSON.stringify(report));
