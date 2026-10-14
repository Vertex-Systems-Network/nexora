import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { test } from 'node:test';

const require = createRequire(import.meta.url);
const ts = require('typescript');

const jsx = (type, props = {}) => ({ type, props });
const component = (name) => ({ children, ...props }) => jsx(name, { ...props, children });

function compile(file, mocks) {
    const contents = readFileSync(new URL(`../../${file}`, import.meta.url), 'utf8');
    const result = ts.transpileModule(contents, {
        fileName: file,
        reportDiagnostics: true,
        compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.CommonJS, jsx: ts.JsxEmit.ReactJSX },
    });
    const errors = (result.diagnostics ?? []).filter((diagnostic) => diagnostic.category === ts.DiagnosticCategory.Error);
    assert.equal(errors.length, 0, `${file} should transpile without syntax errors`);
    const exports = {};
    const commonjs = { exports };
    const localRequire = (name) => {
        if (name === 'react/jsx-runtime') return { jsx, jsxs: jsx };
        if (Object.hasOwn(mocks, name)) return mocks[name];
        throw new Error(`Unexpected import ${name} from ${file}`);
    };
    new Function('require', 'module', 'exports', result.outputText)(localRequire, commonjs, exports);
    return commonjs.exports;
}

function textFromElement(node) {
    if (node === null || node === undefined || typeof node === 'boolean') return '';
    if (Array.isArray(node)) return node.map(textFromElement).join(' ');
    if (typeof node === 'string' || typeof node === 'number') return String(node);
    if (typeof node !== 'object' || !Object.hasOwn(node, 'type')) {
        throw new Error('Non-renderable raw object found in React children');
    }
    if (typeof node.type === 'function') return textFromElement(node.type(node.props));
    return textFromElement(node.props.children);
}

const uiMock = {
    '@inertiajs/react': { Head: component('head') },
    '@admin/layout/AdminLayout': { AdminLayout: component('main') },
    '@admin/components/PageHeader': { PageHeader: ({ title, actions }) => jsx('header', { children: [title, actions] }) },
    '@nexora/admin-ui': { Card: component('section'), Badge: component('span') },
};

test('System Health never renders nested process runtime objects as React children', () => {
    const { default: Health } = compile('resources/js/admin/pages/Admin/System/Health.tsx', uiMock);
    const runtime = {
        php: '8.4.24', laravel: '13.33.0', environment: 'testing', database: 'sqlite',
        processPlane: {
            status: 'pass',
            required: { web: 2, queue: 0, scheduler: 1 },
            live: { counts: { web: 1, queue: 0, scheduler: 0 }, nodes: {}, rows: [], observed_at: '2026-10-15T00:00:00Z' },
            checks: { process_policy_enabled: true },
            policy: { materials: { queue: 'sync' } },
        },
    };
    const text = textFromElement(Health({ runtime, checks: [{ name: 'Database', status: 'healthy', message: 'Operational', durationMs: 0.7 }] }));
    assert.match(text, /System health/);
    assert.match(text, /Policy passing/);
    assert.match(text, /1\s+observed \/\s+2\s+required/);
    assert.match(text, /sqlite/);
});

test('System Health marks failed process policies as needing attention', () => {
    const { default: Health } = compile('resources/js/admin/pages/Admin/System/Health.tsx', uiMock);
    const text = textFromElement(Health({
        checks: [{ name: 'Database', status: 'healthy', message: 'Operational', durationMs: 0.7 }],
        runtime: { php: '8.4', laravel: '13', environment: 'local', database: 'sqlite',
            processPlane: { status: 'fail', required: { web: 2, queue: 2, scheduler: 1 }, live: { counts: { web: 0, queue: 0, scheduler: 0 } }, checks: {} },
        },
    }));
    assert.match(text, /Attention required/);
    assert.match(text, /Policy needs attention/);
});

test('Admin error boundary clears crash state on navigation and cleans up listener', () => {
    class Component {
        constructor(props) { this.props = props; this.state = {}; }
        setState(next) { this.state = { ...this.state, ...next }; }
    }
    const oldDocument = globalThis.document;
    const document = new EventTarget();
    globalThis.document = document;
    try {
        const { AppErrorBoundary } = compile('resources/js/admin/providers/AppErrorBoundary.tsx', {
            react: { Component },
            '@admin/components/LoadingStates': { ErrorState: ({ description }) => jsx('p', { children: description }) },
        });
        const boundary = new AppErrorBoundary({ children: jsx('div', { children: 'Admin recovered' }) });
        boundary.componentDidMount();
        boundary.setState(AppErrorBoundary.getDerivedStateFromError());
        assert.match(textFromElement(boundary.render()), /could not render/);
        document.dispatchEvent(new Event('inertia:navigate'));
        assert.match(textFromElement(boundary.render()), /Admin recovered/);
        boundary.setState({ failed: true });
        boundary.componentWillUnmount();
        document.dispatchEvent(new Event('inertia:navigate'));
        assert.equal(boundary.state.failed, true, 'unmounted boundary must not retain active listeners');
    } finally {
        globalThis.document = oldDocument;
    }
});
