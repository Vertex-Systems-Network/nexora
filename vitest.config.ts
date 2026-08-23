import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            "@admin": fileURLToPath(new URL("./resources/js/admin", import.meta.url)),
            "@nexora/admin-ui": fileURLToPath(new URL("./resources/js/admin/ui/index.ts", import.meta.url)),
        },
    },
    test: {
        environment: "jsdom",
        setupFiles: ["resources/js/admin/test/setup.ts"],
        include: ["resources/js/**/*.test.{ts,tsx}"],
    },
});
