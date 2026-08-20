import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Badge, Card } from "@nexora/admin-ui";

type Props = {
    summary: { users: number; modules: number; healthIssues: number; auditEvents24h: number };
    database: { driver: string; connected: boolean };
};

export default function Dashboard({ summary, database }: Props) {
    const stats = [
        ["Users", summary.users.toLocaleString(), "Foundation identities"],
        ["Modules", summary.modules.toLocaleString(), "Registered modules"],
        ["Health issues", summary.healthIssues.toLocaleString(), summary.healthIssues === 0 ? "All core checks healthy" : "Review system health"],
        ["Audit events", summary.auditEvents24h.toLocaleString(), "Last 24 hours"],
    ];

    return (
        <AdminLayout>
            <Head title="Dashboard" />
            <PageHeader eyebrow="Foundation" title="Dashboard" description="A calm operational overview. Nexora keeps high-signal system information visible without turning the admin into a noisy analytics wall." />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map(([label, value, hint]) => (
                    <Card key={label} className="p-5 sm:p-6">
                        <p className="text-sm font-medium text-[var(--nx-text-muted)]">{label}</p>
                        <p className="mt-4 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{value}</p>
                        <p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{hint}</p>
                    </Card>
                ))}
            </div>

            <div className="grid gap-4 xl:grid-cols-[1.35fr_.65fr]">
                <Card className="p-5 sm:p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-base font-semibold text-[var(--nx-text)]">Foundation status</h2>
                            <p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Core admin shell, identity, settings and health infrastructure are active.</p>
                        </div>
                        <Badge tone={summary.healthIssues === 0 ? "success" : "warning"}>{summary.healthIssues === 0 ? "Healthy" : "Attention"}</Badge>
                    </div>
                    <div className="mt-6 grid gap-3 sm:grid-cols-3">
                        {["Admin UI boundary", "Capability-ready core", "Audit foundation"].map((item) => (
                            <div key={item} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] px-4 py-3 text-sm font-medium text-[var(--nx-text-secondary)]">{item}</div>
                        ))}
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <h2 className="text-base font-semibold text-[var(--nx-text)]">Runtime</h2>
                    <dl className="mt-5 grid gap-4 text-sm">
                        <div className="flex items-center justify-between gap-4"><dt className="text-[var(--nx-text-muted)]">Database</dt><dd className="font-semibold capitalize text-[var(--nx-text)]">{database.driver}</dd></div>
                        <div className="flex items-center justify-between gap-4"><dt className="text-[var(--nx-text-muted)]">Connection</dt><dd><Badge tone={database.connected ? "success" : "danger"}>{database.connected ? "Connected" : "Unavailable"}</Badge></dd></div>
                    </dl>
                </Card>
            </div>
        </AdminLayout>
    );
}
