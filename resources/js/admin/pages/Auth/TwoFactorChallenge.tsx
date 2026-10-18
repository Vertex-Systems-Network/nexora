import { Head, useForm } from "@inertiajs/react";
import { useState } from "react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button, Input, TextLink } from "@nexora/admin-ui";

export default function TwoFactorChallenge() {
    const [recovery, setRecovery] = useState(false);
    const form = useForm({ code: "" });

    return (
        <AuthLayout
            title="Two-factor verification"
            description={recovery ? "Enter one of your saved recovery codes. Each code works once." : "Enter the 6-digit code from your authenticator app."}
        >
            <Head title="Two-factor verification" />
            <form
                className="grid gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post("/two-factor-challenge", { onFinish: () => form.reset("code") });
                }}
            >
                <Input
                    label={recovery ? "Recovery code" : "Authentication code"}
                    value={form.data.code}
                    onChange={(event) => form.setData("code", event.target.value)}
                    error={form.errors.code}
                    autoFocus
                    autoComplete="one-time-code"
                    inputMode={recovery ? "text" : "numeric"}
                    placeholder={recovery ? "abcde-12345" : "123456"}
                />
                <Button type="submit" size="lg" className="w-full" loading={form.processing}>
                    Verify and sign in
                </Button>
            </form>
            <div className="mt-6 flex items-center justify-between text-sm">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => {
                        setRecovery((value) => !value);
                        form.reset("code");
                        form.clearErrors();
                    }}
                >
                    {recovery ? "Use authenticator code" : "Use a recovery code"}
                </Button>
                <TextLink href="/login">Back to sign in</TextLink>
            </div>
        </AuthLayout>
    );
}
