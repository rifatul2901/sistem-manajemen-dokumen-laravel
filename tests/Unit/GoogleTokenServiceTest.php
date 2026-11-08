<?php

namespace Tests\Unit;

use App\Models\DriveToken;
use App\Services\GoogleTokenService;
use Google\Client;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GoogleTokenServiceTest extends TestCase
{
    public function test_token_yang_masih_berlaku_tidak_dianggap_kedaluwarsa(): void
    {
        $token = new DriveToken([
            'access_token' => 'token-uji',
            'refresh_token' => 'refresh-uji',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
        $token->expires_at = Carbon::now()->addHour();
        $token->updated_at = Carbon::now()->subDay();

        $client = new Client();
        $client->setAccessToken(GoogleTokenService::accessTokenArray($token));

        $this->assertFalse($client->isAccessTokenExpired());
    }

    public function test_access_token_kosong_dipaksa_kedaluwarsa_agar_bisa_refresh(): void
    {
        $token = new DriveToken([
            'access_token' => null,
            'refresh_token' => 'refresh-uji',
            'expires_in' => 3600,
        ]);
        $token->expires_at = Carbon::now()->addHour();

        $array = GoogleTokenService::accessTokenArray($token);

        $this->assertSame(0, $array['created']);

        $client = new Client();
        $client->setAccessToken($array);

        $this->assertTrue($client->isAccessTokenExpired());
    }
}
