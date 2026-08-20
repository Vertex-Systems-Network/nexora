import { router } from "@inertiajs/react";
import { Select } from "@nexora/admin-ui";
import type { EnterpriseSharedProps } from "@admin/types/page";

export function OrganizationSwitcher({ enterprise }: { enterprise: EnterpriseSharedProps }) {
    if (!enterprise.current || enterprise.available.length < 2) return null;
    return <Select
        ariaLabel="Current organization"
        value={enterprise.current.id}
        onChange={(organizationId) => router.post("/admin/enterprise/switch", { organization_id: organizationId }, { preserveScroll: true })}
        options={enterprise.available.map((organization) => ({ value: organization.id, label: organization.name }))}
        className="hidden min-w-48 lg:block"
    />;
}
