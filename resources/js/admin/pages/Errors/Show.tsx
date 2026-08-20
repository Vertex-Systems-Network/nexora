import { Head, router } from "@inertiajs/react";
import { Button, ButtonLink, Card } from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";

type ErrorPayload = { status:number; code:string; title:string; message:string; request_id:string };

export default function ErrorPage({ error }: { error: ErrorPayload }) {
    const home = error.status === 401 ? "/login" : "/admin";
    return (
        <main className="grid min-h-screen place-items-center bg-[var(--nx-bg)] px-5 py-10">
            <Head title={`${error.status} · ${error.title}`} />
            <Card className="w-full max-w-xl p-7 sm:p-9">
                <div className="grid h-12 w-12 place-items-center rounded-2xl bg-[var(--nx-surface-subtle)] text-[var(--nx-text-secondary)]">
                    <Icon name={error.status >= 500 ? "error" : "alert"} className="h-6 w-6" />
                </div>
                <p className="mt-6 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--nx-text-muted)]">{error.code}</p>
                <h1 className="mt-2 text-2xl font-semibold tracking-tight text-[var(--nx-text)]">{error.title}</h1>
                <p className="mt-3 text-sm leading-6 text-[var(--nx-text-secondary)]">{error.message}</p>
                <div className="mt-5 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] px-4 py-3 text-xs text-[var(--nx-text-muted)]">
                    Request reference: <code className="font-semibold text-[var(--nx-text)]">{error.request_id}</code>
                </div>
                <div className="mt-6 flex flex-wrap gap-3">
                    <Button onClick={() => router.reload()} leadingIcon={<Icon name="refresh" className="h-4 w-4" />}>Retry</Button>
                    <ButtonLink href={home} variant="secondary">{error.status === 401 ? "Sign in" : "Back to Admin"}</ButtonLink>
                </div>
            </Card>
        </main>
    );
}
