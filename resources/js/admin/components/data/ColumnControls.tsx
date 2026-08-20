import { useEffect, useState } from "react";
import { Button, Checkbox, Modal } from "@nexora/admin-ui";

type Item={key:string;label:string;required?:boolean};
export function ColumnControls({scope,columns,visible,onChange}:{scope:string;columns:Item[];visible:string[];onChange:(keys:string[])=>void}){
 const [open,setOpen]=useState(false);
 useEffect(()=>{const raw=localStorage.getItem(`nexora.columns.${scope}`);if(!raw)return;try{const keys=JSON.parse(raw);if(Array.isArray(keys))onChange(keys.filter((key):key is string=>typeof key==="string"))}catch{}},[scope]);
 const update=(keys:string[])=>{const required=columns.filter(c=>c.required).map(c=>c.key);const next=Array.from(new Set([...keys,...required]));onChange(next);localStorage.setItem(`nexora.columns.${scope}`,JSON.stringify(next))};
 return <><Button type="button" variant="secondary" onClick={()=>setOpen(true)}>Columns</Button><Modal open={open} onClose={()=>setOpen(false)} title="Visible columns" description="Choose the information shown in this table. Your preference is stored on this device." footer={<Button onClick={()=>setOpen(false)}>Done</Button>}><div className="grid gap-3">{columns.map(c=><Checkbox key={c.key} label={c.label} checked={visible.includes(c.key)} disabled={c.required} onChange={()=>update(visible.includes(c.key)?visible.filter(k=>k!==c.key):[...visible,c.key])}/>)}</div></Modal></>;
}
