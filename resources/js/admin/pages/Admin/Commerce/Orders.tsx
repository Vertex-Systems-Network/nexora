import { useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Button, Input, Modal, Select } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";
type Row={id:string;number:string;status:string;customer:string;email:string|null;items_count:number;currency:string;total:string;paid:string;created_at:string|null};
type Props={orders:Paginator<Row>;customers:Array<{id:string;name:string;email:string}>;prices:Array<{id:string;label:string;currency:string}>;currencies:Array<{code:string;name:string}>;canManage:boolean};
const human=(v:string)=>v.replace(/[_-]+/g," ").replace(/\b\w/g,c=>c.toUpperCase());
export default function Orders({orders,customers,prices,currencies,canManage}:Props){
 const [open,setOpen]=useState(false); const form=useForm({customer_id:"",currency:currencies[0]?.code??"USD",price_id:"",quantity:"1"});
 const priceOptions=prices.filter(p=>p.currency===form.data.currency).map(p=>({value:p.id,label:p.label}));
 const columns=useMemo<Column<Row>[]>(()=>[
  {key:"order",label:"Order",render:r=><div><p className="font-semibold text-[var(--nx-text)]">{r.number}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.customer} · {r.items_count} item{r.items_count===1?"":"s"}</p></div>},
  {key:"status",label:"Status",render:r=><Badge tone={r.status==="paid"||r.status==="completed"?"success":r.status==="cancelled"||r.status==="refunded"?"neutral":"warning"}>{human(r.status)}</Badge>},
  {key:"total",label:"Total",render:r=><div><p className="font-semibold text-[var(--nx-text)]">{r.total}</p><p className="text-xs text-[var(--nx-text-muted)]">Paid {r.paid}</p></div>},
  {key:"actions",label:"",className:"w-44 text-right",render:r=>canManage?<div className="flex justify-end gap-2">{r.status==="draft"&&<Button size="sm" variant="secondary" onClick={()=>router.post(`/admin/commerce/orders/${r.id}/place`,{}, {preserveScroll:true})}>Place</Button>}<Button size="sm" variant="secondary" onClick={()=>router.post(`/admin/commerce/orders/${r.id}/invoice`,{}, {preserveScroll:true})}>Invoice</Button></div>:null},
 ],[canManage]);
 return <AdminLayout><Head title="Commerce Orders"/><PageHeader eyebrow="Commerce" title="Orders" description="Orders use immutable monetary snapshots so later product-price changes do not rewrite historical totals." actions={canManage?<Button onClick={()=>setOpen(true)}>Create draft order</Button>:undefined}/><CommerceNav current="/admin/commerce/orders"/>
 <DataTable rows={orders.data} columns={columns} paginator={orders} empty={<EmptyState title="No orders" description="Create a draft order from an active Commerce price."/>}/>
 <Modal open={open} onClose={()=>setOpen(false)} title="Create draft order" description="This foundation creates one line item per draft. Multi-line checkout composition is exposed through Commerce services for future checkout extensions." footer={<><Button variant="secondary" onClick={()=>setOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={()=>form.post('/admin/commerce/orders',{preserveScroll:true,onSuccess:()=>{form.reset();setOpen(false)}})}>Create order</Button></>}><div className="grid gap-4"><Select label="Customer" value={form.data.customer_id} onChange={v=>form.setData('customer_id',v)} options={[{value:'',label:'Guest customer'},...customers.map(c=>({value:c.id,label:`${c.name} — ${c.email}`}))]}/><Select label="Currency" value={form.data.currency} onChange={v=>{form.setData('currency',v);form.setData('price_id','')}} options={currencies.map(c=>({value:c.code,label:`${c.code} — ${c.name}`}))}/><Select label="Product price" value={form.data.price_id} onChange={v=>form.setData('price_id',v)} options={priceOptions} placeholder="Choose an active price"/><Input label="Quantity" value={form.data.quantity} onChange={e=>form.setData('quantity',e.target.value)} inputMode="numeric"/></div></Modal>
 </AdminLayout>;
}
