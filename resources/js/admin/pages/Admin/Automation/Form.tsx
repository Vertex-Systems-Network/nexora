import { useForm } from "@inertiajs/react";
import { Button, ButtonLink, Card, Input, Select, Textarea } from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type Option = {
    value: string;
    label: string;
    description?: string;
};

type Trigger = {
    key: string;
    label: string;
    group: string;
    description: string;
    fields: string[];
};

type ActionDefinition = {
    key: string;
    label: string;
    group: string;
    description: string;
};

type Condition = {
    field: string;
    operator: string;
    value: string;
};

type WorkflowScalar = string | number | boolean | null;

type TriggerConfig = Record<string, WorkflowScalar>;

type WorkflowAction = {
    key: string;
    type: string;
    config: Record<string, WorkflowScalar>;
};

type WorkflowFormData = {
    name: string;
    slug: string;
    description: string;
    status: string;
    trigger_key: string;
    trigger_config: TriggerConfig;
    conditions: Condition[];
    actions: WorkflowAction[];
};

type Workflow = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    status: string;
    triggerKey: string;
    triggerConfig: TriggerConfig;
    conditions: Condition[];
    actions: WorkflowAction[];
};

type Props = {
    workflow?: Workflow | null;
    triggers: Trigger[];
    actions: ActionDefinition[];
    destinations: Option[];
    endpoints: Option[];
    users: Option[];
};

const operatorOptions = [
    { value: "equals", label: "Equals" },
    { value: "not_equals", label: "Does not equal" },
    { value: "contains", label: "Contains" },
    { value: "not_contains", label: "Does not contain" },
    { value: "exists", label: "Exists" },
    { value: "not_exists", label: "Does not exist" },
    { value: "greater_than", label: "Greater than" },
    { value: "less_than", label: "Less than" },
];

function initialAction(users: Option[]): WorkflowAction {
    return {
        key: "step-1",
        type: "admin.notification",
        config: {
            user_id: users[0]?.value ?? "",
            title: "Nexora automation",
            message: "Workflow {{document.title}} completed.",
        },
    };
}

function actionConfigFor(type: string, destinations: Option[], users: Option[]): WorkflowAction["config"] {
    if (type === "webhook.send") {
        return { destination_id: destinations[0]?.value ?? "" };
    }

    if (type === "admin.notification") {
        return {
            user_id: users[0]?.value ?? "",
            title: "Automation notification",
            message: "",
        };
    }

    return { event: "automation.workflow.action" };
}

export default function WorkflowForm({
    workflow,
    triggers,
    actions,
    destinations,
    endpoints,
    users,
}: Props) {
    const form = useForm<WorkflowFormData>({
        name: workflow?.name ?? "",
        slug: workflow?.slug ?? "",
        description: workflow?.description ?? "",
        status: workflow?.status ?? "draft",
        trigger_key: workflow?.triggerKey ?? "document.published",
        trigger_config: workflow?.triggerConfig ?? {},
        conditions: workflow?.conditions ?? [],
        actions: workflow?.actions ?? [initialAction(users)],
    });

    const selectedTrigger = triggers.find((item) => item.key === form.data.trigger_key);

    const setCondition = (index: number, patch: Partial<Condition>) => {
        form.setData(
            "conditions",
            form.data.conditions.map((item, itemIndex) =>
                itemIndex === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const setAction = (index: number, patch: Partial<WorkflowAction>) => {
        form.setData(
            "actions",
            form.data.actions.map((item, itemIndex) =>
                itemIndex === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const setConfig = (index: number, key: string, value: WorkflowScalar) => {
        const action = form.data.actions[index];
        if (! action) {
            return;
        }

        setAction(index, {
            config: {
                ...action.config,
                [key]: value,
            },
        });
    };

    const addCondition = () => {
        form.setData("conditions", [
            ...form.data.conditions,
            {
                field: selectedTrigger?.fields[0] ?? "",
                operator: "equals",
                value: "",
            },
        ]);
    };

    const removeCondition = (index: number) => {
        form.setData(
            "conditions",
            form.data.conditions.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const addAction = () => {
        form.setData("actions", [
            ...form.data.actions,
            {
                key: `step-${form.data.actions.length + 1}`,
                type: "admin.notification",
                config: {
                    user_id: users[0]?.value ?? "",
                    title: "Automation notification",
                    message: "",
                },
            },
        ]);
    };

    const removeAction = (index: number) => {
        form.setData(
            "actions",
            form.data.actions.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const changeActionType = (index: number, type: string) => {
        setAction(index, {
            type,
            config: actionConfigFor(type, destinations, users),
        });
    };

    const submit = () => {
        if (workflow) {
            form.put(`/admin/automation/${workflow.id}`);
            return;
        }

        form.post("/admin/automation");
    };

    return (
        <AdminLayout>
            <PageHeader
                eyebrow="Automation"
                title={workflow ? `Edit ${workflow.name}` : "Create workflow"}
                description="Choose an event trigger, optional payload conditions and ordered controlled actions. Nexora validates the entire definition again on the server."
                actions={(
                    <ButtonLink href="/admin/automation" variant="secondary">
                        Back to workflows
                    </ButtonLink>
                )}
            />

            <form
                className="mt-5 grid gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
            >
                <Card className="grid gap-4 p-5 sm:p-6">
                    <div>
                        <h2 className="font-semibold text-[var(--nx-text)]">Workflow identity</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Use human-readable names. The slug is a stable system identifier.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input
                            label="Workflow name"
                            value={form.data.name}
                            onChange={(event) => form.setData("name", event.target.value)}
                            error={form.errors.name}
                        />
                        <Input
                            label="Stable slug"
                            value={form.data.slug}
                            onChange={(event) => form.setData("slug", event.target.value)}
                            placeholder="publish-notification"
                            error={form.errors.slug}
                        />
                    </div>

                    <Textarea
                        label="Description"
                        value={form.data.description}
                        onChange={(event) => form.setData("description", event.target.value)}
                        rows={3}
                        error={form.errors.description}
                    />
                    <Select
                        label="Workflow status"
                        value={form.data.status}
                        onChange={(value) => form.setData("status", value)}
                        options={[
                            { value: "draft", label: "Draft", description: "Saved but never executes." },
                            { value: "active", label: "Active", description: "Eligible for matching events." },
                            { value: "paused", label: "Paused", description: "Definition retained but new events are ignored." },
                        ]}
                    />
                </Card>

                <Card className="grid gap-4 p-5 sm:p-6">
                    <div>
                        <h2 className="font-semibold text-[var(--nx-text)]">1. Trigger</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            The trigger controls which Nexora event can start this workflow.
                        </p>
                    </div>

                    <Select
                        label="Event trigger"
                        value={form.data.trigger_key}
                        onChange={(value) => {
                            form.setData("trigger_key", value);
                            form.setData("trigger_config", {});
                        }}
                        options={triggers.map((item) => ({
                            value: item.key,
                            label: item.label,
                            description: `${item.group} · ${item.description}`,
                        }))}
                        error={form.errors.trigger_key}
                    />

                    {form.data.trigger_key === "webhook.inbound" && (
                        <Select
                            label="Inbound webhook endpoint"
                            value={String(form.data.trigger_config.endpoint_id ?? "")}
                            onChange={(value) => {
                                form.setData("trigger_config", { endpoint_id: Number(value) });
                            }}
                            options={endpoints}
                            placeholder="Choose endpoint"
                        />
                    )}

                    {selectedTrigger && selectedTrigger.fields.length > 0 && (
                        <div className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3">
                            <p className="text-xs font-semibold text-[var(--nx-text)]">
                                Available payload fields
                            </p>
                            <p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">
                                {selectedTrigger.fields.join(" · ")}
                            </p>
                        </div>
                    )}
                </Card>

                <Card className="grid gap-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">2. Conditions</h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                All conditions must pass. Leave empty to run for every matching trigger.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                            onClick={addCondition}
                        >
                            Add condition
                        </Button>
                    </div>

                    {form.data.conditions.length === 0 ? (
                        <p className="rounded-xl border border-dashed border-[var(--nx-border)] p-4 text-sm text-[var(--nx-text-muted)]">
                            No conditions. Every matching trigger event is eligible.
                        </p>
                    ) : (
                        <div className="grid gap-3">
                            {form.data.conditions.map((condition, index) => (
                                <div
                                    key={`${condition.field}-${index}`}
                                    className="grid gap-3 rounded-xl border border-[var(--nx-border)] p-4 lg:grid-cols-[1fr_220px_1fr_auto]"
                                >
                                    <Input
                                        label="Payload field"
                                        value={condition.field}
                                        onChange={(event) => {
                                            setCondition(index, { field: event.target.value });
                                        }}
                                        placeholder="document.type"
                                    />
                                    <Select
                                        label="Operator"
                                        value={condition.operator}
                                        onChange={(value) => setCondition(index, { operator: value })}
                                        options={operatorOptions}
                                    />
                                    <Input
                                        label="Value"
                                        value={condition.value}
                                        onChange={(event) => {
                                            setCondition(index, { value: event.target.value });
                                        }}
                                        disabled={["exists", "not_exists"].includes(condition.operator)}
                                    />
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => removeCondition(index)}
                                        >
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>

                <Card className="grid gap-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">3. Actions</h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Actions execute in order. Successful earlier steps are not repeated if a later step retries.
                            </p>
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                            onClick={addAction}
                        >
                            Add action
                        </Button>
                    </div>

                    {form.data.actions.map((action, index) => (
                        <div
                            key={action.key}
                            className="grid gap-4 rounded-xl border border-[var(--nx-border)] p-4"
                        >
                            <div className="flex items-end gap-3">
                                <div className="min-w-0 flex-1">
                                    <Select
                                        label={`Action ${index + 1}`}
                                        value={action.type}
                                        onChange={(value) => changeActionType(index, value)}
                                        options={actions.map((item) => ({
                                            value: item.key,
                                            label: item.label,
                                            description: `${item.group} · ${item.description}`,
                                        }))}
                                    />
                                </div>
                                {form.data.actions.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => removeAction(index)}
                                    >
                                        Remove
                                    </Button>
                                )}
                            </div>

                            {action.type === "admin.notification" && (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Select
                                        label="Recipient"
                                        value={String(action.config.user_id ?? "")}
                                        onChange={(value) => setConfig(index, "user_id", value)}
                                        options={users}
                                    />
                                    <Input
                                        label="Notification title"
                                        value={String(action.config.title ?? "")}
                                        onChange={(event) => {
                                            setConfig(index, "title", event.target.value);
                                        }}
                                    />
                                    <div className="sm:col-span-2">
                                        <Textarea
                                            label="Message"
                                            value={String(action.config.message ?? "")}
                                            onChange={(event) => {
                                                setConfig(index, "message", event.target.value);
                                            }}
                                            rows={3}
                                            hint="Payload templates such as {{document.title}} are resolved at execution time."
                                        />
                                    </div>
                                    <div className="sm:col-span-2">
                                        <Input
                                            label="Action URL"
                                            value={String(action.config.action_url ?? "")}
                                            onChange={(event) => {
                                                setConfig(index, "action_url", event.target.value);
                                            }}
                                            placeholder="/admin/documents/{{document.id}}/edit"
                                        />
                                    </div>
                                </div>
                            )}

                            {action.type === "webhook.send" && (
                                <Select
                                    label="Webhook destination"
                                    value={String(action.config.destination_id ?? "")}
                                    onChange={(value) => setConfig(index, "destination_id", value)}
                                    options={destinations}
                                    placeholder="Choose an outbound destination"
                                />
                            )}

                            {action.type === "audit.record" && (
                                <Input
                                    label="Audit event name"
                                    value={String(action.config.event ?? "automation.workflow.action")}
                                    onChange={(event) => {
                                        setConfig(index, "event", event.target.value);
                                    }}
                                    hint="Use a stable dotted event identifier."
                                />
                            )}
                        </div>
                    ))}
                </Card>

                {form.hasErrors && (
                    <Card className="border-red-200 p-4 text-sm text-[var(--nx-danger)]">
                        Some workflow fields need attention. Review the highlighted fields and action configuration.
                    </Card>
                )}

                <div className="flex justify-end gap-2">
                    <ButtonLink href="/admin/automation" variant="secondary">
                        Cancel
                    </ButtonLink>
                    <Button
                        type="submit"
                        loading={form.processing}
                        leadingIcon={<Icon name="save" className="h-4 w-4" />}
                    >
                        {workflow ? "Save workflow" : "Create workflow"}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
