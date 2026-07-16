<?php

namespace App\Services;

use App\Models\DriveToken;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Auth;

class GoogleDriveService
{
    protected Drive $service;
    protected ?DriveToken $driveToken = null;

    public function __construct(?DriveToken $driveToken = null)
    {
        $user = Auth::user();
        $clientSecretPath = storage_path('app/oauth_client_secret.json');

        if (!file_exists($clientSecretPath)) {
            throw new \RuntimeException('OAuth client belum dikonfigurasi. Upload file JSON dari Google Cloud Console.');
        }

        $client = new Client();
        $client->setApplicationName('Sistem Manajemen Dokumen');
        $client->setScopes([Drive::DRIVE]);
        $client->setAuthConfig($clientSecretPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if ($driveToken) {
            $this->driveToken = $driveToken;
        } elseif ($user) {
            $this->driveToken = DriveToken::where('user_id', $user->id)->first();
        }

        if ($this->driveToken) {
            $accessToken = [
                'access_token'  => $this->driveToken->access_token,
                'refresh_token' => $this->driveToken->refresh_token ?? '',
                'token_type'    => $this->driveToken->token_type ?? 'Bearer',
                'expires_in'    => $this->driveToken->expires_in ?? 3600,
                'created'       => $this->driveToken->created_at?->timestamp ?? time(),
            ];

            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                if ($this->driveToken) {
                    $this->driveToken->update([
                        'access_token' => $newToken['access_token'],
                        'expires_in'   => $newToken['expires_in'] ?? null,
                        'expires_at'   => isset($newToken['expires_in'])
                            ? now()->addSeconds($newToken['expires_in'])
                            : null,
                    ]);
                }
            } else {
                throw new \RuntimeException('Token Google Drive tidak valid. Silakan hubungkan ulang akun Google Anda.');
            }
        }

        $this->service = new Drive($client);
    }

    public function getService(): Drive
    {
        return $this->service;
    }

    public function uploadFile($fileContent, string $filename, string $folderId, ?string $mimeType = null): string
    {
        $mimeType = $mimeType ?: $this->detectMimeType($filename);
        $file = new DriveFile();
        $file->setName($filename);
        $file->setParents([$folderId]);

        $upload = $this->service->files->create($file, [
            'data'       => $fileContent,
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id',
        ]);

        return $upload->getId();
    }

    public function updateFile(string $fileId, $fileContent, string $filename, ?string $mimeType = null): string
    {
        $mimeType = $mimeType ?: $this->detectMimeType($filename);
        $file = new DriveFile();
        $file->setName($filename);

        $updated = $this->service->files->update($fileId, $file, [
            'data'       => $fileContent,
            'mimeType'   => $mimeType,
            'uploadType' => 'multipart',
            'fields'     => 'id',
        ]);

        return $updated->getId();
    }

    public function listSubfolders(string $folderId): array
    {
        $results = $this->service->files->listFiles([
            'q'        => "'{$folderId}' in parents and trashed=false and mimeType='application/vnd.google-apps.folder'",
            'fields'   => 'files(id, name)',
            'orderBy'  => 'name',
            'pageSize' => 200,
        ]);

        return $results->getFiles() ?? [];
    }

    public function listFilesRecursive(string $folderId, string $label = '(folder utama)'): array
    {
        $results = [];
        $this->scanRecursive($folderId, $label, $results);
        return $results;
    }

    protected function scanRecursive(string $folderId, string $label, array &$results): void
    {
        $pageToken = null;
        do {
            $opt = [
                'q'        => "'{$folderId}' in parents and trashed=false",
                'fields'   => 'nextPageToken, files(id, name, mimeType, createdTime, owners)',
                'orderBy'  => 'createdTime desc',
                'pageSize' => 200,
            ];
            if ($pageToken) {
                $opt['pageToken'] = $pageToken;
            }

            $items = $this->service->files->listFiles($opt);

            foreach ($items->getFiles() as $item) {
                if ($item->getMimeType() === 'application/vnd.google-apps.folder') {
                    $this->scanRecursive($item->getId(), $item->getName(), $results);
                } else {
                    $results[] = [
                        'id'          => $item->getId(),
                        'name'        => $item->getName(),
                        'subfolder'   => $label,
                        'createdTime' => $item->getCreatedTime(),
                        'owners'      => $item->getOwners(),
                    ];
                }
            }
            $pageToken = $items->getNextPageToken();
        } while ($pageToken);
    }

    public function listFilesInRoot(string $folderId, string $label = '(folder utama)'): array
    {
        $results = [];
        $items = $this->service->files->listFiles([
            'q'        => "'{$folderId}' in parents and trashed=false and mimeType!='application/vnd.google-apps.folder'",
            'fields'   => 'files(id, name, createdTime, owners)',
            'orderBy'  => 'createdTime desc',
            'pageSize' => 200,
        ]);

        foreach ($items->getFiles() as $item) {
            $results[] = [
                'id'          => $item->getId(),
                'name'        => $item->getName(),
                'subfolder'   => $label,
                'createdTime' => $item->getCreatedTime(),
                'owners'      => $item->getOwners(),
            ];
        }

        return $results;
    }

    public function checkFileExists(string $folderId, string $filename): array
    {
        $quotedName = str_replace("'", "\\'", $filename);
        $items = $this->service->files->listFiles([
            'q'        => "'{$folderId}' in parents and trashed=false and name='{$quotedName}'",
            'fields'   => 'files(id, name)',
            'pageSize' => 10,
        ]);

        return $items->getFiles() ?? [];
    }

    public function downloadFile(string $fileId, string $savePath): string
    {
        $content = $this->service->files->get($fileId, ['alt' => 'media']);
        file_put_contents($savePath, $content->getBody());
        return $savePath;
    }

    protected function detectMimeType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf'  => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc'  => 'application/msword',
            default => 'application/octet-stream',
        };
    }

    public static function extractFolderId(string $text): string
    {
        $text = trim($text);
        if (preg_match('/\/folders\/([a-zA-Z0-9_-]+)/', $text, $m)) {
            return $m[1];
        }
        return $text;
    }
}
