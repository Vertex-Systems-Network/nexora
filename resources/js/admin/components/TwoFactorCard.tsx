import { useForm } from "@inertiajs/react";
import { Badge, Button, Card, Input } from "@nexora/admin-ui";

export type TwoFactorState = {
    enabled: boolean;
    pending: boolean;
    setupKey: string | null;
    otpauthUri: string | null;
    recoveryRemaining: number;
};

/** Self-service TOTP enrolment (SECURITY-BASELINE-200 / SYS-MFA-TOTP). */
export function TwoFactorCard({ state, recoveryCodes }: { state: TwoFactorState; recoveryCodes?: string[] | null }) {
    const passwordForm = useForm({ current_password: "", code: "" });
    const confirmForm = useForm({ code: "" });
    const opts = { preserveScroll: true, onSuccess: () => passwordForm.reset() };

    return (
        <Card className="p-5 sm:p-6">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-[var(--nx-text)]">Two-factor authentication</h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        Require a code from an authenticator app (Google Authenticator, 1Password, Authy…) in addition to your password, on the web and in the mobile apps.
                    </p>
                </div>
                <Badge tone={state.enabled ? "success" : state.pending ? "warning" : "neutral"}>
                    {state.enabled ? "On" : state.pending ? "Setup pending" : "Off"}
                </Badge>
            </div>

            {recoveryCodes && recoveryCodes.length > 0 && (
                <div className="mt-5 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                    <p className="text-sm font-semibold text-[var(--nx-text)]">Recovery codes — shown once</p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">Store these somewhere safe. Each code signs you in once if you lose your device.</p>
                    <ul className="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-[var(--nx-text)]">
                        {recoveryCodes.map((code) => <li key={code}>{code}</li>)}
                    </ul>
                </div>
            )}

            {state.pending && state.setupKey && (
                <div className="mt-5 grid gap-4">
                    <div className="rounded-xl border border-[var(--nx-border)] p-4">
                        <p className="text-sm font-medium text-[var(--nx-text)]">1. Add this key to your authenticator app</p>
                        <p className="mt-2 break-all font-mono text-sm tracking-wider text-[var(--nx-text)]">{state.setupKey.replace(/(.{4})/g, "$1 ").trim()}</p>
                        {state.otpauthUri && (
                            <a className="mt-2 inline-block text-xs font-medium text-[var(--nx-brand-600)] hover:underline" href={state.otpauthUri}>
                                Open in authenticator app
                            </a>
                        )}
                    </div>
                    <form
                        className="grid gap-3"
                        onSubmit={(event) => {
                            event.preventDefault();
                            confirmForm.post("/admin/profile/two-factor/confirm", { preserveScroll: true, onSuccess: () => confirmForm.reset() });
                        }}
                    >
                        <Input
                            label="2. Enter the 6-digit code to confirm"
                            value={confirmForm.data.code}
                            onChange={(event) => confirmForm.setData("code", event.target.value)}
                            error={confirmForm.errors.code}
                            inputMode="numeric"
                            autoComplete="one-time-code"
                        />
                        <div className="flex justify-end">
                            <Button type="submit" loading={confirmForm.processing}>Confirm and turn on</Button>
                        </div>
                    </form>
                </div>
            )}

            <form className="mt-5 grid gap-3" onSubmit={(event) => event.preventDefault()}>
                <Input
                    label="Current password"
                    type="password"
                    value={passwordForm.data.current_password}
                    onChange={(event) => passwordForm.setData("current_password", event.target.value)}
                    error={passwordForm.errors.current_password}
                    hint={state.enabled ? `${state.recoveryRemaining} recovery codes remaining.` : "Required to change two-factor settings."}
                />
                {state.enabled && (
                    <Input
                        label="Authenticator or recovery code (required to turn off)"
                        value={passwordForm.data.code}
                        onChange={(event) => passwordForm.setData("code", event.target.value)}
                        error={passwordForm.errors.code}
                        autoComplete="one-time-code"
                    />
                )}
                <div className="flex flex-wrap justify-end gap-2">
                    {state.enabled ? (
                        <>
                            <Button variant="secondary" loading={passwordForm.processing} onClick={() => passwordForm.post("/admin/profile/two-factor/recovery-codes", opts)}>
                                New recovery codes
                            </Button>
                            <Button variant="danger" loading={passwordForm.processing} onClick={() => passwordForm.delete("/admin/profile/two-factor", opts)}>
                                Turn off
                            </Button>
                        </>
                    ) : (
                        <>
                            {state.pending && (
                                <Button variant="ghost" loading={passwordForm.processing} onClick={() => passwordForm.delete("/admin/profile/two-factor", opts)}>
                                    Cancel setup
                                </Button>
                            )}
                            <Button loading={passwordForm.processing} onClick={() => passwordForm.post("/admin/profile/two-factor", opts)}>
                                {state.pending ? "Restart setup" : "Set up two-factor"}
                            </Button>
                        </>
                    )}
                </div>
            </form>
        </Card>
    );
}
