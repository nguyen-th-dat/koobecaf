/* Kiểm tra bố cục hồ sơ bằng cách đo trực tiếp (không phụ thuộc ảnh). */
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
    { name: 'settings', path: '/settings/profile' },
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
            await page.goto(`${BASE}${p.path}`, { waitUntil: 'networkidle2' });
            await new Promise((r) => setTimeout(r, 200));

            const info = await page.evaluate(() => {
                const rect = (sel) => {
                    const el = document.querySelector(sel);
                    if (!el) return null;
                    const r = el.getBoundingClientRect();
                    return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) };
                };
                const style = (sel, prop) => {
                    const el = document.querySelector(sel);
                    return el ? getComputedStyle(el)[prop] : null;
                };
                const overlap = (a, b) => {
                    if (!a || !b) return false;
                    return !(a.x + a.w <= b.x || b.x + b.w <= a.x || a.y + a.h <= b.y || b.y + b.h <= a.y);
                };
                const cover = rect('.profile-cover');
                const avatar = rect('.profile-avatar');
                const heroBody = rect('.profile-hero-body');
                const name = rect('.profile-name');
                const actions = rect('.profile-actions');
                const stats = rect('.profile-stats-row');
                const tabs = rect('.profile-tabs-wrap');

                return {
                    theme: document.documentElement.getAttribute('data-theme'),
                    overflowX: document.documentElement.scrollWidth > window.innerWidth + 1,
                    cover,
                    avatar,
                    avatarOverlapsCover: overlap(avatar, cover),
                    heroBody,
                    name,
                    nameFont: style('.profile-name', 'fontFamily'),
                    bio: rect('.profile-bio'),
                    actions,
                    actionsInsideCard: actions && heroBody
                        ? actions.x + actions.w <= heroBody.x + heroBody.w + 1
                        : null,
                    stats,
                    statsInsideCard: stats && heroBody
                        ? stats.x + stats.w <= heroBody.x + heroBody.w + 1
                        : null,
                    tabs,
                    tabsBelowStats: tabs && stats ? tabs.y > stats.y + stats.h - 1 : null,
                    // trạng thái các nút hành động
                    actionBtns: [...document.querySelectorAll('.profile-actions .btn')].map((b) => {
                        const r = b.getBoundingClientRect();
                        return { t: b.textContent.trim().replace(/\s+/g, ' '), x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width) };
                    }),
                    // phần tử tràn ra ngoài khung chính
                    overflowing: [...document.querySelectorAll('.profile-hero *')]
                        .filter((el) => {
                            const card = document.querySelector('.profile-hero').getBoundingClientRect();
                            const r = el.getBoundingClientRect();
                            return r.width > 0 && (r.right > card.right + 1 || r.left < card.left - 1);
                        })
                        .map((el) => el.className || el.tagName)
                        .slice(0, 8),
                    // trang cài đặt
                    settings: document.querySelector('.settings-head') ? {
                        title: document.querySelector('.settings-title')?.textContent.trim(),
                        preview: rect('.profile-preview'),
                        previewAvatar: rect('.profile-preview-avatar'),
                        themeOptions: [...document.querySelectorAll('.theme-option')].map((o) => o.dataset.theme + (o.classList.contains('active') ? '*' : '')),
                        cardTitles: [...document.querySelectorAll('.settings-card-title')].map((t) => t.textContent.trim().replace(/\s+/g, ' ')),
                    } : null,
                };
            });
            console.log(`\n=== ${vp.name} / ${p.name} ===`);
            console.log(JSON.stringify(info, null, 1));
        }
        await page.close();
    }
    await browser.close();
})();
