/**
 * Verify the real, disposable /install page with Chromium and zero third-party JS dependencies.
 * Usage: node tests/browser/installer-chromium-smoke.mjs http://127.0.0.1:8973/install
 */
import assert from "node:assert/strict";
import { spawn } from "node:child_process";
import { mkdtempSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { createServer } from "node:net";

const url = process.argv[2];
if (!/^http:\/\/127\.0\.0\.1:\d+\/install\/?$/.test(url || "")) {
    throw new Error("Provide the disposable loopback installer origin.");
}
const chrome = process.env.CHROME_PATH || "google-chrome";
const temp = mkdtempSync(join(tmpdir(), "nexora-browser-"));
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
// Chrome versions and runner images differ in whether they write
// DevToolsActivePort for port=0. Reserve an explicit disposable loopback port.
const port = await new Promise((resolve, reject) => {
    const server = createServer();
    server.once("error", reject);
    server.listen(0, "127.0.0.1", () => {
        const address = server.address();
        server.close((error) => error ? reject(error) : resolve(address.port));
    });
});
const child = spawn(chrome, [
    "--headless=new", "--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage",
    "--no-first-run", "--no-default-browser-check", "--disable-background-networking",
    "--disable-extensions", "--remote-debugging-port=" + port,
    "--user-data-dir=" + temp, "about:blank",
], { stdio: ["ignore", "ignore", "pipe"] });
const diagnostics = [];
let startError = null;
child.on("error", (error) => { startError = error; });
child.stderr?.on("data", (chunk) => {
    diagnostics.push(chunk.toString("utf8"));
    if (diagnostics.length > 100) diagnostics.shift();
});
let ws;
try {
    let pages;
    for (let i = 0; i < 200; i++) {
        if (startError || child.exitCode !== null) break;
        try {
            const response = await fetch("http://127.0.0.1:" + port + "/json/list", {
                signal: AbortSignal.timeout(700),
            });
            if (response.ok) {
                pages = await response.json();
                break;
            }
        } catch { /* Chrome still starting. */ }
        await sleep(100);
    }
    assert.ok(pages?.length,
        "Chromium debugging endpoint unavailable. Chrome: " + chrome
        + "; exit=" + child.exitCode + "; spawn=" + (startError?.message || "none")
        + "; stderr=" + diagnostics.join("").slice(-2800));
    const page = pages.find((target) => target.type === "page");
    assert.ok(page?.webSocketDebuggerUrl, "No Chromium page target.");
    ws = new WebSocket(page.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        ws.addEventListener("open", resolve, { once: true });
        ws.addEventListener("error", reject, { once: true });
    });

    let sequence = 0;
    const pending = new Map();
    const exceptions = [];
    ws.addEventListener("message", (event) => {
        const message = JSON.parse(event.data);
        if (message.method === "Runtime.exceptionThrown") {
            exceptions.push(message.params?.exceptionDetails?.text || "unknown JavaScript exception");
        }
        if (!message.id) return;
        const entry = pending.get(message.id);
        if (!entry) return;
        pending.delete(message.id);
        if (message.error) entry.reject(new Error(message.error.message));
        else entry.resolve(message.result || {});
    });
    function cdp(method, params = {}) {
        return new Promise((resolve, reject) => {
            const id = ++sequence;
            const timeout = setTimeout(() => {
                pending.delete(id);
                reject(new Error("CDP timeout: " + method));
            }, 12000);
            pending.set(id, {
                resolve(result) { clearTimeout(timeout); resolve(result); },
                reject(error) { clearTimeout(timeout); reject(error); },
            });
            ws.send(JSON.stringify({ id, method, params }));
        });
    }
    async function evalJs(expression) {
        const result = await cdp("Runtime.evaluate", { expression, returnByValue: true, awaitPromise: true });
        if (result.exceptionDetails) throw new Error("Browser expression failure: " + result.exceptionDetails.text);
        return result.result?.value;
    }

    await cdp("Page.enable");
    await cdp("Runtime.enable");
    const navigation = await cdp("Page.navigate", { url });
    assert.ok(!navigation.errorText, "Browser navigation failed: " + navigation.errorText);
    let ready = false;
    for (let i = 0; i < 100; i++) {
        ready = await evalJs("document.readyState === 'complete' && !!document.querySelector('#admin_password')");
        if (ready) break;
        await sleep(100);
    }
    assert.equal(ready, true, "Installer password control did not render.");

    for (const width of [320, 390, 768, 1440]) {
        await cdp("Emulation.setDeviceMetricsOverride", { width, height: 850, deviceScaleFactor: 1, mobile: false });
        const dimensions = await evalJs("({ width: innerWidth, scroll: document.documentElement.scrollWidth })");
        if (dimensions.scroll > dimensions.width + 1) {
            const offenders = await evalJs("Array.from(document.querySelectorAll('body *')).filter(e => { const r=e.getBoundingClientRect(); return r.width > 0 && r.right > innerWidth + 1; }).slice(0,12).map(e => ({ tag:e.tagName, className:String(e.className), right:Math.round(e.getBoundingClientRect().right) }))");
            assert.fail("Installer horizontal overflow at width " + width + ": " + JSON.stringify({ ...dimensions, offenders }));
        }
    }

    const script = [
        "(() => {",
        "document.querySelector('[data-theme-choice=\"dark\"]').click();",
        "const input=document.getElementById('admin_password');",
        "const dark=document.documentElement.dataset.theme==='dark';",
        "const control=getComputedStyle(input);",
        "const readable=control.color!==control.backgroundColor;",
        "document.querySelectorAll('.wizard-step').forEach(step=>step.classList.toggle('active',step.dataset.step==='2'));",
        "input.value='WeakPass11!';input.dispatchEvent(new Event('input',{bubbles:true}));",
        "const weak=document.getElementById('password-level').textContent;",
        "input.value='V7!qR2#pL9@mX4$z';input.dispatchEvent(new Event('input',{bubbles:true}));",
        "return {dark,readable,weak,strong:document.getElementById('password-level').textContent,",
        "meter:document.getElementById('password-strength-track').getAttribute('aria-valuenow')};",
        "})()",
    ].join("\n");
    const result = await evalJs(script);
    assert.equal(result.dark, true, "Dark theme did not activate.");
    assert.equal(result.readable, true, "Dark theme input contrast failed.");
    assert.equal(result.weak, "Weak", "Weak password feedback failed.");
    assert.equal(result.strong, "Strong", "Strong password feedback failed.");
    assert.equal(result.meter, "100", "Password meter did not update.");
    assert.deepEqual(exceptions, [], "Installer had browser JavaScript errors.");
    console.log("PASS: real Chromium /install, widths 320/390/768/1440, dark theme, Weak-to-Strong meter, no page errors.");
} finally {
    ws?.close();
    child.kill("SIGKILL");
    // Chromium may still write to its disposable profile after SIGKILL.
    // Preserve the browser assertion result instead of masking it with ENOTEMPTY.
    try {
        rmSync(temp, { recursive: true, force: true, maxRetries: 12, retryDelay: 125 });
    } catch (cleanupError) {
        console.warn("Disposable Chromium profile cleanup incomplete:", cleanupError.code || "unknown");
    }
}
