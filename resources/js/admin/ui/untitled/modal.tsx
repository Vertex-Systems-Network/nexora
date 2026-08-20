import { useEffect, useId, useRef, type ReactNode } from "react";
import { UntitledButton as Button } from "./button";

const focusableSelector = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

export function Modal({ open, title, description, children, onClose, footer }: { open:boolean; title:string; description?:string; children?:ReactNode; onClose:()=>void; footer?:ReactNode }) {
 const panel=useRef<HTMLDivElement>(null); const titleId=useId(); const descriptionId=useId();
 useEffect(()=>{
   if(!open)return;
   const previous=document.activeElement as HTMLElement|null;
   const onKey=(e:KeyboardEvent)=>{
     if(e.key==="Escape"){e.preventDefault();onClose();return}
     if(e.key!=="Tab"||!panel.current)return;
     const items=(Array.from(panel.current.querySelectorAll(focusableSelector)) as HTMLElement[]).filter(el=>el.offsetParent!==null);
     if(items.length===0){e.preventDefault();panel.current.focus();return}
     const first=items[0],last=items[items.length-1];
     if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}
     else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
   };
   window.addEventListener("keydown",onKey);
   requestAnimationFrame(()=>panel.current?.querySelector<HTMLElement>(focusableSelector)?.focus()??panel.current?.focus());
   return()=>{window.removeEventListener("keydown",onKey);previous?.focus()};
 },[open,onClose]);
 if(!open) return null;
 return <div className="fixed inset-0 z-[80] grid place-items-center p-4" role="presentation">
   <button className="nx-pressable absolute inset-0 bg-slate-950/50 backdrop-blur-sm" onClick={onClose} aria-label="Close dialog" />
   <div ref={panel} tabIndex={-1} className="relative w-full max-w-lg rounded-[calc(var(--nx-radius-card)+4px)] border border-[var(--nx-border)] bg-[var(--nx-surface)] p-5 shadow-xl sm:p-6" role="dialog" aria-modal="true" aria-labelledby={titleId} aria-describedby={description?descriptionId:undefined}>
     <div className="pe-10"><h2 id={titleId} className="text-lg font-semibold tracking-tight text-[var(--nx-text)]">{title}</h2>{description && <p id={descriptionId} className="mt-1.5 text-sm leading-6 text-[var(--nx-text-muted)]">{description}</p>}</div>
     <div className="mt-5">{children}</div>
     {footer && <div className="mt-6 flex justify-end gap-2 border-t border-[var(--nx-border)] pt-4">{footer}</div>}
     <Button variant="ghost" size="sm" className="absolute end-3 top-3" onClick={onClose} aria-label="Close dialog">×</Button>
   </div>
 </div>;
}
