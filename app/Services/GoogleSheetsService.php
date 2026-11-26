<?php

namespace App\Services;

use App\Models\DriveToken;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
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

    /**
     * Petakan error Google Sheets API ke pesan & status HTTP yang informatif.
     *
     * @return array{message: string, status: int}
     */
    public static function fetchErrorResponse(\Throwable $e): array
    {
        $raw = $e->getMessage();
        $message = strtolower($raw);

        $code = (int) $e->getCode();
        $reason = '';

        if ($e instanceof GoogleServiceException) {
            foreach ($e->getErrors() ?? [] as $err) {
                $candidate = strtolower((string) ($err['reason'] ?? ''));
                if ($candidate !== '') {
                    $reason = $candidate;
                    break;
                }
            }
        }

        $isOfficeFile = stripos($raw, 'Office file') !== false
            || (
                stripos($raw, 'Office') !== false
                && (
                    $reason === 'failedprecondition'
                    || stripos($raw, 'FAILED_PRECONDITION') !== false
                    || stripos($raw, 'failedPrecondition') !== false
                )
            );

        if ($isOfficeFile) {
            return [
                'message' => 'File ini adalah file Office (XLSX/XLS) di Google Drive, bukan Google Sheets native. Buka file di Drive → File → Simpan sebagai Google Sheets lalu pakai link sheet tersebut. Atau gunakan menu Upload Excel.',
                'status' => 400,
            ];
        }

        $isForbidden = $code === 403
            || $reason === 'forbidden'
            || $reason === 'permissiondenied'
            || str_contains($message, '403')
            || str_contains($message, 'permission')
            || str_contains($message, 'forbidden');

        if ($isForbidden) {
            return [
                'message' => 'Akses ditolak. Pastikan spreadsheet sudah di-share ke email Google yang terhubung di halaman Drive Config.',
                'status' => 403,
            ];
        }

        $isNotFound = $code === 404
            || $reason === 'notfound'
            || str_contains($message, '404')
            || str_contains($message, 'not found');

        if ($isNotFound) {
            return [
                'message' => 'Spreadsheet tidak ditemukan. Periksa kembali link dan pastikan file belum dihapus.',
                'status' => 404,
            ];
        }

        return [
            'message' => 'Gagal membaca spreadsheet. Pastikan link benar dan spreadsheet sudah di-share ke email Google yang terhubung.',
            'status' => 500,
        ];
    }
}
