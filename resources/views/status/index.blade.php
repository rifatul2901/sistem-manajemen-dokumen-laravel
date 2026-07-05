@extends('layouts.main')

@section('page-title', 'Status Berkas')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Riwayat Pengiriman &amp; Revisi</h4>
            <p class="text-muted small mb-0 mt-1">Pantau status berkas yang pernah kamu upload.</p>
        </div>
        <a href="{{ route('status.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    @forelse ($kejadianPerDokumen ?? [] as $doc)
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span><i class="bi bi-file-earmark me-1"></i> {{ $doc['induk'] ?? 'Dokumen' }}</span>
                @php
                    $badgeClass = $doc['statusLabel'] == 'Menunggu' ? 'badge-pending' : ($doc['statusLabel'] == 'Disetujui' ? 'badge-approved' : 'badge-revision');
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $doc['statusLabel'] ?? 'Pending' }}</span>
            </div>
            <div class="card-body">
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

                @php
                    $relatedUpload = $uploads->first(function($u) use ($doc) {
                        return $u->filename === $doc['induk'] || (isset($u->revisionHistories[0]) && $u->revisionHistories[0]->nama_file_sebelumnya === $doc['induk']);
                    });
                @endphp

                @if ($relatedUpload && $relatedUpload->status == 'revision' && !empty($relatedUpload->catatan_admin))
                    <div class="alert alert-danger mt-3 mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-chat-dots mt-1"></i>
                            <div>
                                <strong>Catatan Admin:</strong>
                                <ul class="mb-0 mt-1 ps-3">
                                    @foreach ((array) $relatedUpload->catatan_admin as $note)
                                        @if (trim($note))
                                            <li>{{ trim($note) }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @if ($relatedUpload->notifikasi_pdf && file_exists($relatedUpload->notifikasi_pdf))
                        <a href="{{ route('status.download-notif', $relatedUpload->id) }}" class="btn btn-sm btn-outline-danger mt-2">
                            <i class="bi bi-file-pdf me-1"></i> Download Notifikasi Revisi (PDF)
                        </a>
                    @endif
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
</div>
@endsection