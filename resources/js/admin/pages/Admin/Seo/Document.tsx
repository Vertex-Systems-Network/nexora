import { Head, router, useForm, usePage } from "@inertiajs/react";
import { MediaPicker, type MediaPickerSelection } from "@admin/components/MediaPicker";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, Checkbox, Input, Select, Textarea } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type DocumentInfo = { id:number; title:string; slug:string|null; status:string; excerpt:string|null };
type SeoForm = {
    seo_title:string;
    meta_description:string;
    canonical_url:string;
    url_path:string;
    robots_index:boolean;
    robots_follow:boolean;
    robots_directives:string[];
    schema_type:string;
    sitemap_include:boolean;
    social_title:string;
    social_description:string;
    social_image:string;
    social_image_media_id:number|null;
};
type Issue = { severity:"high"|"medium"|"low"; code:string; title:string; description:string };
type LinkSuggestion = { id:number; target_title:string; target_slug:string|null; anchor_text:string; status:string; reason:string|null; confidence:number };

type Props = {
    document:DocumentInfo;
    seo:SeoForm;
    socialImageMedia:MediaPickerSelection|null;
    metadata:Record<string,unknown>;
    issues:Issue[];
    schemaGraph:Record<string,unknown>;
    schemaTypes:Array<{value:string;label:string}>;
    internalLinks:LinkSuggestion[];
};

const toneForIssue = (severity:Issue["severity"]):"danger"|"warning"|"neutral" => severity==="high"?"danger":severity==="medium"?"warning":"neutral";
const directiveOptions = ["noarchive","nosnippet","noimageindex","notranslate"];

export default function DocumentSeo({ document, seo, socialImageMedia, metadata, issues, schemaGraph, schemaTypes, internalLinks }: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("seo.manage");
    const canAnalyze = permissions.includes("seo.links.analyze");
    const form = useForm<SeoForm>(seo);
    const toggleDirective = (directive:string, checked:boolean) => form.setData("robots_directives", checked ? [...new Set([...form.data.robots_directives,directive])] : form.data.robots_directives.filter((item)=>item!==directive));
    const save = () => form.put(`/admin/seo/documents/${document.id}`, { preserveScroll:true });
    const effectiveTitle = form.data.seo_title.trim() || document.title;
    const effectiveDescription = form.data.meta_description.trim() || document.excerpt || "No search description configured.";
    const displayUrl = form.data.canonical_url || form.data.url_path || "Public URL not configured";

    return <AdminLayout>
        <Head title={`SEO · ${document.title}`}/>
        <PageHeader
            eyebrow="SEO resource"
            title={document.title}
            description="Theme-independent search metadata, indexing policy, schema, social sharing and internal-link analysis for this document."
            actions={<div className="flex flex-wrap gap-2"><ButtonLink href={`/admin/documents/${document.id}/edit`} variant="secondary" leadingIcon={<Icon name="edit" className="h-4 w-4"/>}>Open writer</ButtonLink><ButtonLink href="/admin/seo" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4"/>}>SEO overview</ButtonLink></div>}
        />

        <div className="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_24rem]">
            <div className="grid gap-5">
                <Card className="p-5 sm:p-6">
                    <div className="grid gap-5">
                        <div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Search metadata</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Title, description & canonical</h2></div>
                        <Input label="Search title" value={form.data.seo_title} onChange={(event)=>form.setData("seo_title",event.target.value)} error={form.errors.seo_title} hint="Leave blank to inherit the document title." maxLength={255}/>
                        <Textarea label="Meta description" value={form.data.meta_description} onChange={(event)=>form.setData("meta_description",event.target.value)} error={form.errors.meta_description} rows={4} maxLength={1000} hint="Write for humans. Nexora intentionally does not calculate a synthetic keyword-density score."/>
                        <div className="grid gap-4 lg:grid-cols-2"><Input label="Canonical URL" value={form.data.canonical_url} onChange={(event)=>form.setData("canonical_url",event.target.value)} error={form.errors.canonical_url} placeholder="https://example.com/page"/><Input label="Public URL path" value={form.data.url_path} onChange={(event)=>form.setData("url_path",event.target.value)} error={form.errors.url_path} placeholder="/guides/example" hint="Used as a sitemap and canonical fallback when an absolute canonical URL is empty."/></div>
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <div className="grid gap-5">
                        <div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Indexing policy</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Robots & sitemap</h2></div>
                        <Checkbox label="Allow indexing" description="When disabled, Nexora treats the resource as noindex and excludes it from generated sitemaps." checked={form.data.robots_index} onChange={(event)=>form.setData("robots_index",event.target.checked)}/>
                        <Checkbox label="Allow link following" checked={form.data.robots_follow} onChange={(event)=>form.setData("robots_follow",event.target.checked)}/>
                        <Checkbox label="Include in sitemap" description="Only indexable published resources with a usable public URL are emitted." checked={form.data.sitemap_include} onChange={(event)=>form.setData("sitemap_include",event.target.checked)}/>
                        <div><p className="mb-3 text-sm font-medium text-[var(--nx-text)]">Additional robots directives</p><div className="grid gap-3 sm:grid-cols-2">{directiveOptions.map((directive)=><Checkbox key={directive} label={directive} checked={form.data.robots_directives.includes(directive)} onChange={(event)=>toggleDirective(directive,event.target.checked)}/>)}</div></div>
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <div className="grid gap-5">
                        <div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Semantic graph</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Schema Graph</h2></div>
                        <Select label="Schema type" value={form.data.schema_type} onChange={(value)=>form.setData("schema_type",value)} options={schemaTypes} error={form.errors.schema_type}/>
                        <div className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"><p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">JSON-LD graph preview</p><pre className="nx-scrollbar mt-3 max-h-80 overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-[var(--nx-text-secondary)]">{JSON.stringify(schemaGraph,null,2)}</pre></div>
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <div className="grid gap-5">
                        <div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Social sharing</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Open Graph & social metadata</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Saved social fields are emitted by the public theme head. Media Library selection takes priority over the external image fallback.</p></div>
                        <Input label="Social title" value={form.data.social_title} onChange={(event)=>form.setData("social_title",event.target.value)} error={form.errors.social_title} hint="Leave blank to inherit the resolved SEO title."/>
                        <Textarea label="Social description" value={form.data.social_description} onChange={(event)=>form.setData("social_description",event.target.value)} rows={3} error={form.errors.social_description} hint="Leave blank to inherit the resolved SEO description."/>
                        <MediaPicker
                            value={socialImageMedia?.url ?? undefined}
                            selection={socialImageMedia}
                            type="image"
                            showSelection
                            allowClear
                            buttonLabel="Choose social image"
                            onChange={(_url, asset)=>form.setData("social_image_media_id",asset.id)}
                            onClear={()=>form.setData("social_image_media_id",null)}
                        />
                        {form.errors.social_image_media_id&&<p className="text-xs text-[var(--nx-danger)]">{form.errors.social_image_media_id}</p>}
                        <Input label="External social image URL" value={form.data.social_image} onChange={(event)=>form.setData("social_image",event.target.value)} error={form.errors.social_image} placeholder="Optional fallback https://…"/>
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">Internal linking</p><h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Link opportunities</h2></div>{canAnalyze&&<Button type="button" variant="secondary" onClick={()=>router.post(`/admin/seo/documents/${document.id}/internal-links/refresh`,{}, {preserveScroll:true})} leadingIcon={<Icon name="refresh" className="h-4 w-4"/>}>Analyze content</Button>}</div>
                    <div className="mt-5 grid gap-3">{internalLinks.length===0?<p className="rounded-xl border border-dashed border-[var(--nx-border)] p-5 text-sm text-[var(--nx-text-muted)]">No suggestions yet. Nexora only suggests links when another published document title appears naturally in the current document.</p>:internalLinks.map((item)=><div key={item.id} className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"><div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><div className="flex flex-wrap items-center gap-2"><p className="text-sm font-semibold text-[var(--nx-text)]">{item.target_title}</p><Badge tone={item.status==="added"?"success":item.status==="dismissed"?"neutral":"warning"}>{item.status==="added"?"Added":item.status==="dismissed"?"Dismissed":"Suggested"}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Anchor: “{item.anchor_text}” · Confidence {Math.round(item.confidence*100)}%</p>{item.reason&&<p className="mt-2 text-sm text-[var(--nx-text-secondary)]">{item.reason}</p>}</div>{canManage&&item.status==="suggested"&&<div className="flex gap-2"><Button type="button" size="sm" variant="secondary" onClick={()=>router.patch(`/admin/seo/internal-links/${item.id}`,{status:"added"},{preserveScroll:true})}>Mark added</Button><Button type="button" size="sm" variant="ghost" onClick={()=>router.patch(`/admin/seo/internal-links/${item.id}`,{status:"dismissed"},{preserveScroll:true})}>Dismiss</Button></div>}</div></div>)}</div>
                </Card>
            </div>

            <div className="grid h-fit gap-4 2xl:sticky 2xl:top-[calc(var(--nx-header-height)+2rem)]">
                <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Search preview</p><p className="mt-4 text-xs text-emerald-700 dark:text-emerald-400">{displayUrl}</p><h3 className="mt-1 text-lg font-medium text-[var(--nx-brand-700)]">{effectiveTitle}</h3><p className="mt-1 text-sm leading-5 text-[var(--nx-text-secondary)]">{effectiveDescription}</p></Card>
                <Card className="p-5"><div className="flex items-center justify-between"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">SEO audit</p><Badge tone={issues.length===0?"success":"warning"}>{issues.length} issue{issues.length===1?"":"s"}</Badge></div><div className="mt-4 grid gap-3">{issues.length===0?<div className="flex gap-3 rounded-xl bg-green-50 p-3 text-green-800 dark:bg-green-950/25 dark:text-green-300"><Icon name="success" className="mt-0.5 h-4 w-4 shrink-0"/><p className="text-sm">No current structural SEO issues detected.</p></div>:issues.map((issue)=><div key={issue.code} className="rounded-xl border border-[var(--nx-border)] p-3"><div className="flex items-center gap-2"><Badge tone={toneForIssue(issue.severity)}>{issue.severity}</Badge><p className="text-sm font-semibold text-[var(--nx-text)]">{issue.title}</p></div><p className="mt-2 text-xs leading-5 text-[var(--nx-text-muted)]">{issue.description}</p></div>)}</div></Card>
                <Card className="p-5"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Resolved metadata</p><pre className="nx-scrollbar mt-3 max-h-52 overflow-auto whitespace-pre-wrap break-words text-xs leading-5 text-[var(--nx-text-secondary)]">{JSON.stringify(metadata,null,2)}</pre></Card>
                {canManage&&<Button type="button" loading={form.processing} disabled={!form.isDirty} onClick={save} leadingIcon={<Icon name="check" className="h-4 w-4"/>}>Save SEO metadata</Button>}
            </div>
        </div>
    </AdminLayout>;
}