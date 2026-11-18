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
    console.log('state:', JSON.stringify(await p.evaluate(() => ({
        theme: document.documentElement.getAttribute('data-theme'),
        modal: !!document.querySelector('.modal-backdrop, .leave-modal, [data-leave-modal]'),
        draft: Object.keys(localStorage).filter((k) => k.startsWith('kb:draft')),
        backdropDisplay: (() => { const b = document.querySelector('.drawer-backdrop'); return b ? getComputedStyle(b).display + '/' + getComputedStyle(b).opacity : null; })(),
    }))));
    await p.click('.theme-option[data-theme=dark]');
    await new Promise((r) => setTimeout(r, 1000));
    console.log('after p.click ->', await p.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await b.close();
})().catch((e) => console.log('ERR', e.message));
