@extends('layouts.main')

@section('page-title', 'Riwayat Berkas')

@push('styles')
<style>
    .rev-timeline { position: relative; padding-left: 1.25rem; }
    .rev-timeline::before {
        content: ''; position: absolute; left: 5px; top: 8px; bottom: 8px;
        width: 2px; background: #e2e8f0; border-radius: 1px;
    }
    .rev-timeline .rev-item { position: relative; padding-bottom: .5rem; }
    .rev-timeline .rev-item:last-child { padding-bottom: 0; }
    .rev-timeline .rev-dot {
        position: absolute; left: -1.05rem; top: 6px;
        width: 8px; height: 8px; border-radius: 50%;
        background: #94a3b8; border: 2px solid #fff;
    }
    .rev-timeline .rev-dot.revision { background: #ef4444; }
    .rev-timeline .rev-dot.approved { background: #62B846; }
    .rev-timeline .rev-dot.initial { background: var(--primary); }
    .rev-note {
        background: #f8fafc;
        border-left: 3px solid #ef4444;
        border-radius: 4px;
        padding: .5rem .75rem;
        margin-top: .25rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Riwayat Berkas</h4>
            <p class="text-muted small mb-0 mt-1">Lihat seluruh riwayat pengiriman dan revisi berkas kamu.</p>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('riwayat.index') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama file..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>Butuh Revisi</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="folder" class="form-select">
                            <option value="">Semua Folder</option>
                            @foreach ($folders as $f)
                                <option value="{{ $f }}" {{ request('folder') == $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    </div>
                </div>
                @if (request('search') || request('status') || request('folder'))
                    <a href="{{ route('riwayat.index') }}" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-x-lg me-1"></i> Reset</a>
                @endif
            </form>
        </div>
    </div>

    {{-- Daftar --}}
    @forelse ($uploads as $upload)
        @php
            $revisionCount = $upload->revisionHistories->count();
            $badgeClass = $upload->status == 'pending' ? 'badge-pending' : ($upload->status == 'approved' ? 'badge-approved' : 'badge-revision');
            $statusLabel = $statusLabelMap[$upload->status] ?? $upload->status;
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <i class="bi bi-file-earmark me-1"></i> {{ $upload->filename }}
                    @if ($revisionCount > 0)
                        <span class="badge bg-secondary ms-2">{{ $revisionCount }} revisi</span>
                    @endif
                </div>
                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                    <span class="chip chip-subfolder"><i class="bi bi-folder"></i> {{ $upload->subfolder ?? '(folder utama)' }}</span>
                    <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Kirim: {{ $upload->created_at ? $upload->created_at->format('d/m/Y H:i') : '-' }}</span>
                </div>

                {{-- Timeline toggle --}}
                <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#riwayatTimeline{{ $upload->id }}" aria-expanded="false">
                    <i class="bi bi-chevron-down me-1"></i> Lihat Riwayat Revisi
                </button>

                <div class="collapse mt-3" id="riwayatTimeline{{ $upload->id }}">
                    <div class="rev-timeline">
                        {{-- Pengiriman Awal --}}
                        <div class="rev-item d-flex align-items-start gap-2">
                            <div class="rev-dot initial flex-shrink-0"></div>
                            <div class="small flex-grow-1">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="fw-medium">Pengiriman Awal</span>
                                    <span class="text-muted">{{ $upload->tanggal_kirim_awal ?: ($upload->created_at ? $upload->created_at->format('d-m-Y H:i') : '-') }}</span>
                                </div>
                                <div class="text-muted">{{ $upload->filename }}</div>
                            </div>
                        </div>

                        {{-- Riwayat Revisi --}}
                        @foreach ($upload->revisionHistories as $i => $rev)
                            <div class="rev-item d-flex align-items-start gap-2">
                                <div class="rev-dot revision flex-shrink-0"></div>
                                <div class="small flex-grow-1">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="fw-medium">Revisi ke-{{ $i + 1 }}</span>
                                        <span class="text-muted">{{ $rev->tanggal ?? ($rev->created_at ? $rev->created_at->format('d-m-Y H:i') : '-') }}</span>
                                    </div>
                                    <div class="text-muted">{{ $rev->nama_file_sebelumnya ?? $upload->filename }}</div>
                                    @if (!empty($rev->catatan_admin))
                                        <div class="rev-note">
                                            <i class="bi bi-chat-dots text-danger me-1"></i>
                                            @foreach ((array) $rev->catatan_admin as $note)
                                                @if (trim($note))
                                                    <div>{{ trim($note) }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        {{-- Status Akhir --}}
                        @if ($upload->status == 'approved')
                            <div class="rev-item d-flex align-items-start gap-2">
                                <div class="rev-dot approved flex-shrink-0"></div>
                                <div class="small flex-grow-1">
                                    <span class="fw-medium text-success">✅ Disetujui</span>
                                </div>
                            </div>
                        @elseif ($upload->status == 'revision')
                            <div class="rev-item d-flex align-items-start gap-2">
                                <div class="rev-dot revision flex-shrink-0"></div>
                                <div class="small flex-grow-1">
                                    <span class="fw-medium text-danger">Butuh Revisi</span>
                                    <span class="text-muted"> — Silakan upload revisi melalui halaman Status Berkas</span>
                                </div>
                            </div>
                        @else
                            <div class="rev-item d-flex align-items-start gap-2">
                                <div class="rev-dot flex-shrink-0" style="background: #F58220;"></div>
                                <div class="small flex-grow-1">
                                    <span class="fw-medium text-warning">Menunggu Review Admin</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p class="mb-0">Belum ada riwayat berkas.</p>
            </div>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($uploads->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection