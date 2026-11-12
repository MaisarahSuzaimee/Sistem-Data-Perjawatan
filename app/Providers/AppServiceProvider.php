<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            $settings = Setting::allCached();

            if ($settings === []) {
                return;
            }

            if (filled($settings['turnstile_site_key'] ?? null)) {
                config(['services.turnstile.site_key' => $settings['turnstile_site_key']]);
            }

            if (filled($settings['turnstile_secret_key'] ?? null)) {
                config(['services.turnstile.secret_key' => $settings['turnstile_secret_key']]);
            }

            if (filled($settings['prestasi_v2_api_key'] ?? null)) {
                config(['services.prestasi_v2.api_key' => $settings['prestasi_v2_api_key']]);
            }

            if (filled($settings['prestasi_v2_base_url'] ?? null)) {
                config(['services.prestasi_v2.base_url' => $settings['prestasi_v2_base_url']]);
            }
        } catch (\Throwable $e) {
            // Fail silently during install/migrate when table not ready.
        }
    }
}
