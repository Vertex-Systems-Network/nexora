import { useMemo, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, IconButton, Input, Modal, Select } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type CanvasRow = {
    id: number; name: string; scope: "standalone" | "document" | "theme-template"; status: "draft" | "published";
    document: string | null; theme: string | null; templateKey: string | null; revisionsCount: number; updatedAt: string | null;
};
type Paginator<T> = { data: T[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; total: number };
type Option = { id: number; title?: string; name?: string };

const scopeLabel = (scope: CanvasRow["scope"]) => scope === "theme-template" ? "Theme template" : scope === "document" ? "Document design" : "Standalone canvas";
const statusTone = (status: CanvasRow["status"]): "success" | "warning" => status === "published" ? "success" : "warning";

export default function StudioIndex({ canvases, filters, documents, themes }: { canvases: Paginator<CanvasRow>; filters: { search: string; scope: string }; documents: Option[]; themes: Option[] }) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canCreate = permissions.includes("studio.create");
    const canDelete = permissions.includes("studio.delete");
    const [createOpen, setCreateOpen] = useState(false);
    const [search, setSearch] = useState(filters.search ?? "");
    const [scopeFilter, setScopeFilter] = useState(filters.scope ?? "");
    const [deleteTarget, setDeleteTarget] = useState<CanvasRow | null>(null);
    const [deleting, setDeleting] = useState(false);
    const form = useForm({ name: "", scope: "standalone", document_id: "", theme_id: "", template_key: "home" });

    const scopeOptions = [
        { value: "standalone", label: "Standalone canvas", description: "Reusable visual layout not bound to a document or theme template." },
        { value: "document", label: "Document design", description: "Bind this visual layout to a structured Nexora document." },
        { value: "theme-template", label: "Theme template overlay", description: "Visual template foundation linked to an installed theme." },
    ];
    const create = () => form.post("/admin/studio", { preserveScroll: true, onSuccess: () => { setCreateOpen(false); form.reset(); } });
    const applyFilters = (nextScope = scopeFilter) => router.get("/admin/studio", { search, scope: nextScope }, { preserveState: true, replace: true });
    const updated = (value: string | null) => value ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "—";
    const totals = useMemo(() => ({ published: canvases.data.filter((item) => item.status === "published").length, revisions: canvases.data.reduce((sum, item) => sum + item.revisionsCount, 0) }), [canvases.data]);

    return <AdminLayout>
        <Head title="Studio" />
        <PageHeader
            eyebrow="Visual experience"
            title="Nexora Studio"
            description="Build safe visual layouts with typed elements, responsive styles, dynamic data bindings and reusable components without editing theme files."
            actions={canCreate ? <Button onClick={() => setCreateOpen(true)} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>New canvas</Button> : undefined}
        />

        <div className="mb-5 grid gap-3 md:grid-cols-3">
            <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="studio" className="h-4 w-4" /></span><div><p className="text-xs text-[var(--nx-text-muted)]">Canvases</p><p className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">{canvases.total}</p></div></div></Card>
            <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-green-50 text-green-700 dark:bg-green-950/25 dark:text-green-300"><Icon name="success" className="h-4 w-4" /></span><div><p className="text-xs text-[var(--nx-text-muted)]">Published on this page</p><p className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">{totals.published}</p></div></div></Card>
            <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-surface-subtle)] text-[var(--nx-text-muted)]"><Icon name="history" className="h-4 w-4" /></span><div><p className="text-xs text-[var(--nx-text-muted)]">Revision snapshots on page</p><p className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">{totals.revisions}</p></div></div></Card>
        </div>

        <Card className="mb-5 p-4">
            <div className="grid gap-3 lg:grid-cols-[minmax(18rem,1fr)_16rem_auto]">
                <Input name="search" value={search} placeholder="Search Studio canvases…" onChange={(event) => setSearch(event.target.value)} onKeyDown={(event) => event.key === "Enter" && applyFilters()} />
                <Select ariaLabel="Filter Studio by scope" value={scopeFilter} onChange={(value) => { setScopeFilter(value); applyFilters(value); }} options={[{ value: "", label: "All canvas scopes" }, ...scopeOptions]} />
                <Button variant="secondary" onClick={() => applyFilters()} leadingIcon={<Icon name="search" className="h-4 w-4" />}>Search</Button>
            </div>
        </Card>

        {canvases.data.length === 0 ? <EmptyState title="No Studio canvases yet" description="Create a visual canvas and start composing layouts from typed Nexora elements." /> : <div className="grid gap-4 xl:grid-cols-2">
            {canvases.data.map((canvas) => <Card key={canvas.id} className="p-5">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2"><h2 className="truncate text-base font-semibold text-[var(--nx-text)]">{canvas.name}</h2><Badge tone={statusTone(canvas.status)}>{canvas.status === "published" ? "Published" : "Draft"}</Badge></div>
                        <p className="mt-1 text-xs text-[var(--nx-text-muted)]">{scopeLabel(canvas.scope)} · {canvas.revisionsCount} revisions</p>
                    </div>
                    {canDelete && <IconButton label={`Delete ${canvas.name}`} tone="danger" onClick={() => setDeleteTarget(canvas)}><Icon name="trash" className="h-4 w-4" /></IconButton>}
                </div>
                <div className="mt-4 grid gap-2 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-sm text-[var(--nx-text-secondary)] sm:grid-cols-2">
                    <div><span className="block text-xs text-[var(--nx-text-muted)]">Binding</span><span className="mt-1 block font-medium text-[var(--nx-text)]">{canvas.document ?? canvas.theme ?? "None"}</span></div>
                    <div><span className="block text-xs text-[var(--nx-text-muted)]">Updated</span><span className="mt-1 block font-medium text-[var(--nx-text)]">{updated(canvas.updatedAt)}</span></div>
                </div>
                <div className="mt-4 flex flex-wrap gap-2"><ButtonLink href={`/admin/studio/${canvas.id}/edit`} leadingIcon={<Icon name="edit" className="h-4 w-4" />}>Open Studio</ButtonLink>{canvas.templateKey && <Badge>{canvas.templateKey}</Badge>}</div>
            </Card>)}
        </div>}

        {(canvases.prev_page_url || canvases.next_page_url) && <div className="mt-5 flex items-center justify-between">{canvases.prev_page_url ? <ButtonLink href={canvases.prev_page_url} variant="secondary">Previous</ButtonLink> : <Button type="button" variant="secondary" disabled>Previous</Button>}<span className="text-sm text-[var(--nx-text-muted)]">Page {canvases.current_page} of {canvases.last_page}</span>{canvases.next_page_url ? <ButtonLink href={canvases.next_page_url} variant="secondary">Next</ButtonLink> : <Button type="button" variant="secondary" disabled>Next</Button>}</div>}

        <Modal open={createOpen} onClose={() => setCreateOpen(false)} title="Create Studio canvas" description="Choose what this visual layout controls. The canvas stays separate from theme files and document semantics." footer={<><Button variant="secondary" onClick={() => setCreateOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={create}>Create canvas</Button></>}>
            <div className="grid gap-4">
                <Input label="Canvas name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} error={form.errors.name} placeholder="Article layout" />
                <Select label="Canvas scope" value={form.data.scope} onChange={(value) => form.setData("scope", value)} options={scopeOptions} error={form.errors.scope} />
                {form.data.scope === "document" && <Select label="Document" value={form.data.document_id} onChange={(value) => form.setData("document_id", value)} options={[{ value: "", label: "Choose document" }, ...documents.map((document) => ({ value: String(document.id), label: document.title ?? `Document ${document.id}` }))]} error={form.errors.document_id} />}
                {form.data.scope === "theme-template" && <><Select label="Theme" value={form.data.theme_id} onChange={(value) => form.setData("theme_id", value)} options={[{ value: "", label: "Choose theme" }, ...themes.map((theme) => ({ value: String(theme.id), label: theme.name ?? `Theme ${theme.id}` }))]} error={form.errors.theme_id} /><Input label="Template key" value={form.data.template_key} onChange={(event) => form.setData("template_key", event.target.value)} error={form.errors.template_key} hint="Stable identifier such as home, document, landing.default." /></>}
            </div>
        </Modal>

        <ConfirmDialog open={Boolean(deleteTarget)} title="Delete Studio canvas" description={deleteTarget ? `Delete “${deleteTarget.name}” and its visual revision history?` : ""} confirmLabel="Delete canvas" processing={deleting} onCancel={() => setDeleteTarget(null)} onConfirm={() => { if (!deleteTarget) return; setDeleting(true); router.delete(`/admin/studio/${deleteTarget.id}`, { onFinish: () => { setDeleting(false); setDeleteTarget(null); } }); }} />
    </AdminLayout>;
}
