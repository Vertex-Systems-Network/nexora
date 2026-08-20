import { Head, useForm } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Input } from "@nexora/admin-ui";

export default function ResetPassword({ email, token }: { email: string; token: string }) {
    const form = useForm({ email, token, password: "", password_confirmation: "" });
    return (
        <AuthLayout title="Choose a new password" description="Use a strong password you do not reuse on other services.">
            <Head title="Reset password" />
            <form className="grid gap-5" onSubmit={(event) => { event.preventDefault(); form.post("/reset-password"); }}>
                <Input label="Email" name="email" type="email" value={form.data.email} onChange={(e) => form.setData("email", e.target.value)} error={form.errors.email} />
                <Input label="New password" name="password" type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData("password", e.target.value)} error={form.errors.password} />
                <Input label="Confirm password" name="password_confirmation" type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData("password_confirmation", e.target.value)} />
                <Button type="submit" size="lg" loading={form.processing}>Reset password</Button>
            </form>
        </AuthLayout>
    );
}
