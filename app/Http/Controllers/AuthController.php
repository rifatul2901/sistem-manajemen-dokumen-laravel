<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = Str::lower($request->input('username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('Login rate limit hit', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);
            return back()->withErrors([
                'username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
            ])->onlyInput('username');
        }

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                RateLimiter::hit($throttleKey, 180);
                Log::warning('Login ditolak (akun nonaktif)', [
                    'username' => $request->input('username'),
                    'ip' => $request->ip(),
                ]);
                return back()->withErrors([
                    'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi admin.',
                ])->onlyInput('username');
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            Log::info('Login berhasil', [
                'username' => $request->input('username'),
                'ip' => $request->ip(),
            ]);

            if ($user->isAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }

            return redirect()->intended('/');
        }

        RateLimiter::hit($throttleKey, 180);
        Log::warning('Login gagal', [
            'username' => $request->input('username'),
            'ip' => $request->ip(),
        ]);

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
