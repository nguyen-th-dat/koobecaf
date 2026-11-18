const puppeteer = require('puppeteer-core');
(async () => {
    const b = await puppeteer.launch({ executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', headless: 'new', args: ['--no-sandbox', '--disable-gpu', '--hide-scrollbars'] });
    const errors = [];
    // Khách chưa đăng nhập
    const p = await b.newPage();
    await p.setViewport({ width: 1440, height: 1000 });
    p.on('pageerror', (e) => errors.push('guest: ' + e.message));
    await p.goto('http://localhost/koobecaf/u/an', { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    console.log('guest:', JSON.stringify(await p.evaluate(() => ({
        theme: document.documentElement.getAttribute('data-theme'),
        actions: [...document.querySelectorAll('.profile-actions .btn')].map((b) => ({ t: b.textContent.trim().replace(/\s+/g, ' '), href: b.getAttribute('href') })),
        stats: [...document.querySelectorAll('.profile-stat')].map((s) => s.textContent.trim().replace(/\s+/g, ' ')),
        overflowX: document.documentElement.scrollWidth > window.innerWidth + 1,
        appGrid: document.querySelector('.app-grid').className,
    }))));
    await p.close();

    // Kiểm tra nút theo dõi hoạt động (đăng nhập rồi bấm)
    const q = await b.newPage();
    await q.setViewport({ width: 1440, height: 1000 });
    q.on('pageerror', (e) => errors.push('follow: ' + e.message));
    await q.goto('http://localhost/koobecaf/login', { waitUntil: 'networkidle2' });
    await q.type('#email', 'chi');
    await q.type('#password', 'Password@123');
    await Promise.all([q.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}), q.click('button[type=submit]')]);
    await q.goto('http://localhost/koobecaf/u/em', { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 300));
    const before = await q.evaluate(() => document.querySelector('.profile-actions [data-action="follow"]').textContent.trim().replace(/\s+/g, ' '));
    await q.evaluate(() => document.querySelector('.profile-actions [data-action="follow"]').click());
    await new Promise((r) => setTimeout(r, 900));
    const after = await q.evaluate(() => {
        const btn = document.querySelector('.profile-actions [data-action="follow"]');
        return { text: btn.textContent.trim().replace(/\s+/g, ' '), cls: btn.className };
    });
    console.log('follow:', before, '->', JSON.stringify(after));

    // Trang bạn bè dạng lưới
    await q.goto('http://localhost/koobecaf/u/an?tab=followers', { waitUntil: 'networkidle2' });
    await new Promise((r) => setTimeout(r, 200));
    console.log('followers grid:', JSON.stringify(await q.evaluate(() => ({
        people: document.querySelectorAll('.profile-person-card').length,
        count: document.querySelector('.profile-people-count')?.textContent,
        head: document.querySelector('.profile-people-head h2')?.textContent,
        gridCols: getComputedStyle(document.querySelector('.profile-people-grid')).gridTemplateColumns,
    }))));

    console.log(errors.length ? 'LỖI: ' + errors.join(' | ') : 'Không lỗi JS.');
    await b.close();
})().catch((e) => console.log('ERR', e.message));
