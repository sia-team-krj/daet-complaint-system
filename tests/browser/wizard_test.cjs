const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    
    // 1. Desktop test
    const contextDesktop = await browser.newContext({ viewport: { width: 1310, height: 900 } });
    const pageDesktop = await contextDesktop.newPage();

    console.log('1. Logging in as citizen (desktop)...');
    await pageDesktop.goto('http://127.0.0.1:8000/login');
    await pageDesktop.fill('input[name="email"]', 'pedro@gmail.com');
    await pageDesktop.fill('input[name="password"]', 'Test@1234');
    await pageDesktop.click('button[type="submit"]');
    await pageDesktop.waitForURL('**/dashboard');

    console.log('2. Visiting desktop complaint create page...');
    await pageDesktop.goto('http://127.0.0.1:8000/complaints/create');
    await pageDesktop.screenshot({ path: '/tmp/opencode/desktop-after-wizard.png', fullPage: true });

    // 2. Mobile test (390x844)
    const contextMobile = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const pageMobile = await contextMobile.newPage();

    console.log('3. Logging in as citizen (mobile)...');
    await pageMobile.goto('http://127.0.0.1:8000/login');
    await pageMobile.fill('input[name="email"]', 'pedro@gmail.com');
    await pageMobile.fill('input[name="password"]', 'Test@1234');
    await pageMobile.click('button[type="submit"]');
    await pageMobile.waitForURL('**/dashboard');

    console.log('4. Visiting mobile complaint create page (390x844)...');
    await pageMobile.goto('http://127.0.0.1:8000/complaints/create');
    await pageMobile.waitForSelector('#complaint-form', { state: 'attached' });

    // Step 1: Category
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step1.png' });
    await pageMobile.selectOption('#category', 'road_damage');
    await pageMobile.waitForTimeout(400); // auto-advance

    // Step 2: Title
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step2.png' });
    await pageMobile.fill('#title', 'Broken streetlight near plaza');
    await pageMobile.click('#wizard-btn-next');

    // Step 3: What happened
    await pageMobile.waitForTimeout(300);
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step3.png' });
    await pageMobile.fill('#description', 'The streetlight has been flickering for three nights and is now completely dark, posing a risk to pedestrians.');
    await pageMobile.click('#wizard-btn-next');

    // Step 4: Photos
    await pageMobile.waitForTimeout(300);
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step4.png' });
    const buffer = Buffer.from('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'base64');
    await pageMobile.setInputFiles('#images-input', {
        name: 'test-evidence.png',
        mimeType: 'image/png',
        buffer: buffer
    });
    await pageMobile.waitForTimeout(500);
    await pageMobile.click('#wizard-btn-next');

    // Step 5: Location
    await pageMobile.waitForTimeout(300);
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step5.png' });
    await pageMobile.fill('#barangay', 'Barangay II (Pob.)');
    await pageMobile.click('#wizard-btn-next');

    // Step 6: Visibility
    await pageMobile.waitForTimeout(300);
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step6.png' });
    await pageMobile.click('#wizard-btn-next');

    // Step 7: Review & submit
    await pageMobile.waitForTimeout(300);
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step7.png' });
    await pageMobile.check('#terms');
    await pageMobile.screenshot({ path: '/tmp/opencode/audit-390x844-step7-ready.png' });

    console.log('All mobile steps successfully captured and verified!');
    await browser.close();
})().catch(err => {
    console.error('Playwright test failed:', err);
    process.exit(1);
});
