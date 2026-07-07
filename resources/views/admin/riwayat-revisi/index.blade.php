@extends('layouts.main')

@section('page-title', 'Riwayat Revisi')

@push('styles')
<style>
    .status-stripe { width: 4px; border-radius: 2px; flex-shrink: 0; }
    .status-stripe.approved { background: #62B846; }
    .status-stripe.revision { background: #ef4444; }
    .status-stripe.pending { background: #F58220; }

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

    .filter-bar { background: #f8fafc; border-radius: 8px; padding: .75rem 1rem; }
    .filter-bar .form-control, .filter-bar .form-select { font-size: .8rem; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Riwayat Revisi</h4>
            <p class="text-muted small mb-0 mt-1">Riwayat revisi dan persetujuan dokumen.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    {{-- Filter --}}
    <form method="GET" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-search text-muted"></i>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari dokumen atau user..." value="{{ request('search') }}" style="width:200px;">
        </div>
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-folder text-muted"></i>
            <select name="folder" class="form-select form-select-sm" style="width:auto;">
                <option value="">Semua Folder</option>
                @foreach ($folders ?? [] as $f)
                    <option value="{{ $f }}" {{ request('folder') == $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        @if (request('search') || request('folder'))
            <a href="{{ route('admin.riwayat-revisi') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset</a>
        @endif
        <span class="badge bg-light text-dark ms-auto px-3 py-2">{{ $uploads->total() }} dokumen</span>
    </form>

    @php
        $grouped = ($uploads ?? collect())->groupBy(function ($u) {
            return $u->subfolder ?? '(folder utama)';
        });
    @endphp

    @forelse ($grouped as $folder => $items)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2 py-2" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#folder{{ Str::slug(md5($folder)) }}">
                <i class="bi bi-folder2-open text-primary"></i>
                <span class="fw-semibold small">{{ $folder }}</span>
                <span class="badge bg-light text-dark ms-auto">{{ $items->count() }} dokumen</span>
                <i class="bi bi-chevron-down text-muted small"></i>
            </div>
            <div class="collapse show" id="folder{{ Str::slug(md5($folder)) }}">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:24px;"></th>
                                <th>Dokumen</th>
                                <th style="width:140px;">User</th>
                                <th style="width:110px;">Status</th>
                                <th style="width:130px;">Tanggal</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($items as $upload)
                                @php
                                    $revisions = $upload->revisionHistories ?? collect();
                                    $catatan = is_array($upload->catatan_admin) ? $upload->catatan_admin : [];
                                    $revId = 'rev-' . $upload->id;
                                @endphp
                                <tr class="align-middle">
                                    <td class="p-0" style="width:24px;">
                                        <div class="status-stripe {{ $upload->status }}" style="height:2.5rem;margin:0 auto;"></div>
                                    </td>
                                    <td>
                                        <span class="fw-medium small">{{ $upload->filename ?? '-' }}</span>
                                        @if (count($catatan) > 0)
                                            <div class="text-muted small mt-1">
                                                <i class="bi bi-chat-dots me-1"></i>
                                                @foreach ($catatan as $note)
                                                    <div class="ms-3">- {{ $note }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $upload->user->nama ?? 'Eksternal' }}</td>
                                    <td>
                                        @if ($upload->status === 'approved')
                                            <span class="badge badge-approved"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
                                        @elseif ($upload->status === 'revision')
                                            <span class="badge badge-revision"><i class="bi bi-pencil me-1"></i>Revisi</span>
                                        @else
                                            <span class="badge badge-pending"><i class="bi bi-clock me-1"></i>Menunggu</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $upload->created_at ? $upload->created_at->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        @if ($revisions->count() > 0)
                                            <button class="btn btn-sm btn-link text-muted p-1" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $revId }}" title="Lihat riwayat revisi">
                                                <i class="bi bi-arrow-clockwise"></i> <span class="small">{{ $revisions->count() }}</span>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @if ($revisions->count() > 0)
                                    <tr class="collapse" id="{{ $revId }}">
                                        <td colspan="6" class="p-0 border-0">
                                            <div class="rev-timeline mx-3 my-2">
                                                @foreach ($revisions as $rev)
                                                    @php
                                                        $revNotes = is_array($rev->catatan_admin) ? $rev->catatan_admin : [];
                                                    @endphp
                                                    <div class="rev-item d-flex align-items-start gap-2">
                                                        <div class="rev-dot revision flex-shrink-0"></div>
                                                        <div class="small flex-grow-1">
                                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                                <span class="fw-medium">{{ $rev->nama_file_sebelumnya ?? 'Dokumen' }}</span>
                                                                <span class="text-muted">{{ $rev->tanggal ?? ($rev->created_at ? $rev->created_at->format('d/m/Y H:i') : '-') }}</span>
                                                            </div>
                                                            @if (count($revNotes) > 0)
                                                                <div class="text-muted mt-1">
                                                                    @foreach ($revNotes as $note)
                                                                        <div><i class="bi bi-chat-quote me-1"></i>{{ $note }}</div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="mb-0">Belum ada riwayat revisi.</p>
                </div>
            </div>
        </div>
    @endforelse

    @if ($uploads && $uploads->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection
