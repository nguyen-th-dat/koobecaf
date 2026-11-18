const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox'] });
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    p.on('console', (m) => console.log('C:', m.type(), m.text().slice(0, 200)));
    p.on('pageerror', (e) => console.log('PE:', e.message));
    await p.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await p.type('#email', 'chi');
    await p.type('#password', 'Password@123');
    await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), p.click('button[type=submit]')]);
    await p.goto('http://localhost/koobecaf/settings/profile', { waitUntil: 'networkidle2' });
    await p.evaluate(() => {
        document.addEventListener('click', (e) => console.log('CLICK SEEN on', e.target.tagName, e.target.className), true);
    });
    await p.evaluate(() => document.querySelector('.theme-option[data-theme=dark]').scrollIntoView({ block: 'center' }));
    await new Promise((r) => setTimeout(r, 300));
    await p.click('.theme-option[data-theme=dark]');
    await new Promise((r) => setTimeout(r, 1200));
    console.log('theme ->', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
