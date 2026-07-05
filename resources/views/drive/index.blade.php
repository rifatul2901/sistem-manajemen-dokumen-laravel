@extends('layouts.main')

@section('page-title', 'Monitoring Drive')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Monitoring Drive</h4>
            <p class="text-muted small mb-0 mt-1">Lihat semua berkas di Google Drive tim beserta statusnya.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('info')
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-search"></i> Pindai Folder Drive
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('drive.scan') }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Link Folder Google Drive <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="url" name="folder_link" class="form-control" placeholder="https://drive.google.com/drive/folders/...">
                        <div class="form-text">Kosongkan untuk menggunakan folder default.</div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Pindai</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (session('scan_result'))
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span><i class="bi bi-hdd-stack me-1"></i> Hasil Pemindaian: {{ session('scan_label', 'folder') }}</span>
                <span class="badge bg-primary">{{ session('scan_count', 0) }} file</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Nama Berkas</th>
                                <th>Subfolder</th>
                                <th>Diupload Oleh</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (session('scan_result') as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-medium">{{ $item['Nama File'] ?? '-' }}</td>
                                    <td><span class="chip chip-subfolder"><i class="bi bi-folder"></i> {{ $item['Subfolder'] ?? '-' }}</span></td>
                                    <td class="text-muted small">{{ $item['Diupload'] ?? '-' }}</td>
                                    <td class="text-muted small">{{ $item['Tanggal'] ?? '-' }}</td>
                                    <td>
                                        @php $s = $item['Status'] ?? 'Tidak Tercatat'; @endphp
                                        <span class="badge {{ $s == 'Menunggu' ? 'badge-pending' : ($s == 'Disetujui' ? 'badge-approved' : ($s == 'Butuh Revisi' ? 'badge-revision' : 'badge-untracked')) }}">
                                            {{ $s }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-hdd-stack"></i>
                <p class="mb-0">Belum ada data pemindaian. Silakan pindai folder terlebih dahulu.</p>
            </div>
        </div>
    @endif
</div>
@endsection