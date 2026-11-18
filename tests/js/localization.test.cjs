const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

for (const locale of ['en', 'ta', 'hi', 'ml']) {
    const context = {window: {}, document: {addEventListener() {}}};
    vm.createContext(context);
    vm.runInContext(fs.readFileSync(`public/js/locales/${locale}.js`, 'utf8'), context);
    vm.runInContext(fs.readFileSync('public/js/localization.js', 'utf8'), context);
    assert.equal(context.window.t('unknown.key'), 'unknown.key');
    assert.equal(context.window.t('common.available_only', {quantity: '1,234.50'}).includes('1,234.50'), true);
    assert.equal(context.window.t('validation.required', {attribute: 'Email'}).includes(':attribute'), false);
    assert.equal(context.window.t('common.stock_label', {name: ':stock', sku: 'SKU-001', stock: 10}).startsWith(':stock - SKU-001'), true);
    assert.equal(context.window.t('common.language'), {en: 'Language', ta: 'மொழி', hi: 'भाषा', ml: 'ഭാഷ'}[locale]);
    assert.equal(context.window.t('common.greeting_missing', {name: '<script>alert(1)</script>'}), 'common.greeting_missing');
    console.log(`${locale}: dictionary, fallback, interpolation, and numeric preservation passed`);
}
