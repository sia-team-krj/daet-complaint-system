const { chromium } = require('playwright');
const hex = (c) => {
    const m = c.match(/rgba?\(([^)]+)\)/);
    if (!m) return null;
    const p = m[1].split(',').map(Number);
    return p.length === 3 ? [...p, 1] : p;
};
const lum = (rgb) => {
    const a = hex(rgb) ? [0,0,0] : null;
    return null;
};
function srgb(c) { const v = c / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }
function luminance([r, g, b]) { return 0.2126 * srgb(r) + 0.7152 * srgb(g) + 0.0722 * srgb(b); }
function rgbaToRgb([r, g, b, a]) { return [r, g, b, a]; }
function blendOver(fg, bg) {
    // fg [r,g,b,a] over opaque bg [r,g,b,1]
    return [
        Math.round(fg[0] * fg[3] + bg[0] * (1 - fg[3])),
        Math.round(fg[1] * fg[3] + bg[1] * (1 - fg[3])),
        Math.round(fg[2] * fg[3] + bg[2] * (1 - fg[3])),
        1,
    ];
}
function contrast(l1, l2) { const hi = Math.max(l1, l2), lo = Math.min(l1, l2); return (hi + 0.05) / (lo + 0.05); }
const PSpec = ['transparent', 'rgba(0, 0, 0, 0)'];
(async () => {
    const browser = await chromium.launch({ headless: true });
    for (const [w, hgt] of [[393, 852], [1310, 900]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: hgt } });
        const page = await ctx.newPage();
        await page.goto('http://127.0.0.1:8099/transparency/complaints/54', { waitUntil: 'networkidle' });
        await page.waitForTimeout(400);

        const rows = await page.evaluate(() => {
            const out = [];
            const parseColor = (s) => {
                const m = s.match(/rgba?\(([^)]+)\)/);
                if (!m) return null;
                const p = m[1].split(',').map(Number);
                return p.length === 3 ? [p[0], p[1], p[2], 1] : p;
            };
            const isVis = (el) => {
                const cs = getComputedStyle(el);
                if (cs.visibility === 'hidden' || cs.display === 'none') return false;
                const r = el.getBoundingClientRect();
                return r.width > 0 && r.height > 0;
            };
            // Generic sampler for linear-gradient backgrounds: parse stops from
            // the computed style and sample the pixel behind an element's center.
            const sampleGradient = (el, owner) => {
                const cs = getComputedStyle(owner);
                const img = cs.backgroundImage;
                const start = img.indexOf('linear-gradient(');
                if (start === -1) return null;
                let depth = 0, end = -1;
                for (let i = start + 'linear-gradient('.length - 1; i < img.length; i++) {
                    if (img[i] === '(') depth++;
                    if (img[i] === ')') {
                        depth--;
                        if (depth === 0) { end = i; break; }
                    }
                }
                if (end === -1) return null;
                const inner = img.slice(start + 'linear-gradient('.length, end).trim();
                const parts = [];
                let pdepth = 0, cur = '';
                for (const ch of inner) {
                    if (ch === '(') pdepth++;
                    if (ch === ')') pdepth--;
                    if (ch === ',' && pdepth === 0) { parts.push(cur.trim()); cur = ''; }
                    else cur += ch;
                }
                if (cur) parts.push(cur.trim());
                let angle = 180;
                const firstTok = parts[0].trim();
                if (/^(to|[0-9.]+deg)/.test(firstTok)) {
                    const dir = firstTok.match(/^to\s+(\w+)$/);
                    if (dir) {
                        const dirs = { top: 0, right: 90, bottom: 180, left: 270 };
                        angle = dirs[dir[1]] ?? 180;
                    } else {
                        angle = parseFloat(firstTok);
                    }
                    parts.shift();
                }
                const stops = [];
                for (const p of parts) {
                    const mm = p.match(/^(.*?)(?:\s+([0-9.]+)(%|px)?)?$/);
                    if (!mm) continue;
                    const color = mm[1].trim();
                    const pos = mm[2] !== undefined ? parseFloat(mm[2]) : null;
                    const unit = mm[3] || '%';
                    stops.push({ color, pos, unit });
                }
                if (stops.some(s => s.pos === null)) {
                    stops.forEach((s, i) => { s.pos = stops.length === 1 ? 0 : (i / (stops.length - 1)) * 100; s.unit = '%'; });
                }
                const target = el.getBoundingClientRect();
                const ownerRect = owner.getBoundingClientRect();
                const canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.ceil(ownerRect.width));
                canvas.height = Math.max(1, Math.ceil(ownerRect.height));
                const c = canvas.getContext('2d');
                const rad = (angle * Math.PI) / 180;
                const ux = Math.sin(rad), uy = Math.cos(rad);
                const L = Math.abs(canvas.width * ux) + Math.abs(canvas.height * uy);
                const mx = canvas.width / 2, my = canvas.height / 2;
                const g = c.createLinearGradient(mx - (ux * L) / 2, my - (uy * L) / 2, mx + (ux * L) / 2, my + (uy * L) / 2);
                for (const s of stops) {
                    let pos = s.pos ?? 0;
                    if (s.unit === 'px') pos = (pos / L) * 100;
                    g.addColorStop(Math.max(0, Math.min(1, pos / 100)), s.color);
                }
                c.fillStyle = g;
                c.fillRect(0, 0, canvas.width, canvas.height);
                const sx = Math.max(0, Math.min(canvas.width - 1, Math.round(target.left + target.width / 2 - ownerRect.left)));
                const sy = Math.max(0, Math.min(canvas.height - 1, Math.round(target.top + target.height / 2 - ownerRect.top)));
                const d = c.getImageData(sx, sy, 1, 1).data;
                return [d[0], d[1], d[2], 1];
            };
            const resolveBackground = (el) => {
                // walk up; blend until a fully opaque layer or the page bg
                let bg = [244, 246, 250, 1]; // --bg #f4f6fa
                let source = '--bg (#f4f6fa)';
                const layers = [];
                let node = el;
                while (node && node !== document.documentElement) {
                    const cs = getComputedStyle(node);
                    if (cs.backgroundImage && /linear-gradient/.test(cs.backgroundImage)) {
                        const g = sampleGradient(el, node);
                        if (g) { source = `gradient on <${node.tagName.toLowerCase()}> (sampled)`; return { bg: g, source }; }
                    }
                    const bc = parseColor(cs.backgroundColor);
                    if (bc && (bc[3] ?? 1) > 0) {
                        bg = [
                            Math.round(bc[0] * bc[3] + bg[0] * (1 - bc[3])),
                            Math.round(bc[1] * bc[3] + bg[1] * (1 - bc[3])),
                            Math.round(bc[2] * bc[3] + bg[2] * (1 - bc[3])),
                            1,
                        ];
                        if (bc[3] === 1) { source = `${node.tagName.toLowerCase()}.${[...node.classList].slice(0, 2).join('.')}`; break; }
                    }
                    node = node.parentElement;
                }
                return { bg, source };
            };
            const describe = (el) => {
                const cls = [...el.classList].slice(0, 2).join('.');
                const id = el.id ? `#${el.id}` : '';
                return `<${el.tagName.toLowerCase()}${id || (cls ? '.' + cls : '')}>`;
            };
            // text candidates: elements with a direct text node
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_ELEMENT, {
                acceptNode: (el) => {
                    if (!isVis(el)) return NodeFilter.FILTER_REJECT;
                    const hasText = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim().length > 0);
                    return hasText ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
                },
            });
            const textEls = [];
            while (walker.nextNode()) textEls.push(walker.currentNode);
            for (const el of textEls) {
                const cs = getComputedStyle(el);
                const fg = parseColor(cs.color);
                if (!fg) continue;
                const text = el.textContent.trim().replace(/\s+/g, ' ').slice(0, 46);
                const { bg, source } = resolveBackground(el);
                const fontSize = parseFloat(cs.fontSize);
                const weight = parseInt(cs.fontWeight, 10);
                const large = fontSize >= 24 || (fontSize >= 18.66 && weight >= 700);
                out.push({ kind: 'text', el: describe(el), text, fg, bg, source, large });
            }
            // meaningful non-text UI
            const uiAudit = [
                ['.pt-ticket-pill', 'border'],
                ['.pt-update__dot', 'border'],
                ['.pt-dot', 'border'],
                ['.pt-step', 'connector?'],
                ['.status-pill', 'bg'],
            ];
            document.querySelectorAll('.pt-ticket-pill').forEach(el => {
                const cs = getComputedStyle(el);
                out.push({ kind: 'ui', el: describe(el), text: 'ticket pill border', fg: parseColor((cs.borderColor || '').split(' ')[0] || 'rgba(255,255,255,.28)') ?? [255, 255, 255, .28], bg: resolveBackground(el).bg, source: 'gradient @ pill', large: false });
            });
            document.querySelectorAll('.pt-update__dot').forEach(el => {
                if (el.classList.contains('pt-update__dot--newest')) return;
                const cs = getComputedStyle(el);
                out.push({ kind: 'ui', el: describe(el), text: 'hollow update marker border', fg: parseColor(cs.borderColor) ?? [100, 116, 139, 1], bg: [255, 255, 255, 1], source: 'card white', large: false });
            });
            document.querySelectorAll('.pt-dot').forEach(el => {
                const cs = getComputedStyle(el);
                out.push({ kind: 'ui', el: describe(el), text: 'upcoming dot border', fg: parseColor(cs.borderColor) ?? [148, 163, 184, 1], bg: [255, 255, 255, 1], source: 'card white', large: false });
            });
            // footer gold hairline vs navy-950
            document.querySelectorAll('.site-footer').forEach(el => {
                out.push({ kind: 'ui', el: '.site-footer', text: 'footer gold top border', fg: [212, 169, 71, 1], bg: [7, 18, 38, 1], source: 'navy-950', large: false });
            });
            return out;
        });
        console.log(`\n===== ${w}x${hgt} =====`);
        const failing = [];
        for (const r of rows) {
            const L = (rgb) => luminance(rgb);
            const fgL = L(r.fg), bgL = L(r.bg);
            const ratio = contrast(fgL, bgL);
            const limit = r.large ? 3 : 4.5;
            const uiLimit = 3;
            const ok = r.kind === 'ui' ? ratio >= uiLimit : ratio >= limit;
            const line = `${ratio.toFixed(2).padStart(5)}:1  ${ok ? 'PASS' : 'FAIL'}  [${r.kind}] ${r.el} :: ${r.text}`;
            if (!ok) failing.push({ ...r, ratio });
            console.log(line);
        }
        console.log(`--- FAILING (${failing.length}) ---`);
        for (const f of failing) console.log(`${f.ratio.toFixed(2)}  ${f.el} :: ${f.text} (fg=${f.fg} bg=${f.bg} via ${f.source})`);
        await ctx.close();
    }
    await browser.close();
})().catch(e => { console.error(e); process.exit(1); });

const fs = require('fs');
