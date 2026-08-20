import { useCallback, useEffect, useState } from "react";
import { Badge, Button, Input, Modal } from "@nexora/admin-ui";

type MediaPickerAsset = {
    id: number;
    uuid: string;
    title: string;
    original_name: string;
    media_type: string;
    mime_type: string;
    width: number | null;
    height: number | null;
    alt_text: string | null;
    url: string | null;
};

type PickerResponse = { assets: MediaPickerAsset[] };

export function MediaPicker({ value, onChange, type = "image", buttonLabel = "Choose from Media Library" }: { value?: string; onChange: (url: string, asset: MediaPickerAsset) => void; type?: "image" | "video" | "audio" | "document"; buttonLabel?: string }) {
    const [open, setOpen] = useState(false);
    const [draftSearch, setDraftSearch] = useState("");
    const [search, setSearch] = useState("");
    const [assets, setAssets] = useState<MediaPickerAsset[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async (signal?: AbortSignal) => {
        setLoading(true);
        setError(null);
        const query = new URLSearchParams({ picker: "1", type, limit: "48" });
        if (search.trim()) query.set("search", search.trim());

        try {
            const response = await fetch(`/admin/media?${query.toString()}`, {
                credentials: "same-origin",
                headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
                signal,
            });
            if (!response.ok) throw new Error(response.status === 403 ? "You do not have permission to browse the Media Library." : `Media Library request failed (${response.status}).`);
            const payload = await response.json() as PickerResponse;
            setAssets(Array.isArray(payload.assets) ? payload.assets.filter((asset) => Boolean(asset.url)) : []);
        } catch (reason) {
            if (reason instanceof DOMException && reason.name === "AbortError") return;
            setAssets([]);
            setError(reason instanceof Error ? reason.message : "Media Library could not be loaded.");
        } finally {
            if (!signal?.aborted) setLoading(false);
        }
    }, [search, type]);

    useEffect(() => {
        if (!open) return;
        const controller = new AbortController();
        void load(controller.signal);
        return () => controller.abort();
    }, [open, load]);

    const choose = (asset: MediaPickerAsset) => {
        if (!asset.url) return;
        onChange(asset.url, asset);
        setOpen(false);
    };

    return <>
        <Button type="button" variant="secondary" onClick={() => setOpen(true)}>{buttonLabel}</Button>
        <Modal open={open} onClose={() => setOpen(false)} title="Choose media" description={`Select an existing ${type} from the tenant Media Library. The stored public media URL will be reused without duplicating the asset.`} footer={<Button type="button" variant="secondary" onClick={() => setOpen(false)}>Cancel</Button>}>
            <div className="grid gap-4">
                <div className="grid gap-2 sm:grid-cols-[1fr_auto]">
                    <Input aria-label="Search Media Library" value={draftSearch} onChange={(event) => setDraftSearch(event.target.value)} onKeyDown={(event) => { if (event.key === "Enter") setSearch(draftSearch); }} placeholder="Search title, filename or alt text…" />
                    <Button type="button" variant="secondary" loading={loading} onClick={() => setSearch(draftSearch)}>Search</Button>
                </div>

                {error && <div className="rounded-xl border border-red-300/60 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100">{error}</div>}
                {!error && !loading && assets.length === 0 && <div className="rounded-xl border border-dashed border-[var(--nx-border)] px-4 py-8 text-center text-sm text-[var(--nx-text-muted)]">No matching media found. Upload the asset in Media Library and retry.</div>}

                <div className="grid max-h-[26rem] grid-cols-2 gap-3 overflow-y-auto pe-1">
                    {assets.map((asset) => <Button key={asset.id} type="button" variant={value === asset.url ? "primary" : "secondary"} className="h-auto min-h-0 flex-col items-stretch overflow-hidden p-0 text-start" onClick={() => choose(asset)}>
                        <span className="block aspect-square w-full overflow-hidden bg-[var(--nx-surface-subtle)]">
                            {asset.media_type === "image" && asset.url ? <img src={asset.url} alt={asset.alt_text ?? asset.title} className="h-full w-full object-cover" loading="lazy" decoding="async" /> : <span className="grid h-full place-items-center px-3 text-center text-xs text-[var(--nx-text-muted)]">{asset.mime_type}</span>}
                        </span>
                        <span className="grid gap-1 px-3 py-2.5">
                            <span className="truncate text-xs font-semibold text-[var(--nx-text)]">{asset.title}</span>
                            <span className="flex items-center gap-1.5"><Badge>{asset.media_type}</Badge>{asset.width && asset.height ? <span className="text-[10px] text-[var(--nx-text-muted)]">{asset.width}×{asset.height}</span> : null}</span>
                        </span>
                    </Button>)}
                </div>
            </div>
        </Modal>
    </>;
}
