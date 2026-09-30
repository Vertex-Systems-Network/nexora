import { FormEvent, useState } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { EmptyState } from "@admin/components/LoadingStates";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { Badge, Button, Card, Input } from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";

type Log = {
  id:number; event:string; user:{id:number;name:string;email:string}|null;
  subjectType:string|null; subjectId:string|null; ipAddress:string|null; requestId:string|null;
  metadata:Record<string,unknown>|null; createdAt:string|null;
};
type Incident = {
  id:string; severity:string; category:string; code:string; requestId:string|null;
  routeName:string|null; method:string|null; statusCode:number|null; durationMs:number|null;
  nodeKey:string|null; occurredAt:string|null;
};
type Props = {
  logs:Paginator<Log>;
  filters:{search:string};
  incidents:Incident[];
  incidentSummary:{last24h:number;failures24h:number;slow24h:number};
};

const when = (value?:string|null) => value
  ? new Intl.DateTimeFormat(undefined,{dateStyle:"medium",timeStyle:"short"}).format(new Date(value))
  : "—";

export default function AuditIndex({logs,filters,incidents,incidentSummary}:Props){
  const [search,setSearch]=useState(filters.search);
  const [loading,setLoading]=useState(false);
  const submit=(e:FormEvent)=>{
    e.preventDefault();
    setLoading(true);
    router.get("/admin/audit",{search:search||undefined},{preserveState:true,replace:true,onFinish:()=>setLoading(false)});
  };

  const cols:Column<Log>[]=[
    {key:"event",label:"Event",render:l=><div><div className="font-semibold text-[var(--nx-text)]">{l.event}</div><div className="mt-1 text-xs text-[var(--nx-text-muted)]">#{l.id}</div></div>},
    {key:"actor",label:"Actor",render:l=>l.user?<div><div className="text-sm font-medium text-[var(--nx-text)]">{l.user.name}</div><div className="text-xs text-[var(--nx-text-muted)]">{l.user.email}</div></div>:<Badge>System</Badge>},
    {key:"request",label:"Request ID",render:l=><code className="break-all text-xs text-[var(--nx-text-muted)]">{l.requestId??"—"}</code>},
    {key:"subject",label:"Subject",render:l=><span className="text-sm text-[var(--nx-text-secondary)]">{l.subjectType?`${l.subjectType.split("\\").pop()} #${l.subjectId??"—"}`:"—"}</span>},
    {key:"ip",label:"IP",render:l=><code className="text-xs text-[var(--nx-text-muted)]">{l.ipAddress??"—"}</code>},
    {key:"time",label:"Time",render:l=><span className="text-sm text-[var(--nx-text-secondary)]">{when(l.createdAt)}</span>},
  ];

  return <AdminLayout>
    <Head title="Audit & Incidents"/>
    <PageHeader eyebrow="Security & observability" title="Audit & Incidents" description="Tenant-scoped audit history and privacy-minimal operational incident correlation by request ID."/>

    <div className="grid gap-4 md:grid-cols-3">
      <Card className="p-4"><p className="text-xs uppercase tracking-wide text-[var(--nx-text-muted)]">Incidents · 24h</p><p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">{incidentSummary.last24h}</p></Card>
      <Card className="p-4"><p className="text-xs uppercase tracking-wide text-[var(--nx-text-muted)]">HTTP failures · 24h</p><p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">{incidentSummary.failures24h}</p></Card>
      <Card className="p-4"><p className="text-xs uppercase tracking-wide text-[var(--nx-text-muted)]">Slow requests · 24h</p><p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">{incidentSummary.slow24h}</p></Card>
    </div>

    <Card className="mt-5 overflow-hidden">
      <div className="border-b border-[var(--nx-border)] p-5">
        <h2 className="font-semibold text-[var(--nx-text)]">Recent incidents</h2>
        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">Only failed or slow requests are retained. Request payloads, query values and arbitrary headers are not stored.</p>
      </div>
      <div className="divide-y divide-[var(--nx-border)]">
        {incidents.map(i=><div key={i.id} className="grid gap-2 p-4 lg:grid-cols-[140px_1fr_220px] lg:items-center">
          <div className="flex items-center gap-2"><Badge tone={i.severity==="error"?"danger":"warning"}>{i.code}</Badge><span className="text-xs text-[var(--nx-text-muted)]">{i.statusCode??"—"}</span></div>
          <div><p className="text-sm font-medium text-[var(--nx-text)]">{i.method??"—"} · {i.routeName??"Unnamed route"}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{i.durationMs??0} ms · node {i.nodeKey??"—"}</p></div>
          <div className="text-xs text-[var(--nx-text-muted)]"><code className="break-all">{i.requestId??"—"}</code><div className="mt-1">{when(i.occurredAt)}</div></div>
        </div>)}
        {incidents.length===0&&<p className="p-5 text-sm text-[var(--nx-text-muted)]">No incidents match this tenant/filter.</p>}
      </div>
    </Card>

    <div className="mt-5">
      <DataTable loading={loading} rows={logs.data} columns={cols} paginator={logs} empty={<EmptyState title="No audit events" description="No events match this filter."/>} toolbar={<form className="flex max-w-xl gap-2" onSubmit={submit}><Input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search event, subject, IP or request ID" leadingIcon={<Icon name="search" className="h-4 w-4"/>}/><Button variant="secondary" type="submit" loading={loading}>Search</Button></form>}/>
    </div>
  </AdminLayout>;
}
