import "../css/app.css";
import { createInertiaApp } from "@inertiajs/react";
import { AppErrorBoundary } from "@admin/providers/AppErrorBoundary";
import { RouteProgress } from "@admin/providers/RouteProgress";
import { ThemeProvider } from "@admin/providers/ThemeProvider";
import { ToastProvider } from "@admin/providers/ToastProvider";
import type { SharedPageProps } from "@admin/types/page";
import { installDeploymentFetchFence } from "@admin/runtime/deployment-fence";

createInertiaApp({
    title: (title) => (title ? `${title} · Nexora` : "Nexora"),
    pages: {
        path: "./admin/pages",
        extension: ".tsx",
        lazy: true,
    },
    strictMode: true,
    withApp(app, { page }) {
        const props = page.props as unknown as SharedPageProps;
        installDeploymentFetchFence(props.app.deployment?.generation);

        return (
            <AppErrorBoundary>
                <ThemeProvider appearance={props.appearance}>
                    <ToastProvider flash={props.flash}>
                        <RouteProgress />
                        {app}
                    </ToastProvider>
                </ThemeProvider>
            </AppErrorBoundary>
        );
    },
});
