/* Kiểm tra bố cục + chụp màn hình toàn bộ các trang sau khi đổi giao diện. */
const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost:8000';

const VPS = [
    { name: 'desktop', width: 1440, height: 1000 },
    { name: 'tablet', width: 820, height: 1100 },
    { name: 'mobile', width: 390, height: 844 },
];

const PAGES = [
    { name: 'home', path: '/' },
    { name: 'profile', path: '/u/chi' },
    { name: 'post', path: '/p/1' },
    { name: 'notifications', path: '/notifications' },
    { name: 'friends', path: '/friends' },
    { name: 'settings', path: '/settings/profile' },
    { name: 'chat', path: '/chat' },
    { name: 'search', path: '/search?q=a' },
    { name: 'create', path: '/posts/create' },
];

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'],
    });

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
    await login.close();

    for (const vp of VPS) {
        const page = await browser.newPage();
        await page.setViewport({ width: vp.width, height: vp.height, deviceScaleFactor: 1 });
        for (const p of PAGES) {
            try {
                await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle2' });
            } catch (e) {
                console.log(`SKIP ${vp.name}/${p.name}: ${e.message}`);
                continue;
            }
            await new Promise((r) => setTimeout(r, 250));

            const info = await page.evaluate(() => {
                const overflow = [];
                document.querySelectorAll('body *').forEach((el) => {
                    const r = el.getBoundingClientRect();
                    if (r.width > 0 && r.right > window.innerWidth + 2) {
                        overflow.push((el.className || el.tagName).toString().slice(0, 60));
                    }
                });
                return {
                    url: location.pathname,
                    title: document.title,
                    scrollX: document.documentElement.scrollWidth > window.innerWidth + 1,
                    overflow: [...new Set(overflow)].slice(0, 6),
                };
            });
            console.log(`[${vp.name}/${p.name}]`, JSON.stringify(info));
            await page.screenshot({ path: `.tmp/shot-${vp.name}-${p.name}.png`, fullPage: false });
        }
        await page.close();
    }
    await browser.close();
})();
