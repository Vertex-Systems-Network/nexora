import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";

export default defineConfig({
    plugins: [react()],
    test: {
        environment: "jsdom",
        setupFiles: ["resources/js/admin/test/setup.ts"],
        include: ["resources/js/**/*.test.{ts,tsx}"],
    },
});
