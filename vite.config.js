import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({ template: { transformAssetUrls: { base: null, includeAbsolute: false } } }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    server: {
        host: process.env.VITE_HOST ?? 'localhost',
        port: 5173,
        strictPort: true,
        // In Docker the server listens on 0.0.0.0 but the browser reaches it on localhost.
        hmr: { host: 'localhost' },
        watch: { ignored: ['**/storage/framework/views/**', '**/vendor/**'] },
    },
});
