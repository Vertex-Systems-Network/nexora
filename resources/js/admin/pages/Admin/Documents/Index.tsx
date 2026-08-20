import { useMemo, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, IconButton, IconLink, Input, Select, TextLink } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type DocumentRow = { id:number; uuid:string; title:string; slug:string|null; type:string; status:"draft"|"published"|"archived"; workflow_status:string; author:string|null; revisions_count:number; published_at:string|null; updated_at:string|null };
type TypeOption = { key:string; name:string; description:string; icon:string };
type Filters = { search:string; status:string; type:string };

const tone = (status: DocumentRow["status"]): "success" | "warning" | "neutral" => status === "published" ? "success" : status === "draft" ? "warning" : "neutral";
const statusLabel = (status: DocumentRow["status"]) => status === "published" ? "Published" : status === "draft" ? "Draft" : "Archived";

export default function DocumentsIndex({ documents, filters, types, workflowStates }: { documents:Paginator<DocumentRow>; filters:Filters; types:TypeOption[]; workflowStates:Array<{key:string;name:string;tone:string}> }) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canCreate = permissions.includes("documents.create");
    const canUpdate = permissions.includes("documents.update");
    const canDelete = permissions.includes("documents.delete");
    const canViewRevisions = permissions.includes("documents.revisions.view");
    const canViewSeo = permissions.includes("seo.view");
    const [search, setSearch] = useState(filters.search ?? "");
    const [status, setStatus] = useState(filters.status ?? "");
    const [type, setType] = useState(filters.type ?? "");
    const [deleteTarget, setDeleteTarget] = useState<DocumentRow | null>(null);
    const [deleting, setDeleting] = useState(false);

    const typeMap = useMemo(() => Object.fromEntries(types.map((item) => [item.key, item.name])), [types]);
    const workflowMap = useMemo(() => Object.fromEntries(workflowStates.map((item) => [item.key, item.name])), [workflowStates]);
    const apply = (next: Partial<Filters> = {}) => router.get("/admin/documents", { search, status, type, ...next }, { preserveState: true, preserveScroll: true, replace: true });

    const columns: Column<DocumentRow>[] = [
        { key:"title", label:"Document", render:(document)=><div className="min-w-64"><div className="flex items-center gap-2"><span className="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name="file-text" className="h-4 w-4"/></span><div className="min-w-0"><TextLink href={`/admin/documents/${document.id}/edit`} tone="neutral">{document.title}</TextLink><p className="mt-0.5 truncate text-xs text-[var(--nx-text-muted)]">{document.slug ? `/${document.slug}` : document.uuid}</p></div></div></div> },
        { key:"type", label:"Type", render:(document)=><span className="text-sm text-[var(--nx-text-secondary)]">{typeMap[document.type] ?? document.type}</span> },
        { key:"status", label:"Status", render:(document)=><div className="flex flex-wrap gap-1.5"><Badge tone={tone(document.status)}>{statusLabel(document.status)}</Badge><Badge>{workflowMap[document.workflow_status] ?? document.workflow_status.replaceAll("_", " ")}</Badge></div> },
        { key:"revisions", label:"Revisions", render:(document)=><span className="text-sm font-semibold text-[var(--nx-text)]">{document.revisions_count}</span> },
        { key:"updated", label:"Updated", render:(document)=><span className="text-sm text-[var(--nx-text-secondary)]">{document.updated_at ? new Intl.DateTimeFormat(undefined,{dateStyle:"medium",timeStyle:"short"}).format(new Date(document.updated_at)) : "—"}</span> },
        { key:"actions", label:"", className:"text-right", render:(document)=><div className="flex justify-end gap-1">{canUpdate&&<IconLink href={`/admin/documents/${document.id}/edit`} label={`Edit ${document.title}`}><Icon name="edit" className="h-4 w-4"/></IconLink>}{canViewRevisions&&<IconLink href={`/admin/documents/${document.id}/revisions`} label={`Revision history for ${document.title}`}><Icon name="history" className="h-4 w-4"/></IconLink>}{canViewSeo&&<IconLink href={`/admin/seo/documents/${document.id}`} label={`SEO settings for ${document.title}`}><Icon name="search" className="h-4 w-4"/></IconLink>}{canDelete&&<IconButton label={`Delete ${document.title}`} tone="danger" onClick={()=>setDeleteTarget(document)}><Icon name="trash" className="h-4 w-4"/></IconButton>}</div> },
    ];

    return <AdminLayout>
        <Head title="Documents"/>
        <PageHeader eyebrow="Document engine" title="Documents" description="Universal structured documents are the publishing foundation for articles, research, documentation and extension-defined content types." actions={canCreate?<ButtonLink href="/admin/documents/create" leadingIcon={<Icon name="plus" className="h-4 w-4"/>}>New document</ButtonLink>:undefined}/>
        <DataTable rows={documents.data} columns={columns} paginator={documents} toolbar={<div className="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_13rem_13rem_auto]"><Input name="search" placeholder="Search documents…" value={search} onChange={(event)=>setSearch(event.target.value)} onKeyDown={(event)=>event.key==="Enter"&&apply()}/><Select ariaLabel="Filter by status" value={status} onChange={(value)=>{setStatus(value);apply({status:value})}} options={[{value:"",label:"All statuses"},{value:"draft",label:"Draft"},{value:"published",label:"Published"},{value:"archived",label:"Archived"}]}/><Select ariaLabel="Filter by type" value={type} onChange={(value)=>{setType(value);apply({type:value})}} options={[{value:"",label:"All document types"},...types.map((item)=>({value:item.key,label:item.name,description:item.description}))]}/><Button type="button" variant="secondary" onClick={()=>apply()} leadingIcon={<Icon name="search" className="h-4 w-4"/>}>Search</Button></div>} empty={<EmptyState title="No documents yet" description="Create the first structured document. Writer, Blog and external publishing extensions build on the same document engine."/>}/>
        <ConfirmDialog open={!!deleteTarget} title="Delete document" description={deleteTarget?`Delete “${deleteTarget.title}” and all of its revisions?`:""} confirmLabel="Delete document" processing={deleting} onCancel={()=>setDeleteTarget(null)} onConfirm={()=>{if(!deleteTarget)return;setDeleting(true);router.delete(`/admin/documents/${deleteTarget.id}`,{onFinish:()=>{setDeleting(false);setDeleteTarget(null)}})}}/>
    </AdminLayout>;
}
