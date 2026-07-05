<?php

namespace App\Http\Controllers;

use App\Models\DriveToken;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected function getClient(): Client
    {
        $clientSecretPath = storage_path('app/oauth_client_secret.json');

        if (!file_exists($clientSecretPath)) {
            abort(400, 'OAuth client belum diupload. Upload file JSON dari Google Cloud Console terlebih dahulu.');
        }

        $client = new Client();
        $client->setApplicationName('Sistem Manajemen Dokumen');
        $client->setScopes([Drive::DRIVE]);
        $client->setAuthConfig($clientSecretPath);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        $client->setRedirectUri(route('admin.google.callback'));

        return $client;
    }

    public function redirectToGoogle()
    {
        $client = $this->getClient();
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }

    public function handleCallback(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        try {
            $client = $this->getClient();
            $token = $client->fetchAccessTokenWithAuthCode($request->code);

            if (isset($token['error'])) {
                return redirect()->route('admin.drive-config')
                    ->with('error', 'Gagal autentikasi: ' . $token['error']);
            }

            $client->setAccessToken($token);

            // Dapatkan email user Google
            $driveService = new Drive($client);
            $about = $driveService->about->get(['fields' => 'user']);
            $driveEmail = $about->getUser()->getEmailAddress() ?? null;

            // Simpan token ke database
            $expiresAt = isset($token['expires_in'])
                ? now()->addSeconds($token['expires_in'])
                : null;

            DriveToken::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'access_token'  => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? '',
                    'token_type'    => $token['token_type'] ?? 'Bearer',
                    'expires_in'    => $token['expires_in'] ?? null,
                    'expires_at'    => $expiresAt,
                    'drive_email'   => $driveEmail,
                ]
            );

            return redirect()->route('admin.drive-config')
                ->with('success', 'Berhasil terhubung dengan Google Drive sebagai ' . ($driveEmail ?? '(tidak diketahui)'));
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', ['user' => Auth::id(), 'error' => $e->getMessage()]);
            return redirect()->route('admin.drive-config')
                ->with('error', 'Gagal memproses callback Google. Silakan coba lagi.');
        }
    }

    public function disconnect()
    {
        DriveToken::where('user_id', Auth::id())->delete();

        return redirect()->route('admin.drive-config')
            ->with('success', 'Koneksi Google Drive berhasil diputuskan.');
    }
}
