import type { ButtonHTMLAttributes, ReactNode } from "react";
import { cx } from "@admin/utils/cx";
import { Tooltip } from "./tooltip";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
    label: string;
    children: ReactNode;
    tone?: "neutral" | "danger";
    tooltipPlacement?: "top" | "right" | "bottom" | "left";
};

export function UntitledIconButton({ label, children, tone = "neutral", tooltipPlacement = "top", className, ...props }: Props) {
    return (
        <Tooltip content={label} placement={tooltipPlacement}>
            <button
                {...props}
                type={props.type ?? "button"}
                aria-label={label}
                className={cx(
                    "nx-focus nx-pressable inline-grid h-9 w-9 shrink-0 place-items-center rounded-[var(--nx-radius-control)] border border-transparent transition disabled:cursor-not-allowed disabled:opacity-45",
                    tone === "danger"
                        ? "text-[var(--nx-text-muted)] hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30"
                        : "text-[var(--nx-text-muted)] hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]",
                    className,
                )}
            >
                {children}
            </button>
        </Tooltip>
    );
}
