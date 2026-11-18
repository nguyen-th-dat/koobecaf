const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox'] });
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    p.on('response', (r) => { if (r.url().includes('/settings/theme')) console.log('THEME REQ', r.status()); });
    await p.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await p.type('#email', 'chi');
    await p.type('#password', 'Password@123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.click('button[type=submit]')]);
    await p.goto('http://localhost/koobecaf/settings/profile', { waitUntil: 'networkidle2' });

    const target = await p.evaluate(() => {
        const opts = [...document.querySelectorAll('.theme-option')].map((o) => o.dataset.theme);
        const cur = document.documentElement.getAttribute('data-theme');
        // bấm vào theme khác với theme hiện tại
        const next = opts.find((t) => t !== cur);
        document.querySelector(`.theme-option[data-theme="${next}"]`).click();
        return { cur, next, opts };
    });
    console.log('clicked', JSON.stringify(target));
    await new Promise((r) => setTimeout(r, 1200));
    console.log('theme ->', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    console.log('active ->', await p.evaluate(() => document.querySelector('.theme-option.active').dataset.theme));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
