import { Link, type InertiaLinkProps } from "@inertiajs/react";
import type { ReactNode } from "react";
import { cx } from "@admin/utils/cx";

type Props = Omit<InertiaLinkProps, "children"> & {
    children: ReactNode;
    tone?: "brand" | "neutral";
};

export function UntitledTextLink({ children, tone = "brand", className, ...props }: Props) {
    return (
        <Link
            {...props}
            className={cx(
                "nx-focus rounded-sm font-semibold transition",
                tone === "brand"
                    ? "text-[var(--nx-brand-600)] hover:text-[var(--nx-brand-700)] hover:underline"
                    : "text-[var(--nx-text)] hover:text-[var(--nx-brand-600)]",
                className,
            )}
        >
            {children}
        </Link>
    );
}
