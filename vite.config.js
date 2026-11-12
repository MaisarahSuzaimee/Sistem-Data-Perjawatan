import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

function resolveDevHost(appUrl) {
    if (!appUrl || typeof appUrl !== 'string') {
        return null;
    }

    try {
        return new URL(appUrl).host || null;
    } catch {
        return null;
    }
}

export default defineConfig(({ mode, command }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const host = command === 'serve'
        ? resolveDevHost(env.APP_URL) ?? 'sistem-data-perjawatan.test'
        : undefined;

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
            ...(host
                ? {
                    host,
                    hmr: { host },
                }
                : {}),
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
