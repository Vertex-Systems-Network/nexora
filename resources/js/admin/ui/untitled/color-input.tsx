import type { ChangeEvent } from "react";
import { UntitledInput } from "./input";

type Props = {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
};

export function UntitledColorInput({ label, value, onChange, error }: Props) {
    const update = (event: ChangeEvent<HTMLInputElement>) => onChange(event.target.value.toUpperCase());
    return (
        <div className="grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]">
            <span>{label}</span>
            <div className="flex gap-3">
                <label className="nx-focus grid h-[var(--nx-control-height)] w-14 cursor-pointer place-items-center overflow-hidden rounded-[var(--nx-radius-control)] border border-[var(--nx-border)] bg-[var(--nx-surface)] p-1" aria-label={`${label} picker`}>
                    <input type="color" value={value} onChange={update} className="h-full w-full cursor-pointer border-0 bg-transparent p-0" />
                </label>
                <UntitledInput name="primary" value={value} onChange={(event) => onChange(event.target.value)} error={error} className="font-mono uppercase" />
            </div>
        </div>
    );
}
