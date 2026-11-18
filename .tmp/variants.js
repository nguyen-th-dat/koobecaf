/* Kiểm tra các trạng thái: có ảnh bìa, thư viện ảnh, nút kết bạn/theo dõi, theme sáng. */
const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost/koobecaf';

async function loginAs(browser, user) {
    const p = await browser.newPage();
    await p.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    if (await p.$('#email')) {
        await p.type('#email', user);
        await p.type('#password', 'Password@123');
        await Promise.all([
            p.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
            p.click('button[type="submit"]'),
        ]);
    }
    await p.close();
}

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'],
    });

    const errors = [];

    // 1) Người có ảnh bìa + avatar
    await loginAs(browser, 'an');
    let page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}/u/an`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-cover.png', fullPage: true });
    console.log('an profile:', JSON.stringify(await page.evaluate(() => ({
        coverHasImage: getComputedStyle(document.querySelector('.profile-cover')).backgroundImage !== 'none',
        avatarSrc: document.querySelector('.profile-avatar').getAttribute('src'),
        name: document.querySelector('.profile-name').textContent.trim(),
        stats: [...document.querySelectorAll('.profile-stat')].map((s) => s.textContent.trim().replace(/\s+/g, ' ')),
        actions: [...document.querySelectorAll('.profile-actions .btn')].map((b) => b.textContent.trim().replace(/\s+/g, ' ')),
        tabs: [...document.querySelectorAll('.profile-tabs .nav-link')].map((t) => t.textContent.trim().replace(/\s+/g, ' ')),
    }))));
    await page.close();

    // 2) Người có ảnh trong bài viết -> tab Ảnh
    await loginAs(browser, 'ext7207');
    page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}/u/ext7207?tab=photos`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-gallery.png', fullPage: true });
    console.log('gallery:', JSON.stringify(await page.evaluate(() => ({
        items: document.querySelectorAll('.profile-gallery-item').length,
        firstImg: document.querySelector('.profile-gallery-item img')?.getAttribute('src'),
        empty: !!document.querySelector('.empty-state'),
        tabsCount: document.querySelectorAll('.profile-tab-count').length,
    }))));
    await page.close();

    // 3) Xem người khác khi đã đăng nhập -> nút kết bạn / theo dõi + theme sáng
    await loginAs(browser, 'an');
    page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}/u/chi`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-other-light.png', fullPage: true });
    console.log('other (light):', JSON.stringify(await page.evaluate(() => ({
        theme: document.documentElement.getAttribute('data-theme'),
        actions: [...document.querySelectorAll('.profile-actions .btn')].map((b) => b.textContent.trim().replace(/\s+/g, ' ')),
        forms: document.querySelectorAll('.profile-form-action').length,
    }))));

    // Đổi sang theme tối rồi chụp lại
    await page.click('.theme-option[data-theme="dark"]').catch(() => {});
    await new Promise((r) => setTimeout(r, 600));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-other-dark.png', fullPage: true });
    console.log('after theme switch:', await page.evaluate(() => document.documentElement.getAttribute('data-theme')));
    await page.close();

    // 4) Mobile: profile + settings
    await loginAs(browser, 'an');
    page = await browser.newPage();
    await page.setViewport({ width: 390, height: 844, isMobile: true, hasTouch: true });
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}/u/an`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-mobile.png', fullPage: true });
    await page.goto(`${BASE}/settings/profile`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    await page.screenshot({ path: 'D:\\www\\koobecaf\\.tmp\\v-mobile-settings.png', fullPage: true });
    console.log('mobile ok');

    console.log(errors.length ? 'LỖI: ' + errors.join(' | ') : 'Không lỗi JS.');
    await browser.close();
})();
