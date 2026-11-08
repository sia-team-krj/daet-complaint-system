// Verify the public ticket page (/transparency/complaints/{id}) at 393x852,
// 360x740, 768x1024, 1024x768, 1310x900. Asserts layout geometry, colors,
// interactivity and the contrast contract. Usage:
//   node tests/browser/ticket-verify.cjs [base-url]
const { chromium } = require('playwright');

const BASE = process.argv[2] || 'http://127.0.0.1:8099';
const COMPLAINTS = {
    closed: 54,
    inProgress: 22,
    resolved: 23,
    rejected: 66,
    outOfOrder: 26,
    longTitle: 25,
    zeroUpdates: 68,
    oneUpdate: 23,
    eightUpdates: 67,
};

let failures = 0;
const ok = (cond, label) => {
    if (cond) console.log(`  PASS  ${label}`);
    else { failures++; console.log(`  FAIL  ${label}`); }
};
const srgb = (c) => { const v = c / 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
const lum = ([r, g, b]) => 0.2126 * srgb(r) + 0.7152 * srgb(g) + 0.0722 * srgb(b);
const parse = (s) => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(',').map(Number); return p.length === 3 ? [...p, 1] : p; };
const contrast = (a, b) => { const [hi, lo] = [Math.max(lum(a), lum(b)), Math.min(lum(a), lum(b))]; return (hi + 0.05) / (lo + 0.05); };

(async () => {
    const browser = await chromium.launch({ headless: true });

    // ═══ Spec 1: back link ═══
    for (const [w, h] of [[393, 852], [1310, 900]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.closed}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const back = document.querySelector('.pt-back-row .pt-back');
            const cs = getComputedStyle(back);
            const nav = document.getElementById('navbar');
            const rect = (el) => el.getBoundingClientRect();
            const csBg = (el) => getComputedStyle(el).backgroundColor;
            const backRect = rect(back);
            const csColor = cs.color;
            const navBottom = nav.getBoundingClientRect().bottom;
            return {
                color: csColor,
                bg: csBg(back.closest('.pt-back-row') || back.parentElement),
                rect: { x: Math.round(backRect.x), y: Math.round(backRect.y), w: Math.round(backRect.width), h: Math.round(backRect.height) },
                belowNavbar: Math.round(rect(back).top) >= Math.round(navBottom),
                hit: (() => { const hit = document.elementFromPoint(Math.round(backRect.x + backRect.width / 2), Math.round(backRect.y + backRect.height / 2)); return !!hit && (hit === back || back.contains(hit)); })(),
            };
        });
        const ratio = contrast(parse(d.color), parse(d.bg));
        ok(d.color !== 'rgb(255, 255, 255)' && !d.color.startsWith('rgba(255, 255, 255, 0'), `${w}px back link is not white (${d.color})`);
        ok(ratio >= 7, `${w}px back link contrast vs its background >= 7:1 (${ratio.toFixed(2)}:1)`);
        ok(d.hit, `${w}px elementFromPoint at the back link centre returns the link`);
        ok(d.belowNavbar, `${w}px back link box is below the navbar's bottom edge`);
        ok(d.rect.h >= 44, `${w}px back link tap area >= 44px tall (${d.rect.h}px)`);
        await ctx.close();
    }

    // ═══ Spec 2 + 7: banner geometry + card spacing ═══
    for (const [w, h] of [[393, 852], [360, 740], [768, 1024], [1024, 768], [1310, 900]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.closed}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const banner = document.querySelector('.pt-banner');
            const cs = getComputedStyle(banner);
            const status = document.querySelector('.pt-banner__status-row');
            const progress = document.querySelector('.pt-order-progress');
            const bannerRect = banner.getBoundingClientRect();
            const statusBottom = status.getBoundingClientRect().bottom;
            const cardTop = progress.getBoundingClientRect().top;
            const cards = [...document.querySelectorAll('.pt-card')].map(c => ({
                id: c.querySelector('h2')?.id,
                gap: 0,
                top: Math.round(c.getBoundingClientRect().top),
                padding: getComputedStyle(c).padding,
            }));
            // measure gaps between consecutive cards in the stack
            const stackChildren = [...document.querySelectorAll('.pt-stack > .pt-card, .pt-stack > .pt-grid .pt-card')];
            const gaps = [];
            for (let i = 1; i < stackChildren.length; i++) {
                gaps.push(Math.round(stackChildren[i].getBoundingClientRect().top - stackChildren[i - 1].getBoundingClientRect().bottom));
            }
            return {
                x: Math.round(bannerRect.x),
                width: Math.round(bannerRect.width),
                radius: [cs.borderTopLeftRadius, cs.borderTopRightRadius, cs.borderBottomLeftRadius, cs.borderBottomRightRadius],
                height: Math.round(bannerRect.height),
                bottomGap: Math.round(bannerRect.bottom - statusBottom),
                progressOverlap: Math.round(bannerRect.bottom - cardTop),
                bannerY: Math.round(bannerRect.y),
                gaps,
                cardPadding: getComputedStyle(document.querySelector('.pt-card')).padding,
            };
        });
        ok(d.x === 0, `${w}px banner left edge = 0 (${d.x})`);
        ok(d.width === w, `${w}px banner width = viewport width (${d.width})`);
        ok(d.radius.every(r => r === '0px'), `${w}px banner border-radius all zero (${d.radius.join(',')})`);
        ok(d.height <= 255 && d.height >= 150, `${w}px banner height is compact (${d.height}px)`);
        ok(Math.abs(d.bottomGap - 48) <= 2, `${w}px status-row bottom to banner bottom = 48px (${d.bottomGap})`);
        ok(Math.abs(d.progressOverlap - 24) <= 2, `${w}px Progress card overlaps banner by exactly 24px (${d.progressOverlap})`);
        if (w < 768) {
            ok(d.gaps.length > 0 && d.gaps.every(g => g === 16), `${w}px card gaps are 16px (${JSON.stringify(d.gaps)})`);
            ok(d.cardPadding === '20px', `${w}px card padding is 20px (${d.cardPadding})`);
        } else {
            ok(d.cardPadding === '24px', `${w}px card padding is 24px (${d.cardPadding})`);
        }
        // no horizontal overflow
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        ok(overflow === 0, `${w}px no horizontal overflow (delta ${overflow})`);
        await ctx.close();
    }

    // ═══ Spec 3: pill + copy button ═══
    {
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.closed}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const pill = document.querySelector('.pt-ticket-pill');
            const cs = getComputedStyle(pill);
            const copyBtn = document.querySelector('.pt-copy-btn');
            const btnRect = copyBtn.getBoundingClientRect();
            return {
                color: cs.color, bg: cs.backgroundColor, borderColor: cs.borderColor,
                pillHeight: Math.round(pill.getBoundingClientRect().height),
                btnWidth: Math.round(btnRect.width), btnHeight: Math.round(btnRect.height),
                fontFamily: cs.fontFamily, fontSize: cs.fontSize,
            };
        });
        const pillRatio = contrast(parse(d.color), parse(d.bg));
        ok(pillRatio >= 4.5, `ticket pill text contrast >= 4.5:1 (${pillRatio.toFixed(2)}:1)`);
        ok(d.pillHeight === 32, `ticket pill height = 32px (${d.pillHeight})`);
        ok(d.btnWidth === 44 && d.btnHeight === 44, `copy button is 44x44 (${d.btnWidth}x${d.btnHeight})`);
        // copy behaviour: intercept the clipboard write + observe the live region
        const copied = await page.evaluate(async () => {
            const btn = document.querySelector('.pt-copy-btn');
            const live = document.getElementById('copy-live');
            let written = null;
            navigator.clipboard.writeText = (t) => { written = t; return Promise.resolve(); };
            btn.click();
            await new Promise(r => setTimeout(r, 120));
            return { written, live: live.textContent.trim(), ariaLabel: btn.getAttribute('aria-label') };
        });
        ok(copied.written === await page.locator('.pt-ticket-pill').textContent(), `copy button writes the ticket number (${copied.written})`);
        ok(/Copied|Nakopya/.test(copied.live), `live region announces Copied (${copied.live})`);
        await page.waitForTimeout(1900);
        const liveAfter = await page.evaluate(() => document.getElementById('copy-live').textContent.trim());
        ok(liveAfter === '', `live region clears after ~2s ("${liveAfter}")`);
        await ctx.close();
    }

    // ═══ Spec 4: mobile stepper exact geometry ═══
    for (const w of [393, 360]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: 740 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.inProgress}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const steps = [...document.querySelectorAll('.pt-step')];
            const liRects = steps.map(s => { const r = s.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, height: Math.round(r.height), center: 0 }; });
            const dots = steps.map(s => { const r = s.querySelector('.pt-step__marker').getBoundingClientRect(); return { top: r.top, bottom: r.bottom, center: Math.round(r.top + r.height / 2) }; });
            const labels = steps.map(s => getComputedStyle(s.querySelector('.pt-step__label')).fontSize);
            const captions = steps.map(s => getComputedStyle(s.querySelector('.pt-step__caption')).fontSize);
            const labelColor = getComputedStyle(steps[0].querySelector('.pt-step__label')).color;
            const captionColor = getComputedStyle(steps[0].querySelector('.pt-step__caption')).color;
            const textAlignOffset = steps.map(s => {
                const text = s.querySelector('.pt-step__text');
                const marker = s.querySelector('.pt-step__marker');
                return Math.round((text.getBoundingClientRect().top + text.getBoundingClientRect().height / 2) - (marker.getBoundingClientRect().top + marker.getBoundingClientRect().height / 2));
            });
            // connectors
            const connectors = steps.slice(0, -1).map((s, i) => {
                const cs = getComputedStyle(s, '::after');
                const r = s.getBoundingClientRect();
                const dotBottom = dots[i].bottom;
                const nextTop = dots[i + 1].top;
                return { height: cs.height, top: cs.top, dotBottom: Math.round(dotBottom), nextTop: Math.round(nextTop), left: cs.left, width: cs.width };
            });
            return {
                liHeights: liRects.map(r => r.height),
                centers: dots.map(d => Math.round(d.center)),
                labelSizes: labels, captionSizes: captions,
                labelColor, captionColor,
                textAlignOffset,
                connectors,
                markerSizes: steps.map(s => { const r = s.querySelector('.pt-step__marker').getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height)]; }),
            };
        });
        ok(d.liHeights.every((h2, i) => h2 === 56), `${w}px every <li> row is exactly 56px (${d.liHeights.join(',')})`);
        const diffs = d.centers.slice(1).map((c, i) => Math.abs(c - d.centers[i] - 56));
        ok(diffs.every(dd => dd <= 1), `${w}px consecutive node centres are 56px apart (${JSON.stringify(diffs)})`);
        ok(d.connectors.length === 4, `${w}px four connectors rendered (${d.connectors.length})`);
        for (const [i, c] of d.connectors.entries()) {
            ok(Math.abs(parseFloat(c.top) + 0 - c.dotBottom) <= 1, `${w}px connector ${i + 1} starts exactly at the node bottom (${c.top} vs ${c.dotBottom})`);
            ok(Math.abs(parseFloat(c.top) + parseFloat(c.height) - c.nextTop) <= 1, `${w}px connector ${i + 1} ends exactly at the next node top (${parseFloat(c.top) + parseFloat(c.height)} vs ${c.nextTop})`);
        }
        ok(d.labelSizes.every(s => s === '15px'), `${w}px label 15px (${d.labelSizes.join(',')})`);
        ok(d.captionSizes.every(s => s === '13px'), `${w}px caption 13px (${d.captionSizes.join(',')})`);
        ok(d.labelColor === 'rgb(15, 23, 42)' && d.captionColor === 'rgb(71, 85, 105)', `${w}px label #0f172a caption #475569 (${d.labelColor} / ${d.captionColor})`);
        ok(d.textAlignOffset.every(v => Math.abs(v) <= 2), `${w}px node and text vertically centred within 2px (${d.textAlignOffset.join(',')})`);
        ok(d.markerSizes.every(([a, b]) => a === 32 && b === 32), `${w}px every marker is 32x32 (${JSON.stringify(d.markerSizes)})`);
        await ctx.close();
    }

    // ═══ Spec 4 (visual states): rejected complaint + closed all-done ═══
    {
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.rejected}`, { waitUntil: 'networkidle' });
        const rejected = await page.evaluate(() => {
            const markers = [...document.querySelectorAll('.pt-step--rejected .pt-step__marker')];
            return markers.map(m => [getComputedStyle(m).backgroundColor, getComputedStyle(m).borderColor]);
        });
        ok(rejected.length >= 1 && rejected.every(([bg, border]) => bg === 'rgb(239, 68, 68)'), `rejected marker is the rejection red (${JSON.stringify(rejected)})`);
        await ctx.close();
    }

    // ═══ Spec 5: updates order, markers, connectors, disclosure ═══
    for (const [id, expectCount] of [[COMPLAINTS.closed, 5], [COMPLAINTS.resolved, 4], [COMPLAINTS.outOfOrder, 3], [COMPLAINTS.eightUpdates, 8]]) {
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${id}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const times = [...document.querySelectorAll('.pt-updates time')].map(t => t.getAttribute('datetime'));
            const markers = [...document.querySelectorAll('.pt-update__marker')];
            const firstFilled = markers[0] ? getComputedStyle(markers[0]).backgroundColor : null;
            const othersFilled = markers.slice(1).map(m => getComputedStyle(m).backgroundColor);
            const borderColors = markers.slice(1).map(m => getComputedStyle(m).borderColor);
            const details = document.querySelector('.pt-updates-more');
            const earlier = details ? details.querySelectorAll('.pt-update__marker').length : 0;
            const visible = document.querySelectorAll('.pt-updates > li.pt-update').length;
            return { times, firstFilled, othersFilled, borderColors,
                visible, earlier,
                hasDetails: !!details,
                connectorOk: [...document.querySelectorAll('.pt-update:not(:last-child)')].every(li => getComputedStyle(li, '::before').width === '2px'),
            };
        });
        const sorted = [...d.times].sort().reverse();
        ok(JSON.stringify(d.times) === JSON.stringify(sorted), `#${id} updates render newest first (${d.times.join(' | ')})`);
        ok(d.firstFilled === 'rgb(23, 48, 90)', `#${id} first (newest) marker is navy filled (${d.firstFilled})`);
        ok(d.othersFilled.every(bg => bg === 'rgb(255, 255, 255)'), `#${id} older markers are hollow (${JSON.stringify(d.othersFilled)})`);
        ok(d.borderColors.every(c => c === 'rgb(100, 116, 139)'), `#${id} hollow marker borders are #64748b (${JSON.stringify(d.borderColors)})`);
        ok(d.connectorOk, `#${id} 2px connectors between markers`);
        await ctx.close();
    }
    {
        // 8-update complaint: exactly 5 shown + working disclosure (also without JS)
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.eightUpdates}`, { waitUntil: 'networkidle' });
        const count = await page.evaluate(() => document.querySelectorAll('.pt-updates > li.pt-update').length);
        ok(count === 5, `8-update complaint shows exactly 5 visible updates (${count})`);
        const details = await page.locator('.pt-updates-more');
        ok(await details.isVisible(), '8-update complaint shows the earlier-updates disclosure');
        const summary = await details.locator('summary').innerText();
        ok(/Show earlier updates \(3\)/.test(summary), `disclosure label reads "Show earlier updates (3)" (${summary.trim()})`);
        await details.locator('summary').click();
        const afterClick = await page.evaluate(() => document.querySelectorAll('.pt-updates > li.pt-update, .pt-updates-more .pt-update').length);
        const earlierCount = await page.evaluate(() => document.querySelectorAll('.pt-updates-more .pt-update').length);
        ok(earlierCount === 3 && afterClick === 8, `disclosure reveals the 3 earlier updates (${earlierCount}/3, total ${afterClick}/8)`);
        await ctx.close();

        // without JavaScript: disclosure still works (native details)
        const ctx2 = await browser.newContext({ viewport: { width: 393, height: 852 }, javaScriptEnabled: false });
        const page2 = await ctx2.newPage();
        await page2.goto(`${BASE}/transparency/complaints/${COMPLAINTS.eightUpdates}`, { waitUntil: 'networkidle' });
        const listedWithoutJs = await page2.evaluate(() => document.querySelectorAll('.pt-update').length);
        ok(listedWithoutJs === 8, `without JS all 8 updates render and the disclosure is usable (${listedWithoutJs})`);
        await ctx2.close();
    }

    // ═══ Spec 6: ticket details ═══
    for (const [w, h, expectTwoCol] of [[393, 852, true], [360, 740, true], [1310, 900, false]]) {
        const ctx = await browser.newContext({ viewport: { width: w, height: h } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.closed}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const badge = document.querySelector('.dept-badge');
            const card = document.querySelector('.pt-order-details');
            const dl = document.querySelector('.pt-dl');
            const badgeRect = badge.getBoundingClientRect();
            const cardRect = card.getBoundingClientRect();
            const widthRatio = badgeRect.width / cardRect.width;
            const cols = getComputedStyle(dl).gridTemplateColumns;
            return { widthRatio, cols, cardHeight: Math.round(card.getBoundingClientRect().height), hasCategoryIcon: !!dl.querySelector('.cat-icon') };
        });
        ok(d.hasCategoryIcon, `${w}px category icon circle renders in details`);
        ok(d.widthRatio < 0.5, `${w}px department badge is under half the card width (${(d.widthRatio * 100).toFixed(0)}%)`);
        ok(expectTwoCol ? d.cols.split(' ').length === 2 : d.cols.split(' ').length === 1, `${w}px details grid is ${expectTwoCol ? '2-col' : '1-col'} (${d.cols})`);
        if (expectTwoCol) ok(d.cardHeight <= 300, `${w}px details card is at most 300px tall (${d.cardHeight}px)`);
        await ctx.close();
    }

    // ═══ Spec 9: first screen at 393x852 (back link + banner + top of Progress visible) ═══
    {
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        await page.goto(`${BASE}/transparency/complaints/${COMPLAINTS.inProgress}`, { waitUntil: 'networkidle' });
        const d = await page.evaluate(() => {
            const inRect = (el) => { const r = el.getBoundingClientRect(); return { top: r.top, bottom: r.bottom, visible: r.top < window.innerHeight }; };
            return {
                back: inRect(document.querySelector('.pt-back-row .pt-back')),
                banner: inRect(document.querySelector('.pt-banner')),
                progress: inRect(document.querySelector('.pt-order-progress')),
            };
        });
        ok(d.back.visible && d.back.bottom <= 852, `first screen: back link visible (bottom ${Math.round(d.back.bottom)})`);
        ok(d.banner.visible && d.banner.bottom <= 852, `first screen: whole banner visible (bottom ${Math.round(d.banner.bottom)})`);
        ok(d.progress.visible && d.progress.top < 852 - 24, `first screen: top of Progress card visible (top ${Math.round(d.progress.top)})`);
        await ctx.close();
    }

    // ═══ Regression status checks (no screenshots by request) ═══
    for (const path of ['/', '/transparency', '/transparency/complaints', '/login', '/register']) {
        const ctx = await browser.newContext({ viewport: { width: 393, height: 852 } });
        const page = await ctx.newPage();
        const resp = await page.goto(`${BASE}${path}`, { waitUntil: 'networkidle' });
        ok(resp && resp.status() === 200, `${path} returns 200 (${resp?.status()})`);
        await ctx.close();
    }

    await browser.close();
    console.log(`\n${failures === 0 ? 'TICKET PAGE: ALL CHECKS PASSED' : failures + ' CHECK(S) FAILED'}`);
    process.exit(failures === 0 ? 0 : 1);
})().catch(e => { console.error(e); process.exit(1); });
