@extends('layouts.main')

@section('page-title', 'Riwayat Revisi')

@push('styles')
<style>
    .log-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; font-size: .9rem;
    }
    .log-icon.approve { background: #d4edda; color: #155724; }
    .log-icon.revision { background: #f8d7da; color: #721c24; }

    .log-card { transition: box-shadow .15s; }
    .log-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }

    .date-separator {
        display: flex; align-items: center; gap: 1rem;
        font-size: .8rem; font-weight: 600; color: #64748b;
        padding: .5rem 0;
    }
    .date-separator::after {
        content: ''; flex: 1; height: 1px; background: #e2e8f0;
    }

    .filter-bar { background: #f8fafc; border-radius: 8px; padding: .75rem 1rem; }
    .filter-bar .form-control, .filter-bar .form-select { font-size: .8rem; }

    .log-note {
        background: #f8fafc; border-left: 3px solid #ef4444;
        border-radius: 4px; padding: .5rem .75rem; margin-top: .35rem;
        font-size: .8rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Riwayat Revisi</h4>
            <p class="text-muted small mb-0 mt-1">Log aktivitas admin — approve dan permintaan revisi.</p>
        </div>
        <span class="badge bg-light text-dark px-3 py-2">{{ $uploads->total() }} berkas</span>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    {{-- Filter --}}
    <form method="GET" class="filter-bar d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-search text-muted"></i>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari dokumen atau user..." value="{{ request('search') }}" style="width:180px;">
        </div>
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-person text-muted"></i>
            <select name="user_id" class="form-select form-select-sm" style="width:150px;">
                <option value="">Semua User</option>
                @foreach ($users ?? [] as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-funnel text-muted"></i>
            <select name="activity" class="form-select form-select-sm" style="width:150px;">
                <option value="">Semua Aktivitas</option>
                <option value="disetujui" {{ request('activity') == 'disetujui' ? 'selected' : '' }}>✅ Disetujui</option>
                <option value="minta_revisi" {{ request('activity') == 'minta_revisi' ? 'selected' : '' }}>📝 Minta Revisi</option>
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-folder text-muted"></i>
            <select name="folder" class="form-select form-select-sm" style="width:150px;">
                <option value="">Semua Folder</option>
                @foreach ($folders ?? [] as $f)
                    <option value="{{ $f }}" {{ request('folder') == $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex align-items-center gap-1">
            <i class="bi bi-calendar3 text-muted"></i>
            <input type="date" name="date_from" class="form-control form-control-sm" style="width:140px;" value="{{ request('date_from') }}">
            <span class="text-muted small">–</span>
            <input type="date" name="date_to" class="form-control form-control-sm" style="width:140px;" value="{{ request('date_to') }}">
        </div>
        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
        @if (request('search') || request('user_id') || request('activity') || request('folder') || request('date_from') || request('date_to'))
            <a href="{{ route('admin.riwayat-revisi') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset</a>
        @endif
    </form>

    {{-- Log --}}
    @php
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $grouped = $activities->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item['timestamp'])->format('Y-m-d');
        });
    @endphp

    @forelse ($grouped as $date => $items)
        @php
            $carbon = \Carbon\Carbon::parse($date);
            if ($date === $today) {
                $label = 'Hari Ini';
            } elseif ($date === $yesterday) {
                $label = 'Kemarin';
            } else {
                $label = $carbon->isoFormat('D MMMM YYYY');
            }
        @endphp
        <div class="date-separator">{{ $label }}</div>

        @foreach ($items as $activity)
            <div class="card mb-2 log-card">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="log-icon {{ $activity['type'] === 'disetujui' ? 'approve' : 'revision' }}">
                            {!! $activity['type'] === 'disetujui' ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-pencil"></i>' !!}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-medium small">
                                    {{ $activity['type'] === 'disetujui' ? 'Disetujui' : 'Minta Revisi' }}
                                </span>
                                <span class="text-muted small">
                                    {{ \Carbon\Carbon::parse($activity['timestamp'])->format('H:i') }}
                                </span>
                                <span class="text-muted small">•</span>
                                <span class="small text-truncate">{{ $activity['filename'] }}</span>
                                <span class="chip chip-subfolder small py-0 px-1">
                                    <i class="bi bi-folder"></i> {{ $activity['subfolder'] }}
                                </span>
                            </div>
                            <div class="small text-muted">
                                @if ($activity['type'] === 'minta_revisi' && $activity['assigned_nama'])
                                    <i class="bi bi-person-check me-1"></i> Ditugaskan ke: {{ $activity['assigned_nama'] }}
                                @endif
                            </div>
                            @if ($activity['type'] === 'minta_revisi' && !empty($activity['catatan']))
                                <button class="btn btn-sm btn-link text-danger p-0 mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#catatanAdm{{ $loop->parent->index }}_{{ $loop->index }}">
                                    <i class="bi bi-chat-dots me-1"></i> Lihat Catatan
                                </button>
                                <div class="collapse mt-1" id="catatanAdm{{ $loop->parent->index }}_{{ $loop->index }}">
                                    <div class="log-note">
                                        @foreach ((array) $activity['catatan'] as $note)
                                            @if (trim($note))
                                                <div>{{ trim($note) }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @empty
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="mb-0">Belum ada aktivitas admin.</p>
                </div>
            </div>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($uploads->hasPages())
        <div class="d-flex justify-content-center mt-3">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection