@extends('layouts.main')

@section('page-title', 'Status Berkas')

@push('styles')
<style>
    .card-status .card-header {
        padding: .5rem .75rem;
        font-size: .8rem;
    }
    .card-status .card-body {
        padding: .75rem;
    }
    .card-status .small,
    .card-status .text-muted {
        font-size: .75rem;
    }
    .card-status .chip {
        font-size: .7rem;
        padding: .15rem .5rem;
    }
    .card-status .btn-sm {
        font-size: .72rem;
        padding: .25rem .55rem;
    }
    .card-status .alert-danger {
        font-size: .78rem;
        padding: .5rem .65rem;
    }
    .card-status .form-label {
        font-size: .72rem;
    }
    .card-status .form-control {
        font-size: .78rem;
        padding: .3rem .5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Status Berkas</h4>
            <p class="text-muted small mb-0 mt-1">Pantau status berkas yang kamu kirim.</p>
        </div>
        <a href="{{ route('status.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @if (session('upload_result'))
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2">{{ session('upload_result') }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endif

    @php $currentSection = ''; @endphp

    @forelse ($kejadianPerDokumen ?? [] as $doc)
        @php
            $sectionKey = $doc['status'] ?? 'pending';
        @endphp

        @if ($sectionKey !== $currentSection)
            @php $currentSection = $sectionKey; @endphp
            <div class="d-flex align-items-center gap-2 mb-3 mt-4">
                @if ($sectionKey === 'revision')
                    <span class="badge badge-revision" style="font-size:.85rem;padding:.4rem .8rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i> Butuh Revisi
                    </span>
                @elseif ($sectionKey === 'pending')
                    <span class="badge badge-pending" style="font-size:.85rem;padding:.4rem .8rem;">
                        <i class="bi bi-clock me-1"></i> Menunggu
                    </span>
                @else
                    <span class="badge badge-approved" style="font-size:.85rem;padding:.4rem .8rem;">
                        <i class="bi bi-check-circle me-1"></i> Disetujui
                    </span>
                @endif
            </div>
        @endif

        <div class="card card-status mb-2">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <i class="bi bi-file-earmark me-1"></i> {{ $doc['induk'] ?? 'Dokumen' }}
                    <span class="chip chip-subfolder small ms-2"><i class="bi bi-folder"></i> {{ $doc['subfolder'] ?? '(folder utama)' }}</span>
                </div>
                @php
                    $badgeClass = $doc['statusLabel'] == 'Menunggu' ? 'badge-pending' : ($doc['statusLabel'] == 'Disetujui' ? 'badge-approved' : 'badge-revision');
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $doc['statusLabel'] ?? 'Pending' }}</span>
            </div>
            <div class="card-body">
                {{-- Timeline (collapse) --}}
                <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#timelineUser{{ $doc['uploadId'] }}" aria-expanded="false">
                    <i class="bi bi-chevron-down me-1"></i> Lihat Riwayat Pengiriman
                </button>
                <div class="collapse mt-2" id="timelineUser{{ $doc['uploadId'] }}">
                    @if (!empty($doc['kejadian']))
                        <div class="timeline">
                            @foreach ($doc['kejadian'] as $kej)
                                <div class="timeline-item">
                                    <div class="dot"></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="small">{{ $kej['jenis'] ?? '-' }}</strong>
                                        <span class="time-text">{{ $kej['tanggal'] ?? '-' }}</span>
                                    </div>
                                    <div class="text-muted small">{{ $kej['nama'] ?? '-' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @php
                    $relatedUpload = $uploads->firstWhere('id', $doc['uploadId']);
                @endphp

                {{-- Catatan Admin & Upload Revisi (hanya untuk status 'revision') --}}
                @if ($relatedUpload && $relatedUpload->status == 'revision')
                    {{-- Catatan Admin (collapse) --}}
                    @if (!empty($relatedUpload->catatan_admin))
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#catatan{{ $relatedUpload->id }}" aria-expanded="false">
                                <i class="bi bi-chat-dots me-1"></i> Lihat Catatan Admin
                            </button>
                            <div class="collapse mt-2" id="catatan{{ $relatedUpload->id }}">
                                <div class="alert alert-danger mb-0">
                                    <ul class="mb-0 ps-3">
                                        @foreach ((array) $relatedUpload->catatan_admin as $note)
                                            @if (trim($note))
                                                <li>{{ trim($note) }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @if ($relatedUpload->notifikasi_pdf && file_exists($relatedUpload->notifikasi_pdf))
                                    <a href="{{ route('status.download-notif', $relatedUpload->id) }}" class="btn btn-sm btn-outline-danger mt-2">
                                        <i class="bi bi-file-pdf me-1"></i> Download Notifikasi Revisi (PDF)
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Upload Revisi --}}
                    <div class="mt-3 p-3 bg-light rounded">
                        <form method="POST" action="{{ route('dashboard.upload-revisi') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="upload_id" value="{{ $relatedUpload->id }}">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label mb-0">Upload File Revisi (.docx / .pdf)</label>
                                    <input type="file" name="file" class="form-control" accept=".docx,.pdf" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label mb-0">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                                    <textarea name="catatan" class="form-control" rows="1" placeholder="Catatan untuk Admin..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-arrow-repeat me-1"></i> Kirim Revisi</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p class="mb-0">Belum ada riwayat pengiriman.</p>
            </div>
        </div>
    @endforelse

    @if ($uploads->hasPages())
        <div class="mt-4">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection