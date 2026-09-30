import { useMemo, useState } from "react";
import { Head, useForm, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { DataTable, type Column } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import { Badge, Button, Card, Input, Modal, Select } from "@nexora/admin-ui";

type Org={id:string;name:string;slug:string;status:string;is_default:boolean;members_count:number;owner_user_id:number|null};
type Props={current:{id:string;name:string;slug:string;status:string;timezone:string;locale:string}|null;organizations:Org[];canManage:boolean;canImpersonate:boolean};
export default function EnterpriseIndex({current,organizations,canManage}:Props){
 const[open,setOpen]=useState(false);const form=useForm({name:"",slug:"",timezone:"UTC",locale:"en"});
 const manageOrganization=(organization:Org)=>{
  if(current?.id===organization.id){router.visit(`/admin/enterprise/organizations/${organization.id}`);return;}
  router.post('/admin/enterprise/switch',{organization_id:organization.id},{
   preserveScroll:false,
   onSuccess:()=>router.visit(`/admin/enterprise/organizations/${organization.id}`),
  });
 };
 const cols=useMemo<Column<Org>[]>(()=>[
  {key:"name",label:"Organization",render:o=><div><p className="font-semibold text-[var(--nx-text)]">{o.name}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{o.slug}{o.is_default?" · Default organization":""}</p></div>},
  {key:"status",label:"Status",render:o=><Badge>{o.status}</Badge>},{key:"members",label:"Members",render:o=><span>{o.members_count}</span>},
  {key:"current",label:"Current",render:o=>current?.id===o.id?<Badge>Selected</Badge>:<Button size="sm" variant="secondary" onClick={()=>router.post('/admin/enterprise/switch',{organization_id:o.id},{preserveScroll:true})}>Switch</Button>},
  {key:"action",label:"",render:o=><Button size="sm" variant="ghost" onClick={()=>manageOrganization(o)}>Manage</Button>},
 ],[current?.id]);
 return <AdminLayout><Head title="Enterprise"/><PageHeader eyebrow="Enterprise" title="Organizations & tenancy" description="Isolate tenant data, members, domains and identity configuration without duplicating the Nexora platform runtime." actions={canManage?<Button onClick={()=>setOpen(true)}>Create organization</Button>:undefined}/>
 <div className="grid gap-4 sm:grid-cols-3"><Card className="p-5"><p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">Current organization</p><p className="mt-2 text-lg font-semibold text-[var(--nx-text)]">{current?.name??"Not resolved"}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{current?.slug??"No tenant context"}</p></Card><Card className="p-5"><p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">Organizations</p><p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">{organizations.length}</p></Card><Card className="p-5"><p className="text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">Isolation model</p><p className="mt-2 text-sm font-semibold text-[var(--nx-text)]">Tenant context + scoped records</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Verified domain/session membership resolution</p></Card></div>
 <DataTable rows={organizations} columns={cols} empty={<EmptyState title="No organizations" description="Create the first tenant organization."/>}/>
 <Modal open={open} onClose={()=>setOpen(false)} title="Create organization" footer={<><Button variant="secondary" onClick={()=>setOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={()=>form.post('/admin/enterprise/organizations',{onSuccess:()=>setOpen(false)})}>Create</Button></>}><div className="grid gap-4 sm:grid-cols-2"><Input label="Organization name" value={form.data.name} onChange={e=>form.setData('name',e.target.value)} error={form.errors.name}/><Input label="Slug" value={form.data.slug} onChange={e=>form.setData('slug',e.target.value)} placeholder="acme" error={form.errors.slug}/><Input label="Timezone" value={form.data.timezone} onChange={e=>form.setData('timezone',e.target.value)}/><Select label="Default language" value={form.data.locale} onChange={v=>form.setData('locale',v)} options={[{value:'en',label:'English'},{value:'ur',label:'Urdu'},{value:'tr',label:'Turkish'},{value:'ar',label:'Arabic'},{value:'ru',label:'Russian'}]}/></div></Modal>
 </AdminLayout>;
}
