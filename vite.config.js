import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true, // Auto-refresh on Blade changes
        }),
    ],
    server: {
        host: "0.0.0.0", // Important for Docker!
        port: 5173,
        strictPort: true,
        hmr: {
            host: "localhost", // HMR host
        },
        watch: {
            usePolling: true, // Important for Docker volume mounts!
        },
    },
});
