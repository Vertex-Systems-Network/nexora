import { useEffect } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card } from "@nexora/admin-ui";

type Run = { id:number; uuid:string; status:string; baseUrl:string; requestedLimit:number; discoveredUrls:number; crawledUrls:number; failedUrls:number; issuesCount:number; highIssuesCount:number; summary:Record<string,number>; startedAt:string|null; completedAt:string|null; error:string|null };
type Issue = { id:number; severity:string; code:string; category:string; title:string; description:string; url:string|null; metadata:Record<string,unknown>|null };
type Page = { id:number; url:string; statusCode:number|null; durationMs:number|null; title:string|null; h1Count:number; wordCount:number; internalLinks:number; externalLinks:number; hasSchema:boolean };
const tone = (s:string):"danger"|"warning"|"neutral" => s==="critical"||s==="high"?"danger":s==="medium"?"warning":"neutral";
export default function Crawl({run,issues,slowestPages}:{run:Run;issues:Paginator<Issue>;slowestPages:Page[]}) {
 useEffect(()=>{ if(!["queued","running","cancel_requested"].includes(run.status)) return; const id=window.setInterval(()=>router.reload({only:["run","issues","slowestPages"]}),5000); return()=>window.clearInterval(id); },[run.status]);
 const columns:Column<Issue>[]=[
  {key:"severity",label:"Severity",render:(row)=><Badge tone={tone(row.severity)}>{row.severity}</Badge>},
  {key:"finding",label:"Finding",render:(row)=><div className="min-w-72"><p className="font-medium text-[var(--nx-text)]">{row.title}</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">{row.description}</p>{row.url&&<p className="mt-1 max-w-xl truncate text-xs text-[var(--nx-text-muted)]">{row.url}</p>}</div>},
  {key:"category",label:"Category",render:(row)=><span className="text-sm capitalize text-[var(--nx-text-secondary)]">{row.category.replaceAll("-"," ")}</span>},
  {key:"code",label:"Rule",render:(row)=><code className="text-xs text-[var(--nx-text-muted)]">{row.code}</code>},
 ];
 return <AdminLayout><Head title={`SEO Crawl ${run.uuid.slice(0,8)}`}/><PageHeader eyebrow="Discovery intelligence" title="SEO Crawl" description={`${run.baseUrl} · ${run.crawledUrls} crawled · ${run.issuesCount} observations. Nexora reports individual evidence and does not collapse findings into a synthetic SEO score.`} actions={<div className="flex flex-wrap gap-2">{["queued","running"].includes(run.status)&&<Button type="button" variant="secondary" onClick={()=>router.post(`/admin/discovery/crawls/${run.id}/cancel`,{}, {preserveScroll:true})}>Cancel crawl</Button>}<Button type="button" variant="secondary" onClick={()=>router.reload({only:["run","issues","slowestPages"]})} leadingIcon={<Icon name="refresh" className="h-4 w-4"/>}>Refresh</Button><ButtonLink href="/admin/discovery" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4"/>}>Back to discovery</ButtonLink></div>}/>
 <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5"><Stat label="Status" value={run.status}/><Stat label="Discovered" value={run.discoveredUrls}/><Stat label="Crawled" value={run.crawledUrls}/><Stat label="Failed" value={run.failedUrls}/><Stat label="High findings" value={run.highIssuesCount}/></div>
 {run.error&&<Card className="mb-5 border-red-200 p-4 dark:border-red-900"><p className="font-semibold text-red-700 dark:text-red-300">Crawler error</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">{run.error}</p></Card>}
 <DataTable rows={issues.data} columns={columns} paginator={issues} empty={<EmptyState title="No crawl observations" description={run.status==="completed"?"The crawler completed without recorded issues.":"Issues will appear as the crawl is processed."}/>}/>
 <Card className="mt-5 p-5"><h2 className="text-base font-semibold text-[var(--nx-text)]">Slowest crawled pages</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Response time is an operational observation, not a Core Web Vitals replacement.</p>{slowestPages.length===0?<div className="mt-4"><EmptyState title="No page data" description="Page measurements appear after the crawler processes HTML URLs."/></div>:<div className="mt-4 divide-y divide-[var(--nx-border)]">{slowestPages.map((page)=><div key={page.id} className="grid gap-2 py-3 sm:grid-cols-[1fr_auto_auto]"><div className="min-w-0"><p className="truncate font-medium text-[var(--nx-text)]">{page.title||page.url}</p><p className="truncate text-xs text-[var(--nx-text-muted)]">{page.url}</p></div><Badge tone={(page.statusCode??0)>=400?"danger":"neutral"}>{page.statusCode??"—"}</Badge><p className="text-sm font-medium text-[var(--nx-text-secondary)]">{page.durationMs??0} ms</p></div>)}</div>}</Card>
 </AdminLayout>;
}
function Stat({label,value}:{label:string;value:string|number}){return <Card className="p-4"><p className="text-xs text-[var(--nx-text-muted)]">{label}</p><p className="mt-1 text-lg font-semibold capitalize text-[var(--nx-text)]">{typeof value==="number"?value.toLocaleString():value}</p></Card>}
