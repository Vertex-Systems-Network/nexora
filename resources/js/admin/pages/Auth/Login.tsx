import { useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Checkbox, Input, TextLink } from "@nexora/admin-ui";
import { credentialToJson, firstError, passkeysSupported, postJson, toRequestOptions } from "@admin/utils/passkeys";

type SsoContext = {
    organization: { id: string; name: string; slug: string } | null;
    required: boolean;
    providers: Array<{ name: string; slug: string; protocol: string; href: string }>;
};

type Props = {
    canResetPassword: boolean;
    status?: string | null;
    sso: SsoContext;
};

export default function Login({ canResetPassword, status, sso }: Props) {
    const form = useForm({ email: "", password: "", remember: false });
    const [passkeyBusy, setPasskeyBusy] = useState(false);
    const [passkeyError, setPasskeyError] = useState<string | null>(null);

    // SECURITY-BASELINE-200 / SYS-MFA-PASSKEY: discoverable-credential sign-in; the device chooses the account.
    const signInWithPasskey = async () => {
        setPasskeyError(null);
        setPasskeyBusy(true);
        try {
            const options = await postJson("/passkeys/login/options");
            if (!options.ok) return setPasskeyError(firstError(options.data, "Passkey sign-in is unavailable right now."));
            const credential = (await navigator.credentials.get({
                publicKey: toRequestOptions(options.data.publicKey as Record<string, unknown>),
            })) as PublicKeyCredential | null;
            if (!credential) return setPasskeyError("No passkey was selected.");
            const result = await postJson("/passkeys/login", { credential: credentialToJson(credential) });
            if (!result.ok || typeof result.data.redirect !== "string") {
                return setPasskeyError(firstError(result.data, "That passkey could not be verified."));
            }
            window.location.assign(result.data.redirect);
        } catch (exception) {
            setPasskeyError(exception instanceof DOMException && exception.name === "NotAllowedError" ? "Passkey sign-in was cancelled." : "Your browser could not use a passkey.");
        } finally {
            setPasskeyBusy(false);
        }
    };

    return (
        <AuthLayout title="Welcome back" description="Sign in to the Nexora administration workspace.">
            <Head title="Sign in" />

            {status && (
                <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300">
                    {status}
                </div>
            )}

            {sso.organization && (sso.providers.length > 0 || sso.required) && (
                <div className="mb-5 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                    <p className="text-sm font-semibold text-[var(--nx-text)]">
                        {sso.organization.name} organization SSO
                    </p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                        {sso.required
                            ? "SSO is required for organization members. Super Admin recovery access remains available."
                            : "You can use an organization identity provider instead of a local password."}
                    </p>

                    {sso.providers.length > 0 ? (
                        <div className="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm">
                            {sso.providers.map((provider) => (
                                <TextLink key={provider.slug} href={provider.href}>
                                    Continue with {provider.name}
                                </TextLink>
                            ))}
                        </div>
                    ) : (
                        <p className="mt-3 text-sm font-medium text-[var(--nx-danger)]">
                            No compatible SSO adapter is currently available. Contact an organization administrator.
                        </p>
                    )}
                </div>
            )}

            <form
                className="grid gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post("/login");
                }}
            >
                <Input
                    label="Email"
                    name="email"
                    type="email"
                    autoComplete="email"
                    autoFocus
                    value={form.data.email}
                    onChange={(event) => form.setData("email", event.target.value)}
                    error={form.errors.email}
                    placeholder="you@example.com"
                />
                <Input
                    label="Password"
                    name="password"
                    type="password"
                    autoComplete="current-password"
                    value={form.data.password}
                    onChange={(event) => form.setData("password", event.target.value)}
                    error={form.errors.password}
                />
                <div className="flex items-center justify-between gap-4 text-sm">
                    <Checkbox
                        checked={form.data.remember}
                        onChange={(event) => form.setData("remember", event.target.checked)}
                        label="Remember me"
                    />
                    {canResetPassword && <TextLink href="/forgot-password">Forgot password?</TextLink>}
                </div>
                <Button type="submit" size="lg" loading={form.processing}>Sign in</Button>
            </form>

            {passkeysSupported() && (
                <div className="mt-4 grid gap-2">
                    <Button type="button" variant="secondary" size="lg" loading={passkeyBusy} onClick={() => void signInWithPasskey()}>
                        Sign in with a passkey
                    </Button>
                    {passkeyError && <p className="text-center text-sm font-medium text-[var(--nx-danger)]">{passkeyError}</p>}
                </div>
            )}

            <p className="mt-6 text-center text-sm text-[var(--nx-text-muted)]">
                New to Nexora? <TextLink href="/register">Create account</TextLink>
            </p>
        </AuthLayout>
    );
}
