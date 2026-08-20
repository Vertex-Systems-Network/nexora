import type { RequestPayload } from "@inertiajs/core";
import { Head, router } from "@inertiajs/react";
import { useMemo, useState } from "react";
import { Badge, Button, ButtonLink, Card } from "@nexora/admin-ui";
import { DataTable, type Column } from "@admin/components/data/DataTable";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type NodeRow = {
    id: string;
    node_key: string;
    hostname: string | null;
    role: string;
    status: string;
    version: string | null;
    environment: string | null;
    last_heartbeat_at: string | null;
    stale: boolean;
};

type LeaseRow = {
    id: string;
    name: string;
    owner: string | null;
    expires_at: string | null;
    active: boolean;
};

type BackupRow = {
    id: string;
    type: string;
    status: string;
    driver: string | null;
    bytes: number | null;
    checksum: string | null;
    error: string | null;
    created_at: string | null;
    completed_at: string | null;
};

type Health = {
    ready: boolean;
    checks: Array<{ name: string; status: string; duration_ms: number }>;
};

type Topology = {
    node: { key: string; hostname: string };
    database: { connection: string; driver: string };
    cache: { store: string; shared_candidate: boolean };
    queue: { connection: string; async: boolean; queues: string[] };
    session: { driver: string; shared_candidate: boolean };
    object_storage: {
        disk: string;
        driver: string;
        shared: boolean;
        temporary_urls: boolean;
        public_urls: boolean;
    };
    scheduler: { leadership: string; lease_seconds: number };
    warnings: string[];
    ha_ready: boolean;
};

type HaCertification = {
    ready: boolean;
    node_count: number;
    version: string;
    checks: Array<{ name: string; status: string; detail: string }>;
};

type RestoreConfirmation = {
    plan_id: string;
    confirmation: string;
    expires_at: string | null;
    steps: string[];
};

type Props = {
    topology: Topology;
    health: Health;
    haCertification: HaCertification;
    currentNodeKey: string;
    nodes: NodeRow[];
    leases: LeaseRow[];
    backups: BackupRow[];
    metrics: Record<string, number>;
    oneTimeRestoreConfirmation: RestoreConfirmation | null;
    canManage: boolean;
    canBackup: boolean;
};

function formatBytes(value: number | null): string {
    if (! value) {
        return "—";
    }

    const units = ["B", "KB", "MB", "GB", "TB"];
    let amount = value;
    let unit = 0;

    while (amount >= 1024 && unit < units.length - 1) {
        amount /= 1024;
        unit += 1;
    }

    return `${amount.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}

function formatTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : "—";
}

function statusTone(status: string, stale: boolean): "success" | "warning" | "danger" {
    if (stale) {
        return "danger";
    }

    if (status === "active") {
        return "success";
    }

    return status === "draining" ? "warning" : "danger";
}

export default function CloudOperations({
    topology,
    health,
    haCertification,
    currentNodeKey,
    nodes,
    leases,
    backups,
    metrics,
    oneTimeRestoreConfirmation,
    canManage,
    canBackup,
}: Props) {
    const [pending, setPending] = useState<string | null>(null);

    const post = (key: string, url: string, data: RequestPayload = {}) => {
        setPending(key);
        router.post(url, data, {
            preserveScroll: true,
            onFinish: () => setPending(null),
        });
    };

    const nodeColumns = useMemo<Column<NodeRow>[]>(() => [
        {
            key: "node",
            label: "Node",
            render: (node) => (
                <div>
                    <p className="font-semibold text-[var(--nx-text)]">
                        {node.hostname ?? node.node_key}
                    </p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                        {node.node_key}
                        {node.node_key === currentNodeKey ? " · Current node" : ""}
                    </p>
                </div>
            ),
        },
        {
            key: "role",
            label: "Role",
            render: (node) => (
                <span className="text-sm text-[var(--nx-text)]">{node.role}</span>
            ),
        },
        {
            key: "status",
            label: "Status",
            render: (node) => (
                <Badge tone={statusTone(node.status, node.stale)}>
                    {node.stale ? "stale" : node.status}
                </Badge>
            ),
        },
        {
            key: "heartbeat",
            label: "Last heartbeat",
            render: (node) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {formatTime(node.last_heartbeat_at)}
                </span>
            ),
        },
        {
            key: "version",
            label: "Version",
            render: (node) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {node.version ?? "—"}
                </span>
            ),
        },
    ], [currentNodeKey]);

    const leaseColumns = useMemo<Column<LeaseRow>[]>(() => [
        {
            key: "name",
            label: "Lease",
            render: (lease) => (
                <span className="font-semibold text-[var(--nx-text)]">{lease.name}</span>
            ),
        },
        {
            key: "owner",
            label: "Owner",
            render: (lease) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {lease.owner ?? "Unowned"}
                </span>
            ),
        },
        {
            key: "state",
            label: "State",
            render: (lease) => (
                <Badge tone={lease.active ? "success" : "neutral"}>
                    {lease.active ? "Active" : "Expired"}
                </Badge>
            ),
        },
        {
            key: "expires",
            label: "Expires",
            render: (lease) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {formatTime(lease.expires_at)}
                </span>
            ),
        },
    ], []);

    const backupColumns = useMemo<Column<BackupRow>[]>(() => [
        {
            key: "backup",
            label: "Backup",
            render: (backup) => (
                <div>
                    <p className="font-semibold text-[var(--nx-text)]">
                        {backup.type} · {backup.driver ?? "unknown"}
                    </p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">{backup.id}</p>
                </div>
            ),
        },
        {
            key: "status",
            label: "Status",
            render: (backup) => (
                <Badge
                    tone={backup.status === "completed"
                        ? "success"
                        : backup.status === "failed"
                            ? "danger"
                            : "warning"}
                >
                    {backup.status}
                </Badge>
            ),
        },
        {
            key: "size",
            label: "Size",
            render: (backup) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {formatBytes(backup.bytes)}
                </span>
            ),
        },
        {
            key: "created",
            label: "Created",
            render: (backup) => (
                <span className="text-sm text-[var(--nx-text-muted)]">
                    {formatTime(backup.created_at)}
                </span>
            ),
        },
        {
            key: "actions",
            label: "Actions",
            render: (backup) => (
                <div className="flex flex-wrap gap-2">
                    {backup.status === "completed" && (
                        <>
                            <Button
                                size="sm"
                                variant="secondary"
                                loading={pending === `verify:${backup.id}`}
                                onClick={() => {
                                    post(
                                        `verify:${backup.id}`,
                                        `/admin/cloud/backups/${backup.id}/verify`,
                                    );
                                }}
                            >
                                Verify
                            </Button>
                            <Button
                                size="sm"
                                variant="secondary"
                                loading={pending === `plan:${backup.id}`}
                                onClick={() => {
                                    post(
                                        `plan:${backup.id}`,
                                        `/admin/cloud/backups/${backup.id}/restore-plan`,
                                    );
                                }}
                            >
                                Restore plan
                            </Button>
                            <ButtonLink
                                size="sm"
                                variant="ghost"
                                href={`/admin/cloud/backups/${backup.id}/download`}
                            >
                                Download
                            </ButtonLink>
                        </>
                    )}
                    {backup.error && (
                        <span className="max-w-xs text-xs text-[var(--nx-danger)]">
                            {backup.error}
                        </span>
                    )}
                </div>
            ),
        },
    ], [pending]);

    const passedHaChecks = haCertification.checks.filter(
        (check) => check.status === "pass",
    ).length;

    return (
        <AdminLayout>
            <Head title="Cloud & Operations" />
            <PageHeader
                eyebrow="Operations"
                title="Cloud, HA & distributed runtime"
                description="Operate Nexora as one node or many. Shared runtime primitives expose topology, scheduler leadership, node drain state, health, metrics and protected backup orchestration without forcing cloud-only infrastructure."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {canManage && (
                            <Button
                                variant="secondary"
                                loading={pending === "metrics"}
                                onClick={() => post("metrics", "/admin/cloud/metrics")}
                            >
                                Capture metrics
                            </Button>
                        )}
                        {canBackup && (
                            <Button
                                loading={pending === "backup"}
                                onClick={() => post("backup", "/admin/cloud/backups")}
                            >
                                Create database backup
                            </Button>
                        )}
                    </div>
                )}
            />

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                        Readiness
                    </p>
                    <div className="mt-3 flex items-center gap-2">
                        <Badge tone={health.ready ? "success" : "danger"}>
                            {health.ready ? "Ready" : "Not ready"}
                        </Badge>
                        <span className="text-sm text-[var(--nx-text-muted)]">
                            {health.checks.length} probes
                        </span>
                    </div>
                </Card>

                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                        HA posture
                    </p>
                    <div className="mt-3">
                        <Badge tone={haCertification.ready ? "success" : "warning"}>
                            {haCertification.ready ? "HA certification-ready" : "HA evidence pending"}
                        </Badge>
                        <p className="mt-2 text-xs text-[var(--nx-text-muted)]">
                            {haCertification.node_count} fresh active node(s) · {passedHaChecks}/{haCertification.checks.length} strict checks
                        </p>
                    </div>
                </Card>

                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                        Queue
                    </p>
                    <p className="mt-2 text-lg font-semibold text-[var(--nx-text)]">
                        {topology.queue.connection}
                    </p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                        {topology.queue.async ? "Asynchronous workers" : "Synchronous request execution"}
                    </p>
                </Card>

                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                        Object storage
                    </p>
                    <p className="mt-2 text-lg font-semibold text-[var(--nx-text)]">
                        {topology.object_storage.disk}
                    </p>
                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                        {topology.object_storage.driver} · {topology.object_storage.shared ? "shared" : "node-local"}
                    </p>
                </Card>
            </div>

            {topology.warnings.length > 0 && (
                <Card className="border-amber-300/60 p-5">
                    <h2 className="font-semibold text-[var(--nx-text)]">Horizontal-scaling readiness</h2>
                    <div className="mt-3 grid gap-2">
                        {topology.warnings.map((warning) => (
                            <p key={warning} className="text-sm text-[var(--nx-text-muted)]">
                                • {warning}
                            </p>
                        ))}
                    </div>
                </Card>
            )}

            {oneTimeRestoreConfirmation && (
                <Card className="border-amber-300/60 p-5">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">
                                One-time restore plan confirmation
                            </h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Plan {oneTimeRestoreConfirmation.plan_id}. Copy this confirmation now; Nexora stores only its hash.
                            </p>
                            <p className="mt-3 font-mono text-lg font-semibold text-[var(--nx-text)]">
                                {oneTimeRestoreConfirmation.confirmation}
                            </p>
                        </div>
                        <Badge tone="warning">Offline restore only</Badge>
                    </div>
                    <div className="mt-4 grid gap-2">
                        {oneTimeRestoreConfirmation.steps.map((step, index) => (
                            <p key={step} className="text-sm text-[var(--nx-text-muted)]">
                                {index + 1}. {step}
                            </p>
                        ))}
                    </div>
                </Card>
            )}

            <Card className="p-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-[var(--nx-text)]">Current node</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            {topology.node.hostname} · {topology.node.key}
                        </p>
                    </div>
                    {canManage && (
                        <div className="flex flex-wrap gap-2">
                            <Button
                                size="sm"
                                variant="secondary"
                                loading={pending === "heartbeat"}
                                onClick={() => post("heartbeat", "/admin/cloud/node/heartbeat")}
                            >
                                Heartbeat
                            </Button>
                            <Button
                                size="sm"
                                variant="secondary"
                                loading={pending === "drain"}
                                onClick={() => post("drain", "/admin/cloud/node/status", { status: "draining" })}
                            >
                                Drain node
                            </Button>
                            <Button
                                size="sm"
                                variant="secondary"
                                loading={pending === "maintenance"}
                                onClick={() => post("maintenance", "/admin/cloud/node/status", { status: "maintenance" })}
                            >
                                Maintenance
                            </Button>
                            <Button
                                size="sm"
                                loading={pending === "active"}
                                onClick={() => post("active", "/admin/cloud/node/status", { status: "active" })}
                            >
                                Activate
                            </Button>
                        </div>
                    )}
                </div>
            </Card>

            <section className="grid gap-4 xl:grid-cols-2">
                <Card className="p-5">
                    <h2 className="font-semibold text-[var(--nx-text)]">Runtime topology</h2>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        {[
                            ["Database", `${topology.database.driver} · ${topology.database.connection}`],
                            ["Cache", topology.cache.store],
                            ["Session", topology.session.driver],
                            ["Scheduler", `${topology.scheduler.leadership} · ${topology.scheduler.lease_seconds}s lease`],
                        ].map(([label, value]) => (
                            <div
                                key={label}
                                className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"
                            >
                                <dt className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                                    {label}
                                </dt>
                                <dd className="mt-2 text-sm font-semibold text-[var(--nx-text)]">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </Card>

                <Card className="p-5">
                    <h2 className="font-semibold text-[var(--nx-text)]">Latest operational metrics</h2>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-2">
                        {Object.entries(metrics).length === 0 ? (
                            <p className="text-sm text-[var(--nx-text-muted)]">
                                No runtime metric snapshot has been recorded yet.
                            </p>
                        ) : Object.entries(metrics).map(([key, value]) => (
                            <div
                                key={key}
                                className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"
                            >
                                <dt className="text-xs font-semibold text-[var(--nx-text-muted)]">
                                    {key.replace(/^runtime\./, "").replaceAll("_", " ")}
                                </dt>
                                <dd className="mt-2 text-lg font-semibold text-[var(--nx-text)]">
                                    {Number(value).toLocaleString()}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </Card>
            </section>

            <section>
                <h2 className="mb-3 text-base font-semibold text-[var(--nx-text)]">Runtime nodes</h2>
                <DataTable rows={nodes} columns={nodeColumns} />
            </section>
            <section>
                <h2 className="mb-3 text-base font-semibold text-[var(--nx-text)]">Distributed leases</h2>
                <DataTable rows={leases} columns={leaseColumns} />
            </section>
            <section>
                <h2 className="mb-3 text-base font-semibold text-[var(--nx-text)]">Protected backups</h2>
                <DataTable rows={backups} columns={backupColumns} />
            </section>
        </AdminLayout>
    );
}
