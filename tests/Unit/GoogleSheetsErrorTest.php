<?php

namespace Tests\Unit;

use App\Services\GoogleSheetsService;
use Google\Service\Exception as GoogleServiceException;
use Tests\TestCase;

class GoogleSheetsErrorTest extends TestCase
{
    public function test_error_office_file_diberi_pesan_spesifik(): void
    {
        $e = new \RuntimeException('{"error":{"code":400,"message":"This operation is not supported for this document. The document must not be an Office file. Please use the Drive API to access this file.","status":"FAILED_PRECONDITION"}}');

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(400, $response['status']);
        $this->assertStringContainsString('bukan Google Sheets native', $response['message']);
        $this->assertStringContainsString('Upload Excel', $response['message']);
    }

    public function test_error_permission_diberi_status_403(): void
    {
        $e = new \RuntimeException('The caller does not have permission (403)');

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(403, $response['status']);
        $this->assertStringContainsString('Akses ditolak', $response['message']);
    }

    public function test_error_umum_tetap_memakai_pesan_default(): void
    {
        $e = new \RuntimeException('Network down');

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(500, $response['status']);
        $this->assertStringContainsString('Gagal membaca spreadsheet', $response['message']);
    }

    public function test_error_404_diberi_status_404(): void
    {
        $e = new \RuntimeException('The file could not be found (404)');

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(404, $response['status']);
        $this->assertStringContainsString('tidak ditemukan', $response['message']);
    }

    public function test_failed_precondition_non_office_tetap_default(): void
    {
        $e = new \RuntimeException('FAILED_PRECONDITION: invalid range');

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(500, $response['status']);
        $this->assertStringContainsString('Gagal membaca spreadsheet', $response['message']);
    }

    public function test_error_terstruktur_google_dipetakan_dari_reason(): void
    {
        $e = new GoogleServiceException('Request failed', 403, null, [
            ['domain' => 'global', 'reason' => 'forbidden', 'message' => 'Forbidden'],
        ]);

        $response = GoogleSheetsService::fetchErrorResponse($e);

        $this->assertSame(403, $response['status']);
        $this->assertStringContainsString('Akses ditolak', $response['message']);
    }
}
