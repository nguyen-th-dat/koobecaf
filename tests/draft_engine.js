/**
 * Kiểm tra logic lưu nháp form trong assets/js/app.js bằng DOM giả lập tối giảu.
 *
 * Chạy: node tests/draft_engine.js
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const appJs = fs.readFileSync(path.join(__dirname, '..', 'assets', 'js', 'app.js'), 'utf8');

// Chuẩn hoá line-ending để không phụ thuộc CRLF/LF trên đĩa.
const normalized = appJs.replace(/\r\n/g, '\n');

// Chỉ lấy phần code lưu nháp để test độc lập với DOM của trang.
const start = normalized.indexOf('/* =============================================================\n   Lưu nháp form');
if (start === -1) {
    console.error('[FAIL] Không tìm thấy khối code lưu nháp trong app.js');
    process.exit(1);
}
const draftCode = normalized.slice(start);

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

/** Element input/textarea/select giả lập. */
class FakeElement {
    constructor(name, type = 'text', value = '') {
        this.name = name;
        this.type = type;
        this.value = value;
        this.checked = false;
        this.form = null;
        this._listeners = {};
    }

    addEventListener(type, handler) {
        this._listeners[type] = this._listeners[type] || [];
        this._listeners[type].push(handler);
    }

    /** Sự kiện nổi (bubble) lên form như trong DOM thật. */
    dispatch(type) {
        (this._listeners[type] || []).forEach(handler => handler({}));
        if (this.form) this.form.dispatch(type);
    }
}

class FakeForm {
    constructor(fields, dataset = {}) {
        this.fields = fields;
        this.dataset = { ...dataset };
        this._listeners = {};
        fields.forEach(field => { field.form = this; });
    }

    addEventListener(type, handler) {
        this._listeners[type] = this._listeners[type] || [];
        this._listeners[type].push(handler);
    }

    dispatch(type) {
        (this._listeners[type] || []).forEach(handler => handler({}));
    }

    /** Sự kiện input/change nổi (bubble) từ field lên form như trong DOM thật. */
    dispatchFromField(type) {
        this.dispatch(type);
    }

    appendChild(child) {
        this.appended = child;
        return child;
    }

    querySelector(selector) {
        const found = this.querySelectorAll(selector);
        return found.length > 0 ? found[0] : null;
    }

    querySelectorAll(selector) {
        // Node con do code tự tạo (vd chỉ báo "đã lưu nháp").
        if (selector === '[data-draft-hint]') {
            return this.appended ? [this.appended] : [];
        }

        // Selector liệt kê nhiều loại field: input[name], textarea[name], select[name]
        if (selector.includes(',')) {
            return this.fields.filter(f => /^(input|textarea|select)$/.test(f.tag || 'input'));
        }

        const nameMatch = selector.match(/^\[name="(.+)"\]$/);
        if (nameMatch) {
            return this.fields.filter(f => f.name === nameMatch[1]);
        }

        const radioMatch = selector.match(/^\[name="(.+)"\]:checked$/);
        if (radioMatch) {
            return this.fields.filter(f => f.name === radioMatch[1] && f.checked);
        }

        return [];
    }
}

function makeContext(form, storage) {
    const document = {
        body: { dataset: {} },
        querySelectorAll: () => (form ? [form] : []),
        querySelector: () => null,
        createElement: () => ({
            className: '',
            dataset: {},
            hidden: true,
            innerHTML: '',
            textContent: '',
            setAttribute() {},
            appendChild() {},
        }),
    };

    const context = {
        document,
        window: {
            localStorage: storage,
            addEventListener: () => {},
        },
        setTimeout: (fn) => fn(),
        clearTimeout: () => {},
        console,
        CSS: { escape: (value) => value },
    };
    context.window.document = document;
    context.globalThis = context;

    return vm.createContext(context);
}

function makeStorage(initial = {}) {
    const data = { ...initial };
    return {
        getItem: key => (key in data ? data[key] : null),
        setItem: (key, value) => { data[key] = String(value); },
        removeItem: key => { delete data[key]; },
        _data: data,
    };
}

function runDraftEngine(form, storage) {
    const context = makeContext(form, storage);
    vm.runInContext(draftCode + '\n;({ readDraft, writeDraft, removeDraft, collectDraftFields, applyDraft, initDrafts });', context);
    return context;
}

// ------------------------------------------------------------------
// 1. Lưu nháp khi nhập
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', 'a@b.com');
    const form = new FakeForm([email], { draft: 'login' });
    const storage = makeStorage();

    const ctx = runDraftEngine(form, storage);
    ctx.initDrafts();

    email.value = 'user@example.com';
    email.dispatch('input');

    const saved = JSON.parse(storage.getItem('kb:draft:0:login'));
    check('Lưu nháp vào localStorage khi nhập', saved !== null && saved[0].value === 'user@example.com', JSON.stringify(saved));
    check('Nháp lưu đúng tên field', saved !== null && saved[0].name === 'email');
}

// ------------------------------------------------------------------
// 2. Khôi phục nháp khi tải trang
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', '');
    const form = new FakeForm([email], { draft: 'login' });
    const storage = makeStorage({
        'kb:draft:0:login': JSON.stringify([{ name: 'email', kind: 'value', value: 'saved@example.com' }]),
    });

    const ctx = runDraftEngine(form, storage);
    ctx.initDrafts();

    check('Khôi phục nháp khi tải trang', email.value === 'saved@example.com', 'value=' + email.value);
}

// ------------------------------------------------------------------
// 3. Giá trị do server render được giữ nguyên
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', 'server@example.com');
    const form = new FakeForm([email], { draft: 'login', oldFields: 'email' });
    const storage = makeStorage({
        'kb:draft:0:login': JSON.stringify([{ name: 'email', kind: 'value', value: 'draft@example.com' }]),
    });

    const ctx = runDraftEngine(form, storage);
    ctx.initDrafts();

    check('Giá trị server render không bị nháp cũ đè', email.value === 'server@example.com', 'value=' + email.value);
}

// ------------------------------------------------------------------
// 4. Nháp chỉ được đè lên field không do server render
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', 'server@example.com');
    const displayName = new FakeElement('display_name', 'text', '');
    const form = new FakeForm([email, displayName], { draft: 'register', oldFields: 'email' });
    const storage = makeStorage({
        'kb:draft:0:register': JSON.stringify([
            { name: 'email', kind: 'value', value: 'draft@example.com' },
            { name: 'display_name', kind: 'value', value: 'Nguyen Van A' },
        ]),
    });

    const ctx = runDraftEngine(form, storage);
    ctx.initDrafts();

    check('Field không do server render lấy giá trị nháp', displayName.value === 'Nguyen Van A', 'value=' + displayName.value);
    check('Field do server render giữ nguyên', email.value === 'server@example.com', 'value=' + email.value);
}

// ------------------------------------------------------------------
// 5. Không lưu field nhạy cảm (password, file, hidden)
// ------------------------------------------------------------------
{
    const password = new FakeElement('password', 'password', 'secret');
    const file = new FakeElement('images[]', 'file', '');
    const hidden = new FakeElement('_token', 'hidden', 'abc');
    const content = new FakeElement('content', 'text', 'hello');
    const form = new FakeForm([password, file, hidden, content], { draft: 'composer' });
    const storage = makeStorage();

    const ctx = runDraftEngine(form, storage);
    ctx.initDrafts();

    content.dispatch('input');

    const saved = JSON.parse(storage.getItem('kb:draft:0:composer'));
    const names = saved.map(item => item.name);
    check('Không lưu field password', !names.includes('password'), names.join(','));
    check('Không lưu field file', !names.includes('images[]'), names.join(','));
    check('Không lưu field hidden', !names.includes('_token'), names.join(','));
    check('Có lưu field nội dung', names.includes('content'), names.join(','));
}

// ------------------------------------------------------------------
// 6. Xoá nháp khi server báo form đã xử lý thành công
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', 'a@b.com');
    const form = new FakeForm([email], { draft: 'login' });
    const storage = makeStorage({
        'kb:draft:0:login': JSON.stringify([{ name: 'email', kind: 'value', value: 'x@y.com' }]),
    });

    const ctx = runDraftEngine(form, storage);
    ctx.document.body.dataset.clearedDrafts = 'login,register';
    ctx.initDrafts();

    check('Xoá nháp sau khi form thành công', storage.getItem('kb:draft:0:login') === null);
}

// ------------------------------------------------------------------
// 7. Nháp không bị ghi đè bởi dữ liệu hỏng trong localStorage
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', '');
    const form = new FakeForm([email], { draft: 'login' });
    const storage = makeStorage({ 'kb:draft:0:login': '{khong phai json' });

    let threw = false;
    try {
        const ctx = runDraftEngine(form, storage);
        ctx.initDrafts();
    } catch (e) {
        threw = true;
    }
    check('localStorage hỏng không gây lỗi JS', !threw);
    check('Giá trị field giữ nguyên khi localStorage hỏng', email.value === '', 'value=' + email.value);
}

// ------------------------------------------------------------------
// 8. localStorage bị chặn không gây lỗi
// ------------------------------------------------------------------
{
    const email = new FakeElement('email', 'email', '');
    const form = new FakeForm([email], { draft: 'login' });
    const storage = {
        getItem: () => { throw new Error('blocked'); },
        setItem: () => { throw new Error('blocked'); },
        removeItem: () => { throw new Error('blocked'); },
    };

    let threw = false;
    try {
        const ctx = runDraftEngine(form, storage);
        ctx.initDrafts();
        email.dispatch('input');
    } catch (e) {
        threw = true;
    }
    check('localStorage bị chặn không gây lỗi JS', !threw);
}

console.log('\n================================');
console.log(`PASS: ${passed} | FAIL: ${failed}`);
console.log('================================');

process.exit(failed === 0 ? 0 : 1);
