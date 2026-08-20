import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Badge, Card } from "@nexora/admin-ui";

type Check = { name: string; status: "healthy" | "unhealthy"; message: string; durationMs: number };

export default function Health({ checks, runtime }: { checks: Check[]; runtime: { php: string; laravel: string; environment: string } }) {
    const healthy = checks.every((check) => check.status === "healthy");
    return (
        <AdminLayout>
            <Head title="System Health" />
            <PageHeader eyebrow="Operations" title="System health" description="Fast operational probes for the foundation. Sentinel and deeper extension health diagnostics will extend this page in the security milestones." actions={<Badge tone={healthy ? "success" : "danger"}>{healthy ? "All systems healthy" : "Attention required"}</Badge>} />
            <div className="grid gap-4 lg:grid-cols-3">
                {checks.map((check) => <Card key={check.name} className="p-5"><div className="flex items-start justify-between gap-4"><div><h2 className="font-semibold text-[var(--nx-text)]">{check.name}</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">{check.message}</p></div><Badge tone={check.status === "healthy" ? "success" : "danger"}>{check.status}</Badge></div><p className="mt-5 text-xs text-[var(--nx-text-muted)]">Probe {check.durationMs} ms</p></Card>)}
            </div>
            <Card className="p-5 sm:p-6"><h2 className="text-base font-semibold text-[var(--nx-text)]">Runtime</h2><dl className="mt-5 grid gap-4 sm:grid-cols-3">{Object.entries(runtime).map(([key, value]) => <div key={key} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"><dt className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">{key}</dt><dd className="mt-2 text-sm font-semibold text-[var(--nx-text)]">{value}</dd></div>)}</dl></Card>
        </AdminLayout>
    );
}
