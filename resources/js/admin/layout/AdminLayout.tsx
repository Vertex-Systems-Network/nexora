import { useEffect, useMemo, useState, type CSSProperties, type ReactNode } from "react";
import { router, usePage } from "@inertiajs/react";
import { Icon } from "@admin/components/Icon";
import { CommandPalette } from "@admin/components/CommandPalette";
import { LanguageSwitcher } from "@admin/components/LanguageSwitcher";
import { ThemeSwitcher } from "@admin/components/ThemeSwitcher";
import { OrganizationSwitcher } from "@admin/components/OrganizationSwitcher";
import { Button, ButtonLink, IconButton, IconLink, NavLink, OverlayDismiss } from "@nexora/admin-ui";
import type { SharedPageProps } from "@admin/types/page";
import { cx } from "@admin/utils/cx";

const SIDEBAR_KEY = "nexora.admin.sidebar.collapsed";

function BrandMark({ src }: { src: string }) {
    return <img src={src} alt="" className="h-9 w-9 rounded-xl object-contain shadow-sm" />;
}

function initials(name?: string | null) {
    return name?.split(" ").map((part) => part[0]).join("").slice(0, 2).toUpperCase() ?? "NX";
}

export function AdminLayout({ children }: { children: ReactNode }) {
    const { props, url } = usePage<SharedPageProps>();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(
        () => typeof window !== "undefined" && window.localStorage.getItem(SIDEBAR_KEY) === "1",
    );
    const user = props.auth.user;
    const rtl = props.localization.direction === "rtl";
    const permissions = user?.permissions ?? [];
    const canSearch = permissions.includes("search.use");
    const canNotify = permissions.includes("notifications.view");
    const canProfile = permissions.includes("profile.manage");

    useEffect(() => {
        document.documentElement.lang = props.localization.locale;
        document.documentElement.dir = props.localization.direction;
    }, [props.localization.locale, props.localization.direction]);

    const toggleCollapsed = () => {
        setCollapsed((current) => {
            const next = !current;
            window.localStorage.setItem(SIDEBAR_KEY, next ? "1" : "0");
            return next;
        });
    };

    const sidebarStyle = useMemo(
        () => ({ "--nx-sidebar-current-width": collapsed ? "4.75rem" : "17rem" }) as CSSProperties,
        [collapsed],
    );
    const tooltipPlacement = rtl ? "left" : "right";

    const renderNavigation = (compact: boolean, mobile = false) => (
        <div className="flex h-full flex-col">
            <div
                className={cx(
                    "relative flex h-[var(--nx-header-height)] items-center border-b border-[var(--nx-border)]",
                    compact ? "justify-center px-2" : "gap-3 px-5",
                )}
            >
                <BrandMark src={props.app.logoUrl} />
                {!compact && (
                    <div className="min-w-0">
                        <div className="truncate text-sm font-semibold tracking-[-0.01em] text-[var(--nx-text)]">
                            {props.app.name}
                        </div>
                        <div className="text-[11px] font-medium uppercase tracking-[0.12em] text-[var(--nx-text-muted)]">
                            {props.localization.messages.controlCenter}
                        </div>
                    </div>
                )}
                {!mobile && (
                    <div className={cx("absolute top-1/2 -translate-y-1/2", "-end-4")}>
                        <IconButton
                            label={compact ? "Expand sidebar" : "Collapse sidebar"}
                            onClick={toggleCollapsed}
                            className="border border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-sm"
                            tooltipPlacement={tooltipPlacement}
                        >
                            <Icon name={compact ? "panel-open" : "panel-close"} className="h-4 w-4" />
                        </IconButton>
                    </div>
                )}
            </div>

            {mobile && (
                <div className="grid gap-3 border-b border-[var(--nx-border)] p-3">
                    <OrganizationSwitcher enterprise={props.enterprise} label />
                    <LanguageSwitcher localization={props.localization} label />
                </div>
            )}

            <nav
                className={cx("nx-scrollbar flex-1 space-y-1 overflow-y-auto", compact ? "p-2" : "p-3")}
                aria-label="Admin navigation"
            >
                {props.adminNavigation.map((item) => {
                    const active = item.href === "/admin" ? url === "/admin" : url.startsWith(item.href);
                    const unread = item.id === "notifications" && props.notifications.unread > 0;
                    return (
                        <NavLink
                            key={item.id}
                            href={item.href}
                            label={item.label}
                            active={active}
                            collapsed={compact}
                            tooltipPlacement={tooltipPlacement}
                            onClick={() => setMobileOpen(false)}
                            icon={(
                                <Icon
                                    name={item.icon}
                                    className={cx("h-[18px] w-[18px]", active && "text-[var(--nx-brand-600)]")}
                                />
                            )}
                            badge={unread ? (
                                compact ? (
                                    <span className="h-2 w-2 rounded-full bg-[var(--nx-brand-600)] ring-2 ring-[var(--nx-surface)]" />
                                ) : (
                                    <span className="rounded-full bg-[var(--nx-brand-600)] px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {props.notifications.unread > 99 ? "99+" : props.notifications.unread}
                                    </span>
                                )
                            ) : undefined}
                        />
                    );
                })}
            </nav>

            <div
                className={cx(
                    "border-t border-[var(--nx-border)]",
                    compact ? "grid justify-items-center gap-1 p-2" : "p-3",
                )}
            >
                {compact ? (
                    <>
                        {canProfile ? (
                            <IconLink
                                href="/admin/profile"
                                label={`${user?.name ?? "Admin"} · ${user?.email ?? "Open profile"}`}
                                tooltipPlacement={tooltipPlacement}
                                className="h-10 w-10 rounded-full bg-[var(--nx-surface-subtle)] ring-1 ring-[var(--nx-border)]"
                            >
                                <span className="text-xs font-bold text-[var(--nx-text-secondary)]">
                                    {initials(user?.name)}
                                </span>
                            </IconLink>
                        ) : (
                            <span className="grid h-10 w-10 place-items-center rounded-full bg-[var(--nx-surface-subtle)] text-xs font-bold text-[var(--nx-text-secondary)] ring-1 ring-[var(--nx-border)]">
                                {initials(user?.name)}
                            </span>
                        )}
                        <IconButton
                            label={props.localization.messages.signOut}
                            tooltipPlacement={tooltipPlacement}
                            onClick={() => router.post("/logout")}
                        >
                            <Icon name="logout" className="h-4 w-4" />
                        </IconButton>
                    </>
                ) : (
                    <>
                        {canProfile ? (
                            <ButtonLink
                                href="/admin/profile"
                                variant="ghost"
                                className="mb-2 h-auto w-full justify-start px-3 py-2"
                                leadingIcon={(
                                    <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--nx-surface-subtle)] text-xs font-bold text-[var(--nx-text-secondary)] ring-1 ring-[var(--nx-border)]">
                                        {initials(user?.name)}
                                    </span>
                                )}
                            >
                                <span className="min-w-0 text-start">
                                    <span className="block truncate text-sm font-semibold text-[var(--nx-text)]">
                                        {user?.name ?? "Admin"}
                                    </span>
                                    <span className="block truncate text-xs font-normal text-[var(--nx-text-muted)]">
                                        {user?.email}
                                    </span>
                                </span>
                            </ButtonLink>
                        ) : (
                            <div className="mb-2 flex items-center gap-3 rounded-xl px-3 py-2">
                                <span className="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[var(--nx-surface-subtle)] text-xs font-bold text-[var(--nx-text-secondary)] ring-1 ring-[var(--nx-border)]">
                                    {initials(user?.name)}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-sm font-semibold text-[var(--nx-text)]">
                                        {user?.name ?? "Admin"}
                                    </div>
                                    <div className="truncate text-xs text-[var(--nx-text-muted)]">{user?.email}</div>
                                </div>
                            </div>
                        )}
                        <Button
                            className="w-full justify-start"
                            variant="ghost"
                            size="sm"
                            leadingIcon={<Icon name="logout" className="h-4 w-4" />}
                            onClick={() => router.post("/logout")}
                        >
                            {props.localization.messages.signOut}
                        </Button>
                    </>
                )}
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-[var(--nx-bg)]" style={sidebarStyle}>
            <a href="#nexora-main-content" className="nx-skip-link">Skip to main content</a>
            {canSearch && <CommandPalette />}
            <aside
                className={cx(
                    "fixed inset-y-0 z-40 hidden w-[var(--nx-sidebar-current-width)] border-[var(--nx-border)] bg-[var(--nx-surface)] transition-[width] duration-200 lg:block",
                    "start-0 border-e",
                )}
            >
                {renderNavigation(collapsed)}
            </aside>

            {mobileOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <OverlayDismiss aria-label="Close navigation" onClick={() => setMobileOpen(false)} />
                    <aside
                        className={cx(
                            "relative h-full w-[min(88vw,19rem)] border-[var(--nx-border)] bg-[var(--nx-surface)] shadow-md",
                            rtl ? "ms-auto border-l" : "border-r",
                        )}
                    >
                        {renderNavigation(false, true)}
                    </aside>
                </div>
            )}

            <div className={cx("transition-[padding] duration-200", "lg:ps-[var(--nx-sidebar-current-width)]")}>
                <header className="sticky top-0 z-30 flex h-[var(--nx-header-height)] items-center gap-2 border-b border-[var(--nx-border)] bg-[color-mix(in_srgb,var(--nx-surface)_92%,transparent)] px-4 backdrop-blur-xl sm:px-6 lg:px-8">
                    <IconButton className="lg:hidden" label="Open navigation" onClick={() => setMobileOpen(true)}>
                        <Icon name="menu" className="h-5 w-5" />
                    </IconButton>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-[var(--nx-text)]">
                            {props.app.name} · {props.localization.messages.controlCenter}
                        </p>
                        <p className="truncate text-xs text-[var(--nx-text-muted)]">
                            {props.enterprise.current
                                ? `${props.enterprise.current.name} · ${props.enterprise.memberRole ?? "platform"}`
                                : props.app.environment}
                        </p>
                    </div>
                    {canSearch && (
                        <Button
                            onClick={() => window.dispatchEvent(new KeyboardEvent("keydown", { key: "k", ctrlKey: true }))}
                            variant="secondary"
                            size="sm"
                            className="hidden min-w-44 justify-start text-[var(--nx-text-muted)] sm:inline-flex"
                            leadingIcon={<Icon name="search" className="h-4 w-4" />}
                        >
                            <span className="flex w-full items-center gap-2">
                                <span>{props.localization.messages.search}</span>
                                <kbd className="ms-auto rounded border border-[var(--nx-border)] px-1.5 py-0.5 text-[10px]">
                                    Ctrl K
                                </kbd>
                            </span>
                        </Button>
                    )}
                    <OrganizationSwitcher enterprise={props.enterprise} className="hidden min-w-48 lg:grid" />
                    <ThemeSwitcher />
                    <LanguageSwitcher localization={props.localization} className="hidden w-44 sm:grid" />
                    {canNotify && (
                        <IconLink href="/admin/notifications" label="Notifications" className="relative h-10 w-10">
                            <Icon name="bell" className="h-5 w-5" />
                            {props.notifications.unread > 0 && (
                                <span className="absolute end-2 top-2 h-2 w-2 rounded-full bg-[var(--nx-brand-600)] ring-2 ring-[var(--nx-surface)]" />
                            )}
                        </IconLink>
                    )}
                </header>
                {props.enterprise.impersonation && (
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-amber-300/50 bg-amber-50 px-4 py-2 text-sm text-amber-950 dark:border-amber-700/50 dark:bg-amber-950/35 dark:text-amber-100 sm:px-6 lg:px-8">
                        <span>
                            <strong>Impersonation active:</strong> signed in as {props.enterprise.impersonation.target_name}. Original administrator: {props.enterprise.impersonation.actor_name}.
                        </span>
                        <Button
                            size="sm"
                            variant="secondary"
                            onClick={() => router.post("/admin/enterprise/impersonation/stop")}
                        >
                            Stop impersonation
                        </Button>
                    </div>
                )}
                <main
                    id="nexora-main-content"
                    tabIndex={-1}
                    className="mx-auto w-full max-w-[var(--nx-page-max-width)] p-4 sm:p-6 lg:p-8"
                >
                    <div className="grid gap-[var(--nx-content-gap)]">{children}</div>
                </main>
            </div>
        </div>
    );
}
