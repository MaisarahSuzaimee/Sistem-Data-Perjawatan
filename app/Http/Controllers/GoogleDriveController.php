<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveConnection;
use Filament\Notifications\Notification;
use Google\Client as GoogleClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class GoogleDriveController extends Controller
{
    private function client(): GoogleClient
    {
        $client = new GoogleClient();

        $client->setClientId(
            config('services.google_drive.client_id')
        );

        $client->setClientSecret(
            config('services.google_drive.client_secret')
        );

        $client->setRedirectUri(
            config('services.google_drive.redirect_uri')
        );

        $client->addScope(
            'https://www.googleapis.com/auth/drive.file'
        );

        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);

        return $client;
    }

    public function connect(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()?->isSuperAdmin(),
            403
        );

        $state = bin2hex(random_bytes(32));

        $request->session()->put(
            'google_drive_oauth_state',
            $state
        );

        $client = $this->client();
        $client->setState($state);

        return redirect()->away(
            $client->createAuthUrl()
        );
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(
            $request->user()?->isSuperAdmin(),
            403
        );

        if ($request->filled('error')) {
            return redirect('/app/database-backup')
                ->with('error', 'Google Drive authorization was cancelled or denied.');
        }

        $expectedState = $request->session()->pull(
            'google_drive_oauth_state'
        );

        if (
            !is_string($expectedState) ||
            !hash_equals(
                $expectedState,
                (string) $request->query('state', '')
            )
        ) {
            abort(419, 'Invalid Google OAuth state. Please try connecting again.');
        }

        $code = $request->query('code');

        abort_unless(
            is_string($code) && $code !== '',
            400,
            'Google did not return an authorization code.'
        );

        try {
            $client = $this->client();

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                throw new \RuntimeException(
                    'Google authorization failed: ' .
                    ($token['error_description'] ?? $token['error'])
                );
            }

            $connection = GoogleDriveConnection::query()
                ->find(1);

            $existingToken = $connection?->token ?? [];

            // Google may omit a refresh token if one was previously granted.
            if (
                empty($token['refresh_token']) &&
                !empty($existingToken['refresh_token'])
            ) {
                $token['refresh_token'] =
                    $existingToken['refresh_token'];
            }

            if (empty($token['refresh_token'])) {
                throw new \RuntimeException(
                    'Google did not return a refresh token. Please try connecting again.'
                );
            }

            $connection ??= new GoogleDriveConnection();
            $connection->id = 1;
            $connection->token = $token;
            $connection->save();

            Notification::make()
                ->title('Google Drive connected successfully')
                ->success()
                ->send();

            return redirect('/app/database-backup');
        } catch (Throwable $exception) {
            report($exception);

            return redirect('/app/database-backup')
                ->with('error', 'Could not connect Google Drive. Check the application logs.');
        }
    }
}
