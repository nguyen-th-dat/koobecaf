/**
 * Kiểm tra logic cảnh báo rời trang khi còn nội dung chưa gửi.
 *
 * Chạy: node tests/leave_guard.js
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const appJs = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'app.js'), 'utf8');

// Chuẩn hoá line-ending để không phụ thuộc CRLF/LF trên đĩa.
const normalized = appJs.replace(/\r\n/g, '\n');

const start = normalized.indexOf('/* =============================================================\n   Cảnh báo rời trang');
if (start === -1) {
    console.error('[FAIL] Không tìm thấy khối code cảnh báo rời trang trong app.js');
    process.exit(1);
}
const end = normalized.indexOf('/* =============================================================\n   Lưu nháp form');
const guardCode = normalized.slice(start, end === -1 ? undefined : end);

let passed = 0;
let failed = 0;

function check(name, condition, detail = '') {
    if (condition) {
        passed++;
        console.log(`[PASS] ${name}`);
        return;
    }
    failed++;
    console.log(`[FAIL] ${name}${detail ? ' - ' + detail : ''}`);
}

class FakeElement {
    constructor(name, type = 'text', value = '', extra = {}) {
        this.name = name;
        this.type = type;
        this.value = value;
        this.checked = false;
        this.tagName = 'INPUT';
        this.id = extra.id || '';
        this.placeholder = extra.placeholder || '';
        this.files = extra.files || null;
        this.closest = (selector) => (selector === 'label' ? (extra.label || null) : null);
    }
}

class FakeForm {
    constructor(fields, dataset = {}) {
        this.fields = fields;
        this.dataset = { ...dataset };
        this.submitted = false;
    }

    querySelectorAll(selector) {
        if (selector.includes(',')) return this.fields;
        const nameMatch = selector.match(/^\[name="(.+)"\]$/);
        if (nameMatch) return this.fields.filter(f => f.name === nameMatch[1]);
        const radioMatch = selector.match(/^\[name="(.+)"\]:checked$/);
        if (radioMatch) return this.fields.filter(f => f.name === radioMatch[1] && f.checked);
        return [];
    }
}

function makeContext(forms) {
    const listeners = {};
    const document = {
        body: { dataset: {} },
        addEventListener: (type, handler) => { listeners[type] = handler; },
        querySelectorAll: selector => (selector === 'form[data-guard]' ? forms : []),
        querySelector: () => null,
        createElement: () => ({
            className: '',
            hidden: true,
            innerHTML: '',
            textContent: '',
            appendChild() {},
            querySelector: () => null,
            setAttribute() {},
            focus() {},
        }),
        getElementById: () => null,
    };

    const context = {
        document,
        window: {
            location: { href: 'http://localhost/', pathname: '/', search: '' },
            addEventListener: (type, handler) => { listeners[type] = handler; },
        },
        console,
        CSS: { escape: v => v },
        URL,
        Set,
        WeakMap,
        Map,
    };
    context.window.document = document;
    context.globalThis = context;

    return vm.createContext(context);
}

function run(forms) {
    const context = makeContext(forms);
    vm.runInContext(guardCode + '\n;({ initGuards, losingForms, isFieldDirty, guardForms });', context);
    return context;
}

// ------------------------------------------------------------------
// 1. Form rỗng: không có gì để mất
// ------------------------------------------------------------------
{
    const content = new FakeElement('content', 'text', '');
    const form = new FakeForm([content], { guard: 'Bài viết đang soạn' });

    const ctx = run([form]);
    ctx.initGuards();

    check('Form rỗng không bị coi là mất dữ liệu', ctx.losingForms().length === 0);
}

// ------------------------------------------------------------------
// 2. Người dùng gõ nội dung vào form rỗng: bị coi là mất dữ liệu
// ------------------------------------------------------------------
{
    const content = new FakeElement('content', 'text', '');
    const form = new FakeForm([content], { guard: 'Bài viết đang soạn' });

    const ctx = run([form]);
    ctx.initGuards();
    content.value = 'Xin chào';

    const dirty = ctx.losingForms();
    check('Form có nội dung bị coi là mất dữ liệu', dirty.length === 1);
    check('Xác định đúng nhãn form', dirty.length === 1 && dirty[0].label === 'Bài viết đang soạn', dirty.length ? dirty[0].label : '');
    check('Liệt kê field bị mất', dirty.length === 1 && dirty[0].items.length === 1, JSON.stringify(dirty[0]?.items));
    check('Xem trước hiển thị nội dung vừa gõ', dirty.length === 1 && dirty[0].items[0].preview === 'Xin chào', JSON.stringify(dirty[0]?.items));
}

// ------------------------------------------------------------------
// 3. Form hồ sơ có sẵn giá trị: không mất nếu không sửa
// ------------------------------------------------------------------
{
    const displayName = new FakeElement('display_name', 'text', 'Ten Hien Tai');
    const bio = new FakeElement('bio', 'text', '');
    const form = new FakeForm([displayName, bio], { guard: 'Thông tin hồ sơ' });

    const ctx = run([form]);
    ctx.initGuards();

    check('Giá trị mặc định không bị coi là mất dữ liệu', ctx.losingForms().length === 0);
}

// ------------------------------------------------------------------
// 4. Form hồ sơ bị sửa: có mất dữ liệu
// ------------------------------------------------------------------
{
    const displayName = new FakeElement('display_name', 'text', 'Ten Hien Tai');
    const bio = new FakeElement('bio', 'text', '');
    const form = new FakeForm([displayName, bio], { guard: 'Thông tin hồ sơ' });

    const ctx = run([form]);
    ctx.initGuards();
    displayName.value = 'Ten Da Sua';

    check('Sửa giá trị thì bị coi là mất dữ liệu', ctx.losingForms().length === 1);
}

// ------------------------------------------------------------------
// 5. Giá trị server render lại sau lỗi validate: vẫn phải cảnh báo
// ------------------------------------------------------------------
{
    const content = new FakeElement('content', 'text', 'Noi dung gui bi loi');
    const form = new FakeForm([content], { guard: 'Bài viết đang soạn', oldFields: 'content' });

    const ctx = run([form]);
    ctx.initGuards();

    check('Giá trị do server render lại vẫn được cảnh báo', ctx.losingForms().length === 1);
}

// ------------------------------------------------------------------
// 6. File đã chọn: có mất dữ liệu
// ------------------------------------------------------------------
{
    const images = new FakeElement('images[]', 'file', '', { files: [{ name: 'a.png' }] });
    const form = new FakeForm([images], { guard: 'Bài viết đang soạn' });

    const ctx = run([form]);
    ctx.initGuards();

    const dirty = ctx.losingForms();
    check('File đã chọn bị coi là mất dữ liệu', dirty.length === 1);
    check('Hiển thị số lượng tệp đã chọn', dirty.length === 1 && dirty[0].items[0].preview.includes('tệp'), JSON.stringify(dirty[0]?.items));
}

// ------------------------------------------------------------------
// 7. Nhiều form: chỉ những form có dữ liệu mới bị tính
// ------------------------------------------------------------------
{
    const content = new FakeElement('content', 'text', '');
    const formEmpty = new FakeForm([content], { guard: 'Bài viết đang soạn' });
    const email = new FakeElement('email', 'email', '');
    const formFilled = new FakeForm([email], { guard: 'Thông tin đăng nhập' });

    const ctx = run([formEmpty, formFilled]);
    ctx.initGuards();
    email.value = 'a@b.com';

    const dirty = ctx.dirtyGuardForms();
    check('Chỉ tính form có dữ liệu', dirty.length === 1 && dirty[0].label === 'Thông tin đăng nhập', JSON.stringify(dirty.map(d => d.label)));
}

// ------------------------------------------------------------------
// 8. Xem trước nội dung bị cắt ngắn
// ------------------------------------------------------------------
{
    const long = 'x'.repeat(200);
    const content = new FakeElement('content', 'text', '');
    const form = new FakeForm([content], { guard: 'Bài viết đang soạn' });

    const ctx = run([form]);
    ctx.initGuards();
    content.value = long;

    const dirty = ctx.dirtyGuardForms();
    check('Nội dung dài được cắt bớt khi xem trước', dirty.length === 1 && dirty[0].items[0].preview.length <= 61, String(dirty[0]?.items[0].preview.length));
}

// ------------------------------------------------------------------
// 9. Field ẩn không được coi là mất dữ liệu
// ------------------------------------------------------------------
{
    const token = new FakeElement('_token', 'hidden', 'abc');
    const form = new FakeForm([token], { guard: 'Bài viết đang soạn' });

    const ctx = run([form]);
    ctx.initGuards();

    check('Field ẩn không bị coi là mất dữ liệu', ctx.dirtyGuardForms().length === 0);
}

console.log('\n================================');
console.log(`PASS: ${passed} | FAIL: ${failed}`);
console.log('================================');

process.exit(failed === 0 ? 0 : 1);
