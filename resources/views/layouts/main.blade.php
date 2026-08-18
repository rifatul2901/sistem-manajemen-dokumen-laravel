<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page-title', 'Sistem Manajemen Dokumen')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_no_text.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
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
            background: linear-gradient(180deg, #0f172a 0%, #0a0f1e 100%);
            z-index: 1040;
            display: flex; flex-direction: column;
            transition: transform .3s cubic-bezier(.4,0,.2,1);
        }
        .sidebar-brand { padding: .75rem 1rem; display: flex; align-items: center; justify-content: center; border-bottom: 1px solid rgba(255,255,255,.06); }
        .sidebar-brand img { height: 90px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,.2)); }
        .sidebar-user { padding: .6rem 1.1rem; display: flex; align-items: center; gap: .6rem; }
        .sidebar-user .avatar {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 600; font-size: .72rem; flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,146,223,.3);
        }
        .sidebar-user .user-info { min-width: 0; }
        .sidebar-user .user-name { font-size: .8rem; font-weight: 500; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sidebar-user .user-role { font-size: .6rem; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: #64748b; }
        .sidebar-nav { flex: 1; overflow-y: auto; padding: .4rem .7rem; }
        .sidebar-nav .nav-section { font-size: .58rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: #475569; padding: .6rem .7rem .3rem; display: block; }
        .sidebar-nav .nav-item { margin-bottom: 1px; }
        .sidebar-nav .nav-link {
            display: flex; align-items: center; gap: .6rem;
            padding: .48rem .65rem; border-radius: var(--radius-sm);
            color: #94a3b8; font-size: .8rem; font-weight: 500;
            text-decoration: none; transition: all .15s ease;
            position: relative;
        }
        .sidebar-nav .nav-link:hover { color: #e2e8f0; background: rgba(255,255,255,.06); }
        .sidebar-nav .nav-link.active {
            color: #fff; background: rgba(0,146,223,.2);
            box-shadow: inset 3px 0 0 var(--primary);
        }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
        .sidebar-nav .nav-link .nav-badge { margin-left: auto; font-size: .65rem; padding: .15rem .5rem; border-radius: 20px; background: rgba(255,255,255,.1); color: #94a3b8; }
        .sidebar-footer { padding: .75rem; border-top: 1px solid rgba(255,255,255,.06); }
        .sidebar-footer .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: .5rem;
            width: 100%; padding: .6rem; border-radius: var(--radius-sm);
            background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.08);
            color: #94a3b8; font-size: .875rem; font-weight: 500;
            transition: all .15s ease; cursor: pointer;
        }
        .sidebar-footer .btn-logout:hover { background: rgba(239,68,68,.15); border-color: rgba(239,68,68,.25); color: #fca5a5; }

        .sidebar-watermark {
            padding: .75rem;
            margin: 0 .75rem .1rem;
            border-top: 1px solid rgba(255,255,255,.06);
            text-align: center;
        }
        .sidebar-watermark span {
            display: block;
            font-size: .6rem;
            line-height: 1.4;
            color: #475569;
        }
        .sidebar-watermark span:last-child {
            font-size: .55rem;
            color: #6b7280;
            margin-top: 2px;
        }
        .sidebar-watermark span:first-child {
            color: #9ca3af;
        }
        .sidebar-quote {
            padding: .3rem .5rem;
            margin: .1rem .75rem .35rem;
            border-left: 2px solid rgba(245,158,11,.5);
            background: rgba(245,158,11,.04);
            border-radius: 6px;
        }
        .sidebar-quote i { color: rgba(245,158,11,.6); font-size: .55rem; display: block; margin-bottom: .1rem; }
        .sidebar-quote span { font-size: .68rem; line-height: 1.4; color: #afb6c1; font-style: italic; display: block; }
        .sidebar-quote .quote-source {
            font-size: .56rem;
            color: rgba(245,158,11,.5);
            font-style: normal;
            text-align: right;
            margin-top: .2rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left .3s cubic-bezier(.4,0,.2,1);
        }
        .main-header {
            position: sticky; top: 0; z-index: 1030;
            background: rgba(255,255,255,.8); backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: none; align-items: center; gap: 1rem;
        }
        .main-header .toggle-sidebar { background: 0; border: 0; color: #475569; font-size: 1.25rem; cursor: pointer; padding: .25rem; border-radius: 6px; transition: background .15s; }
        .main-header .toggle-sidebar:hover { background: #f1f5f9; }
        .main-header .page-title { font-size: 1rem; font-weight: 600; color: #1e293b; }
        .main-body { padding: 1.5rem; }
        .main-body > .page-heading { margin-bottom: 1.5rem; }
        .main-body > .page-heading h4 { font-size: 1.35rem; font-weight: 700; color: #0f172a; letter-spacing: -.02em; margin: 0; }
        .main-body > .page-heading p { font-size: .8rem; color: #64748b; margin: .15rem 0 0; }
        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1039; display: none; }

        @media (max-width: 767.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main-content { margin-left: 0; }
            .main-header { display: flex; }
            .main-body { padding: 1rem; }
        }

        .card { border: 0; border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.04), 0 1px 2px rgba(0,0,0,.06); transition: box-shadow .2s, transform .2s; }
        .card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.06), 0 2px 4px rgba(0,0,0,.04); }
        .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; font-weight: 600; font-size: .875rem; border-radius: var(--radius) var(--radius) 0 0 !important; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
        .card-header .card-header-actions { display: flex; align-items: center; gap: .5rem; }
        .card-body { padding: 1.25rem; }

        .btn { border-radius: var(--radius-sm); font-weight: 500; font-size: .875rem; padding: .5rem 1rem; transition: all .15s ease; cursor: pointer; }
        .btn-sm { padding: .35rem .65rem; font-size: .8rem; }
        .btn:active { transform: scale(.97); }
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
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #d1fae5; color: #065f46; }
        .badge-revision { background: #fecaca; color: #7f1d1d; }
        .badge-untracked { background: #f1f5f9; color: #475569; }

        .form-control, .form-select {
            border-radius: var(--radius-sm); border: 1.5px solid #e2e8f0;
            font-size: .875rem; padding: .5rem .75rem; transition: all .15s ease;
        }
        .form-control:hover, .form-select:hover { border-color: #cbd5e1; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,146,223,.12); }
        .form-label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
        .form-text { font-size: .75rem; color: #94a3b8; }

        .alert { border: 0; border-radius: var(--radius-sm); padding: .75rem 1rem; font-size: .875rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fce7f3; color: #9d174d; }
        .alert-warning { background: #fef3c7; color: #92400e; }

        .table { font-size: .875rem; margin: 0; }
        .table thead th {
            background: #f8fafc; border-bottom: 2px solid #e2e8f0;
            font-weight: 600; font-size: .75rem; text-transform: uppercase;
            letter-spacing: .03em; color: #64748b; padding: .75rem 1rem;
            white-space: nowrap;
        }
        .table tbody td { padding: .75rem 1rem; vertical-align: middle; border-color: #f1f5f9; }
        .table tbody tr { transition: background .1s ease; }
        .table tbody tr:hover { background: #f1f5f9; }

        .modal-content { border: 0; border-radius: var(--radius); box-shadow: 0 25px 80px rgba(0,0,0,.2); }
        .modal-header { border-bottom: 1px solid #f1f5f9; padding: 1rem 1.25rem; }
        .modal-body { padding: 1.25rem; }
        .modal-footer { border-top: 1px solid #f1f5f9; padding: 1rem 1.25rem; }

        .page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; letter-spacing: -.02em; margin: 0; }
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
            background: linear-gradient(135deg, #0a3d5a 0%, #0c2a40 50%, #0f4b6e 100%);
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        .login-page::before {
            content: '';
            position: absolute; inset: 0;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(28,141,203,.2) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(107,190,69,.12) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(242,140,0,.08) 0%, transparent 50%);
            pointer-events: none;
        }
        .login-page::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(28,141,203,.06) 0%, transparent 70%);
            top: -100px; right: -100px;
            pointer-events: none;
        }
        .login-card { width: 100%; max-width: 420px; position: relative; z-index: 1; }
        .login-card .card {
            background: rgba(255,255,255,.08);
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.75rem 2rem 2.25rem;
            border: 1px solid rgba(255,255,255,.12);
            box-shadow: 0 25px 80px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.06);
            position: relative;
            animation: loginFade .5s ease-out;
        }
        @keyframes loginFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-logo { display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem; }
        .login-logo img { height: 68px; width: auto; filter: drop-shadow(0 2px 8px rgba(0,0,0,.15)); }
        .login-title { font-family: 'Poppins', sans-serif; font-size: 1.35rem; font-weight: 700; text-align: center; color: #fff; margin-bottom: .15rem; letter-spacing: -.02em; text-shadow: 0 1px 4px rgba(0,0,0,.15); }
        .login-subtitle { font-family: 'Open Sans', sans-serif; font-size: .85rem; color: rgba(255,255,255,.7); text-align: center; margin-bottom: 1.75rem; }
        .login-field { margin-bottom: 1.125rem; }
        .login-card .form-label { font-family: 'Open Sans', sans-serif; font-size: .8rem; font-weight: 600; color: rgba(255,255,255,.85); margin-bottom: .35rem; display: block; letter-spacing: .01em; }
        .login-card .input-group { border-radius: 12px; overflow: hidden; transition: box-shadow .25s ease; }
        .login-card .input-group:focus-within { box-shadow: 0 0 0 3px rgba(28,141,203,.3); }
        .login-card .input-group-text {
            border: 1.5px solid rgba(255,255,255,.15); border-right: none;
            background: rgba(255,255,255,.06); color: rgba(255,255,255,.6);
            padding: .65rem .85rem; font-size: 1rem;
        }
        .login-card .form-control {
            border: 1.5px solid rgba(255,255,255,.15); border-left: none;
            padding: .65rem .85rem .65rem 0;
            font-family: 'Open Sans', sans-serif;
            font-size: .9rem;
            background: rgba(255,255,255,.06);
            color: #fff;
            transition: background .2s ease, border-color .2s ease;
        }
        .login-card .form-control:focus {
            border-color: rgba(255,255,255,.3);
            box-shadow: none;
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .login-card .form-control.is-invalid { border-color: rgba(239,68,68,.7); }
        .login-card .form-control::placeholder { color: rgba(255,255,255,.35); font-weight: 400; }
        .login-card .btn-primary {
            background: linear-gradient(135deg, #1C8DCB, #1679b0);
            border: none;
            border-radius: 12px;
            padding: .75rem 1rem;
            font-family: 'Poppins', sans-serif;
            font-size: .95rem;
            font-weight: 600;
            letter-spacing: .02em;
            transition: all .25s ease;
            color: #fff;
        }
        .login-card .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(28,141,203,.35); }
        .login-card .btn-primary:active { transform: translateY(0); box-shadow: 0 2px 8px rgba(28,141,203,.25); }
        .login-alert {
            display: flex; align-items: center; gap: .6rem;
            padding: .65rem .85rem;
            border-radius: 12px;
            font-family: 'Open Sans', sans-serif;
            font-size: .8rem;
            margin-bottom: 1.25rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .login-alert-error { background: rgba(239,68,68,.12); color: #fca5a5; border: 1px solid rgba(239,68,68,.2); }
        .login-alert-error i { font-size: .95rem; flex-shrink: 0; }
        .login-alert-error ul { margin: 0; padding-left: 1.25rem; }
        .login-alert-error ul li { margin-bottom: .15rem; }
        .login-alert-error ul li:last-child { margin-bottom: 0; }

        .empty-state { padding: 3rem 1rem; text-align: center; }
        .empty-state i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: .5rem; display: block; }
        .empty-state p { color: #94a3b8; font-size: .9rem; }
        .empty-state .empty-action { margin-top: 1rem; }

        .section-card { border-left: 3px solid var(--primary); }

        .chip { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .6rem; border-radius: 6px; font-size: .75rem; font-weight: 500; }
        .chip-subfolder { background: #e0f2fe; color: #0369a1; }

        .page-heading { margin-bottom: 1.5rem; }
        .page-heading h4 { font-size: 1.35rem; font-weight: 700; color: #0f172a; letter-spacing: -.02em; margin: 0; }
        .page-heading p { font-size: .8rem; color: #64748b; margin: .15rem 0 0; }

        .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }

        .table-responsive { border-radius: var(--radius-sm); }
        .table-hover tbody tr { transition: background .1s ease; }

        .dropdown-menu { border: 0; border-radius: var(--radius-sm); box-shadow: 0 4px 20px rgba(0,0,0,.1); }
        .dropdown-item { font-size: .85rem; padding: .5rem 1rem; border-radius: 4px; }
        .dropdown-item:hover { background: #f1f5f9; }
        .dropdown-item i { width: 18px; text-align: center; margin-right: .5rem; color: #64748b; }

        .log-card { transition: transform .15s ease, box-shadow .15s ease; }
        .log-card:hover { transform: translateX(3px); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@7.3.0/dist/turbo.es2017-umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cleanupCollapse() {
        document.querySelectorAll('.collapse').forEach(function (el) {
            var instance = bootstrap.Collapse.getInstance(el);
            if (instance) instance.dispose();
            el.classList.remove('show');
            var btn = document.querySelector('[data-bs-target="#' + el.id + '"]');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    }
    document.addEventListener('turbo:before-cache', cleanupCollapse);
    document.addEventListener('turbo:load', cleanupCollapse);
    </script>
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
            <span>Jadilah tolok ukur kualitas. Sebagian orang belum terbiasa berada di lingkungan yang menuntut keunggulan.</span>
            <span class="quote-source">— Steve Jobs</span>
        </div>
        <div class="sidebar-watermark">
            <span>© 2026 {{ config('app.instansi', 'Badan Pusat Statistik Provinsi Sulawesi Utara 2026') }}. All rights reserved.</span>
            <span>Developed by Rifatul Nabil</span>
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
    @stack('scripts')
</body>
</html>