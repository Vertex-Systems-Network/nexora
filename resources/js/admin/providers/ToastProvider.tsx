import { useEffect, useState, type ReactNode } from "react";
import { Icon } from "@admin/components/Icon";
import { IconButton } from "@nexora/admin-ui";

type Flash = { success?: string | null; error?: string | null; warning?: string | null };
type Toast = { kind: "success" | "error" | "warning"; message: string };

function ToastIcon({ kind }: { kind: Toast["kind"] }) {
    if (kind === "success") return <Icon name="success" className="h-5 w-5 text-green-600" />;
    if (kind === "warning") return <Icon name="triangle-alert" className="h-5 w-5 text-amber-600" />;
    return <Icon name="error" className="h-5 w-5 text-red-600" />;
}

export function ToastProvider({ children, flash = {} }: { children: ReactNode; flash?: Flash }) {
    const [toast, setToast] = useState<Toast | null>(null);

    useEffect(() => {
        if (flash.success) setToast({ kind: "success", message: flash.success });
        else if (flash.error) setToast({ kind: "error", message: flash.error });
        else if (flash.warning) setToast({ kind: "warning", message: flash.warning });
    }, [flash.success, flash.error, flash.warning]);

    useEffect(() => {
        if (!toast) return;
        const id = window.setTimeout(() => setToast(null), 5200);
        return () => window.clearTimeout(id);
    }, [toast]);

    return (
        <>
            {children}
            {toast && (
                <div
                    className="fixed end-4 top-4 z-[1100] flex w-[min(24rem,calc(100vw-2rem))] items-start gap-3 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-elevated)] px-4 py-3 text-sm text-[var(--nx-text)] shadow-md"
                    role={toast.kind === "error" ? "alert" : "status"}
                    aria-live={toast.kind === "error" ? "assertive" : "polite"}
                    aria-atomic="true"
                >
                    <span className="mt-0.5 shrink-0" aria-hidden="true">
                        <ToastIcon kind={toast.kind} />
                    </span>
                    <p className="min-w-0 flex-1 break-words font-medium leading-5">
                        {toast.message}
                    </p>
                    <IconButton
                        label="Dismiss notification"
                        className="-me-1 -mt-1 h-8 w-8"
                        onClick={() => setToast(null)}
                    >
                        <Icon name="close" className="h-4 w-4" />
                    </IconButton>
                </div>
            )}
        </>
    );
}
