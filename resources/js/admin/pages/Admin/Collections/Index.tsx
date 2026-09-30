import { useState } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, IconButton, Input, Modal, Select, Textarea } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";

type CollectionField = { key: string; label: string; type: "text" | "long-text" | "number" | "boolean" | "date" | "url"; required: boolean };
type CollectionRow = { id: number; uuid: string; name: string; slug: string; description: string | null; status: "active" | "archived"; document_type: string | null; documents_count: number; updated_at: string | null };
type TypeOption = { key: string; name: string; description: string };

const fieldTypes = [
    { value: "text", label: "Text" },
    { value: "long-text", label: "Long text" },
    { value: "number", label: "Number" },
    { value: "boolean", label: "Boolean" },
    { value: "date", label: "Date" },
    { value: "url", label: "URL" },
];
const newField = (): CollectionField => ({ key: "", label: "", type: "text", required: false });

export default function CollectionsIndex({ collections, types }: { collections: CollectionRow[]; types: TypeOption[] }) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("collections.manage");
    const [open, setOpen] = useState(false);
    const form = useForm<{ name: string; slug: string; description: string; status: "active" | "archived"; document_type: string; schema: CollectionField[] }>({
        name: "", slug: "", description: "", status: "active", document_type: "", schema: [],
    });

    const updateField = (index: number, patch: Partial<CollectionField>) => form.setData("schema", form.data.schema.map((field, position) => position === index ? { ...field, ...patch } : field));
    const removeField = (index: number) => form.setData("schema", form.data.schema.filter((_, position) => position !== index));
    const create = () => form.post("/admin/collections", { preserveScroll: true, onSuccess: () => { setOpen(false); form.reset(); } });

    return <AdminLayout>
        <Head title="Content Collections" />
        <PageHeader
            eyebrow="CMS"
            title="Content Collections"
            description="Define reusable content groupings with typed custom fields, then attach structured Nexora documents as collection entries."
            actions={canManage ? <Button onClick={() => setOpen(true)} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>New collection</Button> : undefined}
        />

        {collections.length === 0 ? <Card className="border-dashed p-10 text-center"><span className="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="layers" className="h-6 w-6" /></span><h2 className="mt-4 text-base font-semibold text-[var(--nx-text)]">No content collections yet</h2><p className="mx-auto mt-2 max-w-xl text-sm leading-6 text-[var(--nx-text-muted)]">Collections let you organize documents into reusable CMS datasets without changing Document Engine storage or bypassing tenant boundaries.</p>{canManage && <Button className="mt-5" onClick={() => setOpen(true)}>Create first collection</Button>}</Card> : <div className="grid gap-4 xl:grid-cols-2">
            {collections.map((collection) => <Card key={collection.id} className="p-5">
                <div className="flex items-start justify-between gap-4"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><h2 className="truncate text-base font-semibold text-[var(--nx-text)]">{collection.name}</h2><Badge tone={collection.status === "active" ? "success" : "neutral"}>{collection.status}</Badge></div><p className="mt-1 text-xs text-[var(--nx-text-muted)]">/{collection.slug} · {collection.document_type ? `${collection.document_type} only` : "Any document type"}</p></div><span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="layers" className="h-4 w-4" /></span></div>
                {collection.description && <p className="mt-4 line-clamp-2 text-sm leading-6 text-[var(--nx-text-secondary)]">{collection.description}</p>}
                <div className="mt-4 flex items-center justify-between rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] px-3 py-2.5"><span className="text-xs text-[var(--nx-text-muted)]">Entries</span><span className="text-sm font-semibold text-[var(--nx-text)]">{collection.documents_count}</span></div>
                <ButtonLink href={`/admin/collections/${collection.id}`} className="mt-4" leadingIcon={<Icon name="right" className="h-4 w-4" />}>Manage collection</ButtonLink>
            </Card>)}
        </div>}

        <Modal open={open} onClose={() => setOpen(false)} title="Create content collection" description="Choose the document scope and optional typed fields that every entry in this collection can store." footer={<><Button variant="secondary" onClick={() => setOpen(false)}>Cancel</Button><Button loading={form.processing} onClick={create}>Create collection</Button></>}>
            <div className="grid gap-4">
                <Input label="Collection name" value={form.data.name} onChange={(event) => form.setData("name", event.target.value)} error={form.errors.name} placeholder="Case studies" />
                <Input label="Slug" value={form.data.slug} onChange={(event) => form.setData("slug", event.target.value.toLowerCase())} error={form.errors.slug} hint="Optional. Nexora generates it from the name when blank." />
                <Textarea label="Description" value={form.data.description} onChange={(event) => form.setData("description", event.target.value)} error={form.errors.description} rows={3} />
                <Select label="Allowed document type" value={form.data.document_type} onChange={(value) => form.setData("document_type", value)} options={[{ value: "", label: "Any document type" }, ...types.map((type) => ({ value: type.key, label: type.name, description: type.description }))]} error={form.errors.document_type} />
                <div className="rounded-xl border border-[var(--nx-border)] p-4"><div className="flex items-center justify-between gap-3"><div><p className="text-sm font-semibold text-[var(--nx-text)]">Custom fields</p><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Optional structured values stored with each collection entry.</p></div><Button type="button" size="sm" variant="secondary" onClick={() => form.setData("schema", [...form.data.schema, newField()])} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>Add field</Button></div>
                    <div className="mt-4 grid gap-3">{form.data.schema.map((field, index) => <Card key={index} className="p-3 shadow-none"><div className="grid gap-3 sm:grid-cols-2"><Input label="Field label" value={field.label} onChange={(event) => updateField(index, { label: event.target.value })} placeholder="Client name" /><Input label="Field key" value={field.key} onChange={(event) => updateField(index, { key: event.target.value.toLowerCase().replace(/[^a-z0-9_]/g, "_") })} placeholder="client_name" /><Select label="Type" value={field.type} onChange={(value) => updateField(index, { type: value as CollectionField["type"] })} options={fieldTypes} /><div className="flex items-end gap-2"><Select className="flex-1" label="Required" value={field.required ? "yes" : "no"} onChange={(value) => updateField(index, { required: value === "yes" })} options={[{ value: "no", label: "Optional" }, { value: "yes", label: "Required" }]} /><IconButton label={`Remove field ${field.label || index + 1}`} tone="danger" onClick={() => removeField(index)}><Icon name="trash" className="h-4 w-4" /></IconButton></div></div></Card>)}{form.data.schema.length === 0 && <p className="rounded-lg border border-dashed border-[var(--nx-border)] px-3 py-4 text-center text-xs text-[var(--nx-text-muted)]">No custom fields. The collection can still group documents.</p>}</div>
                </div>
            </div>
        </Modal>
    </AdminLayout>;
}
