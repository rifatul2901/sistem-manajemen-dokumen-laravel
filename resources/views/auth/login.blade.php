@extends('layouts.main')

@section('content')
<div class="login-page">
    <div class="login-bg" aria-hidden="true">
        <span class="lb-glow lb-glow-a"></span>
        <span class="lb-glow lb-glow-b"></span>
        <span class="lb-dots lb-dots-l"></span>
        <span class="lb-dots lb-dots-r"></span>

        {{-- Gelombang utama di dasar layar: 3 lapisan navy/biru + 1 garis aksen cyan --}}
        <svg class="lb-wave lb-wave-bottom" viewBox="0 0 1440 420" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" focusable="false">
            <defs>
                <linearGradient id="lbWaveBlue" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#0B3B70" stop-opacity="0"/>
                    <stop offset="100%" stop-color="#0B3B70" stop-opacity=".5"/>
                </linearGradient>
            </defs>
            <path d="M0,240 C240,200 420,270 720,225 C960,188 1200,250 1440,215 L1440,420 L0,420 Z" fill="url(#lbWaveBlue)"/>
            <path d="M0,300 C260,265 460,330 760,288 C1020,254 1240,310 1440,282 L1440,420 L0,420 Z" fill="#08264A" opacity=".5"/>
            <path d="M0,300 C260,265 460,330 760,288 C1020,254 1240,310 1440,282" fill="none" stroke="#0EA5E9" stroke-opacity=".22" stroke-width="1.2"/>
            <path d="M0,360 C280,330 500,385 800,355 C1060,330 1260,368 1440,356 L1440,420 L0,420 Z" fill="#071A35" opacity=".65"/>
        </svg>
    </div>

    <div class="login-split">
        <div class="login-card login-panel-form">
            <div class="card">
                <h1 class="login-title">Selamat Datang</h1>
                <p class="login-subtitle">Masuk ke Sistem Manajemen Dokumen</p>

                @php
                    $loginErrors = $errors->all();
                    $invalidCredentials = count($loginErrors) === 1
                        && $loginErrors[0] === 'Username atau password salah.';
                    $hasSessionError = session()->has('error');
                    $sessionError = session('error');
                @endphp

                @if ($errors->any() || $hasSessionError)
                    <div class="login-alert login-alert-error" role="alert" aria-atomic="true">
                        <span class="login-alert-icon" aria-hidden="true">
                            <i class="bi bi-shield-exclamation"></i>
                        </span>
                        <div class="login-alert-body">
                            <p class="login-alert-title">Login gagal</p>
                            @if ($invalidCredentials)
                                <p class="login-alert-text">Username atau password yang kamu masukkan tidak sesuai.</p>
                            @elseif ($errors->any())
                                <ul class="login-alert-list">
                                    @foreach ($loginErrors as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($hasSessionError)
                                <p class="login-alert-text">{{ $sessionError }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="login-field">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Masukkan username">
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="Masukkan password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </form>

                <div class="login-copyright">
                    <span>© 2026 {{ config('app.instansi', 'Badan Pusat Statistik Provinsi Sulawesi Utara 2026') }}. All rights reserved.</span>
                    <span>Developed by Rifatul Nabil</span>
                </div>
            </div>
        </div>
        <div class="login-panel-promo">
            <div class="promo-head">
                <div class="promo-logo">
                    <picture>
                        <source srcset="{{ asset('images/logo_no_text.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo_no_text.png') }}" alt="Logo" width="512" height="456" loading="eager" fetchpriority="high" decoding="async">
                    </picture>
                </div>
                <div class="promo-head-text">
                    <h2 class="promo-title">SIMADO</h2>
                    <p class="promo-subtitle">Sistem Manajemen Dokumen</p>
                </div>
            </div>
            <p class="promo-desc">Sistem kontrol kualitas dokumen digital untuk mendukung kelengkapan dan transparansi kerja tim.</p>
            <div class="promo-quote">
                @php($quote = collect(config('quotes'))->random())
                <i class="bi bi-quote"></i>
                <span>{{ $quote['text'] }}</span>
                <span class="quote-source">— {{ $quote['source'] }}</span>
            </div>
        </div>
    </div>
</div>
@endsection