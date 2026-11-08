const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const page = await context.newPage();

    console.log('Logging in...');
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'pedro@gmail.com');
    await page.fill('input[name="password"]', 'Test@1234');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard');

    console.log('Going to complaint create...');
    await page.goto('http://127.0.0.1:8000/complaints/create');
    console.log('Current URL:', page.url());
    const content = await page.content();
    console.log('Has complaint-form?', content.includes('complaint-form'));
    await page.screenshot({ path: '/tmp/opencode/debug-mobile.png', fullPage: true });

    await browser.close();
})().catch(err => {
    console.error(err);
    process.exit(1);
});
