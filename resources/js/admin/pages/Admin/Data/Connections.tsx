import { useMemo, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import type { SharedPageProps } from "@admin/types/page";
import { Badge, Button, Card, Input, Modal, Select } from "@nexora/admin-ui";

type CatalogItem = {
    key: string;
    label: string;
    kind: string;
    provider: string;
    available: boolean;
    requirement: string;
    example: string;
    availability_message: string;
};

type Connection = {
    id: number;
    name: string;
    driver: string;
    provider: string;
    purpose: string;
    status: string;
    enabled: boolean;
    endpoint: string | null;
    database: string | null;
    username: string | null;
    region: string;
    hasPassword: boolean;
    hasAccessKey: boolean;
    hasSecretKey: boolean;
    lastTestedAt: string | null;
    lastError: string | null;
};

type ConnectionFields = {
    name: string;
    driver: string;
    endpoint: string;
    database: string;
    username: string;
    password: string;
    region: string;
    access_key: string;
    secret_key: string;
};

function statusMeta(status: string): {
    label: string;
    tone: "neutral" | "success" | "warning" | "danger";
    icon: string;
} {
    if (status === "healthy") {
        return { label: "Healthy", tone: "success", icon: "success" };
    }
    if (status === "failed") {
        return { label: "Failed", tone: "danger", icon: "error" };
    }
    if (status === "adapter-missing") {
        return { label: "Adapter needed", tone: "warning", icon: "alert" };
    }
    if (status === "credential-rotation-required") {
        return { label: "Rotate credentials", tone: "warning", icon: "key" };
    }
    if (status === "unconfigured") {
        return { label: "Needs setup", tone: "warning", icon: "dashed" };
    }
    return { label: "Not tested", tone: "neutral", icon: "dashed" };
}

function emptyConnection(): ConnectionFields {
    return {
        name: "",
        driver: "mongodb",
        endpoint: "mongodb://127.0.0.1:27017",
        database: "",
        username: "",
        password: "",
        region: "us-east-1",
        access_key: "",
        secret_key: "",
    };
}

export default function DataConnections({
    connections,
    catalog,
}: {
    connections: Connection[];
    catalog: CatalogItem[];
}) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("data.connections.manage");
    const canTest = permissions.includes("data.connections.test");

    const [open, setOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Connection | null>(null);
    const [testing, setTesting] = useState<number | null>(null);
    const [toggling, setToggling] = useState<number | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<Connection | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [testMessage, setTestMessage] = useState<Record<number, string>>({});

    const form = useForm<ConnectionFields>(emptyConnection());
    const editForm = useForm<ConnectionFields>(emptyConnection());

    const selected = useMemo(
        () => catalog.find((item) => item.key === form.data.driver),
        [catalog, form.data.driver],
    );
    const editSelected = useMemo(
        () => catalog.find((item) => item.key === editForm.data.driver),
        [catalog, editForm.data.driver],
    );
    const options = catalog.map((item) => ({
        value: item.key,
        label: item.label,
        description: item.available
            ? item.kind
            : `${item.kind} · ${item.requirement} required`,
        leading: (
            <Icon
                name={
                    item.provider === "aws"
                        ? "cloud"
                        : item.provider === "redis"
                            ? "zap"
                            : "database"
                }
                className="h-4 w-4"
            />
        ),
    }));

    const submit = () => {
        form.post("/admin/data/connections", {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setOpen(false);
            },
        });
    };

    const openEdit = (connection: Connection) => {
        editForm.clearErrors();
        editForm.setData({
            name: connection.name,
            driver: connection.driver,
            endpoint: connection.endpoint ?? "",
            database: connection.database ?? "",
            username: connection.username ?? "",
            password: "",
            region: connection.region || "us-east-1",
            access_key: "",
            secret_key: "",
        });
        setEditTarget(connection);
    };

    const saveEdit = () => {
        if (!editTarget) return;
        editForm.patch(`/admin/data/connections/${editTarget.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditTarget(null),
        });
    };

    const test = async (connection: Connection) => {
        setTesting(connection.id);
        setTestMessage((value) => ({
            ...value,
            [connection.id]: "Testing connection…",
        }));
        try {
            const response = await fetch(`/admin/data/connections/${connection.id}/test`, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                            ?.content ?? "",
                },
            });
            const body = await response.json().catch(() => ({
                message: `Request failed (HTTP ${response.status}).`,
            }));
            setTestMessage((value) => ({
                ...value,
                [connection.id]: body.message ?? "Connection test finished.",
            }));
            router.reload({ only: ["connections"] });
        } finally {
            setTesting(null);
        }
    };

    const toggle = (connection: Connection) => {
        setToggling(connection.id);
        router.patch(
            `/admin/data/connections/${connection.id}`,
            { enabled: !connection.enabled },
            {
                preserveScroll: true,
                onFinish: () => setToggling(null),
            },
        );
    };

    return (
        <AdminLayout>
            <Head title="Data Connections" />
            <PageHeader
                eyebrow="Data fabric"
                title="Data Connections"
                description="Attach document stores, Redis services and cloud data platforms without coupling feature modules to credentials or vendor SDKs."
                actions={canManage ? (
                    <Button
                        onClick={() => setOpen(true)}
                        leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                    >
                        Add connection
                    </Button>
                ) : undefined}
            />

            <div className="mb-5 grid gap-3 md:grid-cols-3">
                <Card className="p-4">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]">
                            <Icon name="database" className="h-4 w-4" />
                        </span>
                        <div>
                            <div className="text-xs text-[var(--nx-text-muted)]">Connections</div>
                            <div className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">
                                {connections.length}
                            </div>
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-xl bg-green-50 text-green-700">
                            <Icon name="success" className="h-4 w-4" />
                        </span>
                        <div>
                            <div className="text-xs text-[var(--nx-text-muted)]">Healthy</div>
                            <div className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">
                                {connections.filter((item) => item.status === "healthy").length}
                            </div>
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-surface-subtle)] text-[var(--nx-text-secondary)]">
                            <Icon name="blocks" className="h-4 w-4" />
                        </span>
                        <div>
                            <div className="text-xs text-[var(--nx-text-muted)]">
                                Available connector types
                            </div>
                            <div className="mt-0.5 text-lg font-semibold text-[var(--nx-text)]">
                                {catalog.length}
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <div className="grid gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                {connections.map((connection) => {
                    const status = statusMeta(connection.status);
                    const definition = catalog.find(
                        (item) => item.key === connection.driver,
                    );
                    return (
                        <Card key={connection.id} className="p-5">
                            <div className="flex items-start justify-between gap-4">
                                <div className="flex min-w-0 gap-3">
                                    <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]">
                                        <Icon
                                            name={
                                                connection.provider === "aws"
                                                    ? "cloud"
                                                    : connection.driver.includes("redis")
                                                        ? "zap"
                                                        : "database"
                                            }
                                            className="h-5 w-5"
                                        />
                                    </span>
                                    <div className="min-w-0">
                                        <h2 className="truncate text-sm font-semibold text-[var(--nx-text)]">
                                            {connection.name}
                                        </h2>
                                        <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                            {definition?.kind ?? "Data service"}
                                        </p>
                                    </div>
                                </div>
                                <Badge tone={status.tone}>
                                    <span className="inline-flex items-center gap-1.5">
                                        <Icon name={status.icon} className="h-3.5 w-3.5" />
                                        {status.label}
                                    </span>
                                </Badge>
                            </div>

                            <div className="mt-4 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs text-[var(--nx-text-muted)]">
                                <div className="truncate">
                                    {connection.endpoint ?? "No endpoint configured"}
                                </div>
                                {connection.database && (
                                    <div className="mt-1">
                                        Database / namespace: {connection.database}
                                    </div>
                                )}
                                {connection.lastTestedAt && (
                                    <div className="mt-1">
                                        Last tested: {new Date(connection.lastTestedAt).toLocaleString()}
                                    </div>
                                )}
                            </div>

                            {(testMessage[connection.id] || connection.lastError) && (
                                <p className="mt-3 text-xs leading-5 text-[var(--nx-text-muted)]">
                                    {testMessage[connection.id] || connection.lastError}
                                </p>
                            )}

                            <div className="mt-4 flex flex-wrap gap-2">
                                {canTest && (
                                    <Button
                                        variant="secondary"
                                        loading={testing === connection.id}
                                        onClick={() => void test(connection)}
                                    >
                                        Test connection
                                    </Button>
                                )}
                                {canManage && (
                                    <Button
                                        variant={connection.enabled ? "secondary" : "primary"}
                                        loading={toggling === connection.id}
                                        disabled={!connection.enabled && connection.status !== "healthy"}
                                        onClick={() => toggle(connection)}
                                    >
                                        {connection.enabled ? "Disable" : "Enable"}
                                    </Button>
                                )}
                                {canManage && (
                                    <Button variant="ghost" onClick={() => openEdit(connection)}>
                                        Edit
                                    </Button>
                                )}
                                {canManage && !connection.enabled && (
                                    <Button
                                        variant="ghost"
                                        onClick={() => setDeleteTarget(connection)}
                                    >
                                        Remove
                                    </Button>
                                )}
                            </div>
                        </Card>
                    );
                })}

                {connections.length === 0 && (
                    <Card className="col-span-full p-8 text-center">
                        <span className="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]">
                            <Icon name="database" className="h-6 w-6" />
                        </span>
                        <h2 className="mt-4 font-semibold text-[var(--nx-text)]">
                            No auxiliary data services attached
                        </h2>
                        <p className="mx-auto mt-2 max-w-lg text-sm leading-6 text-[var(--nx-text-muted)]">
                            Your primary relational database is already configured. Add MongoDB,
                            Redis, Amazon DocumentDB, ElastiCache or DynamoDB only when a module
                            needs them.
                        </p>
                    </Card>
                )}
            </div>

            <Modal
                open={open}
                onClose={() => setOpen(false)}
                title="Add data connection"
                description="Credentials are encrypted at rest. Modules will consume connection handles rather than raw secrets."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setOpen(false)}>
                            Cancel
                        </Button>
                        <Button loading={form.processing} onClick={submit}>
                            Save connection
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Connection name"
                        value={form.data.name}
                        onChange={(event) => form.setData("name", event.target.value)}
                        error={form.errors.name}
                        placeholder="e.g. Product search cache"
                    />
                    <Select
                        label="Service"
                        value={form.data.driver}
                        onChange={(value) => {
                            form.setData("driver", value);
                            const item = catalog.find((entry) => entry.key === value);
                            if (item) {
                                form.setData("endpoint", item.example);
                                if (form.data.name.trim() === "") {
                                    form.setData("name", item.label);
                                }
                            }
                        }}
                        options={options}
                        error={form.errors.driver}
                    />
                    {selected && !selected.available && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/25 dark:text-amber-300">
                            <strong>Connector runtime is not installed yet.</strong>
                            <div className="mt-1">
                                {selected.requirement}. You can save the connection now and
                                install/enable its adapter later.
                            </div>
                        </div>
                    )}
                    <Input
                        label="Endpoint / connection string"
                        value={form.data.endpoint}
                        onChange={(event) => form.setData("endpoint", event.target.value)}
                        error={form.errors.endpoint}
                        placeholder={selected?.example}
                        hint="Keep credentials out of the endpoint; use the encrypted fields below."
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Database / namespace"
                            value={form.data.database}
                            onChange={(event) => form.setData("database", event.target.value)}
                            error={form.errors.database}
                        />
                        <Input
                            label="Username"
                            value={form.data.username}
                            onChange={(event) => form.setData("username", event.target.value)}
                            error={form.errors.username}
                        />
                    </div>
                    <Input
                        label="Password / token"
                        type="password"
                        value={form.data.password}
                        onChange={(event) => form.setData("password", event.target.value)}
                        error={form.errors.password}
                    />
                    {form.data.driver === "aws_dynamodb" && (
                        <>
                            <Input
                                label="AWS region"
                                value={form.data.region}
                                onChange={(event) => form.setData("region", event.target.value)}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Access key (optional with IAM role)"
                                    value={form.data.access_key}
                                    onChange={(event) => form.setData("access_key", event.target.value)}
                                />
                                <Input
                                    label="Secret key"
                                    type="password"
                                    value={form.data.secret_key}
                                    onChange={(event) => form.setData("secret_key", event.target.value)}
                                />
                            </div>
                        </>
                    )}
                    <div className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs leading-5 text-[var(--nx-text-muted)]">
                        New connections start disabled. Run a successful connection test before
                        Nexora lets modules use them.
                    </div>
                </div>
            </Modal>

            <Modal
                open={editTarget !== null}
                onClose={() => setEditTarget(null)}
                title={editTarget ? `Edit ${editTarget.name}` : "Edit data connection"}
                description="Secret values are never returned to the browser. Leave secret fields blank to keep the encrypted values already stored."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setEditTarget(null)}>
                            Cancel
                        </Button>
                        <Button loading={editForm.processing} onClick={saveEdit}>
                            Save changes
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Connection name"
                        value={editForm.data.name}
                        onChange={(event) => editForm.setData("name", event.target.value)}
                        error={editForm.errors.name}
                    />
                    <Select
                        label="Service"
                        value={editForm.data.driver}
                        onChange={() => undefined}
                        options={options}
                        disabled
                        hint="Connector type is immutable. Create a new connection to change service type."
                    />
                    {editSelected && !editSelected.available && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/25 dark:text-amber-300">
                            {editSelected.availability_message}
                        </div>
                    )}
                    <Input
                        label="Endpoint / connection string"
                        value={editForm.data.endpoint}
                        onChange={(event) => editForm.setData("endpoint", event.target.value)}
                        error={editForm.errors.endpoint}
                        hint="Credentials inside the endpoint are rejected."
                    />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Database / namespace"
                            value={editForm.data.database}
                            onChange={(event) => editForm.setData("database", event.target.value)}
                            error={editForm.errors.database}
                        />
                        <Input
                            label="Username"
                            value={editForm.data.username}
                            onChange={(event) => editForm.setData("username", event.target.value)}
                            error={editForm.errors.username}
                        />
                    </div>
                    <Input
                        label="Rotate password / token"
                        type="password"
                        value={editForm.data.password}
                        onChange={(event) => editForm.setData("password", event.target.value)}
                        error={editForm.errors.password}
                        placeholder={editTarget?.hasPassword ? "Encrypted value is stored" : "No password stored"}
                        hint="Leave blank to preserve the current encrypted value."
                    />
                    {editForm.data.driver === "aws_dynamodb" && (
                        <>
                            <Input
                                label="AWS region"
                                value={editForm.data.region}
                                onChange={(event) => editForm.setData("region", event.target.value)}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Rotate access key"
                                    value={editForm.data.access_key}
                                    onChange={(event) => editForm.setData("access_key", event.target.value)}
                                    placeholder={editTarget?.hasAccessKey ? "Encrypted key is stored" : "No key stored"}
                                    hint="Leave blank to preserve the stored key."
                                />
                                <Input
                                    label="Rotate secret key"
                                    type="password"
                                    value={editForm.data.secret_key}
                                    onChange={(event) => editForm.setData("secret_key", event.target.value)}
                                    placeholder={editTarget?.hasSecretKey ? "Encrypted secret is stored" : "No secret stored"}
                                    hint="Leave blank to preserve the stored secret."
                                />
                            </div>
                        </>
                    )}
                    <div className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs leading-5 text-[var(--nx-text-muted)]">
                        Changing endpoint, namespace, username, region or credentials invalidates
                        the previous health test. Nexora will disable the connection until it
                        passes a fresh test.
                    </div>
                </div>
            </Modal>

            <ConfirmDialog
                open={deleteTarget !== null}
                title="Remove data connection"
                description={
                    deleteTarget
                        ? `Remove ${deleteTarget.name}? Stored credentials for this connection will be deleted from Nexora.`
                        : ""
                }
                confirmLabel="Remove connection"
                processing={deleting}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => {
                    if (!deleteTarget) return;
                    setDeleting(true);
                    router.delete(`/admin/data/connections/${deleteTarget.id}`, {
                        preserveScroll: true,
                        onFinish: () => {
                            setDeleting(false);
                            setDeleteTarget(null);
                        },
                    });
                }}
            />
        </AdminLayout>
    );
}
