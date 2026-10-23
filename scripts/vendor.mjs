// Copies the front-end libraries and the curated Lucide icons into static/.
// Run with `npm run vendor` after `npm install`.
import { cpSync, existsSync, mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const nm = (p) => join(root, "node_modules", p);
const out = (p) => join(root, "static", p);
const copy = (from, to) => {
  mkdirSync(dirname(out(to)), { recursive: true });
  cpSync(nm(from), out(to), { recursive: true });
};

copy("alpinejs/dist/cdn.min.js", "vendor/alpine.min.js");
copy("@alpinejs/focus/dist/cdn.min.js", "vendor/alpine-focus.min.js");
copy("@alpinejs/collapse/dist/cdn.min.js", "vendor/alpine-collapse.min.js");
copy("htmx.org/dist/htmx.min.js", "vendor/htmx.min.js");
copy("trix/dist/trix.umd.min.js", "vendor/trix.umd.min.js");
copy("trix/dist/trix.umd.min.js.map", "vendor/trix.umd.min.js.map");
copy("trix/dist/trix.css", "vendor/trix.css");

for (const [pkg, prefix] of [["@fontsource-variable/inter", "inter"], ["@fontsource-variable/manrope", "manrope"], ["@fontsource-variable/jost", "jost"]]) {
  for (const subset of ["latin", "latin-ext"]) {
    copy(`${pkg}/files/${prefix}-${subset}-wght-normal.woff2`, `fonts/${prefix}-${subset}-wght-normal.woff2`);
  }
}

for (const subset of ["latin", "latin-ext"]) {
  copy(`@fontsource/russo-one/files/russo-one-${subset}-400-normal.woff2`, `fonts/russo-one-${subset}-400-normal.woff2`);
}

// Icons: parse the Python list so there is a single source of truth.
const py = readFileSync(join(root, "apps/core/icons.py"), "utf8");
const block = py.slice(py.indexOf("ICONS: list[str] = ["), py.indexOf("]\n# de-duplicate"));
const names = [...new Set([...block.matchAll(/"([a-z0-9-]+)"/g)].map((m) => m[1]))];
mkdirSync(out("icons"), { recursive: true });
const missing = [];
for (const name of names) {
  const src = nm(`lucide-static/icons/${name}.svg`);
  if (!existsSync(src)) { missing.push(name); continue; }
  // Strip the licence comment and fixed classes; the template tag sets attributes.
  const svg = readFileSync(src, "utf8").replace(/<!--[\s\S]*?-->/g, "").trim();
  writeFileSync(out(`icons/${name}.svg`), svg + "\n");
}
// Sprite used by the dashboard icon picker (<use href="sprite.svg#name">).
const symbols = [];
for (const file of [...names.filter((n) => !missing.includes(n)).map((n) => `${n}.svg`)]) {
  const svg = readFileSync(out(`icons/${file}`), "utf8");
  const inner = svg.replace(/^[\s\S]*?<svg[^>]*>/, "").replace(/<\/svg>\s*$/, "");
  symbols.push(`<symbol id="${file.replace(".svg", "")}" viewBox="0 0 24 24">${inner.trim()}</symbol>`);
}
writeFileSync(out("icons/sprite.svg"), `<svg xmlns="http://www.w3.org/2000/svg">${symbols.join("")}</svg>\n`);
console.log(`vendored ${names.length - missing.length} icons`);
if (missing.length) { console.warn("missing icons:", missing.join(", ")); process.exitCode = 1; }
