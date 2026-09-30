import { router } from "@inertiajs/react";
import { Select } from "@nexora/admin-ui";
import type { EnterpriseSharedProps } from "@admin/types/page";
import { cx } from "@admin/utils/cx";

export function OrganizationSwitcher({
    enterprise,
    className,
    label = false,
}: {
    enterprise: EnterpriseSharedProps;
    className?: string;
    label?: boolean;
}) {
    if (!enterprise.current || enterprise.available.length < 2) return null;

    return (
        <Select
            label={label ? "Organization" : undefined}
            ariaLabel="Current organization"
            value={enterprise.current.id}
            onChange={(organizationId) => {
                router.post(
                    "/admin/enterprise/switch",
                    { organization_id: organizationId },
                    { preserveScroll: true },
                );
            }}
            options={enterprise.available.map((organization) => ({
                value: organization.id,
                label: organization.name,
            }))}
            className={cx("min-w-0", className)}
        />
    );
}
