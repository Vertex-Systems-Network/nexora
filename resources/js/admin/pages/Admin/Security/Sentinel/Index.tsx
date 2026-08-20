import { FormEvent, useState } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, Card, FilePicker, TextLink } from "@nexora/admin-ui";

type Scan = {
    id: string;
    source_name: string;
    source_sha256: string;
    status: string;
    decision: "allow" | "review" | "block" | "pending";
    risk_score: number;
    engine_version: string;
    findings_count: number;
    requested_by: string | null;
    created_at: string | null;
    package_status: string | null;
};

type Props = {
    scans: Paginator<Scan>;
    summary: { total: number; blocked: number; review: number; allowed: number; quarantined: number };
    upload: { maxKilobytes: number; extensions: string[] };
};

const decisionTone = (decision: Scan["decision"]): "success" | "warning" | "danger" | "neutral" => {
    if (decision === "allow") return "success";
    if (decision === "review") return "warning";
    if (decision === "block") return "danger";
    return "neutral";
};

export default function SentinelIndex({ scans, summary, upload }: Props) {
    const [file, setFile] = useState<File | null>(null);
    const [scanning, setScanning] = useState(false);
    const [progress, setProgress] = useState<number | null>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!file) return;
        setScanning(true);
        setProgress(0);
        router.post("/admin/security/sentinel", { package: file }, {
            forceFormData: true,
            onProgress: (value) => setProgress(value?.percentage ?? null),
            onFinish: () => {
                setScanning(false);
                setProgress(null);
            },
            onSuccess: () => {
                setFile(null);
            },
        });
    };

    const columns: Column<Scan>[] = [
        {
            key: "package",
            label: "Package",
            render: (scan) => (
                <div className="max-w-lg">
                    <TextLink href={`/admin/security/sentinel/scans/${scan.id}`} tone="neutral">
                        {scan.source_name}
                    </TextLink>
                    <code className="mt-1 block truncate text-[11px] text-[var(--nx-text-muted)]">{scan.source_sha256}</code>
                </div>
            ),
        },
        {
            key: "decision",
            label: "Decision",
            render: (scan) => <Badge tone={decisionTone(scan.decision)}>{scan.decision}</Badge>,
        },
        {
            key: "risk",
            label: "Risk",
            render: (scan) => (
                <div className="min-w-28">
                    <div className="flex items-center justify-between text-xs font-semibold text-[var(--nx-text)]"><span>{scan.risk_score}/100</span><span>{scan.findings_count} findings</span></div>
                    <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--nx-surface-subtle)]"><div className="h-full rounded-full bg-[var(--nx-brand-600)]" style={{ width: `${Math.min(100, scan.risk_score)}%` }} /></div>
                </div>
            ),
        },
        {
            key: "status",
            label: "Quarantine",
            render: (scan) => <Badge tone={scan.package_status === "scanned" ? "success" : "warning"}>{scan.package_status ?? scan.status}</Badge>,
        },
        {
            key: "time",
            label: "Scanned",
            render: (scan) => <span className="text-sm text-[var(--nx-text-secondary)]">{scan.created_at ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(scan.created_at)) : "—"}</span>,
        },
    ];

    return (
        <AdminLayout>
            <Head title="Nexora Sentinel" />
            <PageHeader eyebrow="Zero-trust security" title="Nexora Sentinel" description="Every package enters quarantine first. Sentinel inspects the archive and source without executing or installing it, then returns an allow, review or block decision." />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {[
                    ["Scans", summary.total, "All package inspections"],
                    ["Blocked", summary.blocked, "Activation must remain denied"],
                    ["Review", summary.review, "Human security review required"],
                    ["Allowed", summary.allowed, "No blocking findings detected"],
                    ["Quarantined", summary.quarantined, "Packages isolated from runtime"],
                ].map(([label, value, hint]) => (
                    <Card key={label} className="p-5">
                        <p className="text-sm font-medium text-[var(--nx-text-muted)]">{label}</p>
                        <p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{value}</p>
                        <p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{hint}</p>
                    </Card>
                ))}
            </div>

            <Card className="overflow-hidden">
                <div className="grid gap-6 p-5 lg:grid-cols-[1fr_auto] lg:items-center sm:p-6">
                    <div>
                        <div className="flex items-center gap-2"><div className="grid h-10 w-10 place-items-center rounded-xl bg-[var(--nx-surface-subtle)] text-[var(--nx-brand-600)]"><Icon name="sentinel" className="h-5 w-5" /></div><div><h2 className="font-semibold text-[var(--nx-text)]">Scan a package</h2><p className="text-sm text-[var(--nx-text-muted)]">ZIP only · maximum {Math.round(upload.maxKilobytes / 1024)} MB · never extracted into runtime</p></div></div>
                    </div>
                    <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <FilePicker label="Choose package ZIP" description={`ZIP only · maximum ${Math.round(upload.maxKilobytes / 1024)} MB`} accept=".zip,application/zip" file={file} onChange={setFile} className="min-w-72" />
                        <Button type="submit" loading={scanning} disabled={!file} leadingIcon={<Icon name="search" className="h-4 w-4" />}>{scanning ? "Scanning" : "Quarantine & scan"}</Button>
                    </form>
                </div>
                {scanning && progress !== null && <div className="h-1 bg-[var(--nx-surface-subtle)]"><div className="h-full bg-[var(--nx-brand-600)] transition-[width]" style={{ width: `${progress}%` }} /></div>}
            </Card>

            <DataTable rows={scans.data} columns={columns} paginator={scans} empty={<EmptyState title="No packages scanned yet" description="Upload a Nexora package to start the zero-trust inspection pipeline." />} />
        </AdminLayout>
    );
}
