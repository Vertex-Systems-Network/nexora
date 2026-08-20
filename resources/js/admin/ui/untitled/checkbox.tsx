import type { InputHTMLAttributes, ReactNode } from "react";
import { cx } from "@admin/utils/cx";
type Props = Omit<InputHTMLAttributes<HTMLInputElement>, "type"> & { label?: ReactNode; description?: ReactNode };
export function UntitledCheckbox({ label, description, className, ...props }: Props) {
 return <label className="flex cursor-pointer items-start gap-3 text-sm text-[var(--nx-text)]">
   <input {...props} type="checkbox" className={cx("nx-focus mt-0.5 h-4 w-4 rounded border-[var(--nx-border)] accent-[var(--nx-brand-600)]", className)} />
   {(label || description) && <span className="grid gap-0.5"><span className="font-medium">{label}</span>{description && <span className="text-xs text-[var(--nx-text-muted)]">{description}</span>}</span>}
 </label>;
}
