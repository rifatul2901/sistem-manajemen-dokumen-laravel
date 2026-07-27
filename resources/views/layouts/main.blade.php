<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Dokumen</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_no_text.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @stack('styles')
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-width: 260px;
            --primary: #0092DF;
            --primary-hover: #0072B0;
            --primary-light: #4FC3F7;
            --success: #62B846;
            --success-hover: #4EA035;
            --success-light: #86C97A;
            --warning: #F58220;
            --warning-hover: #D96D0E;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --info: #0ea5e9;
            --bg-body: #f1f5f9;
            --card-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            --radius: 12px;
            --radius-sm: 8px;
        }
        *, ::after, ::before { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background: var(--bg-body);
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
        }
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .sidebar {
            position: fixed; inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            z-index: 1040;
            display: flex; flex-direction: column;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }
        .sidebar-brand { padding: .1rem 0 .1rem .1rem; display: flex; align-items: center;justify-content: center; }
        .sidebar-divider { height: 1px; background: rgba(255,255,255,.08); margin: .5rem 1rem; }
        .sidebar-user { padding: .5rem 1.25rem; display: flex; align-items: center; gap: .75rem; }
        .sidebar-user .avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 600; font-size: .75rem; flex-shrink: 0;
        }
        .sidebar-user .user-info { min-width: 0; }
        .sidebar-user .user-name { font-size: .875rem; font-weight: 500; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sidebar-user .user-role { font-size: .7rem; font-weight: 500; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; }
        .sidebar-nav { flex: 1; overflow-y: auto; padding: .25rem .75rem; }
        .sidebar-nav .nav-item { margin-bottom: 2px; }
        .sidebar-nav .nav-link {
            display: flex; align-items: center; gap: .75rem;
            padding: .6rem .75rem; border-radius: var(--radius-sm);
            color: #94a3b8; font-size: .875rem; font-weight: 500;
            text-decoration: none; transition: all .15s ease;
        }
        .sidebar-nav .nav-link:hover { color: #e2e8f0; background: rgba(255,255,255,.06); }
        .sidebar-nav .nav-link.active { color: #fff; background: rgba(0,146,223,.25); }
        .sidebar-nav .nav-link i { font-size: 1.1rem; width: 20px; text-align: center; flex-shrink: 0; }
        .sidebar-footer { padding: .75rem; }
        .sidebar-footer .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            width: 100%; padding: .6rem; border-radius: var(--radius-sm);
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
            color: #94a3b8; font-size: .875rem; font-weight: 500;
            transition: all .15s ease; cursor: pointer;
        }
        .sidebar-footer .btn-logout:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.25); color: #fca5a5; }

        .sidebar-quote {
            padding: .6rem .75rem;
            margin: .25rem .75rem .5rem;
            border-left: 2px solid #f59e0b;
            background: rgba(245,158,11,.06);
            border-radius: 6px;
        }
        .sidebar-quote i {
            color: #f59e0b;
            font-size: .6rem;
            display: block;
            margin-bottom: .15rem;
        }
        .sidebar-quote span {
            font-size: .725rem;
            line-height: 1.45;
            color: #94a3b8;
            font-style: italic;
            display: block;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }
        .main-header {
            position: sticky; top: 0; z-index: 1030;
            background: rgba(255,255,255,.8); backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: none; align-items: center; gap: 1rem;
        }
        .main-header .toggle-sidebar { background: 0; border: 0; color: #475569; font-size: 1.25rem; cursor: pointer; padding: .25rem; }
        .main-header .page-title { font-size: 1rem; font-weight: 600; color: #1e293b; }
        .main-body { padding: 1.5rem; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1039; display: none; }

        @media (max-width: 767.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0; }
            .main-header { display: flex; }
            .main-body { padding: 1rem; }
        }

        .card { border: 0; border-radius: var(--radius); box-shadow: var(--card-shadow); transition: box-shadow .2s, transform .2s; }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; font-weight: 600; font-size: .875rem; border-radius: var(--radius) var(--radius) 0 0 !important; }
        .card-body { padding: 1.25rem; }

        .btn { border-radius: var(--radius-sm); font-weight: 500; font-size: .875rem; padding: .5rem 1rem; transition: all .15s; }
        .btn-sm { padding: .35rem .65rem; font-size: .8rem; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }
        .btn-success { background: var(--success); border-color: var(--success); }
        .btn-success:hover { background: var(--success-hover); border-color: var(--success-hover); }
        .btn-danger { background: var(--danger); border-color: var(--danger); }
        .btn-danger:hover { background: var(--danger-hover); border-color: var(--danger-hover); }
        .btn-outline-primary { color: var(--primary); border-color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-outline-success { color: var(--success); border-color: var(--success); }
        .btn-outline-success:hover { background: var(--success); border-color: var(--success); color: #fff; }
        .btn-outline-danger { color: var(--danger); border-color: var(--danger); }
        .btn-outline-danger:hover { background: var(--danger); border-color: var(--danger); color: #fff; }
        .btn-outline-info { color: var(--info); border-color: var(--info); }
        .btn-outline-info:hover { background: var(--info); border-color: var(--info); color: #fff; }

        .badge { font-weight: 500; padding: .35em .65em; border-radius: 6px; font-size: .75rem; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-revision { background: #f8d7da; color: #721c24; }
        .badge-untracked { background: #e2e8f0; color: #475569; }

        .form-control, .form-select {
            border-radius: var(--radius-sm); border: 1.5px solid #e2e8f0;
            font-size: .875rem; padding: .5rem .75rem; transition: all .15s;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,146,223,.15); }
        .form-label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
        .form-text { font-size: .75rem; color: #94a3b8; }

        .alert { border: 0; border-radius: var(--radius-sm); padding: .75rem 1rem; font-size: .875rem; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-warning { background: #fff3cd; color: #856404; }
        .alert-info { background: #d1ecf1; color: #0c5460; }

        .table { font-size: .875rem; margin: 0; }
        .table thead th {
            background: #f8fafc; border-bottom: 2px solid #e2e8f0;
            font-weight: 600; font-size: .75rem; text-transform: uppercase;
            letter-spacing: .03em; color: #64748b; padding: .75rem 1rem;
        }
        .table tbody td { padding: .75rem 1rem; vertical-align: middle; border-color: #f1f5f9; }
        .table tbody tr:hover { background: #f8fafc; }

        .modal-content { border: 0; border-radius: var(--radius); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; }
        .modal-body { padding: 1.25rem; }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 1rem 1.25rem; }

        .page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; letter-spacing: -.02em; }
        .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
        .btn-icon i { font-size: .875rem; }
        .action-group { display: flex; gap: 4px; flex-wrap: wrap; }

        .timeline-item { position: relative; padding-left: 1.5rem; padding-bottom: 1rem; }
        .timeline-item::before { content: ''; position: absolute; left: 4px; top: 8px; bottom: 0; width: 2px; background: #e2e8f0; }
        .timeline-item:last-child::before { display: none; }
        .timeline-item .dot {
            position: absolute; left: 0; top: 6px;
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--primary); border: 2px solid #fff;
        }
        .timeline-item .time-text { font-size: .75rem; color: #94a3b8; }

        .login-page {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            padding: 1rem;
        }
        .login-card { width: 100%; max-width: 420px; }
        .login-card .card { background: rgba(255,255,255,.98); border-radius: 16px; padding: 2rem; }
        .login-logo {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .login-logo i { font-size: 1.5rem; color: #fff; }
        .login-title { font-size: 1.4rem; font-weight: 700; text-align: center; color: #0f172a; margin-bottom: .25rem; }
        .login-subtitle { font-size: .85rem; color: #64748b; text-align: center; margin-bottom: 1.5rem; }

        .empty-state { padding: 3rem 1rem; text-align: center; }
        .empty-state i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .75rem; display: block; }
        .empty-state p { color: #94a3b8; font-size: .9rem; }

        .section-card { border-left: 3px solid var(--primary); }

        .chip { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 6px; font-size: .75rem; font-weight: 500; }
        .chip-subfolder { background: #e0f2fe; color: #0369a1; }
    </style>
</head>
<body>
    @auth
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Sistem Manajemen Dokumen" style="height:100px;width:auto;">
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-user">
            <div class="avatar">{{ strtoupper(substr(Auth::user()->nama ?? Auth::user()->username ?? 'U', 0, 2)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->nama ?? Auth::user()->username ?? 'User' }}</div>
                <div class="user-role">{{ Auth::user()->role ?? 'user' }}</div>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-nav">
            @if (Auth::user()->role === 'admin')
            <div class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data"></i> Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.generate') }}" class="nav-link {{ request()->routeIs('admin.generate*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-plus"></i> Generate Dokumen
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.rekapitulasi') }}" class="nav-link {{ request()->routeIs('admin.rekapitulasi*') ? 'active' : '' }}">
                    <i class="bi bi-bar-chart"></i> Rekapitulasi
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.drive-config') }}" class="nav-link {{ request()->routeIs('admin.drive-config') ? 'active' : '' }}">
                    <i class="bi bi-google"></i> Drive Config
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Manajemen User
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('admin.riwayat-revisi') }}" class="nav-link {{ request()->routeIs('admin.riwayat-revisi') ? 'active' : '' }}">
                    <i class="bi bi-clock-history"></i> Riwayat Revisi
                </a>
            </div>
            @else
            <div class="nav-item">
                <a href="{{ route('status.index') }}" class="nav-link {{ request()->routeIs('status*') ? 'active' : '' }}">
                    <i class="bi bi-check-circle"></i> Status Berkas
                </a>
            </div>
            <div class="nav-item">
                <a href="{{ route('riwayat.index') }}" class="nav-link {{ request()->routeIs('riwayat*') ? 'active' : '' }}">
                    <i class="bi bi-archive"></i> Riwayat
                </a>
            </div>
            @endif
        </div>
        <div class="sidebar-quote">
            <i class="bi bi-quote"></i>
            <span>Lengkap bukan berarti selesai,<br>tapi selesai haruslah lengkap</span>
        </div>
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Keluar</button>
            </form>
        </div>
    </nav>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="main-content">
        <header class="main-header">
            <button class="toggle-sidebar" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <span class="page-title">@yield('page-title', 'Sistem Manajemen Dokumen')</span>
        </header>
        <div class="main-body">
            @yield('content')
        </div>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }
    document.addEventListener('DOMContentLoaded', function() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrf) window.csrfToken = csrf;
    });
    </script>
    @else
    <div class="container-fluid p-0">
        @yield('content')
    </div>
    @endauth
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js"></script>
    @stack('scripts')
</body>
</html>