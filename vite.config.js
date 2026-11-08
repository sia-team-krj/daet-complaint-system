import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        tailwindcss({
            content: [
                './resources/views/**/*.blade.php',
                './resources/views/components/**/*.blade.php',
                './resources/views/pages/transparency/**/*.blade.php',
                './resources/views/livewire/**/*.blade.php',
                './resources/js/**/*.{js,ts,jsx,tsx}',
                './resources/css/**/*.css',
            ],
        }),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true, // Auto-refresh on Blade changes
        }),
    ],
    server: {
        host: "0.0.0.0", // Important for Docker!
        port: 5173,
        strictPort: false,
        hmr: {
            host: process.env.VITE_HMR_HOST || "localhost", // Set to the computer's LAN IP for phone testing
        },
        watch: {
            usePolling: true, // Important for Docker volume mounts!
        },
    },
});
