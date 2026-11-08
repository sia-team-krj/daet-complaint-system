// Read-only diagnostic for the public ticket page. Prints computed values the
// fix relies on. Usage: node tests/browser/ticket-diagnose.cjs [base-url]
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://127.0.0.1:8099';

(async () => {
    const browser = await chromium.launch({ headless: true });
    for (const [w, h] of [[393, 852], [1310, 900]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/54`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const rect = (el) => el ? { x: Math.round(el.getBoundingClientRect().x), y: Math.round(el.getBoundingClientRect().y), w: Math.round(el.getBoundingClientRect().width), h: Math.round(el.getBoundingClientRect().height) } : null;
            const cs = (el, p) => el ? getComputedStyle(el).getPropertyValue(p) : null;
            const back = document.querySelector('.pt-back-row .pt-back');
            const banner = document.querySelector('.pt-banner');
            const status = document.querySelector('.pt-banner__status-row');
            return {
                back: { rect: rect(back), color: cs(back, 'color'), font: cs(back, 'font'), visible: !!(back && back.offsetWidth && back.offsetHeight), text: back?.textContent.trim() },
                backRow: { rect: rect(document.querySelector('.pt-back-row')), padding: [cs(document.querySelector('.pt-back-row'), 'padding-top'), cs(document.querySelector('.pt-back-row'), 'padding-bottom')] },
                banner: { rect: rect(banner), padding: [cs(banner, 'padding-top'), cs(banner, 'padding-bottom')], radius: [cs(banner, 'border-top-left-radius'), cs(banner, 'border-top-right-radius'), cs(banner, 'border-bottom-left-radius'), cs(banner, 'border-bottom-right-radius')], bottomGap: banner && status ? Math.round(banner.getBoundingClientRect().bottom - status.getBoundingClientRect().bottom) : null, fullBleed: rect(banner)?.x === 0 && Math.round(rect(banner)?.width) === window.innerWidth },
                bannerHeight: rect(banner)?.h,
                steps: [...document.querySelectorAll('.pt-step')].map(s => { const r = s.getBoundingClientRect(); const dot = s.querySelector('.pt-dot').getBoundingClientRect(); return { h: Math.round(r.height), center: Math.round(dot.top + dot.height / 2) }; }),
                containerGap: cs(document.querySelector('.pt-col'), 'gap'),
                cardPadding: cs(document.querySelector('.pt-card'), 'padding'),
                h2Size: cs(document.querySelector('.pt-card h2'), 'font-size'),
            };
        });
        console.log(JSON.stringify({ viewport: `${w}x${h}`, ...d }, null, 1));
        await ctx.close();
    }
    await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
