# N1.0 RC21 — Laragon Frontend Type Contract Stabilization

RC21 is based on the real target `npm run build` error inventory. The target reported 76 TypeScript errors across 11 Admin files. The dominant failure classes were Inertia v3 form-data serialization constraints, `transform()` chaining, router payload typing, recursive Writer form values, and a shared `NavLink` API mismatch.

RC21 does not add product scope. It converts those target failures into permanent source contracts while preserving RC20 final-closure integrity.

## Contract

- Nested `useForm` data must be recursively convertible by Inertia; arbitrary `Record<string, unknown>` is not valid form state.
- Automation and Enterprise identity forms use the official `FormDataConvertible` boundary for dynamic nested configuration.
- Generic router helpers use the official `RequestPayload` type instead of arbitrary unknown records.
- Inertia v3 `form.transform()` is a void mutator: transform and submit are separate statements and chaining is prohibited.
- Writer form payload values remain recursively scalar/array/object serializable.
- Horizontal Helpdesk/Membership navigation uses shared `ButtonLink`; Untitled `NavLink` remains a label+icon navigation primitive.
- `scripts/inertia-frontend-contract-verify.php` runs before dependency-backed build certification and rejects known regression patterns.

The dependency-free gate cannot replace TypeScript. Exact PASS still requires the reviewed dependency locks followed by `npm ci` and the real `tsc --noEmit && vite build` on the target machine.
