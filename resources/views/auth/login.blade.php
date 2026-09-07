@extends('layouts.main')

@section('content')
<div class="login-page">
    <div class="login-split">
        <div class="login-card login-panel-form">
            <div class="card">
                <h1 class="login-title">Selamat Datang</h1>
                <p class="login-subtitle">Masuk ke Sistem Manajemen Dokumen</p>

                @if ($errors->any())
                    <div class="login-alert login-alert-error" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @session('error')
                    <div class="login-alert login-alert-error" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>{{ $value }}</span>
                    </div>
                @endsession

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
                <i class="bi bi-quote"></i>
                <span>Jadilah tolok ukur kualitas. Sebagian orang belum terbiasa berada di lingkungan yang menuntut keunggulan.</span>
                <span class="quote-source">— Steve Jobs</span>
            </div>
        </div>
    </div>
</div>
@endsection