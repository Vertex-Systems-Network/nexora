import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { UntitledButton } from "./button";

describe("Nexora button boundary", () => {
    it("disables the control while loading", () => {
        render(<UntitledButton loading>Save</UntitledButton>);
        expect(screen.getByRole("button", { name: "Save" })).toBeDisabled();
        expect(screen.getByRole("button", { name: "Save" })).toHaveAttribute("aria-busy", "true");
    });

    it("renders primary actions with the shared brand gradient fill", () => {
        render(<UntitledButton>Create</UntitledButton>);
        expect(screen.getByRole("button", { name: "Create" })).toHaveClass("nx-brand-fill");
        render(<UntitledButton variant="secondary">Cancel</UntitledButton>);
        expect(screen.getByRole("button", { name: "Cancel" })).not.toHaveClass("nx-brand-fill");
    });
});
