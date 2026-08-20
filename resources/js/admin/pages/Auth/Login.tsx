import { Head, useForm } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Checkbox, Input, TextLink } from "@nexora/admin-ui";

export default function Login({ canResetPassword, status }: { canResetPassword: boolean; status?: string | null }) {
    const form = useForm({ email: "", password: "", remember: false });
    return (
        <AuthLayout title="Welcome back" description="Sign in to the Nexora administration workspace.">
            <Head title="Sign in" />
            {status && <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300">{status}</div>}
            <form className="grid gap-5" onSubmit={(event) => { event.preventDefault(); form.post("/login"); }}>
                <Input label="Email" name="email" type="email" autoComplete="email" autoFocus value={form.data.email} onChange={(e) => form.setData("email", e.target.value)} error={form.errors.email} placeholder="you@example.com" />
                <Input label="Password" name="password" type="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData("password", e.target.value)} error={form.errors.password} />
                <div className="flex items-center justify-between gap-4 text-sm">
                    <Checkbox checked={form.data.remember} onChange={(e) => form.setData("remember", e.target.checked)} label="Remember me" />
                    {canResetPassword && <TextLink href="/forgot-password">Forgot password?</TextLink>}
                </div>
                <Button type="submit" size="lg" loading={form.processing}>Sign in</Button>
            </form>
            <p className="mt-6 text-center text-sm text-[var(--nx-text-muted)]">New to Nexora? <TextLink href="/register">Create account</TextLink></p>
        </AuthLayout>
    );
}
