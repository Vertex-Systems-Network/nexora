import { ButtonLink } from "@nexora/admin-ui";

const items = [
    ["Overview", "/admin/membership"],
    ["Plans", "/admin/membership/plans"],
    ["Members", "/admin/membership/members"],
    ["Access policies", "/admin/membership/access-policies"],
] as const;

type Props = {
    current: string;
};

export function MembershipNav({ current }: Props) {
    return (
        <div className="mb-5 flex flex-wrap gap-2">
            {items.map(([label, href]) => (
                <ButtonLink
                    key={href}
                    href={href}
                    size="sm"
                    variant={current === href ? "primary" : "secondary"}
                >
                    {label}
                </ButtonLink>
            ))}
        </div>
    );
}
