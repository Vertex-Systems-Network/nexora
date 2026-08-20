import { ButtonLink } from "@nexora/admin-ui";
const items=[
 ["Overview","/admin/crm"],["Organizations","/admin/crm/organizations"],["Contacts","/admin/crm/contacts"],["Leads","/admin/crm/leads"],
 ["Opportunities","/admin/crm/opportunities"],["Commerce links","/admin/crm/commerce-links"],["Settings","/admin/crm/settings"],
] as const;
export function CrmNav({current}:{current:string}){return <div className="mb-5 flex flex-wrap gap-2">{items.map(([label,href])=><ButtonLink key={href} href={href} size="sm" variant={current===href?"primary":"secondary"}>{label}</ButtonLink>)}</div>}
