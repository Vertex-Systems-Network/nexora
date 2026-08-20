import { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, Card } from "@nexora/admin-ui";

type Dependency = { identifier: string; constraint: string; optional: boolean };
type RuntimeModule = {
    id: string;
    identifier: string;
    name: string;
    version: string;
    description: string;
    class: string;
    core: boolean;
    loadOrder: number;
    bootPosition: number;
    capabilities: string[];
    dependencies: Dependency[];
    manifestHash: string;
    synced: boolean;
    versionIntegrity: boolean | null;
    lastBootedAt: string | null;
};

type Props = {
    modules: RuntimeModule[];
    summary: { registered: number; core: number; synced: number; integrityIssues: number };
    canSync: boolean;
};

export default function Modules({ modules, summary, canSync }: Props) {
    const [syncing, setSyncing] = useState(false);
    const columns: Column<RuntimeModule>[] = [
        {
            key: "module",
            label: "Module",
            render: (module) => (
                <div className="max-w-xl">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-semibold text-[var(--nx-text)]">{module.name}</span>
                        {module.core && <Badge tone="brand">Core</Badge>}
                        <Badge tone={module.synced ? "success" : "warning"}>{module.synced ? "Synced" : "Sync needed"}</Badge>
                        {!module.versionIntegrity && <Badge tone="danger">Version changed</Badge>}
                    </div>
                    <p className="mt-1 text-xs font-medium text-[var(--nx-text-muted)]">{module.identifier}</p>
                    <p className="mt-2 text-sm leading-5 text-[var(--nx-text-secondary)]">{module.description}</p>
                </div>
            ),
        },
        {
            key: "version",
            label: "Version",
            render: (module) => <code className="text-xs font-semibold text-[var(--nx-text-secondary)]">{module.version}</code>,
        },
        {
            key: "boot",
            label: "Boot order",
            render: (module) => (
                <div className="text-sm text-[var(--nx-text-secondary)]">
                    <div className="font-semibold text-[var(--nx-text)]">#{module.bootPosition}</div>
                    <div className="mt-1 text-xs text-[var(--nx-text-muted)]">weight {module.loadOrder}</div>
                </div>
            ),
        },
        {
            key: "dependencies",
            label: "Dependencies",
            render: (module) => module.dependencies.length ? (
                <div className="flex max-w-sm flex-wrap gap-1.5">
                    {module.dependencies.map((dependency) => (
                        <Badge key={dependency.identifier}>{dependency.identifier} {dependency.constraint}</Badge>
                    ))}
                </div>
            ) : <span className="text-sm text-[var(--nx-text-muted)]">None</span>,
        },
        {
            key: "capabilities",
            label: "Capabilities",
            render: (module) => <Badge tone="neutral">{module.capabilities.length}</Badge>,
        },
    ];

    const sync = () => {
        setSyncing(true);
        router.post("/admin/system/runtime/sync", {}, { preserveScroll: true, onFinish: () => setSyncing(false) });
    };

    return (
        <AdminLayout>
            <Head title="Runtime Modules" />
            <PageHeader
                eyebrow="Runtime"
                title="Modules"
                description="Deterministic modules registered by the Nexora kernel. Runtime order is dependency-resolved; web requests never scan the filesystem for modules."
                actions={canSync ? <Button onClick={sync} loading={syncing} leadingIcon={<Icon name="refresh" className="h-4 w-4" />}>Synchronize runtime</Button> : undefined}
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ["Registered", summary.registered, "Known to the live kernel"],
                    ["Core", summary.core, "First-party trusted modules"],
                    ["Synchronized", summary.synced, summary.synced === summary.registered ? "Database metadata matches runtime" : "Runtime sync recommended"],
                    ["Integrity issues", summary.integrityIssues, summary.integrityIssues === 0 ? "No same-version manifest drift" : "Review changed module versions"],
                ].map(([label, value, hint]) => (
                    <Card key={label} className="p-5">
                        <p className="text-sm font-medium text-[var(--nx-text-muted)]">{label}</p>
                        <p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{value}</p>
                        <p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{hint}</p>
                    </Card>
                ))}
            </div>

            <DataTable
                rows={modules}
                columns={columns}
                empty={<EmptyState title="No runtime modules" description="No modules are configured for the Nexora kernel." />}
            />
        </AdminLayout>
    );
}
