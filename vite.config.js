import { defineConfig } from 'vite-plus';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    fmt: {
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        ignorePatterns: [
            '.agents/**',
            '.claude/**',
            '.junie/**',
            '.ai/**',
            'AGENTS.md',
            'CLAUDE.md',
            'README.md',
            'composer.json',
        ],
    },
});
