@extends('layouts.main')

@section('content')
<div class="login-page">
    <div class="login-card">
        <div class="card">
            <div class="login-logo"><img src="{{ asset('images/logo_no_text.png') }}" alt="Logo"></div>
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
                        <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}" required autofocus placeholder="Masukkan username">
                    </div>
                </div>
                <div class="login-field">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required placeholder="Masukkan password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Masuk</button>
            </form>
        </div>
    </div>
</div>
@endsection