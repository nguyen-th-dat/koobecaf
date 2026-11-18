/* Kiểm tra giao diện trang cá nhân + cài đặt (đăng nhập 1 lần, dùng chung cookie). */
const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost/koobecaf';

const VPS = [
    { name: 'desktop', width: 1440, height: 1000 },
    { name: 'tablet', width: 820, height: 1100 },
    { name: 'mobile', width: 390, height: 844 },
];

const PAGES = [
    { name: 'profile', path: '/u/chi' },
    { name: 'photos', path: '/u/chi?tab=photos' },
    { name: 'friends', path: '/u/chi?tab=friends' },
    { name: 'settings', path: '/settings/profile' },
];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'],
    });

    const ctx = browser.defaultBrowserContext();
    await ctx.overridePermissions(BASE, []);
    const errors = [];

    // Đăng nhập một lần
    const login = await browser.newPage();
    await login.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    if (await login.$('#email')) {
        await login.type('#email', 'chi');
        await login.type('#password', 'Password@123');
        await Promise.all([
            login.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
            login.click('button[type="submit"]'),
        ]);
    }
    console.log('Đăng nhập xong, URL:', login.url());
    await login.close();

    for (const vp of VPS) {
        const page = await browser.newPage();
        await page.setViewport({ width: vp.width, height: vp.height, deviceScaleFactor: 1 });
        page.on('console', (m) => { if (m.type() === 'error') errors.push(`[${vp.name}] ${m.text()}`); });
        page.on('pageerror', (e) => errors.push(`[${vp.name}] ${e.message}`));

        for (const p of PAGES) {
            await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle2' });
            await new Promise((r) => setTimeout(r, 200));

            const out = `D:\\www\\koobecaf\\.tmp\\${vp.name}-${p.name}.png`;
            await page.screenshot({ path: out, fullPage: true });

            const info = await page.evaluate(() => {
                const tabs = document.querySelector('.profile-tabs');
                const btns = [...document.querySelectorAll('.profile-actions .btn')];
                return {
                    overflowX: document.documentElement.scrollWidth > window.innerWidth + 1,
                    tabsScroll: tabs ? tabs.scrollWidth > tabs.clientWidth + 1 : null,
                    tabsWrap: tabs ? getComputedStyle(tabs).flexWrap : null,
                    tabHeights: tabs ? [...tabs.children].map((n) => Math.round(n.getBoundingClientRect().top)) : [],
                    actionTops: btns.map((b) => Math.round(b.getBoundingClientRect().top)),
                    statTops: [...document.querySelectorAll('.profile-stat')].map((n) => Math.round(n.getBoundingClientRect().top)),
                    gallery: document.querySelectorAll('.profile-gallery-item').length,
                    people: document.querySelectorAll('.profile-person-card').length,
                    bioCount: document.querySelector('[data-bio-count]')?.textContent ?? null,
                    themeActive: document.querySelector('.theme-option.active')?.dataset.theme ?? null,
                    theme: document.documentElement.getAttribute('data-theme'),
                };
            });
            console.log(`${vp.name}/${p.name}`, JSON.stringify(info));
        }
        await page.close();
    }

    console.log(errors.length ? '\nLỖI:\n' + errors.join('\n') : '\nKhông lỗi JS.');
    await browser.close();
})();
