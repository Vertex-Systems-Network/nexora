import { useEffect, type ReactNode } from "react";
import { usePage } from "@inertiajs/react";
import type { SharedPageProps } from "@admin/types/page";
import { LanguageSwitcher } from "@admin/components/LanguageSwitcher";
import { ThemeSwitcher } from "@admin/components/ThemeSwitcher";

export function AuthLayout({ children, title, description }: { children: ReactNode; title: string; description: string }) {
    const props = usePage<SharedPageProps>().props;
    const { app, localization } = props;
    useEffect(() => { document.documentElement.lang = localization.locale; document.documentElement.dir = localization.direction; }, [localization.locale, localization.direction]);

    return (
        <main className="grid min-h-screen bg-[var(--nx-bg)] lg:grid-cols-[minmax(0,1fr)_minmax(30rem,0.9fr)]">
            <section className="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
                <div className="w-full max-w-md">
                    <div className="mb-10 flex items-center justify-between gap-3">
                        <div className="flex min-w-0 items-center gap-3"><img src="/brand/nexora-mark.svg" alt="" className="h-10 w-10 rounded-xl shadow-sm" /><div className="min-w-0"><p className="truncate text-sm font-semibold text-[var(--nx-text)]">{app.name}</p><p className="text-xs text-[var(--nx-text-muted)]">Secure modular platform</p></div></div>
                        <div className="flex items-center gap-2"><ThemeSwitcher /><LanguageSwitcher localization={localization} /></div>
                    </div>
                    <div className="mb-7">
                        <h1 className="text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{title}</h1>
                        <p className="mt-3 text-sm leading-6 text-[var(--nx-text-muted)]">{description}</p>
                    </div>
                    {children}
                    <p className="mt-10 text-xs leading-5 text-[var(--nx-text-muted)]">Protected by Nexora foundation security controls. Activity may be audited.</p>
                </div>
            </section>
            <aside className="relative hidden overflow-hidden border-s border-[var(--nx-border)] bg-[#0c0d12] lg:block" aria-hidden="true">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_20%_15%,rgba(124,58,237,.35),transparent_34%),radial-gradient(circle_at_80%_75%,rgba(59,130,246,.18),transparent_30%)]" />
                <div className="absolute inset-0 opacity-30 [background-image:linear-gradient(rgba(255,255,255,.06)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.06)_1px,transparent_1px)] [background-size:48px_48px]" />
                <div className="relative flex h-full items-end p-12">
                    <div className="max-w-xl">
                        <div className="mb-5 inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-white/70 backdrop-blur">Nexora Platform</div>
                        <p className="text-4xl font-semibold leading-tight tracking-[-0.05em] text-white">Built to stay fast, extensible and secure as Nexora grows.</p>
                        <p className="mt-5 max-w-lg text-sm leading-6 text-white/55">Premium administration, predictable extension contracts and zero-trust security boundaries start at the foundation.</p>
                    </div>
                </div>
            </aside>
        </main>
    );
}
