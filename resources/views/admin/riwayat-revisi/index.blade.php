@extends('layouts.main')

@section('page-title', 'Riwayat Revisi')

@push('styles')
<style>
    /* === Riwayat Revisi — scoped theme (konsisten dengan dashboard SIMADO) === */
    .riwayat-page { --rv-border: #E2EAF5; --rv-muted: #64748B; }

    /* Header halaman */
    .riwayat-page .riwayat-head { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
    .riwayat-page .riwayat-head .head-icon {
        width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.25rem;
    }
    .riwayat-page .riwayat-head .page-title { font-size: 1.35rem; }
    .riwayat-page .riwayat-head p { color: var(--rv-muted); font-size: .875rem; margin: .15rem 0 0; }
    .riwayat-page .count-badge {
        margin-left: auto; display: inline-flex; align-items: center; gap: .35rem;
        background: #E7F3FC; color: #0369a1; font-size: .78rem; font-weight: 500;
        padding: .4em .8em; border-radius: 62.4375rem;
    }

    /* Panel filter */
    .riwayat-page .filter-panel {
        background: #fff; border: 1px solid var(--rv-border); border-radius: 0.75rem;
        padding: .8rem .9rem; margin-bottom: 1rem;
        display: flex; flex-wrap: wrap; align-items: flex-end; gap: .6rem;
    }
    .riwayat-page .filter-field { flex: 1 1 11.25rem; min-width: 0; }
    .riwayat-page .filter-field .filter-label {
        display: block; font-size: .68rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .04em; color: #94a3b8; margin-bottom: .25rem;
    }
    .riwayat-page .filter-input { position: relative; }
    .riwayat-page .filter-input > i {
        position: absolute; left: .65rem; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: .8rem; pointer-events: none;
    }
    .riwayat-page .filter-input .form-control,
    .riwayat-page .filter-input .form-select { padding-left: 2rem; min-height: 2.5rem; font-size: .82rem; }
    .riwayat-page .filter-dates { flex: 2 1 18.75rem; }
    .riwayat-page .filter-dates .date-row { display: flex; align-items: center; gap: .45rem; }
    .riwayat-page .filter-dates .date-row .filter-input { flex: 1 1 0; min-width: 0; }
    .riwayat-page .filter-dates .date-dash { color: #94a3b8; flex-shrink: 0; }
    .riwayat-page .filter-actions { display: flex; align-items: center; gap: .45rem; }
    .riwayat-page .filter-actions .btn { min-height: 2.5rem; }
    @media (max-width: 575.98px) {
        .riwayat-page .filter-field,
        .riwayat-page .filter-dates { flex: 1 1 100%; }
        .riwayat-page .filter-actions { flex: 1 1 100%; }
        .riwayat-page .filter-actions .btn { flex: 1 1 auto; }
    }

    /* Header kelompok tanggal (collapsible) */
    .riwayat-page .date-sep {
        display: flex; align-items: center; gap: .55rem; width: 100%;
        background: transparent; border: 0; padding: .3rem 0;
        margin: .9rem 0 .55rem; text-align: left; cursor: pointer;
    }
    .riwayat-page .date-sep:first-of-type { margin-top: .1rem; }
    .riwayat-page .date-sep .date-sep-icon {
        width: 1.625rem; height: 1.625rem; border-radius: 0.5rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #EAF2FB; color: var(--primary); font-size: .78rem;
    }
    .riwayat-page .date-sep .date-sep-label { font-size: .82rem; font-weight: 600; color: #334155; white-space: nowrap; }
    .riwayat-page .date-sep:hover .date-sep-label { color: var(--primary); }
    .riwayat-page .date-sep .date-sep-count {
        font-size: .68rem; font-weight: 600; color: #64748b;
        background: #F1F5F9; border-radius: 62.4375rem; padding: .1rem .5rem;
    }
    .riwayat-page .date-sep .date-sep-line { flex: 1; height: 1px; background: var(--rv-border); }
    .riwayat-page .date-sep .date-sep-chev { color: #94a3b8; font-size: .8rem; transition: transform .2s ease; }
    .riwayat-page .date-sep[aria-expanded="false"] .date-sep-chev { transform: rotate(-90deg); }
    .riwayat-page .date-sep:focus-visible { outline: 0.125rem solid var(--primary); outline-offset: 0.125rem; border-radius: 0.375rem; }

    /* Kartu aktivitas (kartu putih, ringkas) */
    .riwayat-page .activity-card {
        display: flex; gap: .7rem; align-items: flex-start;
        background: #fff; border: 1px solid var(--rv-border); border-left: 0.1875rem solid #cbd5e1;
        border-radius: 0.625rem; padding: .6rem .8rem; margin-bottom: 0.5rem;
        transition: box-shadow .15s ease;
    }
    .riwayat-page .activity-card:hover { box-shadow: 0 0.1875rem 0.625rem rgba(15,23,42,.06); }
    .riwayat-page .activity-card.is-approve { border-left-color: #62B846; }
    .riwayat-page .activity-card.is-revision { border-left-color: #ef4444; }

    .riwayat-page .activity-icon {
        width: 2.125rem; height: 2.125rem; border-radius: 0.5625rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: .9rem;
    }
    .riwayat-page .activity-card.is-approve .activity-icon { background: #E6F6EC; color: #16a34a; }
    .riwayat-page .activity-card.is-revision .activity-icon { background: #FEE2E2; color: #dc2626; }

    .riwayat-page .activity-body { flex: 1 1 auto; min-width: 0; }

    .riwayat-page .activity-top { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; }
    .riwayat-page .activity-status {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .7rem; font-weight: 600; padding: .2em .6em; border-radius: 62.4375rem;
    }
    .riwayat-page .activity-card.is-approve .activity-status { background: #E6F6EC; color: #15803d; }
    .riwayat-page .activity-card.is-revision .activity-status { background: #FEE2E2; color: #b91c1c; }
    .riwayat-page .activity-time { font-size: .73rem; color: #94a3b8; display: inline-flex; align-items: center; gap: .2rem; }

    .riwayat-page .activity-doc {
        font-size: .86rem; font-weight: 500; color: #0f172a; margin-top: .2rem;
        overflow-wrap: anywhere; word-break: break-word;
    }

    .riwayat-page .folder-badge {
        display: inline-flex; align-items: center; gap: .25rem; max-width: 15rem;
        background: #E7F3FC; color: #0369a1; font-size: .7rem; font-weight: 500;
        padding: .2em .6em; border-radius: 0.5rem;
    }
    .riwayat-page .folder-badge span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .riwayat-page .assignee { font-size: .73rem; color: var(--rv-muted); display: inline-flex; align-items: center; gap: .25rem; }

    .riwayat-page .btn-note {
        display: inline-flex; align-items: center; margin-left: auto;
        font-size: .7rem; font-weight: 500; color: #b91c1c;
        background: #FEF2F2; border: 1px solid #FADADA; border-radius: 0.5rem; padding: .2rem .55rem;
    }
    .riwayat-page .btn-note:hover { background: #FDE8E8; color: #991b1b; }
    .riwayat-page .activity-note {
        background: #FEF2F2; border-left: 0.1875rem solid #ef4444; border-radius: 0.5rem;
        padding: .55rem .75rem; font-size: .78rem; color: #7f1d1d; margin-top: .5rem;
    }
    .riwayat-page .activity-note > div + div { margin-top: .25rem; }

    /* Pagination ringkas */
    .riwayat-page .rv-pagination {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: .5rem 1rem;
        margin-top: 1rem; padding: .55rem .85rem;
        background: #fff; border: 1px solid var(--rv-border); border-radius: 0.625rem;
    }
    .riwayat-page .rv-page-info { margin: 0; font-size: .8rem; color: var(--rv-muted); line-height: 1.4; }
    .riwayat-page .rv-page-info strong { color: #334155; font-weight: 600; }
    .riwayat-page .rv-pagination .pagination { margin: 0; gap: .25rem; flex-wrap: wrap; }
    .riwayat-page .rv-pagination .page-item .page-link { margin-left: 0; }
    .riwayat-page .rv-pagination .page-link {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 2.375rem; height: 2.375rem; padding: 0 .5rem;
        font-size: .8rem; line-height: 1;
        color: var(--primary); background: #fff;
        border: 1px solid var(--rv-border); border-radius: 0.5rem;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .riwayat-page .rv-pagination .page-item:not(.disabled):not(.active) .page-link:hover {
        background: #EAF2FB; border-color: #B9D9F2; color: #0369a1;
    }
    .riwayat-page .rv-pagination .page-item.active .page-link {
        background: var(--primary); border-color: var(--primary); color: #fff;
        box-shadow: 0 0.125rem 0.375rem rgba(0,146,223,.25);
    }
    .riwayat-page .rv-pagination .page-item.disabled .page-link {
        color: #cbd5e1; background: #F8FAFC; border-color: #E2E8F0;
        pointer-events: none; cursor: default;
    }
    .riwayat-page .rv-pagination .page-link:focus-visible {
        outline: 0.125rem solid var(--primary); outline-offset: 0.125rem; box-shadow: none;
    }
    @media (max-width: 575.98px) {
        .riwayat-page .rv-pagination { justify-content: center; }
        .riwayat-page .rv-page-info { flex: 1 1 100%; text-align: center; }
        .riwayat-page .rv-pagination .pagination { justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 riwayat-page">
    <div class="riwayat-head">
        <span class="head-icon"><i class="bi bi-clock-history"></i></span>
        <div>
            <h4 class="page-title">Riwayat Revisi</h4>
            <p>Catatan aktivitas admin — persetujuan dan permintaan revisi dokumen.</p>
        </div>
        <span class="count-badge"><i class="bi bi-files"></i>{{ $uploads->total() }} berkas</span>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    {{-- Filter --}}
    <form method="GET" class="filter-panel">
        <div class="filter-field">
            <label class="filter-label" for="rvSearch">Pencarian</label>
            <div class="filter-input">
                <i class="bi bi-search"></i>
                <input type="text" name="search" id="rvSearch" class="form-control" placeholder="Cari dokumen atau user..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="filter-field">
            <label class="filter-label" for="rvUser">User</label>
            <div class="filter-input">
                <i class="bi bi-person"></i>
                <select name="user_id" id="rvUser" class="form-select">
                    <option value="">Semua User</option>
                    @foreach ($users ?? [] as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filter-field">
            <label class="filter-label" for="rvActivity">Aktivitas</label>
            <div class="filter-input">
                <i class="bi bi-activity"></i>
                <select name="activity" id="rvActivity" class="form-select">
                    <option value="">Semua Aktivitas</option>
                    <option value="disetujui" {{ request('activity') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="minta_revisi" {{ request('activity') == 'minta_revisi' ? 'selected' : '' }}>Minta Revisi</option>
                </select>
            </div>
        </div>
        <div class="filter-field">
            <label class="filter-label" for="rvFolder">Folder</label>
            <div class="filter-input">
                <i class="bi bi-folder"></i>
                <select name="folder" id="rvFolder" class="form-select">
                    <option value="">Semua Folder</option>
                    @foreach ($folders ?? [] as $f)
                        <option value="{{ $f }}" {{ request('folder') == $f ? 'selected' : '' }}>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="filter-field filter-dates">
            <label class="filter-label">Rentang Tanggal</label>
            <div class="date-row">
                <div class="filter-input">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" aria-label="Tanggal awal">
                </div>
                <span class="date-dash">–</span>
                <div class="filter-input">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" aria-label="Tanggal akhir">
                </div>
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Filter</button>
            @if (request('search') || request('user_id') || request('activity') || request('folder') || request('date_from') || request('date_to'))
                <a href="{{ route('admin.riwayat-revisi') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i> Reset</a>
            @endif
        </div>
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
            $groupId = 'dayGroup' . $loop->index;
        @endphp

        {{-- Header tanggal (terbuka secara default, bisa ditutup) --}}
        <button class="date-sep" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" aria-expanded="true" aria-controls="{{ $groupId }}">
            <span class="date-sep-icon"><i class="bi bi-calendar3"></i></span>
            <span class="date-sep-label">{{ $label }}</span>
            <span class="date-sep-count">{{ $items->count() }}</span>
            <span class="date-sep-line"></span>
            <i class="bi bi-chevron-down date-sep-chev"></i>
        </button>

        <div class="collapse show" id="{{ $groupId }}">
            @foreach ($items as $activity)
                <div class="activity-card {{ $activity['type'] === 'disetujui' ? 'is-approve' : 'is-revision' }}">
                    <span class="activity-icon">
                        {!! $activity['type'] === 'disetujui' ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-pencil"></i>' !!}
                    </span>
                    <div class="activity-body">
                        <div class="activity-top">
                            <span class="activity-status">
                                {!! $activity['type'] === 'disetujui' ? '<i class="bi bi-check-circle-fill"></i> Disetujui' : '<i class="bi bi-pencil-fill"></i> Minta Revisi' !!}
                            </span>
                            <span class="activity-time"><i class="bi bi-clock"></i>{{ \Carbon\Carbon::parse($activity['timestamp'])->format('H:i') }}</span>
                            <span class="folder-badge" title="{{ $activity['subfolder'] }}"><i class="bi bi-folder"></i><span>{{ $activity['subfolder'] }}</span></span>
                            @if ($activity['type'] === 'minta_revisi' && $activity['assigned_nama'])
                                <span class="assignee"><i class="bi bi-person-check"></i> {{ $activity['assigned_nama'] }}</span>
                            @endif
                            @if ($activity['type'] === 'minta_revisi' && !empty($activity['catatan']))
                                <button class="btn btn-note" type="button" data-bs-toggle="collapse" data-bs-target="#catatanAdm{{ $loop->parent->index }}_{{ $loop->index }}" aria-expanded="false">
                                    <i class="bi bi-chat-dots me-1"></i><span class="note-toggle-text">Lihat Catatan</span>
                                </button>
                            @endif
                        </div>
                        <div class="activity-doc" title="{{ $activity['filename'] }}">{{ $activity['filename'] }}</div>
                        @if ($activity['type'] === 'minta_revisi' && !empty($activity['catatan']))
                            <div class="collapse activity-note-collapse" id="catatanAdm{{ $loop->parent->index }}_{{ $loop->index }}">
                                <div class="activity-note">
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
            @endforeach
        </div>
    @empty
        <div class="card">
            <div class="card-body">
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p class="mb-0">Tidak ada riwayat aktivitas yang ditemukan.</p>
                </div>
            </div>
        </div>
    @endforelse

    {{-- Pagination --}}
    {{ $uploads->links('admin.riwayat-revisi.pagination') }}
</div>
@endsection

@push('scripts')
<script>
(function() {
    // Ganti label tombol catatan sesuai keadaan buka/tutup
    document.querySelectorAll('.activity-note-collapse').forEach(function(el) {
        var trigger = document.querySelector('[data-bs-target="#' + el.id + '"]');
        if (!trigger) return;
        var label = trigger.querySelector('.note-toggle-text');
        el.addEventListener('shown.bs.collapse', function() {
            if (label) label.textContent = 'Tutup Catatan';
        });
        el.addEventListener('hidden.bs.collapse', function() {
            if (label) label.textContent = 'Lihat Catatan';
        });
    });
})();
</script>
@endpush
