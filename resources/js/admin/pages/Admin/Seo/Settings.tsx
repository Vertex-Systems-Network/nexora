import { Head, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Button, ButtonLink, Card, Input } from "@nexora/admin-ui";

type Settings = { site_name:string; organization_name:string; organization_url:string; organization_logo:string };

export default function SeoSettings({ settings }: { settings:Settings }) {
    const form = useForm(settings);
    return <AdminLayout>
        <Head title="SEO settings"/>
        <PageHeader eyebrow="SEO Core" title="Site identity" description="Define the site and organization identities used by Nexora's central Schema Graph. Themes consume the graph; they do not own it." actions={<ButtonLink href="/admin/seo" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4"/>}>Back to SEO</ButtonLink>}/>
        <form onSubmit={(event)=>{event.preventDefault();form.put("/admin/seo/settings",{preserveScroll:true});}} className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <Card className="p-5 sm:p-6"><div className="grid gap-5"><Input label="Site name" value={form.data.site_name} onChange={(event)=>form.setData("site_name",event.target.value)} error={form.errors.site_name}/><Input label="Organization name" value={form.data.organization_name} onChange={(event)=>form.setData("organization_name",event.target.value)} error={form.errors.organization_name} hint="Optional. Leave blank for personal/non-organization sites."/><Input label="Organization URL" value={form.data.organization_url} onChange={(event)=>form.setData("organization_url",event.target.value)} error={form.errors.organization_url} placeholder="https://example.com"/><Input label="Organization logo URL" value={form.data.organization_logo} onChange={(event)=>form.setData("organization_logo",event.target.value)} error={form.errors.organization_logo} placeholder="https://example.com/logo.png"/><div className="flex justify-end border-t border-[var(--nx-border)] pt-5"><Button type="submit" loading={form.processing} disabled={!form.isDirty}>Save SEO identity</Button></div></div></Card>
            <Card className="h-fit p-5 sm:p-6"><span className="grid h-10 w-10 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name="blocks" className="h-5 w-5"/></span><h2 className="mt-4 text-sm font-semibold text-[var(--nx-text)]">Central Schema Graph</h2><p className="mt-2 text-sm leading-6 text-[var(--nx-text-muted)]">Nexora creates stable WebSite, Organization and resource nodes. Extensions can add nodes through the SEO capability layer without injecting conflicting JSON-LD from themes.</p></Card>
        </form>
    </AdminLayout>;
}
