const fs = require("fs");
const path = require("path");
const { chromium } = require("playwright");

const BASE = "https://meem-market.com";
const OUT = path.join(__dirname, "audit_out");
fs.mkdirSync(OUT, { recursive: true });

const seeds = [
  `${BASE}/`,
  `${BASE}/offers/`,
  `${BASE}/branches/`,
];

const MAX_PAGES = 200;

function safeName(url) {
  return url.replace(/^https?:\/\//, "").replace(/[^\w.-]+/g, "_").slice(0, 180);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  const seen = new Set();
  const queue = [...seeds];

  // Capture JSON responses from XHR/fetch
  page.on("response", async (res) => {
    try {
      const req = res.request();
      const rt = req.resourceType();
      const ct = (res.headers()["content-type"] || "").toLowerCase();
      if ((rt === "xhr" || rt === "fetch") && ct.includes("application/json")) {
        const url = res.url();
        const body = await res.text();
        const file = path.join(OUT, `json_${Date.now()}_${safeName(url)}.json`);
        fs.writeFileSync(file, body, "utf8");
      }
    } catch {}
  });

  while (queue.length && seen.size < MAX_PAGES) {
    const url = queue.shift();
    if (!url.startsWith(BASE) || seen.has(url)) continue;
    seen.add(url);

    try {
      await page.goto(url, { waitUntil: "networkidle", timeout: 45000 });
      const html = await page.content();
      fs.writeFileSync(path.join(OUT, `html_${safeName(url)}.html`), html, "utf8");

      // Extract JSON-LD blocks
      const jsonLd = await page.$$eval('script[type="application/ld+json"]', els =>
        els.map(e => e.textContent).filter(Boolean)
      );
      if (jsonLd.length) {
        fs.writeFileSync(
          path.join(OUT, `jsonld_${safeName(url)}.json`),
          JSON.stringify(jsonLd, null, 2),
          "utf8"
        );
      }

      // Extract links
      const links = await page.$$eval("a[href]", (as) => as.map(a => a.href));
      for (const l of links) {
        if (l.startsWith(BASE) && !seen.has(l)) queue.push(l.split("#")[0]);
      }
    } catch (e) {
      fs.writeFileSync(path.join(OUT, `error_${safeName(url)}.txt`), String(e), "utf8");
    }
  }

  fs.writeFileSync(path.join(OUT, "visited_urls.txt"), [...seen].join("\n"), "utf8");
  await browser.close();

  console.log(`Done. Visited ${seen.size} pages. Output in audit_out/`);
})();
