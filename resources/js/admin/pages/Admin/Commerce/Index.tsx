import { Head } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Card } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";

type Props={summary:{products:number;customers:number;orders:number;open_invoices:number;active_subscriptions:number;revenue:string;currency:string};recentOrders:Array<{id:string;number:string;status:string;customer:string;currency:string;total:string;created_at:string|null}>;providers:Array<{key:string;label:string;capabilities:string[]}>};
const human=(v:string)=>v.replace(/[_-]+/g," ").replace(/\b\w/g,c=>c.toUpperCase());
export default function CommerceIndex({summary,recentOrders,providers}:Props){
 const stats=[
  ["Active products",summary.products,"package"],["Customers",summary.customers,"users"],["Orders",summary.orders,"shopping-cart"],
  ["Open invoices",summary.open_invoices,"file-text"],["Active subscriptions",summary.active_subscriptions,"refresh-cw"],[`Net recorded revenue (${summary.currency})`,summary.revenue,"wallet"],
 ] as const;
 return <AdminLayout><Head title="Commerce"/><PageHeader eyebrow="Commerce & billing" title="Commerce" description="Provider-neutral catalog, orders, invoices, payments and subscriptions. Payment gateways are registered by verified extensions, not hard-coded into Core."/>
 <CommerceNav current="/admin/commerce"/>
 <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">{stats.map(([label,value,icon])=><Card key={label} className="p-5"><div className="flex items-center gap-3"><span className="grid h-10 w-10 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name={icon} className="h-4 w-4"/></span><div><p className="text-xs text-[var(--nx-text-muted)]">{label}</p><p className="mt-1 text-xl font-semibold text-[var(--nx-text)]">{value}</p></div></div></Card>)}</div>
 <div className="mt-5 grid gap-5 xl:grid-cols-[1.6fr_1fr]"><Card className="p-5 sm:p-6"><h2 className="font-semibold text-[var(--nx-text)]">Recent orders</h2><div className="mt-4 grid gap-2">{recentOrders.length===0?<p className="py-8 text-center text-sm text-[var(--nx-text-muted)]">No orders yet.</p>:recentOrders.map(o=><div key={o.id} className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[var(--nx-border)] p-3"><div><p className="font-semibold text-[var(--nx-text)]">{o.number}</p><p className="text-xs text-[var(--nx-text-muted)]">{o.customer}</p></div><div className="flex items-center gap-3"><Badge>{human(o.status)}</Badge><span className="text-sm font-semibold text-[var(--nx-text)]">{o.total}</span></div></div>)}</div></Card>
 <Card className="p-5 sm:p-6"><h2 className="font-semibold text-[var(--nx-text)]">Payment providers</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Provider adapters appear only when an enabled verified extension registers them.</p><div className="mt-4 grid gap-2">{providers.length===0?<div className="rounded-xl border border-dashed border-[var(--nx-border)] p-4 text-sm text-[var(--nx-text-muted)]">No payment-provider extension registered. Commerce records remain usable without storing gateway secrets in Core.</div>:providers.map(p=><div key={p.key} className="rounded-xl border border-[var(--nx-border)] p-3"><p className="font-semibold text-[var(--nx-text)]">{p.label}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{p.capabilities.map(human).join(" · ")}</p></div>)}</div></Card></div>
 </AdminLayout>;
}
