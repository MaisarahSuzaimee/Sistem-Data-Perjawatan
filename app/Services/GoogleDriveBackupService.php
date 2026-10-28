<?php

namespace App\Services;

use App\Models\GoogleDriveConnection;
use Google\Client as GoogleClient;
use Google\Http\MediaFileUpload;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception as GoogleServiceException;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GoogleDriveBackupService
{
    /**
     * Upload a local compressed backup to Google Drive.
     */
    public function upload(string $relativePath): array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($relativePath)) {
            throw new RuntimeException(
                'The local backup file does not exist.'
            );
        }

        $absolutePath = $disk->path($relativePath);

        $client = $this->authenticatedClient();
        $drive = new Drive($client);

        $folderId = config('services.google_drive.backup_folder_id');

        $metadata = new DriveFile([
            'name' => basename($absolutePath),
        ]);

        if (filled($folderId)) {
            $metadata->setParents([$folderId]);
        }

        $chunkSize = 1024 * 1024; // 1 MB chunks

        $client->setDefer(true);

        $handle = null;

        try {
            $request = $drive->files->create(
                $metadata,
                [
                    'uploadType' => 'resumable',
                    'fields' => 'id,name,mimeType,size,webViewLink',
                    'supportsAllDrives' => true,
                ]
            );

            $media = new MediaFileUpload(
                $client,
                $request,
                'application/gzip',
                null,
                true,
                $chunkSize
            );

            $media->setFileSize(filesize($absolutePath));

            $handle = fopen($absolutePath, 'rb');

            if ($handle === false) {
                throw new RuntimeException(
                    'Could not open the local backup file.'
                );
            }

            $result = false;

            while (! $result && ! feof($handle)) {
                $chunk = fread($handle, $chunkSize);

                if ($chunk === false) {
                    throw new RuntimeException(
                        'Could not read the backup file.'
                    );
                }

                if ($chunk === '' && feof($handle)) {
                    break;
                }

                $result = $media->nextChunk($chunk);
            }

            $fileId = is_object($result) ? ($result->id ?? $result->getId()) : null;

            if (! $result || empty($fileId)) {
                throw new RuntimeException(
                    'Google Drive did not confirm the backup upload.'
                );
            }

            return [
                'id' => $fileId,
                'name' => $result->name ?? $result->getName(),
                'mime_type' => $result->mimeType ?? $result->getMimeType(),
                'size' => $result->size ?? $result->getSize(),
                'web_view_link' => $result->webViewLink ?? $result->getWebViewLink(),
            ];
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            $client->setDefer(false);
        }
    }

    /**
     * Permanently delete a backup file from Google Drive.
     */
    public function delete(string $fileId): void
    {
        if ($fileId === '') {
            return;
        }

        try {
            $this->drive()->files->delete($fileId, [
                'supportsAllDrives' => true,
            ]);
        } catch (GoogleServiceException $exception) {
            if ((int) $exception->getCode() === 404) {
                return;
            }

            throw $exception;
        }
    }

    private function drive(): Drive
    {
        return new Drive($this->authenticatedClient());
    }

    private function authenticatedClient(): GoogleClient
    {
        $connection = GoogleDriveConnection::query()->latest('id')->first();

        if (! $connection) {
            throw new RuntimeException(
                'Google Drive has not been connected.'
            );
        }

        $token = $connection->token;

        if (
            ! is_array($token) ||
            empty($token['refresh_token'])
        ) {
            throw new RuntimeException(
                'Google Drive refresh token is missing. Reconnect Google Drive.'
            );
        }

        $client = new GoogleClient;

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
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken(
                $token['refresh_token']
            );

            if (
                isset($newToken['error']) ||
                empty($newToken['access_token'])
            ) {
                throw new RuntimeException(
                    'Could not refresh the Google Drive access token.'
                );
            }

            $newToken['refresh_token'] =
                $newToken['refresh_token']
                ?? $token['refresh_token'];

            $token = array_merge($token, $newToken);

            $connection->token = $token;
            $connection->save();

            $client->setAccessToken($token);
        }

        return $client;
    }
}
