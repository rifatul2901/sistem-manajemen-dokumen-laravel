@extends('layouts.main')

@section('page-title', 'Riwayat Berkas')

@push('styles')
<style>
    /* ==== Halaman Riwayat Berkas (user) — scoped, compact modern ==== */
    .rh-page { --rh-border: #E6ECF5; --rh-muted: #64748B; }

    /* Header */
    .rh-page .rh-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .9rem; flex-wrap: wrap; }
    .rh-page .rh-head-icon {
        width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.15rem;
    }
    .rh-page .rh-title { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -.02em; }
    .rh-page .rh-sub { font-size: .85rem; color: var(--rh-muted); margin: .1rem 0 0; }

    /* Panel filter */
    .rh-page .rh-filter {
        background: #fff; border: 1px solid var(--rh-border); border-radius: .7rem;
        padding: .65rem .75rem; margin-bottom: .9rem; display: flex; flex-wrap: wrap;
        align-items: center; gap: .5rem; box-shadow: 0 1px 2px rgba(15,23,42,.03);
    }
    .rh-page .rh-filter .rh-search { flex: 1 1 16rem; min-width: 0; position: relative; }
    .rh-page .rh-filter .rh-search > i { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .8rem; pointer-events: none; }
    .rh-page .rh-filter .rh-search .form-control { padding-left: 2rem; }
    .rh-page .rh-filter .form-control,
    .rh-page .rh-filter .form-select { height: 2.4rem; font-size: .8rem; }
    .rh-page .rh-filter .rh-sel { flex: 0 0 12rem; min-width: 0; }
    .rh-page .rh-filter .btn { height: 2.4rem; font-size: .8rem; display: inline-flex; align-items: center; gap: .35rem; }
    .rh-page .rh-filter .btn-reset { border: 1px solid var(--rh-border); background: #fff; color: #475569; padding: 0 .8rem; border-radius: .5rem; text-decoration: none; }
    .rh-page .rh-filter .btn-reset:hover { background: #f8fafc; color: var(--primary); }

    /* Header kelompok status (collapsible) */
    .rh-page .rh-group { display: flex; align-items: center; gap: .55rem; width: 100%; background: transparent; border: 0; padding: .25rem 0; margin: .85rem 0 .4rem; text-align: left; cursor: pointer; }
    .rh-page .rh-group:first-of-type { margin-top: .1rem; }
    .rh-page .rh-group-icon { width: 1.7rem; height: 1.7rem; border-radius: .5rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
    .rh-page .rh-group-icon.rev { background: #FEE2E2; color: #dc2626; }
    .rh-page .rh-group-icon.pend { background: #E0F2FE; color: #0369a1; }
    .rh-page .rh-group-icon.appr { background: #DCFCE7; color: #15803d; }
    .rh-page .rh-group-label { font-size: .85rem; font-weight: 600; color: #334155; white-space: nowrap; }
    .rh-page .rh-group-count { font-size: .68rem; font-weight: 600; color: #475569; background: #EEF2F7; border-radius: 999px; padding: .05rem .5rem; }
    .rh-page .rh-group-line { flex: 1; height: 1px; background: var(--rh-border); }
    .rh-page .rh-group-chev { color: #94a3b8; font-size: .8rem; transition: transform .2s ease; }
    .rh-page .rh-group[aria-expanded="false"] .rh-group-chev { transform: rotate(-90deg); }
    .rh-page .rh-group:hover .rh-group-label { color: var(--primary); }
    .rh-page .rh-group:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; border-radius: .5rem; }

    /* Kartu berkas */
    .rh-page .rh-doc { background: #fff; border: 1px solid var(--rh-border); border-radius: .65rem; padding: .6rem .75rem; margin-bottom: .5rem; box-shadow: 0 1px 2px rgba(15,23,42,.03); transition: box-shadow .15s ease, border-color .15s ease; }
    .rh-page .rh-doc:hover { box-shadow: 0 3px 10px rgba(15,23,42,.06); border-color: #d7e3f1; }
    .rh-page .rh-doc-row { display: flex; align-items: center; gap: .7rem; }
    .rh-page .rh-doc-icon { width: 2.1rem; height: 2.1rem; border-radius: .55rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; background: #EEF4FB; color: #3b82f6; }
    .rh-page .rh-doc-icon.rev { background: #FEF2F2; color: #dc2626; }
    .rh-page .rh-doc-icon.pend { background: #EFF6FF; color: #2563eb; }
    .rh-page .rh-doc-icon.appr { background: #ECFDF3; color: #16a34a; }
    .rh-page .rh-doc-info { flex: 1 1 auto; min-width: 0; }
    .rh-page .rh-doc-name { font-size: .86rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rh-page .rh-doc-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; margin-top: .15rem; }
    .rh-page .rh-folder { display: inline-flex; align-items: center; gap: .2rem; max-width: 16rem; font-size: .7rem; font-weight: 500; color: #0369a1; background: #E7F3FC; border-radius: .375rem; padding: .1rem .45rem; }
    .rh-page .rh-folder span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rh-page .rh-time { display: inline-flex; align-items: center; gap: .2rem; font-size: .72rem; color: #94a3b8; }
    .rh-page .rh-assignee { display: inline-flex; align-items: center; gap: .2rem; font-size: .72rem; color: #64748b; }
    .rh-page .rh-rev-count { display: inline-flex; align-items: center; gap: .2rem; font-size: .68rem; font-weight: 600; color: #0369a1; background: #E0F2FE; border-radius: 999px; padding: .1rem .5rem; white-space: nowrap; }
    .rh-page .rh-status { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 600; padding: .25em .6em; border-radius: 999px; white-space: nowrap; }
    .rh-page .rh-status.rev { background: #FEE2E2; color: #b91c1c; }
    .rh-page .rh-status.pend { background: #E0F2FE; color: #0369a1; }
    .rh-page .rh-status.appr { background: #DCFCE7; color: #15803d; }

    /* Tombol */
    .rh-page .rh-btn { display: inline-flex; align-items: center; gap: .3rem; height: 1.9rem; padding: 0 .65rem; font-size: .74rem; font-weight: 500; border-radius: .5rem; border: 1px solid var(--rh-border); background: #fff; color: #475569; cursor: pointer; text-decoration: none; transition: background .15s, border-color .15s, color .15s; white-space: nowrap; }
    .rh-page .rh-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--primary); }

    /* Panel detail + timeline */
    .rh-page .rh-panel { margin-top: .6rem; padding-top: .6rem; border-top: 1px dashed var(--rh-border); }
    .rh-page .rev-timeline { position: relative; padding-left: 1.2rem; }
    .rh-page .rev-timeline::before { content: ''; position: absolute; left: .3rem; top: .4rem; bottom: .4rem; width: 1px; background: #e2e8f0; }
    .rh-page .rev-item { position: relative; padding-bottom: .5rem; }
    .rh-page .rev-item:last-child { padding-bottom: 0; }
    .rh-page .rev-dot { position: absolute; left: -1.15rem; top: .3rem; width: .5rem; height: .5rem; border-radius: 50%; background: #94a3b8; border: 2px solid #fff; box-shadow: 0 0 0 1px #e2e8f0; }
    .rh-page .rev-dot.initial { background: var(--primary); }
    .rh-page .rev-dot.revision { background: #ef4444; }
    .rh-page .rev-dot.approved { background: #62B846; }
    .rh-page .rev-item .small { font-size: .78rem; }
    .rh-page .rev-note { background: #FEF2F2; border-left: 3px solid #ef4444; border-radius: .5rem; padding: .4rem .6rem; margin-top: .3rem; font-size: .75rem; color: #7f1d1d; }

    /* Pagination ringkas */
    .rh-page .pagination { margin-bottom: 0; justify-content: center; }
    .rh-page .page-link { padding: .25rem .55rem; font-size: .78rem; color: var(--primary); }
    .rh-page .page-item.active .page-link { background: var(--primary); border-color: var(--primary); }
    .rh-page nav p { font-size: .75rem; color: var(--rh-muted); }

    @media (max-width: 575.98px) {
        .rh-page .rh-filter .rh-sel { flex: 1 1 100%; }
        .rh-page .rh-filter .btn { flex: 1 1 auto; justify-content: center; }
        .rh-page .rh-doc-row { flex-wrap: wrap; }
        .rh-page .rh-status { margin-left: auto; }
        .rh-page .rh-doc .rh-btn { width: 100%; justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 rh-page">
    {{-- Header --}}
    <div class="rh-head">
        <span class="rh-head-icon"><i class="bi bi-clock-history"></i></span>
        <div>
            <h1 class="rh-title">Riwayat Berkas</h1>
            <p class="rh-sub">Lihat seluruh riwayat pengiriman dan revisi berkas kamu.</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('riwayat.index') }}" class="rh-filter">
        <div class="rh-search">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Cari nama file..." value="{{ request('search') }}">
        </div>
        <div class="rh-sel">
            <select name="status" class="form-select">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>Butuh Revisi</option>
            </select>
        </div>
        <div class="rh-sel">
            <select name="folder" class="form-select">
                <option value="">Semua Folder</option>
                @foreach ($folders as $f)
                    <option value="{{ $f }}" {{ request('folder') == $f ? 'selected' : '' }}>{{ $f }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
        @if (request('search') || request('status') || request('folder'))
            <a href="{{ route('riwayat.index') }}" class="btn btn-reset"><i class="bi bi-x-lg"></i> Reset</a>
        @endif
    </form>

    {{-- Kelompok status --}}
    @php
        $groupMeta = [
            'revision' => ['label' => $statusLabelMap['revision'] ?? 'Butuh Revisi', 'icon' => 'bi-exclamation-triangle', 'cls' => 'rev'],
            'pending'  => ['label' => $statusLabelMap['pending']  ?? 'Menunggu',      'icon' => 'bi-clock',                'cls' => 'pend'],
            'approved' => ['label' => $statusLabelMap['approved'] ?? 'Disetujui',     'icon' => 'bi-check-circle',         'cls' => 'appr'],
        ];
        $priority = ['revision' => 0, 'pending' => 1, 'approved' => 2];
        $grouped = collect($uploads->items())
            ->groupBy('status')
            ->sortBy(fn ($items, $status) => $priority[$status] ?? 99);
    @endphp

    @forelse ($grouped as $status => $items)
        @php
            $meta = $groupMeta[$status] ?? ['label' => ucfirst($status), 'icon' => 'bi-file-earmark', 'cls' => 'pend'];
            $groupId = 'rhGroup' . $status;
        @endphp

        {{-- Header kelompok (terbuka default, bisa ditutup) --}}
        <button class="rh-group" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" aria-expanded="true" aria-controls="{{ $groupId }}">
            <span class="rh-group-icon {{ $meta['cls'] }}"><i class="bi {{ $meta['icon'] }}"></i></span>
            <span class="rh-group-label">{{ $meta['label'] }}</span>
            <span class="rh-group-count">{{ $items->count() }}</span>
            <span class="rh-group-line"></span>
            <i class="bi bi-chevron-down rh-group-chev"></i>
        </button>

        <div class="collapse show rh-group-collapse" id="{{ $groupId }}">
            @foreach ($items as $upload)
                @php
                    $revisionCount = $upload->revisionHistories->count();
                    $statusLabel = $statusLabelMap[$upload->status] ?? $upload->status;
                @endphp
                <div class="rh-doc">
                    <div class="rh-doc-row">
                        <span class="rh-doc-icon {{ $meta['cls'] }}">
                            <i class="bi {{ $status === 'revision' ? 'bi-exclamation-triangle' : 'bi-file-earmark-text' }}"></i>
                        </span>
                        <div class="rh-doc-info">
                            <div class="rh-doc-name" title="{{ $upload->filename }}">{{ $upload->filename }}</div>
                            <div class="rh-doc-meta">
                                <span class="rh-folder" title="{{ $upload->subfolder ?? '(folder utama)' }}"><i class="bi bi-folder"></i><span>{{ $upload->subfolder ?? '(folder utama)' }}</span></span>
                                <span class="rh-time"><i class="bi bi-calendar3"></i> {{ $upload->created_at ? $upload->created_at->format('d/m/Y H:i') : '-' }}</span>
                                @if ($upload->assignedUser)
                                    <span class="rh-assignee"><i class="bi bi-person-check"></i> {{ $upload->assignedUser->nama ?? $upload->assignedUser->username }}</span>
                                @endif
                            </div>
                        </div>
                        @if ($revisionCount > 0)
                            <span class="rh-rev-count"><i class="bi bi-arrow-repeat"></i> {{ $revisionCount }} revisi</span>
                        @endif
                        <span class="rh-status {{ $meta['cls'] }}">
                            <i class="bi {{ $meta['icon'] }}"></i> {{ $statusLabel }}
                        </span>
                        <button class="rh-btn" type="button" data-bs-toggle="collapse" data-bs-target="#riwayatTimeline{{ $upload->id }}" aria-expanded="false">
                            <i class="bi bi-clock-history"></i> Lihat Detail
                        </button>
                    </div>

                    {{-- Panel: timeline riwayat --}}
                    <div class="collapse rh-panel" id="riwayatTimeline{{ $upload->id }}">
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
                                                <i class="bi bi-chat-dots me-1"></i>
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
                                        <span class="fw-medium text-success"><i class="bi bi-check-circle me-1"></i>Disetujui</span>
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
                                    <div class="rev-dot flex-shrink-0" style="background: #2563eb;"></div>
                                    <div class="small flex-grow-1">
                                        <span class="fw-medium text-primary">Menunggu Review Admin</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
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
        <div class="d-flex justify-content-center mt-3">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Layout global menutup semua .collapse pada setiap turbo:load (cleanupCollapse),
    // jadi buka kembali khusus kelompok status Riwayat Berkas agar default terbuka.
    function openRiwayatGroups() {
        document.querySelectorAll('.rh-page .collapse.rh-group-collapse').forEach(function (el) {
            el.classList.add('show');
            var btn = document.querySelector('[data-bs-target="#' + el.id + '"]');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });
    }
    document.addEventListener('turbo:load', openRiwayatGroups);
    if (document.readyState !== 'loading') {
        openRiwayatGroups();
    } else {
        document.addEventListener('DOMContentLoaded', openRiwayatGroups);
    }
})();
</script>
@endpush
