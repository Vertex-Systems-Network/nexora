import type { ButtonHTMLAttributes, ReactNode } from "react";
import { cx } from "@admin/utils/cx";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & { leading?: ReactNode; children: ReactNode };

export function UntitledActionRow({ leading, children, className, ...props }: Props) {
    return (
        <button type="button" {...props} className={cx("nx-focus nx-pressable flex w-full items-center gap-3 rounded-xl px-3 py-3 text-start transition hover:bg-[var(--nx-surface-subtle)] disabled:cursor-not-allowed disabled:opacity-50", className)}>
            {leading}
            <span className="min-w-0 flex-1">{children}</span>
        </button>
    );
}
