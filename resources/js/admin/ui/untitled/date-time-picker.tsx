import { CalendarDays, ChevronLeft, ChevronRight, Clock } from "lucide-react";
import {
    Button as AriaButton,
    Calendar,
    CalendarCell,
    CalendarGrid,
    CalendarGridBody,
    CalendarGridHeader,
    CalendarHeaderCell,
    DateInput,
    DatePicker as AriaDatePicker,
    DateSegment,
    Dialog,
    Group,
    Heading,
    Label,
    Popover,
    Text,
    TimeField as AriaTimeField,
} from "react-aria-components";
import { parseDate, parseDateTime, parseTime } from "@internationalized/date";
import { cx } from "@admin/utils/cx";

type CommonProps = {
    label?: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    hint?: string;
    disabled?: boolean;
    required?: boolean;
    className?: string;
};

function safeDate(value: string) {
    if (!value) return null;
    try { return parseDate(value.slice(0, 10)); } catch { return null; }
}

function safeDateTime(value: string) {
    if (!value) return null;
    try { return parseDateTime(value.replace(" ", "T").slice(0, 19)); } catch { return null; }
}

function safeTime(value: string) {
    if (!value) return null;
    try { return parseTime(value.slice(0, 8)); } catch { return null; }
}

function FieldHelp({ error, hint }: Pick<CommonProps, "error" | "hint">) {
    if (error) return <Text slot="errorMessage" className="text-xs font-medium text-[var(--nx-danger)]">{error}</Text>;
    if (hint) return <Text slot="description" className="text-xs font-normal text-[var(--nx-text-muted)]">{hint}</Text>;
    return null;
}

function CalendarPopover() {
    return (
        <Popover placement="bottom start" offset={6} className="z-50 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] p-3 shadow-xl outline-none">
            <Dialog className="outline-none">
                <Calendar className="w-[19rem] max-w-[calc(100vw-3rem)]">
                    <header className="mb-3 flex items-center justify-between gap-2">
                        <AriaButton slot="previous" className="nx-focus grid h-8 w-8 place-items-center rounded-lg text-[var(--nx-text-muted)] transition-colors hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]"><ChevronLeft className="h-4 w-4" /></AriaButton>
                        <Heading className="text-sm font-semibold text-[var(--nx-text)]" />
                        <AriaButton slot="next" className="nx-focus grid h-8 w-8 place-items-center rounded-lg text-[var(--nx-text-muted)] transition-colors hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]"><ChevronRight className="h-4 w-4" /></AriaButton>
                    </header>
                    <CalendarGrid className="w-full border-separate border-spacing-1">
                        <CalendarGridHeader>{(day) => <CalendarHeaderCell className="pb-1 text-center text-[11px] font-semibold uppercase text-[var(--nx-text-muted)]">{day}</CalendarHeaderCell>}</CalendarGridHeader>
                        <CalendarGridBody>{(date) => <CalendarCell date={date} className="grid h-9 w-9 cursor-default place-items-center rounded-lg text-sm text-[var(--nx-text-secondary)] outline-none transition-colors data-[disabled]:opacity-30 data-[focus-visible]:ring-2 data-[focus-visible]:ring-[rgb(var(--nx-brand-rgb)/0.2)] data-[hovered]:bg-[var(--nx-surface-subtle)] data-[outside-month]:text-[var(--nx-text-muted)] data-[selected]:bg-[var(--nx-brand-600)] data-[selected]:font-semibold data-[selected]:text-white" />}</CalendarGridBody>
                    </CalendarGrid>
                </Calendar>
            </Dialog>
        </Popover>
    );
}

export function DatePicker({ label, value, onChange, error, hint, disabled = false, required = false, className }: CommonProps) {
    return (
        <AriaDatePicker value={safeDate(value)} onChange={(next) => onChange(next?.toString() ?? "")} isDisabled={disabled} isRequired={required} isInvalid={Boolean(error)} className={cx("grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]", className)}>
            {label && <Label>{label}</Label>}
            <Group className="nx-focus flex h-[var(--nx-control-height)] items-center rounded-[var(--nx-radius-control)] border border-[var(--nx-border)] bg-[var(--nx-surface)] px-3 shadow-xs transition-colors data-[focus-within]:border-[var(--nx-brand-400)] data-[disabled]:opacity-50">
                <DateInput className="min-w-0 flex-1 whitespace-nowrap text-sm text-[var(--nx-text)]">{(segment) => <DateSegment segment={segment} className="rounded px-0.5 outline-none data-[focused]:bg-[var(--nx-brand-soft)] data-[placeholder]:text-[var(--nx-text-muted)]" />}</DateInput>
                <AriaButton className="nx-focus grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[var(--nx-text-muted)] transition-colors hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]"><CalendarDays className="h-4 w-4" /></AriaButton>
            </Group>
            <FieldHelp error={error} hint={hint} />
            <CalendarPopover />
        </AriaDatePicker>
    );
}

export function DateTimePicker({ label, value, onChange, error, hint, disabled = false, required = false, className }: CommonProps) {
    return (
        <AriaDatePicker granularity="minute" value={safeDateTime(value)} onChange={(next) => onChange(next ? next.toString().slice(0, 16) : "")} isDisabled={disabled} isRequired={required} isInvalid={Boolean(error)} className={cx("grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]", className)}>
            {label && <Label>{label}</Label>}
            <Group className="nx-focus flex h-[var(--nx-control-height)] items-center rounded-[var(--nx-radius-control)] border border-[var(--nx-border)] bg-[var(--nx-surface)] px-3 shadow-xs transition-colors data-[focus-within]:border-[var(--nx-brand-400)] data-[disabled]:opacity-50">
                <DateInput className="min-w-0 flex-1 whitespace-nowrap text-sm text-[var(--nx-text)]">{(segment) => <DateSegment segment={segment} className="rounded px-0.5 outline-none data-[focused]:bg-[var(--nx-brand-soft)] data-[placeholder]:text-[var(--nx-text-muted)]" />}</DateInput>
                <AriaButton className="nx-focus grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[var(--nx-text-muted)] transition-colors hover:bg-[var(--nx-surface-subtle)] hover:text-[var(--nx-text)]"><CalendarDays className="h-4 w-4" /></AriaButton>
            </Group>
            <FieldHelp error={error} hint={hint} />
            <CalendarPopover />
        </AriaDatePicker>
    );
}

export function TimePicker({ label, value, onChange, error, hint, disabled = false, required = false, className }: CommonProps) {
    return (
        <AriaTimeField granularity="minute" value={safeTime(value)} onChange={(next) => onChange(next ? next.toString().slice(0, 5) : "")} isDisabled={disabled} isRequired={required} isInvalid={Boolean(error)} className={cx("grid gap-1.5 text-sm font-medium text-[var(--nx-text-secondary)]", className)}>
            {label && <Label>{label}</Label>}
            <Group className="nx-focus flex h-[var(--nx-control-height)] items-center rounded-[var(--nx-radius-control)] border border-[var(--nx-border)] bg-[var(--nx-surface)] px-3 shadow-xs transition-colors data-[focus-within]:border-[var(--nx-brand-400)] data-[disabled]:opacity-50">
                <DateInput className="min-w-0 flex-1 whitespace-nowrap text-sm text-[var(--nx-text)]">{(segment) => <DateSegment segment={segment} className="rounded px-0.5 outline-none data-[focused]:bg-[var(--nx-brand-soft)] data-[placeholder]:text-[var(--nx-text-muted)]" />}</DateInput>
                <Clock className="h-4 w-4 shrink-0 text-[var(--nx-text-muted)]" />
            </Group>
            <FieldHelp error={error} hint={hint} />
        </AriaTimeField>
    );
}
