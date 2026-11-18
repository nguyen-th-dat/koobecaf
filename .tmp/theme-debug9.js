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
    await new Promise((r) => setTimeout(r, 500));

    await p.evaluate(() => {
        window.__log = [];
        ['pointerdown', 'mousedown', 'mouseup', 'click'].forEach((t) =>
            document.addEventListener(t, (e) => window.__log.push(t + ':' + e.target.tagName + '.' + e.target.className + ' defaultPrevented=' + e.defaultPrevented), true));
    });

    const sel = '.theme-option[data-theme="light"]';
    // Dùng scrollIntoView với behavior instant để tránh cuộn mượt
    await p.evaluate((s) => document.querySelector(s).scrollIntoView({ block: 'center', behavior: 'instant' }), sel);
    await new Promise((r) => setTimeout(r, 800));
    console.log('before theme:', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    const rect = await p.evaluate((s) => { const r = document.querySelector(s).getBoundingClientRect(); return { x: r.x, y: r.y, w: r.width, h: r.height }; }, sel);
    await p.mouse.click(rect.x + rect.w / 2, rect.y + rect.h / 2);
    await new Promise((r) => setTimeout(r, 1000));
    console.log('log:', JSON.stringify(await p.evaluate(() => window.__log), null, 1));
    console.log('after theme:', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
