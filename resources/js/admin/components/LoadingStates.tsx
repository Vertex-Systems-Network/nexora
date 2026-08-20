import type { ReactNode } from "react";
import { Button, Card } from "@nexora/admin-ui";

export function Skeleton({ className = "h-4 w-full rounded-lg" }: { className?: string }) {
    return <div className={`nx-skeleton ${className}`} aria-hidden="true" />;
}

export function StatCardsSkeleton() {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {Array.from({ length: 4 }).map((_, index) => (
                <Card className="p-5" key={index}>
                    <Skeleton className="h-4 w-24 rounded" />
                    <Skeleton className="mt-5 h-8 w-20 rounded-lg" />
                    <Skeleton className="mt-3 h-3 w-32 rounded" />
                </Card>
            ))}
        </div>
    );
}

export function TableSkeleton({ rows = 8 }: { rows?: number }) {
    return (
        <Card className="overflow-hidden">
            <div className="border-b border-[var(--nx-border)] p-5"><Skeleton className="h-10 w-full max-w-sm rounded-xl" /></div>
            <div className="divide-y divide-[var(--nx-border)]">
                {Array.from({ length: rows }).map((_, index) => (
                    <div className="grid grid-cols-[2fr_2fr_1fr] gap-6 p-5" key={index}>
                        <Skeleton className="h-4 w-2/3 rounded" />
                        <Skeleton className="h-4 w-4/5 rounded" />
                        <Skeleton className="h-4 w-1/2 rounded" />
                    </div>
                ))}
            </div>
        </Card>
    );
}

export function EmptyState({ title, description, action }: { title: string; description?: string; action?: ReactNode }) {
    return (
        <Card role="status" aria-live="polite" className="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
            <div className="mb-4 grid h-12 w-12 place-items-center rounded-full border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] text-[var(--nx-text-muted)]">—</div>
            <h3 className="text-base font-semibold text-[var(--nx-text)]">{title}</h3>
            {description && <p className="mt-2 max-w-lg text-sm leading-6 text-[var(--nx-text-muted)]">{description}</p>}
            {action && <div className="mt-5">{action}</div>}
        </Card>
    );
}

export function ErrorState({ title = "Something went wrong", description, onRetry }: { title?: string; description?: string; onRetry?: () => void }) {
    return (
        <Card role="alert" aria-live="assertive" className="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center">
            <div className="mb-4 grid h-12 w-12 place-items-center rounded-full bg-red-50 text-[var(--nx-danger)] dark:bg-red-950/30">!</div>
            <h3 className="text-base font-semibold text-[var(--nx-text)]">{title}</h3>
            {description && <p className="mt-2 max-w-lg text-sm leading-6 text-[var(--nx-text-muted)]">{description}</p>}
            {onRetry && <Button className="mt-5" variant="secondary" onClick={onRetry}>Try again</Button>}
        </Card>
    );
}
