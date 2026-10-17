import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Badge, Card } from "@nexora/admin-ui";

type Check = { name: string; status: "healthy" | "unhealthy"; message: string; durationMs: number };
type ProcessRoles = "web" | "queue" | "scheduler";
type RuntimeProcessStatus = {
    status: "pass" | "fail";
    required: Record<ProcessRoles, number>;
    live: { counts: Record<ProcessRoles, number> };
    checks: Record<string, boolean>;
};
type Runtime = {
    php: string;
    laravel: string;
    environment: string;
    database: string;
    processPlane?: RuntimeProcessStatus;
};

const processRoles: ProcessRoles[] = ["web", "queue", "scheduler"];

export default function Health({ checks, runtime }: { checks: Check[]; runtime: Runtime }) {
    const processPlane = runtime.processPlane;
    // Include the process-plane result in the page-level health summary.
    const healthy = checks.every((check) => check.status === "healthy") && (!processPlane || processPlane.status === "pass");
    const scalarRuntime = [
        ["PHP", runtime.php],
        ["Laravel", runtime.laravel],
        ["Environment", runtime.environment],
        ["Database", runtime.database],
    ] as const;

    return (
        <AdminLayout>
            <Head title="System Health" />
            <PageHeader eyebrow="Operations" title="System health" description="Fast operational probes for the foundation. Sentinel and deeper extension health diagnostics will extend this page in the security milestones." actions={<Badge tone={healthy ? "success" : "danger"}>{healthy ? "All systems healthy" : "Attention required"}</Badge>} />
            <div className="grid gap-4 lg:grid-cols-3">
                {checks.map((check) => (
                    <Card key={check.name} className="p-5">
                        <div className="flex items-start justify-between gap-4">
                            <div><h2 className="font-semibold text-[var(--nx-text)]">{check.name}</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">{check.message}</p></div>
                            <Badge tone={check.status === "healthy" ? "success" : "danger"}>{check.status}</Badge>
                        </div>
                        <p className="mt-5 text-xs text-[var(--nx-text-muted)]">Probe {check.durationMs} ms</p>
                    </Card>
                ))}
            </div>
            <Card className="p-5 sm:p-6">
                <h2 className="text-base font-semibold text-[var(--nx-text)]">Runtime</h2>
                <dl className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {scalarRuntime.map(([key, value]) => (
                        <div key={key} className="min-w-0 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                            <dt className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">{key}</dt>
                            <dd className="mt-2 break-words text-sm font-semibold text-[var(--nx-text)]">{value}</dd>
                        </div>
                    ))}
                </dl>
            </Card>
            {processPlane && (
                <Card className="p-5 sm:p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-base font-semibold text-[var(--nx-text)]">Process runtime</h2>
                        <Badge tone={processPlane.status === "pass" ? "success" : "danger"}>{processPlane.status === "pass" ? "Policy passing" : "Policy needs attention"}</Badge>
                    </div>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-3">
                        {processRoles.map((role) => (
                            <div key={role} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                                <dt className="text-xs font-semibold uppercase text-[var(--nx-text-muted)]">{role}</dt>
                                <dd className="mt-2 text-sm font-semibold text-[var(--nx-text)]">{processPlane.live?.counts?.[role] ?? 0} observed / {processPlane.required?.[role] ?? 0} required</dd>
                            </div>
                        ))}
                    </dl>
                    <p className="mt-3 text-xs text-[var(--nx-text-muted)]">Process policy is shown separately from node liveness. A passing policy does not certify HA capacity.</p>
                </Card>
            )}
        </AdminLayout>
    );
}
