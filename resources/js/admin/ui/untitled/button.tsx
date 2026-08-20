import type { ButtonHTMLAttributes, ReactNode } from "react";
import { cx } from "@admin/utils/cx";

export type ButtonVariant = "primary" | "secondary" | "ghost" | "danger";
export type ButtonSize = "sm" | "md" | "lg";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
    children: ReactNode;
    variant?: ButtonVariant;
    size?: ButtonSize;
    loading?: boolean;
    leadingIcon?: ReactNode;
};

export function UntitledButton({
    children,
    variant = "primary",
    size = "md",
    loading = false,
    leadingIcon,
    className,
    disabled,
    ...props
}: Props) {
    const variants: Record<ButtonVariant, string> = {
        primary: "border-transparent bg-[var(--nx-brand-600)] text-white shadow-sm hover:brightness-95",
        secondary: "border-[var(--nx-border)] bg-[var(--nx-surface)] text-[var(--nx-text)] shadow-xs hover:bg-[var(--nx-surface-subtle)]",
        ghost: "border-transparent bg-transparent text-[var(--nx-text-secondary)] hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]",
        danger: "border-transparent bg-[var(--nx-danger)] text-white shadow-sm hover:brightness-95",
    };
    const sizes: Record<ButtonSize, string> = {
        sm: "h-9 px-3 text-sm",
        md: "h-[var(--nx-control-height)] px-4 text-sm",
        lg: "h-12 px-5 text-base",
    };

    return (
        <button
            {...props}
            disabled={disabled || loading}
            aria-busy={loading || undefined}
            className={cx(
                "nx-focus nx-pressable inline-flex shrink-0 items-center justify-center gap-2 rounded-[var(--nx-radius-control)] border font-semibold transition duration-150 disabled:cursor-not-allowed disabled:opacity-55",
                variants[variant],
                sizes[size],
                className,
            )}
        >
            {loading ? (
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true" />
            ) : leadingIcon}
            <span>{children}</span>
        </button>
    );
}
