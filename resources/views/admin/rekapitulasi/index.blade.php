@extends('layouts.main')

@section('page-title', 'Rekapitulasi Catatan')

@push('styles')
<style>
    /* === Rekapitulasi Catatan — scoped theme (konsisten dengan dashboard SIMADO) === */
    .rekap-page { --rekap-border: #E2E8F0; --rekap-muted: #64748B; }

    /* Header halaman */
    .rekap-page .rekap-head { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.25rem; }
    .rekap-page .rekap-head .head-icon {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.25rem;
    }
    .rekap-page .rekap-head .page-title { font-size: 1.35rem; }
    .rekap-page .rekap-head p { color: var(--rekap-muted); font-size: .875rem; margin: .15rem 0 0; }

    /* Card header: judul + info & pencarian */
    .rekap-page .card-header { padding: 1rem 1.25rem; }
    .rekap-page .hdr-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.05rem;
    }
    .rekap-page .hdr-title { font-weight: 600; font-size: .95rem; color: #0f172a; line-height: 1.2; }
    .rekap-page .hdr-sub { font-size: .75rem; color: var(--rekap-muted); }

    .rekap-page .rekap-search { display: flex; align-items: center; gap: .5rem; }
    .rekap-page .search-field { position: relative; min-width: 230px; }
    .rekap-page .search-field > i {
        position: absolute; left: .7rem; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: .85rem; pointer-events: none;
    }
    .rekap-page .search-field input { padding-left: 2.1rem; }
    @media (max-width: 575.98px) {
        .rekap-page .rekap-search { width: 100%; }
        .rekap-page .search-field { min-width: 0; flex: 1 1 auto; }
    }

    /* Tabel */
    .rekap-page .rekap-table { font-size: .875rem; margin: 0; }
    .rekap-page .rekap-table thead th {
        background: #EAF2FB; color: #475569; font-weight: 600;
        font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
        padding: .85rem 1.25rem; border-bottom: 1px solid var(--rekap-border); white-space: nowrap;
    }
    .rekap-page .rekap-table tbody td { padding: .85rem 1.25rem; vertical-align: middle; border-color: #f1f5f9; }
    .rekap-page .rekap-table tbody tr { transition: background .12s ease; }
    .rekap-page .rekap-table tbody tr:hover { background: #F8FAFC; }
    .rekap-page .rekap-username { color: var(--rekap-muted); }
    .rekap-page .rekap-table .btn { border-radius: 8px; }

    .rekap-page .badge-doc {
        display: inline-flex; align-items: center; gap: .3rem;
        background: #E7F3FC; color: #0369A1; font-weight: 500;
        padding: .35em .7em; border-radius: 999px; font-size: .75rem;
    }

    .rekap-page .rekap-footer { border-top: 1px solid var(--rekap-border); }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 rekap-page">
    <div class="rekap-head">
        <span class="head-icon"><i class="bi bi-file-earmark-bar-graph"></i></span>
        <div>
            <h4 class="page-title">Rekapitulasi Catatan</h4>
            <p>Download rekapitulasi catatan per user dalam format Excel.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center gap-3">
                <span class="hdr-icon"><i class="bi bi-people"></i></span>
                <div>
                    <div class="hdr-title">Daftar Rekapitulasi</div>
                    <div class="hdr-sub">Total {{ $users->total() }} data</div>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.rekapitulasi') }}" class="rekap-search" role="search">
                <div class="search-field">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama user..." aria-label="Cari nama user">
                </div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1 d-none d-sm-inline"></i>Cari</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table rekap-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:64px;">No</th>
                            <th>Nama User</th>
                            <th>Username</th>
                            <th>Jumlah Dokumen</th>
                            <th style="width:200px;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users ?? [] as $user)
                            <tr>
                                <td class="text-muted">{{ $users->firstItem() + $loop->index }}</td>
                                <td class="fw-semibold text-dark">{{ $user->nama ?? '-' }}</td>
                                <td><span class="rekap-username">&#64;{{ $user->username }}</span></td>
                                <td>
                                    <span class="badge-doc"><i class="bi bi-file-earmark-text"></i>{{ $countMap[$user->id] ?? 0 }} dokumen</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.rekapitulasi.download', $user) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i> Download Excel
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state"><i class="bi bi-people"></i><p class="mb-0">Belum ada data rekapitulasi.</p></div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="p-3 rekap-footer">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
