@extends('layouts.main')

@section('page-title', 'Konfigurasi Google Drive')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Konfigurasi Google Drive</h4>
            <p class="text-muted small mb-0 mt-1">Hubungkan akun Google Drive untuk memindai dan mengelola berkas.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    {{-- Langkah 1: Upload OAuth Client --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill">1</span>
            Upload OAuth Client dari Google Cloud
            @if ($clientExists)
                <span class="badge bg-success ms-auto"><i class="bi bi-check-circle me-1"></i>Terpasang</span>
            @else
                <span class="badge bg-warning text-dark ms-auto"><i class="bi bi-exclamation-triangle me-1"></i>Belum</span>
            @endif
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-info-circle fs-4 flex-shrink-0"></i>
                    <div>
                        <strong>Panduan mendapatkan file OAuth Client:</strong>
                        <ol class="mb-0 mt-2 ps-3">
                            <li>Buka <a href="https://console.cloud.google.com" target="_blank" rel="noopener">Google Cloud Console</a></li>
                            <li>Pilih Project atau buat project baru</li>
                            <li>Masuk ke <strong>APIs & Services</strong> → <strong>Credentials</strong></li>
                            <li>Klik <strong>+ Create Credentials</strong> → <strong>OAuth Client ID</strong></li>
                            <li>Pilih tipe <strong>"Web application"</strong></li>
                            <li>Di bagian <strong>Authorized redirect URIs</strong>, tambahkan:<br>
                                <code class="mt-1 d-inline-block">{{ route('admin.google.callback') }}</code>
                            </li>
                            <li>Klik <strong>Create</strong> → download file <strong>.json</strong></li>
                            <li>Upload file tersebut melalui form di bawah</li>
                        </ol>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.upload-oauth-client') }}" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end g-3">
                    <div class="col-md-6">
                        <label class="form-label">File OAuth Client (.json)</label>
                        <input type="file" name="client_file" class="form-control" accept=".json,.txt" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload me-1"></i> Upload
                        </button>
                    </div>
                    <div class="col-md-3">
                        @if ($clientExists)
                            <small class="text-success d-block mt-1"><i class="bi bi-check-circle me-1"></i>Sudah terpasang. Upload ulang untuk mengganti.</small>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Langkah 2: Hubungkan Google Drive --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill">2</span>
            Hubungkan Akun Google Drive
            @if ($driveToken)
                <span class="badge bg-success ms-auto"><i class="bi bi-check-circle me-1"></i>Tersambung</span>
            @else
                <span class="badge bg-secondary ms-auto"><i class="bi bi-dash-circle me-1"></i>Belum</span>
            @endif
        </div>
        <div class="card-body">
            @if ($driveToken)
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded-3">
                                <i class="bi bi-google fs-3 text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Terhubung sebagai</h6>
                                <p class="mb-0 fw-medium">{{ $driveToken->drive_email ?? '(email tidak diketahui)' }}</p>
                                <small class="text-muted">Status token:
                                    @if ($driveToken->isExpired())
                                        <span class="text-danger">Kadaluarsa</span>
                                    @else
                                        <span class="text-success">Aktif</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.google.disconnect') }}" id="disconnectForm">
                        @csrf
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disconnectModal">
                            <i class="bi bi-box-arrow-left me-1"></i> Putuskan
                        </button>
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
                                    <button type="button" class="btn btn-danger px-4" onclick="document.getElementById('disconnectForm').submit();"><i class="bi bi-box-arrow-left me-1"></i>Ya, Putuskan</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-google display-4 text-muted mb-3 d-block"></i>
                    <p class="mb-2">Akun Google Drive belum terhubung.</p>
                    @if ($clientExists)
                        <a href="{{ route('admin.google.redirect') }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-google me-2"></i> Hubungkan Google Drive
                        </a>
                    @else
                        <button class="btn btn-secondary btn-lg" disabled title="Upload OAuth Client terlebih dahulu">
                            <i class="bi bi-google me-2"></i> Hubungkan Google Drive
                        </button>
                        <p class="text-muted small mt-2">Upload OAuth Client pada langkah 1 terlebih dahulu.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Langkah 3: Konfigurasi Folder Drive --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <span class="badge bg-primary rounded-pill">3</span>
            Konfigurasi Folder Google Drive
        </div>
        <div class="card-body">
            @if (!$driveToken)
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    Hubungkan akun Google Drive terlebih dahulu pada langkah 2.
                </div>
            @else
                <form method="POST" action="{{ route('admin.save-drive-config') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Link Folder Google Drive</label>
                        <input type="url" name="folder_link" class="form-control" value="{{ $driveConfig->folder_link ?? '' }}" placeholder="https://drive.google.com/drive/folders/..." {{ $driveToken ? '' : 'disabled' }}>
                        <div class="form-text">Masukkan link folder yang ingin dipindai. Contoh: https://drive.google.com/drive/folders/abc123</div>
                    </div>
                    <button type="submit" class="btn btn-primary" {{ $driveToken ? '' : 'disabled' }}>
                        <i class="bi bi-floppy me-1"></i> Simpan
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Informasi Sistem --}}
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-info-circle text-muted"></i> Informasi Sistem
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-hdd-stack text-muted"></i>
                        <div>
                            <small class="text-muted d-block">Penyimpanan Generate</small>
                            <span class="fw-medium">{{ $config['folder_size'] ?? '0 B' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-google text-muted"></i>
                        <div>
                            <small class="text-muted d-block">Status OAuth Client</small>
                            <span class="fw-medium">{{ $clientExists ? 'Terpasang' : 'Belum diupload' }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-check text-muted"></i>
                        <div>
                            <small class="text-muted d-block">Akun Google Drive</small>
                            <span class="fw-medium">{{ optional($driveToken)->drive_email ?? 'Belum terhubung' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
