import { Link, type InertiaLinkProps } from "@inertiajs/react";
import type { ReactNode } from "react";
import { cx } from "@admin/utils/cx";
import { Tooltip } from "./tooltip";

type Props = Omit<InertiaLinkProps, "children"> & {
    label: string;
    icon: ReactNode;
    active?: boolean;
    collapsed?: boolean;
    badge?: ReactNode;
    tooltipPlacement?: "left" | "right";
};

export function UntitledNavLink({ label, icon, active = false, collapsed = false, badge, tooltipPlacement = "right", className, ...props }: Props) {
    const link = (
        <Link
            {...props}
            aria-label={collapsed ? label : undefined}
            className={cx(
                "nx-focus nx-pressable relative flex min-h-9 items-center rounded-xl text-sm font-medium transition",
                collapsed ? "justify-center px-2" : "gap-2.5 px-3 py-1.5",
                active ? "bg-[var(--nx-surface-subtle)] text-[var(--nx-text)] shadow-[inset_0_0_0_1px_var(--nx-border)]" : "text-[var(--nx-text-secondary)] hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]",
                className,
            )}
        >
            {active && <span aria-hidden="true" className="nx-brand-fill absolute inset-y-2 start-0 w-[3px] rounded-full" />}
            <span className="grid h-6 w-6 shrink-0 place-items-center">{icon}</span>
            {!collapsed && <span className="truncate">{label}</span>}
            {badge && <span className={cx(collapsed ? "absolute translate-x-3 -translate-y-3" : "ms-auto")}>{badge}</span>}
        </Link>
    );

    return collapsed ? <Tooltip content={label} placement={tooltipPlacement}>{link}</Tooltip> : link;
}
