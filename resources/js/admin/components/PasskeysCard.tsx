import { useState } from "react";
import { router } from "@inertiajs/react";
import { Badge, Button, Card, Input } from "@nexora/admin-ui";
import { credentialToJson, firstError, passkeysSupported, postJson, toCreationOptions } from "@admin/utils/passkeys";

export type PasskeySummary = { id: number; name: string; createdAt: string | null; lastUsedAt: string | null };

/** Self-service passkeys (SECURITY-BASELINE-200 / SYS-MFA-PASSKEY). Adding or removing one re-proves the password. */
export function PasskeysCard({ passkeys, max, requireCode = false }: { passkeys: PasskeySummary[]; max: number; requireCode?: boolean }) {
    const [password, setPassword] = useState("");
    const [code, setCode] = useState("");
    const [name, setName] = useState("");
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);
    const supported = passkeysSupported();

    const add = async () => {
        setError(null);
        setBusy(true);
        try {
            const options = await postJson("/admin/profile/passkeys/options", requireCode ? { current_password: password, code } : { current_password: password });
            if (!options.ok) return setError(firstError(options.data, "Could not start passkey setup."));
            const credential = (await navigator.credentials.create({
                publicKey: toCreationOptions(options.data.publicKey as Record<string, unknown>),
            })) as PublicKeyCredential | null;
            if (!credential) return setError("No passkey was created.");
            router.post(
                "/admin/profile/passkeys",
                { name: name.trim() || "Passkey", credential: credentialToJson(credential) },
                { preserveScroll: true, onSuccess: () => { setPassword(""); setCode(""); setName(""); }, onError: (errors) => setError(Object.values(errors)[0] ?? "Could not add passkey.") },
            );
        } catch (exception) {
            // NotAllowedError = the user dismissed the device prompt; nothing was stored.
            setError(exception instanceof DOMException && exception.name === "NotAllowedError" ? "Passkey setup was cancelled." : "Your browser could not create a passkey.");
        } finally {
            setBusy(false);
        }
    };

    const remove = (id: number) => {
        setError(null);
        router.delete(`/admin/profile/passkeys/${id}`, {
            data: { current_password: password },
            preserveScroll: true,
            onError: (errors) => setError(Object.values(errors)[0] ?? "Could not remove passkey."),
        });
    };

    return (
        <Card className="p-5 sm:p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-[var(--nx-text)]">Passkeys</h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        Sign in with your fingerprint, face or device PIN instead of a password. Passkeys cannot be phished or reused on other sites.
                    </p>
                </div>
                <Badge tone={passkeys.length > 0 ? "success" : "neutral"}>{passkeys.length > 0 ? `${passkeys.length} added` : "None"}</Badge>
            </div>

            {passkeys.length > 0 && (
                <ul className="mt-5 divide-y divide-[var(--nx-border)] rounded-xl border border-[var(--nx-border)]">
                    {passkeys.map((passkey) => (
                        <li key={passkey.id} className="flex items-center justify-between gap-4 p-4">
                            <div className="min-w-0">
                                <p className="truncate text-sm font-medium text-[var(--nx-text)]">{passkey.name}</p>
                                <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                    Added {passkey.createdAt ? new Date(passkey.createdAt).toLocaleDateString() : "—"} · Last used{" "}
                                    {passkey.lastUsedAt ? new Date(passkey.lastUsedAt).toLocaleString() : "never"}
                                </p>
                            </div>
                            <Button type="button" variant="secondary" size="sm" disabled={!password} onClick={() => remove(passkey.id)}>
                                Remove
                            </Button>
                        </li>
                    ))}
                </ul>
            )}

            {!supported ? (
                <p className="mt-5 text-sm text-[var(--nx-text-muted)]">This browser does not support passkeys.</p>
            ) : (
                <form className="mt-5 grid gap-3" onSubmit={(event) => { event.preventDefault(); void add(); }}>
                    <Input label="Current password" type="password" autoComplete="current-password" value={password} onChange={(event) => setPassword(event.target.value)} />
                    {requireCode && (
                        <Input
                            label="Authenticator or recovery code"
                            hint="The code is used when setup starts. A recovery code used here is spent even if you cancel the device prompt."
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            value={code}
                            onChange={(event) => setCode(event.target.value)}
                        />
                    )}
                    <Input label="Passkey name" placeholder="e.g. Work laptop" maxLength={120} value={name} onChange={(event) => setName(event.target.value)} />
                    {error && <p className="text-sm font-medium text-[var(--nx-danger)]">{error}</p>}
                    <div className="flex justify-end">
                        <Button type="submit" loading={busy} disabled={!password || (requireCode && !code) || passkeys.length >= max}>Add a passkey</Button>
                    </div>
                </form>
            )}
        </Card>
    );
}
