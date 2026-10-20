@extends('layouts.main')

@section('page-title', 'Dashboard')

@push('styles')
<style>
    .ud-page {
        --ud-border: #E6ECF5;
        --ud-muted: #64748B;
        --ud-text: #0F172A;
        --ud-blue: #0092DF;
        color: var(--ud-text);
        max-width: 100rem;
        margin: 0 auto;
        padding-bottom: .5rem;
    }

    .ud-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: .75rem 1rem; flex-wrap: wrap; margin-bottom: 1rem;
    }
    .ud-head-main { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .ud-head-icon {
        width: 2.6rem; height: 2.6rem; border-radius: .7rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.2rem;
    }
    .ud-title { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -.02em; }
    .ud-sub { font-size: .84rem; color: var(--ud-muted); margin: .1rem 0 0; }
    .ud-sub strong { color: #334155; font-weight: 600; }
    .ud-head-side { display: flex; align-items: center; gap: .45rem; margin-left: auto; flex-wrap: wrap; }
    .ud-date {
        display: inline-flex; align-items: center; gap: .4rem; height: 2.2rem; padding: 0 .7rem;
        font-size: .74rem; color: #475569; background: #fff;
        border: 1px solid var(--ud-border); border-radius: .55rem;
    }
    .ud-date i { color: var(--primary); font-size: .8rem; }
    .ud-refresh {
        display: inline-flex; align-items: center; gap: .35rem; height: 2.2rem; padding: 0 .75rem;
        font-size: .74rem; font-weight: 500; color: #475569; background: #fff;
        border: 1px solid var(--ud-border); border-radius: .55rem; text-decoration: none;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
    }
    .ud-refresh:hover { background: #F8FAFC; border-color: #CBD5E1; color: var(--primary); }

    .ud-stats {
        display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: .65rem; margin-bottom: .9rem;
    }
    .ud-stat {
        display: flex; align-items: flex-start; gap: .6rem; min-width: 0;
        background: #fff; border: 1px solid var(--ud-border); border-radius: .75rem;
        padding: .72rem .8rem; box-shadow: 0 1px 2px rgba(15,23,42,.035);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .ud-stat:hover { transform: translateY(-1px); border-color: #D7E3F1; box-shadow: 0 4px 12px rgba(15,23,42,.06); }
    .ud-stat-icon {
        width: 2.15rem; height: 2.15rem; border-radius: .58rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .ud-stat-content { min-width: 0; }
    .ud-stat-label {
        display: block; font-size: .64rem; font-weight: 600; text-transform: uppercase;
        letter-spacing: .035em; color: var(--ud-muted); line-height: 1.3;
    }
    .ud-stat-value { display: block; font-size: 1.35rem; font-weight: 700; line-height: 1.15; color: var(--ud-text); margin-top: .06rem; }
    .ud-stat-desc { display: block; font-size: .65rem; color: #94a3b8; line-height: 1.3; margin-top: .08rem; }
    .ud-stat.total .ud-stat-icon { background: #E7F3FC; color: #0092DF; }
    .ud-stat.pending .ud-stat-icon { background: #FEF3C7; color: #B45309; }
    .ud-stat.revision .ud-stat-icon { background: #FEE2E2; color: #DC2626; }
    .ud-stat.approved .ud-stat-icon { background: #DCFCE7; color: #15803D; }
    .ud-stat.revisions .ud-stat-icon { background: #E0F2FE; color: #0369A1; }

    .ud-main-grid {
        display: grid; grid-template-columns: minmax(0, 1.12fr) minmax(0, .88fr);
        gap: .9rem; align-items: start;
    }
    .ud-panel {
        background: #fff; border: 1px solid var(--ud-border); border-radius: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.035); overflow: hidden;
    }
    .ud-panel-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: .5rem .75rem; flex-wrap: wrap; padding: .8rem 1rem;
        border-bottom: 1px solid #EEF2F7;
    }
    .ud-panel-title { font-size: .9rem; font-weight: 600; color: #0f172a; margin: 0; display: flex; align-items: center; gap: .4rem; }
    .ud-panel-title i { color: var(--primary); font-size: .9rem; }
    .ud-panel-sub { font-size: .7rem; color: var(--ud-muted); margin: .12rem 0 0; }
    .ud-panel-link {
        display: inline-flex; align-items: center; gap: .25rem;
        font-size: .72rem; font-weight: 500; color: var(--primary); text-decoration: none;
        white-space: nowrap;
    }
    .ud-panel-link:hover { color: var(--primary-hover); }
    .ud-panel-body { padding: .55rem .7rem .65rem; }

    .ud-action {
        border: 1px solid #EEF2F7; border-radius: .6rem; padding: .58rem .65rem;
        margin-bottom: .42rem; background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ud-action:last-child { margin-bottom: 0; }
    .ud-action:hover { border-color: #D7E3F1; box-shadow: 0 3px 10px rgba(15,23,42,.055); }
    .ud-action-top { display: flex; align-items: flex-start; gap: .55rem; }
    .ud-action-icon {
        width: 2rem; height: 2rem; border-radius: .55rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #FEF2F2; color: #DC2626; font-size: .9rem;
    }
    .ud-action-info { flex: 1 1 auto; min-width: 0; }
    .ud-action-name { font-size: .81rem; font-weight: 600; color: #0f172a; line-height: 1.35; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ud-action-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .25rem .55rem; margin-top: .15rem; font-size: .67rem; color: #94a3b8; }
    .ud-meta-item { display: inline-flex; align-items: center; gap: .18rem; min-width: 0; }
    .ud-meta-item i { font-size: .68rem; color: #94a3b8; }
    .ud-meta-item.folder { max-width: 14rem; color: #0369a1; }
    .ud-meta-item.folder span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ud-rev-count {
        display: inline-flex; align-items: center; gap: .18rem;
        font-size: .64rem; font-weight: 600; color: #0369a1;
        background: #E0F2FE; border-radius: 999px; padding: .08rem .45rem; white-space: nowrap;
    }
    .ud-status-badge {
        display: inline-flex; align-items: center; gap: .22rem; flex-shrink: 0;
        font-size: .64rem; font-weight: 600; padding: .2rem .48rem;
        border-radius: 999px; white-space: nowrap;
    }
    .ud-status-badge.rev { background: #FEE2E2; color: #B91C1C; }
    .ud-action-note {
        display: flex; align-items: flex-start; gap: .3rem; margin-top: .42rem;
        padding: .32rem .45rem; background: #FEF2F2; border-left: 2px solid #EF4444;
        border-radius: .35rem; font-size: .69rem; color: #7F1D1D; line-height: 1.4;
    }
    .ud-action-note > i { font-size: .68rem; margin-top: .1rem; flex-shrink: 0; }
    .ud-action-note > span { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    .ud-note-more { margin-left: auto; flex-shrink: 0; font-size: .62rem; font-weight: 600; color: #B91C1C; padding-left: .25rem; }
    .ud-action-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .48rem; }
    .ud-action-hint { font-size: .66rem; color: #94a3b8; }
    .ud-btn-rev {
        display: inline-flex; align-items: center; gap: .3rem; height: 1.8rem; padding: 0 .6rem;
        font-size: .69rem; font-weight: 600; color: #B91C1C; background: #FEF2F2;
        border: 1px solid #FCA5A5; border-radius: .45rem; text-decoration: none;
        transition: background .15s ease, border-color .15s ease, color .15s ease;
        white-space: nowrap;
    }
    .ud-btn-rev:hover { background: #FDE8E8; border-color: #F87171; color: #991B1B; }

    .ud-activity { display: flex; align-items: flex-start; gap: .55rem; padding: .55rem 0; border-bottom: 1px solid #F1F5F9; }
    .ud-activity:first-child { padding-top: .1rem; }
    .ud-activity:last-child { border-bottom: 0; padding-bottom: .1rem; }
    .ud-activity-icon {
        width: 1.85rem; height: 1.85rem; border-radius: .5rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: .82rem;
    }
    .ud-activity-icon.blue { background: #E7F3FC; color: #0092DF; }
    .ud-activity-icon.green { background: #DCFCE7; color: #15803D; }
    .ud-activity-icon.red { background: #FEE2E2; color: #DC2626; }
    .ud-activity-icon.amber { background: #FEF3C7; color: #B45309; }
    .ud-activity-body { min-width: 0; flex: 1 1 auto; }
    .ud-activity-top { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
    .ud-activity-label { font-size: .75rem; font-weight: 600; color: #334155; }
    .ud-activity-time { font-size: .66rem; color: #94a3b8; white-space: nowrap; }
    .ud-activity-file { font-size: .74rem; color: #0f172a; margin-top: .04rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ud-activity-note { font-size: .68rem; color: #64748B; margin-top: .12rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .ud-empty { text-align: center; padding: 1.5rem .75rem; }
    .ud-empty i { display: block; font-size: 1.7rem; color: #CBD5E1; margin-bottom: .3rem; }
    .ud-empty p { margin: 0; font-size: .76rem; color: #94a3b8; line-height: 1.5; }
    .ud-empty.is-safe i { color: #86EFAC; }
    .ud-empty.is-safe p { color: #15803D; }

    @media (max-width: 1199.98px) {
        .ud-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 991.98px) {
        .ud-main-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767.98px) {
        .ud-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .ud-stat.revisions { grid-column: span 2; }
        .ud-head-side { margin-left: 0; width: 100%; }
        .ud-date { flex: 1 1 auto; justify-content: center; }
    }
    @media (max-width: 479.98px) {
        .ud-stats { grid-template-columns: 1fr; }
        .ud-stat.revisions { grid-column: auto; }
        .ud-head-main { align-items: flex-start; }
        .ud-title { font-size: 1.25rem; }
        .ud-action-top { flex-wrap: wrap; }
        .ud-status-badge { margin-left: 2.55rem; }
        .ud-action-foot { align-items: stretch; flex-direction: column; }
        .ud-btn-rev, .ud-action-hint { justify-content: center; text-align: center; }
        .ud-btn-rev { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $today = now()->locale('id');
    $userName = $user->nama ?? $user->username ?? 'User';
@endphp

<div class="container-fluid px-0 ud-page">
    <div class="ud-head">
        <div class="ud-head-main">
            <span class="ud-head-icon"><i class="bi bi-grid-1x2"></i></span>
            <div>
                <h1 class="ud-title">Dashboard</h1>
                <p class="ud-sub">Selamat datang kembali, <strong>{{ $userName }}</strong>. Pantau status dan perkembangan berkas kamu.</p>
            </div>
        </div>
        <div class="ud-head-side">
            <span class="ud-date"><i class="bi bi-calendar3"></i> {{ $today->isoFormat('dddd, D MMMM YYYY') }}</span>
            <a href="{{ route('dashboard.index') }}" class="ud-refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="ud-stats">
        <div class="ud-stat total">
            <span class="ud-stat-icon"><i class="bi bi-files"></i></span>
            <div class="ud-stat-content">
                <span class="ud-stat-label">Total Berkas</span>
                <strong class="ud-stat-value">{{ number_format($totalBerkas, 0, ',', '.') }}</strong>
                <span class="ud-stat-desc">Milik atau ditugaskan</span>
            </div>
        </div>
        <div class="ud-stat pending">
            <span class="ud-stat-icon"><i class="bi bi-clock"></i></span>
            <div class="ud-stat-content">
                <span class="ud-stat-label">Menunggu</span>
                <strong class="ud-stat-value">{{ number_format($totalMenunggu, 0, ',', '.') }}</strong>
                <span class="ud-stat-desc">Menunggu review admin</span>
            </div>
        </div>
        <div class="ud-stat revision">
            <span class="ud-stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
            <div class="ud-stat-content">
                <span class="ud-stat-label">Butuh Revisi</span>
                <strong class="ud-stat-value">{{ number_format($totalRevisiBerkas, 0, ',', '.') }}</strong>
                <span class="ud-stat-desc">Perlu perbaikan</span>
            </div>
        </div>
        <div class="ud-stat approved">
            <span class="ud-stat-icon"><i class="bi bi-check-circle"></i></span>
            <div class="ud-stat-content">
                <span class="ud-stat-label">Disetujui</span>
                <strong class="ud-stat-value">{{ number_format($totalDisetujui, 0, ',', '.') }}</strong>
                <span class="ud-stat-desc">Sudah disetujui</span>
            </div>
        </div>
        <div class="ud-stat revisions">
            <span class="ud-stat-icon"><i class="bi bi-arrow-repeat"></i></span>
            <div class="ud-stat-content">
                <span class="ud-stat-label">Total Revisi</span>
                <strong class="ud-stat-value">{{ number_format($totalRevisi, 0, ',', '.') }}</strong>
                <span class="ud-stat-desc">Revisi tercatat</span>
            </div>
        </div>
    </div>

    <div class="ud-main-grid">
        <section class="ud-panel">
            <div class="ud-panel-head">
                <div>
                    <h2 class="ud-panel-title"><i class="bi bi-exclamation-circle"></i> Perlu Tindakan</h2>
                    <p class="ud-panel-sub">Berkas yang menunggu revisi dari kamu.</p>
                </div>
                @if ($totalRevisiBerkas > $perluTindakan->count())
                    <a href="{{ route('status.index') }}" class="ud-panel-link">Lihat Semua <i class="bi bi-arrow-right"></i></a>
                @endif
            </div>
            <div class="ud-panel-body">
                @forelse ($perluTindakan as $upload)
                    @php
                        $notes = $upload->catatan_admin;
                        $notes = is_array($notes) ? $notes : ($notes ? [$notes] : []);
                        $notes = collect($notes)->filter(fn ($note) => is_string($note) && trim($note) !== '')->map(fn ($note) => trim($note))->values();
                        $notePreview = $notes->first();
                        $revisionDate = $upload->revision_requested_at
                            ? $upload->revision_requested_at->locale('id')->isoFormat('D MMM YYYY, HH:mm')
                            : null;
                    @endphp
                    <article class="ud-action">
                        <div class="ud-action-top">
                            <span class="ud-action-icon"><i class="bi bi-file-earmark-text"></i></span>
                            <div class="ud-action-info">
                                <div class="ud-action-name" title="{{ $upload->filename }}">{{ $upload->filename }}</div>
                                <div class="ud-action-meta">
                                    <span class="ud-meta-item folder" title="{{ $upload->subfolder ?? '(folder utama)' }}"><i class="bi bi-folder"></i><span>{{ $upload->subfolder ?? '(folder utama)' }}</span></span>
                                    @if ($revisionDate)
                                        <span class="ud-meta-item" title="Tanggal permintaan revisi"><i class="bi bi-clock"></i>{{ $revisionDate }}</span>
                                    @endif
                                    <span class="ud-rev-count"><i class="bi bi-arrow-repeat"></i> {{ $upload->revision_histories_count }} revisi</span>
                                </div>
                            </div>
                            <span class="ud-status-badge rev"><i class="bi bi-exclamation-triangle-fill"></i> Butuh Revisi</span>
                        </div>
                        @if ($notePreview)
                            <div class="ud-action-note">
                                <i class="bi bi-chat-left-text"></i>
                                <span title="{{ $notePreview }}">{{ \Illuminate\Support\Str::limit($notePreview, 120) }}</span>
                                @if ($notes->count() > 1)
                                    <span class="ud-note-more">+{{ $notes->count() - 1 }}</span>
                                @endif
                            </div>
                        @endif
                        <div class="ud-action-foot">
                            <span class="ud-action-hint"><i class="bi bi-info-circle"></i> Lengkapi revisi melalui Status Berkas.</span>
                            <a href="{{ route('status.index') }}" class="ud-btn-rev">Kirim Revisi <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </article>
                @empty
                    <div class="ud-empty {{ $totalBerkas > 0 ? 'is-safe' : '' }}">
                        <i class="bi {{ $totalBerkas > 0 ? 'bi-check2-circle' : 'bi-inbox' }}"></i>
                        <p>
                            @if ($totalBerkas === 0)
                                Belum ada berkas yang dapat kamu akses.
                            @else
                                Semua berkas aman. Tidak ada revisi yang perlu dilakukan saat ini.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="ud-panel">
            <div class="ud-panel-head">
                <div>
                    <h2 class="ud-panel-title"><i class="bi bi-activity"></i> Aktivitas Terbaru</h2>
                    <p class="ud-panel-sub">Perkembangan dokumen kamu.</p>
                </div>
                @if ($activities->isNotEmpty())
                    <a href="{{ route('riwayat.index') }}" class="ud-panel-link">Lihat Riwayat <i class="bi bi-arrow-right"></i></a>
                @endif
            </div>
            <div class="ud-panel-body">
                @forelse ($activities as $activity)
                    <div class="ud-activity">
                        <span class="ud-activity-icon {{ $activity['tone'] }}"><i class="bi {{ $activity['icon'] }}"></i></span>
                        <div class="ud-activity-body">
                            <div class="ud-activity-top">
                                <span class="ud-activity-label">{{ $activity['label'] }}</span>
                                <time class="ud-activity-time" datetime="{{ $activity['timestamp']->toIso8601String() }}" title="{{ $activity['time_full'] }}">{{ $activity['time_label'] }}</time>
                            </div>
                            <div class="ud-activity-file" title="{{ $activity['filename'] }}">{{ $activity['filename'] }}</div>
                            @if (!empty($activity['note']))
                                <div class="ud-activity-note" title="{{ $activity['note'] }}">{{ $activity['note'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="ud-empty">
                        <i class="bi bi-inbox"></i>
                        <p>
                            @if ($totalBerkas === 0)
                                Belum ada aktivitas karena belum ada berkas yang dapat kamu akses.
                            @else
                                Belum ada aktivitas dokumen terbaru.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
