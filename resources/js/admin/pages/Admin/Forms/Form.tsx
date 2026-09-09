import { Head, useForm } from "@inertiajs/react";
import {
    Button,
    ButtonLink,
    Card,
    Checkbox,
    Input,
    Select,
    Textarea,
} from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type Option = {
    value: string;
    label: string;
};

type FieldDefinition = {
    key: string;
    label: string;
    type: string;
    required: boolean;
    placeholder: string;
    help: string;
    max_length: number;
    options: Option[];
};

type FormSettings = {
    success_message: string;
    submit_button: string;
    require_auth: boolean;
    indexable: boolean;
};

type FormItem = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    status: string;
    fields: FieldDefinition[];
    settings: Partial<FormSettings>;
    publicUrl: string;
};

type Props = {
    form?: FormItem | null;
    fieldTypes: Option[];
};

type FormData = {
    name: string;
    slug: string;
    description: string;
    status: string;
    fields: FieldDefinition[];
    settings: FormSettings;
};

function emptyField(index: number): FieldDefinition {
    return {
        key: `field_${index + 1}`,
        label: `Field ${index + 1}`,
        type: "text",
        required: false,
        placeholder: "",
        help: "",
        max_length: 255,
        options: [],
    };
}

function defaultSettings(settings?: Partial<FormSettings>): FormSettings {
    return {
        success_message:
            settings?.success_message ?? "Thanks. Your response has been received.",
        submit_button: settings?.submit_button ?? "Submit",
        require_auth: settings?.require_auth ?? false,
        indexable: settings?.indexable ?? false,
    };
}

export default function FormsEditor({ form: existing, fieldTypes }: Props) {
    const form = useForm<FormData>({
        name: existing?.name ?? "",
        slug: existing?.slug ?? "",
        description: existing?.description ?? "",
        status: existing?.status ?? "draft",
        fields: existing?.fields?.length ? existing.fields : [emptyField(0)],
        settings: defaultSettings(existing?.settings),
    });

    const errors = form.errors as Record<string, string>;

    const updateField = (index: number, patch: Partial<FieldDefinition>) => {
        form.setData(
            "fields",
            form.data.fields.map((field, fieldIndex) =>
                fieldIndex === index ? { ...field, ...patch } : field,
            ),
        );
    };

    const addField = () => {
        form.setData("fields", [
            ...form.data.fields,
            emptyField(form.data.fields.length),
        ]);
    };

    const removeField = (index: number) => {
        if (form.data.fields.length <= 1) return;
        form.setData(
            "fields",
            form.data.fields.filter((_, fieldIndex) => fieldIndex !== index),
        );
    };

    const addOption = (fieldIndex: number) => {
        const field = form.data.fields[fieldIndex];
        if (!field) return;
        updateField(fieldIndex, {
            options: [
                ...field.options,
                {
                    value: `option_${field.options.length + 1}`,
                    label: `Option ${field.options.length + 1}`,
                },
            ],
        });
    };

    const updateOption = (
        fieldIndex: number,
        optionIndex: number,
        patch: Partial<Option>,
    ) => {
        const field = form.data.fields[fieldIndex];
        if (!field) return;
        updateField(fieldIndex, {
            options: field.options.map((option, index) =>
                index === optionIndex ? { ...option, ...patch } : option,
            ),
        });
    };

    const removeOption = (fieldIndex: number, optionIndex: number) => {
        const field = form.data.fields[fieldIndex];
        if (!field) return;
        updateField(fieldIndex, {
            options: field.options.filter((_, index) => index !== optionIndex),
        });
    };

    const changeType = (index: number, type: string) => {
        const maxLength = type === "textarea" ? 10000 : 255;
        updateField(index, {
            type,
            max_length: maxLength,
            options:
                type === "select"
                    ? form.data.fields[index]?.options?.length
                        ? form.data.fields[index].options
                        : [{ value: "option_1", label: "Option 1" }]
                    : [],
        });
    };

    const submit = () => {
        if (existing) {
            form.put(`/admin/forms/${existing.slug}`);
            return;
        }
        form.post("/admin/forms");
    };

    return (
        <AdminLayout>
            <Head title={existing ? `Edit ${existing.name}` : "Create form"} />
            <PageHeader
                eyebrow="Forms · Data · Workflows"
                title={existing ? `Edit ${existing.name}` : "Create form"}
                description="Define a controlled schema for public responses. Submitted values are validated against this schema before storage and Automation dispatch."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {existing?.status === "active" && (
                            <ButtonLink
                                href={existing.publicUrl}
                                target="_blank"
                                rel="noreferrer"
                                variant="secondary"
                                leadingIcon={<Icon name="external" className="h-4 w-4" />}
                            >
                                Open public form
                            </ButtonLink>
                        )}
                        <ButtonLink href="/admin/forms" variant="secondary">
                            Back to forms
                        </ButtonLink>
                    </div>
                )}
            />

            <form
                className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
            >
                <div className="grid gap-5">
                    <Card className="grid gap-4 p-5 sm:p-6">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">
                                Form identity
                            </h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                The slug becomes the stable public URL identifier.
                            </p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Form name"
                                value={form.data.name}
                                onChange={(event) => form.setData("name", event.target.value)}
                                error={form.errors.name}
                            />
                            <Input
                                label="Stable slug"
                                value={form.data.slug}
                                onChange={(event) => form.setData("slug", event.target.value)}
                                placeholder="contact-us"
                                hint={existing ? undefined : "Optional on create; Nexora can generate a unique slug."}
                                error={form.errors.slug}
                            />
                        </div>
                        <Textarea
                            label="Description"
                            rows={3}
                            value={form.data.description}
                            onChange={(event) => {
                                form.setData("description", event.target.value);
                            }}
                            error={form.errors.description}
                        />
                    </Card>

                    <Card className="p-5 sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="font-semibold text-[var(--nx-text)]">
                                    Fields
                                </h2>
                                <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                    Up to 50 fields. Keys are stable payload identifiers used by Automation.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={addField}
                                disabled={form.data.fields.length >= 50}
                                leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                            >
                                Add field
                            </Button>
                        </div>

                        {form.errors.fields && (
                            <p className="mt-4 text-sm font-medium text-[var(--nx-danger)]" role="alert">
                                {form.errors.fields}
                            </p>
                        )}

                        <div className="mt-5 grid gap-4">
                            {form.data.fields.map((field, index) => {
                                const schemaError =
                                    errors[`fields.${index + 1}`] ??
                                    errors[`fields.${index}`];

                                return (
                                    <div
                                        key={`${field.key}-${index}`}
                                        className="grid gap-4 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-[var(--nx-text)]">
                                                    Field {index + 1}
                                                </p>
                                                <p className="text-xs text-[var(--nx-text-muted)]">
                                                    Payload path: submission.values.{field.key || "…"}
                                                </p>
                                            </div>
                                            {form.data.fields.length > 1 && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    onClick={() => removeField(index)}
                                                >
                                                    Remove
                                                </Button>
                                            )}
                                        </div>

                                        {schemaError && (
                                            <p className="text-sm font-medium text-[var(--nx-danger)]" role="alert">
                                                {schemaError}
                                            </p>
                                        )}

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Input
                                                label="Field label"
                                                value={field.label}
                                                onChange={(event) => {
                                                    updateField(index, { label: event.target.value });
                                                }}
                                            />
                                            <Input
                                                label="Field key"
                                                value={field.key}
                                                onChange={(event) => {
                                                    updateField(index, {
                                                        key: event.target.value
                                                            .toLowerCase()
                                                            .replace(/[^a-z0-9_]/g, "_"),
                                                    });
                                                }}
                                                hint="Lowercase letters, numbers and underscores only."
                                            />
                                            <Select
                                                label="Field type"
                                                value={field.type}
                                                onChange={(value) => changeType(index, value)}
                                                options={fieldTypes}
                                            />
                                            {! ["number", "date", "checkbox", "select"].includes(field.type) && (
                                                <Input
                                                    type="number"
                                                    min={1}
                                                    max={20000}
                                                    label="Maximum length"
                                                    value={field.max_length}
                                                    onChange={(event) => {
                                                        updateField(index, {
                                                            max_length: Number(event.target.value) || 1,
                                                        });
                                                    }}
                                                />
                                            )}
                                        </div>

                                        <Checkbox
                                            checked={field.required}
                                            onChange={(event) => {
                                                updateField(index, { required: event.target.checked });
                                            }}
                                            label="Required field"
                                        />

                                        {! ["checkbox", "select"].includes(field.type) && (
                                            <Input
                                                label="Placeholder"
                                                value={field.placeholder}
                                                onChange={(event) => {
                                                    updateField(index, { placeholder: event.target.value });
                                                }}
                                            />
                                        )}
                                        <Input
                                            label="Help text"
                                            value={field.help}
                                            onChange={(event) => {
                                                updateField(index, { help: event.target.value });
                                            }}
                                            hint="Optional guidance shown under the public field."
                                        />

                                        {field.type === "select" && (
                                            <div className="grid gap-3 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] p-4">
                                                <div className="flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <p className="text-sm font-semibold text-[var(--nx-text)]">
                                                            Select options
                                                        </p>
                                                        <p className="text-xs text-[var(--nx-text-muted)]">
                                                            Values are stored; labels are presentation text.
                                                        </p>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="secondary"
                                                        onClick={() => addOption(index)}
                                                        disabled={field.options.length >= 50}
                                                    >
                                                        Add option
                                                    </Button>
                                                </div>
                                                {field.options.map((option, optionIndex) => (
                                                    <div
                                                        key={`${option.value}-${optionIndex}`}
                                                        className="grid gap-3 sm:grid-cols-[1fr_1fr_auto]"
                                                    >
                                                        <Input
                                                            label="Value"
                                                            value={option.value}
                                                            onChange={(event) => {
                                                                updateOption(index, optionIndex, {
                                                                    value: event.target.value,
                                                                });
                                                            }}
                                                        />
                                                        <Input
                                                            label="Label"
                                                            value={option.label}
                                                            onChange={(event) => {
                                                                updateOption(index, optionIndex, {
                                                                    label: event.target.value,
                                                                });
                                                            }}
                                                        />
                                                        <div className="flex items-end">
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() => {
                                                                    removeOption(index, optionIndex);
                                                                }}
                                                            >
                                                                Remove
                                                            </Button>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </Card>
                </div>

                <div className="grid h-fit gap-5 xl:sticky xl:top-24">
                    <Card className="grid gap-4 p-5">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">
                                Publishing
                            </h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Only active forms accept public submissions.
                            </p>
                        </div>
                        <Select
                            label="Status"
                            value={form.data.status}
                            onChange={(value) => form.setData("status", value)}
                            options={[
                                { value: "draft", label: "Draft" },
                                { value: "active", label: "Active" },
                                { value: "paused", label: "Paused" },
                                { value: "archived", label: "Archived" },
                            ]}
                            error={form.errors.status}
                        />
                        <Checkbox
                            checked={form.data.settings.require_auth}
                            onChange={(event) => {
                                form.setData("settings", {
                                    ...form.data.settings,
                                    require_auth: event.target.checked,
                                });
                            }}
                            label="Require authenticated user"
                            description="Guests receive validation feedback instead of a stored submission."
                        />
                        <Checkbox
                            checked={form.data.settings.indexable}
                            onChange={(event) => {
                                form.setData("settings", {
                                    ...form.data.settings,
                                    indexable: event.target.checked,
                                });
                            }}
                            label="Allow search indexing"
                            description="Forms are noindex by default."
                        />
                    </Card>

                    <Card className="grid gap-4 p-5">
                        <h2 className="font-semibold text-[var(--nx-text)]">
                            Response experience
                        </h2>
                        <Input
                            label="Submit button"
                            value={form.data.settings.submit_button}
                            onChange={(event) => {
                                form.setData("settings", {
                                    ...form.data.settings,
                                    submit_button: event.target.value,
                                });
                            }}
                        />
                        <Textarea
                            label="Success message"
                            rows={3}
                            value={form.data.settings.success_message}
                            onChange={(event) => {
                                form.setData("settings", {
                                    ...form.data.settings,
                                    success_message: event.target.value,
                                });
                            }}
                        />
                    </Card>

                    <Card className="grid gap-3 p-5">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">
                                Automation payload
                            </h2>
                            <p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">
                                Every stored response emits form.submitted. Workflows can filter by form.slug or submission.values.*.
                            </p>
                        </div>
                        <Button
                            type="submit"
                            loading={form.processing}
                            leadingIcon={<Icon name="save" className="h-4 w-4" />}
                        >
                            {existing ? "Save form" : "Create form"}
                        </Button>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
