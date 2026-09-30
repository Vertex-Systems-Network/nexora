import { Head } from "@inertiajs/react";
import { Badge, Card } from "@nexora/admin-ui";
import { CustomerPortalLayout } from "@admin/layout/CustomerPortalLayout";

type Customer={id:string;name:string;email:string;phone:string|null};
type MembershipRow={id:string;plan:string;plan_slug:string|null;status:string;effective:boolean;started_at:string|null;trial_ends_at:string|null;ends_at:string|null;subscription_status:string|null;subscription_period_end:string|null};
type OrderRow={id:string;number:string;status:string;items_count:number;total:string;currency:string;placed_at:string|null;created_at:string|null};
type InvoiceRow={id:string;number:string;status:string;total:string;due:string;issued_at:string|null;due_at:string|null;paid_at:string|null};
type SubscriptionRow={id:string;product:string;provider:string;status:string;amount:string;interval:string;current_period_end:string|null;cancel_at_period_end:boolean};
type Props={customer:Customer|null;memberships:MembershipRow[];orders:OrderRow[];invoices:InvoiceRow[];subscriptions:SubscriptionRow[]};

const human=(value:string)=>value.replace(/[_-]+/g," ").replace(/\b\w/g,letter=>letter.toUpperCase());
const when=(value:string|null)=>value?new Intl.DateTimeFormat(undefined,{dateStyle:"medium"}).format(new Date(value)):"—";
const tone=(status:string):"success"|"warning"|"neutral"=>["active","paid","completed","trial"].includes(status)?"success":["pending","pending_payment","past_due"].includes(status)?"warning":"neutral";

export default function Dashboard({customer,memberships,orders,invoices,subscriptions}:Props){
 return <CustomerPortalLayout>
  <Head title="My account" />
  <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Account summary">
   <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Memberships</p><p className="mt-3 text-3xl font-semibold">{memberships.filter(item=>item.effective).length}</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Active or effective plans</p></Card>
   <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Orders</p><p className="mt-3 text-3xl font-semibold">{orders.length}</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Recent orders shown</p></Card>
   <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Invoices</p><p className="mt-3 text-3xl font-semibold">{invoices.length}</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Recent billing records</p></Card>
   <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Subscriptions</p><p className="mt-3 text-3xl font-semibold">{subscriptions.filter(item=>!["cancelled","canceled"].includes(item.status)).length}</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Current provider subscriptions</p></Card>
  </section>

  <section className="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
   <Card className="p-5 sm:p-6">
    <h2 className="text-lg font-semibold">Account profile</h2>
    {customer?<dl className="mt-5 grid gap-4 text-sm"><div><dt className="text-[var(--nx-text-muted)]">Customer</dt><dd className="mt-1 font-medium">{customer.name}</dd></div><div><dt className="text-[var(--nx-text-muted)]">Email</dt><dd className="mt-1 font-medium">{customer.email}</dd></div><div><dt className="text-[var(--nx-text-muted)]">Phone</dt><dd className="mt-1 font-medium">{customer.phone||"Not provided"}</dd></div></dl>:<div className="mt-5 rounded-xl border border-dashed border-[var(--nx-border)] p-4"><p className="font-medium">No Commerce customer profile is linked yet.</p><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Membership access can still appear here when it is linked directly to your user account.</p></div>}
   </Card>
   <Card className="p-5 sm:p-6">
    <h2 className="text-lg font-semibold">Memberships</h2>
    <div className="mt-4 grid gap-3">{memberships.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No membership is linked to this account.</p>:memberships.map(item=><article key={item.id} className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex flex-wrap items-center justify-between gap-3"><div><p className="font-semibold">{item.plan}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Started {when(item.started_at)}{item.ends_at?` · Ends ${when(item.ends_at)}`:""}</p></div><Badge tone={item.effective?"success":"neutral"}>{human(item.status)}</Badge></div>{item.subscription_status&&<p className="mt-3 text-xs text-[var(--nx-text-muted)]">Billing subscription: {human(item.subscription_status)}{item.subscription_period_end?` · Period ends ${when(item.subscription_period_end)}`:""}</p>}</article>)}</div>
   </Card>
  </section>

  <section className="mt-6 grid gap-6 xl:grid-cols-2">
   <Card className="p-5 sm:p-6"><h2 className="text-lg font-semibold">Recent orders</h2><div className="mt-4 grid gap-3">{orders.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No orders are linked to this account.</p>:orders.map(order=><article key={order.id} className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex items-start justify-between gap-3"><div><p className="font-semibold">{order.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{order.items_count} item{order.items_count===1?"":"s"} · {when(order.placed_at??order.created_at)}</p></div><Badge tone={tone(order.status)}>{human(order.status)}</Badge></div><p className="mt-3 text-sm font-semibold">{order.total}</p></article>)}</div></Card>
   <Card className="p-5 sm:p-6"><h2 className="text-lg font-semibold">Invoices</h2><div className="mt-4 grid gap-3">{invoices.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No invoices are linked to this account.</p>:invoices.map(invoice=><article key={invoice.id} className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex items-start justify-between gap-3"><div><p className="font-semibold">{invoice.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Issued {when(invoice.issued_at)}{invoice.due_at?` · Due ${when(invoice.due_at)}`:""}</p></div><Badge tone={tone(invoice.status)}>{human(invoice.status)}</Badge></div><div className="mt-3 flex items-center justify-between gap-3 text-sm"><span className="font-semibold">{invoice.total}</span><span className="text-[var(--nx-text-muted)]">Due {invoice.due}</span></div></article>)}</div></Card>
  </section>

  <Card className="mt-6 p-5 sm:p-6"><h2 className="text-lg font-semibold">Subscriptions</h2><div className="mt-4 grid gap-3 md:grid-cols-2">{subscriptions.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No provider subscriptions are linked to this account.</p>:subscriptions.map(subscription=><article key={subscription.id} className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex items-start justify-between gap-3"><div><p className="font-semibold">{subscription.product}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{subscription.amount} / {human(subscription.interval)} · {human(subscription.provider)}</p></div><Badge tone={tone(subscription.status)}>{human(subscription.status)}</Badge></div><p className="mt-3 text-xs text-[var(--nx-text-muted)]">Period end: {when(subscription.current_period_end)}{subscription.cancel_at_period_end?" · Cancels at period end":""}</p></article>)}</div></Card>
 </CustomerPortalLayout>;
}
