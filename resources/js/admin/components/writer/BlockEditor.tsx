import { useMemo } from "react";
import { Icon } from "@admin/components/Icon";
import { Button, Card, IconButton, Input, Select, Textarea } from "@nexora/admin-ui";

export type WriterScalar = string | number | boolean | null;
export type WriterValue = WriterScalar | WriterValue[] | { [key: string]: WriterValue };

export type WriterBlock = {
    id: string;
    type: string;
    version: number;
    data: Record<string, WriterValue>;
    children: WriterValue[];
};

export type DocumentContent = { version: number; blocks: WriterBlock[] };
export type BlockDefinition = { type: string; name: string; category: string; schema?: Record<string, unknown> };
export type MediaOption = { id: number; name: string; url: string; alt_text: string | null; width: number | null; height: number | null };

const iconFor = (type: string) => type === "paragraph" ? "paragraph" : type === "heading" ? "heading" : type === "list" ? "list" : type === "quote" ? "quote" : type === "code" ? "code" : type === "image" ? "image" : "blocks";

function newBlock(type: string): WriterBlock {
    const base = { id: crypto.randomUUID(), type, version: 1, children: [] as WriterValue[] };
    if (type === "heading") return { ...base, data: { text: "", level: 2 } };
    if (type === "list") return { ...base, data: { style: "unordered", items: [] } };
    if (type === "quote") return { ...base, data: { text: "", attribution: "" } };
    if (type === "code") return { ...base, data: { code: "", language: "text" } };
    if (type === "divider") return { ...base, data: {} };
    if (type === "image") return { ...base, data: { media_asset_id: null, alt_text: "", caption: "" } };
    return { ...base, type: "paragraph", data: { text: "" } };
}

function stringValue(value: unknown): string { return typeof value === "string" ? value : ""; }
function numberValue(value: unknown, fallback: number): number { return typeof value === "number" ? value : fallback; }
function listValue(value: unknown): string[] { return Array.isArray(value) ? value.filter((item): item is string => typeof item === "string") : []; }

function BlockFields({ block, mediaAssets, onData }: { block: WriterBlock; mediaAssets: MediaOption[]; onData: (data: Record<string, WriterValue>) => void }) {
    if (block.type === "heading") {
        return <div className="grid gap-4 sm:grid-cols-[8rem_minmax(0,1fr)]"><Select label="Level" value={String(numberValue(block.data.level, 2))} onChange={(value) => onData({ ...block.data, level: Number(value) })} options={[1,2,3,4,5,6].map((level) => ({ value: String(level), label: `Heading ${level}` }))} /><Input label="Heading" value={stringValue(block.data.text)} onChange={(event) => onData({ ...block.data, text: event.target.value })} placeholder="Write a clear section heading…" /></div>;
    }
    if (block.type === "list") {
        return <div className="grid gap-4"><Select label="List style" value={stringValue(block.data.style) || "unordered"} onChange={(value) => onData({ ...block.data, style: value })} options={[{ value:"unordered", label:"Bulleted list" }, { value:"ordered", label:"Numbered list" }]} /><Textarea label="List items" value={listValue(block.data.items).join("\n")} onChange={(event) => onData({ ...block.data, items: event.target.value.split("\n").map((line) => line.trim()).filter(Boolean) })} hint="One item per line." rows={5} /></div>;
    }
    if (block.type === "quote") {
        return <div className="grid gap-4"><Textarea label="Quotation" value={stringValue(block.data.text)} onChange={(event) => onData({ ...block.data, text: event.target.value })} rows={4} placeholder="Add a quotation or pull quote…" /><Input label="Attribution" value={stringValue(block.data.attribution)} onChange={(event) => onData({ ...block.data, attribution: event.target.value })} placeholder="Optional source or speaker" /></div>;
    }
    if (block.type === "code") {
        return <div className="grid gap-4"><Input label="Language" value={stringValue(block.data.language)} onChange={(event) => onData({ ...block.data, language: event.target.value })} placeholder="php, ts, bash…" /><Textarea label="Code" value={stringValue(block.data.code)} onChange={(event) => onData({ ...block.data, code: event.target.value })} rows={10} className="font-mono" spellCheck={false} /></div>;
    }
    if (block.type === "divider") {
        return <div className="py-5"><div className="h-px w-full bg-[var(--nx-border-strong)]" /><p className="mt-3 text-center text-xs text-[var(--nx-text-muted)]">Section divider</p></div>;
    }
    if (block.type === "image") {
        const selected = mediaAssets.find((asset) => asset.id === Number(block.data.media_asset_id || 0));
        return <div className="grid gap-4"><Select label="Media Library image" value={selected ? String(selected.id) : ""} onChange={(value) => { const next = mediaAssets.find((asset) => String(asset.id) === value); onData({ ...block.data, media_asset_id: value ? Number(value) : null, alt_text: stringValue(block.data.alt_text) || next?.alt_text || "" }); }} options={[{ value:"", label:"Choose an image" }, ...mediaAssets.map((asset) => ({ value:String(asset.id), label:asset.name, description:asset.width&&asset.height?`${asset.width} × ${asset.height}`:undefined }))]} />{selected && <div className="overflow-hidden rounded-[var(--nx-radius-card)] border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)]"><img src={selected.url} alt="" className="max-h-80 w-full object-contain" loading="lazy" decoding="async" /></div>}<Input label="Alt text" value={stringValue(block.data.alt_text)} onChange={(event) => onData({ ...block.data, alt_text: event.target.value })} hint="Describe the image when it conveys meaning."/><Textarea label="Caption" rows={3} value={stringValue(block.data.caption)} onChange={(event) => onData({ ...block.data, caption: event.target.value })} /></div>;
    }
    return <Textarea label="Paragraph" value={stringValue(block.data.text)} onChange={(event) => onData({ ...block.data, text: event.target.value })} rows={6} placeholder="Start writing…" />;
}

export function BlockEditor({ value, definitions, mediaAssets = [], onChange }: { value: DocumentContent; definitions: BlockDefinition[]; mediaAssets?: MediaOption[]; onChange: (content: DocumentContent) => void }) {
    const definitionMap = useMemo(() => Object.fromEntries(definitions.map((item) => [item.type, item])), [definitions]);

    const updateBlock = (index: number, next: WriterBlock) => onChange({ ...value, blocks: value.blocks.map((block, position) => position === index ? next : block) });
    const addBlock = (type: string) => onChange({ ...value, blocks: [...value.blocks, newBlock(type)] });
    const removeBlock = (index: number) => onChange({ ...value, blocks: value.blocks.filter((_, position) => position !== index) });
    const move = (index: number, offset: -1 | 1) => {
        const target = index + offset;
        if (target < 0 || target >= value.blocks.length) return;
        const blocks = [...value.blocks];
        [blocks[index], blocks[target]] = [blocks[target], blocks[index]];
        onChange({ ...value, blocks });
    };
    const convert = (index: number, type: string) => updateBlock(index, { ...newBlock(type), id: value.blocks[index].id });

    return (
        <div className="grid gap-4">
            <Card className="p-4 sm:p-5">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 className="text-sm font-semibold text-[var(--nx-text)]">Writer blocks</h2><p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">Add semantic blocks. Nexora stores this as a versioned document tree rather than raw HTML.</p></div>
                    <span className="text-xs font-medium text-[var(--nx-text-muted)]">{value.blocks.length} {value.blocks.length === 1 ? "block" : "blocks"}</span>
                </div>
                <div className="mt-4 flex flex-wrap gap-2">
                    {definitions.map((definition) => <Button key={definition.type} type="button" size="sm" variant="secondary" leadingIcon={<Icon name={iconFor(definition.type)} className="h-4 w-4" />} onClick={() => addBlock(definition.type)}>Add {definition.name}</Button>)}
                </div>
            </Card>

            {value.blocks.length === 0 && <Card className="border-dashed p-8 text-center"><span className="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name="writer" className="h-6 w-6" /></span><h3 className="mt-4 text-sm font-semibold text-[var(--nx-text)]">Start the document body</h3><p className="mx-auto mt-2 max-w-md text-sm leading-6 text-[var(--nx-text-muted)]">Add a paragraph, heading, list, quote, code block or divider. Future publishing modules can register additional block types.</p><Button type="button" className="mt-5" leadingIcon={<Icon name="paragraph" className="h-4 w-4" />} onClick={() => addBlock("paragraph")}>Add first paragraph</Button></Card>}

            {value.blocks.map((block, index) => {
                const definition = definitionMap[block.type];
                return (
                    <Card key={block.id} className="p-4 sm:p-5">
                        <div className="mb-4 flex flex-col gap-3 border-b border-[var(--nx-border)] pb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex min-w-0 items-center gap-3"><span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]"><Icon name={iconFor(block.type)} className="h-4 w-4" /></span><div><p className="text-sm font-semibold text-[var(--nx-text)]">{definition?.name ?? block.type}</p><p className="text-xs text-[var(--nx-text-muted)]">Block {index + 1}</p></div></div>
                            <div className="flex items-center gap-1.5"><Select ariaLabel={`Change block ${index + 1} type`} className="w-44" value={block.type} onChange={(type) => convert(index, type)} options={definitions.map((item) => ({ value:item.type, label:item.name }))} /><IconButton label="Move block up" disabled={index === 0} onClick={() => move(index, -1)}><Icon name="up" className="h-4 w-4" /></IconButton><IconButton label="Move block down" disabled={index === value.blocks.length - 1} onClick={() => move(index, 1)}><Icon name="down" className="h-4 w-4" /></IconButton><IconButton label="Delete block" tone="danger" onClick={() => removeBlock(index)}><Icon name="trash" className="h-4 w-4" /></IconButton></div>
                        </div>
                        <BlockFields block={block} mediaAssets={mediaAssets} onData={(data) => updateBlock(index, { ...block, data })} />
                    </Card>
                );
            })}
        </div>
    );
}

export function documentStats(content: DocumentContent) {
    const text = content.blocks.flatMap((block) => {
        if (block.type === "list") return listValue(block.data.items);
        return [stringValue(block.data.text) || stringValue(block.data.code)];
    }).join(" ").trim();
    const words = text === "" ? 0 : text.split(/\s+/).filter(Boolean).length;
    return {
        blocks: content.blocks.length,
        words,
        readingMinutes: words === 0 ? 0 : Math.max(1, Math.ceil(words / 220)),
        headings: content.blocks.filter((block) => block.type === "heading").length,
    };
}
