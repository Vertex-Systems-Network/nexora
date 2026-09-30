import { useMemo, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { DataTable, type Column, type Paginator } from "@admin/components/data/DataTable";
import { EmptyState } from "@admin/components/LoadingStates";
import {
    Badge,
    Button,
    ButtonLink,
    Card,
    Checkbox,
    FilePicker,
    Input,
    Modal,
} from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type ExtensionRow = {
    id: string;
    identifier: string;
    name: string;
    type: string;
    status: string;
    current_version: string | null;
    versions_count: number;
    description: string | null;
    publisher: string | null;
    trust_tier: string | null;
    installed_at: string | null;
};
type Artifact = {
    id: string;
    identifier: string;
    name: string;
    version: string;
    type: string;
    trust_tier: string;
    signature_status: string;
    publisher: string | null;
    content_sha256: string;
};
type Source = {
    id: string;
    name: string;
    base_url: string;
    status: string;
    trusted_only: boolean;
    items_count: number;
    last_synced_at: string | null;
    last_error: string | null;
};
type Catalog = {
    id: string;
    identifier: string;
    name: string;
    type: string;
    version: string;
    description: string | null;
    publisher_key_id: string | null;
    source: string | null;
    synced_at: string | null;
};
type Props = {
    extensions: Paginator<ExtensionRow>;
    eligibleArtifacts: Artifact[];
    sources: Source[];
    catalog: Catalog[];
    summary: { installed: number; enabled: number; versions: number; catalog: number };
    canManage: boolean;
    canInstall: boolean;
    canManageMarketplace: boolean;
};

const human = (value: string) =>
    value.replace(/[_-]+/g, " ").replace(/\b\w/g, (character) => character.toUpperCase());
const tone = (status: string): "success" | "warning" | "danger" | "brand" | "neutral" =>
    status === "enabled" || status === "trusted" || status === "verified" || status === "active"
        ? "success"
        : status === "installed" || status === "disabled" || status === "paused"
            ? "warning"
            : status === "uninstalled"
                ? "neutral"
                : "brand";

export default function ExtensionsIndex({
    extensions,
    eligibleArtifacts,
    sources,
    catalog,
    summary,
    canManage,
    canInstall,
    canManageMarketplace,
}: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canScan =
        permissions.includes("security.sentinel.scan") &&
        permissions.includes("security.sentinel.view");
    const canInstallTheme = permissions.includes("themes.install");
    const canUpload = canInstall && canScan;
    const canStageCatalogItem = (item: Catalog) => item.type === "theme" ? canInstallTheme : canInstall;
    const [sourceOpen, setSourceOpen] = useState(false);
    const [sourceDeleteTarget, setSourceDeleteTarget] = useState<Source | null>(null);
    const source = useForm({ name: "", base_url: "", trusted_publishers_only: true });
    const [uploadOpen, setUploadOpen] = useState(false);
    const [uploadFile, setUploadFile] = useState<File | null>(null);
    const upload = useForm<{ package: File | null }>({ package: null });

    const closeUpload = () => {
        setUploadOpen(false);
        setUploadFile(null);
        upload.reset();
        upload.clearErrors();
    };
    const submitUpload = () => {
        if (!uploadFile) return;
        upload.clearErrors();
        upload.post("/admin/security/sentinel", {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                setUploadOpen(false);
                setUploadFile(null);
                upload.reset();
            },
        });
    };

    const columns = useMemo<Column<ExtensionRow>[]>(
        () => [
            {
                key: "extension",
                label: "Extension",
                render: (extension) => (
                    <div>
                        <div className="font-semibold text-[var(--nx-text)]">{extension.name}</div>
                        <div className="mt-1 text-xs text-[var(--nx-text-muted)]">
                            {extension.identifier} · {human(extension.type)}
                        </div>
                    </div>
                ),
            },
            {
                key: "status",
                label: "Status",
                render: (extension) => <Badge tone={tone(extension.status)}>{human(extension.status)}</Badge>,
            },
            {
                key: "version",
                label: "Version",
                render: (extension) => (
                    <div className="text-sm text-[var(--nx-text-secondary)]">
                        {extension.current_version ?? "Not enabled"}
                        <div className="text-xs text-[var(--nx-text-muted)]">
                            {extension.versions_count} installed version
                            {extension.versions_count === 1 ? "" : "s"}
                        </div>
                    </div>
                ),
            },
            {
                key: "publisher",
                label: "Publisher",
                render: (extension) => (
                    <div className="text-sm text-[var(--nx-text-secondary)]">
                        {extension.publisher ?? "Unsigned / unknown"}
                        {extension.trust_tier && (
                            <div className="text-xs text-[var(--nx-text-muted)]">
                                {human(extension.trust_tier)}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: "actions",
                label: "",
                className: "w-28 text-right",
                render: (extension) => (
                    <ButtonLink href={`/admin/extensions/${extension.id}`} size="sm" variant="secondary">
                        Manage
                    </ButtonLink>
                ),
            },
        ],
        [],
    );

    const headerActions = canUpload || canManageMarketplace ? (
        <div className="flex flex-wrap gap-2">
            {canUpload && (
                <Button
                    leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                    onClick={() => {
                        upload.clearErrors();
                        setUploadOpen(true);
                    }}
                >
                    Upload extension
                </Button>
            )}
            {canManageMarketplace && (
                <Button
                    variant="secondary"
                    leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                    onClick={() => setSourceOpen(true)}
                >
                    Add catalog source
                </Button>
            )}
        </div>
    ) : undefined;

    return (
        <AdminLayout>
            <Head title="Extensions" />
            <PageHeader
                eyebrow="Platform ecosystem"
                title="Extensions"
                description="Upload packages into Sentinel quarantine, install only ALLOW artifacts, review requested capabilities, manage versions and keep Marketplace downloads inside the same trust boundary."
                actions={headerActions}
            />

            <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    { l: "Installed", v: summary.installed, i: "blocks" },
                    { l: "Enabled", v: summary.enabled, i: "success" },
                    { l: "Installed versions", v: summary.versions, i: "history" },
                    { l: "Active catalog packages", v: summary.catalog, i: "package" },
                ].map((item) => (
                    <Card key={item.l} className="p-4">
                        <div className="flex items-center gap-3">
                            <span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]">
                                <Icon name={item.i} className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-xs text-[var(--nx-text-muted)]">{item.l}</p>
                                <p className="text-xl font-semibold text-[var(--nx-text)]">{item.v}</p>
                            </div>
                        </div>
                    </Card>
                ))}
            </div>

            <DataTable
                rows={extensions.data}
                columns={columns}
                paginator={extensions}
                empty={(
                    <EmptyState
                        title="No extensions installed"
                        description="Upload an extension ZIP through Sentinel, review its scan and install the verified artifact here."
                    />
                )}
            />

            <div className="mt-5 grid gap-5 xl:grid-cols-2">
                <Card className="p-5 sm:p-6">
                    <div className="mb-4">
                        <h2 className="font-semibold text-[var(--nx-text)]">Verified packages ready to install</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Only Sentinel ALLOW artifacts with supply-chain identity appear here.
                        </p>
                    </div>
                    <div className="grid gap-3">
                        {eligibleArtifacts.length === 0 ? (
                            <p className="py-6 text-center text-sm text-[var(--nx-text-muted)]">
                                No verified extension artifacts are waiting.
                            </p>
                        ) : (
                            eligibleArtifacts.map((artifact) => (
                                <div key={artifact.id} className="rounded-xl border border-[var(--nx-border)] p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-[var(--nx-text)]">{artifact.name}</p>
                                            <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                                {artifact.identifier} · v{artifact.version} · {human(artifact.type)}
                                            </p>
                                            <div className="mt-2 flex gap-2">
                                                <Badge tone={tone(artifact.trust_tier)}>{human(artifact.trust_tier)}</Badge>
                                                <Badge>{human(artifact.signature_status)}</Badge>
                                            </div>
                                        </div>
                                        {canInstall && (
                                            <Button
                                                size="sm"
                                                onClick={() => router.post(`/admin/extensions/install/${artifact.id}`, {}, { preserveScroll: true })}
                                            >
                                                Install
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </Card>

                <Card className="p-5 sm:p-6">
                    <div className="mb-4">
                        <h2 className="font-semibold text-[var(--nx-text)]">Marketplace sources</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Catalog metadata is discoverable here; package bytes still go through quarantine and Sentinel.
                        </p>
                    </div>
                    <div className="grid gap-3">
                        {sources.length === 0 ? (
                            <p className="py-6 text-center text-sm text-[var(--nx-text-muted)]">No catalog source configured.</p>
                        ) : (
                            sources.map((catalogSource) => (
                                <div key={catalogSource.id} className="rounded-xl border border-[var(--nx-border)] p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <p className="font-semibold text-[var(--nx-text)]">{catalogSource.name}</p>
                                                <Badge tone={tone(catalogSource.status)}>{human(catalogSource.status)}</Badge>
                                            </div>
                                            <p className="mt-1 truncate text-xs text-[var(--nx-text-muted)]">{catalogSource.base_url}</p>
                                            <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                                {catalogSource.items_count} cached packages · {catalogSource.trusted_only ? "Trusted publishers only" : "Sentinel-screened publishers"}
                                            </p>
                                            {catalogSource.last_synced_at && (
                                                <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                                    Last synced {new Date(catalogSource.last_synced_at).toLocaleString()}
                                                </p>
                                            )}
                                            {catalogSource.last_error && (
                                                <p className="mt-2 text-xs text-[var(--nx-danger)]">{catalogSource.last_error}</p>
                                            )}
                                        </div>
                                    </div>
                                    {canManageMarketplace && (
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            {catalogSource.status === "active" && (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    onClick={() => router.post(`/admin/extensions/marketplace/sources/${catalogSource.id}/sync`, {}, { preserveScroll: true })}
                                                >
                                                    Sync
                                                </Button>
                                            )}
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => router.patch(
                                                    `/admin/extensions/marketplace/sources/${catalogSource.id}/status`,
                                                    { status: catalogSource.status === "active" ? "paused" : "active" },
                                                    { preserveScroll: true },
                                                )}
                                            >
                                                {catalogSource.status === "active" ? "Pause" : "Resume"}
                                            </Button>
                                            {catalogSource.status === "paused" && (
                                                <Button size="sm" variant="ghost" onClick={() => setSourceDeleteTarget(catalogSource)}>
                                                    Remove
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </Card>
            </div>

            <Card className="mt-5 p-5 sm:p-6">
                <div className="mb-4">
                    <h2 className="font-semibold text-[var(--nx-text)]">Marketplace catalog</h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        Only active, synchronized sources are listed. Stage downloads into Sentinel; never install catalog code directly from the network. Theme entries require Theme install permission; extension-family entries require Extension install permission.
                    </p>
                </div>
                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {catalog.length === 0 ? (
                        <p className="col-span-full py-6 text-center text-sm text-[var(--nx-text-muted)]">
                            No active Marketplace packages are available. Add or resume a source, then synchronize it.
                        </p>
                    ) : (
                        catalog.map((item) => (
                            <div key={item.id} className="rounded-xl border border-[var(--nx-border)] p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-semibold text-[var(--nx-text)]">{item.name}</p>
                                        <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                            {item.identifier} · v{item.version}
                                        </p>
                                    </div>
                                    <Badge>{human(item.type)}</Badge>
                                </div>
                                {item.description && (
                                    <p className="mt-3 text-sm leading-6 text-[var(--nx-text-secondary)]">{item.description}</p>
                                )}
                                <p className="mt-3 text-xs text-[var(--nx-text-muted)]">Source: {item.source ?? "Catalog"}</p>
                                {canStageCatalogItem(item) && (
                                    <Button
                                        className="mt-3"
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => router.post(
                                            `/admin/extensions/marketplace/catalog/${item.id}/stage`,
                                            {},
                                            { preserveScroll: true },
                                        )}
                                    >
                                        Send to Sentinel
                                    </Button>
                                )}
                            </div>
                        ))
                    )}
                </div>
            </Card>

            <Modal
                open={uploadOpen}
                onClose={closeUpload}
                title="Upload extension"
                description="The package is quarantined and scanned before it can appear in the verified install queue."
                footer={(
                    <>
                        <Button variant="secondary" onClick={closeUpload}>Cancel</Button>
                        <Button loading={upload.processing} disabled={!uploadFile} onClick={submitUpload}>
                            Send to Sentinel
                        </Button>
                    </>
                )}
            >
                <FilePicker
                    label="Extension package"
                    description="Upload a Nexora ZIP package. Sentinel inspects the manifest, archive paths and code before any install is possible."
                    accept=".zip,application/zip"
                    file={uploadFile}
                    error={upload.errors.package}
                    onChange={(next) => {
                        setUploadFile(next);
                        upload.setData("package", next);
                        upload.clearErrors("package");
                    }}
                />
                <div className="mt-4 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs leading-5 text-[var(--nx-text-muted)]">
                    <strong className="text-[var(--nx-text)]">Trust boundary:</strong> upload does not install or execute code. A Sentinel ALLOW decision and supply-chain artifact are required before the Install action becomes available.
                </div>
            </Modal>

            <Modal
                open={sourceOpen}
                onClose={() => setSourceOpen(false)}
                title="Add Marketplace catalog"
                description="Only approved HTTPS public-network sources are accepted. Package downloads still pass through Sentinel."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setSourceOpen(false)}>Cancel</Button>
                        <Button
                            loading={source.processing}
                            onClick={() => source.post("/admin/extensions/marketplace/sources", {
                                preserveScroll: true,
                                onSuccess: () => {
                                    source.reset();
                                    setSourceOpen(false);
                                },
                            })}
                        >
                            Add source
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Catalog name"
                        value={source.data.name}
                        onChange={(event) => source.setData("name", event.target.value)}
                        error={source.errors.name}
                    />
                    <Input
                        label="Base URL"
                        value={source.data.base_url}
                        onChange={(event) => source.setData("base_url", event.target.value)}
                        placeholder="https://marketplace.example.com"
                        error={source.errors.base_url}
                    />
                    <Checkbox
                        label="Require trusted publishers"
                        checked={source.data.trusted_publishers_only}
                        onChange={(event) => source.setData("trusted_publishers_only", event.target.checked)}
                    />
                </div>
            </Modal>

            <ConfirmDialog
                open={sourceDeleteTarget !== null}
                title="Remove Marketplace source"
                description={
                    sourceDeleteTarget
                        ? `Remove ${sourceDeleteTarget.name} and its local catalog cache? Installed extensions are not removed.`
                        : ""
                }
                confirmLabel="Remove source"
                onCancel={() => setSourceDeleteTarget(null)}
                onConfirm={() => {
                    if (!sourceDeleteTarget) return;
                    router.delete(`/admin/extensions/marketplace/sources/${sourceDeleteTarget.id}`, {
                        preserveScroll: true,
                        onFinish: () => setSourceDeleteTarget(null),
                    });
                }}
            />
        </AdminLayout>
    );
}
