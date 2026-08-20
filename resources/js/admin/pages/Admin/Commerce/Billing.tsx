import { useMemo } from "react";
import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Card } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";
type Invoice={id:string;number:string;status:string;customer:string;total:string;due:string;issued_at:string|null;due_at:string|null};
type Props={invoices:Paginator<Invoice>;transactions:Array<{id:string;provider:string;type:string;status:string;amount:string;reference:string|null;processed_at:string|null}>;refunds:Array<{id:string;provider:string|null;status:string;amount:string;reason:string|null;created_at:string|null}>;subscriptions:Array<{id:string;customer:string|null;product:string|null;provider:string;status:string;amount:string;interval:string;period_end:string|null}>};
const human=(v:string)=>v.replace(/[_-]+/g," ").replace(/\b\w/g,c=>c.toUpperCase());
export default function Billing({invoices,transactions,refunds,subscriptions}:Props){
 const columns=useMemo<Column<Invoice>[]>(()=>[
  {key:"invoice",label:"Invoice",render:r=><div><p className="font-semibold text-[var(--nx-text)]">{r.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.customer}</p></div>},
  {key:"status",label:"Status",render:r=><Badge tone={r.status==="paid"?"success":r.status==="void"?"neutral":"warning"}>{human(r.status)}</Badge>},
  {key:"total",label:"Total",render:r=><span className="font-semibold text-[var(--nx-text)]">{r.total}</span>},
  {key:"due",label:"Amount due",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.due}</span>},
 ],[]);
 return <AdminLayout><Head title="Commerce Billing"/><PageHeader eyebrow="Commerce" title="Billing" description="Invoices, provider transactions, refunds and subscriptions remain provider-neutral records inside Core."/><CommerceNav current="/admin/commerce/billing"/>
 <DataTable rows={invoices.data} columns={columns} paginator={invoices} empty={<EmptyState title="No invoices" description="Create an invoice from an order to begin billing history."/>}/>
 <div className="mt-5 grid gap-5 xl:grid-cols-3"><Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Recent payment transactions</h2><div className="mt-4 grid gap-2">{transactions.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No provider transaction recorded.</p>:transactions.map(t=><div key={t.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{t.amount}</span><Badge>{human(t.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{human(t.provider)} · {human(t.type)}</p></div>)}</div></Card>
 <Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Recent refunds</h2><div className="mt-4 grid gap-2">{refunds.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No refunds recorded.</p>:refunds.map(r=><div key={r.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{r.amount}</span><Badge>{human(r.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.reason??"No reason supplied"}</p></div>)}</div></Card>
 <Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Subscriptions</h2><div className="mt-4 grid gap-2">{subscriptions.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No provider subscriptions recorded.</p>:subscriptions.map(s=><div key={s.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{s.amount} / {human(s.interval)}</span><Badge>{human(s.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{s.customer??"Customer"} · {s.product??"Product"}</p></div>)}</div></Card></div>
 </AdminLayout>;
}
