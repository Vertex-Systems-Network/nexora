import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Card } from "@nexora/admin-ui";

type Capability = {
    id: string;
    slug: string;
    name: string;
    group: string;
    risk_level: "normal" | "sensitive" | "critical";
    description: string;
    requestedBy: string[];
};

type Props = {
    capabilities: Capability[];
    summary: { total: number; critical: number; sensitive: number };
};

const riskTone = (risk: Capability["risk_level"]): "neutral" | "warning" | "danger" => {
    if (risk === "critical") return "danger";
    if (risk === "sensitive") return "warning";
    return "neutral";
};

export default function Capabilities({ capabilities, summary }: Props) {
    const columns: Column<Capability>[] = [
        {
            key: "capability",
            label: "Capability",
            render: (capability) => (
                <div className="max-w-xl">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-semibold text-[var(--nx-text)]">{capability.name}</span>
                        <Badge tone={riskTone(capability.risk_level)}>{capability.risk_level}</Badge>
                    </div>
                    <code className="mt-1 block text-xs font-semibold text-[var(--nx-brand-600)]">{capability.slug}</code>
                    <p className="mt-2 text-sm leading-5 text-[var(--nx-text-secondary)]">{capability.description}</p>
                </div>
            ),
        },
        { key: "group", label: "Group", render: (capability) => <Badge>{capability.group}</Badge> },
        {
            key: "requested",
            label: "Requested by",
            render: (capability) => capability.requestedBy.length ? (
                <div className="flex max-w-md flex-wrap gap-1.5">
                    {capability.requestedBy.map((module) => <Badge key={module} tone="brand">{module}</Badge>)}
                </div>
            ) : <span className="text-sm text-[var(--nx-text-muted)]">No module</span>,
        },
    ];

    return (
        <AdminLayout>
            <Head title="Runtime Capabilities" />
            <PageHeader
                eyebrow="Zero-trust foundation"
                title="Capabilities"
                description="Runtime capabilities describe what modules and future extensions may access. They are intentionally separate from human user permissions."
            />

            <div className="grid gap-4 sm:grid-cols-3">
                {[
                    ["Capabilities", summary.total, "Platform API access catalog"],
                    ["Sensitive", summary.sensitive, "Requires elevated review"],
                    ["Critical", summary.critical, "Highest-trust operations"],
                ].map(([label, value, hint]) => (
                    <Card key={label} className="p-5">
                        <p className="text-sm font-medium text-[var(--nx-text-muted)]">{label}</p>
                        <p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{value}</p>
                        <p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{hint}</p>
                    </Card>
                ))}
            </div>

            <DataTable
                rows={capabilities}
                columns={columns}
                empty={<EmptyState title="No capabilities" description="The Nexora capability catalog is empty." />}
            />
        </AdminLayout>
    );
}
