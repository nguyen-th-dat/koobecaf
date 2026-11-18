/* Đo kích thước các khối trên trang cá nhân. */
const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost/koobecaf';

const VPS = [
    { name: 'desktop', width: 1440, height: 1000 },
    { name: 'tablet', width: 820, height: 1100 },
    { name: 'mobile', width: 390, height: 844 },
];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'],
    });

    for (const vp of VPS) {
        const page = await browser.newPage();
        await page.setViewport({ width: vp.width, height: vp.height, deviceScaleFactor: 1 });
        await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
        await page.type('#email', 'chi');
        await page.type('#password', 'Password@123');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
            page.click('button[type="submit"]'),
        ]);

        await page.goto(`${BASE}/u/chi`, { waitUntil: 'networkidle2' });
        const data = await page.evaluate(() => {
            const box = (sel) => {
                const el = document.querySelector(sel);
                if (!el) return null;
                const r = el.getBoundingClientRect();
                return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) };
            };
            const cs = (sel, prop) => {
                const el = document.querySelector(sel);
                return el ? getComputedStyle(el)[prop] : null;
            };
            return {
                cover: box('.profile-cover'),
                avatar: box('.profile-avatar'),
                name: box('.profile-name'),
                bio: box('.profile-bio'),
                actions: box('.profile-actions'),
                stats: box('.profile-stats-row'),
                tabs: box('.profile-tabs-wrap'),
                nameFont: cs('.profile-name', 'fontFamily'),
                nameSize: cs('.profile-name', 'fontSize'),
                tabOverflow: (() => {
                    const t = document.querySelector('.profile-tabs');
                    return t ? t.scrollWidth > t.clientWidth : null;
                })(),
                avatarOverflow: (() => {
                    const a = document.querySelector('.profile-avatar');
                    const p = a ? a.parentElement.getBoundingClientRect() : null;
                    return p ? Math.round(a.getBoundingClientRect().width) : null;
                })(),
                actionsWrap: (() => {
                    const acts = [...document.querySelectorAll('.profile-actions .btn')].map((b) => Math.round(b.getBoundingClientRect().top));
                    return acts;
                })(),
            };
        });
        console.log(vp.name, JSON.stringify(data, null, 1));
        await page.close();
    }
    await browser.close();
})();
