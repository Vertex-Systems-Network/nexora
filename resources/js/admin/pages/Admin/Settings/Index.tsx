import { Head, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { MediaPicker } from "@admin/components/MediaPicker";
import { PageHeader } from "@admin/components/PageHeader";
import { Button, Card, ColorInput, Input, Select } from "@nexora/admin-ui";

type Settings = {
    appName: string;
    logoUrl: string;
    defaultTimezone: string;
    defaultLocale: string;
    theme: "light" | "dark" | "system";
    primary: string;
    density: "comfortable" | "compact";
    radius: "small" | "medium" | "large";
};

type Option = { value: string; label: string; description?: string };

export default function SettingsPage({ settings, timezoneOptions, localeOptions }: { settings: Settings; timezoneOptions: Option[]; localeOptions: Option[] }) {
    const form = useForm(settings);
    const logoPreview = form.data.logoUrl.trim() || "/brand/nexora-mark.svg";

    return (
        <AdminLayout>
            <Head title="Settings" />
            <PageHeader eyebrow="Platform" title="Site & admin settings" description="Manage site identity, regional defaults and the shared Nexora admin design tokens from one controlled settings surface." />

            <form onSubmit={(event) => { event.preventDefault(); form.put("/admin/settings", { preserveScroll: true }); }} className="grid gap-4 xl:grid-cols-[1fr_22rem]">
                <Card className="p-5 sm:p-6">
                    <div className="grid gap-7">
                        <section className="grid gap-4">
                            <div><h2 className="text-base font-semibold text-[var(--nx-text)]">Identity</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Site/workspace name and logo shown across Nexora administration and authentication.</p></div>
                            <Input label="Application name" name="appName" value={form.data.appName} onChange={(e) => form.setData("appName", e.target.value)} error={form.errors.appName} />
                            <div className="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                <Input label="Logo URL or media path" name="logoUrl" value={form.data.logoUrl} onChange={(e) => form.setData("logoUrl", e.target.value)} error={form.errors.logoUrl} placeholder="/media/... or https://..." hint="Choose an existing image from Media Library or enter an approved public path/URL. Leave empty for the Nexora default mark." />
                                <MediaPicker value={form.data.logoUrl} type="image" onChange={(url) => form.setData("logoUrl", url)} buttonLabel="Choose media" />
                            </div>
                        </section>

                        <section className="grid gap-4 border-t border-[var(--nx-border)] pt-6">
                            <div><h2 className="text-base font-semibold text-[var(--nx-text)]">Regional defaults</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Defaults for new or unaffiliated sessions. Individual user language preferences still take precedence.</p></div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Select label="Default language" value={form.data.defaultLocale} onChange={(value) => form.setData("defaultLocale", value)} options={localeOptions} />
                                <Select label="Default display timezone" value={form.data.defaultTimezone} onChange={(value) => form.setData("defaultTimezone", value)} options={timezoneOptions} />
                            </div>
                            <p className="text-xs leading-5 text-[var(--nx-text-muted)]">Nexora infrastructure continues to use its certified runtime timezone; this setting is the site/workspace default for presentation and business workflows.</p>
                        </section>

                        <section className="grid gap-4 border-t border-[var(--nx-border)] pt-6">
                            <div><h2 className="text-base font-semibold text-[var(--nx-text)]">Appearance</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Shared design-system defaults used by the administration UI.</p></div>
                            <Select label="Default admin theme" value={form.data.theme} onChange={(value) => form.setData("theme", value as Settings["theme"])} options={[
                                { value: "system", label: "System", description: "Follow the device appearance preference by default." },
                                { value: "light", label: "Light" },
                                { value: "dark", label: "Dark" },
                            ]} />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Select label="Density" value={form.data.density} onChange={(value) => form.setData("density", value as Settings["density"])} options={[{ value: "comfortable", label: "Comfortable" }, { value: "compact", label: "Compact" }]} />
                                <Select label="Radius" value={form.data.radius} onChange={(value) => form.setData("radius", value as Settings["radius"])} options={[{ value: "small", label: "Small" }, { value: "medium", label: "Medium" }, { value: "large", label: "Large" }]} />
                            </div>
                            <ColorInput label="Primary color" value={form.data.primary} onChange={(value) => form.setData("primary", value)} error={form.errors.primary} />
                        </section>

                        <div className="flex justify-end border-t border-[var(--nx-border)] pt-5"><Button type="submit" loading={form.processing} disabled={!form.isDirty}>Save changes</Button></div>
                    </div>
                </Card>

                <Card className="h-fit p-5 sm:p-6">
                    <p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Live preview</p>
                    <div className="mt-5 rounded-2xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                        <div className="flex items-center gap-3">
                            <img src={logoPreview} alt="" className="h-11 w-11 rounded-xl object-contain shadow-sm" />
                            <div className="min-w-0"><p className="truncate text-sm font-semibold text-[var(--nx-text)]">{form.data.appName || "Nexora"}</p><p className="text-xs text-[var(--nx-text-muted)]">{form.data.defaultLocale.toUpperCase()} · {form.data.defaultTimezone}</p></div>
                        </div>
                        <div className="mt-5 h-10 rounded-xl" style={{ background: form.data.primary }} />
                        <div className="mt-4 h-3 w-2/3 rounded bg-[var(--nx-border-strong)]" /><div className="mt-2 h-3 w-1/2 rounded bg-[var(--nx-border)]" />
                        <Button type="button" className="mt-5 w-full">Preview action</Button>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
