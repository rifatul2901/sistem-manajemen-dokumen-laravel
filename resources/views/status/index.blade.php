@extends('layouts.main')

@section('page-title', 'Status Berkas')

@push('styles')
<style>
    /* ==== Halaman Status Berkas (scoped, tidak mengganggu halaman lain) ==== */
    .sb-page { --sb-border: #E6ECF5; --sb-muted: #64748B; }

    /* Header */
    .sb-page .sb-head { display: flex; align-items: center; gap: .75rem; margin-bottom: .9rem; flex-wrap: wrap; }
    .sb-page .sb-head-icon {
        width: 2.4rem; height: 2.4rem; border-radius: .7rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.15rem;
    }
    .sb-page .sb-head .sb-title { font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -.02em; }
    .sb-page .sb-head .sb-sub { font-size: .85rem; color: var(--sb-muted); margin: .1rem 0 0; }
    .sb-page .sb-refresh {
        margin-left: auto; display: inline-flex; align-items: center; gap: .35rem;
        height: 2.2rem; padding: 0 .85rem; font-size: .8rem; font-weight: 500;
        border-radius: .55rem; border: 1px solid var(--sb-border); background: #fff;
        color: #334155; text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
    }
    .sb-page .sb-refresh:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--primary); }

    /* Alert */
    .sb-page .alert { font-size: .8rem; padding: .55rem .75rem; border-radius: .6rem; }

    /* Header kelompok status (collapsible) */
    .sb-page .sb-group {
        display: flex; align-items: center; gap: .55rem; width: 100%;
        background: transparent; border: 0; padding: .25rem 0;
        margin: .85rem 0 .4rem; text-align: left; cursor: pointer;
    }
    .sb-page .sb-group:first-of-type { margin-top: .1rem; }
    .sb-page .sb-group-icon { width: 1.7rem; height: 1.7rem; border-radius: .5rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
    .sb-page .sb-group-icon.rev { background: #FEE2E2; color: #dc2626; }
    .sb-page .sb-group-icon.pend { background: #FEF3C7; color: #b45309; }
    .sb-page .sb-group-icon.appr { background: #DCFCE7; color: #15803d; }
    .sb-page .sb-group-label { font-size: .85rem; font-weight: 600; color: #334155; white-space: nowrap; }
    .sb-page .sb-group-count { font-size: .68rem; font-weight: 600; color: #475569; background: #EEF2F7; border-radius: 999px; padding: .05rem .5rem; }
    .sb-page .sb-group-line { flex: 1; height: 1px; background: var(--sb-border); }
    .sb-page .sb-group-chev { color: #94a3b8; font-size: .8rem; transition: transform .2s ease; }
    .sb-page .sb-group[aria-expanded="false"] .sb-group-chev { transform: rotate(-90deg); }
    .sb-page .sb-group:hover .sb-group-label { color: var(--primary); }
    .sb-page .sb-group:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; border-radius: .5rem; }

    /* Kartu dokumen */
    .sb-page .sb-doc {
        background: #fff; border: 1px solid var(--sb-border); border-radius: .65rem;
        padding: .55rem .75rem; margin-bottom: .4rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.03);
        transition: box-shadow .15s ease, border-color .15s ease;
    }
    .sb-page .sb-doc:hover { box-shadow: 0 3px 10px rgba(15,23,42,.06); border-color: #d7e3f1; }
    .sb-page .sb-doc-row { display: flex; align-items: center; gap: .7rem; }
    .sb-page .sb-doc-icon { width: 2.1rem; height: 2.1rem; border-radius: .55rem; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; font-size: 1rem; background: #EEF4FB; color: #3b82f6; }
    .sb-page .sb-doc-icon.rev { background: #FEF2F2; color: #dc2626; }
    .sb-page .sb-doc-icon.pend { background: #FFFBEB; color: #d97706; }
    .sb-page .sb-doc-icon.appr { background: #ECFDF3; color: #16a34a; }
    .sb-page .sb-doc-info { flex: 1 1 auto; min-width: 0; }
    .sb-page .sb-doc-name { font-size: .86rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sb-page .sb-doc-meta { display: flex; align-items: center; flex-wrap: wrap; gap: .4rem; margin-top: .15rem; }
    .sb-page .sb-folder { display: inline-flex; align-items: center; gap: .2rem; max-width: 16rem; font-size: .7rem; font-weight: 500; color: #0369a1; background: #E7F3FC; border-radius: .375rem; padding: .1rem .45rem; }
    .sb-page .sb-folder span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sb-page .sb-time { display: inline-flex; align-items: center; gap: .2rem; font-size: .72rem; color: #94a3b8; }
    .sb-page .sb-status { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 600; padding: .25em .6em; border-radius: 999px; white-space: nowrap; }
    .sb-page .sb-status.rev { background: #FEE2E2; color: #b91c1c; }
    .sb-page .sb-status.pend { background: #FEF3C7; color: #92400e; }
    .sb-page .sb-status.appr { background: #DCFCE7; color: #15803d; }

    /* Tombol aksi */
    .sb-page .sb-actions { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; justify-content: flex-end; }
    .sb-page .sb-btn {
        display: inline-flex; align-items: center; gap: .3rem; height: 1.9rem; padding: 0 .6rem;
        font-size: .74rem; font-weight: 500; border-radius: .5rem;
        border: 1px solid var(--sb-border); background: #fff; color: #475569; cursor: pointer;
        text-decoration: none;
        transition: background .15s, border-color .15s, color .15s;
    }
    .sb-page .sb-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--primary); }
    .sb-page .sb-btn.danger { color: #b91c1c; border-color: #FADADA; background: #FEF2F2; }
    .sb-page .sb-btn.danger:hover { background: #FDE8E8; color: #991b1b; }

    /* Panel detail (riwayat / catatan / revisi) */
    .sb-page .sb-panel { margin-top: .6rem; padding-top: .6rem; border-top: 1px dashed var(--sb-border); }
    .sb-page .sb-note { background: #FEF2F2; border-left: 3px solid #ef4444; border-radius: .5rem; padding: .5rem .7rem; color: #7f1d1d; font-size: .78rem; }
    .sb-page .sb-revisi { background: #F8FAFC; border: 1px solid var(--sb-border); border-radius: .6rem; padding: .7rem; }
    .sb-page .sb-revisi .form-label { font-size: .72rem; font-weight: 600; color: #475569; margin-bottom: .15rem; }
    .sb-page .sb-revisi .form-control { font-size: .78rem; }
    .sb-page .sb-revisi .form-control { min-height: 2rem; }
    .sb-page .sb-revisi .btn { min-height: 2rem; font-size: .76rem; }

    /* Timeline (pakai komponen global, dirapatkan) */
    .sb-page .timeline-item { padding-left: 1.15rem; padding-bottom: .6rem; }
    .sb-page .timeline-item::before { left: .25rem; }
    .sb-page .timeline-item .time-text { font-size: .72rem; }

    /* Pagination ringkas */
    .sb-page .pagination { margin-bottom: 0; justify-content: center; }
    .sb-page .page-link { padding: .25rem .55rem; font-size: .78rem; color: var(--primary); }
    .sb-page .page-item.active .page-link { background: var(--primary); border-color: var(--primary); }
    .sb-page .pagination + p, .sb-page nav p { font-size: .75rem; color: var(--sb-muted); }

    @media (max-width: 575.98px) {
        .sb-page .sb-doc-row { flex-wrap: wrap; }
        .sb-page .sb-status { margin-left: auto; }
        .sb-page .sb-actions { justify-content: flex-start; width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 sb-page">
    {{-- Header --}}
    <div class="sb-head">
        <span class="sb-head-icon"><i class="bi bi-folder-check"></i></span>
        <div>
            <h1 class="sb-title">Status Berkas</h1>
            <p class="sb-sub">Pantau status berkas yang kamu kirim.</p>
        </div>
        <a href="{{ route('status.index') }}" class="sb-refresh"><i class="bi bi-arrow-clockwise"></i> Refresh</a>
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

    @php
        $groupMeta = [
            'revision' => ['label' => 'Butuh Revisi', 'icon' => 'bi-exclamation-triangle', 'cls' => 'rev'],
            'pending'  => ['label' => 'Menunggu',      'icon' => 'bi-clock',                'cls' => 'pend'],
            'approved' => ['label' => 'Disetujui',     'icon' => 'bi-check-circle',         'cls' => 'appr'],
        ];
        $groups = collect($kejadianPerDokumen ?? [])->groupBy('status');
    @endphp

    @forelse ($groups as $status => $docs)
        @php
            $meta = $groupMeta[$status] ?? ['label' => ucfirst($status), 'icon' => 'bi-file-earmark', 'cls' => 'pend'];
            $groupId = 'sbGroup' . $status;
        @endphp

        {{-- Header kelompok (terbuka default, bisa ditutup) --}}
        <button class="sb-group" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" aria-expanded="true" aria-controls="{{ $groupId }}">
            <span class="sb-group-icon {{ $meta['cls'] }}"><i class="bi {{ $meta['icon'] }}"></i></span>
            <span class="sb-group-label">{{ $meta['label'] }}</span>
            <span class="sb-group-count">{{ $docs->count() }}</span>
            <span class="sb-group-line"></span>
            <i class="bi bi-chevron-down sb-group-chev"></i>
        </button>

        <div class="collapse show sb-group-collapse" id="{{ $groupId }}">
            @foreach ($docs as $doc)
                @php
                    $relatedUpload = $uploads->firstWhere('id', $doc['uploadId']);
                    $waktu = collect($doc['kejadian'])->last()['tanggal'] ?? null;
                    $isRevision = ($doc['status'] === 'revision');
                @endphp

                <div class="sb-doc">
                    <div class="sb-doc-row">
                        <span class="sb-doc-icon {{ $meta['cls'] }}">
                            <i class="bi {{ $isRevision ? 'bi-exclamation-triangle' : 'bi-file-earmark-text' }}"></i>
                        </span>
                        <div class="sb-doc-info">
                            <div class="sb-doc-name" title="{{ $doc['induk'] ?? 'Dokumen' }}">{{ $doc['induk'] ?? 'Dokumen' }}</div>
                            <div class="sb-doc-meta">
                                <span class="sb-folder" title="{{ $doc['subfolder'] ?? '(folder utama)' }}"><i class="bi bi-folder"></i><span>{{ $doc['subfolder'] ?? '(folder utama)' }}</span></span>
                                @if ($waktu)
                                    <span class="sb-time"><i class="bi bi-clock"></i> {{ $waktu }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="sb-status {{ $meta['cls'] }}">
                            <i class="bi {{ $meta['icon'] }}"></i> {{ $doc['statusLabel'] ?? 'Pending' }}
                        </span>
                        <div class="sb-actions">
                            {{-- Riwayat pengiriman --}}
                            <button class="sb-btn" type="button" data-bs-toggle="collapse" data-bs-target="#timelineUser{{ $doc['uploadId'] }}" aria-expanded="false">
                                <i class="bi bi-clock-history"></i> Riwayat
                            </button>

                            @if ($relatedUpload && $relatedUpload->status == 'revision')
                                {{-- Catatan admin --}}
                                @if (!empty($relatedUpload->catatan_admin))
                                    <button class="sb-btn danger" type="button" data-bs-toggle="collapse" data-bs-target="#catatan{{ $relatedUpload->id }}" aria-expanded="false">
                                        <i class="bi bi-chat-dots"></i> Catatan Admin
                                    </button>
                                @endif
                                {{-- Form revisi --}}
                                <button class="sb-btn danger" type="button" data-bs-toggle="collapse" data-bs-target="#revisiForm{{ $relatedUpload->id }}" aria-expanded="false">
                                    <i class="bi bi-arrow-repeat"></i> Kirim Revisi
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Panel: Riwayat Pengiriman --}}
                    <div class="collapse sb-panel" id="timelineUser{{ $doc['uploadId'] }}">
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
                        @else
                            <p class="text-muted small mb-0">Belum ada riwayat pengiriman.</p>
                        @endif
                    </div>

                    @if ($relatedUpload && $relatedUpload->status == 'revision')
                        {{-- Panel: Catatan Admin --}}
                        @if (!empty($relatedUpload->catatan_admin))
                            <div class="collapse sb-panel" id="catatan{{ $relatedUpload->id }}">
                                <div class="sb-note">
                                    <ul class="mb-0 ps-3">
                                        @foreach ((array) $relatedUpload->catatan_admin as $note)
                                            @if (trim($note))
                                                <li>{{ trim($note) }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @if ($relatedUpload->notifikasi_pdf && file_exists($relatedUpload->notifikasi_pdf))
                                    <a href="{{ route('status.download-notif', $relatedUpload->id) }}" class="sb-btn danger mt-2">
                                        <i class="bi bi-file-pdf"></i> Download Notifikasi Revisi (PDF)
                                    </a>
                                @endif
                            </div>
                        @endif

                        {{-- Panel: Upload File Revisi --}}
                        <div class="collapse sb-panel" id="revisiForm{{ $relatedUpload->id }}">
                            <form method="POST" action="{{ route('dashboard.upload-revisi') }}" enctype="multipart/form-data" class="sb-revisi">
                                @csrf
                                <input type="hidden" name="upload_id" value="{{ $relatedUpload->id }}">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label">Upload File Revisi (.docx / .pdf)</label>
                                        <input type="file" name="file" class="form-control" accept=".docx,.pdf" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
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
            @endforeach
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
        <div class="mt-3">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Layout global menutup semua .collapse pada setiap turbo:load (cleanupCollapse),
    // jadi buka kembali khusus kelompok status Status Berkas agar default terbuka.
    function openStatusGroups() {
        document.querySelectorAll('.sb-page .collapse.sb-group-collapse').forEach(function (el) {
            el.classList.add('show');
            var btn = document.querySelector('[data-bs-target="#' + el.id + '"]');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        });
    }
    document.addEventListener('turbo:load', openStatusGroups);
    if (document.readyState !== 'loading') {
        openStatusGroups();
    } else {
        document.addEventListener('DOMContentLoaded', openStatusGroups);
    }
})();
</script>
@endpush
