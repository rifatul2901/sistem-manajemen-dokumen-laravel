<?php

namespace App\Services;

use App\Models\DriveToken;
use Google\Client;
use Google\Service\Sheets;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Auth;

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
        $client->setHttpClient(new HttpClient([
            'force_ip_resolve' => 'v4',
        ]));
        $client->setApplicationName('Sistem Manajemen Dokumen');
        $client->setScopes([Sheets::SPREADSHEETS_READONLY]);
        $client->setAuthConfig($clientSecretPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if ($user) {
            $this->driveToken = DriveToken::where('user_id', $user->id)->first();
        }

        if ($this->driveToken) {
            $client->setAccessToken(GoogleTokenService::accessTokenArray($this->driveToken));
        }

        GoogleTokenService::ensureFresh($client, $this->driveToken);

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

    public function readRange(string $spreadsheetId, string $sheetName, string $range = 'A:ZZ'): array
    {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $sheetName . '!' . $range);
        return $response->getValues() ?? [];
    }

    public function getSheetTotalColumns(string $spreadsheetId, string $sheetName): int
    {
        $spreadsheet = $this->service->spreadsheets->get($spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getGridProperties()->getColumnCount();
            }
        }
        return 26;
    }
}
