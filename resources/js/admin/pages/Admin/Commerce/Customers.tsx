import { useMemo, useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Button, Input, Modal } from "@nexora/admin-ui";
import { CommerceNav } from "./_CommerceNav";
type Row={id:string;name:string;email:string;phone:string|null;orders_count:number;subscriptions_count:number;created_at:string|null};
type Props={customers:Paginator<Row>;canManage:boolean};
export default function Customers({customers,canManage}:Props){
 const [open,setOpen]=useState(false); const form=useForm({name:"",email:"",phone:"",tax_id:""});
 const columns=useMemo<Column<Row>[]>(()=>[
  {key:"customer",label:"Customer",render:r=><div><p className="font-semibold text-[var(--nx-text)]">{r.name}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{r.email}</p></div>},
  {key:"phone",label:"Phone",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.phone??"—"}</span>},
  {key:"orders",label:"Orders",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.orders_count}</span>},
  {key:"subscriptions",label:"Subscriptions",render:r=><span className="text-sm text-[var(--nx-text-secondary)]">{r.subscriptions_count}</span>},
 ],[]);
 return <AdminLayout><Head title="Commerce Customers"/><PageHeader eyebrow="Commerce" title="Customers" description="Billing identities are separate from platform users so guest buyers and external billing contacts remain supported." actions={canManage?<Button onClick={()=>setOpen(true)}>Create customer</Button>:undefined}/><CommerceNav current="/admin/commerce/customers"/>
 <DataTable rows={customers.data} columns={columns} paginator={customers} empty={<EmptyState title="No customers" description="Create a customer or let future checkout extensions attach buyers to Commerce."/>}/>
 <Modal open={open} onClose={()=>setOpen(false)} title="Create customer" footer={<><Button variant="secondary" onClick={()=>setOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={()=>form.post('/admin/commerce/customers',{preserveScroll:true,onSuccess:()=>{form.reset();setOpen(false)}})}>Create customer</Button></>}><div className="grid gap-4"><Input label="Customer name" value={form.data.name} onChange={e=>form.setData('name',e.target.value)} error={form.errors.name}/><Input label="Email address" value={form.data.email} onChange={e=>form.setData('email',e.target.value)} error={form.errors.email}/><Input label="Phone" value={form.data.phone} onChange={e=>form.setData('phone',e.target.value)}/><Input label="Tax identifier" value={form.data.tax_id} onChange={e=>form.setData('tax_id',e.target.value)} hint="Optional. Store only when your billing process requires it."/></div></Modal>
 </AdminLayout>;
}
