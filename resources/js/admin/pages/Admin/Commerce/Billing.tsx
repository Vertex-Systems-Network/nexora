import { useMemo, useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Button, Card, Input, Modal, Select } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";

type Invoice={id:string;number:string;status:string;customer:string;total:string;due:string;currency:string;can_collect:boolean;issued_at:string|null;due_at:string|null};
type Transaction={id:string;provider:string;type:string;status:string;amount:string;refundable:string;currency:string;reference:string|null;can_refund:boolean;processed_at:string|null};
type Provider={key:string;label:string;capabilities:string[];health:string|null};
type Props={
 invoices:Paginator<Invoice>;
 transactions:Transaction[];
 refunds:Array<{id:string;provider:string|null;status:string;amount:string;reason:string|null;created_at:string|null}>;
 subscriptions:Array<{id:string;customer:string|null;product:string|null;provider:string;status:string;amount:string;interval:string;period_end:string|null}>;
 providers:Provider[];
 canManage:boolean;
};

const human=(v:string)=>v.replace(/[_-]+/g," ").replace(/\b\w/g,c=>c.toUpperCase());
const operationKey=()=>globalThis.crypto?.randomUUID?.()??`billing-${Date.now()}-${Math.random().toString(36).slice(2)}`;

export default function Billing({invoices,transactions,refunds,subscriptions,providers,canManage}:Props){
 const [paymentInvoice,setPaymentInvoice]=useState<Invoice|null>(null);
 const [refundPayment,setRefundPayment]=useState<Transaction|null>(null);
 const paymentProviders=providers.filter(provider=>provider.capabilities.includes("payments"));
 const paymentForm=useForm({provider_key:"",idempotency_key:""});
 const refundForm=useForm({amount:"",reason:"",idempotency_key:""});

 const openPayment=(invoice:Invoice)=>{
  setPaymentInvoice(invoice);
  paymentForm.setData("provider_key",paymentProviders[0]?.key??"");
  paymentForm.setData("idempotency_key",operationKey());
 };
 const openRefund=(payment:Transaction)=>{
  setRefundPayment(payment);
  refundForm.setData("amount","");
  refundForm.setData("reason","");
  refundForm.setData("idempotency_key",operationKey());
 };
 const closePayment=()=>{setPaymentInvoice(null);paymentForm.clearErrors()};
 const closeRefund=()=>{setRefundPayment(null);refundForm.clearErrors()};

 const columns=useMemo<Column<Invoice>[]>(()=>[
  {key:"invoice",label:"Invoice",render:r=><div><p className="font-semibold text-[var(--nx-text)]">{r.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.customer}</p></div>},
  {key:"status",label:"Status",render:r=><Badge tone={r.status==="paid"?"success":r.status==="void"?"neutral":"warning"}>{human(r.status)}</Badge>},
  {key:"total",label:"Total",render:r=><span className="font-semibold text-[var(--nx-text)]">{r.total}</span>},
  {key:"due",label:"Amount due",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.due}</span>},
  {key:"actions",label:"",className:"w-40 text-right",render:r=>canManage&&r.can_collect&&paymentProviders.length>0?<Button size="sm" variant="secondary" onClick={()=>openPayment(r)}>Collect payment</Button>:null},
 ],[canManage,paymentProviders]);

 return <AdminLayout><Head title="Commerce Billing"/><PageHeader eyebrow="Commerce" title="Billing" description="Invoices remain provider-neutral in Core while enabled extensions execute payments and refunds through explicit capability contracts."/><CommerceNav current="/admin/commerce/billing"/>
 <DataTable rows={invoices.data} columns={columns} paginator={invoices} empty={<EmptyState title="No invoices" description="Create an invoice from an order to begin billing history."/>}/>
 {canManage&&paymentProviders.length===0&&<Card className="mt-5 p-4"><p className="text-sm text-[var(--nx-text-secondary)]">No enabled provider currently exposes the payments capability. Enable and health-check a provider in Commerce Settings before collecting payment.</p></Card>}
 <div className="mt-5 grid gap-5 xl:grid-cols-3"><Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Recent payment transactions</h2><div className="mt-4 grid gap-2">{transactions.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No provider transaction recorded.</p>:transactions.map(t=><div key={t.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{t.amount}</span><Badge>{human(t.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{human(t.provider)} · {human(t.type)}</p>{t.can_refund&&canManage&&<div className="mt-3 flex items-center justify-between gap-3"><span className="text-xs text-[var(--nx-text-muted)]">Refundable {t.refundable}</span><Button size="sm" variant="secondary" onClick={()=>openRefund(t)}>Refund</Button></div>}</div>)}</div></Card>
 <Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Recent refunds</h2><div className="mt-4 grid gap-2">{refunds.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No refunds recorded.</p>:refunds.map(r=><div key={r.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{r.amount}</span><Badge>{human(r.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.reason??"No reason supplied"}</p></div>)}</div></Card>
 <Card className="p-5"><h2 className="font-semibold text-[var(--nx-text)]">Subscriptions</h2><div className="mt-4 grid gap-2">{subscriptions.length===0?<p className="text-sm text-[var(--nx-text-muted)]">No provider subscriptions recorded.</p>:subscriptions.map(s=><div key={s.id} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center justify-between gap-2"><span className="font-semibold text-[var(--nx-text)]">{s.amount} / {human(s.interval)}</span><Badge>{human(s.status)}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{s.customer??"Customer"} · {s.product??"Product"}</p></div>)}</div></Card></div>

 <Modal open={paymentInvoice!==null} onClose={closePayment} title="Collect invoice payment" description={paymentInvoice?`Collect the outstanding ${paymentInvoice.due} balance for ${paymentInvoice.number}.`:undefined} footer={<><Button variant="secondary" onClick={closePayment}>Cancel</Button><Button loading={paymentForm.processing} disabled={!paymentForm.data.provider_key} onClick={()=>paymentInvoice&&paymentForm.post(`/admin/commerce/billing/invoices/${paymentInvoice.id}/payments`,{preserveScroll:true,onSuccess:closePayment})}>Collect payment</Button></>}>
  <Select label="Payment provider" value={paymentForm.data.provider_key} onChange={value=>paymentForm.setData("provider_key",value)} options={paymentProviders.map(provider=>({value:provider.key,label:`${provider.label}${provider.health?` — ${human(provider.health)}`:""}`}))} placeholder="Choose an enabled provider"/>
 </Modal>

 <Modal open={refundPayment!==null} onClose={closeRefund} title="Refund payment" description={refundPayment?`Refund up to ${refundPayment.refundable} from ${refundPayment.amount}.`:undefined} footer={<><Button variant="secondary" onClick={closeRefund}>Cancel</Button><Button loading={refundForm.processing} disabled={!refundForm.data.amount} onClick={()=>refundPayment&&refundForm.post(`/admin/commerce/billing/transactions/${refundPayment.id}/refunds`,{preserveScroll:true,onSuccess:closeRefund})}>Submit refund</Button></>}>
  <div className="grid gap-4"><Input label={`Refund amount (${refundPayment?.currency??""})`} value={refundForm.data.amount} onChange={event=>refundForm.setData("amount",event.target.value)} inputMode="decimal"/><Input label="Reason" value={refundForm.data.reason} onChange={event=>refundForm.setData("reason",event.target.value)}/></div>
 </Modal>
 </AdminLayout>;
}
