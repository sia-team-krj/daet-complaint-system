const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1310, height: 900 } });
    const page = await context.newPage();

    console.log('1. Logging in as citizen...');
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'pedro@gmail.com');
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    console.log('2. Visiting desktop complaint create page...');
    await page.goto('http://127.0.0.1:8000/complaints/create');
    await page.screenshot({ path: '/tmp/opencode/audit-desktop-after.png', fullPage: true });

    console.log('3. Switching to mobile viewport (390x844)...');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();
    await page.waitForSelector('#complaint-form');

    // Screenshot Step 1: Category
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step1.png' });

    // Select category to auto-advance or click next
    await page.selectOption('#category', 'road_damage');
    await page.waitForTimeout(400); // auto-advance delay

    // Step 2: Title
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step2.png' });
    await page.fill('#title', 'Broken streetlight near plaza');
    await page.click('#wizard-btn-next');

    // Step 3: What happened
    await page.waitForTimeout(300);
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step3.png' });
    // Test invalid short description hint
    await page.fill('#description', 'Too short');
    const hintText = await page.textContent('#wizard-step-hint');
    console.log('Step 3 short description hint:', hintText);
    await page.fill('#description', 'The streetlight has been flickering for three nights and is now completely dark, posing a risk to pedestrians walking home late at night.');
    await page.click('#wizard-btn-next');

    // Step 4: Photos
    await page.waitForTimeout(300);
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step4.png' });
    
    // Bypass client photo check or attach dummy file
    const buffer = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64');
    await page.setInputFiles('#images-input', {
        name: 'test-evidence.png',
        mimeType: 'image/png',
        buffer: buffer
    });
    await page.waitForTimeout(300);
    await page.click('#wizard-btn-next');

    // Step 5: Location
    await page.waitForTimeout(300);
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step5.png' });
    await page.fill('#barangay', 'Barangay II (Pob.)');
    await page.click('#wizard-btn-next');

    // Step 6: Visibility
    await page.waitForTimeout(300);
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step6.png' });
    await page.click('input[name="visibility"][value="private"]');
    await page.waitForTimeout(400); // auto-advance

    // Step 7: Review & submit
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step7.png' });
    await page.check('#terms');
    await page.screenshot({ path: '/tmp/opencode/audit-390x844-step7-ready.png' });

    console.log('Mobile wizard steps tested successfully.');
    await browser.close();
})().catch(err => {
    console.error('Playwright test failed:', err);
    process.exit(1);
});
