import { Head, useForm } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Input, TextLink } from "@nexora/admin-ui";

export default function ForgotPassword({ status }: { status?: string | null }) {
    const form = useForm({ email: "" });
    return (
        <AuthLayout title="Reset your password" description="Enter your account email and Nexora will send a secure password reset link.">
            <Head title="Forgot password" />
            {status && <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">{status}</div>}
            <form className="grid gap-5" onSubmit={(event) => { event.preventDefault(); form.post("/forgot-password"); }}>
                <Input label="Email" name="email" type="email" autoComplete="email" autoFocus value={form.data.email} onChange={(e) => form.setData("email", e.target.value)} error={form.errors.email} />
                <Button type="submit" size="lg" loading={form.processing}>Send reset link</Button>
            </form>
            <p className="mt-6 text-center text-sm"><TextLink href="/login">Back to sign in</TextLink></p>
        </AuthLayout>
    );
}
