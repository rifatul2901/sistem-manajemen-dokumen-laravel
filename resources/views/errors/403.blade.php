<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Akses Ditolak</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #0b1121 0%, #172554 50%, #0b1121 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 25px 80px rgba(0,0,0,.4);
            animation: fadeIn .5s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { height: 80px; width: auto; margin-bottom: 1.5rem; }
        .error-code { font-size: 5rem; font-weight: 700; color: rgba(255,255,255,.15); line-height: 1; margin-bottom: .5rem; letter-spacing: -.03em; }
        .error-title { font-size: 1.25rem; font-weight: 600; color: #fff; margin-bottom: .75rem; }
        .error-message { font-family: 'Open Sans', sans-serif; font-size: .9rem; color: rgba(255,255,255,.6); line-height: 1.6; margin-bottom: 2rem; }
        .btn-back {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .65rem 1.5rem; background: rgba(255,255,255,.1); color: #fff;
            border: 1px solid rgba(255,255,255,.15); border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: .85rem; font-weight: 500;
            text-decoration: none; transition: background .2s, transform .15s; cursor: pointer;
        }
        .btn-back:hover { background: rgba(255,255,255,.18); transform: translateY(-1px); }
        .btn-back:active { transform: scale(.97); }
    </style>
</head>
<body>
    <div class="error-card">
        <img src="{{ asset('images/logo_no_text.png') }}" alt="Logo" class="logo">
        <div class="error-code">403</div>
        <div class="error-title">Akses Ditolak</div>
        <div class="error-message">
            Anda tidak memiliki izin untuk mengakses halaman ini.
        </div>
        <a href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}" class="btn-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali ke {{ auth()->check() ? 'Dashboard' : 'Halaman Login' }}
        </a>
    </div>
</body>
</html>
