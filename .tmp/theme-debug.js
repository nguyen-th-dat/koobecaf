const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox'] });
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    p.on('pageerror', (e) => console.log('PAGEERROR', e.message));
    p.on('response', (r) => { if (r.url().includes('/settings/theme')) console.log('THEME REQ', r.status(), r.url()); });
    await p.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await p.type('#email', 'chi');
    await p.type('#password', 'Password@123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.click('button[type=submit]')]);
    await p.goto('http://localhost/koobecaf/settings/profile', { waitUntil: 'networkidle2' });
    console.log('start', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await p.click('.theme-option[data-theme=dark]');
    await new Promise((r) => setTimeout(r, 1200));
    console.log('after ->', await p.evaluate(() => ({ theme: document.documentElement.getAttribute('data-theme'), active: document.querySelector('.theme-option.active') && document.querySelector('.theme-option.active').dataset.theme })));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
