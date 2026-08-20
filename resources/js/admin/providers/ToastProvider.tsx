import { useEffect, useState, type ReactNode } from "react";

type Flash = { success?: string | null; error?: string | null; warning?: string | null };

export function ToastProvider({ children, flash = {} }: { children: ReactNode; flash?: Flash }) {
    const [toast, setToast] = useState<{ kind: "success" | "error" | "warning"; message: string } | null>(null);

    useEffect(() => {
        if (flash.success) setToast({ kind: "success", message: flash.success });
        else if (flash.error) setToast({ kind: "error", message: flash.error });
        else if (flash.warning) setToast({ kind: "warning", message: flash.warning });
    }, [flash.success, flash.error, flash.warning]);

    useEffect(() => {
        if (!toast) return;
        const id = window.setTimeout(() => setToast(null), 4200);
        return () => window.clearTimeout(id);
    }, [toast]);

    return (
        <>
            {children}
            {toast && (
                <div className="fixed end-4 top-4 z-[1100] max-w-sm rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-elevated)] px-4 py-3 text-sm font-medium text-[var(--nx-text)] shadow-md" role={toast.kind === "error" ? "alert" : "status"} aria-live={toast.kind === "error" ? "assertive" : "polite"} aria-atomic="true">
                    <span className={toast.kind === "success" ? "text-green-600" : toast.kind === "warning" ? "text-amber-600" : "text-red-600"}>●</span>{" "}{toast.message}
                </div>
            )}
        </>
    );
}
