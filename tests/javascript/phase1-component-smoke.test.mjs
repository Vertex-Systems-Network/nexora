import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { test } from "node:test";

// The React 19 / Vitest component suites exercise rendering and navigation.
// These independent Node source contracts catch accidental regressions without
// depending on TypeScript's non-stable CommonJS runtime export layout.
const health = readFileSync(new URL("../../resources/js/admin/pages/Admin/System/Health.tsx", import.meta.url), "utf8");
const boundary = readFileSync(new URL("../../resources/js/admin/providers/AppErrorBoundary.tsx", import.meta.url), "utf8");

test("System Health renders scalar runtime fields through an explicit allowlist", () => {
  assert.match(health, /const scalarRuntime = \[/);
  assert.match(health, /scalarRuntime\.map\(/);
  assert.doesNotMatch(health, /Object\.entries\(runtime\)/);
});

test("System Health treats process-plane policy as a separate nested status", () => {
  assert.match(health, /processPlane\.status === ["']pass["']/);
  assert.match(health, /processRoles\.map\(/);
  assert.match(health, /Policy needs attention/);
});

test("Admin error boundary resets on navigation and removes its listener", () => {
  assert.match(boundary, /addEventListener\(["']inertia:navigate["']/);
  assert.match(boundary, /removeEventListener\(["']inertia:navigate["']/);
  assert.match(boundary, /if \(this\.state\.failed\) this\.setState\(\{ failed: false \}\)/);
});
