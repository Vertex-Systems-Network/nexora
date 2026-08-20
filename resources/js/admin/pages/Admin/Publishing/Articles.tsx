import { useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, IconLink, Input, Select } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type Row={id:number;title:string;slug:string|null;type:"article"|"blog_post";status:"draft"|"published"|"archived";workflow_status:string;published_at:string|null;scheduled_at:string|null;featured:boolean;authors:string[];terms:string[];updated_at:string|null};
type Filters={search:string;type:string;status:string};
const fmt=(v:string|null)=>v?new Intl.DateTimeFormat(undefined,{dateStyle:"medium",timeStyle:"short"}).format(new Date(v)):"—";

export default function Articles({articles,filters,summary}:{articles:Paginator<Row>;filters:Filters;summary:{total:number;published:number;scheduled:number;featured:number}}){
 const perms=usePage<SharedPageProps>().props.auth.user?.permissions??[]; const canManage=perms.includes("publishing.manage");
 const [search,setSearch]=useState(filters.search??""); const [type,setType]=useState(filters.type??""); const [status,setStatus]=useState(filters.status??"");
 const apply=(next:Partial<Filters>={})=>router.get("/admin/publishing/articles",{search,type,status,...next},{preserveState:true,replace:true});
 const columns:Column<Row>[]=[
  {key:"title",label:"Article",render:r=><div className="min-w-64"><div className="font-semibold text-[var(--nx-text)]">{r.title}</div><div className="mt-1 flex flex-wrap gap-1.5"><Badge>{r.type==="blog_post"?"Blog post":"Article"}</Badge>{r.featured&&<Badge tone="brand">Featured</Badge>}{r.scheduled_at&&r.status!=="published"&&<Badge tone="warning">Scheduled</Badge>}</div></div>},
  {key:"status",label:"Publishing",render:r=><div><Badge tone={r.status==="published"?"success":r.status==="draft"?"warning":"neutral"}>{r.status[0].toUpperCase()+r.status.slice(1)}</Badge><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.scheduled_at&&r.status!=="published"?`Scheduled ${fmt(r.scheduled_at)}`:r.published_at?`Published ${fmt(r.published_at)}`:"Not scheduled"}</p></div>},
  {key:"authors",label:"Authors",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.authors.length?r.authors.join(", "):"No public byline"}</span>},
  {key:"terms",label:"Topics",render:r=><div className="flex max-w-72 flex-wrap gap-1">{r.terms.length?r.terms.map(t=><Badge key={t}>{t}</Badge>):<span className="text-sm text-[var(--nx-text-muted)]">Uncategorized</span>}</div>},
  {key:"updated",label:"Updated",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{fmt(r.updated_at)}</span>},
  {key:"actions",label:"",className:"text-right",render:r=><div className="flex justify-end gap-1"><IconLink href={`/admin/documents/${r.id}/edit`} label={`Write ${r.title}`}><Icon name="edit" className="h-4 w-4"/></IconLink>{canManage&&<IconLink href={`/admin/publishing/articles/${r.id}/settings`} label={`Publishing settings for ${r.title}`}><Icon name="settings" className="h-4 w-4"/></IconLink>}<IconLink href={`/admin/seo/documents/${r.id}`} label={`SEO for ${r.title}`}><Icon name="search" className="h-4 w-4"/></IconLink></div>}
 ];
 return <AdminLayout><Head title="Blog & Articles"/><PageHeader eyebrow="Publishing" title="Blog & Articles" description="Publish articles and blog posts using Nexora Writer, Editorial, SEO and Theme contracts." actions={<div className="flex flex-wrap gap-2"><ButtonLink href="/admin/documents/create?type=article" leadingIcon={<Icon name="plus" className="h-4 w-4"/>}>New article</ButtonLink><ButtonLink href="/admin/documents/create?type=blog_post" variant="secondary" leadingIcon={<Icon name="plus" className="h-4 w-4"/>}>New blog post</ButtonLink></div>}/>
 <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">{[{l:"Publishing items",v:summary.total,i:"file-text"},{l:"Published",v:summary.published,i:"success"},{l:"Scheduled",v:summary.scheduled,i:"history"},{l:"Featured",v:summary.featured,i:"zap"}].map(x=><Card key={x.l} className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name={x.i} className="h-4 w-4"/></span><div><p className="text-xs text-[var(--nx-text-muted)]">{x.l}</p><p className="text-xl font-semibold text-[var(--nx-text)]">{x.v}</p></div></div></Card>)}</div>
 <div className="mb-5 flex flex-wrap gap-2"><ButtonLink href="/admin/publishing/taxonomy" variant="secondary" size="sm">Categories, topics & tags</ButtonLink><ButtonLink href="/admin/publishing/authors" variant="secondary" size="sm">Authors</ButtonLink><ButtonLink href="/admin/publishing/series" variant="secondary" size="sm">Series</ButtonLink></div>
 <DataTable rows={articles.data} columns={columns} paginator={articles} toolbar={<div className="grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_13rem_13rem_auto]"><Input placeholder="Search articles…" value={search} onChange={e=>setSearch(e.target.value)} onKeyDown={e=>e.key==="Enter"&&apply()}/><Select ariaLabel="Content type" value={type} onChange={v=>{setType(v);apply({type:v})}} options={[{value:"",label:"All types"},{value:"article",label:"Article"},{value:"blog_post",label:"Blog post"}]}/><Select ariaLabel="Publication status" value={status} onChange={v=>{setStatus(v);apply({status:v})}} options={[{value:"",label:"All statuses"},{value:"draft",label:"Draft"},{value:"published",label:"Published"},{value:"archived",label:"Archived"}]}/><Button variant="secondary" onClick={()=>apply()} leadingIcon={<Icon name="search" className="h-4 w-4"/>}>Search</Button></div>} empty={<EmptyState title="No articles yet" description="Create an article or blog post. Nexora will reuse Writer, Editorial, SEO, Studio and Theme infrastructure."/>}/>
 </AdminLayout>;
}
