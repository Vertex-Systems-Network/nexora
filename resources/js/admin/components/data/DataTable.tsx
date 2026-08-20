import type { ReactNode } from "react";
import { Button, ButtonLink, Checkbox } from "@nexora/admin-ui";

type LinkItem = { url:string|null; label:string; active:boolean };
export type Paginator<T> = { data:T[]; links:LinkItem[]; current_page:number; last_page:number; total:number; from:number|null; to:number|null };
export type Column<T> = { key:string; label:string; className?:string; sortKey?:string; render:(row:T)=>ReactNode };
const pageLabel = (label:string) => label.replace(/&laquo;|&raquo;/g, "").replace(/<[^>]+>/g, "").trim();

export function DataTable<T extends { id:number|string }>({ rows, columns, paginator, selected, onSelectionChange, toolbar, empty, loading=false, sort, onSort }: { rows:T[]; columns:Column<T>[]; paginator?:Paginator<T>; selected?:Array<number|string>; onSelectionChange?:(ids:Array<number|string>)=>void; toolbar?:ReactNode; empty?:ReactNode; loading?:boolean; sort?:{key:string;direction:"asc"|"desc"}; onSort?:(key:string)=>void }) {
 const all = rows.length > 0 && rows.every(r => selected?.includes(r.id));
 const toggleAll = () => onSelectionChange?.(all ? [] : rows.map(r=>r.id));
 const toggle = (id:number|string) => onSelectionChange?.(selected?.includes(id) ? (selected ?? []).filter(v=>v!==id) : [...(selected ?? []), id]);
 return <div className="relative flex min-h-0 flex-col overflow-hidden rounded-[var(--nx-radius-card)] border border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-xs" aria-busy={loading || undefined}>
   {loading&&<><div className="absolute inset-x-0 top-0 z-40 h-0.5 overflow-hidden bg-[var(--nx-border)]" aria-hidden="true"><div className="h-full w-1/3 animate-[nx-table-load_1s_ease-in-out_infinite] bg-[var(--nx-brand-600)]"/></div><span className="sr-only" role="status" aria-live="polite">Loading table data</span></>}
   {toolbar && <div className="relative z-30 shrink-0 border-b border-[var(--nx-border)] bg-[var(--nx-surface)] p-4 sm:p-5">{toolbar}</div>}
   <div className={loading?"pointer-events-none min-h-0 flex-1 opacity-60 transition-opacity":"min-h-0 flex-1 transition-opacity"}>{rows.length===0 ? <div className="p-5">{empty}</div> : <div className="nx-scrollbar max-h-[calc(100vh-var(--nx-header-height)-12rem)] overflow-auto overscroll-contain" tabIndex={0} aria-label="Scrollable data table"><table className="w-full min-w-[760px] border-collapse text-start">
     <thead className="text-[11px] font-semibold uppercase tracking-[0.07em] text-[var(--nx-text-muted)]"><tr>
       {onSelectionChange && <th scope="col" className="sticky top-0 z-20 w-12 border-b border-[var(--nx-border)] bg-[color-mix(in_srgb,var(--nx-surface-subtle)_96%,transparent)] px-5 py-3.5 backdrop-blur-xl"><Checkbox checked={all} onChange={toggleAll} aria-label="Select all rows" /></th>}
       {columns.map(c=>{
         const ariaSort = c.sortKey && sort?.key===c.sortKey ? (sort.direction === "asc" ? "ascending" : "descending") : c.sortKey ? "none" : undefined;
         return <th key={c.key} scope="col" aria-sort={ariaSort} className={`sticky top-0 z-20 border-b border-[var(--nx-border)] bg-[color-mix(in_srgb,var(--nx-surface-subtle)_96%,transparent)] px-5 py-3.5 backdrop-blur-xl ${c.className ?? ""}`}>{c.sortKey&&onSort?<Button type="button" variant="ghost" size="sm" onClick={()=>onSort(c.sortKey!)} className="h-auto px-1 py-0 font-semibold uppercase tracking-[0.07em]"><span>{c.label}</span>{sort?.key===c.sortKey&&<span aria-hidden="true">{sort.direction==="asc"?"↑":"↓"}</span>}</Button>:c.label}</th>;
       })}
     </tr></thead>
     <tbody className="divide-y divide-[var(--nx-border)]">{rows.map(row=><tr key={row.id} className="group transition hover:bg-[var(--nx-surface-subtle)]">
       {onSelectionChange && <td className="px-5 py-4"><Checkbox checked={selected?.includes(row.id) ?? false} onChange={()=>toggle(row.id)} aria-label={`Select row ${row.id}`} /></td>}
       {columns.map(c=><td key={c.key} className={`px-5 py-4 align-middle ${c.className ?? ""}`}>{c.render(row)}</td>)}
     </tr>)}</tbody>
   </table></div>}</div>
   {paginator && paginator.last_page>1 && <div className="sticky bottom-0 z-30 flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-[var(--nx-border)] bg-[color-mix(in_srgb,var(--nx-surface)_96%,transparent)] px-5 py-4 backdrop-blur-xl"><p className="text-xs text-[var(--nx-text-muted)]">Showing {paginator.from}–{paginator.to} of {paginator.total}</p><nav className="flex flex-wrap gap-1" aria-label="Table pagination">{paginator.links.map((link,index)=>link.url?<ButtonLink key={index} href={link.url} preserveScroll preserveState size="sm" variant={link.active?"primary":"secondary"} aria-current={link.active?"page":undefined} className="h-9 px-3">{pageLabel(link.label)}</ButtonLink>:null)}</nav></div>}
 </div>;
}
