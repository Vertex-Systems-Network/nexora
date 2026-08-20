import { useEffect, useRef, useState, type ReactNode } from "react";
import { Check } from "lucide-react";
import { cx } from "@admin/utils/cx";

export type MenuItem = {
    value: string;
    label: string;
    description?: string;
    leading?: ReactNode;
    disabled?: boolean;
};

type Props = {
    trigger: ReactNode;
    items: MenuItem[];
    value?: string;
    onSelect: (value: string) => void;
    align?: "start" | "end";
    className?: string;
};

export function UntitledMenu({ trigger, items, value, onSelect, align = "end", className }: Props) {
    const [open, setOpen] = useState(false);
    const rootRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const close = (event: MouseEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) setOpen(false);
        };
        const escape = (event: KeyboardEvent) => { if (event.key === "Escape") setOpen(false); };
        document.addEventListener("mousedown", close);
        document.addEventListener("keydown", escape);
        return () => {
            document.removeEventListener("mousedown", close);
            document.removeEventListener("keydown", escape);
        };
    }, []);

    return (
        <div ref={rootRef} className={cx("relative", className)}>
            <div onClick={() => setOpen((current) => !current)}>{trigger}</div>
            {open && (
                <div className={cx("absolute top-[calc(100%+8px)] z-50 min-w-60 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] p-1.5 shadow-xl", align === "end" ? "end-0" : "start-0")}>
                    {items.map((item) => {
                        const selected = item.value === value;
                        return (
                            <button
                                key={item.value}
                                type="button"
                                disabled={item.disabled}
                                onClick={() => { if (!item.disabled) { onSelect(item.value); setOpen(false); } }}
                                className={cx("nx-focus nx-pressable flex w-full items-start gap-3 rounded-lg px-2.5 py-2 text-start transition hover:bg-[var(--nx-surface-subtle)] disabled:cursor-not-allowed disabled:opacity-45", selected && "bg-[var(--nx-brand-soft)]")}
                            >
                                {item.leading && <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center text-[var(--nx-text-secondary)]">{item.leading}</span>}
                                <span className="min-w-0 flex-1">
                                    <span className="block text-sm font-semibold text-[var(--nx-text)]">{item.label}</span>
                                    {item.description && <span className="mt-0.5 block text-xs font-normal leading-5 text-[var(--nx-text-muted)]">{item.description}</span>}
                                </span>
                                <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center text-[var(--nx-brand-600)]">{selected && <Check className="h-4 w-4" strokeWidth={2} />}</span>
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
