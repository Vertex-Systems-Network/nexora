import { useMemo, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import type { CSSProperties, DragEvent } from "react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { Badge, Button, ButtonLink, Card, ColorInput, IconButton, Input, Select } from "@nexora/admin-ui";

type Breakpoint = "desktop" | "tablet" | "mobile";
type StudioNode = {
    id: string;
    type: string;
    props: Record<string, string | number>;
    styles: Record<"base" | "tablet" | "mobile", Record<string, string>>;
    bindings: Record<string, string>;
    children: StudioNode[];
};
type StudioContent = { version: 1; children: StudioNode[] };
type ElementDefinition = {
    type: string; name: string; category: string; icon: string; acceptsChildren: boolean;
    defaultProps: Record<string, string | number>; defaultStyles: Record<"base" | "tablet" | "mobile", Record<string, string>>; bindableProps: string[];
};
type Binding = { key: string; label: string; group: string; description: string };
type ComponentData = { id: number; name: string; category: string; content: StudioNode };
type CanvasData = {
    id: number; uuid: string; name: string; scope: string; status: "draft" | "published"; documentId: number | null;
    document: { id: number; title: string; excerpt: string | null } | null; themeId: number | null; theme: string | null; templateKey: string | null;
    content: StudioContent; lockVersion: number; updatedAt: string | null; publishedAt: string | null; revisions: Array<{ revision: number; createdAt: string | null }>;
};
type ThemeToken = { key: string; label: string; value: string };

type History = { past: StudioContent[]; present: StudioContent; future: StudioContent[] };

const deepClone = <T,>(value: T): T => JSON.parse(JSON.stringify(value)) as T;
const viewportKey = (viewport: Breakpoint): "base" | "tablet" | "mobile" => viewport === "desktop" ? "base" : viewport;
const widthFor = (viewport: Breakpoint) => viewport === "mobile" ? 390 : viewport === "tablet" ? 768 : 1180;
const createId = () => crypto.randomUUID();

function makeNode(definition: ElementDefinition): StudioNode {
    return {
        id: createId(),
        type: definition.type,
        props: deepClone(definition.defaultProps ?? {}),
        styles: {
            base: deepClone(definition.defaultStyles?.base ?? {}),
            tablet: deepClone(definition.defaultStyles?.tablet ?? {}),
            mobile: deepClone(definition.defaultStyles?.mobile ?? {}),
        },
        bindings: {},
        children: [],
    };
}

function findNode(nodes: StudioNode[], id: string): StudioNode | null {
    for (const node of nodes) {
        if (node.id === id) return node;
        const child = findNode(node.children, id);
        if (child) return child;
    }
    return null;
}

function mapNodes(nodes: StudioNode[], id: string, callback: (node: StudioNode) => StudioNode): StudioNode[] {
    return nodes.map((node) => node.id === id ? callback(node) : { ...node, children: mapNodes(node.children, id, callback) });
}

function removeNode(nodes: StudioNode[], id: string): StudioNode[] {
    return nodes.filter((node) => node.id !== id).map((node) => ({ ...node, children: removeNode(node.children, id) }));
}

function appendNode(nodes: StudioNode[], parentId: string | null, node: StudioNode): StudioNode[] {
    if (!parentId) return [...nodes, node];
    return mapNodes(nodes, parentId, (parent) => ({ ...parent, children: [...parent.children, node] }));
}

function flatten(nodes: StudioNode[], depth = 0): Array<{ node: StudioNode; depth: number }> {
    return nodes.flatMap((node) => [{ node, depth }, ...flatten(node.children, depth + 1)]);
}

function resolveText(node: StudioNode, document: CanvasData["document"]): string {
    const source = node.bindings.text;
    if (source === "document.title") return document?.title ?? "Document title";
    if (source === "document.excerpt") return document?.excerpt ?? "Document excerpt";
    if (source === "seo.title") return document?.title ?? "SEO title";
    if (source === "site.name") return "Nexora";
    return String(node.props.text ?? "");
}

function cssStyle(node: StudioNode, viewport: Breakpoint): CSSProperties {
    const merged = { ...node.styles.base, ...(viewport === "desktop" ? {} : node.styles[viewport]) };
    const style: CSSProperties = {};
    if (merged.gap) style.gap = merged.gap;
    if (merged.padding) style.padding = merged.padding;
    if (merged.margin) style.margin = merged.margin;
    if (merged.maxWidth) style.maxWidth = merged.maxWidth;
    if (merged.minHeight) style.minHeight = merged.minHeight;
    if (merged.textAlign) style.textAlign = merged.textAlign as CSSProperties["textAlign"];
    if (merged.fontSize) style.fontSize = merged.fontSize;
    if (merged.fontWeight) style.fontWeight = merged.fontWeight as CSSProperties["fontWeight"];
    if (merged.color) style.color = merged.color;
    if (merged.backgroundColor) style.backgroundColor = merged.backgroundColor;
    if (merged.borderRadius) style.borderRadius = merged.borderRadius;
    if (merged.width) style.width = merged.width;
    if (merged.height) style.height = merged.height;
    return style;
}

function CanvasNodeView({ node, selectedId, viewport, document, onSelect }: { node: StudioNode; selectedId: string | null; viewport: Breakpoint; document: CanvasData["document"]; onSelect: (id: string) => void }) {
    const selected = selectedId === node.id;
    const style = cssStyle(node, viewport);
    const content = resolveText(node, document);
    const merged = { ...node.styles.base, ...(viewport === "desktop" ? {} : node.styles[viewport]) };
    const children = node.children.map((child) => <CanvasNodeView key={child.id} node={child} selectedId={selectedId} viewport={viewport} document={document} onSelect={onSelect} />);
    const shell = "relative rounded-lg transition outline-none "+(selected ? "ring-2 ring-[var(--nx-brand-500)] ring-offset-2 ring-offset-white dark:ring-offset-slate-950" : "hover:ring-1 hover:ring-[var(--nx-border-strong)]");

    let body;
    if (node.type === "section") body = <div style={style} className="min-h-20">{children.length ? children : <div className="rounded-lg border border-dashed border-[var(--nx-border)] p-6 text-center text-xs text-[var(--nx-text-muted)]">Drop or add elements into this section</div>}</div>;
    else if (node.type === "stack") body = <div style={{ ...style, display: "flex", flexDirection: (node.props.direction === "horizontal" ? "row" : "column") }}>{children}</div>;
    else if (node.type === "grid") body = <div style={{ ...style, display: "grid", gridTemplateColumns: `repeat(${Math.max(1, Number(merged.columns ?? node.props.columns ?? 2))}, minmax(0, 1fr))` }}>{children}</div>;
    else if (node.type === "heading") body = <div style={style} className="font-semibold leading-tight">{content || "Heading"}</div>;
    else if (node.type === "button") body = <span style={style} className="inline-flex rounded-lg bg-[var(--nx-brand-600)] px-4 py-2 text-sm font-semibold text-white">{content || "Button"}</span>;
    else if (node.type === "divider") body = <div style={style} className="h-px bg-[var(--nx-border-strong)]" />;
    else if (node.type === "spacer") body = <div style={{ ...style, height: Number(node.props.size ?? 32) }} className="rounded border border-dashed border-[var(--nx-border)] bg-[var(--nx-surface-subtle)]" />;
    else body = <p style={style} className="leading-7">{content || "Text"}</p>;

    return <Card role="button" tabIndex={0} aria-label={`Select ${node.type} element`} className={`${shell} border-transparent bg-transparent p-2 shadow-none`} onClick={(event) => { event.stopPropagation(); onSelect(node.id); }} onKeyDown={(event) => { if (event.key === "Enter" || event.key === " ") { event.preventDefault(); onSelect(node.id); } }}>{body}</Card>;
}

export default function StudioEditor({ canvas, elements, bindings, components, themeTokens }: { canvas: CanvasData; elements: ElementDefinition[]; bindings: Binding[]; components: ComponentData[]; themeTokens: ThemeToken[] }) {
    const elementMap = useMemo(() => Object.fromEntries(elements.map((definition) => [definition.type, definition])), [elements]);
    const [history, setHistory] = useState<History>({ past: [], present: deepClone(canvas.content), future: [] });
    const [selectedId, setSelectedId] = useState<string | null>(history.present.children[0]?.id ?? null);
    const [viewport, setViewport] = useState<Breakpoint>("desktop");
    const [panel, setPanel] = useState<"elements" | "layers" | "components">("elements");
    const [preview, setPreview] = useState(false);
    const [publishing, setPublishing] = useState(false);
    const [componentName, setComponentName] = useState("");
    const saveForm = useForm<{ name: string; content: StudioContent; lock_version: number }>({ name: canvas.name, content: deepClone(canvas.content), lock_version: canvas.lockVersion });
    const componentForm = useForm<{ name: string; node: StudioNode }>({ name: "", node: { id: "placeholder_node", type: "text", props: {}, styles: { base: {}, tablet: {}, mobile: {} }, bindings: {}, children: [] } });
    const selected = selectedId ? findNode(history.present.children, selectedId) : null;
    const selectedDefinition = selected ? elementMap[selected.type] : null;
    const dirty = JSON.stringify(history.present) !== JSON.stringify(canvas.content);

    const commit = (content: StudioContent) => setHistory((state) => ({ past: [...state.past, deepClone(state.present)].slice(-80), present: content, future: [] }));
    const updateSelected = (callback: (node: StudioNode) => StudioNode) => selectedId && commit({ ...history.present, children: mapNodes(history.present.children, selectedId, callback) });
    const add = (definition: ElementDefinition, parentId: string | null = null) => {
        const actualParent = parentId ?? (selected && selectedDefinition?.acceptsChildren ? selected.id : null);
        const node = makeNode(definition);
        commit({ ...history.present, children: appendNode(history.present.children, actualParent, node) });
        setSelectedId(node.id);
    };
    const addComponent = (component: ComponentData) => {
        const node = deepClone(component.content);
        const renew = (item: StudioNode): StudioNode => ({ ...item, id: createId(), children: item.children.map(renew) });
        const next = renew(node);
        commit({ ...history.present, children: appendNode(history.present.children, selected && selectedDefinition?.acceptsChildren ? selected.id : null, next) });
        setSelectedId(next.id);
    };
    const undo = () => setHistory((state) => state.past.length === 0 ? state : { past: state.past.slice(0, -1), present: state.past[state.past.length - 1], future: [deepClone(state.present), ...state.future].slice(0, 80) });
    const redo = () => setHistory((state) => state.future.length === 0 ? state : { past: [...state.past, deepClone(state.present)].slice(-80), present: state.future[0], future: state.future.slice(1) });
    const save = () => {
        saveForm.transform(() => ({ name: canvas.name, content: history.present, lock_version: canvas.lockVersion }));
        saveForm.put(`/admin/studio/${canvas.id}`, { preserveScroll: true, preserveState: false });
    };
    const togglePublish = () => {
        setPublishing(true);
        router.post(`/admin/studio/${canvas.id}/${canvas.status === "published" ? "unpublish" : "publish"}`, {}, { preserveScroll: true, preserveState: false, onFinish: () => setPublishing(false) });
    };
    const onDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        const type = event.dataTransfer.getData("application/x-nexora-studio-element");
        const definition = elementMap[type];
        if (definition) add(definition, null);
    };
    const styleKey = viewportKey(viewport);
    const styleValues: Record<string, string> = selected?.styles[styleKey] ?? {};
    const setStyle = (key: string, value: string) => updateSelected((node) => ({ ...node, styles: { ...node.styles, [styleKey]: { ...node.styles[styleKey], [key]: value } } }));
    const bindingOptions = [{ value: "", label: "Static content" }, ...bindings.map((binding) => ({ value: binding.key, label: binding.label, description: `${binding.group} · ${binding.description}` }))];

    return <AdminLayout>
        <Head title={`Studio · ${canvas.name}`} />
        <PageHeader
            eyebrow="Nexora Studio"
            title={canvas.name}
            description={`${canvas.scope === "document" ? "Document design" : canvas.scope === "theme-template" ? "Theme template overlay" : "Standalone canvas"} · typed visual schema · ${canvas.status === "published" ? "published" : "draft"}`}
            actions={<div className="flex flex-wrap items-center gap-2"><ButtonLink href="/admin/studio" variant="secondary" leadingIcon={<Icon name="left" className="h-4 w-4" />}>Studio</ButtonLink><IconButton label="Undo" disabled={history.past.length === 0} onClick={undo}><Icon name="undo" className="h-4 w-4" /></IconButton><IconButton label="Redo" disabled={history.future.length === 0} onClick={redo}><Icon name="redo" className="h-4 w-4" /></IconButton><Button variant="secondary" onClick={() => setPreview((value) => !value)} leadingIcon={<Icon name="eye" className="h-4 w-4" />}>{preview ? "Edit mode" : "Preview"}</Button><Button variant="secondary" loading={publishing} disabled={dirty} onClick={togglePublish}>{canvas.status === "published" ? "Unpublish" : "Publish"}</Button><Button loading={saveForm.processing} disabled={!dirty} onClick={save} leadingIcon={<Icon name="save" className="h-4 w-4" />}>Save</Button></div>}
        />

        <Card className="mb-4 flex flex-col gap-3 p-3 lg:flex-row lg:items-center lg:justify-between">
            <div className="flex flex-wrap items-center gap-2"><Badge tone={canvas.status === "published" ? "success" : "warning"}>{canvas.status === "published" ? "Published" : "Draft"}</Badge>{dirty && <Badge tone="warning">Unsaved changes</Badge>}<Badge>{canvas.revisions.length} recent revisions</Badge>{canvas.document && <Badge>{canvas.document.title}</Badge>}</div>
            <div className="flex items-center gap-1 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-1">
                <IconButton label="Desktop viewport" onClick={() => setViewport("desktop")} className={viewport === "desktop" ? "bg-[var(--nx-surface)] text-[var(--nx-brand)] shadow-xs" : ""}><Icon name="monitor" className="h-4 w-4" /></IconButton>
                <IconButton label="Tablet viewport" onClick={() => setViewport("tablet")} className={viewport === "tablet" ? "bg-[var(--nx-surface)] text-[var(--nx-brand)] shadow-xs" : ""}><Icon name="tablet" className="h-4 w-4" /></IconButton>
                <IconButton label="Mobile viewport" onClick={() => setViewport("mobile")} className={viewport === "mobile" ? "bg-[var(--nx-surface)] text-[var(--nx-brand)] shadow-xs" : ""}><Icon name="mobile" className="h-4 w-4" /></IconButton>
            </div>
        </Card>

        <div className={preview ? "grid gap-4" : "grid min-h-[720px] gap-4 xl:grid-cols-[280px_minmax(0,1fr)_310px]"}>
            {!preview && <Card className="overflow-hidden">
                <div className="flex gap-1 border-b border-[var(--nx-border)] p-2"><Button size="sm" variant={panel === "elements" ? "primary" : "ghost"} onClick={() => setPanel("elements")}>Elements</Button><Button size="sm" variant={panel === "layers" ? "primary" : "ghost"} onClick={() => setPanel("layers")}>Layers</Button><Button size="sm" variant={panel === "components" ? "primary" : "ghost"} onClick={() => setPanel("components")}>Components</Button></div>
                <div className="max-h-[680px] overflow-auto p-3">
                    {panel === "elements" && <div className="grid gap-4">{Array.from(new Set(elements.map((item) => item.category))).map((category) => <div key={category}><p className="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">{category}</p><div className="grid grid-cols-2 gap-2">{elements.filter((item) => item.category === category).map((definition) => <Card key={definition.type} draggable onDragStart={(event) => { event.dataTransfer.setData("application/x-nexora-studio-element", definition.type); event.dataTransfer.effectAllowed = "copy"; }} onClick={() => add(definition)} className="cursor-grab p-3 transition hover:border-[var(--nx-brand-300)] hover:bg-[var(--nx-brand-soft)]"><span className="grid h-8 w-8 place-items-center rounded-lg bg-[var(--nx-surface-subtle)] text-[var(--nx-brand)]"><Icon name={definition.icon} className="h-4 w-4" /></span><p className="mt-2 text-xs font-semibold text-[var(--nx-text)]">{definition.name}</p></Card>)}</div></div>)}</div>}
                    {panel === "layers" && <div className="grid gap-1">{flatten(history.present.children).map(({ node, depth }) => <Button key={node.id} variant={selectedId === node.id ? "secondary" : "ghost"} size="sm" className="justify-start" style={{ paddingInlineStart: 12 + depth * 14 }} leadingIcon={<Icon name={elementMap[node.type]?.icon ?? "blocks"} className="h-4 w-4" />} onClick={() => setSelectedId(node.id)}>{elementMap[node.type]?.name ?? node.type}</Button>)}{history.present.children.length === 0 && <p className="p-3 text-sm text-[var(--nx-text-muted)]">Add an element to create the first layer.</p>}</div>}
                    {panel === "components" && <div className="grid gap-2">{components.map((component) => <Card key={component.id} className="p-3"><div className="flex items-center gap-2"><span className="grid h-8 w-8 place-items-center rounded-lg bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name="component" className="h-4 w-4" /></span><div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold text-[var(--nx-text)]">{component.name}</p><p className="text-xs text-[var(--nx-text-muted)]">Reusable component</p></div></div><Button size="sm" variant="secondary" className="mt-3 w-full" onClick={() => addComponent(component)}>Insert</Button></Card>)}{components.length === 0 && <p className="p-3 text-sm text-[var(--nx-text-muted)]">Save a selected element as a component to reuse it here.</p>}</div>}
                </div>
            </Card>}

            <Card className="overflow-auto bg-[var(--nx-surface-subtle)] p-4 sm:p-6" onDragOver={(event) => { event.preventDefault(); event.dataTransfer.dropEffect = "copy"; }} onDrop={onDrop} onClick={() => setSelectedId(null)}>
                <div className="mx-auto transition-[width] duration-200" style={{ width: Math.min(widthFor(viewport), 1180), maxWidth: "100%" }}>
                    <div className="min-h-[620px] rounded-2xl border border-[var(--nx-border)] bg-white p-5 text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100">
                        {history.present.children.length === 0 ? <div className="grid min-h-[560px] place-items-center rounded-xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700"><div><span className="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"><Icon name="studio" className="h-6 w-6" /></span><h2 className="mt-4 text-base font-semibold">Start visually composing</h2><p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Click an element from the library or drag one onto this canvas. Studio stores a validated visual tree, not executable theme code.</p></div></div> : history.present.children.map((node) => <CanvasNodeView key={node.id} node={node} selectedId={selectedId} viewport={viewport} document={canvas.document} onSelect={setSelectedId} />)}
                    </div>
                </div>
            </Card>

            {!preview && <Card className="overflow-hidden">
                <div className="border-b border-[var(--nx-border)] p-4"><p className="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand)]">Inspector</p><h2 className="mt-1 text-sm font-semibold text-[var(--nx-text)]">{selectedDefinition?.name ?? "Canvas"}</h2><p className="mt-1 text-xs text-[var(--nx-text-muted)]">Editing {viewport} styles. Desktop values are the base and smaller breakpoints override them.</p></div>
                <div className="max-h-[680px] overflow-auto p-4">
                    {!selected || !selectedDefinition ? <div className="rounded-xl border border-dashed border-[var(--nx-border)] p-5 text-center text-sm text-[var(--nx-text-muted)]">Select an element to edit its properties, responsive styles and data binding.</div> : <div className="grid gap-5">
                        <div className="grid gap-3">
                            <div className="flex items-center justify-between"><p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">Content</p><IconButton label="Delete selected element" tone="danger" onClick={() => { commit({ ...history.present, children: removeNode(history.present.children, selected.id) }); setSelectedId(null); }}><Icon name="trash" className="h-4 w-4" /></IconButton></div>
                            {(selected.type === "heading" || selected.type === "text" || selected.type === "button") && <Input label="Text" value={String(selected.props.text ?? "")} onChange={(event) => updateSelected((node) => ({ ...node, props: { ...node.props, text: event.target.value } }))} />}
                            {selected.type === "heading" && <Select label="Heading level" value={String(selected.props.level ?? 2)} onChange={(value) => updateSelected((node) => ({ ...node, props: { ...node.props, level: Number(value) } }))} options={[1,2,3,4,5,6].map((level) => ({ value: String(level), label: `H${level}` }))} />}
                            {selected.type === "button" && <><Input label="Link" value={String(selected.props.href ?? "#")} onChange={(event) => updateSelected((node) => ({ ...node, props: { ...node.props, href: event.target.value } }))} /><Select label="Open link" value={String(selected.props.target ?? "_self")} onChange={(value) => updateSelected((node) => ({ ...node, props: { ...node.props, target: value } }))} options={[{ value: "_self", label: "Same window" }, { value: "_blank", label: "New window" }]} /></>}
                            {selected.type === "stack" && <Select label="Direction" value={String(selected.props.direction ?? "vertical")} onChange={(value) => updateSelected((node) => ({ ...node, props: { ...node.props, direction: value } }))} options={[{ value: "vertical", label: "Vertical" }, { value: "horizontal", label: "Horizontal" }]} />}
                            {selected.type === "grid" && <Input label="Columns" type="number" min={1} max={12} value={String(styleValues.columns ?? selected.props.columns ?? 2)} onChange={(event) => setStyle("columns", event.target.value)} />}
                            {selected.type === "spacer" && <Input label="Height (px)" type="number" min={4} max={240} value={String(selected.props.size ?? 32)} onChange={(event) => updateSelected((node) => ({ ...node, props: { ...node.props, size: Number(event.target.value) } }))} />}
                            {selectedDefinition.bindableProps.includes("text") && <Select label="Dynamic text binding" value={selected.bindings.text ?? ""} onChange={(value) => updateSelected((node) => ({ ...node, bindings: { ...node.bindings, text: value } }))} options={bindingOptions} />}
                        </div>

                        <div className="grid gap-3 border-t border-[var(--nx-border)] pt-5">
                            <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">Responsive style</p>
                            <Input label="Gap" value={styleValues.gap ?? ""} onChange={(event) => setStyle("gap", event.target.value)} placeholder="20px" />
                            <Input label="Padding" value={styleValues.padding ?? ""} onChange={(event) => setStyle("padding", event.target.value)} placeholder="24px or 24px 16px" />
                            <Input label="Margin" value={styleValues.margin ?? ""} onChange={(event) => setStyle("margin", event.target.value)} placeholder="0 auto" />
                            <Input label="Maximum width" value={styleValues.maxWidth ?? ""} onChange={(event) => setStyle("maxWidth", event.target.value)} placeholder="1200px" />
                            {(selected.type === "heading" || selected.type === "text" || selected.type === "button") && <><Input label="Font size" value={styleValues.fontSize ?? ""} onChange={(event) => setStyle("fontSize", event.target.value)} placeholder="18px" /><Select label="Text alignment" value={styleValues.textAlign ?? ""} onChange={(value) => setStyle("textAlign", value)} options={[{ value: "", label: "Inherit" }, { value: "left", label: "Left" }, { value: "center", label: "Center" }, { value: "right", label: "Right" }]} /><ColorInput label="Text color" value={/^#[0-9A-Fa-f]{6}$/.test(styleValues.color ?? "") ? styleValues.color : "#111827"} onChange={(value) => setStyle("color", value)} /></>}
                            <ColorInput label="Background color" value={/^#[0-9A-Fa-f]{6}$/.test(styleValues.backgroundColor ?? "") ? styleValues.backgroundColor : "#FFFFFF"} onChange={(value) => setStyle("backgroundColor", value)} />
                            <Input label="Border radius" value={styleValues.borderRadius ?? ""} onChange={(event) => setStyle("borderRadius", event.target.value)} placeholder="16px" />
                            {themeTokens.length > 0 && <Select label="Use theme color token" value="" onChange={(value) => value && setStyle("color", `var(--nx-theme-${value.replaceAll(".", "-").replaceAll("_", "-")})`)} options={[{ value: "", label: "Choose token" }, ...themeTokens.map((token) => ({ value: token.key, label: token.label, description: token.value }))]} />}
                        </div>

                        <div className="grid gap-3 border-t border-[var(--nx-border)] pt-5">
                            <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">Reusable component</p>
                            <Input label="Component name" value={componentName} onChange={(event) => setComponentName(event.target.value)} placeholder="Hero heading" />
                            <Button variant="secondary" disabled={!componentName.trim()} leadingIcon={<Icon name="component" className="h-4 w-4" />} onClick={() => { componentForm.transform(() => ({ name: componentName, node: selected! })); componentForm.post(`/admin/studio/${canvas.id}/components`, { preserveScroll: true, onSuccess: () => setComponentName("") }); }}>Save selected as component</Button>
                        </div>
                    </div>}
                </div>
            </Card>}
        </div>
    </AdminLayout>;
}
