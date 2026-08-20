import { useEffect, useMemo, useRef, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { BlockEditor, documentStats, type BlockDefinition, type DocumentContent, type MediaOption } from "@admin/components/writer/BlockEditor";
import { DateTimePicker, Badge, Button, ButtonLink, Card, IconButton, Input, Select, Textarea } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type DocumentData = {
    id: number; uuid: string; title: string; slug: string | null; type: string;
    status: "draft" | "published" | "archived";
    workflow_status: string; assigned_to: number | null; reviewer_id: number | null; review_due_at: string | null;
    excerpt: string | null; content: DocumentContent; revisions_count: number; lock_version: number;
    updated_at: string | null; autosaved_at: string | null;
};
type TypeOption = { key: string; name: string; description: string; icon: string };
type WorkflowState = { key: string; name: string; description: string; tone: "neutral" | "success" | "warning" | "danger" | "brand"; terminal: boolean };
type Person = { id: number; name: string; email: string };
type ReviewComment = { id: number; body: string; status: "open" | "resolved"; author: string; created_at: string | null; resolved_by?: string | null; resolved_at?: string | null };
type AutosaveState = "idle" | "saving" | "saved" | "conflict" | "error";

type FormData = {
    title: string; slug: string; type: string; status: "draft" | "published" | "archived"; workflow_status: string;
    assigned_to: number | null; reviewer_id: number | null; review_due_at: string; excerpt: string;
    // Deliberate shallow boundary: DocumentContent contains recursive WriterValue nodes.
    // Keeping the recursive tree opaque to Inertia's FormDataType prevents TS2589;
    // BlockEditor still owns the concrete DocumentContent type and the server revalidates it.
    content: any;
    lock_version: number;
};

const emptyContent = (): DocumentContent => ({ version: 1, blocks: [{ id: crypto.randomUUID(), type: "paragraph", version: 1, data: { text: "" }, children: [] }] });
const csrfToken = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "";
const formatTime = (value: string | null | undefined) => value ? new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(new Date(value)) : "Not yet";

export default function DocumentForm({ document, initialType, types, blocks, workflowStates, people, mediaAssets, latestAutosave, reviewComments }: {
    document: DocumentData | null; initialType?: string | null; types: TypeOption[]; blocks: BlockDefinition[]; workflowStates: WorkflowState[]; people: Person[]; mediaAssets: MediaOption[]; latestAutosave?: { saved_at: string | null; base_lock_version: number; base_revision: number; title:string; slug:string|null; excerpt:string|null; content:DocumentContent; workflow_status:string } | null; reviewComments: ReviewComment[];
}) {
    const editing = Boolean(document);
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canViewSeo = permissions.includes("seo.view");
    const canUseStudio = permissions.includes("studio.view");
    const form = useForm<FormData>({
        title: document?.title ?? "", slug: document?.slug ?? "", type: document?.type ?? initialType ?? (types[0]?.key ?? "document"),
        status: document?.status ?? "draft", workflow_status: document?.workflow_status ?? "draft", assigned_to: document?.assigned_to ?? null,
        reviewer_id: document?.reviewer_id ?? null, review_due_at: document?.review_due_at ?? "", excerpt: document?.excerpt ?? "",
        content: document?.content ?? emptyContent(), lock_version: document?.lock_version ?? 1,
    });
    const commentForm = useForm({ body: "" });
    const documentError = (form.errors as Record<string, string | undefined>).document;
    const stats = useMemo(() => documentStats(form.data.content as DocumentContent), [form.data.content]);
    const [autosaveState, setAutosaveState] = useState<AutosaveState>(latestAutosave ? "saved" : "idle");
    const [autosavedAt, setAutosavedAt] = useState<string | null>(latestAutosave?.saved_at ?? document?.autosaved_at ?? null);
    const lastAutosaved = useRef("");
    const lastAttempted = useRef("");
    const autosaveSignature = useMemo(() => JSON.stringify({
        title: form.data.title, slug: form.data.slug, excerpt: form.data.excerpt, content: form.data.content, workflow_status: form.data.workflow_status,
    }), [form.data.title, form.data.slug, form.data.excerpt, form.data.content, form.data.workflow_status]);

    useEffect(() => {
        lastAutosaved.current = autosaveSignature;
        setAutosaveState(latestAutosave ? "saved" : "idle");
        setAutosavedAt(latestAutosave?.saved_at ?? document?.autosaved_at ?? null);
    }, [document?.lock_version, document?.revisions_count]);

    useEffect(() => {
        if (!editing || !document || !form.data.title.trim() || autosaveState === "saving" || autosaveState === "conflict" || autosaveSignature === lastAutosaved.current || (autosaveState === "error" && autosaveSignature === lastAttempted.current)) return;
        const timer = window.setTimeout(async () => {
            setAutosaveState("saving");
            lastAttempted.current = autosaveSignature;
            try {
                const response = await fetch(`/admin/documents/${document.id}/autosave`, {
                    method: "PUT", credentials: "same-origin",
                    headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": csrfToken() },
                    body: JSON.stringify({
                        base_lock_version: document.lock_version, base_revision: document.revisions_count, title: form.data.title, slug: form.data.slug,
                        excerpt: form.data.excerpt, content: form.data.content, workflow_status: form.data.workflow_status, metadata: {},
                    }),
                });
                const payload = await response.json();
                if (response.status === 409) { setAutosaveState("conflict"); return; }
                if (!response.ok) throw new Error(payload?.message ?? "Autosave failed");
                lastAutosaved.current = autosaveSignature;
                lastAttempted.current = autosaveSignature;
                setAutosavedAt(payload.saved_at ?? new Date().toISOString());
                setAutosaveState("saved");
            } catch { setAutosaveState("error"); }
        }, 2500);
        return () => window.clearTimeout(timer);
    }, [autosaveSignature, autosaveState, document, editing, form.data]);

    const submit = () => editing
        ? form.put(`/admin/documents/${document!.id}`, { preserveScroll: true, preserveState: false })
        : form.post("/admin/documents");
    const personOptions = [{ value: "", label: "Unassigned" }, ...people.map((person) => ({ value: String(person.id), label: person.name, description: person.email }))];
    const openComments = reviewComments.filter((comment) => comment.status === "open").length;
    const autosaveTone = autosaveState === "saved" ? "success" : autosaveState === "conflict" || autosaveState === "error" ? "danger" : autosaveState === "saving" ? "brand" : "neutral";
    const autosaveLabel = autosaveState === "saving" ? "Autosaving…" : autosaveState === "saved" ? `Autosaved ${formatTime(autosavedAt)}` : autosaveState === "conflict" ? "Autosave paused: newer server version" : autosaveState === "error" ? "Autosave failed" : "Autosave ready";

    return (
        <AdminLayout>
            <Head title={editing ? `Write · ${document!.title}` : "New document"} />
            <PageHeader
                eyebrow="Nexora Writer"
                title={editing ? document!.title : "Create document"}
                description="Write with semantic blocks, conflict-safe autosave, editorial ownership and immutable revision history."
                actions={<div className="flex flex-wrap gap-2">{editing && <ButtonLink href={`/admin/documents/${document!.id}/revisions`} variant="secondary" leadingIcon={<Icon name="history" className="h-4 w-4" />}>Revision history</ButtonLink>}{editing && canViewSeo && <ButtonLink href={`/admin/seo/documents/${document!.id}`} variant="secondary" leadingIcon={<Icon name="search" className="h-4 w-4" />}>SEO & discovery</ButtonLink>}{editing && ["article","blog_post"].includes(document!.type) && <ButtonLink href={`/admin/publishing/articles/${document!.id}/settings`} variant="secondary" leadingIcon={<Icon name="settings" className="h-4 w-4" />}>Publishing</ButtonLink>}{editing && canUseStudio && <ButtonLink href={`/admin/studio?search=${encodeURIComponent(document!.title)}`} variant="secondary" leadingIcon={<Icon name="studio" className="h-4 w-4" />}>Open Studio</ButtonLink>}<ButtonLink href="/admin/documents" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4" />}>Back to documents</ButtonLink></div>}
            />

            {documentError && <Card className="mb-5 border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/25 dark:text-red-300">{documentError}</Card>}

            {editing && latestAutosave && latestAutosave.base_lock_version === document!.lock_version && latestAutosave.base_revision === document!.revisions_count && <Card className="mb-5 border-[var(--nx-brand-200)] bg-[var(--nx-brand-soft)] p-4"><div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div className="flex gap-3"><Icon name="history" className="mt-0.5 h-5 w-5 text-[var(--nx-brand-600)]" /><div><p className="text-sm font-semibold text-[var(--nx-text)]">Recover autosaved work</p><p className="mt-1 text-sm text-[var(--nx-text-secondary)]">An autosaved draft from {formatTime(latestAutosave.saved_at)} is available. It has not created a permanent revision yet.</p></div></div><Button type="button" size="sm" variant="secondary" onClick={() => { form.setData("title", latestAutosave.title); form.setData("slug", latestAutosave.slug ?? ""); form.setData("excerpt", latestAutosave.excerpt ?? ""); form.setData("content", latestAutosave.content); form.setData("workflow_status", latestAutosave.workflow_status); setAutosaveState("saved"); }}>Restore autosave</Button></div></Card>}
            {autosaveState === "conflict" && <Card className="mb-5 border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/25"><div className="flex gap-3"><Icon name="alert" className="mt-0.5 h-5 w-5 text-amber-600" /><div><p className="text-sm font-semibold text-amber-900 dark:text-amber-200">Newer server version detected</p><p className="mt-1 text-sm text-amber-800 dark:text-amber-300">Autosave stopped to protect newer work. Reload the editor before making another saved revision.</p><Button type="button" size="sm" variant="secondary" className="mt-3" onClick={() => window.location.reload()} leadingIcon={<Icon name="refresh" className="h-4 w-4" />}>Reload document</Button></div></div></Card>}

            <form onSubmit={(event) => { event.preventDefault(); submit(); }} className="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_23rem]">
                <div className="grid gap-5">
                    <Card className="p-5 sm:p-6"><div className="grid gap-5">
                        <Input label="Title" name="title" value={form.data.title} onChange={(event) => form.setData("title", event.target.value)} error={form.errors.title} autoFocus />
                        <div className="grid gap-4 lg:grid-cols-2"><Input label="Slug" name="slug" value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value.toLowerCase())} error={form.errors.slug} hint="Optional until publishing. Lowercase letters, numbers and hyphens only." /><Select label="Document type" value={form.data.type} onChange={(value) => form.setData("type", value)} options={types.map((item) => ({ value: item.key, label: item.name, description: item.description }))} error={form.errors.type} /></div>
                        <Textarea label="Excerpt" name="excerpt" value={form.data.excerpt} onChange={(event) => form.setData("excerpt", event.target.value)} error={form.errors.excerpt} hint="Reusable summary for search, previews and distribution adapters." maxLength={1000} rows={4} />
                    </div></Card>
                    <BlockEditor value={form.data.content as DocumentContent} definitions={blocks} mediaAssets={mediaAssets} onChange={(content) => form.setData("content", content)} />
                </div>

                <div className="grid h-fit gap-4 2xl:sticky 2xl:top-[calc(var(--nx-header-height)+2rem)]">
                    <Card className="p-5 sm:p-6"><div className="grid gap-5">
                        <div className="flex items-start justify-between gap-3"><div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Editorial workflow</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Ownership & stage</h2></div>{editing && <Badge tone={autosaveTone}>{autosaveLabel}</Badge>}</div>
                        <Select label="Editorial stage" value={form.data.workflow_status} onChange={(value) => form.setData("workflow_status", value)} options={workflowStates.map((item) => ({ value: item.key, label: item.name, description: item.description }))} error={form.errors.workflow_status} />
                        <Select label="Assigned writer" value={form.data.assigned_to ? String(form.data.assigned_to) : ""} onChange={(value) => form.setData("assigned_to", value ? Number(value) : null)} options={personOptions} error={form.errors.assigned_to} />
                        <Select label="Reviewer" value={form.data.reviewer_id ? String(form.data.reviewer_id) : ""} onChange={(value) => form.setData("reviewer_id", value ? Number(value) : null)} options={personOptions} error={form.errors.reviewer_id} />
                        <DateTimePicker label="Review due" value={form.data.review_due_at} onChange={(value) => form.setData("review_due_at", value)} error={form.errors.review_due_at} />
                        <Select label="Publication status" value={form.data.status} onChange={(value) => form.setData("status", value as FormData["status"])} options={[{ value: "draft", label: "Draft", description: "Not publicly published." }, { value: "published", label: "Published", description: "Available to publishing renderers." }, { value: "archived", label: "Archived", description: "Retained but inactive." }]} error={form.errors.status} />
                        {editing && <div className="grid gap-3 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"><div className="flex items-center justify-between"><span className="text-xs font-medium text-[var(--nx-text-muted)]">Revision snapshots</span><Badge tone="brand">{document!.revisions_count}</Badge></div><div className="flex items-center justify-between"><span className="text-xs font-medium text-[var(--nx-text-muted)]">Open review comments</span><Badge tone={openComments > 0 ? "warning" : "success"}>{openComments}</Badge></div></div>}
                        <Button type="submit" loading={form.processing} disabled={!form.isDirty && editing} leadingIcon={<Icon name="check" className="h-4 w-4" />}>{editing ? "Save new revision" : "Create document"}</Button>
                    </div></Card>

                    {editing && <Card className="p-5 sm:p-6"><div className="flex items-center justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Editorial review</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Review comments</h2></div><Badge tone={openComments ? "warning" : "success"}>{openComments} open</Badge></div>
                        <Textarea className="mt-4" label="Add review comment" value={commentForm.data.body} onChange={(event) => commentForm.setData("body", event.target.value)} error={commentForm.errors.body} rows={3} placeholder="Flag a factual, editorial or structural issue…" />
                        <Button type="button" size="sm" className="mt-3" loading={commentForm.processing} disabled={!commentForm.data.body.trim()} onClick={() => commentForm.post(`/admin/documents/${document!.id}/review-comments`, { preserveScroll: true, onSuccess: () => commentForm.reset() })} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>Add comment</Button>
                        <div className="mt-5 grid gap-3">{reviewComments.length === 0 ? <p className="rounded-xl border border-dashed border-[var(--nx-border)] p-4 text-sm text-[var(--nx-text-muted)]">No review comments yet.</p> : reviewComments.slice(0, 8).map((comment) => <div key={comment.id} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3"><div className="flex items-start justify-between gap-3"><div><p className="text-xs font-semibold text-[var(--nx-text)]">{comment.author}</p><p className="mt-0.5 text-[11px] text-[var(--nx-text-muted)]">{formatTime(comment.created_at)}</p></div><Badge tone={comment.status === "resolved" ? "success" : "warning"}>{comment.status === "resolved" ? "Resolved" : "Open"}</Badge></div><p className="mt-2 whitespace-pre-wrap text-sm leading-5 text-[var(--nx-text-secondary)]">{comment.body}</p>{comment.status === "open" && <div className="mt-3 flex justify-end"><IconButton label="Resolve review comment" onClick={() => router.patch(`/admin/documents/${document!.id}/review-comments/${comment.id}/resolve`, {}, { preserveScroll: true })}><Icon name="check" className="h-4 w-4" /></IconButton></div>}</div>)}</div>
                    </Card>}

                    <Card className="p-5 sm:p-6"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Writer insights</p><div className="mt-4 grid grid-cols-2 gap-3">{[{ label: "Words", value: stats.words }, { label: "Reading time", value: `${stats.readingMinutes} min` }, { label: "Blocks", value: stats.blocks }, { label: "Headings", value: stats.headings }].map((item) => <div key={item.label} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3"><div className="text-xs text-[var(--nx-text-muted)]">{item.label}</div><div className="mt-1 text-lg font-semibold text-[var(--nx-text)]">{item.value}</div></div>)}</div></Card>
                </div>
            </form>
        </AdminLayout>
    );
}
