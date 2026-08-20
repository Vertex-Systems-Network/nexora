import { useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, Card, ColorInput, FilePicker, Input, Modal, Select } from "@nexora/admin-ui";

type ThemeToken = { key: string; label: string; type: "color" | "text" | "number" | "select"; value: string | number | null; default: string | number | null; options: string[] };
type ThemeVersion = { id: number; version: string; sha256: string; installedAt: string | null };
type Theme = {
    id: number;
    identifier: string;
    name: string;
    description: string | null;
    status: string;
    isBuiltin: boolean;
    activeVersionId: number | null;
    version: string | null;
    engine: string | null;
    screenshot: string | null;
    versions: ThemeVersion[];
    tokens: ThemeToken[];
};
type Permissions = { install: boolean; activate: boolean; manage: boolean; preview: boolean };

function ThemeTokenEditor({ theme, canManage }: { theme: Theme; canManage: boolean }) {
    const initial = useMemo(() => Object.fromEntries(theme.tokens.map((token) => [token.key, token.value ?? token.default ?? ""])), [theme]);
    const form = useForm<{ tokens: Record<string, string | number> }>({ tokens: initial });
    if (theme.tokens.length === 0) return null;

    return (
        <Card className="p-5 sm:p-6">
            <div className="flex flex-col gap-2 border-b border-[var(--nx-border)] pb-5 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand)]">Design tokens</p>
                    <h2 className="mt-1 text-base font-semibold text-[var(--nx-text)]">Customize {theme.name}</h2>
                    <p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Token overrides stay separate from theme files, so switching or updating a theme never requires editing package code.</p>
                </div>
                <Badge tone="success"><span className="inline-flex items-center gap-1.5"><Icon name="check" className="h-3.5 w-3.5" />Theme-safe</span></Badge>
            </div>
            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                {theme.tokens.map((token) => {
                    const value = form.data.tokens[token.key] ?? token.default ?? "";
                    if (token.type === "color") {
                        return <ColorInput key={token.key} label={token.label} value={String(value)} onChange={(next) => form.setData("tokens", { ...form.data.tokens, [token.key]: next })} />;
                    }
                    if (token.type === "select") {
                        return <Select key={token.key} label={token.label} value={String(value)} onChange={(next) => form.setData("tokens", { ...form.data.tokens, [token.key]: next })} options={token.options.map((option) => ({ value: option, label: option }))} />;
                    }
                    return <Input key={token.key} label={token.label} type={token.type === "number" ? "number" : "text"} value={String(value)} onChange={(event) => form.setData("tokens", { ...form.data.tokens, [token.key]: token.type === "number" ? Number(event.target.value) : event.target.value })} />;
                })}
            </div>
            {canManage && <div className="mt-5 flex justify-end border-t border-[var(--nx-border)] pt-5"><Button loading={form.processing} disabled={!form.isDirty} onClick={() => form.put(`/admin/appearance/themes/${theme.id}/tokens`, { preserveScroll: true })}>Save design tokens</Button></div>}
        </Card>
    );
}

export default function ThemesPage({ themes, canRollback, permissions }: { themes: Theme[]; canRollback: boolean; permissions: Permissions }) {
    const [installOpen, setInstallOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [previewing, setPreviewing] = useState<number | null>(null);
    const [activating, setActivating] = useState<number | null>(null);
    const [selectedVersions, setSelectedVersions] = useState<Record<number, string>>(() => Object.fromEntries(themes.map((theme) => [theme.id, String(theme.activeVersionId ?? theme.versions[0]?.id ?? "")])));
    const upload = useForm<{ package: File | null }>({ package: null });
    const active = themes.find((theme) => theme.status === "active") ?? themes[0];

    const install = () => {
        if (!file) return;
        upload.post("/admin/appearance/themes/install", {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { setInstallOpen(false); setFile(null); upload.reset(); },
        });
    };

    const preview = async (versionId: number) => {
        setPreviewing(versionId);
        try {
            const response = await fetch(`/admin/appearance/themes/versions/${versionId}/preview`, {
                method: "POST",
                headers: { Accept: "application/json", "X-CSRF-TOKEN": document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "" },
            });
            const body = await response.json().catch(() => ({}));
            if (response.ok && body.url) window.open(body.url, "_blank", "noopener,noreferrer");
        } finally { setPreviewing(null); }
    };

    const activate = (versionId: number) => {
        setActivating(versionId);
        router.post(`/admin/appearance/themes/versions/${versionId}/activate`, {}, { preserveScroll: true, onFinish: () => setActivating(null) });
    };

    return (
        <AdminLayout>
            <Head title="Themes" />
            <PageHeader
                eyebrow="Appearance"
                title="Themes"
                description="Install Sentinel-verified, non-executable themes. Preview privately, switch atomically, customize design tokens and roll back without changing document or SEO semantics."
                actions={permissions.install ? <Button onClick={() => setInstallOpen(true)} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>Install theme</Button> : undefined}
            />

            <div className="mb-5 grid gap-3 md:grid-cols-3">
                <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="palette" className="h-4 w-4" /></span><div><div className="text-xs text-[var(--nx-text-muted)]">Installed themes</div><div className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">{themes.length}</div></div></div></Card>
                <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-green-50 text-green-700 dark:bg-green-950/25 dark:text-green-300"><Icon name="success" className="h-4 w-4" /></span><div><div className="text-xs text-[var(--nx-text-muted)]">Active theme</div><div className="mt-0.5 truncate text-sm font-semibold text-[var(--nx-text)]">{active?.name ?? "Safe fallback"}</div></div></div></Card>
                <Card className="p-4"><div className="flex items-center gap-3"><span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-surface-subtle)] text-[var(--nx-text-muted)]"><Icon name="shield" className="h-4 w-4" /></span><div><div className="text-xs text-[var(--nx-text-muted)]">Execution policy</div><div className="mt-0.5 text-sm font-semibold text-[var(--nx-text)]">Safe HTML only</div></div></div></Card>
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                {themes.map((theme) => {
                    const selectedVersionId = Number(selectedVersions[theme.id] || theme.activeVersionId || theme.versions[0]?.id || 0);
                    const current = theme.versions.find((version) => version.id === selectedVersionId) ?? theme.versions.find((version) => version.id === theme.activeVersionId) ?? theme.versions[0];
                    return (
                        <Card key={theme.id} className="overflow-hidden">
                            <div className="aspect-[16/9] overflow-hidden border-b border-[var(--nx-border)] bg-[var(--nx-surface-subtle)]">
                                {theme.screenshot ? <img src={theme.screenshot} alt={`${theme.name} preview`} className="h-full w-full object-cover" loading="lazy" decoding="async" /> : <div className="grid h-full place-items-center text-[var(--nx-text-muted)]"><Icon name="palette" className="h-10 w-10" /></div>}
                            </div>
                            <div className="p-5">
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><h2 className="text-base font-semibold text-[var(--nx-text)]">{theme.name}</h2>{theme.status === "active" && <Badge tone="success">Active</Badge>}{theme.isBuiltin && <Badge>Built-in fallback</Badge>}</div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">{theme.identifier} · v{current?.version ?? theme.version}</p></div>
                                    <Badge tone="neutral">{theme.engine === "nexora-safe-html" ? "Safe HTML" : theme.engine ?? "Unknown"}</Badge>
                                </div>
                                <p className="mt-3 text-sm leading-6 text-[var(--nx-text-muted)]">{theme.description || "No theme description provided."}</p>
                                {theme.versions.length > 1 && <div className="mt-4"><Select label="Installed version" value={String(current?.id ?? "")} onChange={(value) => setSelectedVersions((state) => ({ ...state, [theme.id]: value }))} options={theme.versions.map((version) => ({ value: String(version.id), label: `Version ${version.version}`, description: version.id === theme.activeVersionId ? "Currently active" : version.installedAt ? `Installed ${new Date(version.installedAt).toLocaleDateString()}` : "Installed" }))} /></div>}
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {permissions.preview && current && <Button variant="secondary" loading={previewing === current.id} leadingIcon={<Icon name="eye" className="h-4 w-4" />} onClick={() => void preview(current.id)}>Preview</Button>}
                                    {permissions.activate && current && (theme.status !== "active" || current.id !== theme.activeVersionId) && <Button loading={activating === current.id} onClick={() => activate(current.id)}>{theme.status === "active" ? "Switch version" : "Activate"}</Button>}
                                    {theme.status === "active" && <Button variant="secondary" leadingIcon={<Icon name="external" className="h-4 w-4" />} onClick={() => window.open("/", "_blank", "noopener,noreferrer")}>View site</Button>}
                                </div>
                            </div>
                        </Card>
                    );
                })}
            </div>

            {permissions.activate && canRollback && <Card className="mt-5 flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 className="font-semibold text-[var(--nx-text)]">Activation rollback available</h2><p className="mt-1 text-sm text-[var(--nx-text-muted)]">Restore the previous theme/version activation snapshot without touching documents, SEO records or theme files.</p></div><Button variant="secondary" leadingIcon={<Icon name="rollback" className="h-4 w-4" />} onClick={() => router.post("/admin/appearance/themes/rollback", {}, { preserveScroll: true })}>Rollback theme</Button></Card>}

            {active && <div className="mt-5"><ThemeTokenEditor theme={active} canManage={permissions.manage} /></div>}

            <Modal open={installOpen} onClose={() => setInstallOpen(false)} title="Install theme" description="Every uploaded theme enters Sentinel quarantine first. Only an ALLOW decision reaches the Theme Engine." footer={<><Button variant="secondary" onClick={() => setInstallOpen(false)}>Cancel</Button><Button loading={upload.processing} disabled={!file} onClick={install}>Scan & install theme</Button></>}>
                <FilePicker label="Theme package" description="Upload a Nexora theme ZIP containing nexora.json, theme.json, declared HTML templates and static CSS/image assets." accept=".zip,application/zip" file={file} onChange={(next) => { setFile(next); upload.setData("package", next); }} />
                <div className="mt-4 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs leading-5 text-[var(--nx-text-muted)]"><strong className="text-[var(--nx-text)]">N0.20 security boundary:</strong> themes cannot ship PHP, JavaScript, executable scripts or undeclared files. Interactive behavior will come later through separately permissioned extensions/Studio capabilities.</div>
            </Modal>
        </AdminLayout>
    );
}
