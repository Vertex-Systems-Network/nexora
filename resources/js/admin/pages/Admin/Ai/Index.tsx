import { useMemo, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import type { SharedPageProps } from "@admin/types/page";
import { Badge, Button, Card, Input, Select, Textarea } from "@nexora/admin-ui";

type Provider={key:string;label:string};
type Connection={id:number;uuid:string;name:string;providerKey:string;model:string;enabled:boolean;hasCredentials:boolean;settings:Record<string,unknown>;maxInputChars:number;maxOutputTokens:number;dailyRequestLimit:number;lastHealthStatus?:string|null;lastHealthMessage?:string|null;lastHealthCheckedAt?:string|null};
type Run={id:number;uuid:string;connectionName?:string|null;providerKey:string;model:string;status:string;promptChars:number;requestedOutputTokens:number;inputTokens?:number|null;outputTokens?:number|null;outputChars?:number|null;errorCode?:string|null;startedAt?:string|null;completedAt?:string|null};
type Generation={connectionName:string;model:string;text:string;inputTokens?:number|null;outputTokens?:number|null};
type Props={providers:Provider[];connections:Connection[];recentRuns:Run[];lastGeneration?:Generation|null};

const when=(value?:string|null)=>value?new Date(value).toLocaleString():"Never";
const statusTone=(status?:string|null):"success"|"warning"|"danger"|"neutral"=>status==="healthy"||status==="succeeded"?"success":status==="unhealthy"||status==="failed"?"danger":status==="running"?"warning":"neutral";

export default function AiPlatformIndex({providers,connections,recentRuns,lastGeneration}:Props){
 const permissions=usePage<SharedPageProps>().props.auth.user?.permissions??[];
 const canManage=permissions.includes("ai.connections.manage");
 const canGenerate=permissions.includes("ai.generate");
 const providerOptions=providers.map(item=>({value:item.key,label:item.label,description:item.key}));
 const enabledConnections=connections.filter(item=>item.enabled);
 const [selectedConnection,setSelectedConnection]=useState<string>(enabledConnections[0]?String(enabledConnections[0].id):"");
 const [deleteTarget,setDeleteTarget]=useState<Connection|null>(null);
 const form=useForm({name:"",provider_key:providers[0]?.key??"",model:"",credentials_json:"",settings_json:"{}",max_input_chars:20000,max_output_tokens:2048,daily_request_limit:100});
 const generateForm=useForm({prompt:"",max_output_tokens:1024});
 const selected=useMemo(()=>connections.find(item=>String(item.id)===selectedConnection),[connections,selectedConnection]);
 const connectionOptions=enabledConnections.map(item=>({value:String(item.id),label:item.name,description:`${item.providerKey} · ${item.model}`}));
 const create=()=>form.post("/admin/ai/connections",{preserveScroll:true,onSuccess:()=>form.reset("name","model","credentials_json")});
 const generate=()=>{if(!selectedConnection)return;generateForm.post(`/admin/ai/connections/${selectedConnection}/generate`,{preserveScroll:true});};
 return <AdminLayout>
  <Head title="AI Platform"/>
  <PageHeader eyebrow="AI Platform" title="Provider-neutral AI" description="Connect verified AI provider adapters without coupling Nexora Core to a vendor SDK. Core enforces tenant isolation, encrypted credentials, bounded generation and privacy-minimal run history."/>

  <div className="mb-5 grid gap-3 md:grid-cols-4">
   <Metric icon="sparkles" label="Providers registered" value={providers.length} hint="Extension-backed adapters"/>
   <Metric icon="link" label="Connections" value={connections.length} hint={`${connections.filter(x=>x.enabled).length} enabled`}/>
   <Metric icon="activity" label="Recent runs" value={recentRuns.length} hint={`${recentRuns.filter(x=>x.status==="failed").length} failed`}/>
   <Metric icon="shield" label="Raw history" value={0} hint="Prompts / outputs persisted"/>
  </div>

  {providers.length===0&&<Card className="mb-5 p-5"><div className="flex gap-3"><Icon name="info" className="mt-0.5 h-5 w-5 text-[var(--nx-brand)]"/><div><h2 className="font-semibold text-[var(--nx-text)]">No AI provider adapter is registered</h2><p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Nexora Core intentionally ships provider-neutral. Install or enable a verified AI provider extension that registers the AI text-provider contract; credentials and model configuration can then be created here.</p></div></div></Card>}

  <div className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(360px,0.8fr)]">
   <Card className="p-5">
    <div className="mb-5"><h2 className="font-semibold text-[var(--nx-text)]">AI connections</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Secrets are encrypted at rest and never returned to the browser. Configuration changes must be tested before the connection can be enabled.</p></div>
    {canManage&&providers.length>0&&<form className="grid gap-4" onSubmit={e=>{e.preventDefault();create();}}>
     <div className="grid gap-4 md:grid-cols-2"><Input label="Connection name" value={form.data.name} onChange={e=>form.setData("name",e.target.value)} error={form.errors.name} placeholder="Editorial assistant"/><Select label="Provider" value={form.data.provider_key} onChange={value=>form.setData("provider_key",value)} options={providerOptions} error={form.errors.provider_key}/></div>
     <Input label="Model" value={form.data.model} onChange={e=>form.setData("model",e.target.value)} error={form.errors.model} placeholder="Provider model identifier"/>
     <div className="grid gap-4 md:grid-cols-2"><Textarea label="Credentials JSON" value={form.data.credentials_json} onChange={e=>form.setData("credentials_json",e.target.value)} error={form.errors.credentials_json} placeholder={'{"api_key":"…"}'} hint="Named JSON keys only. Stored encrypted; not shown again."/><Textarea label="Provider settings JSON" value={form.data.settings_json} onChange={e=>form.setData("settings_json",e.target.value)} error={form.errors.settings_json} placeholder={'{"temperature":0.3}'}/></div>
     <div className="grid gap-4 md:grid-cols-3"><Input label="Max input characters" type="number" min={1} max={200000} value={form.data.max_input_chars} onChange={e=>form.setData("max_input_chars",Number(e.target.value))} error={form.errors.max_input_chars}/><Input label="Max output tokens" type="number" min={1} max={32768} value={form.data.max_output_tokens} onChange={e=>form.setData("max_output_tokens",Number(e.target.value))} error={form.errors.max_output_tokens}/><Input label="Daily request limit" type="number" min={1} max={100000} value={form.data.daily_request_limit} onChange={e=>form.setData("daily_request_limit",Number(e.target.value))} error={form.errors.daily_request_limit}/></div>
     <div><Button type="submit" loading={form.processing} leadingIcon={<Icon name="plus" className="h-4 w-4"/>}>Create connection</Button></div>
    </form>}

    <div className={`${canManage&&providers.length>0?"mt-6":""} divide-y divide-[var(--nx-border)]`}>
     {connections.map(connection=><div key={connection.id} className="py-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><div className="flex flex-wrap items-center gap-2"><p className="font-medium text-[var(--nx-text)]">{connection.name}</p><Badge tone={connection.enabled?"success":"neutral"}>{connection.enabled?"enabled":"disabled"}</Badge><Badge tone={statusTone(connection.lastHealthStatus)}>{connection.lastHealthStatus??"untested"}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{connection.providerKey} · {connection.model} · tested {when(connection.lastHealthCheckedAt)}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Limits: {connection.maxInputChars.toLocaleString()} input chars · {connection.maxOutputTokens.toLocaleString()} output tokens · {connection.dailyRequestLimit.toLocaleString()} requests/day</p>{connection.lastHealthMessage&&<p className="mt-1 text-xs text-[var(--nx-text-muted)]">{connection.lastHealthMessage}</p>}</div>{canManage&&<div className="flex flex-wrap gap-2"><Button size="sm" variant="secondary" onClick={()=>router.post(`/admin/ai/connections/${connection.id}/test`,{}, {preserveScroll:true})}>Test</Button><Button size="sm" variant="ghost" disabled={!connection.enabled&&connection.lastHealthStatus!=="healthy"} onClick={()=>router.patch(`/admin/ai/connections/${connection.id}/enabled`,{enabled:!connection.enabled},{preserveScroll:true})}>{connection.enabled?"Disable":"Enable"}</Button>{!connection.enabled&&<Button size="sm" variant="ghost" onClick={()=>setDeleteTarget(connection)}>Delete</Button>}</div>}</div></div>)}
     {connections.length===0&&<p className="py-5 text-sm text-[var(--nx-text-muted)]">No AI connections configured.</p>}
    </div>
   </Card>

   <Card className="p-5">
    <div className="mb-5"><h2 className="font-semibold text-[var(--nx-text)]">Text generation workbench</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">The prompt is sent to the selected provider for this request only. History stores SHA-256, lengths and token accounting—not prompt or generated text.</p></div>
    {canGenerate&&enabledConnections.length>0?<form className="grid gap-4" onSubmit={e=>{e.preventDefault();generate();}}><Select label="Enabled connection" value={selectedConnection} onChange={setSelectedConnection} options={connectionOptions}/><Textarea label="Prompt" value={generateForm.data.prompt} onChange={e=>generateForm.setData("prompt",e.target.value)} error={generateForm.errors.prompt} placeholder="Draft a concise summary…"/><Input label="Maximum output tokens" type="number" min={1} max={selected?.maxOutputTokens??32768} value={generateForm.data.max_output_tokens} onChange={e=>generateForm.setData("max_output_tokens",Number(e.target.value))} error={generateForm.errors.max_output_tokens}/><div><Button type="submit" loading={generateForm.processing} leadingIcon={<Icon name="sparkles" className="h-4 w-4"/>}>Generate</Button></div></form>:<p className="text-sm leading-6 text-[var(--nx-text-muted)]">Enable a healthy AI connection and ensure your role has <code>ai.generate</code> permission to use the workbench.</p>}
    {lastGeneration&&<div className="mt-5 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"><div className="flex flex-wrap items-center justify-between gap-2"><p className="font-medium text-[var(--nx-text)]">Latest response · {lastGeneration.connectionName}</p><span className="text-xs text-[var(--nx-text-muted)]">{lastGeneration.inputTokens??"—"} in / {lastGeneration.outputTokens??"—"} out tokens</span></div><pre className="mt-3 whitespace-pre-wrap break-words font-sans text-sm leading-6 text-[var(--nx-text)]">{lastGeneration.text}</pre><p className="mt-3 text-xs text-[var(--nx-text-muted)]">This raw response is intentionally not present in generation-history rows.</p></div>}
   </Card>
  </div>

  <Card className="mt-5 overflow-hidden"><div className="border-b border-[var(--nx-border)] p-5"><h2 className="font-semibold text-[var(--nx-text)]">Recent generation metadata</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Operational visibility without storing prompt or output contents.</p></div><div className="divide-y divide-[var(--nx-border)]">{recentRuns.map(run=><div key={run.id} className="grid gap-2 p-4 md:grid-cols-[minmax(0,1fr)_120px_180px] md:items-center"><div><div className="flex items-center gap-2"><p className="font-medium text-[var(--nx-text)]">{run.connectionName??"Deleted connection"}</p><Badge tone={statusTone(run.status)}>{run.status}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{run.providerKey} · {run.model} · {run.promptChars.toLocaleString()} prompt chars · {run.outputChars??"—"} output chars</p></div><div className="text-xs text-[var(--nx-text-muted)]">{run.inputTokens??"—"} / {run.outputTokens??"—"} tokens</div><div className="text-xs text-[var(--nx-text-muted)] md:text-right">{when(run.startedAt)}</div></div>)}{recentRuns.length===0&&<p className="p-5 text-sm text-[var(--nx-text-muted)]">No AI generation runs yet.</p>}</div></Card>

  <ConfirmDialog open={deleteTarget!==null} onClose={()=>setDeleteTarget(null)} title="Delete AI connection?" description="Generation metadata linked to this connection will also be removed. Raw prompts and outputs are never stored in those rows." confirmLabel="Delete connection" tone="danger" onConfirm={()=>{if(!deleteTarget)return;router.delete(`/admin/ai/connections/${deleteTarget.id}`,{preserveScroll:true,onFinish:()=>setDeleteTarget(null)});}}/>
 </AdminLayout>;
}

function Metric({icon,label,value,hint}:{icon:string;label:string;value:number;hint:string}){return <Card className="p-4"><div className="flex gap-3"><span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name={icon} className="h-4 w-4"/></span><div><p className="text-xs font-medium text-[var(--nx-text-muted)]">{label}</p><p className="mt-0.5 text-xl font-semibold text-[var(--nx-text)]">{value.toLocaleString()}</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{hint}</p></div></div></Card>}
