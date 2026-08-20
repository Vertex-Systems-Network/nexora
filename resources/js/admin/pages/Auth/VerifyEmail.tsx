import { Head, router } from "@inertiajs/react";
import { AuthLayout } from "@admin/layout/AuthLayout";
import { Button } from "@nexora/admin-ui";

export default function VerifyEmail({ status }: { status?: string | null }) {
    return (
        <AuthLayout title="Verify your email" description="Open the verification link we sent to your inbox before entering protected Nexora areas.">
            <Head title="Verify email" />
            {status === "verification-link-sent" && <div className="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">A new verification link has been sent.</div>}
            <div className="grid gap-3">
                <Button size="lg" onClick={() => router.post("/email/verification-notification")}>Resend verification email</Button>
                <Button size="lg" variant="secondary" onClick={() => router.post("/logout")}>Sign out</Button>
            </div>
        </AuthLayout>
    );
}
