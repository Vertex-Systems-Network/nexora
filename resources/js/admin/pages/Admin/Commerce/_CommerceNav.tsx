import { ButtonLink } from "@nexora/admin-ui";

const items=[
  ["Overview","/admin/commerce"],["Products","/admin/commerce/products"],["Customers","/admin/commerce/customers"],
  ["Orders","/admin/commerce/orders"],["Billing","/admin/commerce/billing"],["Settings","/admin/commerce/settings"],
] as const;
export function CommerceNav({current}:{current:string}){
 return <div className="mb-5 flex flex-wrap gap-2">{items.map(([label,href])=><ButtonLink key={href} href={href} size="sm" variant={current===href?"primary":"secondary"}>{label}</ButtonLink>)}</div>;
}
