/**
 * Exercise real Auth > Admin > System Health navigation in a disposable CI database.
 * A guarded seed script creates a temporary, random-password Super Admin; no live targets.
 * Usage: node tests/browser/admin-health-chromium-smoke.mjs http://127.0.0.1:8974/login
 */
import assert from "node:assert/strict";
import { spawn } from "node:child_process";
import { mkdtempSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { createServer } from "node:net";

const url = process.argv[2];
if (!/^http:\/\/127\.0\.0\.1:\d+\/login\/?$/.test(url || "")) {
    throw new Error("Provide the disposable loopback login origin.");
}
const email = process.env.NEXORA_CI_BROWSER_EMAIL;
const password = process.env.NEXORA_CI_BROWSER_PASSWORD;
if (process.env.NEXORA_CI_BROWSER_E2E !== "1" || !email || !password) {
    throw new Error("Disposable CI browser credentials and authorization are required.");
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
    async function waitFor(expression, description, attempts = 100) {
        for (let i = 0; i < attempts; i++) {
            if (await evalJs(expression)) return;
            if (child.exitCode !== null) break;
            await sleep(120);
        }
        const diagnostic = await evalJs("({url:location.pathname, title:document.title, ready:document.readyState, root:!!document.getElementById('app'), formCount:document.forms.length, inputs:Array.from(document.querySelectorAll('input')).map(i=>({name:i.name,type:i.type})), body:(document.body?.innerText||'').slice(0,350), scripts:Array.from(document.scripts).map(s=>s.src).filter(Boolean).slice(0,8)})");
        throw new Error("Timed out: " + description + "; browser=" + JSON.stringify(diagnostic) + "; errors=" + JSON.stringify(exceptions).slice(0,1200));
    }
    const navigation = await cdp("Page.navigate", { url });
    assert.ok(!navigation.errorText, "Could not navigate to test login page: " + navigation.errorText);
    await waitFor("document.readyState==='complete' && !!document.querySelector('input[name=email]') && !!document.querySelector('input[name=password]')",
        "React login form");
    // CDP typing fires native input events (unlike directly setting React-controlled input.value).
    await evalJs("document.querySelector('input[name=email]').focus()");
    await cdp("Input.insertText", { text: email });
    await evalJs("document.querySelector('input[name=password]').focus()");
    await cdp("Input.insertText", { text: password });
    const inputsPresent = await evalJs("document.querySelector('input[name=email]').value.length>0 && document.querySelector('input[name=password]').value.length>0");
    assert.equal(inputsPresent, true, "Login form failed to receive native keyboard input.");
    await evalJs("document.querySelector('button[type=submit]').click()");
    await waitFor("location.pathname.startsWith('/admin') && !!document.querySelector('nav[aria-label=\"Admin navigation\"]')",
        "authenticated admin dashboard", 150);

    const healthLink = await evalJs("!!document.querySelector('a[href=\"/admin/system/health\"]')");
    assert.equal(healthLink, true, "The authenticated sidebar is missing its System Health link.");
    await evalJs("document.querySelector('a[href=\"/admin/system/health\"]').click()");
    const healthyPage = [
        "location.pathname==='/admin/system/health'",
        "document.body.textContent.includes('System health')",
        "document.body.textContent.includes('Process runtime')",
        "!document.body.textContent.includes('The admin interface could not render this view.')",
    ].join(" && ");
    await waitFor(healthyPage, "Health page opened using in-app navigation", 150);

    await evalJs("history.back()");
    await waitFor("location.pathname==='/admin' || location.pathname==='/admin/'",
        "browser Back returns to Admin dashboard", 150);
    await evalJs("history.forward()");
    await waitFor(healthyPage, "browser Forward returns to System Health", 150);

    await cdp("Page.reload", { ignoreCache: true });
    await waitFor(healthyPage, "direct Health reload keeps the authorized session", 150);

    assert.deepEqual(exceptions, [], "Uncaught frontend exception on authenticated Health navigation.");
    console.log("PASS: real disposable Chromium login, Admin, System Health, Back/Forward, direct reload; no render exceptions.");
} finally {
    ws?.close();
    child.kill("SIGKILL");
    rmSync(temp, { recursive: true, force: true });
}
