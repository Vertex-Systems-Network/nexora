import type { ReactNode } from "react";
import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import Health from "./Health";

vi.mock("@inertiajs/react", () => ({ Head: () => null }));
vi.mock("@admin/layout/AdminLayout", () => ({ AdminLayout: ({ children }: { children: ReactNode }) => <div>{children}</div> }));
vi.mock("@admin/components/PageHeader", () => ({ PageHeader: ({ title, actions }: { title: string; actions?: ReactNode }) => <div><h1>{title}</h1>{actions}</div> }));
vi.mock("@nexora/admin-ui", () => ({
    Card: ({ children }: { children: ReactNode }) => <section>{children}</section>,
    Badge: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

describe("System Health page", () => {
    it("renders nested process-plane data as structured text, not as a React child", () => {
        render(<Health
            checks={[{ name: "Database", status: "healthy", message: "Operational", durationMs: 0.4 }]}
            runtime={{
                php: "8.4.24", laravel: "13.33.0", environment: "local", database: "sqlite",
                processPlane: {
                    status: "pass",
                    required: { web: 2, queue: 2, scheduler: 1 },
                    live: { counts: { web: 1, queue: 0, scheduler: 0 } },
                    checks: { process_policy_enabled: true },
                },
            }}
        />);

        expect(screen.getByText("System health")).toBeInTheDocument();
        expect(screen.getByText("8.4.24")).toBeInTheDocument();
        expect(screen.getByText("sqlite")).toBeInTheDocument();
        expect(screen.getByText("Policy passing")).toBeInTheDocument();
        expect(screen.getByText("1 observed / 2 required")).toBeInTheDocument();
    });

    it("flags a failed process policy even when individual probes are healthy", () => {
        render(<Health
            checks={[{ name: "Database", status: "healthy", message: "Operational", durationMs: 1 }]}
            runtime={{
                php: "8.4.24", laravel: "13.33.0", environment: "local", database: "sqlite",
                processPlane: {
                    status: "fail",
                    required: { web: 2, queue: 2, scheduler: 1 },
                    live: { counts: { web: 0, queue: 0, scheduler: 0 } },
                    checks: { process_policy_enabled: false },
                },
            }}
        />);

        expect(screen.getByText("Attention required")).toBeInTheDocument();
        expect(screen.getByText("Policy needs attention")).toBeInTheDocument();
    });

});
