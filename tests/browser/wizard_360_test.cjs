const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    
    // 360x800 mobile test
    const context360 = await browser.newContext({ viewport: { width: 360, height: 800 } });
    const page360 = await context360.newPage();

    console.log('Logging in as citizen (360x800)...');
    await page360.goto('http://127.0.0.1:8000/login');
    await page360.fill('input[name="email"]', 'pedro@gmail.com');
    await page360.fill('input[name="password"]', 'Test@1234');
    await page360.click('button[type="submit"]');
    await page360.waitForURL('**/dashboard');

    await page360.goto('http://127.0.0.1:8000/complaints/create');
    await page360.waitForSelector('#complaint-form', { state: 'attached' });
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step1.png' });

    await page360.selectOption('#category', 'road_damage');
    await page360.waitForTimeout(400);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step2.png' });
    await page360.fill('#title', 'Pothole on main road');
    await page360.click('#wizard-btn-next');

    await page360.waitForTimeout(300);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step3.png' });
    await page360.fill('#description', 'Large pothole causing traffic slowdowns and hazard for motorcycles during rainy nights.');
    await page360.click('#wizard-btn-next');

    await page360.waitForTimeout(300);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step4.png' });
    const buffer = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64');
    await page360.setInputFiles('#images-input', {
        name: 'test-evidence.png',
        mimeType: 'image/png',
        buffer: buffer
    });
    await page360.waitForTimeout(500);
    await page360.click('#wizard-btn-next');

    await page360.waitForTimeout(300);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step5.png' });
    await page360.fill('#barangay', 'Barangay II (Pob.)');
    await page360.click('#wizard-btn-next');

    await page360.waitForTimeout(300);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step6.png' });
    await page360.click('#wizard-btn-next');

    await page360.waitForTimeout(300);
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step7.png' });
    await page360.check('#terms');
    await page360.screenshot({ path: '/tmp/opencode/audit-360x800-step7-ready.png' });

    console.log('360x800 screenshots captured successfully.');
    await browser.close();
})().catch(err => {
    console.error('360x800 test failed:', err);
    process.exit(1);
});
