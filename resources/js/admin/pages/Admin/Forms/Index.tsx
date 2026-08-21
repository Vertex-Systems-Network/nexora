import { Head, router, usePage } from "@inertiajs/react";
import { Badge, Button, ButtonLink, Card } from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";
import type { SharedPageProps } from "@admin/types/page";

type FormItem = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    status: string;
    submissionCount: number;
    publicUrl: string;
    updatedAt?: string | null;
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    forms: Paginator<FormItem>;
    summary: {
        total: number;
        active: number;
        submissions: number;
    };
};

function statusTone(status: string): "success" | "warning" | "neutral" {
    if (status === "active") return "success";
    if (status === "paused") return "warning";
    return "neutral";
}

export default function FormsIndex({ forms, summary }: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canManage = permissions.includes("forms.manage");
    const canViewSubmissions = permissions.includes("forms.submissions.view");

    const setStatus = (form: FormItem, status: string) => {
        router.patch(
            `/admin/forms/${form.slug}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <AdminLayout>
            <Head title="Forms" />
            <PageHeader
                eyebrow="Forms · Data · Workflows"
                title="Forms"
                description="Build tenant-native forms, collect validated responses and feed submissions into Nexora Automation without duplicating the workflow engine."
                actions={canManage ? (
                    <ButtonLink
                        href="/admin/forms/create"
                        leadingIcon={<Icon name="plus" className="h-4 w-4" />}
                    >
                        Create form
                    </ButtonLink>
                ) : undefined}
            />

            <div className="grid gap-4 sm:grid-cols-3">
                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">
                        Forms
                    </p>
                    <p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">
                        {summary.total}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">
                        Active
                    </p>
                    <p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">
                        {summary.active}
                    </p>
                </Card>
                <Card className="p-5">
                    <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">
                        Submissions
                    </p>
                    <p className="mt-2 text-2xl font-semibold text-[var(--nx-text)]">
                        {summary.submissions}
                    </p>
                </Card>
            </div>

            {forms.data.length === 0 ? (
                <Card className="p-8 text-center">
                    <Icon name="file-text" className="mx-auto h-8 w-8 text-[var(--nx-text-muted)]" />
                    <h2 className="mt-3 font-semibold text-[var(--nx-text)]">No forms yet</h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        Create a form to start collecting structured responses.
                    </p>
                </Card>
            ) : (
                <div className="grid gap-4 xl:grid-cols-2">
                    {forms.data.map((form) => (
                        <Card key={form.id} className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="truncate font-semibold text-[var(--nx-text)]">
                                            {form.name}
                                        </h2>
                                        <Badge tone={statusTone(form.status)}>{form.status}</Badge>
                                    </div>
                                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                        /forms/{form.slug}
                                    </p>
                                </div>
                                <Badge>{form.submissionCount} submissions</Badge>
                            </div>

                            {form.description && (
                                <p className="mt-4 text-sm leading-6 text-[var(--nx-text-secondary)]">
                                    {form.description}
                                </p>
                            )}

                            <div className="mt-5 flex flex-wrap gap-2">
                                {form.status === "active" && (
                                    <ButtonLink
                                        href={form.publicUrl}
                                        variant="secondary"
                                        target="_blank"
                                        rel="noreferrer"
                                        leadingIcon={<Icon name="external" className="h-4 w-4" />}
                                    >
                                        Open public form
                                    </ButtonLink>
                                )}
                                {canViewSubmissions && (
                                    <ButtonLink
                                        href={`/admin/forms/${form.slug}/submissions`}
                                        variant="secondary"
                                        leadingIcon={<Icon name="inbox" className="h-4 w-4" />}
                                    >
                                        Submissions
                                    </ButtonLink>
                                )}
                                {canManage && (
                                    <ButtonLink
                                        href={`/admin/forms/${form.slug}/edit`}
                                        variant="secondary"
                                        leadingIcon={<Icon name="edit" className="h-4 w-4" />}
                                    >
                                        Edit
                                    </ButtonLink>
                                )}
                                {canManage && form.status !== "active" && form.status !== "archived" && (
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={() => setStatus(form, "active")}
                                    >
                                        Activate
                                    </Button>
                                )}
                                {canManage && form.status === "active" && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => setStatus(form, "paused")}
                                    >
                                        Pause
                                    </Button>
                                )}
                            </div>
                        </Card>
                    ))}
                </div>
            )}

            {forms.last_page > 1 && (
                <nav className="flex items-center justify-between gap-3" aria-label="Forms pagination">
                    <div>
                        {forms.prev_page_url && (
                            <ButtonLink href={forms.prev_page_url} variant="secondary">
                                Previous
                            </ButtonLink>
                        )}
                    </div>
                    <span className="text-sm text-[var(--nx-text-muted)]">
                        Page {forms.current_page} of {forms.last_page}
                    </span>
                    <div>
                        {forms.next_page_url && (
                            <ButtonLink href={forms.next_page_url} variant="secondary">
                                Next
                            </ButtonLink>
                        )}
                    </div>
                </nav>
            )}
        </AdminLayout>
    );
}
