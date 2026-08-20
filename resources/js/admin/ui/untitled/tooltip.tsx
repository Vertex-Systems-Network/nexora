import { useCallback, useEffect, useId, useRef, useState, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { cx } from "@admin/utils/cx";

type Placement = "top" | "right" | "bottom" | "left";
type Point = { top: number; left: number; transform: string };

export function Tooltip({ content, children, placement = "top", disabled = false, className }: { content: ReactNode; children: ReactNode; placement?: Placement; disabled?: boolean; className?: string }) {
    const id = useId();
    const triggerRef = useRef<HTMLSpanElement>(null);
    const [open, setOpen] = useState(false);
    const [point, setPoint] = useState<Point>({ top: 0, left: 0, transform: "translate(-50%, -100%)" });

    const position = useCallback(() => {
        const node = triggerRef.current;
        if (!node) return;
        const rect = node.getBoundingClientRect();
        const gap = 10;
        if (placement === "right") setPoint({ top: rect.top + rect.height / 2, left: rect.right + gap, transform: "translate(0, -50%)" });
        else if (placement === "left") setPoint({ top: rect.top + rect.height / 2, left: rect.left - gap, transform: "translate(-100%, -50%)" });
        else if (placement === "bottom") setPoint({ top: rect.bottom + gap, left: rect.left + rect.width / 2, transform: "translate(-50%, 0)" });
        else setPoint({ top: rect.top - gap, left: rect.left + rect.width / 2, transform: "translate(-50%, -100%)" });
    }, [placement]);

    useEffect(() => {
        if (!open) return;
        position();
        const update = () => position();
        window.addEventListener("resize", update);
        window.addEventListener("scroll", update, true);
        return () => {
            window.removeEventListener("resize", update);
            window.removeEventListener("scroll", update, true);
        };
    }, [open, position]);

    return (
        <>
            <span
                ref={triggerRef}
                className="inline-flex"
                aria-describedby={!disabled && open ? id : undefined}
                onMouseEnter={() => { if (!disabled) { position(); setOpen(true); } }}
                onMouseLeave={() => setOpen(false)}
                onFocusCapture={() => { if (!disabled) { position(); setOpen(true); } }}
                onBlurCapture={() => setOpen(false)}
            >
                {children}
            </span>
            {open && !disabled && typeof document !== "undefined" && createPortal(
                <span
                    id={id}
                    role="tooltip"
                    style={{ position: "fixed", top: point.top, left: point.left, transform: point.transform }}
                    className={cx("pointer-events-none z-[1200] max-w-64 rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-xs font-medium leading-5 text-white shadow-md", className)}
                >
                    {content}
                </span>,
                document.body,
            )}
        </>
    );
}
