import { useEffect, useId, useRef, useState } from "react";
import { router } from "@inertiajs/react";
import { Icon } from "@admin/components/Icon";
import { ActionRow, Input, OverlayDismiss } from "@nexora/admin-ui";

type Result = { type: string; title: string; subtitle: string; href: string };
const focusable = 'input,button:not([disabled]),a[href],[tabindex]:not([tabindex="-1"])';

export function CommandPalette() {
    const [open, setOpen] = useState(false);
    const [q, setQ] = useState("");
    const [results, setResults] = useState<Result[]>([]);
    const [loading, setLoading] = useState(false);
    const timer = useRef<number | undefined>(undefined);
    const panel = useRef<HTMLDivElement>(null);
    const titleId = useId();
    const statusId = useId();

    useEffect(() => {
        const key = (event: KeyboardEvent) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === "k") {
                event.preventDefault();
                setOpen((value) => !value);
            }
            if (event.key === "Escape") setOpen(false);
        };
        window.addEventListener("keydown", key);
        return () => window.removeEventListener("keydown", key);
    }, []);

    useEffect(() => {
        if (!open) return;
        const previous = document.activeElement as HTMLElement | null;
        const trap = (event: KeyboardEvent) => {
            if (event.key !== "Tab" || !panel.current) return;
            const items = Array.from(panel.current.querySelectorAll<HTMLElement>(focusable)).filter((item) => item.offsetParent !== null);
            if (!items.length) { event.preventDefault(); panel.current.focus(); return; }
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        };
        window.addEventListener("keydown", trap);
        requestAnimationFrame(() => panel.current?.querySelector<HTMLElement>(focusable)?.focus());
        return () => { window.removeEventListener("keydown", trap); previous?.focus(); };
    }, [open]);

    useEffect(() => {
        window.clearTimeout(timer.current);
        if (q.trim().length < 2) { setResults([]); setLoading(false); return; }
        timer.current = window.setTimeout(async () => {
            setLoading(true);
            try {
                const response = await fetch(`/admin/search?q=${encodeURIComponent(q)}`, { headers: { Accept: "application/json" } });
                if (response.ok) setResults((await response.json()).results ?? []);
            } finally { setLoading(false); }
        }, 220);
        return () => window.clearTimeout(timer.current);
    }, [q]);

    if (!open) return null;
    const status = loading ? "Searching" : q.length < 2 ? "Type at least 2 characters" : results.length ? `${results.length} results` : "No matching records";

    return (
        <div className="fixed inset-0 z-[90] flex justify-center px-4 pt-[12vh]" role="presentation">
            <OverlayDismiss aria-label="Close global search" onClick={() => setOpen(false)} />
            <div ref={panel} tabIndex={-1} role="dialog" aria-modal="true" aria-labelledby={titleId} aria-describedby={statusId} className="relative h-fit w-full max-w-2xl overflow-hidden rounded-2xl border border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-2xl">
                <h2 id={titleId} className="sr-only">Global search</h2>
                <div className="border-b border-[var(--nx-border)] p-3"><Input autoFocus value={q} onChange={(event) => setQ(event.target.value)} aria-label="Search Nexora" aria-describedby={statusId} placeholder="Search users, roles, Studio canvases and admin actions…" leadingIcon={<Icon name="search" className="h-4 w-4" />} /></div>
                <div className="max-h-[54vh] overflow-y-auto p-2" aria-label="Search results">
                    {loading ? <div className="p-8 text-center text-sm text-[var(--nx-text-muted)]">Searching…</div> : results.length ? results.map((result, index) => (
                        <ActionRow key={`${result.type}-${index}`} leading={<span className="grid h-9 w-9 place-items-center rounded-lg bg-[var(--nx-surface-subtle)]"><Icon name={result.type === "user" ? "users" : result.type === "studio-canvas" ? "studio" : "shield"} className="h-4 w-4" /></span>} onClick={() => { setOpen(false); router.visit(result.href); }}>
                            <span className="block text-sm font-semibold text-[var(--nx-text)]">{result.title}</span>
                            <span className="block text-xs text-[var(--nx-text-muted)]">{result.subtitle}</span>
                        </ActionRow>
                    )) : <div className="p-8 text-center text-sm text-[var(--nx-text-muted)]">{status}</div>}
                </div>
                <div className="flex justify-between border-t border-[var(--nx-border)] px-4 py-2 text-[11px] text-[var(--nx-text-muted)]"><span>Global search</span><span>Esc to close</span></div>
                <p id={statusId} className="sr-only" role="status" aria-live="polite" aria-atomic="true">{status}</p>
            </div>
        </div>
    );
}
