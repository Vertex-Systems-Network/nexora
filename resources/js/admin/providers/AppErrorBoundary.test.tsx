import { fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { AppErrorBoundary } from "./AppErrorBoundary";

vi.mock("@admin/components/LoadingStates", () => ({
    ErrorState: ({ description }: { description: string }) => <p>{description}</p>,
}));

function Broken() {
    throw new Error("View crashed");
}

afterEach(() => vi.restoreAllMocks());

describe("AppErrorBoundary", () => {
    it("recovers on the next Inertia navigation instead of staying on the crashed view", () => {
        vi.spyOn(console, "error").mockImplementation(() => undefined);
        const view = render(<AppErrorBoundary><Broken /></AppErrorBoundary>);
        expect(screen.getByText("The admin interface could not render this view.")).toBeInTheDocument();

        view.rerender(<AppErrorBoundary><div>Recovered Admin view</div></AppErrorBoundary>);
        expect(screen.queryByText("Recovered Admin view")).not.toBeInTheDocument();

        fireEvent(document, new Event("inertia:navigate"));
        expect(screen.getByText("Recovered Admin view")).toBeInTheDocument();
    });
});
