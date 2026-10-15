/**
 * Verify the real, disposable /install page with Chromium and zero third-party JS dependencies.
 * Usage: node tests/browser/installer-chromium-smoke.mjs http://127.0.0.1:8973/install
 */
import assert from "node:assert/strict";
import { spawn } from "node:child_process";
import { existsSync, mkdtempSync, readFileSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const url = process.argv[2];
if (!/^http:\/\/127\.0\.0\.1:\d+\/install\/?$/.test(url || "")) {
    throw new Error("Provide the disposable loopback installer origin.");
}
const chrome = process.env.CHROME_PATH || "google-chrome";
const temp = mkdtempSync(join(tmpdir(), "nexora-browser-"));
const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const child = spawn(chrome, [
    "--headless=new", "--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage",
    "--no-first-run", "--remote-debugging-port=0",
    "--user-data-dir=" + temp, "about:blank",
], { stdio: "ignore" });
let ws;
try {
    let port;
    for (let i = 0; i < 80; i++) {
        const info = join(temp, "DevToolsActivePort");
        if (existsSync(info)) {
            port = Number(readFileSync(info, "utf8").split("\n")[0]);
            break;
        }
        if (child.exitCode !== null) throw new Error("Chromium exited before CDP became available.");
        await sleep(100);
    }
    assert.ok(port, "Chromium debugger unavailable.");
    const pages = await fetch("http://127.0.0.1:" + port + "/json/list").then((r) => r.json());
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
        assert.ok(dimensions.scroll <= dimensions.width + 1,
            "Installer horizontal overflow at width " + width + ": " + JSON.stringify(dimensions));
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
    rmSync(temp, { recursive: true, force: true });
}
