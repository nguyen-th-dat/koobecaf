const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox'] });
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    await p.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await p.type('#email', 'chi');
    await p.type('#password', 'Password@123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.click('button[type=submit]')]);
    await p.goto('http://localhost/koobecaf/settings/profile', { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 300));

    for (const theme of ['light', 'dark']) {
        await p.evaluate((t) => document.querySelector(`.theme-option[data-theme="${t}"]`).scrollIntoView({ block: 'center' }), theme);
        await new Promise((r) => setTimeout(r, 300));
        const before = await p.evaluate(() => document.documentElement.getAttribute('data-theme'));
        await p.click(`.theme-option[data-theme="${theme}"]`);
        await new Promise((r) => setTimeout(r, 1000));
        const after = await p.evaluate(() => document.documentElement.getAttribute('data-theme'));
        console.log(`real-mouse click ${theme}: ${before} -> ${after}`);
    }
    await b.close();
})().catch((e) => console.log('ERR', e.message));
