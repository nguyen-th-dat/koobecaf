const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox'] });
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    p.on('console', (m) => console.log('C:', m.text().slice(0, 200)));
    await p.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await p.type('#email', 'chi');
    await p.type('#password', 'Password@123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.click('button[type=submit]')]);
    await p.goto('http://localhost/koobecaf/settings/profile', { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 300));

    await p.evaluate(() => {
        window.__log = [];
        document.addEventListener('pointerdown', (e) => window.__log.push('pointerdown:' + e.target.tagName + '.' + e.target.className), true);
        document.addEventListener('click', (e) => window.__log.push('click:' + e.target.tagName + '.' + e.target.className), true);
    });

    const sel = '.theme-option[data-theme="light"]';
    await p.evaluate((s) => document.querySelector(s).scrollIntoView({ block: 'center' }), sel);
    await new Promise((r) => setTimeout(r, 300));
    const rect = await p.evaluate((s) => { const r = document.querySelector(s).getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }, sel);
    console.log('rect', JSON.stringify(rect));
    await p.mouse.click(rect.x + rect.w / 2, rect.y + rect.h / 2);
    await new Promise((r) => setTimeout(r, 1000));
    console.log('log:', JSON.stringify(await p.evaluate(() => window.__log)));
    console.log('theme ->', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
