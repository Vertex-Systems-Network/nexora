import { useId, type InputHTMLAttributes, type ReactNode } from "react";
import { cx } from "@admin/utils/cx";

type Props = InputHTMLAttributes<HTMLInputElement> & {
    label?: string;
    hint?: string;
    error?: string;
    leadingIcon?: ReactNode;
};

export function UntitledInput({ label, hint, error, leadingIcon, className, id, ...props }: Props) {
    const generatedId = useId();
    const inputId = id ?? props.name ?? generatedId;
    const messageId = `${inputId}-message`;

    return (
        <div className="grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]">
            {label && <label htmlFor={inputId}>{label}</label>}
            <span className="relative block">
                {leadingIcon && <span className="pointer-events-none absolute inset-y-0 start-3 flex items-center text-[var(--nx-text-muted)]">{leadingIcon}</span>}
                <input
                    {...props}
                    id={inputId}
                    aria-invalid={Boolean(error) || undefined}
                    aria-describedby={(error || hint) ? messageId : undefined}
                    aria-errormessage={error ? messageId : undefined}
                    className={cx(
                        "nx-focus h-[var(--nx-control-height)] w-full rounded-[var(--nx-radius-control)] border bg-[var(--nx-surface)] px-3 text-sm text-[var(--nx-text)] shadow-xs placeholder:text-[var(--nx-text-muted)] disabled:cursor-not-allowed disabled:opacity-60",
                        Boolean(leadingIcon) && "ps-10",
                        error ? "border-[var(--nx-danger)]" : "border-[var(--nx-border)] hover:border-[var(--nx-border-strong)]",
                        className,
                    )}
                />
            </span>
            {error ? <span id={messageId} role="alert" className="text-xs font-medium text-[var(--nx-danger)]">{error}</span> : hint ? <span id={messageId} className="text-xs font-normal text-[var(--nx-text-muted)]">{hint}</span> : null}
        </div>
    );
}
