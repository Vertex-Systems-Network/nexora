import type { ButtonHTMLAttributes } from "react";
import { cx } from "@admin/utils/cx";

export function UntitledOverlayDismiss({ className, ...props }: ButtonHTMLAttributes<HTMLButtonElement>) {
    return <button type="button" {...props} className={cx("nx-pressable absolute inset-0 bg-slate-950/45 backdrop-blur-[2px]", className)} />;
}
