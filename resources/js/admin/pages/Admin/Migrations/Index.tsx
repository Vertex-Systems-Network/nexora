import { Head, router, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, Card, Input } from "@nexora/admin-ui";

type Run = {
  id:string; sourceType:string; sourceName:string; sourceBytes:number; status:string;
  processed:number; imported:number; skipped:number; failed:number; errorCode?:string|null;
  createdBy?:string|null; createdAt?:string|null; startedAt?:string|null; completedAt?:string|null; canResume:boolean;
};
type Props = { runs:Run[]; limits:{sourceBytes:number;itemsPerRun:number;remoteMediaFetch:boolean;xmlReaderAvailable:boolean} };

const tone=(status:string):"success"|"warning"|"danger"|"neutral"=>status==="completed"?"success":status==="failed"?"danger":status==="running"||status==="queued"||status==="completed_with_errors"?"warning":"neutral";
const when=(value?:string|null)=>value?new Date(value).toLocaleString():"—";
const bytes=(value:number)=>value<1024?`${value} B`:value<1024*1024?`${(value/1024).toFixed(1)} KB`:`${(value/1024/1024).toFixed(1)} MB`;

export default function MigrationIndex({runs,limits}:Props){
  const form=useForm<{source:File|null}>({source:null});
  const submit=()=>form.post("/admin/migrations/wordpress",{forceFormData:true,preserveScroll:true,onSuccess:()=>form.reset()});

  return <AdminLayout>
    <Head title="Import / Export"/>
    <PageHeader eyebrow="Content portability" title="Import / Export" description="Move content into or out of the active organization without bypassing Nexora document validation, revisions or tenant boundaries."/>

    <div className="grid gap-5 xl:grid-cols-2">
      <Card className="p-5">
        <div className="flex items-start gap-3">
          <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="download" className="h-4 w-4"/></span>
          <div><h2 className="font-semibold text-[var(--nx-text)]">WordPress WXR import</h2><p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Upload a local WordPress XML export. Posts and pages are imported through the Nexora document repository. Remote media is not fetched by Core.</p></div>
        </div>
        {!limits.xmlReaderAvailable&&<div className="mt-4 rounded-xl border border-[var(--nx-danger)]/30 bg-[var(--nx-danger)]/5 p-4 text-sm leading-6 text-[var(--nx-text)]"><strong>WXR import unavailable:</strong> enable the PHP XMLReader extension on this runtime. Export remains available.</div>}
        <form className="mt-5 grid gap-4" onSubmit={e=>{e.preventDefault();if(limits.xmlReaderAvailable)submit();}}>
          <Input label="WordPress export" type="file" accept=".xml,.wxr,text/xml,application/xml" disabled={!limits.xmlReaderAvailable} onChange={e=>form.setData("source",e.target.files?.[0]??null)} error={form.errors.source} hint={`Maximum ${bytes(limits.sourceBytes)} · ${limits.itemsPerRun.toLocaleString()} mapped items/run`}/>
          <div><Button type="submit" loading={form.processing} disabled={!limits.xmlReaderAvailable||!form.data.source} leadingIcon={<Icon name="download" className="h-4 w-4"/>}>Queue import</Button></div>
        </form>
        <div className="mt-5 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4 text-sm leading-6 text-[var(--nx-text-muted)]">
          WXR parsing disables network entity access and DTD/entity substitution. Imported HTML is converted to bounded plain-text blocks. Source URLs remain metadata only; <strong>remote media fetch is {limits.remoteMediaFetch?"enabled":"disabled"}</strong>.
        </div>
      </Card>

      <Card className="p-5">
        <div className="flex items-start gap-3">
          <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="archive" className="h-4 w-4"/></span>
          <div><h2 className="font-semibold text-[var(--nx-text)]">Nexora JSON export</h2><p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Stream the active organization&apos;s document records in the versioned Nexora export format. The export is private and not cached.</p></div>
        </div>
        <div className="mt-5"><Button type="button" leadingIcon={<Icon name="archive" className="h-4 w-4"/>} onClick={()=>window.location.assign("/admin/migrations/export/documents")}>Export documents</Button></div>
      </Card>
    </div>

    <Card className="mt-5 overflow-hidden">
      <div className="border-b border-[var(--nx-border)] p-5"><h2 className="font-semibold text-[var(--nx-text)]">Migration runs</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Replay-safe run history for this organization.</p></div>
      <div className="divide-y divide-[var(--nx-border)]">
        {runs.map(run=><div key={run.id} className="p-4">
          <div className="flex flex-wrap items-start justify-between gap-3">
            <div><div className="flex flex-wrap items-center gap-2"><p className="font-medium text-[var(--nx-text)]">{run.sourceName}</p><Badge tone={tone(run.status)}>{run.status}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{bytes(run.sourceBytes)} · created {when(run.createdAt)}{run.createdBy?` by ${run.createdBy}`:""}</p></div>
            {run.canResume&&<Button size="sm" variant="secondary" disabled={!limits.xmlReaderAvailable} onClick={()=>router.post(`/admin/migrations/${run.id}/resume`,{}, {preserveScroll:true})}>Resume</Button>}
          </div>
          <div className="mt-3 grid gap-2 text-xs text-[var(--nx-text-muted)] sm:grid-cols-4"><span>Processed <strong>{run.processed}</strong></span><span>Imported <strong>{run.imported}</strong></span><span>Replay-skipped <strong>{run.skipped}</strong></span><span>Failed <strong>{run.failed}</strong></span></div>
          {run.errorCode&&<p className="mt-2 text-xs text-[var(--nx-danger)]">Error reference: {run.errorCode}</p>}
        </div>)}
        {runs.length===0&&<p className="p-5 text-sm text-[var(--nx-text-muted)]">No migration runs yet.</p>}
      </div>
    </Card>
  </AdminLayout>;
}
