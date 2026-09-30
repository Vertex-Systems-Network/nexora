import { fireEvent, render, screen, within } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { UntitledInput } from "./input";
import { UntitledTextarea } from "./textarea";
import { UntitledIconButton } from "./icon-button";
import { Modal } from "./modal";

describe("Nexora accessibility primitives", () => {
    it("connects input errors to the control", () => {
        render(<UntitledInput label="Email" error="Email is required" />);
        const input = screen.getByRole("textbox", { name: "Email" });
        const error = screen.getByRole("alert");
        expect(input).toHaveAttribute("aria-invalid", "true");
        expect(input).toHaveAttribute("aria-describedby", error.id);
        expect(input).toHaveAttribute("aria-errormessage", error.id);
    });

    it("connects textarea hints to the control", () => {
        render(<UntitledTextarea label="Reason" hint="Explain the change" />);
        const control = screen.getByRole("textbox", { name: "Reason" });
        expect(control.getAttribute("aria-describedby")).toBeTruthy();
    });

    it("requires an accessible label for icon buttons", () => {
        render(<UntitledIconButton label="Delete record"><span aria-hidden="true">×</span></UntitledIconButton>);
        expect(screen.getByRole("button", { name: "Delete record" })).toBeInTheDocument();
    });

    it("exposes modal semantics and escape close behavior", () => {
        const close = vi.fn();
        render(<Modal open title="Delete record" description="This cannot be undone" onClose={close}><p>Body</p></Modal>);
        const dialog = screen.getByRole("dialog", { name: "Delete record" });
        expect(dialog).toHaveAttribute("aria-modal", "true");
        fireEvent.keyDown(window, { key: "Escape" });
        expect(close).toHaveBeenCalledTimes(1);
    });

    it("traps tab focus inside an open modal", () => {
        render(
            <Modal open title="Edit record" onClose={() => undefined}>
                <button type="button">First action</button>
                <button type="button">Second action</button>
            </Modal>,
        );

        const dialog = screen.getByRole("dialog", { name: "Edit record" });
        const dialogQueries = within(dialog);
        const first = dialogQueries.getByRole("button", { name: "First action" });
        const close = dialogQueries.getByRole("button", { name: "Close dialog" });

        for (const element of Array.from(dialog.querySelectorAll<HTMLElement>('button,[tabindex]'))) {
            Object.defineProperty(element, "offsetParent", { configurable: true, get: () => dialog });
        }

        close.focus();
        expect(close).toHaveFocus();
        fireEvent.keyDown(window, { key: "Tab" });
        expect(first).toHaveFocus();
    });
});
