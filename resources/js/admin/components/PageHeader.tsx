import type { ReactNode } from "react";

export function PageHeader({ eyebrow, title, description, actions }: { eyebrow?: string; title: string; description?: string; actions?: ReactNode }) {
    return (
        <div className="flex flex-col gap-4 border-b border-[var(--nx-border)] pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                {eyebrow && <p className="mb-1 text-xs font-semibold uppercase tracking-[0.12em] text-[var(--nx-brand-600)]">{eyebrow}</p>}
                <h1 className="text-2xl font-semibold tracking-[-0.03em] text-[var(--nx-text)] sm:text-[1.75rem]">{title}</h1>
                {description && <p className="mt-2 max-w-3xl text-sm leading-6 text-[var(--nx-text-muted)]">{description}</p>}
            </div>
            {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
