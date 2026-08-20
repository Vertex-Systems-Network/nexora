import { Check, ChevronDown } from "lucide-react";
import {
    Button as AriaButton,
    Label,
    ListBox,
    ListBoxItem,
    Popover,
    Select as AriaSelect,
    SelectValue,
    Text,
} from "react-aria-components";
import type { ReactNode } from "react";
import { cx } from "@admin/utils/cx";

export type SelectOption = {
    value: string;
    label: string;
    description?: string;
    leading?: ReactNode;
    disabled?: boolean;
};

type Props = {
    label?: string;
    error?: string;
    hint?: string;
    value: string;
    onChange: (value: string) => void;
    options: SelectOption[];
    placeholder?: string;
    ariaLabel?: string;
    className?: string;
    disabled?: boolean;
};

export function UntitledSelect({ label, error, hint, value, onChange, options, placeholder = "Select an option", ariaLabel, className, disabled = false }: Props) {
    const selected = options.find((option) => option.value === value);

    return (
        <AriaSelect
            value={value === "" ? null : value}
            onChange={(key) => onChange(key === null ? "" : String(key))}
            isDisabled={disabled}
            aria-label={ariaLabel ?? (label ? undefined : placeholder)}
            className={cx("grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]", className)}
        >
            {label && <Label>{label}</Label>}
            <AriaButton className="nx-focus group flex h-[var(--nx-control-height)] w-full items-center justify-between gap-3 rounded-[var(--nx-radius-control)] border border-[var(--nx-border)] bg-[var(--nx-surface)] px-3 text-start text-sm text-[var(--nx-text)] shadow-xs transition-colors hover:border-[var(--nx-border-strong)] data-[focus-visible]:ring-2 data-[focus-visible]:ring-[rgb(var(--nx-brand-rgb)/0.18)] data-[pressed]:border-[var(--nx-brand-400)] disabled:cursor-not-allowed disabled:opacity-50">
                <span className="flex min-w-0 items-center gap-2.5">
                    {selected?.leading && <span className="grid shrink-0 place-items-center">{selected.leading}</span>}
                    <SelectValue className="truncate text-[var(--nx-text)] data-[placeholder]:text-[var(--nx-text-muted)]">
                        {({ defaultChildren }) => selected?.label ?? defaultChildren ?? placeholder}
                    </SelectValue>
                </span>
                <ChevronDown className="h-4 w-4 shrink-0 text-[var(--nx-text-muted)] transition-transform group-data-[open]:rotate-180" strokeWidth={1.8} />
            </AriaButton>
            <Popover
                placement="bottom start"
                offset={6}
                className="z-50 w-[var(--trigger-width)] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-xl outline-none data-[entering]:animate-in data-[exiting]:animate-out"
            >
                <ListBox className="nx-scrollbar max-h-72 overflow-auto p-1.5 outline-none" items={options}>
                    {(option) => (
                        <ListBoxItem
                            id={option.value}
                            textValue={option.label}
                            isDisabled={option.disabled}
                            className="group flex cursor-default items-start gap-3 rounded-lg px-2.5 py-2 text-start outline-none transition-colors data-[focused]:bg-[var(--nx-surface-muted)] data-[selected]:bg-[var(--nx-brand-soft)] data-[disabled]:cursor-not-allowed data-[disabled]:opacity-45"
                        >
                            {option.leading && <span className="mt-0.5 grid shrink-0 place-items-center">{option.leading}</span>}
                            <span className="min-w-0 flex-1">
                                <Text slot="label" className="block truncate text-sm font-semibold text-[var(--nx-text)]">{option.label}</Text>
                                {option.description && <Text slot="description" className="mt-0.5 block text-xs font-normal leading-5 text-[var(--nx-text-muted)]">{option.description}</Text>}
                            </span>
                            <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center text-[var(--nx-brand)] opacity-0 group-data-[selected]:opacity-100"><Check className="h-4 w-4" strokeWidth={2} /></span>
                        </ListBoxItem>
                    )}
                </ListBox>
            </Popover>
            {error ? <Text slot="errorMessage" className="text-xs font-medium text-[var(--nx-danger)]">{error}</Text> : hint ? <Text slot="description" className="text-xs font-normal text-[var(--nx-text-muted)]">{hint}</Text> : null}
        </AriaSelect>
    );
}
