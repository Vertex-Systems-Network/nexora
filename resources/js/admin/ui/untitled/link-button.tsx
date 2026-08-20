import { Link, type InertiaLinkProps } from "@inertiajs/react";
import type { ReactNode } from "react";
import { cx } from "@admin/utils/cx";
import type { ButtonSize, ButtonVariant } from "./button";
import { Tooltip } from "./tooltip";

type BaseProps = Omit<InertiaLinkProps, "children" | "size"> & {
    children: ReactNode;
    variant?: ButtonVariant;
    size?: ButtonSize;
    leadingIcon?: ReactNode;
};

const variants: Record<ButtonVariant, string> = {
    primary: "border-transparent bg-[var(--nx-brand-600)] text-white shadow-sm hover:brightness-95",
    secondary: "border-[var(--nx-border)] bg-[var(--nx-surface)] text-[var(--nx-text)] shadow-xs hover:bg-[var(--nx-surface-subtle)]",
    ghost: "border-transparent bg-transparent text-[var(--nx-text-secondary)] hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]",
    danger: "border-transparent bg-[var(--nx-danger)] text-white shadow-sm hover:brightness-95",
};
const sizes: Record<ButtonSize, string> = { sm:"h-9 px-3 text-sm", md:"h-[var(--nx-control-height)] px-4 text-sm", lg:"h-12 px-5 text-base" };

export function UntitledButtonLink({ children, variant="primary", size="md", leadingIcon, className, ...props }: BaseProps) {
    return <Link {...props} className={cx("nx-focus nx-pressable inline-flex shrink-0 items-center justify-center gap-2 rounded-[var(--nx-radius-control)] border font-semibold transition duration-150", variants[variant], sizes[size], className)}>{leadingIcon}<span>{children}</span></Link>;
}

type IconProps = Omit<InertiaLinkProps, "children"> & { label:string; children:ReactNode; tone?:"neutral"|"danger"; tooltipPlacement?:"top"|"right"|"bottom"|"left" };
export function UntitledIconLink({ label, children, tone="neutral", tooltipPlacement="top", className, ...props }: IconProps) {
    return <Tooltip content={label} placement={tooltipPlacement}><Link {...props} aria-label={label} className={cx("nx-focus nx-pressable inline-grid h-9 w-9 shrink-0 place-items-center rounded-[var(--nx-radius-control)] border border-transparent transition", tone==="danger"?"text-[var(--nx-text-muted)] hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30":"text-[var(--nx-text-muted)] hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]", className)}>{children}</Link></Tooltip>;
}
