import { Head, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Button, Card, ColorInput, Input, Select } from "@nexora/admin-ui";

type Settings = { appName: string; theme: "light" | "dark" | "system"; primary: string; density: "comfortable" | "compact"; radius: "small" | "medium" | "large" };

export default function SettingsPage({ settings }: { settings: Settings }) {
    const form = useForm(settings);

    return (
        <AdminLayout>
            <Head title="Settings" />
            <PageHeader eyebrow="Customization" title="Admin appearance" description="Nexora's admin UI is token-driven. These foundation settings already control brand color, theme, density and radius without coupling feature modules to vendor styles." />

            <form onSubmit={(event) => { event.preventDefault(); form.put("/admin/settings", { preserveScroll: true }); }} className="grid gap-4 xl:grid-cols-[1fr_22rem]">
                <Card className="p-5 sm:p-6">
                    <div className="grid gap-6">
                        <div><h2 className="text-base font-semibold text-[var(--nx-text)]">General</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Core branding and admin presentation.</p></div>
                        <Input label="Application name" name="appName" value={form.data.appName} onChange={(e) => form.setData("appName", e.target.value)} error={form.errors.appName} />
                        <Select label="Default admin theme" value={form.data.theme} onChange={(value) => form.setData("theme", value as Settings["theme"])} options={[
                            { value: "system", label: "System", description: "Default to the device appearance preference. Admins can temporarily override this from the top bar." },
                            { value: "light", label: "Light" },
                            { value: "dark", label: "Dark" },
                        ]} />
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Select label="Density" value={form.data.density} onChange={(value) => form.setData("density", value as Settings["density"])} options={[{ value: "comfortable", label: "Comfortable" }, { value: "compact", label: "Compact" }]} />
                            <Select label="Radius" value={form.data.radius} onChange={(value) => form.setData("radius", value as Settings["radius"])} options={[{ value: "small", label: "Small" }, { value: "medium", label: "Medium" }, { value: "large", label: "Large" }]} />
                        </div>
                        <ColorInput label="Primary color" value={form.data.primary} onChange={(value) => form.setData("primary", value)} error={form.errors.primary} />
                        <div className="flex justify-end border-t border-[var(--nx-border)] pt-5"><Button type="submit" loading={form.processing} disabled={!form.isDirty}>Save changes</Button></div>
                    </div>
                </Card>

                <Card className="h-fit p-5 sm:p-6">
                    <p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">Live token preview</p>
                    <div className="mt-5 rounded-2xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4">
                        <div className="h-10 rounded-xl" style={{ background: form.data.primary }} />
                        <div className="mt-4 h-3 w-2/3 rounded bg-[var(--nx-border-strong)]" /><div className="mt-2 h-3 w-1/2 rounded bg-[var(--nx-border)]" />
                        <Button type="button" className="mt-5 w-full">Preview action</Button>
                    </div>
                </Card>
            </form>
        </AdminLayout>
    );
}
