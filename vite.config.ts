import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import inertia from "@inertiajs/vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.tsx"],
            refresh: true,
        }),
        react(),
        inertia(),
        tailwindcss(),
    ],
    build: {
        sourcemap: false,
        cssCodeSplit: true,
        reportCompressedSize: false,
        chunkSizeWarningLimit: 900,
    },
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            "@admin": fileURLToPath(new URL("./resources/js/admin", import.meta.url)),
            "@nexora/admin-ui": fileURLToPath(new URL("./resources/js/admin/ui/index.ts", import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
