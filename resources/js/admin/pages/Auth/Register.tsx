import { Head, useForm } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Input, TextLink } from "@nexora/admin-ui";

export default function Register() {
    const form = useForm({ name: "", email: "", password: "", password_confirmation: "" });
    return (
        <AuthLayout title="Create your account" description="Start with a secure Nexora identity. Admin access is granted separately through roles.">
            <Head title="Register" />
            <form className="grid gap-5" onSubmit={(event) => { event.preventDefault(); form.post("/register"); }}>
                <Input label="Full name" name="name" autoComplete="name" value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} error={form.errors.name} />
                <Input label="Email" name="email" type="email" autoComplete="email" value={form.data.email} onChange={(e) => form.setData("email", e.target.value)} error={form.errors.email} />
                <Input label="Password" name="password" type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData("password", e.target.value)} error={form.errors.password} />
                <Input label="Confirm password" name="password_confirmation" type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData("password_confirmation", e.target.value)} />
                <Button type="submit" size="lg" loading={form.processing}>Create account</Button>
            </form>
            <p className="mt-6 text-center text-sm text-[var(--nx-text-muted)]">Already registered? <TextLink href="/login">Sign in</TextLink></p>
        </AuthLayout>
    );
}
