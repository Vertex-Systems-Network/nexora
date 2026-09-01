import { useMemo, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, IconButton, Input, Modal, Select, Textarea } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type FieldType = "text" | "long-text" | "number" | "boolean" | "date" | "url";
type CollectionField = { key: string; label: string; type: FieldType; required: boolean };
type CollectionData = { id: number; uuid: string; name: string; slug: string; description: string | null; status: "active" | "archived"; document_type: string | null; documents_count: number; updated_at: string | null; schema: CollectionField[] };
type ServerEntryData = Record<string, unknown>;
type DocumentRow = { id: number; title: string; slug: string | null; type: string; status: string; position: number; data: ServerEntryData };
type AvailableDocument = { id: number; title: string; slug: string | null; type: string; status: string };
type TypeOption = { key: string; name: string; description: string };
type EntryValue = string | number | boolean | null;
type EntryData = Record<string, EntryValue>;

const fieldTypes = [
    { value: "text", label: "Text" }, { value: "long-text", label: "Long text" }, { value: "number", label: "Number" },
    { value: "boolean", label: "Boolean" }, { value: "date", label: "Date" }, { value: "url", label: "URL" },
];
const newField = (): CollectionField => ({ key: "", label: "", type: "text", required: false });
const initialData = (schema: CollectionField[]): EntryData => Object.fromEntries(schema.map((field) => [field.key, field.type === "boolean" ? false : ""])) as EntryData;
const normalizeEntryData = (data: ServerEntryData): EntryData => Object.fromEntries(Object.entries(data).map(([key, value]) => {
    if (value === null || typeof value === "string" || typeof value === "number" || typeof value === "boolean") return [key, value];
    if (value === undefined) return [key, null];
    return [key, String(value)];
})) as EntryData;

function FieldInput({ field, value, onChange }: { field: CollectionField; value: unknown; onChange: (value: EntryValue) => void }) {
    if (field.type === "long-text") return <Textarea label={`${field.label}${field.required ? " *" : ""}`} value={String(value ?? "")} onChange={(event) => onChange(event.target.value)} rows={4} />;
    if (field.type === "boolean") return <Select label={`${field.label}${field.required ? " *" : ""}`} value={value === true || value === "true" || value === 1 ? "true" : "false"} onChange={(next) => onChange(next === "true")} options={[{ value: "false", label: "No" }, { value: "true", label: "Yes" }]} />;
    const type = field.type === "number" ? "number" : field.type === "date" ? "date" : field.type === "url" ? "url" : "text";
    return <Input label={`${field.label}${field.required ? " *" : ""}`} type={type} value={String(value ?? "")} onChange={(event) => onChange(field.type === "number" && event.target.value !== "" ? Number(event.target.value) : event.target.value)} />;
}

export default function CollectionShow({ collection, documents, availableDocuments, types }: { collection: CollectionData; documents: DocumentRow[]; availableDocuments: AvailableDocument[]; types: TypeOption[] }) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("collections.manage");
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [attachOpen, setAttachOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [detachTarget, setDetachTarget] = useState<DocumentRow | null>(null);
    const [editTarget, setEditTarget] = useState<DocumentRow | null>(null);
    const [entryData, setEntryData] = useState<EntryData>({});
    const [entrySaving, setEntrySaving] = useState(false);

    const settings = useForm<{ name: string; slug: string; description: string; status: "active" | "archived"; document_type: string; schema: CollectionField[] }>({
        name: collection.name, slug: collection.slug, description: collection.description ?? "", status: collection.status, document_type: collection.document_type ?? "", schema: collection.schema ?? [],
    });
    const attach = useForm<{ document_id: string; data: EntryData }>({ document_id: "", data: initialData(collection.schema ?? []) });
    const availableOptions = useMemo(() => availableDocuments.map((document) => ({ value: String(document.id), label: document.title, description: `${document.type} · ${document.status}` })), [availableDocuments]);

    const updateField = (index: number, patch: Partial<CollectionField>) => settings.setData("schema", settings.data.schema.map((field, position) => position === index ? { ...field, ...patch } : field));
    const removeField = (index: number) => settings.setData("schema", settings.data.schema.filter((_, position) => position !== index));
    const saveSettings = () => settings.put(`/admin/collections/${collection.id}`, { preserveScroll: true, onSuccess: () => setSettingsOpen(false) });
    const attachDocument = () => attach.post(`/admin/collections/${collection.id}/documents`, { preserveScroll: true, onSuccess: () => { setAttachOpen(false); attach.reset(); attach.setData("data", initialData(collection.schema ?? [])); } });
    const openEntry = (document: DocumentRow) => { setEditTarget(document); setEntryData({ ...initialData(collection.schema ?? []), ...normalizeEntryData(document.data ?? {}) }); };
    const saveEntry = () => {
        if (!editTarget) return;
        setEntrySaving(true);
        router.put(`/admin/collections/${collection.id}/documents/${editTarget.id}`, { data: entryData }, { preserveScroll: true, onSuccess: () => setEditTarget(null), onFinish: () => setEntrySaving(false) });
    };

    return <AdminLayout>
        <Head title={`Collection · ${collection.name}`} />
        <PageHeader
            eyebrow="CMS Collection"
            title={collection.name}
            description={collection.description ?? `/${collection.slug} · ${collection.document_type ? `${collection.document_type} entries` : "mixed document types"}`}
            actions={<div className="flex flex-wrap gap-2"><ButtonLink href="/admin/collections" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4" />}>Collections</ButtonLink>{canManage && <><Button variant="secondary" onClick={() => setSettingsOpen(true)} leadingIcon={<Icon name="settings" className="h-4 w-4" />}>Collection settings</Button><Button onClick={() => setAttachOpen(true)} disabled={availableDocuments.length === 0} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>Add document</Button></>}</div>}
        />

        <div className="grid gap-3 md:grid-cols-3">
            <Card className="p-4"><p className="text-xs text-[var(--nx-text-muted)]">Entries</p><p className="mt-1 text-xl font-semibold text-[var(--nx-text)]">{documents.length}</p></Card>
            <Card className="p-4"><p className="text-xs text-[var(--nx-text-muted)]">Custom fields</p><p className="mt-1 text-xl font-semibold text-[var(--nx-text)]">{collection.schema.length}</p></Card>
            <Card className="p-4"><p className="text-xs text-[var(--nx-text-muted)]">Status</p><div className="mt-2"><Badge tone={collection.status === "active" ? "success" : "neutral"}>{collection.status}</Badge></div></Card>
        </div>

        {documents.length === 0 ? <Card className="border-dashed p-10 text-center"><span className="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="layers" className="h-6 w-6" /></span><h2 className="mt-4 text-base font-semibold text-[var(--nx-text)]">Collection is empty</h2><p className="mx-auto mt-2 max-w-xl text-sm text-[var(--nx-text-muted)]">Attach an existing document. The document remains independently editable while collection-specific fields live on the collection entry.</p>{canManage && availableDocuments.length > 0 && <Button className="mt-5" onClick={() => setAttachOpen(true)}>Add first document</Button>}</Card> : <div className="grid gap-3">
            {documents.map((document) => <Card key={document.id} className="p-4"><div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><h2 className="truncate text-sm font-semibold text-[var(--nx-text)]">{document.title}</h2><Badge>{document.type}</Badge><Badge tone={document.status === "published" ? "success" : "warning"}>{document.status}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Position {document.position}{document.slug ? ` · /${document.slug}` : ""}</p>{collection.schema.length > 0 && <div className="mt-3 flex flex-wrap gap-2">{collection.schema.slice(0, 4).map((field) => <span key={field.key} className="rounded-lg border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] px-2 py-1 text-xs text-[var(--nx-text-secondary)]"><strong>{field.label}:</strong> {String(document.data?.[field.key] ?? "—")}</span>)}</div>}</div><div className="flex shrink-0 gap-2"><ButtonLink href={`/admin/documents/${document.id}/edit`} variant="secondary" size="sm">Edit document</ButtonLink>{canManage && collection.schema.length > 0 && <Button size="sm" variant="secondary" onClick={() => openEntry(document)}>Edit fields</Button>}{canManage && <IconButton label={`Remove ${document.title} from collection`} tone="danger" onClick={() => setDetachTarget(document)}><Icon name="trash" className="h-4 w-4" /></IconButton>}</div></div></Card>)}
        </div>}

        {canManage && <div className="flex justify-end"><Button variant="danger" onClick={() => setDeleteOpen(true)} leadingIcon={<Icon name="trash" className="h-4 w-4" />}>Delete collection</Button></div>}

        <Modal open={attachOpen} onClose={() => setAttachOpen(false)} title="Add document to collection" description="Only documents from the active organization are available. Type restrictions are enforced again on the server." footer={<><Button variant="secondary" onClick={() => setAttachOpen(false)}>Cancel</Button><Button loading={attach.processing} disabled={!attach.data.document_id} onClick={attachDocument}>Add document</Button></>}>
            <div className="grid gap-4"><Select label="Document" value={attach.data.document_id} onChange={(value) => attach.setData("document_id", value)} options={[{ value: "", label: "Choose document" }, ...availableOptions]} error={attach.errors.document_id} />{collection.schema.map((field) => <FieldInput key={field.key} field={field} value={attach.data.data?.[field.key]} onChange={(value) => attach.setData("data", { ...attach.data.data, [field.key]: value })} />)}</div>
        </Modal>

        <Modal open={Boolean(editTarget)} onClose={() => setEditTarget(null)} title={editTarget ? `Edit fields · ${editTarget.title}` : "Edit collection fields"} description="These values belong to this collection entry; the underlying document content remains unchanged." footer={<><Button variant="secondary" onClick={() => setEditTarget(null)}>Cancel</Button><Button loading={entrySaving} onClick={saveEntry}>Save fields</Button></>}>
            <div className="grid gap-4">{collection.schema.map((field) => <FieldInput key={field.key} field={field} value={entryData[field.key]} onChange={(value) => setEntryData((current) => ({ ...current, [field.key]: value }))} />)}</div>
        </Modal>

        <Modal open={settingsOpen} onClose={() => setSettingsOpen(false)} title="Collection settings" description="Schema changes are validated against existing entries before Nexora commits them." footer={<><Button variant="secondary" onClick={() => setSettingsOpen(false)}>Cancel</Button><Button loading={settings.processing} onClick={saveSettings}>Save collection</Button></>}>
            <div className="grid gap-4"><Input label="Name" value={settings.data.name} onChange={(event) => settings.setData("name", event.target.value)} error={settings.errors.name} /><Input label="Slug" value={settings.data.slug} onChange={(event) => settings.setData("slug", event.target.value.toLowerCase())} error={settings.errors.slug} /><Textarea label="Description" value={settings.data.description} onChange={(event) => settings.setData("description", event.target.value)} rows={3} /><div className="grid gap-3 sm:grid-cols-2"><Select label="Status" value={settings.data.status} onChange={(value) => settings.setData("status", value as "active" | "archived")} options={[{ value: "active", label: "Active" }, { value: "archived", label: "Archived" }]} /><Select label="Allowed document type" value={settings.data.document_type} onChange={(value) => settings.setData("document_type", value)} options={[{ value: "", label: "Any document type" }, ...types.map((type) => ({ value: type.key, label: type.name }))]} /></div><div className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex items-center justify-between gap-3"><div><p className="text-sm font-semibold text-[var(--nx-text)]">Custom fields</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Adding a required field will fail safely until existing entries contain a value.</p></div><Button type="button" size="sm" variant="secondary" onClick={() => settings.setData("schema", [...settings.data.schema, newField()])}>Add field</Button></div><div className="mt-4 grid gap-3">{settings.data.schema.map((field, index) => <Card key={index} className="p-3 shadow-none"><div className="grid gap-3 sm:grid-cols-2"><Input label="Label" value={field.label} onChange={(event) => updateField(index, { label: event.target.value })} /><Input label="Key" value={field.key} onChange={(event) => updateField(index, { key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, "_") })} /><Select label="Type" value={field.type} onChange={(value) => updateField(index, { type: value as FieldType })} options={fieldTypes} /><div className="flex items-end gap-2"><Select className="flex-1" label="Requirement" value={field.required ? "yes" : "no"} onChange={(value) => updateField(index, { required: value === "yes" })} options={[{ value: "no", label: "Optional" }, { value: "yes", label: "Required" }]} /><IconButton label={`Remove field ${field.label || index + 1}`} tone="danger" onClick={() => removeField(index)}><Icon name="trash" className="h-4 w-4" /></IconButton></div></div></Card>)}</div></div></div>
        </Modal>

        <ConfirmDialog open={deleteOpen} title="Delete content collection" description={`Delete “${collection.name}”? Documents remain intact; only collection membership and collection-specific values are removed.`} confirmLabel="Delete collection" onCancel={() => setDeleteOpen(false)} onConfirm={() => router.delete(`/admin/collections/${collection.id}`)} />
        <ConfirmDialog open={Boolean(detachTarget)} title="Remove document from collection" description={detachTarget ? `Remove “${detachTarget.title}” from this collection? The document itself will not be deleted.` : ""} confirmLabel="Remove document" onCancel={() => setDetachTarget(null)} onConfirm={() => { if (!detachTarget) return; router.delete(`/admin/collections/${collection.id}/documents/${detachTarget.id}`, { onFinish: () => setDetachTarget(null) }); }} />
    </AdminLayout>;
}
