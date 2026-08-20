import { ButtonLink } from "@nexora/admin-ui";

const items = [
    ["Overview", "/admin/helpdesk"],
    ["Tickets", "/admin/helpdesk/tickets"],
    ["Settings", "/admin/helpdesk/settings"],
] as const;

type Props = {
    current: string;
};

export function HelpdeskNav({ current }: Props) {
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
