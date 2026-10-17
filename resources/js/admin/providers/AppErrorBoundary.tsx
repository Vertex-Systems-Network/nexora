import { Component, type ErrorInfo, type ReactNode } from "react";
import { ErrorState } from "@admin/components/LoadingStates";

export class AppErrorBoundary extends Component<{ children: ReactNode }, { failed: boolean }> {
    state = { failed: false };

    static getDerivedStateFromError() { return { failed: true }; }

    componentDidMount() {
        // Inertia keeps the app root mounted across visits. Clear a failed view on the
        // next navigation so Back/Forward does not leave the entire admin unusable.
        document.addEventListener("inertia:navigate", this.resetAfterNavigation);
    }

    componentWillUnmount() {
        document.removeEventListener("inertia:navigate", this.resetAfterNavigation);
    }

    private resetAfterNavigation = () => {
        if (this.state.failed) this.setState({ failed: false });
    };

    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error("Nexora render failure", error, info);
    }

    render() {
        if (this.state.failed) {
            return <div className="mx-auto max-w-3xl p-8"><ErrorState description="The admin interface could not render this view." onRetry={() => window.location.reload()} /></div>;
        }
        return this.props.children;
    }
}
