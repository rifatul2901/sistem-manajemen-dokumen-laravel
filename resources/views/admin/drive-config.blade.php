@extends('layouts.main')

@section('page-title', 'Konfigurasi Google Drive')

@push('styles')
<style>
    /* === Drive Config — scoped theme (konsisten dengan dashboard SIMADO) === */
    .drive-page { --drive-border: #E2EAF5; --drive-muted: #64748B; }

    /* Header halaman */
    .drive-page .drive-head { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.25rem; }
    .drive-page .drive-head .head-icon {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.25rem;
    }
    .drive-page .drive-head .page-title { font-size: 1.35rem; }
    .drive-page .drive-head p { color: var(--drive-muted); font-size: .875rem; margin: .15rem 0 0; }

    .drive-page .card { border: 1px solid var(--drive-border); }
    .drive-page .card-header .hdr-title { display: inline-flex; align-items: center; gap: .5rem; font-weight: 600; }
    .drive-page .card-header .hdr-title i { color: var(--primary); font-size: 1rem; }

    /* Panel info biru muda */
    .drive-page .info-panel { display: flex; gap: .75rem; align-items: flex-start; padding: .9rem 1rem; border-radius: 12px; background: #EAF2FB; border: 1px solid #D7E6F7; margin-bottom: 1.1rem; }
    .drive-page .info-panel i { color: var(--primary); font-size: 1.15rem; flex-shrink: 0; line-height: 1.4; }
    .drive-page .info-panel p { margin: 0; font-size: .82rem; color: #334155; }
    .drive-page .info-panel strong { color: #0f172a; }

    /* Ringkasan koneksi (di card konfigurasi) */
    .drive-page .conn-strip { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem 1rem; padding: .8rem 1rem; border: 1px solid var(--drive-border); border-radius: 12px; background: #FBFDFF; margin-bottom: 1.25rem; }
    .drive-page .conn-strip .conn-label { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; color: #94a3b8; }
    .drive-page .conn-strip .conn-value { font-size: .85rem; font-weight: 500; color: #0f172a; word-break: break-word; }
    @media (max-width: 575.98px) { .drive-page .conn-strip { grid-template-columns: 1fr; } }

    /* Subbagian konfigurasi */
    .drive-page .section-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .75rem; }
    .drive-page .section-head h6 { margin: 0; font-weight: 600; font-size: .9rem; color: #0f172a; }
    .drive-page .divider { margin: 1.25rem 0; border-color: var(--drive-border); opacity: 1; }

    /* Panduan OAuth (collapsible) */
    .drive-page .oauth-guide { border: 1px solid var(--drive-border); border-radius: 10px; padding: .65rem .85rem; margin-bottom: 1rem; background: #F8FAFC; }
    .drive-page .oauth-guide summary { cursor: pointer; font-weight: 600; font-size: .82rem; color: #334155; list-style: none; display: flex; align-items: center; gap: .4rem; }
    .drive-page .oauth-guide summary::-webkit-details-marker { display: none; }
    .drive-page .oauth-guide summary::before { content: "\F282"; font-family: "bootstrap-icons"; font-size: .8rem; color: var(--primary); transition: transform .15s ease; }
    .drive-page .oauth-guide[open] summary::before { transform: rotate(90deg); }
    .drive-page .oauth-guide[open] summary { margin-bottom: .6rem; }
    .drive-page .oauth-guide ol { margin: 0; padding-left: 1.15rem; font-size: .8rem; color: #475569; }
    .drive-page .oauth-guide li { margin-bottom: .25rem; }
    .drive-page .oauth-guide code { font-size: .75rem; color: #0369a1; background: #EAF2FB; padding: .1rem .35rem; border-radius: 5px; }

    /* Badge status */
    .drive-page .status-badge { display: inline-flex; align-items: center; gap: .35rem; padding: .35em .75em; border-radius: 999px; font-size: .75rem; font-weight: 500; }
    .drive-page .status-badge.ok { background: #E6F6EC; color: #15803d; }
    .drive-page .status-badge.warn { background: #FEF3C7; color: #92400e; }
    .drive-page .status-badge.off { background: #EEF2F7; color: #64748b; }
    .drive-page .status-badge.bad { background: #FEE2E2; color: #b91c1c; }

    /* Card status & uji koneksi */
    .drive-page .status-hero { text-align: center; padding: 1.25rem 1rem; border-radius: 12px; background: #F8FAFC; border: 1px solid var(--drive-border); margin-bottom: 1rem; }
    .drive-page .status-hero .status-icon { width: 56px; height: 56px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: .6rem; }
    .drive-page .status-hero.is-ok .status-icon { background: #E6F6EC; color: #16a34a; }
    .drive-page .status-hero.is-warn .status-icon { background: #FEF3C7; color: #b45309; }
    .drive-page .status-hero.is-off .status-icon { background: #EEF2F7; color: #94a3b8; }
    .drive-page .status-hero .status-msg { font-size: .82rem; color: var(--drive-muted); margin: .5rem 0 0; }

    .drive-page .status-list { margin: 0 0 1rem; }
    .drive-page .status-list .status-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid #f1f5f9; font-size: .82rem; }
    .drive-page .status-list .status-row:last-child { border-bottom: 0; }
    .drive-page .status-list .label { color: var(--drive-muted); }
    .drive-page .status-list .value { font-weight: 500; color: #0f172a; text-align: right; word-break: break-word; }

    /* Panel Informasi Sistem */
    .drive-page .info-tile { height: 100%; display: flex; align-items: center; gap: .8rem; padding: .9rem 1rem; border: 1px solid var(--drive-border); border-radius: 12px; background: #fff; transition: box-shadow .15s ease; }
    .drive-page .info-tile:hover { box-shadow: 0 4px 12px rgba(0,0,0,.05); }
    .drive-page .info-tile .tile-icon { width: 42px; height: 42px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; background: #EAF2FB; color: var(--primary); font-size: 1.1rem; flex-shrink: 0; }
    .drive-page .info-tile .tile-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; color: #94a3b8; }
    .drive-page .info-tile .tile-value { font-weight: 600; color: #0f172a; font-size: .9rem; line-height: 1.2; }
    .drive-page .info-tile .tile-sub { font-size: .72rem; color: #94a3b8; }
    @media (max-width: 575.98px) { .drive-page .info-tile { align-items: flex-start; } }
</style>
@endpush

@section('content')
@php
    $connected = (bool) $driveToken;
    $tokenExpired = $connected && $driveToken->isExpired();

    if (!$clientExists) {
        $svcState = 'warn'; $svcLabel = 'Belum Siap'; $svcNote = 'OAuth Client belum diupload.';
    } elseif (!$connected) {
        $svcState = 'off'; $svcLabel = 'Tidak Aktif'; $svcNote = 'Akun Drive belum terhubung.';
    } elseif ($tokenExpired) {
        $svcState = 'warn'; $svcLabel = 'Perlu Perhatian'; $svcNote = 'Token akses perlu diperbarui.';
    } else {
        $svcState = 'ok'; $svcLabel = 'Aktif'; $svcNote = 'Layanan siap digunakan.';
    }

    $heroState = (!$clientExists || $tokenExpired) ? 'is-warn' : ($connected ? 'is-ok' : 'is-off');
@endphp
<div class="container-fluid px-0 drive-page">
    <div class="drive-head">
        <span class="head-icon"><i class="bi bi-google"></i></span>
        <div>
            <h4 class="page-title">Drive Config</h4>
            <p>Konfigurasi Google Drive untuk menyimpan hasil generate dokumen.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="row g-4 mb-4">
        {{-- KIRI: Konfigurasi Google Drive --}}
        <div class="col-lg-7 d-flex">
            <div class="card h-100 w-100">
                <div class="card-header"><span class="hdr-title"><i class="bi bi-sliders"></i> Konfigurasi Google Drive</span></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Hubungkan akun Google Drive untuk menyimpan hasil generate dokumen secara otomatis.</p>

                    <div class="info-panel">
                        <i class="bi bi-info-circle-fill"></i>
                        <p>Akun Google Drive yang terhubung akan digunakan sebagai penyimpanan hasil <strong>generate dokumen</strong>. Pastikan akun memiliki akses ke folder tujuan.</p>
                    </div>

                    <div class="conn-strip">
                        <div>
                            <span class="conn-label">Email Google</span>
                            <span class="conn-value">{{ $connected ? ($driveToken->drive_email ?? '(email tidak diketahui)') : 'Belum terhubung' }}</span>
                        </div>
                        <div>
                            <span class="conn-label">Status Koneksi</span>
                            <span>
                                @if ($connected && !$tokenExpired)
                                    <span class="status-badge ok"><i class="bi bi-check-circle-fill"></i> Terhubung</span>
                                @elseif ($connected)
                                    <span class="status-badge warn"><i class="bi bi-exclamation-triangle-fill"></i> Token Kadaluarsa</span>
                                @else
                                    <span class="status-badge off"><i class="bi bi-dash-circle"></i> Belum Terhubung</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Subbagian: Kredensial OAuth Client --}}
                    <div class="section-head">
                        <h6><i class="bi bi-key me-1 text-primary"></i> Kredensial OAuth Client</h6>
                        @if ($clientExists)
                            <span class="status-badge ok"><i class="bi bi-check-circle-fill"></i> Terpasang</span>
                        @else
                            <span class="status-badge warn"><i class="bi bi-exclamation-triangle-fill"></i> Belum</span>
                        @endif
                    </div>

                    <details class="oauth-guide">
                        <summary>Panduan mendapatkan file OAuth Client</summary>
                        <ol>
                            <li>Buka <a href="https://console.cloud.google.com" target="_blank" rel="noopener">Google Cloud Console</a></li>
                            <li>Pilih Project atau buat project baru</li>
                            <li>Masuk ke <strong>APIs &amp; Services</strong> &rarr; <strong>Credentials</strong></li>
                            <li>Klik <strong>+ Create Credentials</strong> &rarr; <strong>OAuth Client ID</strong></li>
                            <li>Pilih tipe <strong>"Web application"</strong></li>
                            <li>Di bagian <strong>Authorized redirect URIs</strong>, tambahkan: <code>{{ route('admin.google.callback') }}</code></li>
                            <li>Klik <strong>Create</strong> &rarr; download file <strong>.json</strong></li>
                            <li>Upload file tersebut melalui form di bawah</li>
                        </ol>
                    </details>

                    <form method="POST" action="{{ route('admin.upload-oauth-client') }}" enctype="multipart/form-data" id="uploadOauthForm">
                        @csrf
                        <div class="row align-items-end g-3">
                            <div class="col-md-7">
                                <label class="form-label" for="client_file">File OAuth Client (.json)</label>
                                <input type="file" name="client_file" id="client_file" class="form-control" accept=".json,.txt" required>
                                <div class="form-text">File JSON hasil download dari Google Cloud Console.</div>
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-upload me-1"></i> Upload
                                </button>
                                @if ($clientExists)
                                    <small class="text-success d-block mt-1"><i class="bi bi-check-circle me-1"></i>Sudah terpasang. Upload ulang untuk mengganti.</small>
                                @endif
                            </div>
                        </div>
                    </form>

                    <hr class="divider">

                    {{-- Subbagian: Folder Penyimpanan --}}
                    <div class="section-head">
                        <h6><i class="bi bi-folder2-open me-1 text-primary"></i> Folder Penyimpanan</h6>
                    </div>

                    @if (!$driveToken)
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            Hubungkan akun Google Drive terlebih dahulu pada panel Status &amp; Uji Koneksi.
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.save-drive-config') }}" id="saveDriveForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="folder_link">Link Folder Google Drive</label>
                                <input type="url" name="folder_link" id="folder_link" class="form-control" value="{{ $driveConfig->folder_link ?? '' }}" placeholder="https://drive.google.com/drive/folders/...">
                                <div class="form-text">Masukkan link folder yang ingin dipindai. Contoh: https://drive.google.com/drive/folders/abc123</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-floppy me-1"></i> Simpan Konfigurasi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- KANAN: Status & Uji Koneksi --}}
        <div class="col-lg-5 d-flex">
            <div class="card h-100 w-100">
                <div class="card-header"><span class="hdr-title"><i class="bi bi-plug"></i> Status &amp; Uji Koneksi</span></div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small mb-3">Pastikan koneksi ke Google Drive berjalan dengan baik.</p>

                    <div class="status-hero {{ $heroState }}">
                        <span class="status-icon"><i class="bi bi-google"></i></span>
                        <div>
                            @if ($connected && !$tokenExpired)
                                <span class="status-badge ok"><i class="bi bi-check-circle-fill"></i> Terhubung</span>
                            @elseif ($connected)
                                <span class="status-badge warn"><i class="bi bi-exclamation-triangle-fill"></i> Token Kadaluarsa</span>
                            @else
                                <span class="status-badge off"><i class="bi bi-dash-circle"></i> Belum Terhubung</span>
                            @endif
                        </div>
                        <p class="status-msg">
                            @if (!$clientExists)
                                Upload OAuth Client JSON terlebih dahulu untuk mulai menghubungkan akun.
                            @elseif (!$connected)
                                Akun Google Drive belum terhubung.
                            @elseif ($tokenExpired)
                                Token akses sudah kadaluarsa. Sistem akan memperbarui otomatis saat digunakan.
                            @else
                                Koneksi ke Google Drive aktif dan siap digunakan.
                            @endif
                        </p>
                    </div>

                    <div class="status-list">
                        <div class="status-row">
                            <span class="label">Email Google</span>
                            <span class="value">{{ $connected ? ($driveToken->drive_email ?? '(tidak diketahui)') : 'Belum terhubung' }}</span>
                        </div>
                        <div class="status-row">
                            <span class="label">Status Token OAuth</span>
                            <span class="value">
                                @if (!$connected)
                                    <span class="text-muted">Tidak ada token</span>
                                @elseif ($tokenExpired)
                                    <span class="text-danger">Kadaluarsa</span>
                                @else
                                    <span class="text-success">Aktif</span>
                                @endif
                            </span>
                        </div>
                        <div class="status-row">
                            <span class="label">OAuth Client</span>
                            <span class="value">{{ $clientExists ? 'Terpasang' : 'Belum diupload' }}</span>
                        </div>
                    </div>

                    <div class="mt-auto d-grid gap-2">
                        @if ($driveToken)
                            <a href="{{ route('admin.drive-config') }}" class="btn btn-outline-primary" id="recheckBtn">
                                <i class="bi bi-arrow-clockwise me-1"></i> Cek Ulang Koneksi
                            </a>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disconnectModal">
                                <i class="bi bi-box-arrow-left me-1"></i> Putuskan Koneksi
                            </button>
                        @elseif ($clientExists)
                            <a href="{{ route('admin.google.redirect') }}" class="btn btn-primary">
                                <i class="bi bi-google me-1"></i> Hubungkan Google Drive
                            </a>
                            <a href="{{ route('admin.drive-config') }}" class="btn btn-outline-primary" id="recheckBtn">
                                <i class="bi bi-arrow-clockwise me-1"></i> Cek Ulang Koneksi
                            </a>
                        @else
                            <button type="button" class="btn btn-primary" disabled title="Upload OAuth Client terlebih dahulu">
                                <i class="bi bi-google me-1"></i> Hubungkan Google Drive
                            </button>
                            <a href="{{ route('admin.drive-config') }}" class="btn btn-outline-primary" id="recheckBtn">
                                <i class="bi bi-arrow-clockwise me-1"></i> Cek Ulang Koneksi
                            </a>
                            <p class="text-muted small text-center mb-0 mt-1">Upload OAuth Client pada panel Konfigurasi terlebih dahulu.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Informasi Sistem --}}
    <div class="card">
        <div class="card-header"><span class="hdr-title"><i class="bi bi-info-circle"></i> Informasi Sistem</span></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="tile-icon"><i class="bi bi-hdd-stack"></i></span>
                        <div>
                            <div class="tile-label">Penyimpanan Generate</div>
                            <div class="tile-value">{{ $config['folder_size'] ?? '0 B' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="tile-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <div>
                            <div class="tile-label">Jumlah File</div>
                            <div class="tile-value">{{ number_format($config['file_count'] ?? 0, 0, ',', '.') }} file</div>
                            <div class="tile-sub">Folder hasil generate</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="tile-icon"><i class="bi bi-clock-history"></i></span>
                        <div>
                            <div class="tile-label">Terakhir Terhubung</div>
                            <div class="tile-value">{{ $connected && $driveToken->updated_at ? $driveToken->updated_at->translatedFormat('d M Y H:i') : 'Belum tersedia' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="info-tile">
                        <span class="tile-icon"><i class="bi bi-shield-check"></i></span>
                        <div>
                            <div class="tile-label">Status Layanan</div>
                            <div class="mt-1">
                                <span class="status-badge {{ $svcState }}"><i class="bi {{ $svcState === 'ok' ? 'bi-check-circle-fill' : ($svcState === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-dash-circle') }}"></i> {{ $svcLabel }}</span>
                            </div>
                            <div class="tile-sub">{{ $svcNote }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal putuskan koneksi (fungsi & id dipertahankan) --}}
<form method="POST" action="{{ route('admin.google.disconnect') }}" id="disconnectForm">
    @csrf
</form>
<div class="modal fade" id="disconnectModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="bi bi-box-arrow-left text-danger" style="font-size:3rem;"></i>
                <h5 class="mt-3">Putuskan Koneksi</h5>
                <p class="text-muted mb-0 small">Koneksi Google Drive akan diputus. Scan dan upload tidak bisa jalan sampai dihubungkan lagi.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger px-4" id="confirmDisconnectBtn"><i class="bi bi-box-arrow-left me-1"></i>Ya, Putuskan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        function setLoading(btn, label) {
            if (!btn) return;
            btn.dataset.original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + label;
        }

        var uploadForm = document.getElementById('uploadOauthForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function () {
                var btn = uploadForm.querySelector('button[type="submit"]');
                setLoading(btn, 'Mengunggah...');
            });
        }

        var saveForm = document.getElementById('saveDriveForm');
        if (saveForm) {
            saveForm.addEventListener('submit', function () {
                var btn = saveForm.querySelector('button[type="submit"]');
                setLoading(btn, 'Menyimpan...');
            });
        }

        var confirmBtn = document.getElementById('confirmDisconnectBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                setLoading(confirmBtn, 'Memutuskan...');
                document.getElementById('disconnectForm').submit();
            });
        }

        var recheck = document.getElementById('recheckBtn');
        if (recheck) {
            recheck.addEventListener('click', function (e) {
                if (recheck.dataset.loading) { e.preventDefault(); return; }
                recheck.dataset.loading = '1';
                var label = 'Mengecek...';
                recheck.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + label;
            });
        }
    })();
</script>
@endpush
