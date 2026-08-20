import { Head, router, useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import {
    Badge,
    Button,
    ButtonLink,
    Card,
    DateTimePicker,
    Input,
    Modal,
    Select,
    Textarea,
} from "@nexora/admin-ui";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";
import type { SharedPageProps } from "@admin/types/page";

type Adapter = {
    key: string;
    name: string;
    description: string;
    available: boolean;
    endpoint?: string;
    transport?: string;
};

type ListRow = {
    id: number;
    name: string;
    description: string | null;
    status: string;
    subscribers_count: number;
};

type Subscriber = {
    id: number;
    email: string;
    name: string | null;
    status: string;
    locale: string;
    consented_at: string | null;
    unsubscribed_at: string | null;
};

type Campaign = {
    id: number;
    name: string;
    subject: string;
    status: string;
    list: string | null;
    document: string | null;
    scheduled_at: string | null;
    sent_at: string | null;
    delivered_count: number;
    failed_count: number;
};

type DocumentOption = {
    id: number;
    title: string;
    type: string;
};

type LanguageOption = {
    value: string;
    label: string;
    native: string;
    country: string;
    flag_asset: string;
};

type Props = {
    adapters: Adapter[];
    lists: ListRow[];
    subscribers: Subscriber[];
    campaigns: Campaign[];
    documents: DocumentOption[];
    languages: LanguageOption[];
    summary: {
        active_subscribers: number;
        lists: number;
        campaigns: number;
        scheduled: number;
    };
};

function formatDate(value: string | null): string {
    if (! value) {
        return "—";
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
}

function campaignTone(status: string): "success" | "brand" | "warning" | "danger" | "neutral" {
    if (status === "sent") {
        return "success";
    }
    if (status === "sending") {
        return "brand";
    }
    if (status === "scheduled") {
        return "warning";
    }
    if (status === "cancelled") {
        return "danger";
    }

    return "neutral";
}

export default function DistributionIndex({
    adapters,
    lists,
    subscribers,
    campaigns,
    documents,
    languages,
    summary,
}: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("distribution.manage");
    const canSend = permissions.includes("distribution.send");
    const languageMap = new Map(languages.map((language) => [language.value, language]));

    const [listOpen, setListOpen] = useState(false);
    const [subscriberOpen, setSubscriberOpen] = useState(false);
    const [campaignOpen, setCampaignOpen] = useState(false);
    const [queueing, setQueueing] = useState<Campaign | null>(null);

    const listForm = useForm({ name: "", description: "" });
    const subscriberForm = useForm({
        email: "",
        name: "",
        locale: "en",
        list_id: "",
    });
    const campaignForm = useForm({
        name: "",
        subject: "",
        preview_text: "",
        document_id: "",
        list_id: "",
        scheduled_at: "",
    });

    const createCampaign = () => {
        campaignForm.transform((data) => ({
            ...data,
            document_id: data.document_id ? Number(data.document_id) : null,
            list_id: Number(data.list_id) || null,
        }));
        campaignForm.post("/admin/distribution/campaigns", {
            preserveScroll: true,
            onSuccess: () => {
                campaignForm.reset();
                setCampaignOpen(false);
            },
        });
    };

    const summaryCards = [
        { label: "Active subscribers", value: summary.active_subscribers, icon: "users" },
        { label: "Audience lists", value: summary.lists, icon: "list" },
        { label: "Campaigns", value: summary.campaigns, icon: "mail" },
        { label: "Scheduled", value: summary.scheduled, icon: "history" },
    ];

    return (
        <AdminLayout>
            <Head title="Newsletter & Distribution" />
            <PageHeader
                eyebrow="Audience & syndication"
                title="Newsletter & Distribution"
                description="Manage consented audiences, queued email campaigns and public syndication adapters without duplicating your published content."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {canManage && (
                            <Button
                                variant="secondary"
                                leadingIcon={<Icon name="users" className="h-4 w-4" />}
                                onClick={() => setSubscriberOpen(true)}
                            >
                                Add subscriber
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                variant="secondary"
                                leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                                onClick={() => setListOpen(true)}
                            >
                                New list
                            </Button>
                        )}
                        {canManage && (
                            <Button
                                leadingIcon={<Icon name="send" className="h-4 w-4" />}
                                onClick={() => setCampaignOpen(true)}
                            >
                                New campaign
                            </Button>
                        )}
                    </div>
                )}
            />

            <div className="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                {summaryCards.map((card) => (
                    <Card key={card.label} className="p-4">
                        <div className="flex items-center gap-3">
                            <span className="grid h-9 w-9 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]">
                                <Icon name={card.icon} className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-xs text-[var(--nx-text-muted)]">{card.label}</p>
                                <p className="text-xl font-semibold text-[var(--nx-text)]">{card.value}</p>
                            </div>
                        </div>
                    </Card>
                ))}
            </div>

            <div className="grid gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(22rem,.7fr)]">
                <div className="grid gap-5">
                    <Card className="p-5 sm:p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="font-semibold text-[var(--nx-text)]">Campaigns</h2>
                                <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                    Draft, schedule or queue content to a consented newsletter audience.
                                </p>
                            </div>
                            <Badge>{campaigns.length} recent</Badge>
                        </div>

                        {campaigns.length === 0 ? (
                            <p className="py-8 text-center text-sm text-[var(--nx-text-muted)]">
                                No campaigns yet.
                            </p>
                        ) : (
                            <div className="grid gap-3">
                                {campaigns.map((campaign) => (
                                    <div
                                        key={campaign.id}
                                        className="rounded-[var(--nx-radius-card)] border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-4"
                                    >
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="font-semibold text-[var(--nx-text)]">
                                                        {campaign.name}
                                                    </p>
                                                    <Badge tone={campaignTone(campaign.status)}>
                                                        {campaign.status[0].toUpperCase() + campaign.status.slice(1)}
                                                    </Badge>
                                                </div>
                                                <p className="mt-1 text-sm text-[var(--nx-text-secondary)]">
                                                    {campaign.subject}
                                                </p>
                                                <p className="mt-2 text-xs text-[var(--nx-text-muted)]">
                                                    Audience: {campaign.list ?? "No list"} · Content: {campaign.document ?? "Preview text only"}
                                                    {campaign.scheduled_at ? ` · Scheduled ${formatDate(campaign.scheduled_at)}` : ""}
                                                </p>
                                                <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                                    Delivered {campaign.delivered_count} · Failed {campaign.failed_count}
                                                </p>
                                            </div>
                                            {canSend && ["draft", "scheduled"].includes(campaign.status) && (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    leadingIcon={<Icon name="send" className="h-4 w-4" />}
                                                    onClick={() => setQueueing(campaign)}
                                                >
                                                    Queue now
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </Card>

                    <Card className="p-5 sm:p-6">
                        <div className="mb-4">
                            <h2 className="font-semibold text-[var(--nx-text)]">Subscribers</h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Consent source and unsubscribe status are stored independently from user accounts.
                            </p>
                        </div>
                        <div className="grid gap-2">
                            {subscribers.length === 0 ? (
                                <p className="py-6 text-center text-sm text-[var(--nx-text-muted)]">
                                    No subscribers yet.
                                </p>
                            ) : subscribers.map((subscriber) => (
                                <div
                                    key={subscriber.id}
                                    className="flex flex-col gap-3 rounded-xl border border-[var(--nx-border)] p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-semibold text-[var(--nx-text)]">
                                            {subscriber.name || subscriber.email}
                                        </p>
                                        <p className="truncate text-xs text-[var(--nx-text-muted)]">
                                            {subscriber.email} · {languageMap.get(subscriber.locale)?.label ?? subscriber.locale.toUpperCase()} · consent {formatDate(subscriber.consented_at)}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Badge tone={subscriber.status === "active" ? "success" : "neutral"}>
                                            {subscriber.status}
                                        </Badge>
                                        {canManage && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                onClick={() => {
                                                    router.patch(
                                                        `/admin/distribution/subscribers/${subscriber.id}`,
                                                        {
                                                            status: subscriber.status === "active"
                                                                ? "unsubscribed"
                                                                : "active",
                                                        },
                                                        { preserveScroll: true },
                                                    );
                                                }}
                                            >
                                                {subscriber.status === "active" ? "Unsubscribe" : "Reactivate"}
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>

                <div className="grid h-fit gap-5">
                    <Card className="p-5 sm:p-6">
                        <h2 className="font-semibold text-[var(--nx-text)]">Distribution adapters</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Adapters expose delivery capabilities through a stable registry.
                        </p>
                        <div className="mt-4 grid gap-3">
                            {adapters.map((adapter) => (
                                <div
                                    key={adapter.key}
                                    className="rounded-xl border border-[var(--nx-border)] p-4"
                                >
                                    <div className="flex items-start gap-3">
                                        <span className="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]">
                                            <Icon
                                                name={adapter.key === "rss" ? "globe" : "mail"}
                                                className="h-4 w-4"
                                            />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="text-sm font-semibold text-[var(--nx-text)]">
                                                    {adapter.name}
                                                </p>
                                                <Badge tone={adapter.available ? "success" : "warning"}>
                                                    {adapter.available ? "Available" : "Needs configuration"}
                                                </Badge>
                                            </div>
                                            <p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">
                                                {adapter.description}
                                            </p>
                                            {adapter.endpoint && (
                                                <ButtonLink
                                                    href={adapter.endpoint}
                                                    variant="ghost"
                                                    size="sm"
                                                    className="mt-2"
                                                    leadingIcon={<Icon name="external" className="h-4 w-4" />}
                                                >
                                                    Open feed
                                                </ButtonLink>
                                            )}
                                            {adapter.transport && (
                                                <p className="mt-2 text-xs font-medium text-[var(--nx-text-secondary)]">
                                                    Mail transport: {adapter.transport}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    <Card className="p-5 sm:p-6">
                        <h2 className="font-semibold text-[var(--nx-text)]">Audience lists</h2>
                        <div className="mt-4 grid gap-2">
                            {lists.map((list) => (
                                <div
                                    key={list.id}
                                    className="rounded-xl border border-[var(--nx-border)] p-3"
                                >
                                    <div className="flex items-center justify-between gap-2">
                                        <p className="text-sm font-semibold text-[var(--nx-text)]">
                                            {list.name}
                                        </p>
                                        <Badge>{list.subscribers_count} active</Badge>
                                    </div>
                                    {list.description && (
                                        <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                            {list.description}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>
            </div>

            <Modal
                open={listOpen}
                onClose={() => setListOpen(false)}
                title="Create audience list"
                description="Lists organize consented newsletter subscribers."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setListOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={listForm.processing}
                            onClick={() => {
                                listForm.post("/admin/distribution/lists", {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        listForm.reset();
                                        setListOpen(false);
                                    },
                                });
                            }}
                        >
                            Create list
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="List name"
                        value={listForm.data.name}
                        onChange={(event) => listForm.setData("name", event.target.value)}
                    />
                    <Textarea
                        label="Description"
                        rows={4}
                        value={listForm.data.description}
                        onChange={(event) => listForm.setData("description", event.target.value)}
                    />
                </div>
            </Modal>

            <Modal
                open={subscriberOpen}
                onClose={() => setSubscriberOpen(false)}
                title="Add subscriber"
                description="Use only addresses with a valid consent basis. Nexora records this action as an Admin subscription source."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setSubscriberOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={subscriberForm.processing}
                            onClick={() => {
                                subscriberForm.post("/admin/distribution/subscribers", {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        subscriberForm.reset();
                                        setSubscriberOpen(false);
                                    },
                                });
                            }}
                        >
                            Add subscriber
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        type="email"
                        label="Email"
                        value={subscriberForm.data.email}
                        onChange={(event) => subscriberForm.setData("email", event.target.value)}
                    />
                    <Input
                        label="Name"
                        value={subscriberForm.data.name}
                        onChange={(event) => subscriberForm.setData("name", event.target.value)}
                    />
                    <Select
                        label="Language"
                        value={subscriberForm.data.locale}
                        onChange={(value) => subscriberForm.setData("locale", value)}
                        options={languages.map((language) => ({
                            value: language.value,
                            label: language.label,
                            description: `${language.native}${language.country ? ` · ${language.country}` : ""}`,
                            leading: language.flag_asset ? (
                                <img
                                    src={language.flag_asset}
                                    alt=""
                                    className="h-4 w-6 rounded-[3px] object-cover ring-1 ring-black/5"
                                />
                            ) : undefined,
                        }))}
                    />
                    <Select
                        label="Audience list"
                        value={subscriberForm.data.list_id}
                        onChange={(value) => subscriberForm.setData("list_id", value)}
                        options={[
                            { value: "", label: "No list" },
                            ...lists.map((list) => ({
                                value: String(list.id),
                                label: list.name,
                            })),
                        ]}
                    />
                </div>
            </Modal>

            <Modal
                open={campaignOpen}
                onClose={() => setCampaignOpen(false)}
                title="Create newsletter campaign"
                description="Campaigns can reuse a published Nexora document or send preview text only."
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setCampaignOpen(false)}>
                            Cancel
                        </Button>
                        <Button loading={campaignForm.processing} onClick={createCampaign}>
                            Create campaign
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Internal campaign name"
                        value={campaignForm.data.name}
                        onChange={(event) => campaignForm.setData("name", event.target.value)}
                    />
                    <Input
                        label="Email subject"
                        value={campaignForm.data.subject}
                        onChange={(event) => campaignForm.setData("subject", event.target.value)}
                    />
                    <Textarea
                        label="Preview text"
                        rows={3}
                        value={campaignForm.data.preview_text}
                        onChange={(event) => campaignForm.setData("preview_text", event.target.value)}
                    />
                    <Select
                        label="Audience list"
                        value={campaignForm.data.list_id}
                        onChange={(value) => campaignForm.setData("list_id", value)}
                        options={[
                            { value: "", label: "Select a list" },
                            ...lists.map((list) => ({
                                value: String(list.id),
                                label: `${list.name} · ${list.subscribers_count} active`,
                            })),
                        ]}
                    />
                    <Select
                        label="Published content"
                        value={campaignForm.data.document_id}
                        onChange={(value) => campaignForm.setData("document_id", value)}
                        options={[
                            { value: "", label: "No linked document" },
                            ...documents.map((document) => ({
                                value: String(document.id),
                                label: document.title,
                                description: document.type === "blog_post"
                                    ? "Blog post"
                                    : document.type === "article"
                                        ? "Article"
                                        : "Document",
                            })),
                        ]}
                    />
                    <DateTimePicker
                        label="Schedule delivery"
                        value={campaignForm.data.scheduled_at}
                        onChange={(value) => campaignForm.setData("scheduled_at", value)}
                        hint="Leave empty to keep the campaign as a draft."
                    />
                </div>
            </Modal>

            <ConfirmDialog
                open={Boolean(queueing)}
                title="Queue newsletter campaign?"
                description={queueing
                    ? `${queueing.name} will create deliveries for active, subscribed recipients in ${queueing.list ?? "the selected list"}. Queue workers will send the email using the configured mail transport.`
                    : ""}
                confirmLabel="Queue campaign"
                danger={false}
                onCancel={() => setQueueing(null)}
                onConfirm={() => {
                    if (! queueing) {
                        return;
                    }

                    router.post(
                        `/admin/distribution/campaigns/${queueing.id}/queue`,
                        {},
                        {
                            preserveScroll: true,
                            onFinish: () => setQueueing(null),
                        },
                    );
                }}
            />
        </AdminLayout>
    );
}
