import type { ReactNode } from "react";
import { cx } from "@admin/utils/cx";

type Tone = "neutral" | "success" | "warning" | "danger" | "brand";

export function UntitledBadge({ children, tone = "neutral" }: { children: ReactNode; tone?: Tone }) {
    const tones: Record<Tone, string> = {
        neutral: "bg-[var(--nx-surface-subtle)] text-[var(--nx-text-secondary)] ring-[var(--nx-border)]",
        success: "bg-green-50 text-green-700 ring-green-200 dark:bg-green-950/35 dark:text-green-300 dark:ring-green-900",
        warning: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/35 dark:text-amber-300 dark:ring-amber-900",
        danger: "bg-red-50 text-red-700 ring-red-200 dark:bg-red-950/35 dark:text-red-300 dark:ring-red-900",
        brand: "bg-[var(--nx-brand-50)] text-[var(--nx-brand-700)] ring-[var(--nx-brand-100)] dark:bg-violet-950/35 dark:text-violet-300 dark:ring-violet-900",
    };

    return <span className={cx("inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset", tones[tone])}>{children}</span>;
}
