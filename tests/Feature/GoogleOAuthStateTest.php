<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleOAuthStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_callback_dengan_state_salah_ditolak(): void
    {
        $admin = $this->buatAdmin('admin_oauth_state_salah');

        $response = $this->withSession(['oauth_state' => 'state-benar'])
            ->actingAs($admin)
            ->get('/admin/google/callback?code=abc&state=state-salah');

        $response->assertRedirect(route('admin.drive-config'));
        $response->assertSessionHas('error', 'Autentikasi Google gagal. Silakan hubungkan kembali.');
        $response->assertSessionMissing('oauth_state');
    }

    public function test_callback_tanpa_state_ditolak_oleh_validasi(): void
    {
        $admin = $this->buatAdmin('admin_oauth_tanpa_state');

        $response = $this->actingAs($admin)
            ->get('/admin/google/callback?code=abc');

        $response->assertSessionHasErrors('state');
    }

    private function buatAdmin(string $username): User
    {
        return User::create([
            'username' => $username,
            'nama' => 'Admin ' . $username,
            'email' => $username . '@example.test',
            'password' => 'password',
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
