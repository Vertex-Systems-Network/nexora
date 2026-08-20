import { useState } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, Card } from "@nexora/admin-ui";

type Finding = { id: number; rule_id: string; severity: "critical" | "high" | "medium" | "low" | "info"; category: string; title: string; message: string; file_path: string | null; line_start: number | null; line_end: number | null; excerpt: string | null; hard_block: boolean; metadata: Record<string, unknown> | null };
type Scan = { id: string; source_name: string; source_sha256: string; engine_version: string; status: string; decision: "allow" | "review" | "block" | "pending"; risk_score: number; manifest: Record<string, unknown>; summary: { severity?: Record<string, number>; metrics?: Record<string, number>; finding_count?: number }; error: string | null; requested_by: { name: string; email: string } | null; started_at: string | null; completed_at: string | null; package: { id: string; name: string; status: string; size_bytes: number; sha256: string } | null };
type SupplyChain={id:string;signature_status:string;provenance_status:string;trust_tier:string;sandbox_profile:string;components_count:number;content_sha256:string;verification_error:string|null;publisher:{name:string;key_id:string;trust_tier:string;status:string}|null};
type Props = { scan: Scan; findings: Paginator<Finding>; filters: { severity: string }; supplyChain:SupplyChain|null; canRescan: boolean; canDelete: boolean };

const tone = (value: string): "success" | "warning" | "danger" | "neutral" => value === "allow" || value === "low" || value === "info" ? "success" : value === "review" || value === "medium" ? "warning" : value === "block" || value === "critical" || value === "high" ? "danger" : "neutral";

export default function SentinelShow({ scan, findings, filters, supplyChain, canRescan, canDelete }: Props) {
    const [rescanning, setRescanning] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const counts = scan.summary?.severity ?? {};
    const metrics = scan.summary?.metrics ?? {};

    const columns: Column<Finding>[] = [
        { key: "severity", label: "Severity", render: (finding) => <div className="flex flex-col gap-1.5"><Badge tone={tone(finding.severity)}>{finding.severity}</Badge>{finding.hard_block && <Badge tone="danger">Hard block</Badge>}</div> },
        { key: "finding", label: "Finding", render: (finding) => <div className="max-w-2xl"><div className="flex flex-wrap items-center gap-2"><code className="text-xs font-bold text-[var(--nx-brand-600)]">{finding.rule_id}</code><Badge>{finding.category}</Badge></div><p className="mt-2 font-semibold text-[var(--nx-text)]">{finding.title}</p><p className="mt-1 text-sm leading-6 text-[var(--nx-text-secondary)]">{finding.message}</p>{finding.file_path && <p className="mt-2 text-xs font-medium text-[var(--nx-text-muted)]">{finding.file_path}{finding.line_start ? `:${finding.line_start}` : ""}</p>}{finding.excerpt && <pre className="nx-scrollbar mt-3 max-w-2xl overflow-x-auto rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-[11px] leading-5 text-[var(--nx-text-secondary)]"><code>{finding.excerpt}</code></pre>}</div> },
    ];

    const rescan = () => {
        if (!scan.package) return;
        setRescanning(true);
        router.post(`/admin/security/sentinel/packages/${scan.package.id}/rescan`, {}, { onFinish: () => setRescanning(false) });
    };
    const destroy = () => {
        if (!scan.package) return;
        setDeleting(true);
        router.delete(`/admin/security/sentinel/packages/${scan.package.id}`, { onFinish: () => setDeleting(false) });
    };
    const filter = (severity: string) => router.get(`/admin/security/sentinel/scans/${scan.id}`, severity ? { severity } : {}, { preserveState: true, replace: true });

    return <AdminLayout>
        <Head title={`Sentinel · ${scan.source_name}`} />
        <PageHeader eyebrow="Sentinel report" title={scan.source_name} description="Source-level security findings are preserved with exact rule, file and line context before any package activation is permitted." actions={<div className="flex gap-2"><Button variant="secondary" onClick={() => router.visit("/admin/security/sentinel")}>All scans</Button>{canRescan && scan.package && <Button variant="secondary" onClick={rescan} loading={rescanning} leadingIcon={<Icon name="refresh" className="h-4 w-4" />}>Rescan</Button>}{canDelete && scan.package && <Button variant="danger" onClick={() => setConfirmDelete(true)} leadingIcon={<Icon name="trash" className="h-4 w-4" />}>Delete package</Button>}</div>} />

        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <Card className="p-5"><p className="text-sm font-medium text-[var(--nx-text-muted)]">Decision</p><div className="mt-3"><Badge tone={tone(scan.decision)}>{scan.decision}</Badge></div><p className="mt-3 text-xs text-[var(--nx-text-muted)]">Sentinel {scan.engine_version}</p></Card>
            <Card className="p-5"><p className="text-sm font-medium text-[var(--nx-text-muted)]">Risk score</p><p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{scan.risk_score}<span className="text-base text-[var(--nx-text-muted)]">/100</span></p><div className="mt-3 h-1.5 overflow-hidden rounded-full bg-[var(--nx-surface-subtle)]"><div className="h-full bg-[var(--nx-brand-600)]" style={{ width: `${scan.risk_score}%` }} /></div></Card>
            <Card className="p-5"><p className="text-sm font-medium text-[var(--nx-text-muted)]">Critical / high</p><p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{(counts.critical ?? 0) + (counts.high ?? 0)}</p><p className="mt-2 text-xs text-[var(--nx-text-muted)]">{counts.critical ?? 0} critical · {counts.high ?? 0} high</p></Card>
            <Card className="p-5"><p className="text-sm font-medium text-[var(--nx-text-muted)]">Archive inspected</p><p className="mt-3 text-3xl font-semibold tracking-[-0.04em] text-[var(--nx-text)]">{metrics.entries ?? 0}</p><p className="mt-2 text-xs text-[var(--nx-text-muted)]">{metrics.analyzed_files ?? 0} source/config files analyzed</p></Card>
        </div>

        {scan.error && <Card className="border-red-300 p-5"><div className="flex gap-3"><Icon name="alert" className="mt-0.5 h-5 w-5 text-red-600"/><div><h2 className="font-semibold text-[var(--nx-text)]">Scan failed closed</h2><p className="mt-1 text-sm text-[var(--nx-text-secondary)]">{scan.error}</p><p className="mt-2 text-xs text-[var(--nx-text-muted)]">Package remains quarantined and blocked because Sentinel could not fully inspect it.</p></div></div></Card>}

        {supplyChain && <Card className="p-5 sm:p-6"><div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">Supply-chain trust</p><div className="mt-3 flex flex-wrap gap-2"><Badge tone={tone(supplyChain.signature_status)}>{`Signature: ${supplyChain.signature_status}`}</Badge><Badge tone={tone(supplyChain.provenance_status)}>{`Provenance: ${supplyChain.provenance_status}`}</Badge><Badge tone={tone(supplyChain.trust_tier)}>{`Trust: ${supplyChain.trust_tier}`}</Badge></div><p className="mt-3 text-sm text-[var(--nx-text-secondary)]">{supplyChain.components_count} dependency components inventoried · execution profile: <strong className="text-[var(--nx-text)]">{supplyChain.sandbox_profile.replaceAll("-"," ")}</strong></p>{supplyChain.publisher&&<p className="mt-1 text-xs text-[var(--nx-text-muted)]">Publisher: {supplyChain.publisher.name} · {supplyChain.publisher.key_id}</p>}{supplyChain.verification_error&&<p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{supplyChain.verification_error}</p>}</div><Button variant="secondary" onClick={()=>router.visit("/admin/security/supply-chain")} leadingIcon={<Icon name="package-check" className="h-4 w-4"/>}>Supply Chain</Button></div></Card>}

        <Card className="p-5 sm:p-6">
            <div className="grid gap-5 lg:grid-cols-2"><div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">Package identity</p><dl className="mt-3 grid gap-3 text-sm"><div><dt className="text-[var(--nx-text-muted)]">SHA-256</dt><dd><code className="break-all text-xs text-[var(--nx-text-secondary)]">{scan.source_sha256}</code></dd></div><div><dt className="text-[var(--nx-text-muted)]">Quarantine</dt><dd className="font-medium text-[var(--nx-text)]">{scan.package?.status ?? "Unavailable"}</dd></div></dl></div><div><p className="text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">Manifest</p><dl className="mt-3 grid grid-cols-2 gap-3 text-sm">{["id","name","type","version"].map(key=><div key={key}><dt className="text-[var(--nx-text-muted)]">{key}</dt><dd className="truncate font-medium text-[var(--nx-text)]">{String(scan.manifest?.[key] ?? "—")}</dd></div>)}</dl></div></div>
        </Card>

        <div className="flex flex-wrap gap-2">{["", "critical", "high", "medium", "low", "info"].map(value => <Button key={value || "all"} size="sm" variant={filters.severity === value ? "primary" : "secondary"} onClick={() => filter(value)}>{value ? `${value} (${counts[value] ?? 0})` : `All (${scan.summary?.finding_count ?? findings.total})`}</Button>)}</div>

        <DataTable rows={findings.data} columns={columns} paginator={findings} empty={<EmptyState title="No findings in this filter" description="Sentinel did not record findings for the selected severity." />} />

        <ConfirmDialog open={confirmDelete} onCancel={() => setConfirmDelete(false)} title="Delete quarantined package?" description="This permanently removes the isolated ZIP and all associated scan history. This action cannot be undone." confirmLabel="Delete package" processing={deleting} danger onConfirm={destroy} />
    </AdminLayout>;
}
