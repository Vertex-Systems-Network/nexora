import { useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, IconLink, Input, TextLink } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type SeoRow = { id:number; title:string; slug:string|null; status:string; seo_title:string; canonical_url:string|null; url_path:string|null; robots_index:boolean; sitemap_include:boolean; issues_count:number; high_issues:number; updated_at:string|null };
type Summary = { configured:number; indexable:number; excluded:number; sitemap:number };

export default function SeoIndex({ documents, filters, summary }: { documents:Paginator<SeoRow>; filters:{search:string}; summary:Summary }) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("seo.manage");
    const [search, setSearch] = useState(filters.search ?? "");
    const apply = () => router.get("/admin/seo", { search }, { preserveScroll:true, preserveState:true, replace:true });
    const columns: Column<SeoRow>[] = [
        { key:"document", label:"Document", render:(row)=><div className="min-w-64"><TextLink href={`/admin/seo/documents/${row.id}`} tone="neutral">{row.title}</TextLink><p className="mt-1 truncate text-xs text-[var(--nx-text-muted)]">{row.canonical_url || row.url_path || "No public URL configured"}</p></div> },
        { key:"indexing", label:"Indexing", render:(row)=><div className="flex flex-wrap gap-1.5"><Badge tone={row.robots_index?"success":"warning"}>{row.robots_index?"Indexable":"Noindex"}</Badge>{row.sitemap_include&&<Badge tone="brand">Sitemap</Badge>}</div> },
        { key:"issues", label:"Audit", render:(row)=>row.issues_count===0?<Badge tone="success"><span className="inline-flex items-center gap-1.5"><Icon name="success" className="h-3.5 w-3.5"/>No current issues</span></Badge>:<Badge tone={row.high_issues>0?"danger":"warning"}>{row.issues_count} issue{row.issues_count===1?"":"s"}</Badge> },
        { key:"updated", label:"Updated", render:(row)=><span className="text-sm text-[var(--nx-text-secondary)]">{row.updated_at?new Intl.DateTimeFormat(undefined,{dateStyle:"medium"}).format(new Date(row.updated_at)):"—"}</span> },
        { key:"actions", label:"", className:"text-right", render:(row)=><div className="flex justify-end"><IconLink href={`/admin/seo/documents/${row.id}`} label={`SEO settings for ${row.title}`}><Icon name="search" className="h-4 w-4"/></IconLink></div> },
    ];

    return <AdminLayout>
        <Head title="SEO & Discovery"/>
        <PageHeader eyebrow="Discovery" title="SEO & Discovery" description="Manage canonical metadata, indexing policy, Schema Graph output, sitemaps and internal-link opportunities without coupling SEO data to the active theme." actions={canManage?<ButtonLink href="/admin/seo/settings" variant="secondary" leadingIcon={<Icon name="settings" className="h-4 w-4"/>}>SEO settings</ButtonLink>:undefined}/>
        <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            {[{label:"Configured resources",value:summary.configured,icon:"search"},{label:"Indexable",value:summary.indexable,icon:"success"},{label:"Excluded",value:summary.excluded,icon:"alert"},{label:"Sitemap eligible",value:summary.sitemap,icon:"globe"}].map((item)=><Card key={item.label} className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name={item.icon} className="h-4 w-4"/></span><div><p className="text-xs text-[var(--nx-text-muted)]">{item.label}</p><p className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">{item.value}</p></div></div></Card>)}
        </div>
        <DataTable rows={documents.data} columns={columns} paginator={documents} toolbar={<div className="flex flex-col gap-3 sm:flex-row"><Input className="min-w-0 flex-1" placeholder="Search documents…" value={search} onChange={(event)=>setSearch(event.target.value)} onKeyDown={(event)=>event.key==="Enter"&&apply()}/><Button type="button" variant="secondary" onClick={apply} leadingIcon={<Icon name="search" className="h-4 w-4"/>}>Search</Button></div>} empty={<EmptyState title="No documents available" description="SEO metadata attaches to publishable Nexora resources through stable contracts. Create a document first."/>}/>
    </AdminLayout>;
}
