import { Head, useForm } from "@inertiajs/react";
import {
    Badge,
    Button,
    ButtonLink,
    Card,
    Checkbox,
    DateTimePicker,
    Input,
    Select,
} from "@nexora/admin-ui";
import { Icon } from "@admin/components/Icon";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type SimpleOption = {
    id: number;
    name: string;
    slug: string;
};

type Term = SimpleOption & {
    taxonomy: string;
};

type MediaOption = {
    id: number;
    name: string;
    url: string;
};

type Document = {
    id: number;
    title: string;
    type: string;
    status: string;
    slug: string | null;
    scheduled_at: string;
    is_featured: boolean;
    featured_until: string;
    hero_image_url: string;
    hero_media_id: number | null;
    source_url: string;
    allow_comments: boolean;
    is_sponsored: boolean;
    author_profile_ids: number[];
    term_ids: number[];
    series_id: number | null;
    series_position: number;
};

type Props = {
    document: Document;
    authors: SimpleOption[];
    terms: Term[];
    series: SimpleOption[];
    media: MediaOption[];
};

export default function ArticleSettings({ document, authors, terms, series, media }: Props) {
    const form = useForm({
        scheduled_at: document.scheduled_at,
        is_featured: document.is_featured,
        featured_until: document.featured_until,
        hero_image_url: document.hero_image_url,
        hero_media_id: document.hero_media_id ? String(document.hero_media_id) : "",
        source_url: document.source_url,
        allow_comments: document.allow_comments,
        is_sponsored: document.is_sponsored,
        author_profile_ids: document.author_profile_ids,
        term_ids: document.term_ids,
        series_id: document.series_id ? String(document.series_id) : "",
        series_position: document.series_position,
    });

    const toggle = (key: "author_profile_ids" | "term_ids", id: number) => {
        const selected = form.data[key];
        form.setData(
            key,
            selected.includes(id)
                ? selected.filter((value) => value !== id)
                : [...selected, id],
        );
    };

    const groupedTerms = terms.reduce<Record<string, Term[]>>((groups, term) => {
        (groups[term.taxonomy] ??= []).push(term);
        return groups;
    }, {});

    const selectedMedia = media.find(
        (item) => String(item.id) === form.data.hero_media_id,
    );

    const submit = () => {
        form.transform((data) => ({
            ...data,
            hero_media_id: data.hero_media_id ? Number(data.hero_media_id) : null,
        }));
        form.put(`/admin/publishing/articles/${document.id}/settings`);
    };

    return (
        <AdminLayout>
            <Head title={`Publishing · ${document.title}`} />
            <PageHeader
                eyebrow="Publishing settings"
                title={document.title}
                description="Manage bylines, taxonomy, series, scheduling and article presentation without duplicating Writer or SEO settings."
                actions={(
                    <div className="flex gap-2">
                        <ButtonLink
                            href={`/admin/documents/${document.id}/edit`}
                            variant="secondary"
                            leadingIcon={<Icon name="edit" className="h-4 w-4" />}
                        >
                            Writer
                        </ButtonLink>
                        <ButtonLink
                            href={`/admin/seo/documents/${document.id}`}
                            variant="secondary"
                            leadingIcon={<Icon name="search" className="h-4 w-4" />}
                        >
                            SEO
                        </ButtonLink>
                        <ButtonLink
                            href="/admin/media"
                            variant="secondary"
                            leadingIcon={<Icon name="image" className="h-4 w-4" />}
                        >
                            Media Library
                        </ButtonLink>
                        <ButtonLink href="/admin/publishing/articles" variant="secondary">
                            Back
                        </ButtonLink>
                    </div>
                )}
            />

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    submit();
                }}
                className="grid gap-5 xl:grid-cols-[minmax(0,1fr)_23rem]"
            >
                <div className="grid gap-5">
                    <Card className="p-5 sm:p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div>
                                <h2 className="font-semibold text-[var(--nx-text)]">Authors & byline</h2>
                                <p className="text-sm text-[var(--nx-text-muted)]">
                                    First selected profile is the primary author.
                                </p>
                            </div>
                            <Badge>{authors.length} available</Badge>
                        </div>
                        <div className="grid gap-2">
                            {authors.length > 0 ? authors.map((author) => (
                                <Checkbox
                                    key={author.id}
                                    checked={form.data.author_profile_ids.includes(author.id)}
                                    onChange={() => toggle("author_profile_ids", author.id)}
                                    label={author.name}
                                />
                            )) : (
                                <p className="text-sm text-[var(--nx-text-muted)]">
                                    Create an author profile first.
                                </p>
                            )}
                        </div>
                    </Card>

                    <Card className="p-5 sm:p-6">
                        <h2 className="font-semibold text-[var(--nx-text)]">Categories, topics & tags</h2>
                        <div className="mt-4 grid gap-5 md:grid-cols-3">
                            {["category", "topic", "tag"].map((group) => (
                                <div key={group}>
                                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--nx-text-muted)]">
                                        {group === "category" ? "Categories" : group === "topic" ? "Topics" : "Tags"}
                                    </p>
                                    <div className="grid gap-2">
                                        {(groupedTerms[group] ?? []).map((term) => (
                                            <Checkbox
                                                key={term.id}
                                                checked={form.data.term_ids.includes(term.id)}
                                                onChange={() => toggle("term_ids", term.id)}
                                                label={term.name}
                                            />
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>

                <div className="grid h-fit gap-5 xl:sticky xl:top-24">
                    <Card className="p-5 sm:p-6">
                        <div className="grid gap-4">
                            <DateTimePicker
                                label="Schedule publication"
                                value={form.data.scheduled_at}
                                onChange={(value) => form.setData("scheduled_at", value)}
                                hint="Leave empty to publish manually from Writer."
                            />
                            <Checkbox
                                checked={form.data.is_featured}
                                onChange={(event) => form.setData("is_featured", event.target.checked)}
                                label="Feature this content"
                            />
                            <DateTimePicker
                                label="Feature until"
                                value={form.data.featured_until}
                                onChange={(value) => form.setData("featured_until", value)}
                            />
                            <Select
                                label="Hero image from Media Library"
                                value={form.data.hero_media_id}
                                onChange={(value) => form.setData("hero_media_id", value)}
                                options={[
                                    { value: "", label: "No Media Library image" },
                                    ...media.map((item) => ({
                                        value: String(item.id),
                                        label: item.name,
                                    })),
                                ]}
                            />
                            {selectedMedia && (
                                <div className="overflow-hidden rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)]">
                                    <img
                                        src={selectedMedia.url}
                                        alt=""
                                        className="aspect-[16/9] w-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </div>
                            )}
                            <Input
                                label="External hero image URL"
                                value={form.data.hero_image_url}
                                onChange={(event) => form.setData("hero_image_url", event.target.value)}
                                placeholder="Optional fallback https://…"
                                hint="Media Library takes priority when selected."
                            />
                            <Input
                                label="Original source URL"
                                value={form.data.source_url}
                                onChange={(event) => form.setData("source_url", event.target.value)}
                                placeholder="Optional canonical source reference"
                            />
                            <Checkbox
                                checked={form.data.allow_comments}
                                onChange={(event) => form.setData("allow_comments", event.target.checked)}
                                label="Allow comments when a comment provider is installed"
                            />
                            <Checkbox
                                checked={form.data.is_sponsored}
                                onChange={(event) => form.setData("is_sponsored", event.target.checked)}
                                label="Mark as sponsored content"
                            />
                            <Select
                                label="Series"
                                value={form.data.series_id}
                                onChange={(value) => form.setData("series_id", value)}
                                options={[
                                    { value: "", label: "No series" },
                                    ...series.map((item) => ({
                                        value: String(item.id),
                                        label: item.name,
                                    })),
                                ]}
                            />
                            <Input
                                type="number"
                                min={1}
                                label="Series position"
                                value={form.data.series_position}
                                onChange={(event) => {
                                    form.setData("series_position", Number(event.target.value) || 1);
                                }}
                            />
                            <Button
                                type="submit"
                                loading={form.processing}
                                leadingIcon={<Icon name="check" className="h-4 w-4" />}
                            >
                                Save publishing settings
                            </Button>
                        </div>
                    </Card>
                </div>
            </form>
        </AdminLayout>
    );
}
