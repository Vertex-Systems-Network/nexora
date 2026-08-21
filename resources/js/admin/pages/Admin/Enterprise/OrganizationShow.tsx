import { Head, router, useForm } from "@inertiajs/react";
import { useMemo, useState } from "react";
import {
    Badge,
    Button,
    Card,
    Checkbox,
    Input,
    Modal,
    Select,
    Textarea,
} from "@nexora/admin-ui";
import { DataTable, type Column } from "@admin/components/data/DataTable";
import { PageHeader } from "@admin/components/PageHeader";
import { AdminLayout } from "@admin/layout/AdminLayout";

type Organization = {
    id: string;
    name: string;
    slug: string;
    status: string;
    timezone: string;
    locale: string;
    is_default: boolean;
};

type Member = {
    id: string;
    user_id: number;
    name: string | null;
    email: string | null;
    user_status: string | null;
    role: string;
    status: string;
    joined_at: string | null;
};

type EnterpriseRole = {
    id: string;
    name: string;
    slug: string;
    permissions: string[];
    is_system: boolean;
};

type Domain = {
    id: string;
    host: string;
    status: string;
    is_primary: boolean;
    verified_at: string | null;
};

type Invitation = {
    id: string;
    email: string;
    role: string;
    expires_at: string | null;
};

type SsoProvider = {
    id: string;
    name: string;
    slug: string;
    protocol: string;
    adapter_key: string;
    enabled: boolean;
    enforce_for_members: boolean;
    adapter_available: boolean;
};

type ScimToken = {
    id: string;
    name: string;
    enabled: boolean;
    last_used_at: string | null;
    expires_at: string | null;
    revoked_at: string | null;
};

type AuditEvent = {
    id: string;
    event_type: string;
    subject_type: string | null;
    subject_id: string | null;
    actor_user_id: number | null;
    occurred_at: string | null;
    payload: unknown;
};

type UserOption = {
    id: number;
    name: string;
};

type Permission = {
    slug: string;
    name: string;
    group: string;
};

type Props = {
    organization: Organization;
    members: Member[];
    roles: EnterpriseRole[];
    domains: Domain[];
    invitations: Invitation[];
    ssoProviders: SsoProvider[];
    registeredIdentityAdapters: Array<{ key: string; protocol: string }>;
    scimTokens: ScimToken[];
    audit: AuditEvent[];
    users: UserOption[];
    availablePermissions: Permission[];
    canManageMembers: boolean;
    canDirectAddMembers: boolean;
    canManageDomains: boolean;
    canManageIdentity: boolean;
    canManageScim: boolean;
    canImpersonate: boolean;
    oneTimeInvitationUrl: string | null;
    oneTimeDomainToken: { host: string; dns_name: string; value: string } | null;
    oneTimeScimToken: string | null;
};

function humanize(value: string): string {
    return value
        .replace(/[._-]/g, " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formattedDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : "—";
}

export default function OrganizationShow(props: Props) {
    const [memberOpen, setMemberOpen] = useState(false);
    const [inviteOpen, setInviteOpen] = useState(false);
    const [domainOpen, setDomainOpen] = useState(false);
    const [ssoOpen, setSsoOpen] = useState(false);
    const [scimOpen, setScimOpen] = useState(false);
    const [impersonationOpen, setImpersonationOpen] = useState(false);
    const [roleEdit, setRoleEdit] = useState<EnterpriseRole | null>(null);

    const memberForm = useForm({ user_id: "", role: "member" });
    const invitationForm = useForm({ email: "", role: "member" });
    const domainForm = useForm({ host: "", is_primary: false });
    // Deliberate shallow boundary: SSO configuration and secret payload default server-side.
    const ssoForm = useForm({
        name: "",
        slug: "",
        protocol: "oidc",
        adapter_key: "",
        enabled: false,
        enforce_for_members: false,
    });
    const scimForm = useForm({ name: "Provisioning token", expires_at: "" });
    const impersonationForm = useForm({ target_user_id: "", reason: "" });
    const roleForm = useForm({ permissions: [] as string[] });

    const memberColumns = useMemo<Column<Member>[]>(() => [
        {
            key: "member",
            label: "Member",
            render: (member) => (
                <div>
                    <p className="font-semibold text-[var(--nx-text)]">
                        {member.name ?? "Unknown user"}
                    </p>
                    <p className="text-xs text-[var(--nx-text-muted)]">{member.email}</p>
                </div>
            ),
        },
        {
            key: "role",
            label: "Organization role",
            render: (member) => <Badge>{humanize(member.role)}</Badge>,
        },
        {
            key: "status",
            label: "Status",
            render: (member) => <Badge>{humanize(member.status)}</Badge>,
        },
        {
            key: "joined",
            label: "Joined",
            render: (member) => (
                <span className="text-sm text-[var(--nx-text-secondary)]">
                    {formattedDate(member.joined_at)}
                </span>
            ),
        },
        {
            key: "action",
            label: "",
            render: (member) => props.canImpersonate ? (
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => {
                        impersonationForm.setData("target_user_id", String(member.user_id));
                        setImpersonationOpen(true);
                    }}
                >
                    Impersonate
                </Button>
            ) : null,
        },
    ], [props.canImpersonate]);

    const permissionGroups = Array.from(
        new Set(props.availablePermissions.map((permission) => permission.group)),
    );
    const impersonationUsers = props.members
        .filter((member) => member.status === "active" && member.user_status === "active")
        .map((member) => ({
            value: String(member.user_id),
            label: [member.name ?? "Unknown user", member.email].filter(Boolean).join(" · "),
        }));

    const openRolePermissions = (role: EnterpriseRole) => {
        setRoleEdit(role);
        roleForm.setData(
            "permissions",
            role.permissions.includes("*") ? [] : role.permissions,
        );
    };

    return (
        <AdminLayout>
            <Head title={`${props.organization.name} · Enterprise`} />
            <PageHeader
                eyebrow="Enterprise"
                title={props.organization.name}
                description={[
                    `Tenant ${props.organization.slug}`,
                    humanize(props.organization.status),
                    props.organization.timezone,
                ].join(" · ")}
                actions={(
                    <Button
                        variant="secondary"
                        onClick={() => router.visit("/admin/enterprise")}
                    >
                        All organizations
                    </Button>
                )}
            />

            {(props.oneTimeInvitationUrl || props.oneTimeDomainToken || props.oneTimeScimToken) && (
                <Card className="border-[var(--nx-brand-300)] p-5">
                    <h2 className="font-semibold text-[var(--nx-text)]">One-time setup value</h2>

                    {props.oneTimeInvitationUrl && (
                        <>
                            <p className="mt-2 text-sm text-[var(--nx-text-muted)]">
                                Invitation acceptance URL
                            </p>
                            <code className="mt-1 block break-all rounded-lg bg-[var(--nx-surface-subtle)] p-3 text-xs">
                                {props.oneTimeInvitationUrl}
                            </code>
                        </>
                    )}

                    {props.oneTimeDomainToken && (
                        <>
                            <p className="mt-2 text-sm text-[var(--nx-text-muted)]">
                                Publish this DNS TXT record: {props.oneTimeDomainToken.dns_name}
                            </p>
                            <code className="mt-1 block break-all rounded-lg bg-[var(--nx-surface-subtle)] p-3 text-xs">
                                {props.oneTimeDomainToken.value}
                            </code>
                        </>
                    )}

                    {props.oneTimeScimToken && (
                        <>
                            <p className="mt-2 text-sm text-[var(--nx-text-muted)]">
                                SCIM bearer token — it will not be shown again.
                            </p>
                            <code className="mt-1 block break-all rounded-lg bg-[var(--nx-surface-subtle)] p-3 text-xs">
                                {props.oneTimeScimToken}
                            </code>
                        </>
                    )}
                </Card>
            )}

            <Card className="p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-[var(--nx-text)]">Members</h2>
                        <p className="text-sm text-[var(--nx-text-muted)]">
                            Platform permissions are further restricted by organization roles.
                        </p>
                    </div>
                    {props.canManageMembers && (
                        <div className="flex gap-2">
                            <Button variant="secondary" onClick={() => setInviteOpen(true)}>
                                Invite
                            </Button>
                            {props.canDirectAddMembers && (
                                <Button onClick={() => setMemberOpen(true)}>Add member</Button>
                            )}
                        </div>
                    )}
                </div>
                <DataTable rows={props.members} columns={memberColumns} />
            </Card>

            <div className="grid gap-4 xl:grid-cols-2">
                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">Domains</h2>
                            <p className="text-sm text-[var(--nx-text-muted)]">
                                Verified hosts can resolve the tenant before public rendering.
                            </p>
                        </div>
                        {props.canManageDomains && (
                            <Button size="sm" onClick={() => setDomainOpen(true)}>
                                Add domain
                            </Button>
                        )}
                    </div>
                    <div className="space-y-2">
                        {props.domains.length > 0 ? props.domains.map((domain) => (
                            <div
                                key={domain.id}
                                className="flex items-center justify-between gap-3 rounded-xl border border-[var(--nx-border)] p-3"
                            >
                                <div>
                                    <p className="font-medium text-[var(--nx-text)]">{domain.host}</p>
                                    <p className="text-xs text-[var(--nx-text-muted)]">
                                        {domain.is_primary ? "Primary · " : ""}{humanize(domain.status)}
                                    </p>
                                </div>
                                {domain.status !== "verified" && props.canManageDomains ? (
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => {
                                            router.post(
                                                `/admin/enterprise/organizations/${props.organization.id}/domains/${domain.id}/verify`,
                                                {},
                                                { preserveScroll: true },
                                            );
                                        }}
                                    >
                                        Verify DNS
                                    </Button>
                                ) : (
                                    <Badge>{humanize(domain.status)}</Badge>
                                )}
                            </div>
                        )) : (
                            <p className="text-sm text-[var(--nx-text-muted)]">No domains configured.</p>
                        )}
                    </div>
                </Card>

                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">Enterprise identity</h2>
                            <p className="text-sm text-[var(--nx-text-muted)]">
                                OIDC/SAML records activate only when a verified adapter is registered.
                            </p>
                        </div>
                        {props.canManageIdentity && (
                            <Button size="sm" onClick={() => setSsoOpen(true)}>
                                Add provider
                            </Button>
                        )}
                    </div>
                    <div className="space-y-2">
                        {props.ssoProviders.length > 0 ? props.ssoProviders.map((provider) => (
                            <div
                                key={provider.id}
                                className="flex items-center justify-between gap-3 rounded-xl border border-[var(--nx-border)] p-3"
                            >
                                <div>
                                    <p className="font-medium text-[var(--nx-text)]">{provider.name}</p>
                                    <p className="text-xs text-[var(--nx-text-muted)]">
                                        {provider.protocol.toUpperCase()} · {provider.adapter_key} · {provider.adapter_available ? "Adapter ready" : "Adapter unavailable"}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge>{provider.enabled ? "Enabled" : "Disabled"}</Badge>
                                    {props.canManageIdentity && (
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            onClick={() => {
                                                router.post(
                                                    `/admin/enterprise/organizations/${props.organization.id}/sso/${provider.id}/health`,
                                                    {},
                                                    { preserveScroll: true },
                                                );
                                            }}
                                        >
                                            Health
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )) : (
                            <p className="text-sm text-[var(--nx-text-muted)]">
                                No identity providers configured.
                            </p>
                        )}
                    </div>
                </Card>
            </div>

            <div className="grid gap-4 xl:grid-cols-2">
                <Card className="p-5">
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold text-[var(--nx-text)]">SCIM provisioning</h2>
                            <p className="text-sm text-[var(--nx-text-muted)]">
                                Organization-scoped bearer tokens; only hashes are stored.
                            </p>
                        </div>
                        {props.canManageScim && (
                            <Button size="sm" onClick={() => setScimOpen(true)}>
                                Issue token
                            </Button>
                        )}
                    </div>
                    <div className="space-y-2">
                        {props.scimTokens.length > 0 ? props.scimTokens.map((token) => (
                            <div
                                key={token.id}
                                className="flex items-center justify-between gap-3 rounded-xl border border-[var(--nx-border)] p-3"
                            >
                                <div>
                                    <p className="font-medium text-[var(--nx-text)]">{token.name}</p>
                                    <p className="text-xs text-[var(--nx-text-muted)]">
                                        {token.last_used_at
                                            ? `Last used ${formattedDate(token.last_used_at)}`
                                            : "Never used"}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Badge>
                                        {token.revoked_at ? "Revoked" : token.enabled ? "Active" : "Disabled"}
                                    </Badge>
                                    {props.canManageScim && ! token.revoked_at && (
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            onClick={() => {
                                                router.patch(
                                                    `/admin/enterprise/organizations/${props.organization.id}/scim/${token.id}/revoke`,
                                                    {},
                                                    { preserveScroll: true },
                                                );
                                            }}
                                        >
                                            Revoke
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )) : (
                            <p className="text-sm text-[var(--nx-text-muted)]">No SCIM tokens.</p>
                        )}
                    </div>
                </Card>

                <Card className="p-5">
                    <h2 className="font-semibold text-[var(--nx-text)]">Enterprise roles</h2>
                    <p className="mt-1 text-sm text-[var(--nx-text-muted)]">
                        These roles can only restrict permissions already granted by platform RBAC.
                    </p>
                    <div className="mt-4 space-y-2">
                        {props.roles.map((role) => (
                            <div
                                key={role.id}
                                className="flex items-center justify-between gap-3 rounded-xl border border-[var(--nx-border)] p-3"
                            >
                                <div>
                                    <p className="font-medium text-[var(--nx-text)]">{role.name}</p>
                                    <p className="text-xs text-[var(--nx-text-muted)]">
                                        {role.permissions.includes("*")
                                            ? "All platform-granted permissions"
                                            : `${role.permissions.length} allowed permissions`}
                                    </p>
                                </div>
                                {props.canManageMembers && (
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        onClick={() => openRolePermissions(role)}
                                        disabled={role.permissions.includes("*") && ! props.canImpersonate}
                                    >
                                        Permissions
                                    </Button>
                                )}
                            </div>
                        ))}
                    </div>
                </Card>
            </div>

            <Card className="p-5">
                <h2 className="font-semibold text-[var(--nx-text)]">Governance audit</h2>
                <div className="mt-4 space-y-2">
                    {props.audit.length > 0 ? props.audit.map((event) => (
                        <div
                            key={event.id}
                            className="flex flex-wrap justify-between gap-3 border-b border-[var(--nx-border)] py-3 last:border-0"
                        >
                            <div>
                                <p className="text-sm font-medium text-[var(--nx-text)]">
                                    {humanize(event.event_type)}
                                </p>
                                <p className="text-xs text-[var(--nx-text-muted)]">
                                    {event.subject_type ?? "organization"} {event.subject_id ?? ""}
                                </p>
                            </div>
                            <span className="text-xs text-[var(--nx-text-muted)]">
                                {formattedDate(event.occurred_at)}
                            </span>
                        </div>
                    )) : (
                        <p className="text-sm text-[var(--nx-text-muted)]">
                            No enterprise governance events yet.
                        </p>
                    )}
                </div>
            </Card>

            <Modal
                open={memberOpen && props.canDirectAddMembers}
                onClose={() => setMemberOpen(false)}
                title="Add organization member"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setMemberOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={memberForm.processing}
                            onClick={() => {
                                memberForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/members`,
                                    { onSuccess: () => setMemberOpen(false) },
                                );
                            }}
                        >
                            Save member
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Select
                        label="User"
                        value={memberForm.data.user_id}
                        onChange={(value) => memberForm.setData("user_id", value)}
                        options={[
                            { value: "", label: "Choose a user" },
                            ...props.users.map((user) => ({ value: String(user.id), label: user.name })),
                        ]}
                    />
                    <Select
                        label="Organization role"
                        value={memberForm.data.role}
                        onChange={(value) => memberForm.setData("role", value)}
                        options={["owner", "admin", "member", "viewer"].map((value) => ({
                            value,
                            label: humanize(value),
                        }))}
                    />
                </div>
            </Modal>

            <Modal
                open={inviteOpen}
                onClose={() => setInviteOpen(false)}
                title="Invite member"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setInviteOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={invitationForm.processing}
                            onClick={() => {
                                invitationForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/invitations`,
                                    { onSuccess: () => setInviteOpen(false) },
                                );
                            }}
                        >
                            Create invitation
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Email"
                        type="email"
                        value={invitationForm.data.email}
                        onChange={(event) => invitationForm.setData("email", event.target.value)}
                    />
                    <Select
                        label="Organization role"
                        value={invitationForm.data.role}
                        onChange={(value) => invitationForm.setData("role", value)}
                        options={["admin", "member", "viewer"].map((value) => ({
                            value,
                            label: humanize(value),
                        }))}
                    />
                </div>
            </Modal>

            <Modal
                open={domainOpen}
                onClose={() => setDomainOpen(false)}
                title="Add domain"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setDomainOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={domainForm.processing}
                            onClick={() => {
                                domainForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/domains`,
                                    { onSuccess: () => setDomainOpen(false) },
                                );
                            }}
                        >
                            Add domain
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Host"
                        value={domainForm.data.host}
                        onChange={(event) => domainForm.setData("host", event.target.value)}
                        placeholder="portal.example.com"
                    />
                    <Checkbox
                        checked={domainForm.data.is_primary}
                        onChange={(event) => domainForm.setData("is_primary", event.target.checked)}
                        label="Primary domain"
                    />
                </div>
            </Modal>

            <Modal
                open={ssoOpen}
                onClose={() => setSsoOpen(false)}
                title="Add enterprise identity provider"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setSsoOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={ssoForm.processing}
                            onClick={() => {
                                ssoForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/sso`,
                                    { onSuccess: () => setSsoOpen(false) },
                                );
                            }}
                        >
                            Save provider
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Input
                        label="Name"
                        value={ssoForm.data.name}
                        onChange={(event) => ssoForm.setData("name", event.target.value)}
                    />
                    <Input
                        label="Slug"
                        value={ssoForm.data.slug}
                        onChange={(event) => ssoForm.setData("slug", event.target.value)}
                    />
                    <Select
                        label="Protocol"
                        value={ssoForm.data.protocol}
                        onChange={(value) => ssoForm.setData("protocol", value)}
                        options={[
                            { value: "oidc", label: "OpenID Connect" },
                            { value: "saml", label: "SAML 2.0" },
                        ]}
                    />
                    <Input
                        label="Adapter key"
                        value={ssoForm.data.adapter_key}
                        onChange={(event) => ssoForm.setData("adapter_key", event.target.value)}
                        placeholder="vendor.identity-adapter"
                    />
                    <Checkbox
                        checked={ssoForm.data.enabled}
                        onChange={(event) => ssoForm.setData("enabled", event.target.checked)}
                        label="Enable after adapter validation"
                    />
                    <Checkbox
                        checked={ssoForm.data.enforce_for_members}
                        onChange={(event) => {
                            ssoForm.setData("enforce_for_members", event.target.checked);
                        }}
                        label="Mark as enforced for organization members"
                    />
                </div>
            </Modal>

            <Modal
                open={scimOpen}
                onClose={() => setScimOpen(false)}
                title="Issue SCIM token"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setScimOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={scimForm.processing}
                            onClick={() => {
                                scimForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/scim`,
                                    { onSuccess: () => setScimOpen(false) },
                                );
                            }}
                        >
                            Issue token
                        </Button>
                    </>
                )}
            >
                <Input
                    label="Token name"
                    value={scimForm.data.name}
                    onChange={(event) => scimForm.setData("name", event.target.value)}
                />
            </Modal>

            <Modal
                open={impersonationOpen}
                onClose={() => setImpersonationOpen(false)}
                title="Start governed impersonation"
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setImpersonationOpen(false)}>
                            Cancel
                        </Button>
                        <Button
                            loading={impersonationForm.processing}
                            onClick={() => {
                                impersonationForm.post(
                                    `/admin/enterprise/organizations/${props.organization.id}/impersonate`,
                                );
                            }}
                        >
                            Start impersonation
                        </Button>
                    </>
                )}
            >
                <div className="grid gap-4">
                    <Select
                        label="Target user"
                        value={impersonationForm.data.target_user_id}
                        onChange={(value) => impersonationForm.setData("target_user_id", value)}
                        options={[
                            { value: "", label: "Choose an organization member" },
                            ...impersonationUsers,
                        ]}
                    />
                    <Textarea
                        label="Reason"
                        value={impersonationForm.data.reason}
                        onChange={(event) => impersonationForm.setData("reason", event.target.value)}
                        placeholder="Explain why temporary impersonation is required."
                    />
                </div>
            </Modal>

            <Modal
                open={roleEdit !== null}
                onClose={() => setRoleEdit(null)}
                title={roleEdit ? `${roleEdit.name} permissions` : "Role permissions"}
                footer={(
                    <>
                        <Button variant="secondary" onClick={() => setRoleEdit(null)}>
                            Cancel
                        </Button>
                        <Button
                            disabled={! roleEdit}
                            loading={roleForm.processing}
                            onClick={() => {
                                if (! roleEdit) {
                                    return;
                                }

                                roleForm.put(
                                    `/admin/enterprise/organizations/${props.organization.id}/roles/${roleEdit.id}`,
                                    { onSuccess: () => setRoleEdit(null) },
                                );
                            }}
                        >
                            Save permissions
                        </Button>
                    </>
                )}
            >
                <div className="max-h-[55vh] space-y-4 overflow-auto">
                    {permissionGroups.map((group) => (
                        <div key={group}>
                            <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-[var(--nx-text-muted)]">
                                {humanize(group)}
                            </p>
                            <div className="grid gap-2">
                                {props.availablePermissions
                                    .filter((permission) => permission.group === group)
                                    .map((permission) => (
                                        <Checkbox
                                            key={permission.slug}
                                            checked={roleForm.data.permissions.includes(permission.slug)}
                                            onChange={(event) => {
                                                const current = roleForm.data.permissions;
                                                roleForm.setData(
                                                    "permissions",
                                                    event.target.checked
                                                        ? [...current, permission.slug]
                                                        : current.filter((slug) => slug !== permission.slug),
                                                );
                                            }}
                                            label={permission.name}
                                        />
                                    ))}
                            </div>
                        </div>
                    ))}
                </div>
            </Modal>
        </AdminLayout>
    );
}
