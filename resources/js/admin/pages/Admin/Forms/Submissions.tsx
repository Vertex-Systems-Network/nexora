import { Head } from "@inertiajs/react";
import { Badge, ButtonLink, Card } from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type FormItem = {
    id: number;
    name: string;
    slug: string;
    status: string;
    submissionCount: number;
    publicUrl: string;
};

type Submission = {
    id: number;
    uuid: string;
    status: string;
    values: Record<string, unknown>;
    metadata: Record<string, unknown>;
    submittedAt?: string | null;
    user?: {
        id: number;
        name: string;
        email: string;
    } | null;
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    form: FormItem;
    submissions: Paginator<Submission>;
};

function displayValue(value: unknown): string {
    if (value === null || value === undefined || value === "") return "—";
    if (typeof value === "boolean") return value ? "Yes" : "No";
    if (typeof value === "string" || typeof value === "number") return String(value);
    return JSON.stringify(value);
}

export default function FormSubmissions({ form, submissions }: Props) {
    return (
        <AdminLayout>
            <Head title={`Submissions · ${form.name}`} />
            <PageHeader
                eyebrow="Forms · Submissions"
                title={form.name}
                description="Validated form responses stored in the active tenant. Raw request headers and IP addresses are not part of the form response record."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {form.status === "active" && (
                            <ButtonLink
                                href={form.publicUrl}
                                target="_blank"
                                rel="noreferrer"
                                variant="secondary"
                                leadingIcon={<Icon name="external" className="h-4 w-4" />}
                            >
                                Public form
                            </ButtonLink>
                        )}
                        <ButtonLink
                            href={`/admin/forms/${form.slug}/edit`}
                            variant="secondary"
                        >
                            Edit form
                        </ButtonLink>
                        <ButtonLink href="/admin/forms" variant="secondary">
                            Back to forms
                        </ButtonLink>
                    </div>
                )}
            />

            <Card className="p-5 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.1em] text-[var(--nx-text-muted)]">
                            Response inbox
                        </p>
                        <p className="mt-1 text-sm text-[var(--nx-text-secondary)]">
                            {form.submissionCount} stored response{form.submissionCount === 1 ? "" : "s"}
                        </p>
                    </div>
                    <Badge tone={form.status === "active" ? "success" : "neutral"}>
                        {form.status}
                    </Badge>
                </div>
            </Card>

            {submissions.data.length === 0 ? (
                <Card className="p-8 text-center">
                    <Icon name="inbox" className="mx-auto h-8 w-8 text-[var(--nx-text-muted)]" />
                    <h2 className="mt-3 font-semibold text-[var(--nx-text)]">
                        No submissions yet
                    </h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        Valid public responses will appear here after they are stored.
                    </p>
                </Card>
            ) : (
                <div className="grid gap-4">
                    {submissions.data.map((submission) => (
                        <Card key={submission.id} className="p-5 sm:p-6">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <p className="font-semibold text-[var(--nx-text)]">
                                            Submission #{submission.id}
                                        </p>
                                        <Badge>{submission.status}</Badge>
                                        {submission.user && <Badge tone="success">Authenticated</Badge>}
                                    </div>
                                    <p className="mt-1 text-xs text-[var(--nx-text-muted)]">
                                        {submission.submittedAt
                                            ? new Date(submission.submittedAt).toLocaleString()
                                            : "Submission time unavailable"}
                                    </p>
                                </div>
                                <code className="max-w-52 truncate text-xs text-[var(--nx-text-muted)]">
                                    {submission.uuid}
                                </code>
                            </div>

                            {submission.user && (
                                <div className="mt-4 rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-sm">
                                    <span className="font-medium text-[var(--nx-text)]">
                                        {submission.user.name}
                                    </span>
                                    <span className="ms-2 text-[var(--nx-text-muted)]">
                                        {submission.user.email}
                                    </span>
                                </div>
                            )}

                            <dl className="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                {Object.entries(submission.values).map(([key, value]) => (
                                    <div
                                        key={key}
                                        className="min-w-0 rounded-xl border border-[var(--nx-border)] p-3"
                                    >
                                        <dt className="truncate text-xs font-semibold uppercase tracking-[0.08em] text-[var(--nx-text-muted)]">
                                            {key}
                                        </dt>
                                        <dd className="mt-1 whitespace-pre-wrap break-words text-sm text-[var(--nx-text)]">
                                            {displayValue(value)}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </Card>
                    ))}
                </div>
            )}

            {submissions.last_page > 1 && (
                <nav className="flex items-center justify-between gap-3" aria-label="Submission pagination">
                    <div>
                        {submissions.prev_page_url && (
                            <ButtonLink href={submissions.prev_page_url} variant="secondary">
                                Previous
                            </ButtonLink>
                        )}
                    </div>
                    <span className="text-sm text-[var(--nx-text-muted)]">
                        Page {submissions.current_page} of {submissions.last_page}
                    </span>
                    <div>
                        {submissions.next_page_url && (
                            <ButtonLink href={submissions.next_page_url} variant="secondary">
                                Next
                            </ButtonLink>
                        )}
                    </div>
                </nav>
            )}
        </AdminLayout>
    );
}
