import { useMemo, useState } from "react";
import { Head, router } from "@inertiajs/react";
import { AdminLayout } from "@admin/layout/AdminLayout";
import { PageHeader } from "@admin/components/PageHeader";
import { Icon } from "@admin/components/Icon";
import { ConfirmDialog } from "@admin/components/ConfirmDialog";
import { Badge, Button, Card, Input } from "@nexora/admin-ui";

type Ability = { slug: string; label: string; description: string };
type TokenRecord = {
  id: string;
  name: string;
  hint: string;
  abilities: string[];
  user?: { id: number; name: string; email: string } | null;
  last_used_at?: string | null;
  expires_at?: string | null;
  revoked_at?: string | null;
  created_at?: string | null;
};
type Props = {
  organization: { id: string; name: string; slug: string };
  tokens: TokenRecord[];
  abilities: Ability[];
  baseUrl: string;
};
type Issued = { token: string; record: Pick<TokenRecord, "id" | "name" | "hint" | "abilities" | "expires_at">; warning: string };

const csrf = () => document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? "";
const when = (value?: string | null) => (value ? new Date(value).toLocaleString() : "Never");

export default function ApiTokens({ organization, tokens, abilities, baseUrl }: Props) {
  const [name, setName] = useState("");
  const [expires, setExpires] = useState(90);
  const [selected, setSelected] = useState<string[]>(abilities[0] ? [abilities[0].slug] : []);
  const [issuing, setIssuing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [issued, setIssued] = useState<Issued | null>(null);
  const [revokeTarget, setRevokeTarget] = useState<TokenRecord | null>(null);
  const active = useMemo(() => tokens.filter((token) => !token.revoked_at), [tokens]);

  const toggleAbility = (slug: string) => {
    setSelected((current) => current.includes(slug) ? current.filter((item) => item !== slug) : [...current, slug]);
  };

  const issue = async () => {
    if (issuing) return;
    setIssuing(true);
    setError(null);
    setIssued(null);
    try {
      const response = await fetch("/admin/developer/api-tokens", {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf(),
        },
        body: JSON.stringify({ name, abilities: selected, expires_in_days: expires }),
      });
      const body = await response.json().catch(() => ({ message: `Token creation failed (HTTP ${response.status}).` }));
      if (!response.ok) {
        const fieldError = body?.errors ? Object.values(body.errors).flat()?.[0] : null;
        setError(typeof fieldError === "string" ? fieldError : body?.message ?? "API token creation failed.");
        return;
      }
      setIssued(body as Issued);
      setName("");
      router.reload({ only: ["tokens"], preserveState: true });
    } finally {
      setIssuing(false);
    }
  };

  const copyIssued = async () => {
    if (!issued) return;
    await navigator.clipboard.writeText(issued.token);
  };

  return <AdminLayout>
    <Head title="API & Integrations" />
    <PageHeader
      eyebrow="Developer platform"
      title="API & Integrations"
      description={`Issue tenant-bound API credentials for ${organization.name}. Tokens are shown once, stored as hashes, scoped explicitly and can be revoked at any time.`}
    />

    <div className="mb-5 grid gap-3 md:grid-cols-3">
      <Metric icon="code" label="API version" value="v1" hint={baseUrl} />
      <Metric icon="key" label="Active tokens" value={String(active.length)} hint={`${tokens.length} total records`} />
      <Metric icon="shield" label="Plaintext stored" value="0" hint="Hash-only token persistence" />
    </div>

    {issued && <Card className="mb-5 border-[var(--nx-warning)] p-5">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div className="min-w-0">
          <div className="flex items-center gap-2">
            <Icon name="key" className="h-5 w-5 text-[var(--nx-warning)]" />
            <h2 className="font-semibold text-[var(--nx-text)]">Copy this token now</h2>
          </div>
          <p className="mt-2 text-sm leading-6 text-[var(--nx-text-muted)]">{issued.warning}</p>
          <code className="mt-3 block overflow-x-auto rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface-subtle)] p-3 text-xs text-[var(--nx-text)]">{issued.token}</code>
        </div>
        <div className="flex shrink-0 gap-2">
          <Button variant="secondary" onClick={() => void copyIssued()} leadingIcon={<Icon name="copy" className="h-4 w-4" />}>Copy</Button>
          <Button variant="ghost" onClick={() => setIssued(null)}>Dismiss</Button>
        </div>
      </div>
    </Card>}

    <div className="grid gap-5 xl:grid-cols-[minmax(340px,0.75fr)_minmax(0,1.25fr)]">
      <Card className="p-5">
        <h2 className="font-semibold text-[var(--nx-text)]">Issue API token</h2>
        <p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">The plaintext credential exists only in the immediate JSON response and this browser state. It is never placed in session flash or persisted by Nexora.</p>

        <form className="mt-5 grid gap-4" onSubmit={(event) => { event.preventDefault(); void issue(); }}>
          <Input label="Token name" value={name} onChange={(event) => setName(event.target.value)} placeholder="Editorial integration" />
          <Input label="Expires in days" type="number" min={1} max={365} value={expires} onChange={(event) => setExpires(Number(event.target.value))} />

          <fieldset>
            <legend className="text-sm font-medium text-[var(--nx-text)]">Abilities</legend>
            <div className="mt-2 grid gap-2">
              {abilities.map((ability) => {
                const chosen = selected.includes(ability.slug);
                return <button
                  type="button"
                  key={ability.slug}
                  aria-pressed={chosen}
                  onClick={() => toggleAbility(ability.slug)}
                  className="rounded-xl border border-[var(--nx-border)] bg-[var(--nx-surface)] p-3 text-left transition hover:border-[var(--nx-brand)]"
                >
                  <div className="flex items-center justify-between gap-3">
                    <span className="font-medium text-[var(--nx-text)]">{ability.label}</span>
                    <Badge tone={chosen ? "success" : "neutral"}>{chosen ? "selected" : "not selected"}</Badge>
                  </div>
                  <p className="mt-1 text-xs leading-5 text-[var(--nx-text-muted)]">{ability.description}</p>
                  <code className="mt-2 block text-xs text-[var(--nx-text-muted)]">{ability.slug}</code>
                </button>;
              })}
            </div>
          </fieldset>

          {error && <p role="alert" className="text-sm text-[var(--nx-danger)]">{error}</p>}
          <div><Button type="submit" loading={issuing} disabled={selected.length === 0} leadingIcon={<Icon name="plus" className="h-4 w-4" />}>Issue token</Button></div>
        </form>
      </Card>

      <Card className="overflow-hidden">
        <div className="border-b border-[var(--nx-border)] p-5">
          <h2 className="font-semibold text-[var(--nx-text)]">Tenant API tokens</h2>
          <p className="mt-1 text-sm text-[var(--nx-text-muted)]">Only non-secret hints and lifecycle metadata are displayed. Guessing another tenant's token UUID cannot cross the active tenant scope.</p>
        </div>
        <div className="divide-y divide-[var(--nx-border)]">
          {tokens.map((token) => <div key={token.id} className="p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div>
                <div className="flex flex-wrap items-center gap-2">
                  <p className="font-medium text-[var(--nx-text)]">{token.name}</p>
                  <Badge tone={token.revoked_at ? "danger" : "success"}>{token.revoked_at ? "revoked" : "active"}</Badge>
                </div>
                <p className="mt-1 text-xs text-[var(--nx-text-muted)]">{token.hint} · issued by {token.user?.name ?? "Former user"}</p>
                <div className="mt-2 flex flex-wrap gap-1">{token.abilities.map((ability) => <Badge key={ability} tone="neutral">{ability}</Badge>)}</div>
                <p className="mt-2 text-xs text-[var(--nx-text-muted)]">Created {when(token.created_at)} · expires {when(token.expires_at)} · last used {when(token.last_used_at)}</p>
              </div>
              {!token.revoked_at && <Button size="sm" variant="ghost" onClick={() => setRevokeTarget(token)}>Revoke</Button>}
            </div>
          </div>)}
          {tokens.length === 0 && <p className="p-5 text-sm text-[var(--nx-text-muted)]">No API tokens have been issued for this organization.</p>}
        </div>
      </Card>
    </div>

    <Card className="mt-5 p-5">
      <h2 className="font-semibold text-[var(--nx-text)]">v1 document API</h2>
      <p className="mt-1 text-sm leading-6 text-[var(--nx-text-muted)]">Use <code>Authorization: Bearer &lt;token&gt;</code>. Document list responses use cursor pagination and cap <code>per_page</code> at 100.</p>
      <div className="mt-3 grid gap-2 text-xs">
        <code className="rounded-lg bg-[var(--nx-surface-subtle)] p-3 text-[var(--nx-text)]">GET {baseUrl}/documents</code>
        <code className="rounded-lg bg-[var(--nx-surface-subtle)] p-3 text-[var(--nx-text)]">GET {baseUrl}/documents/&#123;document&#125;</code>
      </div>
    </Card>

    <ConfirmDialog
      open={revokeTarget !== null}
      onClose={() => setRevokeTarget(null)}
      title="Revoke API token?"
      description="Requests using this credential will fail immediately. Revocation cannot reveal or restore the original plaintext token."
      confirmLabel="Revoke token"
      tone="danger"
      onConfirm={() => {
        if (!revokeTarget) return;
        router.delete(`/admin/developer/api-tokens/${revokeTarget.id}`, { preserveScroll: true, onFinish: () => setRevokeTarget(null) });
      }}
    />
  </AdminLayout>;
}

function Metric({ icon, label, value, hint }: { icon: string; label: string; value: string; hint: string }) {
  return <Card className="p-4"><div className="flex gap-3"><span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[var(--nx-brand-soft)] text-[var(--nx-brand)]"><Icon name={icon} className="h-4 w-4" /></span><div className="min-w-0"><p className="text-xs font-medium text-[var(--nx-text-muted)]">{label}</p><p className="mt-0.5 text-xl font-semibold text-[var(--nx-text)]">{value}</p><p className="mt-1 truncate text-xs text-[var(--nx-text-muted)]">{hint}</p></div></div></Card>;
}
