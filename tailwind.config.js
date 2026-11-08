/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/views/components/**/*.blade.php',
        './resources/views/pages/transparency/**/*.blade.php',
        './resources/views/livewire/**/*.blade.php',
        './resources/js/**/*.{js,ts,jsx,tsx}',
        './resources/css/**/*.css',
    ],
    theme: {
        extend: {
            colors: {
                navy: {
                    900: '#0b1f3a',
                    800: '#142D4F',
                    700: '#1B3A61',
                    600: '#2c5282',
                    50: '#f1f5f9',
                    100: '#e2e8f0',
                    200: '#cbd5e1',
                },
                gold: {
                    500: '#8a6a12',
                    400: '#c9a84c',
                    300: '#e2c06a',
                },
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
            },
        },
    },
    plugins: [],
}
