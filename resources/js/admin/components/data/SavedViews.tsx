import { useEffect, useState } from "react";
import { Button, Input, Modal, Select } from "@nexora/admin-ui";

type SavedView={id:number;name:string;is_default:boolean;state:Record<string,unknown>};
function csrf(){return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content??""}
export function SavedViews({scope,state,onApply}:{scope:string;state:Record<string,unknown>;onApply:(state:Record<string,unknown>)=>void}){
 const [views,setViews]=useState<SavedView[]>([]),[selected,setSelected]=useState(""),[open,setOpen]=useState(false),[name,setName]=useState(""),[saving,setSaving]=useState(false);
 const load=async()=>{const r=await fetch(`/admin/saved-views?scope=${encodeURIComponent(scope)}`,{headers:{Accept:"application/json"}});if(r.ok)setViews((await r.json()).views??[])};
 useEffect(()=>{void load()},[scope]);
 const save=async()=>{if(!name.trim())return;setSaving(true);try{const r=await fetch('/admin/saved-views',{method:'POST',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf()},body:JSON.stringify({scope,name:name.trim(),state})});if(r.ok){setOpen(false);setName("");await load()}}finally{setSaving(false)}};
 const remove=async()=>{if(!selected)return;setSaving(true);try{const r=await fetch(`/admin/saved-views/${selected}`,{method:"DELETE",headers:{Accept:"application/json","X-CSRF-TOKEN":csrf()}});if(r.ok){setSelected("");await load()}}finally{setSaving(false)}};
 const apply=(id:string)=>{setSelected(id);const view=views.find(v=>String(v.id)===id);if(view)onApply(view.state)};
 return <><div className="flex items-end gap-2"><div className="min-w-44"><Select ariaLabel="Saved views" value={selected} onChange={apply} options={[{value:"",label:"Saved views"},...views.map(v=>({value:String(v.id),label:v.name+(v.is_default?" · default":"")}))]}/></div><Button type="button" variant="secondary" onClick={()=>setOpen(true)}>Save view</Button>{selected&&<Button type="button" variant="ghost" loading={saving} onClick={remove}>Remove</Button>}</div><Modal open={open} onClose={()=>setOpen(false)} title="Save current view" description="Store the current filters so you can restore this workspace later." footer={<><Button variant="secondary" onClick={()=>setOpen(false)}>Cancel</Button><Button loading={saving} onClick={save}>Save view</Button></>}><Input label="View name" autoFocus value={name} onChange={e=>setName(e.target.value)} placeholder="e.g. Suspended users"/></Modal></>;
}
