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

test('paste, deletion and confirmation mismatch update the current password state', () => {
    const { fields, context } = setup();
    const password = fields.admin_password;
    const confirmation = fields.admin_password_confirmation;

    // Pasting into an input emits input; never rely on a keyup-only meter.
    password.value = 'V7!qR2#pL9@mX4$z';
    password.listeners.input();
    assert.equal(fields['password-level'].textContent, 'Strong');
    assert.equal(fields['password-strength-track'].attrs['aria-valuenow'], '100');

    confirmation.value = 'different-confirmation';
    confirmation.listeners.input();
    assert.equal(fields['password-match'].textContent, 'Passwords do not match.');
    assert.equal(vm.runInContext('passwordReady()', context), false);

    confirmation.value = password.value;
    confirmation.listeners.change();
    assert.equal(fields['password-match'].textContent, 'Passwords match.');
    assert.equal(vm.runInContext('passwordReady()', context), true);

    // Simulate selecting and deleting the full password after an autofill/paste.
    password.value = '';
    password.listeners.input();
    assert.equal(fields['password-level'].textContent, 'Blocked');
    assert.equal(fields['password-strength-track'].attrs['aria-valuenow'], '0');
    assert.equal(vm.runInContext('passwordReady()', context), false);
});

test('strong replacement clears prior predictable-password consent', () => {
    const { fields, context } = setup();
    const password = fields.admin_password;
    const confirmation = fields.admin_password_confirmation;
    const consent = fields['password-consent-checkbox'];
    password.value = 'NexoraAdmin1234!Qz';
    confirmation.value = password.value;
    password.listeners.input();
    confirmation.listeners.input();
    consent.checked = true;
    assert.equal(vm.runInContext('passwordReady()', context), true);

    password.value = 'V7!qR2#pL9@mX4$z';
    confirmation.value = password.value;
    password.listeners.input();
    confirmation.listeners.input();
    assert.equal(fields['password-level'].textContent, 'Strong');
    assert.equal(consent.checked, false, 'Consent should clear when no longer needed.');
    assert.equal(vm.runInContext('passwordReady()', context), true);
});
