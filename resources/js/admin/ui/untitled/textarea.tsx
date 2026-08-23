import { useId, type TextareaHTMLAttributes } from "react";
import { cx } from "@admin/utils/cx";

type Props = TextareaHTMLAttributes<HTMLTextAreaElement> & { label?: string; hint?: string; error?: string };

export function UntitledTextarea({ label, hint, error, className, id, ...props }: Props) {
    const generatedId = useId();
    const controlId = id ?? props.name ?? generatedId;
    const messageId = `${controlId}-message`;
    return (
        <div className="grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]">
            {label && <label htmlFor={controlId}>{label}</label>}
            <textarea
                {...props}
                id={controlId}
                aria-invalid={Boolean(error) || undefined}
                aria-describedby={(error || hint) ? messageId : undefined}
                aria-errormessage={error ? messageId : undefined}
                className={cx(
                    "nx-focus min-h-28 w-full resize-y rounded-[var(--nx-radius-control)] border bg-[var(--nx-surface)] px-3 py-2.5 text-sm leading-6 text-[var(--nx-text)] shadow-xs placeholder:text-[var(--nx-text-muted)]",
                    error ? "border-[var(--nx-danger)]" : "border-[var(--nx-border)] hover:border-[var(--nx-border-strong)]",
                    className,
                )}
            />
            {error ? <span id={messageId} role="alert" className="text-xs font-medium text-[var(--nx-danger)]">{error}</span> : hint ? <span id={messageId} className="text-xs font-normal text-[var(--nx-text-muted)]">{hint}</span> : null}
        </div>
    );
}
