import type { RequestPayload } from "@inertiajs/core";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { Badge, Button, Card, Checkbox, Input, Select, TextLink } from "@nexora/admin-ui";
import { EmptyState } from "@admin/components/LoadingStates";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";
import type { SharedPageProps } from "@admin/types/page";

type Summary = {
    indexed: number;
    publishedIndexed: number;
    searches: number;
    zeroResultSearches: number;
    pageViews: number;
    uniqueVisitors: number;
    lastIndexedAt: string | null;
    lastAggregatedAt: string | null;
};

type ContentRow = {
    id: number;
    title: string;
    type: string;
    pageViews: number;
    uniqueVisitors: number;
    href: string | null;
};

type QueryRow = {
    query: string;
    searches: number;
    zeroResults: number;
};

type Crawl = {
    id: number;
    uuid: string;
    status: string;
    crawledUrls: number;
    failedUrls: number;
    issuesCount: number;
    highIssuesCount: number;
    startedAt: string | null;
    completedAt: string | null;
};

type Issue = {
    id: number;
    severity: string;
    code: string;
    category: string;
    title: string;
    description: string;
    url: string | null;
};

type DiscoverySettings = {
    publicSearch: boolean;
    analyticsEnabled: boolean;
    rawRetentionDays: number;
    searchRetentionDays: number;
    crawlerEnabled: boolean;
    crawlerMaxUrls: number;
};

type Props = {
    filters: { days: number };
    summary: Summary;
    topContent: ContentRow[];
    topQueries: QueryRow[];
    latestCrawl: Crawl | null;
    crawlIssues: Issue[];
    settings: DiscoverySettings;
};

function severityTone(severity: string): "danger" | "warning" | "neutral" {
    if (severity === "critical" || severity === "high") {
        return "danger";
    }

    return severity === "medium" ? "warning" : "neutral";
}

function formattedTime(value: string | null): string {
    if (! value) {
        return "Never";
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
}

export default function DiscoveryIndex({
    filters,
    summary,
    topContent,
    topQueries,
    latestCrawl,
    crawlIssues,
    settings,
}: Props) {
    const permissions = usePage<SharedPageProps>().props.auth.user?.permissions ?? [];
    const canIndex = permissions.includes("search.index.manage");
    const canAggregate = permissions.includes("analytics.aggregate");
    const canCrawl = permissions.includes("seo.crawler.run");
    const canManage = permissions.includes("discovery.manage");
    const settingsForm = useForm(settings);

    const post = (url: string, data: RequestPayload = {}) => {
        router.post(url, data, { preserveScroll: true });
    };

    const zeroResultRate = summary.searches > 0
        ? Math.round((summary.zeroResultSearches / summary.searches) * 100)
        : 0;

    return (
        <AdminLayout>
            <Head title="Search & Analytics" />
            <PageHeader
                eyebrow="Discovery intelligence"
                title="Search & Analytics"
                description="First-party content search, privacy-aware content analytics and an observable same-host SEO crawler. Nexora reports concrete findings rather than inventing a synthetic SEO score."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {canIndex && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => post("/admin/discovery/reindex")}
                                leadingIcon={<Icon name="refresh" className="h-4 w-4" />}
                            >
                                Rebuild index
                            </Button>
                        )}
                        {canAggregate && (
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => post("/admin/discovery/aggregate")}
                                leadingIcon={<Icon name="gauge" className="h-4 w-4" />}
                            >
                                Refresh analytics
                            </Button>
                        )}
                        {canCrawl && (
                            <Button
                                type="button"
                                onClick={() => post("/admin/discovery/crawl")}
                                leadingIcon={<Icon name="globe" className="h-4 w-4" />}
                            >
                                Run SEO crawl
                            </Button>
                        )}
                    </div>
                )}
            />

            <div className="mb-5 flex max-w-xs">
                <Select
                    ariaLabel="Analytics range"
                    value={String(filters.days)}
                    onChange={(value) => {
                        router.get(
                            "/admin/discovery",
                            { days: value },
                            {
                                preserveState: true,
                                replace: true,
                                preserveScroll: true,
                            },
                        );
                    }}
                    options={[
                        { value: "7", label: "Last 7 days" },
                        { value: "30", label: "Last 30 days" },
                        { value: "90", label: "Last 90 days" },
                    ]}
                />
            </div>

            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <Metric
                    icon="search"
                    label="Indexed resources"
                    value={summary.indexed.toLocaleString()}
                    hint={`${summary.publishedIndexed.toLocaleString()} published · Last index ${formattedTime(summary.lastIndexedAt)}`}
                />
                <Metric
                    icon="eye"
                    label="Page views"
                    value={summary.pageViews.toLocaleString()}
                    hint={`${summary.uniqueVisitors.toLocaleString()} daily-unique visitor total`}
                />
                <Metric
                    icon="search"
                    label="Site searches"
                    value={summary.searches.toLocaleString()}
                    hint={`${zeroResultRate}% returned no results`}
                />
                <Metric
                    icon="globe"
                    label="Latest crawl"
                    value={latestCrawl ? latestCrawl.status.replaceAll("_", " ") : "Not run"}
                    hint={latestCrawl
                        ? `${latestCrawl.crawledUrls} URLs · ${latestCrawl.issuesCount} observations`
                        : "Run a crawl when APP_URL is publicly reachable"}
                />
            </div>

            <div className="mt-5 grid gap-5 xl:grid-cols-2">
                <Card className="p-5">
                    <div className="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold text-[var(--nx-text)]">Top content</h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Aggregated first-party page views for the selected period.
                            </p>
                        </div>
                        <Badge tone="brand">Content analytics</Badge>
                    </div>

                    {topContent.length === 0 ? (
                        <EmptyState
                            title="No aggregated content data"
                            description="Publish content, receive page views, then refresh analytics to build daily aggregates."
                        />
                    ) : (
                        <div className="divide-y divide-[var(--nx-border)]">
                            {topContent.map((row) => (
                                <div
                                    key={row.id}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <div className="min-w-0">
                                        {row.href ? (
                                            <TextLink href={row.href} tone="neutral">
                                                {row.title}
                                            </TextLink>
                                        ) : (
                                            <p className="font-medium text-[var(--nx-text)]">{row.title}</p>
                                        )}
                                        <p className="mt-0.5 text-xs text-[var(--nx-text-muted)]">
                                            {row.type.replaceAll("_", " ")}
                                        </p>
                                    </div>
                                    <div className="shrink-0 text-right">
                                        <p className="font-semibold text-[var(--nx-text)]">
                                            {row.pageViews.toLocaleString()}
                                        </p>
                                        <p className="text-xs text-[var(--nx-text-muted)]">
                                            {row.uniqueVisitors.toLocaleString()} daily uniques
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>

                <Card className="p-5">
                    <div className="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 className="text-base font-semibold text-[var(--nx-text)]">Search demand</h2>
                            <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                                Queries visitors actually used. Zero-result searches expose content/findability gaps.
                            </p>
                        </div>
                        <Badge tone={zeroResultRate > 20 ? "warning" : "neutral"}>
                            {zeroResultRate}% zero-result
                        </Badge>
                    </div>

                    {topQueries.length === 0 ? (
                        <EmptyState
                            title="No search queries yet"
                            description="Public site search records normalized queries without storing raw IP addresses."
                        />
                    ) : (
                        <div className="divide-y divide-[var(--nx-border)]">
                            {topQueries.map((row) => (
                                <div
                                    key={row.query}
                                    className="flex items-center justify-between gap-4 py-3"
                                >
                                    <p className="min-w-0 truncate font-medium text-[var(--nx-text)]">
                                        {row.query}
                                    </p>
                                    <div className="shrink-0 text-right">
                                        <p className="font-semibold text-[var(--nx-text)]">{row.searches}</p>
                                        <p className="text-xs text-[var(--nx-text-muted)]">
                                            {row.zeroResults} no result
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            <Card className="mt-5 p-5">
                <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 className="text-base font-semibold text-[var(--nx-text)]">SEO crawler</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Same-host crawl observations covering response health, metadata, canonical/indexing conflicts, headings, Schema Graph presence and response time.
                        </p>
                    </div>
                    {latestCrawl && (
                        <div className="flex items-center gap-2">
                            <Badge
                                tone={latestCrawl.status === "completed"
                                    ? "success"
                                    : latestCrawl.status === "failed"
                                        ? "danger"
                                        : "warning"}
                            >
                                {latestCrawl.status}
                            </Badge>
                            <TextLink href={`/admin/discovery/crawls/${latestCrawl.id}`}>
                                Open crawl
                            </TextLink>
                        </div>
                    )}
                </div>

                {! latestCrawl ? (
                    <EmptyState
                        title="No crawl has been run"
                        description="Run the crawler after APP_URL points to the reachable Nexora site. Admin actions queue the crawl; the CLI command can run it immediately."
                    />
                ) : (
                    <>
                        <div className="mb-4 grid gap-3 sm:grid-cols-4">
                            <Mini label="Crawled" value={latestCrawl.crawledUrls} />
                            <Mini label="Failed" value={latestCrawl.failedUrls} />
                            <Mini label="Observations" value={latestCrawl.issuesCount} />
                            <Mini label="High" value={latestCrawl.highIssuesCount} />
                        </div>
                        {crawlIssues.length > 0 && (
                            <div className="divide-y divide-[var(--nx-border)]">
                                {crawlIssues.slice(0, 8).map((issue) => (
                                    <div key={issue.id} className="flex gap-3 py-3">
                                        <Badge tone={severityTone(issue.severity)}>{issue.severity}</Badge>
                                        <div className="min-w-0">
                                            <p className="font-medium text-[var(--nx-text)]">{issue.title}</p>
                                            <p className="mt-0.5 text-sm text-[var(--nx-text-muted)]">
                                                {issue.description}
                                            </p>
                                            {issue.url && (
                                                <p className="mt-1 truncate text-xs text-[var(--nx-text-muted)]">
                                                    {issue.url}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </Card>

            {canManage && (
                <Card className="mt-5 p-5 sm:p-6">
                    <div className="mb-5">
                        <h2 className="text-base font-semibold text-[var(--nx-text)]">Discovery settings</h2>
                        <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                            Control public search, first-party analytics retention and optional scheduled crawling without editing database settings directly.
                        </p>
                    </div>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            settingsForm.put("/admin/discovery/settings", { preserveScroll: true });
                        }}
                        className="grid gap-5"
                    >
                        <div className="grid gap-4 lg:grid-cols-2">
                            <Checkbox
                                checked={settingsForm.data.publicSearch}
                                onChange={(event) => settingsForm.setData("publicSearch", event.target.checked)}
                                label="Public site search"
                                description="Expose /search for published Nexora documents. Search-result pages remain noindex,follow."
                            />
                            <Checkbox
                                checked={settingsForm.data.analyticsEnabled}
                                onChange={(event) => settingsForm.setData("analyticsEnabled", event.target.checked)}
                                label="First-party content analytics"
                                description="Record privacy-aware page/search events. GPC and DNT requests are excluded."
                            />
                            <Checkbox
                                checked={settingsForm.data.crawlerEnabled}
                                onChange={(event) => settingsForm.setData("crawlerEnabled", event.target.checked)}
                                label="Scheduled SEO crawl"
                                description="Run the same-host crawler daily at 03:15. Manual Admin/CLI crawls remain available."
                            />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3">
                            <Input
                                label="Raw analytics retention"
                                type="number"
                                min={7}
                                max={365}
                                value={settingsForm.data.rawRetentionDays}
                                onChange={(event) => {
                                    settingsForm.setData("rawRetentionDays", Number(event.target.value));
                                }}
                                hint="Days; daily aggregates are retained separately."
                                error={settingsForm.errors.rawRetentionDays}
                            />
                            <Input
                                label="Search query retention"
                                type="number"
                                min={7}
                                max={365}
                                value={settingsForm.data.searchRetentionDays}
                                onChange={(event) => {
                                    settingsForm.setData("searchRetentionDays", Number(event.target.value));
                                }}
                                hint="Days before normalized raw search-demand logs are pruned."
                                error={settingsForm.errors.searchRetentionDays}
                            />
                            <Input
                                label="Crawler URL limit"
                                type="number"
                                min={10}
                                max={1000}
                                value={settingsForm.data.crawlerMaxUrls}
                                onChange={(event) => {
                                    settingsForm.setData("crawlerMaxUrls", Number(event.target.value));
                                }}
                                hint="Maximum URLs per scheduled crawl."
                                error={settingsForm.errors.crawlerMaxUrls}
                            />
                        </div>
                        <div className="flex justify-end border-t border-[var(--nx-border)] pt-4">
                            <Button
                                type="submit"
                                loading={settingsForm.processing}
                                disabled={! settingsForm.isDirty}
                            >
                                Save discovery settings
                            </Button>
                        </div>
                    </form>
                </Card>
            )}
        </AdminLayout>
    );
}

type MetricProps = {
    icon: string;
    label: string;
    value: string;
    hint: string;
};

function Metric({ icon, label, value, hint }: MetricProps) {
    return (
        <Card className="p-4">
            <div className="flex gap-3">
                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand-600)]">
                    <Icon name={icon} className="h-4 w-4" />
                </span>
                <div className="min-w-0">
                    <p className="text-xs font-medium text-[var(--nx-text-muted)]">{label}</p>
                    <p className="mt-0.5 truncate text-xl font-semibold capitalize text-[var(--nx-text)]">
                        {value}
                    </p>
                    <p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">{hint}</p>
                </div>
            </div>
        </Card>
    );
}

function Mini({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3">
            <p className="text-xs text-[var(--nx-text-muted)]">{label}</p>
            <p className="mt-1 text-lg font-semibold text-[var(--nx-text)]">
                {value.toLocaleString()}
            </p>
        </div>
    );
}
