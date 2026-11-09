import { chromium } from 'playwright';

const STAFF_EMAIL = 'staff@daet.test';
const STAFF_PASS = 'password';
const BASE = 'http://127.0.0.1:8000';

function phraseKey(text) {
  return text
    .toLowerCase()
    .replace(/[^a-z0-9\s]/gi, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}
function wordCount(text) {
  return text.trim().split(/\s+/).filter(Boolean).length;
}

const EL_SELECTOR_SRC = `
function elSelector(el) {
  const parts = [];
  let node = el;
  while (node && node.nodeType === 1 && node.tagName !== 'HTML') {
    let tag = node.tagName.toLowerCase();
    let sel = tag;
    const id = node.getAttribute('id');
    if (id) sel += '#' + CSS.escape(id);
    else {
      const cls = node.getAttribute('class');
      if (cls) sel += '.' + CSS.escape(cls.trim().split(/\\s+/).join('.'));
      const parent = node.parentNode;
      if (parent) {
        const sibs = Array.from(parent.children).filter(
          (s) => s.tagName === node.tagName && s.getAttribute('class') === node.getAttribute('class')
        );
        if (sibs.length > 1) sel += ':nth-child(' + (sibs.indexOf(node) + 1) + ')';
      }
    }
    parts.unshift(sel);
    node = node.parentNode;
  }
  return parts.join(' > ');
}
`;

async function visibleTextNodes(page) {
  return page.evaluate((src) => {
    eval(src);
    const results = [];
    const walker = document.createTreeWalker(
      document.body,
      NodeFilter.SHOW_TEXT,
      {
        acceptNode: (n) => {
          const t = n.textContent || '';
          if (!t.trim()) return NodeFilter.FILTER_REJECT;
          return NodeFilter.FILTER_ACCEPT;
        },
      }
    );
    let node;
    while ((node = walker.nextNode())) {
      let el = node.parentElement;
      let visible = true;
      let cardSel = null;
      while (el && el !== document.body) {
        const cs = getComputedStyle(el);
        if (cs.display === 'none' || cs.visibility === 'hidden') { visible = false; break; }
        if (el.classList.contains('sr-only')) { visible = false; break; }
        if (el.getAttribute('aria-hidden') === 'true') { visible = false; break; }
        if (!cardSel && el.classList.contains('dept-complaint')) cardSel = elSelector(el);
        el = el.parentElement;
      }
      if (!visible) continue;
      results.push({
        text: node.textContent.trim(),
        selector: elSelector(node.parentElement),
        card: cardSel,
      });
    }
    return results;
  }, EL_SELECTOR_SRC);
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath:
      process.env.PLAYWRIGHT_CHROMIUM_PATH ||
      '/home/crop/.cache/ms-playwright/chromium-1254/chrome-linux64/chrome',
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
  });

  const results = {};

  for (const [w, h, label] of [
    [1440, 900, 'desktop-1440'],
    [393, 852, 'mobile-393'],
  ]) {
    const page = await browser.newPage({ viewport: { width: w, height: h } });
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', STAFF_EMAIL);
    await page.fill('input[name="password"]', STAFF_PASS);
    await page.click('button[type="submit"]:has-text("Sign")');
    await page.waitForURL('**/staff/dashboard', { timeout: 15000 });
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(300);

    const nodes = await visibleTextNodes(page);

    // Phrase dedup across viewport (2+ words)
    const byPhrase = new Map();
    for (const n of nodes) {
      const key = phraseKey(n.text);
      if (!key) continue;
      if (!byPhrase.has(key)) byPhrase.set(key, []);
      byPhrase.get(key).push(n);
    }
    const dupPhrases = [];
    for (const [key, occs] of byPhrase) {
      if (occs.length > 1 && wordCount(key) >= 2) {
        dupPhrases.push({
          phrase: occs[0].text,
          count: occs.length,
          selectors: occs.map((o) => o.selector),
        });
      }
    }

    // Single-word labels repeating inside the same card
    const byCardWord = new Map(); // card -> word -> occs
    for (const n of nodes) {
      if (!n.card) continue;
      const key = phraseKey(n.text);
      if (!key || wordCount(key) !== 1) continue;
      if (!byCardWord.has(n.card)) byCardWord.set(n.card, new Map());
      const m = byCardWord.get(n.card);
      if (!m.has(key)) m.set(key, []);
      m.get(key).push(n);
    }
    const cardDupes = [];
    for (const [card, m] of byCardWord) {
      for (const [key, occs] of m) {
        if (occs.length > 1) {
          cardDupes.push({
            phrase: occs[0].text,
            count: occs.length,
            cardSelector: card,
            selectors: occs.map((o) => o.selector),
          });
        }
      }
    }

    results[label] = { nodes: nodes.length, dupPhrases, cardDupes };
    await page.close();
  }

  await browser.close();

  console.log('\n==============================================================================');
  console.log('UI REDUNDANCY AUDIT — visible repeated text');
  console.log('==============================================================================\n');

  for (const [label, r] of Object.entries(results)) {
    console.log(`--- ${label} (${r.nodes} visible text nodes) ---`);
    console.log('\nRepeated phrases (2+ words, count > 1):');
    if (r.dupPhrases.length === 0) console.log('  (none)');
    for (const p of r.dupPhrases) {
      console.log(
        `  "${p.phrase}"  x${p.count}\n    ${p.selectors.join('\n    ')}`
      );
    }
    console.log('\nRepeated single-word labels inside a complaint card:');
    if (r.cardDupes.length === 0) console.log('  (none)');
    for (const c of r.cardDupes) {
      console.log(
        `  "${c.phrase}"  x${c.count}  in card ${c.cardSelector}\n    ${c.selectors.join('\n    ')}`
      );
    }
    console.log('');
  }

  process.exit(0);
})().catch((e) => {
  console.error('AUDIT ERROR:', e);
  process.exit(1);
});
