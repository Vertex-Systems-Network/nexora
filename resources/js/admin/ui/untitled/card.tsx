import type { HTMLAttributes, ReactNode } from "react";
import { cx } from "@admin/utils/cx";

type Props = HTMLAttributes<HTMLDivElement> & { children: ReactNode };

export function UntitledCard({ children, className, ...props }: Props) {
    return (
        <div {...props} className={cx("rounded-2xl border border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-xs", className)}>
            {children}
        </div>
    );
}
