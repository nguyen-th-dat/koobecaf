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

    const sel = '.theme-option[data-theme="light"]';
    await p.evaluate((s) => document.querySelector(s).scrollIntoView({ block: 'center' }), sel);
    await new Promise((r) => setTimeout(r, 500));
    const info = await p.evaluate((s) => {
        const el = document.querySelector(s);
        const r = el.getBoundingClientRect();
        const cx = r.x + r.width / 2;
        const cy = r.y + r.height / 2;
        const hit = document.elementFromPoint(cx, cy);
        return { cx, cy, scrollY: window.scrollY, vh: window.innerHeight, hit: hit ? hit.tagName + '.' + hit.className : null };
    }, sel);
    console.log('info', JSON.stringify(info));
    await p.mouse.move(info.cx, info.cy);
    await p.mouse.down();
    await new Promise((r) => setTimeout(r, 60));
    await p.mouse.up();
    await new Promise((r) => setTimeout(r, 1000));
    console.log('theme ->', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
