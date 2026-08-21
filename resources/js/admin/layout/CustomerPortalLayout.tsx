import { router, usePage } from "@inertiajs/react";
import type { ReactNode } from "react";
import { Button, TextLink } from "@nexora/admin-ui";
import { LanguageSwitcher } from "@admin/components/LanguageSwitcher";
import { ThemeSwitcher } from "@admin/components/ThemeSwitcher";
import type { SharedPageProps } from "@admin/types/page";

export function CustomerPortalLayout({ children }: { children: ReactNode }) {
    const { app, auth, localization } = usePage<SharedPageProps>().props;
    const user = auth.user;

    return (
        <div className="min-h-screen bg-[var(--nx-bg)] text-[var(--nx-text)]">
            <header className="border-b border-[var(--nx-border)] bg-[var(--nx-surface)]/95 backdrop-blur">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                    <TextLink href="/account" tone="neutral" className="flex min-w-0 items-center gap-3 rounded-xl no-underline hover:no-underline">
                        <img src={app.logoUrl} alt="" className="h-10 w-10 rounded-xl object-contain" loading="lazy" decoding="async" />
                        <span className="min-w-0">
                            <span className="block truncate text-sm font-semibold">{app.name}</span>
                            <span className="block text-xs font-normal text-[var(--nx-text-muted)]">Customer portal</span>
                        </span>
                    </TextLink>
                    <div className="flex flex-wrap items-center justify-end gap-2">
                        <TextLink href="/" tone="neutral" className="rounded-lg px-3 py-2 text-sm font-medium">View site</TextLink>
                        {user?.permissions.includes("admin.access") && <TextLink href="/admin" tone="neutral" className="rounded-lg px-3 py-2 text-sm font-medium">Admin</TextLink>}
                        <ThemeSwitcher />
                        <LanguageSwitcher localization={localization} />
                        <Button size="sm" variant="secondary" onClick={() => router.post("/logout")}>Sign out</Button>
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-10">
                <div className="mb-8">
                    <p className="text-sm font-medium text-[var(--nx-text-muted)]">Signed in as {user?.email ?? "account"}</p>
                    <h1 className="mt-2 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Welcome, {user?.name ?? "customer"}</h1>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-[var(--nx-text-muted)]">Your membership and Commerce history for the current organization, without exposing administrative controls.</p>
                </div>
                {children}
            </main>
        </div>
    );
}
