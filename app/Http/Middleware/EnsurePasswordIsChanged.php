<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password) {
            $panel = Filament::getCurrentPanel() ?? Filament::getPanel('app');
            $panelId = $panel->getId();

            if (! $request->routeIs([
                "filament.{$panelId}.auth.profile",
                "filament.{$panelId}.auth.logout",
            ])) {
                return redirect()->to($panel->getProfileUrl() ?? "/{$panel->getPath()}/profile");
            }
        }

        return $next($request);
    }
}
