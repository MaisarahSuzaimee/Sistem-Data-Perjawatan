import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const host = new URL(env.APP_URL || 'https://sistem-data-perjawatan.test').host;

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/css/filament/app/theme.css',
                    'resources/js/app.js',
                    'resources/js/dashboard-charts.js',
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            host,
            hmr: {
                host,
            },
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
