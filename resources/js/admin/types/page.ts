export type DeploymentIdentity = { mode:string; platform_version:string; generation:string; source_tree_sha256:string|null; frontend_manifest_sha256:string|null; composer_lock_sha256:string|null; package_lock_sha256:string|null; runtime_policy_sha256:string|null; upgrade_policy_sha256:string|null };
export type AuthUser = {
    id: number; name: string; email: string; email_verified_at: string | null; status: string; timezone: string; locale: string; permissions: string[];
};
export type NavigationItem = { id:string; label:string; href:string; icon:string; order:number; permission?:string };
export type AppearanceSettings = { theme:"light"|"dark"|"system"; primary:string; density:"comfortable"|"compact"; radius:"small"|"medium"|"large" };
export type SupportedLocale = { code:string; name:string; native:string; country:string; flag:string; flagUrl?:string; direction:"ltr"|"rtl" };
export type LocalizationProps = { locale:string; direction:"ltr"|"rtl"; supported:SupportedLocale[]; messages:{language:string;controlCenter:string;search:string;signOut:string} };
export type EnterpriseSharedProps = { current:{id:string;name:string;slug:string;status:string;timezone:string;locale:string}|null; available:{id:string;name:string;slug:string}[]; memberRole:string|null; impersonation:{active:true;actor_id:number;actor_name:string;target_name:string}|null };
export type SharedPageProps = {
    [key:string]: unknown;
    app:{name:string;logoUrl:string;defaultTimezone:string;defaultLocale:string;environment:string;deployment?:DeploymentIdentity}; auth:{user:AuthUser|null}; adminNavigation:NavigationItem[]; appearance:AppearanceSettings;
    notifications:{unread:number}; enterprise:EnterpriseSharedProps; localization:LocalizationProps; flash:{success?:string|null;error?:string|null;warning?:string|null}; errors:Record<string,string>;
};
