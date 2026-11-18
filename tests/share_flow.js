'use strict';

/**
 * Kiểm tra luồng chia sẻ: mở modal, chuyển tab, gửi tin nhắn, chia sẻ lên feed.
 *
 * Chạy: node tests/share_flow.js
 */

const path = require('path');
const fs = require('fs');
const puppeteer = require('puppeteer-core');

const BASE = process.env.KB_BASE_URL || 'http://localhost/koobecaf';

const CHROME_PATHS = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
];

let passed = 0;
let failed = 0;

function check(name, condition, detail = '') {
    if (condition) {
        passed++;
        console.log(`[PASS] ${name}`);
    } else {
        failed++;
        console.log(`[FAIL] ${name}${detail ? ' — ' + detail : ''}`);
    }
}

async function main() {
    const executablePath = CHROME_PATHS.find(p => fs.existsSync(p));
    if (!executablePath) {
        console.error('[FAIL] Không tìm thấy Chrome/Edge.');
        process.exit(1);
    }

    const browser = await puppeteer.launch({
        executablePath,
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });
    await page.setCacheEnabled(false);

    const errors = [];
    page.on('pageerror', e => errors.push(e.message));

    await page.goto(BASE + '/', { waitUntil: 'networkidle2' });
    if (await page.$('[data-action="share"]') === null) {
        await page.goto(BASE + '/login', { waitUntil: 'networkidle2' });
        await page.type('input[name="email"]', process.env.KB_USER || 'an@koobecaf.test');
        await page.type('input[name="password"]', process.env.KB_PASS || 'Password@123');
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }),
            page.click('button[type="submit"]'),
        ]);
    }

    // 1. Mở modal từ thẻ bài viết trong feed.
    await page.click('[data-action="share"]');
    await page.waitForSelector('#kbShareModal:not([hidden])', { timeout: 5000 });
    check('Mở modal khi bấm nút Chia sẻ', true);

    // 2. Preview bài viết có dữ liệu.
    const preview = await page.evaluate(() => ({
        author: document.querySelector('[data-share-preview-author]').textContent.trim(),
        excerpt: document.querySelector('[data-share-preview-excerpt]').textContent.trim(),
        link: document.querySelector('[data-share-link]').value,
    }));
    check('Preview hiển thị tác giả', preview.author !== '', preview.author);
    check('Preview hiển thị trích dẫn', preview.excerpt !== '', preview.excerpt);
    check('Ô link chứa URL bài viết', /\/p\/\d+$/.test(preview.link), preview.link);

    // 3. Tab Tin nhắn tải danh sách người nhận.
    await page.click('[data-share-tab="message"]');
    await page.waitForSelector('.share-person', { timeout: 5000 });
    const people = await page.$$eval('.share-person', els => els.length);
    check('Tab Tin nhắn tải danh sách người nhận', people > 0, `${people} người`);

    // 4. Tab Sao chép link hiển thị đường dẫn.
    await page.click('[data-share-tab="link"]');
    const linkPanelVisible = await page.$eval('[data-share-panel="link"]', el => el.classList.contains('is-active'));
    check('Tab Sao chép link hiển thị', linkPanelVisible);

    // 5. Gửi bài viết cho một người qua tin nhắn.
    await page.click('[data-share-tab="message"]');
    await page.waitForSelector('.share-person');
    await page.click('.share-person');
    await page.waitForSelector('.share-person.is-sent', { timeout: 5000 });
    check('Gửi bài viết qua tin nhắn thành công', true);

    // 6. Chọn quyền riêng tư rồi chia sẻ lên trang cá nhân.
    await page.click('[data-share-tab="feed"]');
    await page.click('[data-share-privacy="followers"]');
    const privacyActive = await page.$eval('[data-share-privacy="followers"]', el => el.classList.contains('is-active'));
    check('Đổi quyền riêng tư thành công', privacyActive);

    await page.type('[data-share-text]', 'Bài viết hay quá, mình cũng muốn chia sẻ!');
    await page.click('[data-share-submit]');
    await page.waitForFunction(
        () => document.getElementById('kbShareModal').hidden === true,
        { timeout: 5000 }
    );
    check('Chia sẻ lên trang cá nhân thành công (modal đóng)', true);

    // 7. Số liệu lượt chia sẻ được cập nhật.
    await new Promise(r => setTimeout(r, 400));
    const counter = await page.$eval('[data-share-count]', el => el.textContent.trim());
    check('Bộ đếm lượt chia sẻ cập nhật', /[1-9]/.test(counter), counter);

    // 8. Đóng modal bằng phím Escape.
    await page.click('[data-action="share"]');
    await page.waitForSelector('#kbShareModal:not([hidden])');
    await page.keyboard.press('Escape');
    await new Promise(r => setTimeout(r, 200));
    const closed = await page.$eval('#kbShareModal', el => el.hidden);
    check('Đóng modal bằng phím Escape', closed);

    check('Không có lỗi JS', errors.length === 0, errors.join(' | '));

    await browser.close();

    console.log(`\n${passed} pass, ${failed} fail`);
    process.exit(failed === 0 ? 0 : 1);
}

main().catch(err => {
    console.error(err);
    process.exit(1);
});
