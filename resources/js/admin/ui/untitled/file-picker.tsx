import { useId, useRef, type ChangeEvent, type DragEvent, type ReactNode } from "react";
import { UploadCloud } from "lucide-react";
import { cx } from "@admin/utils/cx";
import { UntitledButton } from "./button";

type Props = {
    label: string;
    description?: string;
    accept?: string;
    file?: File | null;
    onChange: (file: File | null) => void;
    icon?: ReactNode;
    className?: string;
    error?: string;
};

export function UntitledFilePicker({ label, description, accept, file, onChange, icon, className, error }: Props) {
    const ref = useRef<HTMLInputElement>(null);
    const inputId = useId();
    const descriptionId = useId();
    const errorId = useId();
    const pick = (event: ChangeEvent<HTMLInputElement>) => onChange(event.target.files?.[0] ?? null);
    const drop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        onChange(event.dataTransfer.files?.[0] ?? null);
    };
    const describedBy = [description ? descriptionId : null, error ? errorId : null].filter(Boolean).join(" ") || undefined;

    return (
        <div className={cx("grid gap-2", className)}>
            <input
                id={inputId}
                ref={ref}
                type="file"
                accept={accept}
                className="sr-only"
                onChange={pick}
                aria-label={label}
                aria-describedby={describedBy}
                aria-invalid={Boolean(error)}
            />
            <div
                onDragOver={(event) => event.preventDefault()}
                onDrop={drop}
                className={cx(
                    "rounded-[var(--nx-radius-card)] border border-dashed bg-[var(--nx-surface-subtle)] p-5",
                    error ? "border-red-400 dark:border-red-500/60" : "border-[var(--nx-border-strong)]",
                )}
            >
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex min-w-0 items-start gap-3">
                        <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] text-[var(--nx-brand-600)]">{icon ?? <UploadCloud className="h-5 w-5" strokeWidth={1.8} />}</span>
                        <div className="min-w-0">
                            <p className="text-sm font-semibold text-[var(--nx-text)]">{label}</p>
                            {description && <p id={descriptionId} className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">{description}</p>}
                            {file && <p className="mt-2 truncate text-xs font-semibold text-[var(--nx-brand-600)]">{file.name} · {(file.size / 1024 / 1024).toFixed(2)} MB</p>}
                        </div>
                    </div>
                    <UntitledButton type="button" variant="secondary" onClick={() => ref.current?.click()}>Browse file</UntitledButton>
                </div>
            </div>
            {error && <p id={errorId} role="alert" className="text-sm text-red-600 dark:text-red-300">{error}</p>}
        </div>
    );
}
