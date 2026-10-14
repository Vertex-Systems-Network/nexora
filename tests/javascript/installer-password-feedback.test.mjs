import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/install/index.blade.php', import.meta.url), 'utf8');
const beginning = view.indexOf("const password = document.getElementById('admin_password');");
const end = view.indexOf('function refreshSummary()', beginning);
assert.ok(beginning !== -1 && end > beginning, 'password code must be present in the actual installer');
const code = view.slice(beginning, end);

function field() {
    return { value: '', checked: false, textContent: '', style: {}, attrs: {},
        listeners: {}, classList: { toggle() {} },
        addEventListener(type, callback) { this.listeners[type] = callback; },
        setAttribute(name, value) { this.attrs[name] = value; },
    };
}

function setup() {
    const names = ['admin_password', 'admin_password_confirmation', 'password-consent',
        'password-consent-checkbox', 'password-strength-track', 'password-strength-bar',
        'password-level', 'password-match'];
    const fields = Object.fromEntries(names.map((name) => [name, field()]));
    const window = { listeners: {}, addEventListener(type, callback) { this.listeners[type] = callback; } };
    const context = vm.createContext({ document: { getElementById: (name) => fields[name] }, window });
    vm.runInContext(code, context);
    return { fields, window, context };
}

test('installer meter updates weak to strong and recalculates consent/confirmation', () => {
    const { fields } = setup();
    const password = fields.admin_password;
    const confirmation = fields.admin_password_confirmation;
    const level = fields['password-level'];
    password.value = 'Abcdefgh12';
    password.listeners.input();
    assert.equal(level.textContent, 'Weak');
    assert.equal(fields['password-strength-track'].attrs['aria-valuenow'], '25');
    password.value = 'V7!qR2#pL9@mX4$z';
    password.listeners.input();
    confirmation.value = password.value;
    confirmation.listeners.input();
    assert.equal(level.textContent, 'Strong');
    assert.equal(fields['password-strength-track'].attrs['aria-valuenow'], '100');
    assert.equal(fields['password-match'].textContent, 'Passwords match.');
});

test('browser autofill/change is re-evaluated before continuing', () => {
    const { fields, window, context } = setup();
    fields.admin_password.value = 'V7!qR2#pL9@mX4$z';
    fields.admin_password_confirmation.value = 'V7!qR2#pL9@mX4$z';
    window.listeners.pageshow();
    assert.equal(fields['password-level'].textContent, 'Strong');
    assert.equal(vm.runInContext('passwordReady()', context), true);
    fields.admin_password.value = 'short';
    assert.equal(vm.runInContext('passwordReady()', context), false);
    assert.equal(fields['password-level'].textContent, 'Blocked');
});

test('predictable Super Admin patterns remain consent gated', () => {
    const { fields, context } = setup();
    fields.admin_password.value = 'NexoraAdmin1234!Qz';
    fields.admin_password.listeners.input();
    assert.equal(fields['password-level'].textContent, 'Medium');
    assert.equal(vm.runInContext('passwordReady()', context), false);
    fields.admin_password_confirmation.value = fields.admin_password.value;
    fields.admin_password_confirmation.listeners.input();
    fields['password-consent-checkbox'].checked = true;
    assert.equal(vm.runInContext('passwordReady()', context), true);
});
