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
    await p.evaluate(() => document.querySelector('.theme-option[data-theme=dark]').scrollIntoView({ block: 'center' }));
    await new Promise((r) => setTimeout(r, 400));
    console.log(await p.evaluate(() => {
        const e = document.querySelector('.theme-option[data-theme=dark]');
        const r = e.getBoundingClientRect();
        const top = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
        return { rect: { x: r.x, y: r.y, w: r.width, h: r.height }, hit: top ? top.className + '|' + top.tagName : null, theme: document.documentElement.getAttribute('data-theme') };
    }));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
