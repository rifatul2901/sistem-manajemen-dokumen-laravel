<?php

namespace App\Services;

use App\Models\DriveToken;
use Google\Client;
use Illuminate\Support\Facades\Log;

/**
 * Logika token Google dipakai bersama oleh GoogleDriveService,
 * GoogleSheetsService, dan halaman Drive Config.
 */
class GoogleTokenService
{
    /**
     * Susun token untuk Google Client.
     *
     * Google Client menghitung kedaluwarsa dari 'created' + 'expires_in'.
     * Jangan pakai created_at (tanggal pertama connect), karena nilainya
     * tidak berubah setelah refresh dan membuat token selalu dianggap habis.
     */
    public static function accessTokenArray(DriveToken $token): array
    {
        $expiresIn = (int) ($token->expires_in ?: 3600);
        $hasAccessToken = filled($token->access_token);

        if ($hasAccessToken) {
            $created = $token->expires_at
                ? $token->expires_at->timestamp - $expiresIn
                : ($token->updated_at?->timestamp ?? 0);
        } else {
            // Paksa dianggap kedaluwarsa agar langsung dicoba refresh.
            $created = 0;
        }

        return [
            'access_token'  => $token->access_token ?? '',
            'refresh_token' => $token->refresh_token ?? '',
            'token_type'    => $token->token_type ?? 'Bearer',
            'expires_in'    => $expiresIn,
            'created'       => $created,
        ];
    }

    /**
     * Pastikan token masih berlaku; refresh bila sudah kedaluwarsa.
     */
    public static function ensureFresh(Client $client, ?DriveToken $token): void
    {
        if (!$client->isAccessTokenExpired()) {
            return;
        }

        $refreshToken = $client->getRefreshToken() ?: $token?->refresh_token;

        if (!$token || !$refreshToken) {
            throw new \RuntimeException('Token Google Drive tidak valid. Silakan hubungkan ulang akun Google Anda.');
        }

        $maxAttempts = 2;
        $attempt = 0;
        $newToken = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                break;
            } catch (\Exception $e) {
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
                Log::warning('Refresh token gagal (percobaan ' . $attempt . '), mencoba ulang...', [
                    'error' => $e->getMessage(),
                ]);
                usleep(2000000);
            }
        }

        // Google bisa mengembalikan array error tanpa access_token.
        // Tanpa penjagaan ini, access_token bisa tersimpan NULL dan
        // semua request berikutnya gagal dengan "Invalid token format".
        if (empty($newToken['access_token'])) {
            throw new \RuntimeException('Google tidak mengembalikan access token baru. Silakan hubungkan ulang akun Google Anda.');
        }

        $expiresIn = $newToken['expires_in'] ?? null;

        $token->update([
            'access_token'  => $newToken['access_token'],
            'refresh_token' => $newToken['refresh_token'] ?? $token->refresh_token,
            'expires_in'    => $expiresIn,
            'expires_at'    => $expiresIn ? now()->addSeconds($expiresIn) : null,
        ]);
    }
}
