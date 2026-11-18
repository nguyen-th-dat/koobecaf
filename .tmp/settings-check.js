/* Kiểm tra đổi theme trên trang cài đặt + vài hành vi vi mô. */
const puppeteer = require('puppeteer-core');
const CHROME = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const BASE = 'http://localhost/koobecaf';

(async () => {
    const browser = await puppeteer.launch({
        executablePath: CHROME,
        headless: 'new',
        args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'],
    });
    const errors = [];
    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 1000 });
    page.on('pageerror', (e) => errors.push('pageerror: ' + e.message));
    page.on('console', (m) => { if (m.type() === 'error') errors.push('console: ' + m.text()); });

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2' });
    await page.type('#email', 'chi');
    await page.type('#password', 'Password@123');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
        page.click('button[type="submit"]'),
    ]);

    await page.goto(`${BASE}/settings/profile`, { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 300));

    const before = await page.evaluate(() => ({
        theme: document.documentElement.getAttribute('data-theme'),
        active: document.querySelector('.theme-option.active')?.dataset.theme,
        hiddenInput: document.getElementById('themeInput')?.value,
    }));

    await page.click('.theme-option[data-theme="light"]');
    await new Promise((r) => setTimeout(r, 800));

    const after = await page.evaluate(() => ({
        theme: document.documentElement.getAttribute('data-theme'),
        active: document.querySelector('.theme-option.active')?.dataset.theme,
        hiddenInput: document.getElementById('themeInput')?.value,
        toast: document.querySelector('.toast')?.textContent.trim().replace(/\s+/g, ' ') ?? null,
    }));

    // Bộ đếm bio
    await page.evaluate(() => {
        const bio = document.getElementById('bio');
        bio.value = 'a'.repeat(470);
        bio.dispatchEvent(new Event('input', { bubbles: true }));
    });
    await new Promise((r) => setTimeout(r, 100));
    const counter = await page.evaluate(() => ({
        text: document.querySelector('[data-bio-count]').textContent,
        warning: document.querySelector('[data-bio-count]').closest('.form-text').classList.contains('is-warning'),
    }));

    // Preview ảnh bìa (tạo file ảnh nhỏ)
    const fs = require('fs');
    const b64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
    const tmp = 'D:\\www\\koobecaf\\.tmp\\tiny.png';
    fs.writeFileSync(tmp, Buffer.from(b64, 'base64'));
    const input = await page.$('#cover');
    await input.uploadFile(tmp);
    await new Promise((r) => setTimeout(r, 300));
    const preview = await page.evaluate(() => ({
        bg: getComputedStyle(document.querySelector('.profile-preview')).backgroundImage.slice(0, 40),
        hasEmptyClass: document.querySelector('.profile-preview').classList.contains('is-empty'),
    }));

    console.log('theme before:', JSON.stringify(before));
    console.log('theme after :', JSON.stringify(after));
    console.log('counter     :', JSON.stringify(counter));
    console.log('cover prev  :', JSON.stringify(preview));
    console.log(errors.length ? 'LỖI:\n' + errors.join('\n') : 'Không lỗi JS.');

    await browser.close();
})();
