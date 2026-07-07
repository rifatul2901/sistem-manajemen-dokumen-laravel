<?php

namespace App\Services;

use App\Models\DriveToken;
use Google\Client;
use Google\Service\Sheets;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected Sheets $service;
    protected ?DriveToken $driveToken = null;

    public function __construct()
    {
        $user = Auth::user();
        $clientSecretPath = storage_path('app/oauth_client_secret.json');

        if (!file_exists($clientSecretPath)) {
            throw new \RuntimeException('OAuth client belum dikonfigurasi.');
        }

        $client = new Client();
        $client->setApplicationName('Sistem Manajemen Dokumen');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($clientSecretPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if ($user) {
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
                throw new \RuntimeException('Token Google tidak valid. Silakan hubungkan ulang akun Google Anda.');
            }
        }

        $this->service = new Sheets($client);
    }

    public function extractSpreadsheetId(string $url): ?string
    {
        if (preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9_-]+)/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $url)) {
            return $url;
        }
        return null;
    }

    public function getSheetNames(string $spreadsheetId): array
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        $sheets = $spreadsheet->getSheets();
        $names = [];
        foreach ($sheets as $sheet) {
            $names[] = $sheet->getProperties()->getTitle();
        }
        return $names;
    }

    public function readSheet(string $spreadsheetId, string $range = 'A:ZZ'): array
    {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
        return $response->getValues() ?? [];
    }

    public function readRange(string $spreadsheetId, string $sheetName, string $range = 'A:ZZ'): array
    {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName . '!' . $range);
        return $response->getValues() ?? [];
    }
}
