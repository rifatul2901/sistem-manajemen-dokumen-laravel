@extends('layouts.main')

@section('page-title', 'Dashboard')

@push('styles')
<style>
.poin-label-text {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    word-break: break-word;
    line-height: 1.3;
}

.poin-badge {
    transition: all .15s ease;
}
.poin-badge:hover {
    transform: scale(1.05);
}
.poin-badge-empty {
    border: 2px dashed #adb5bd !important;
    background: #f8f9fa !important;
    color: #6c757d !important;
}
.poin-badge-empty:hover {
    border-color: #6c757d !important;
    background: #e9ecef !important;
}
.poin-badge-full {
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #fff !important;
    box-shadow: 0 2px 8px rgba(245,158,11,0.35);
}

@keyframes poinPopupFade {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Tooltip Poin Checklist: kartu popover modern, aksen biru SIMADO.
   Hanya menata popup checklist; tidak menyentuh tabel/badge/filter/layout. */
#poinPopup {
    position: absolute;
    z-index: 1060;
    width: 400px;
    max-width: calc(100vw - 16px);
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    color: #0F172A;
    font-size: 14px;
    animation: poinPopupFade .16s ease-out;
}
#poinPopup .poin-popup-arrow {
    position: absolute;
    top: -6px;
    width: 12px;
    height: 12px;
    background: #FFFFFF;
    border-left: 1px solid #E2E8F0;
    border-top: 1px solid #E2E8F0;
    transform: rotate(45deg);
    z-index: 1;
    pointer-events: none;
}
#poinPopup.is-flipped .poin-popup-arrow {
    top: auto;
    bottom: -6px;
    border-left: none;
    border-top: none;
    border-right: 1px solid #E2E8F0;
    border-bottom: 1px solid #E2E8F0;
}
#poinPopup .poin-popup-header {
    height: 50px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-bottom: 1px solid #E2E8F0;
}
#poinPopup .poin-popup-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #0F172A;
}
#poinPopup .poin-popup-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: #EFF6FF;
    color: #0D6EFD;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
}
#poinPopup .poin-popup-close {
    width: 30px;
    height: 30px;
    flex: 0 0 auto;
    border: 0;
    border-radius: 8px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    color: #64748B;
    font-size: 16px;
    line-height: 1;
    cursor: pointer;
    transition: background .15s ease, color .15s ease;
}
#poinPopup .poin-popup-close:hover {
    background: #F1F5F9;
    color: #0F172A;
}
#poinPopup .poin-popup-progress {
    padding: 12px 16px;
    border-bottom: 1px solid #E2E8F0;
}
#poinPopup .poin-progress-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
}
#poinPopup .poin-progress-count {
    font-size: 13px;
    font-weight: 600;
    color: #0F172A;
}
#poinPopup .poin-progress-pct {
    font-size: 12px;
    color: #64748B;
}
#poinPopup .poin-progress-track {
    height: 6px;
    border-radius: 999px;
    background: #E2E8F0;
    overflow: hidden;
}
#poinPopup .poin-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: #0D6EFD;
    transition: width .2s ease;
}
#poinPopup .poin-popup-body {
    max-height: 320px;
    overflow-y: auto;
    padding: 0;
}
#poinPopup .poin-list-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 0;
    padding: 11px 16px;
    border-bottom: 1px solid #E2E8F0;
    cursor: pointer;
    transition: background .15s ease;
}
#poinPopup .poin-list-item:last-child {
    border-bottom: none;
}
#poinPopup .poin-list-item:hover {
    background: #F8FAFC;
}
#poinPopup .poin-list-item .poin-check {
    flex: 0 0 auto;
    margin-top: 2px;
    cursor: pointer;
}
#poinPopup .poin-check:checked {
    background-color: #0D6EFD;
    border-color: #0D6EFD;
}
#poinPopup .poin-check:focus {
    border-color: #86B7FE;
    box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .2);
}
#poinPopup .poin-label-text {
    display: block;
    color: #334155;
    font-size: 13.5px;
    line-height: 1.4;
    overflow-wrap: anywhere;
    -webkit-line-clamp: unset;
    overflow: visible;
}

/* Modal aksi (Approve & Revisi) - modern, konsisten, tanpa duplikasi */
#approveModal .modal-content,
#revisionModal .modal-content {
    border: none;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .25);
    overflow: hidden;
}
#approveModal .modal-header,
#revisionModal .modal-header {
    border-bottom: 1px solid #eef2f7;
    padding: .9rem 1.1rem;
}
#approveModal .modal-title,
#revisionModal .modal-title {
    font-size: 1rem;
    font-weight: 600;
}
#revisionModal .modal-title i {
    color: #ef4444;
}
#approveModal .modal-footer,
#revisionModal .modal-footer {
    border-top: 1px solid #eef2f7;
    padding: .8rem 1.1rem;
}
.modal-status-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.modal-status-icon i { font-size: 2rem; }
.modal-status-icon.is-success { background: #e7f8f0; color: #10b981; }
.modal-doc-name {
    font-weight: 600;
    color: #172b4d;
    word-break: break-word;
}

.tooltip-inner > div {
    border-left: 3px solid #62B846;
    padding-left: 8px;
    margin-bottom: 2px;
}

/* Legacy dashboard-only rules removed during SIMADO redesign (replaced by scoped .simado-dash theme). */
</style>
@endpush

@push('styles')
<style>
/* ===== SIMADO Dashboard — scoped theme (only applies on this page) ===== */
html body { background: #F3F7FC; }
.simado-dash {
    --dash-navy: #0B1730;
    --dash-blue: #1687F8;
    --dash-blue-dark: #0f6fd6;
    --dash-bg: #F3F7FC;
    --dash-text: #172B4D;
    --dash-muted: #64748B;
    --dash-border: #E2EAF3;
    --dash-green: #10B981;
    --dash-amber: #F59E0B;
    --dash-red: #EF4444;
    color: var(--dash-text);
    padding-bottom: 1rem;
}

.dash-topbar {
    display: flex; align-items: center; justify-content: space-between;
    background: #fff; border: 1px solid var(--dash-border); border-radius: 14px;
    padding: .55rem .8rem; margin-bottom: 1.25rem; box-shadow: 0 1px 2px rgba(16,42,77,.04);
}
.dash-topbar .btn-toggle-dash {
    border: 0; background: transparent; color: #475569; width: 38px; height: 38px;
    border-radius: 9px; display: inline-flex; align-items: center; justify-content: center;
    font-size: 1.15rem; cursor: pointer; transition: all .15s ease;
}
.dash-topbar .btn-toggle-dash:hover { background: var(--dash-bg); color: var(--dash-blue); }
.dash-topbar-title { font-weight: 600; font-size: .95rem; color: var(--dash-text); }
.dash-date {
    display: inline-flex; align-items: center; gap: .45rem; font-size: .78rem; color: var(--dash-muted);
    background: var(--dash-bg); border: 1px solid var(--dash-border); padding: .4rem .75rem; border-radius: 8px;
}

.dash-welcome h1 { font-size: 1.5rem; font-weight: 700; letter-spacing: -.02em; color: var(--dash-text); margin: 0; }
.dash-welcome p { color: var(--dash-muted); font-size: .875rem; margin: .3rem 0 0; }

.simado-dash .card {
    border: 1px solid var(--dash-border); border-radius: 14px;
    box-shadow: 0 1px 2px rgba(16,42,77,.04), 0 1px 3px rgba(16,42,77,.05);
}
.simado-dash .card:hover { box-shadow: 0 6px 18px rgba(16,42,77,.07); }
.simado-dash .card-header {
    background: #fff; border-bottom: 1px solid var(--dash-border); padding: .9rem 1.15rem;
    font-weight: 600; font-size: .875rem; border-radius: 14px 14px 0 0 !important; color: var(--dash-text);
}

.dash-stat { display: flex; align-items: center; gap: .9rem; padding: 1rem 1.1rem; }
.dash-stat-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.icon-total { background: #E7F1FF; color: #1687F8; }
.icon-approved { background: #E6F7F1; color: #10B981; }
.icon-pending { background: #FEF4E6; color: #F59E0B; }
.icon-revision { background: #FDECEC; color: #EF4444; }
.dash-stat-value { font-size: 1.45rem; font-weight: 700; line-height: 1.1; color: var(--dash-text); }
.dash-stat-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--dash-muted); margin-top: .1rem; }

.dash-panel { background: #F8FAFD; border: 1px solid var(--dash-border); border-radius: 10px; padding: .75rem .85rem; }
.dash-folder-active { display: flex; align-items: center; gap: .6rem; background: #fff; border: 1px solid var(--dash-border); border-radius: 9px; padding: .5rem .7rem; }

.dash-progress-wrap { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.dash-progress { flex: 1; min-width: 0; }
.simado-dash #completenessProgress { border-radius: 999px; transition: width .5s ease; background: var(--dash-blue); }
#completenessProgress.bg-danger { background: var(--dash-red) !important; }
#completenessProgress.bg-warning { background: var(--dash-amber) !important; }
#completenessProgress.bg-success { background: var(--dash-green) !important; }
#completenessProgress.bg-gold { background: linear-gradient(135deg,#f59e0b,#d97706) !important; }
.dash-progress-value { font-size: 1.35rem; font-weight: 700; color: var(--dash-text); min-width: 62px; text-align: right; }

.simado-dash #poinFilterBar { background: #fff; }
.simado-dash #poinFilterBar .btn { border-radius: 999px; font-size: .72rem; padding: .28rem .7rem; }
.simado-dash #poinFilterBar .btn-outline-secondary { border-color: var(--dash-border); color: var(--dash-muted); }
.simado-dash #poinFilterBar .btn-outline-secondary:hover { background: var(--dash-bg); color: var(--dash-text); }
.simado-dash #poinFilterBar .btn.active { background: #E7F1FF; border-color: var(--dash-blue); color: var(--dash-blue); font-weight: 600; }
.simado-dash #poinFilterBar .btn.active::before { content: "\F26A"; font-family: "bootstrap-icons"; margin-right: 4px; }

.simado-dash #berkasCard .table { font-size: .8rem; table-layout: fixed; width: 100%; }
.simado-dash #berkasCard .table thead th {
    background: #F8FAFD; border-bottom: 1px solid var(--dash-border); font-weight: 600;
    font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: var(--dash-muted);
    padding: .65rem .7rem; white-space: nowrap;
}
.simado-dash #berkasCard .table tbody td { padding: .6rem .7rem; vertical-align: middle; border-color: #EEF3F9; }
.simado-dash #berkasCard .table tbody tr:hover { background: #F8FAFD; }
.simado-dash #berkasCard .table td { overflow: hidden; }
.simado-dash #berkasCard .table th:nth-child(1){ width: 48px; }
.simado-dash #berkasCard .table th:nth-child(2){ width: 24%; }
.simado-dash #berkasCard .table th:nth-child(3){ width: 15%; }
.simado-dash #berkasCard .table th:nth-child(4){ width: 120px; }
.simado-dash #berkasCard .table th:nth-child(5){ width: 140px; }
.simado-dash #berkasCard .table th:nth-child(6){ width: 120px; }
.simado-dash #berkasCard .table th:nth-child(7){ width: 110px; }
.simado-dash #berkasCard .table th:nth-child(8){ width: 170px; }
.simado-dash #berkasCard .chip-subfolder { background: #E7F1FF; color: #1687F8; border-radius: 6px; }
.simado-dash #berkasCard .chip { font-size: .7rem; padding: .2rem .5rem; }
.simado-dash #berkasCard .btn-icon { font-size: .78rem; }
.simado-dash #berkasCard .action-group { display: flex; gap: .25rem; flex-wrap: wrap; }

.simado-dash .badge { border-radius: 7px; font-weight: 600; letter-spacing: .01em; }
.simado-dash .badge-pending { background: #FEF4E6; color: #B45309; }
.simado-dash .badge-approved { background: #E6F7F1; color: #047857; }
.simado-dash .badge-revision { background: #FDECEC; color: #B91C1C; }

@media (max-width: 991.98px) { .dash-welcome h1 { font-size: 1.3rem; } }
@media (max-width: 575.98px) {
    .dash-stat { padding: .8rem .85rem; gap: .6rem; }
    .dash-stat-icon { width: 40px; height: 40px; font-size: 1.05rem; }
    .dash-stat-value { font-size: 1.2rem; }
    .dash-stat-label { font-size: .64rem; }
    .dash-welcome h1 { font-size: 1.15rem; }
}
</style>
@endpush

@section('content')
<div class="container-fluid px-0 simado-dash">
    <div class="dash-topbar d-none d-lg-flex">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn-toggle-dash" onclick="toggleDashSidebar()" aria-label="Ciutkan sidebar" title="Ciutkan sidebar">
                <i class="bi bi-list"></i>
            </button>
            <span class="dash-topbar-title">Dashboard</span>
        </div>
        <span class="dash-date"><i class="bi bi-calendar3"></i> {{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
    </div>

    <div class="dash-welcome mb-4">
        <h1>Selamat datang, {{ Auth::user()->nama ?? Auth::user()->username ?? 'Admin' }}</h1>
        <p>Kelola dokumen, folder, dan data dengan lebih mudah dan terintegrasi.</p>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="row g-3 mb-3" id="statCards">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="dash-stat">
                    <div class="dash-stat-icon icon-total"><i class="bi bi-files"></i></div>
                    <div class="min-w-0">
                        <div class="dash-stat-value" id="statTotal">0</div>
                        <div class="dash-stat-label">Total File</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="dash-stat">
                    <div class="dash-stat-icon icon-approved"><i class="bi bi-check-circle"></i></div>
                    <div class="min-w-0">
                        <div class="dash-stat-value" id="statApproved">0</div>
                        <div class="dash-stat-label">Disetujui</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="dash-stat">
                    <div class="dash-stat-icon icon-pending"><i class="bi bi-clock"></i></div>
                    <div class="min-w-0">
                        <div class="dash-stat-value" id="statPending">0</div>
                        <div class="dash-stat-label">Menunggu Review</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="dash-stat">
                    <div class="dash-stat-icon icon-revision"><i class="bi bi-pencil"></i></div>
                    <div class="min-w-0">
                        <div class="dash-stat-value" id="statRevision">0</div>
                        <div class="dash-stat-label">Butuh Revisi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="workspaceCard">
        <div class="card-header">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-hdd-stack text-primary"></i> Workspace Drive</span>
            <span class="badge bg-light text-dark border d-none d-sm-inline-flex align-items-center gap-1"><i class="bi bi-hdd"></i> {{ $config['folder_size'] ?? '0 B' }}</span>
        </div>
        <div class="card-body">
            <!-- Konfigurasi -->
            <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                <div class="flex-grow-1 min-w-0">
                    <div class="text-muted mb-1 d-flex align-items-center gap-1" style="font-size:.72rem;"><i class="bi bi-folder2 text-primary"></i> Folder aktif</div>
                    @if ($driveConfig && $driveConfig->folder_link)
                        <div class="dash-folder-active">
                            <i class="bi bi-folder-fill text-primary"></i>
                            <span class="fw-medium text-truncate" style="font-size:.8rem;">{{ $driveConfig->folder_link }}</span>
                        </div>
                    @else
                        <div class="dash-panel text-center">
                            <span class="text-muted small">Belum ada folder dikonfigurasi.</span>
                        </div>
                    @endif
                </div>
                <a href="{{ route('admin.drive-config') }}" class="btn btn-outline-primary btn-sm flex-shrink-0"><i class="bi bi-gear me-1"></i> Atur</a>
            </div>
            <!-- Navigasi -->
            <div class="dash-panel">
                <label class="form-label d-flex align-items-center gap-1 mb-2"><i class="bi bi-signpost-split text-muted"></i> Navigasi Folder</label>
                <nav id="breadcrumbNav" class="breadcrumb-nav mb-2" aria-label="Navigasi folder"></nav>
                <div class="subfolder-toolbar" id="subfolderToolbar" style="display:none;">
                    <input type="search" id="subfolderSearch" placeholder="Cari subfolder..." aria-label="Cari subfolder">
                    <span class="subfolder-count" id="subfolderCount"></span>
                </div>
                <div id="subfolderButtons" class="subfolder-grid subfolder-area"></div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-sm btn-primary" onclick="scanCurrent(false)"><i class="bi bi-search me-1"></i> Pindai</button>
                    <button class="btn btn-sm btn-outline-primary" onclick="scanCurrent(true)"><i class="bi bi-files me-1"></i> Scan All</button>
                </div>
                <input type="hidden" id="currentFolderId" value="">
                <input type="hidden" id="currentFolderName" value="(folder utama)">
            </div>
        </div>
    </div>

    <div class="card mb-3" id="progressCard">
        <div class="card-header">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-bar-chart-line text-primary"></i> Progres Kelengkapan</span>
        </div>
        <div class="card-body">
            <div id="progressEmpty" class="text-center py-1">
                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Lakukan scan folder untuk melihat progress kelengkapan.</small>
            </div>
            <div id="progressMain" style="display:none;">
                <div class="dash-progress-wrap">
                    <div class="dash-progress">
                        <div class="progress" style="height:12px;border-radius:999px;background:#E9F0F8;">
                            <div class="progress-bar" id="completenessProgress" style="width:0%;"></div>
                        </div>
                        <small class="text-muted d-block mt-2" id="progressText">0 dari 0 file disetujui</small>
                    </div>
                    <div class="dash-progress-value" id="progressPercent">0%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="berkasCard">
        <div class="card-header">
            <span class="d-flex align-items-center gap-2"><i class="bi bi-files text-primary"></i> Daftar Berkas Terbaru</span>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-light text-dark border" id="scanTime" style="display:none;"><i class="bi bi-clock me-1"></i><span></span></span>
                <span class="badge bg-light text-dark border" id="fileCount" style="display:none;"><i class="bi bi-file-earmark me-1"></i><span></span></span>
                <span class="badge bg-light text-dark border"><i class="bi bi-hdd me-1"></i>{{ $config['folder_size'] ?? '0 B' }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2 border-bottom d-flex flex-wrap align-items-center gap-1" id="poinFilterBar" style="display:none;">
                <span class="text-muted small me-1">Filter Poin:</span>
                <button class="btn btn-sm btn-outline-secondary active" data-filter="all">Semua</button>
                <button class="btn btn-sm btn-outline-secondary" data-filter="poin_pengukuran_kinerja">Pengukuran Kinerja</button>
                <button class="btn btn-sm btn-outline-secondary" data-filter="poin_dokumen_sumber">Dok. Sumber</button>
                <button class="btn btn-sm btn-outline-secondary" data-filter="poin_rencana_tindak_lanjut">RTL</button>
                <button class="btn btn-sm btn-outline-secondary" data-filter="poin_bukti_kegiatan">Kegiatan</button>
                <button class="btn btn-sm btn-outline-secondary" data-filter="poin_bukti_media_kinerja">Media Kinerja</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="uploadsTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Berkas</th>
                            <th>Subfolder</th>
                            <th>Status</th>
                            <th>Poin</th>
                            <th>Diupload</th>
                            <th>Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="uploadsBody">
                        <tr id="scanPromptRow">
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-search"></i>
                                    <p class="mb-0">Pilih folder dan klik <strong>Pindai</strong> untuk melihat daftar berkas.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div id="paginationControls"></div>
            </div>
        </div>
    </div>
</div>

<form id="approveForm" method="POST" style="display:none;">@csrf</form>
<form id="deleteForm" method="POST" style="display:none;">@csrf</form>

<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalTitle">Setujui Berkas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="modal-status-icon is-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                <p class="mb-2 small text-muted">Berkas berikut akan ditandai sebagai <strong>disetujui</strong>.</p>
                <p class="modal-doc-name mb-0" id="approveModalFileName"></p>
                <div class="alert alert-danger py-2 px-3 mt-3 mb-0 small d-none" id="approveModalError"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success px-4" id="approveModalConfirm"><i class="bi bi-check-lg me-1"></i>Ya, Setujui</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <i class="bi bi-trash text-danger" style="font-size:3rem;"></i>
                <h5 class="mt-3">Hapus Berkas</h5>
                <p class="text-muted mb-0 small" id="deleteModalFileName"></p>
                <p class="text-muted mb-0 small">File di Drive tidak akan terhapus dan tidak dapat dikembalikan.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger px-4" id="deleteModalConfirm"><i class="bi bi-trash me-1"></i>Ya, Hapus</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="revisionModal" tabindex="-1" aria-labelledby="revisionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="revisionForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="revisionModalTitle"><i class="bi bi-pencil-square me-2"></i>Catatan Revisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tugaskan ke User</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Pilih User --</option>
                            @foreach (\App\Models\User::where('role', 'user')->where('is_active', true)->orderBy('nama')->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->nama }} ({{ '@'.$u->username }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="catatanContainer">
                        <label class="form-label">Catatan Revisi</label>
                        <div class="input-group mb-2">
                            <input type="text" name="catatan[]" class="form-control" placeholder="Catatan revisi..." required>
                            <button type="button" class="btn btn-outline-success addCatatan"><i class="bi bi-plus-lg"></i></button>
                        </div>
                    </div>
                    <div class="alert alert-danger py-2 px-3 mb-0 small d-none" id="revisionModalError"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i> Kirim Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var navStack = [];
var _allFiles = [];
var _currentPage = 1;
var _perPage = 20;
var _activeFilter = 'all';

window.toggleDashSidebar = function() {
    document.body.classList.toggle('sidebar-collapsed');
};
$(document).on('turbo:before-cache', function() {
    document.body.classList.remove('sidebar-collapsed');
});

function getFilteredFiles() {
    if (_activeFilter === 'all') return _allFiles;
    return (_allFiles || []).filter(function(f) { return f[_activeFilter]; });
}

function pageInit() {
    navStack = [];
    updateBreadcrumb('', '(folder utama)');
    _allFiles = [];
    _currentPage = 1;
    _activeFilter = 'all';
    $('#uploadsBody').html(
        '<tr id="scanPromptRow"><td colspan="8">' +
        '<div class="empty-state"><i class="bi bi-search"></i>' +
        '<p class="mb-0">Pilih folder dan klik <strong>Pindai</strong> untuk melihat daftar berkas.</p>' +
        '</div></td></tr>'
    );
    $('#paginationControls').empty();
    $('#progressEmpty').show();
    $('#progressMain').hide();
    updateStatCards();
    $('#scanTime').hide();
    $('#fileCount').hide();
    $('#poinFilterBar').hide();
    $('[data-filter="all"]').addClass('active').siblings().removeClass('active');
    // auto-load anak Folder Utama tanpa scan file
    fetchSubfolders('', '(folder utama)');
}

$(document).off('turbo:load.dashboard').on('turbo:load.dashboard', pageInit);
if (!window.Turbo) $(document).ready(pageInit);

if (!window._dashboardEvents) {
    window._dashboardEvents = true;

    $(document).on('click', function(e) {
        var popup = $('#poinPopup');
        if (popup.length && !$(e.target).closest('.poin-badge, #poinPopup').length) {
            popup.remove();
        }
    });

    $(document).on('click', '.addCatatan', function() {
        const container = $('#catatanContainer');
        const group = $('<div class="input-group mb-2">');
        group.append('<input type="text" name="catatan[]" class="form-control" placeholder="Catatan revisi..." required>');
        group.append('<button type="button" class="btn btn-outline-danger removeCatatan"><i class="bi bi-x-lg"></i></button>');
        container.append(group);
    });

    $(document).on('click', '.removeCatatan', function() {
        $(this).closest('.input-group').remove();
    });

    $(document).on('click', '#approveModalConfirm', doApprove);
    $(document).on('click', '#deleteModalConfirm', doDeleteUpload);

    // Sinkronkan state modal aktif saat modal ditutup (termasuk via X / Batal / backdrop).
    $(document).on('hidden.bs.modal', '.modal', function() {
        if (this.id === _activeModal) _activeModal = null;
    });

    $(document).on('click', '.btn-scan-folder', function() {
        navigateToFolder($(this).data('folder-id'), $(this).data('folder-name'));
    });

    $(document).on('click', '.btn-breadcrumb-folder', function() {
        navigateToFolder($(this).data('folder-id'), $(this).data('folder-name'));
    });

    $(document).on('click', '.poin-badge', function(e) {
        e.stopPropagation();
        e.preventDefault();
        var tip = bootstrap.Tooltip.getInstance(this);
        if (tip) tip.hide();
        $('#poinPopup').remove();
        var badge = $(this);
        var key = badge.data('file-key');
        var file = findFileByKey(key);
        if (!file) return;

        var popup = $('<div id="poinPopup">')
            .attr('data-file-key', key)
            .html(
                '<div class="poin-popup-arrow"></div>' +
                '<div class="poin-popup-header">' +
                '<span class="poin-popup-title"><span class="poin-popup-icon"><i class="bi bi-check2-square"></i></span>Poin Checklist</span>' +
                '<button type="button" class="poin-popup-close" id="poinPopupClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>' +
                '</div>' +
                '<div class="poin-popup-progress">' + poinProgressHtml(file) + '</div>' +
                '<div class="poin-popup-body">' + poinChecklistHtml(file, key) + '</div>'
            )
            .appendTo('body');

        positionPoinPopup(popup, badge);
    });

    $(document).on('click', '#poinPopupClose', function() {
        $('#poinPopup').remove();
    });

    // Tooltip poin: didelegasikan agar badge yang baru dirender selalu dapat tooltip
    // tanpa digandakan (pakai getOrCreateInstance) dan tanpa kehilangan konten.
    $(document).on('mouseenter focusin', '.poin-badge[data-poin-tip]', function() {
        try {
            var opts = Object.assign({ title: this.getAttribute('data-poin-tip') || '' }, poinTooltipOptions);
            bootstrap.Tooltip.getOrCreateInstance(this, opts).show();
        } catch(e) {}
    });
    $(document).on('mouseleave focusout', '.poin-badge[data-poin-tip]', function() {
        try {
            var tip = bootstrap.Tooltip.getInstance(this);
            if (tip) tip.hide();
        } catch(e) {}
    });

    $(document).on('change', '.poin-check', function() {
        var cb = $(this);
        var key = cb.data('file-key');
        var uploadId = cb.data('upload-id');
        var poin = cb.data('poin');
        var file = findFileByKey(key);

        // Identitas wajib: tanpa file/ID yang jelas jangan menulis ke baris lain.
        if (!file || !uploadId) {
            cb.prop('checked', !cb.prop('checked'));
            showAppToast('danger', 'Poin ini tidak dapat disimpan.');
            return;
        }

        var desired = cb.is(':checked');
        file[poin] = desired; // optimistic; sumber kebenaran = objek file ini
        cb.prop('disabled', true);

        // Token per file per poin: respons usang tidak boleh menimpa state terbaru.
        file._poinReq = file._poinReq || {};
        var reqId = file._poinReq[poin] = (file._poinReq[poin] || 0) + 1;

        $.ajax({
            url: '{{ route("admin.toggle-poin") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                upload_id: uploadId,
                poin: poin,
                value: desired ? 1 : 0
            },
            success: function(res) {
                if (file._poinReq[poin] !== reqId) return; // sudah ada request lebih baru
                if (!res || !res.success) {
                    file[poin] = !desired;
                    applyFilePoinUpdate(file);
                    showAppToast('danger', 'Gagal menyimpan poin. Silakan coba lagi.');
                    return;
                }
                file[poin] = !!res.value;
                applyFilePoinUpdate(file);
            },
            error: function() {
                if (file._poinReq[poin] !== reqId) return;
                file[poin] = !desired; // revert; tidak menampilkan keberhasilan palsu
                applyFilePoinUpdate(file);
                showAppToast('danger', 'Gagal menyimpan poin. Silakan coba lagi.');
            },
            complete: function() {
                if (file._poinReq[poin] === reqId) cb.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '#poinFilterBar [data-filter]', function() {
        _activeFilter = $(this).data('filter');
        $(this).addClass('active').siblings().removeClass('active');
        _currentPage = 1;
        renderPage();
    });

    $(document).on('submit', '#revisionForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('[type="submit"]');
        if ($btn.prop('disabled')) return; // cegah pengiriman ganda
        document.activeElement?.blur();
        $('#revisionModalError').addClass('d-none').text('');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...');
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(res) {
                if (!res || !res.success) {
                    // Pertahankan modal + input agar admin dapat memperbaiki dan mencoba lagi.
                    $('#revisionModalError').removeClass('d-none').text('Gagal mengirim revisi. Silakan coba lagi.');
                    resetRevisionButton();
                    return;
                }
                var f = res.file;
                var $row = $('tr[data-db-id="' + f.database_id + '"]');
                $row.attr('data-status', 'revision');
                $row.find('td').eq(3).html(badgeHtml('revision'));
                $row.find('td').eq(7).html(buildActions(f));
                var matched = _allFiles.find(function(x) { return String(x.database_id) === String(f.database_id); });
                if (matched) matched.status = 'revision';
                updateProgress();
                updateStatCards();
                // Tutup modal hanya setelah berhasil, lalu bersihkan form untuk pemakaian berikutnya.
                bootstrap.Modal.getOrCreateInstance(document.getElementById('revisionModal')).hide();
                $form[0].reset();
                $('#catatanContainer .input-group:gt(0)').remove();
                resetRevisionButton();
            },
            error: function() {
                $('#revisionModalError').removeClass('d-none').text('Gagal mengirim revisi. Silakan coba lagi.');
                resetRevisionButton();
            }
        });
    });
}

var approveTargetId = null;
var revisionTargetId = null;

// Satu state modal aktif (null | 'approveModal' | 'revisionModal' | 'deleteModal').
// Hanya satu modal bisa terbuka agar tidak ada overlay bertumpuk.
var _activeModal = null;
var modalEls = ['approveModal', 'revisionModal', 'deleteModal'];
function showAppModal(id) {
    modalEls.forEach(function(other) {
        if (other === id) return;
        var inst = bootstrap.Modal.getInstance(document.getElementById(other));
        if (inst) inst.hide();
    });
    _activeModal = id;
    bootstrap.Modal.getOrCreateInstance(document.getElementById(id)).show();
}
function resetApproveButton() {
    $('#approveModalConfirm').prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Ya, Setujui');
}
function resetRevisionButton() {
    $('#revisionForm [type="submit"]').prop('disabled', false).html('<i class="bi bi-send me-1"></i> Kirim Revisi');
}

window.approve = function(id) {
    approveTargetId = id;
    var fileName = $('tr[data-db-id="' + id + '"] td').eq(1).text();
    $('#approveModalFileName').text(fileName || 'Berkas ini');
    $('#approveModalError').addClass('d-none').text('');
    resetApproveButton();
    showAppModal('approveModal');
};

function doApprove() {
    var id = approveTargetId;
    if (!id) return;
    var $btn = $('#approveModalConfirm');
    if ($btn.prop('disabled')) return; // cegah pengiriman ganda
    document.activeElement?.blur();
    $('#approveModalError').addClass('d-none').text('');
    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyetujui...');
    $.ajax({
        url: '{{ url("admin/approve") }}/' + id,
        method: 'POST',
        dataType: 'json',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            if (!res || !res.success) {
                $('#approveModalError').removeClass('d-none').text('Gagal menyetujui berkas. Silakan coba lagi.');
                resetApproveButton();
                return;
            }
            var f = res.file;
            var $row = $('tr[data-db-id="' + id + '"]');
            $row.attr('data-status', 'approved');
            $row.find('td').eq(3).html(badgeHtml('approved'));
            $row.find('td').eq(7).html(buildActions(f));
            var matched = _allFiles.find(function(x) { return String(x.database_id) === String(id); });
            if (matched) matched.status = 'approved';
            updateProgress();
            updateStatCards();
            // Tutup modal hanya setelah berhasil.
            bootstrap.Modal.getOrCreateInstance(document.getElementById('approveModal')).hide();
            resetApproveButton();
        },
        error: function() {
            // Pertahankan modal agar admin dapat mencoba lagi.
            $('#approveModalError').removeClass('d-none').text('Gagal menyetujui berkas. Silakan coba lagi.');
            resetApproveButton();
        }
    });
}

window.showRevision = function(id) {
    revisionTargetId = id;
    $('#revisionForm').attr('action', '{{ url("admin/revision") }}/' + id);
    $('#revisionModalError').addClass('d-none').text('');
    resetRevisionButton();
    showAppModal('revisionModal');
};

var deleteTargetId = null;

window.deleteUpload = function(id) {
    deleteTargetId = id;
    var fileName = $('tr[data-db-id="' + id + '"] td').eq(1).text();
    $('#deleteModalFileName').text(fileName || 'Berkas ini');
    showAppModal('deleteModal');
};

function doDeleteUpload() {
    var id = deleteTargetId;
    if (!id) return;
    document.activeElement?.blur();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).hide();
    $.ajax({
        url: '{{ url("admin/delete-upload") }}/' + id,
        method: 'POST',
        dataType: 'json',
        data: { _token: $('meta[name="csrf-token"]').attr('content') },
        success: function(res) {
            if (!res.success) return;
            var idx = _allFiles.findIndex(function(f) { return String(f.database_id) === String(id); });
            if (idx !== -1) _allFiles.splice(idx, 1);
            renderPage();
        },
        error: function() {
            showAppToast('danger', 'Gagal menghapus berkas. Silakan coba lagi.');
        }
    });
};

window.navigateToFolder = function(folderId, folderName) {
    $('#currentFolderId').val(folderId);
    $('#currentFolderName').val(folderName);
    updateBreadcrumb(folderId, folderName);
    // reset tabel ke prompt sampai Pindai ditekan
    $('#uploadsBody').html(
        '<tr id="scanPromptRow"><td colspan="8">' +
        '<div class="empty-state"><i class="bi bi-search"></i>' +
        '<p class="mb-0">Pilih folder dan klik <strong>Pindai</strong> untuk melihat daftar berkas.</p>' +
        '</div></td></tr>'
    );
    $('#paginationControls').empty();
    fetchSubfolders(folderId, folderName);
};
window.scanFolder = window.navigateToFolder; // backward compat

let _allSubfolders = [];
function renderSubfolders(filter) {
    const q = (filter || '').toLowerCase();
    const list = q ? _allSubfolders.filter(function(s){ return s.name.toLowerCase().includes(q); }) : _allSubfolders;
    $('#subfolderCount').text(list.length ? list.length + ' folder' : '');
    if (!list.length) {
        $('#subfolderButtons').html(q ? '<div class="subfolder-empty"><i class="bi bi-search"></i> Tidak ada hasil untuk "'+esc(filter)+'"</div>' : '<div class="subfolder-empty"><i class="bi bi-folder-x"></i> Tidak ada subfolder di folder ini</div>');
        return;
    }
    var h = '';
    list.forEach(function(sf) {
        h += '<button class="folder-card btn-scan-folder" data-folder-id="' + esc(sf.id) + '" data-folder-name="' + esc(sf.name) + '" aria-label="Buka folder '+esc(sf.name)+'">'
           + '<span class="folder-icon"><i class="bi bi-folder-fill"></i></span>'
           + '<span class="folder-name">' + esc(sf.name) + '</span>'
           + '<i class="bi bi-chevron-right folder-chevron"></i>'
           + '</button>';
    });
    $('#subfolderButtons').html(h);
}
function fetchSubfolders(folderId, folderName) {
    $('#subfolderButtons').addClass('subfolder-skeleton').html('<div class="skel"></div><div class="skel"></div><div class="skel"></div><div class="skel"></div>');
    $('#subfolderToolbar').hide();
    $.ajax({
        url: '{{ route("admin.list-subfolders") }}',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            parent_id: folderId,
            parent_name: folderName
        },
        success: function(res) {
            _allSubfolders = res.subfolders || [];
            $('#subfolderButtons').removeClass('subfolder-skeleton');
            $('#subfolderToolbar').toggle(_allSubfolders.length > 4);
            $('#subfolderSearch').val('');
            renderSubfolders('');
        },
        error: function() {
            $('#subfolderButtons').removeClass('subfolder-skeleton').html('<div class="subfolder-empty"><i class="bi bi-exclamation-triangle"></i> Gagal memuat subfolder</div>');
        }
    });
}
$(document).on('input', '#subfolderSearch', function(){ renderSubfolders($(this).val()); });

window.scanCurrent = function(scanAll) {
    var id = $('#currentFolderId').val();
    var name = $('#currentFolderName').val();
    doScan(id, name, scanAll);
};

function doScan(folderId, folderName, scanAll) {
    if (window._scanXhr) window._scanXhr.abort();
    window._scanXhr = $.ajax({
        url: '{{ route("admin.scan-folder") }}',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            parent_id: folderId,
            parent_name: folderName,
            scan_all: scanAll ? 1 : 0
        },
        beforeSend: function() {
            $('#uploadsBody').html('<tr><td colspan="8"><div class="empty-state"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0">Memindai folder...</p></div></td></tr>');
        },
        success: function(res) {
            window._allFiles = res.files || [];
            window._currentPage = 1;
            window._perPage = 20;
            _activeFilter = 'all';
            renderPage();
            if (res.files && res.files.length > 0) {
                $('#scanTime').show().find('span').text(res.waktu || '-');
                $('#fileCount').show().find('span').text(res.files.length + ' berkas');
        $('#poinFilterBar').show();
        $('[data-filter="all"]').addClass('active').siblings().removeClass('active');
        updatePoinCounts();
            } else {
                $('#scanTime').hide();
                $('#fileCount').hide();
                $('#poinFilterBar').hide();
            }

            $('#currentFolderId').val(res.folder_id || '');
            $('#currentFolderName').val(res.folder_name || '(folder utama)');

            _allSubfolders = res.subfolders || [];
            $('#subfolderToolbar').toggle(_allSubfolders.length > 4);
            $('#subfolderSearch').val('');
            renderSubfolders('');
            $('#subfolderButtons').removeClass('subfolder-skeleton');

            updateBreadcrumb(res.folder_id || '', res.folder_name || '(folder utama)');
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal memindai folder.';
            $('#uploadsBody').html('<tr><td colspan="8"><div class="empty-state text-danger"><i class="bi bi-exclamation-triangle"></i><p class="mb-0">' + msg + '</p></div></td></tr>');
        }
    });
}

function updateBreadcrumb(folderId, folderName) {
    if (!folderId) {
        navStack = [];
    } else {
        var idx = -1;
        for (var i = 0; i < navStack.length; i++) {
            if (navStack[i].id === folderId) {
                idx = i;
                break;
            }
        }

        if (idx === -1) {
            navStack.push({ id: folderId, name: folderName });
        } else {
            navStack = navStack.slice(0, idx + 1);
        }
    }

    var h = '<button class="crumb crumb-root btn-breadcrumb-folder" data-folder-id="" data-folder-name="(folder utama)" aria-label="Kembali ke Folder Utama"><i class="bi bi-house-door"></i> Folder Utama</button>';
    navStack.forEach(function(n) {
        h += '<i class="bi bi-chevron-right crumb-sep"></i>';
        h += '<button class="crumb crumb-sub btn-breadcrumb-folder" data-folder-id="' + esc(n.id) + '" data-folder-name="' + esc(n.name) + '" aria-label="Buka ' + esc(n.name) + '">' + esc(n.name) + '</button>';
    });
    $('#breadcrumbNav').html(h);
}

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

function badgeHtml(status) {
    const map = { pending: ['badge-pending', 'bi-clock', 'Menunggu'], approved: ['badge-approved', 'bi-check-circle', 'Disetujui'], revision: ['badge-revision', 'bi-pencil', 'Butuh Revisi'] };
    const m = map[status] || map['pending'];
    return '<span class="badge ' + m[0] + '"><i class="bi ' + m[1] + ' me-1"></i>' + m[2] + '</span>';
}

function isTracked(s) { return s && s !== 'untracked'; }

function buildActions(f) {
    const dbId = f.database_id;
    const driveId = f.drive_file_id || f.id;
    let h = '<div class="action-group">';
    if (dbId && isTracked(f.status)) {
        h += '<a href="' + '{{ url("admin/download") }}/' + dbId + '" class="btn btn-sm btn-outline-primary btn-icon" title="Download"><i class="bi bi-download"></i></a>';
        h += '<a href="' + '{{ url("admin/preview") }}/' + dbId + '" class="btn btn-sm btn-outline-info btn-icon" title="Preview" target="_blank"><i class="bi bi-eye"></i></a>';
    }
    if (f.status === 'pending' || f.status === 'revision') {
        h += '<button type="button" class="btn btn-sm btn-outline-success btn-icon" title="Setujui" onclick="approve(\'' + esc(dbId || driveId) + '\')"><i class="bi bi-check-lg"></i></button>';
        h += '<button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Minta Revisi" onclick="showRevision(\'' + esc(dbId || driveId) + '\')"><i class="bi bi-pencil"></i></button>';
    }
    if (isTracked(f.status) && dbId) {
        h += '<button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus" onclick="deleteUpload(\'' + esc(String(dbId)) + '\')"><i class="bi bi-trash"></i></button>';
    }
    h += '</div>';
    if (f.revision_histories && f.revision_histories.length > 0) {
        var revId = 'rev-' + esc(String(dbId || driveId || '')).replace(/[^a-zA-Z0-9]/g, '');
        if (!revId || revId === 'rev-') revId = 'rev-' + Date.now();
        h += '<div class="mt-2"><button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#' + revId + '" aria-expanded="false">';
        h += '<i class="bi bi-arrow-clockwise me-1"></i> Riwayat Revisi (' + f.revision_histories.length + ')</button>';
        h += '<div class="collapse mt-1" id="' + revId + '"><div class="bg-light rounded-2 p-2 small">';
        f.revision_histories.forEach(function(r) {
            const notes = Array.isArray(r.catatan_admin) ? r.catatan_admin.join('; ') : (r.catatan_admin || '-');
            h += '<div class="d-flex align-items-start gap-2 mb-1 pb-1 border-bottom border-light"><span class="text-nowrap text-muted" style="font-size:.7rem;">' + esc(r.tanggal || '-') + '</span><span class="fw-semibold">' + esc(r.submitted_by || '') + '</span><span>' + esc(notes) + '</span></div>';
        });
        h += '</div></div></div>';
    }
    if (f.catatan_user) {
        h += '<div class="mt-1 small text-muted"><i class="bi bi-chat-dots me-1"></i> <strong>Catatan:</strong> ' + esc(f.catatan_user) + '</div>';
    }
    return h;
}

var poinKeys = ['poin_pengukuran_kinerja', 'poin_dokumen_sumber', 'poin_rencana_tindak_lanjut', 'poin_bukti_kegiatan', 'poin_bukti_media_kinerja'];
var poinLabels = @json(\App\Models\Upload::poinLabels());
var poinShortLabels = {
    'poin_pengukuran_kinerja': 'Pengukuran Kinerja',
    'poin_dokumen_sumber': 'Dok. Sumber',
    'poin_rencana_tindak_lanjut': 'RTL',
    'poin_bukti_kegiatan': 'Kegiatan',
    'poin_bukti_media_kinerja': 'Media Kinerja',
};

function poinCount(f) {
    return poinKeys.filter(function(k) { return f[k]; }).length;
}

function firstCheckedPoin(f) {
    for (var i = 0; i < poinKeys.length; i++) {
        if (f[poinKeys[i]]) return poinKeys[i];
    }
    return null;
}

function poinTooltipHtml(f) {
    var h = '';
    for (var i = 0; i < poinKeys.length; i++) {
        if (f[poinKeys[i]]) {
            h += '<div class="d-flex align-items-center gap-1 py-1"><i class="bi bi-check-circle-fill text-success"></i> ' + esc(poinLabels[poinKeys[i]]) + '</div>';
        }
    }
    return h || '<div class="py-1">Belum ada poin checklist.</div>';
}

function buildPoinCell(f) {
    var count = poinCount(f);
    var key = fileKeyOf(f);
    var badgeHtml = '';
    var tip = '';

    if (count === 0) {
        tip = poinTooltipHtml(f);
        badgeHtml = '<span class="badge bg-light text-dark px-2 py-1 border poin-badge poin-badge-empty" role="button" tabindex="0" style="cursor:pointer;" aria-label="Poin checklist" data-file-key="' + escAttr(key) + '" data-poin-tip="' + escAttr(tip) + '"><i class="bi bi-square me-1"></i>Poin</span>';
    } else if (count === 1) {
        var k1 = firstCheckedPoin(f);
        tip = poinTooltipHtml(f);
        badgeHtml = '<span class="badge bg-success px-2 py-1 poin-badge" role="button" tabindex="0" style="cursor:pointer;" aria-label="' + escAttr(poinLabels[k1]) + '" data-file-key="' + escAttr(key) + '" data-poin-tip="' + escAttr(tip) + '"><i class="bi bi-check-square me-1"></i>' + esc(poinShortLabels[k1]) + '</span>';
    } else {
        var k2 = firstCheckedPoin(f);
        var cls = count === 5 ? 'poin-badge-full' : 'bg-success';
        tip = poinTooltipHtml(f);
        badgeHtml = '<span class="badge ' + cls + ' px-2 py-1 poin-badge" role="button" tabindex="0" style="cursor:pointer;" data-file-key="' + escAttr(key) + '" data-poin-tip="' + escAttr(tip) + '"><i class="bi bi-check-square me-1"></i>' + esc(poinShortLabels[k2]) + ' +' + (count - 1) + '</span>';
    }

    return '<td class="poin-cell">' + badgeHtml + '</td>';
}

// State checklist selalu dibaca dari objek file (satu sumber kebenaran), bukan dari klon DOM.
function fileKeyOf(f) {
    if (!f) return '';
    if (f.database_id !== undefined && f.database_id !== null && String(f.database_id) !== '') return 'db-' + String(f.database_id);
    if (f.drive_file_id) return 'dr-' + String(f.drive_file_id);
    return '';
}

function findFileByKey(key) {
    if (!key) return null;
    return (_allFiles || []).find(function(f) { return fileKeyOf(f) === key; }) || null;
}

function escAttr(str) {
    if (str === undefined || str === null) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Posisi popup: dekat badge, flip ke atas bila ruang bawah tidak cukup,
// dan selalu dikunci di dalam viewport (tidak keluar kiri/kanan).
function positionPoinPopup(popup, badge) {
    var pos = badge.offset();
    var badgeW = badge.outerWidth();
    var badgeH = badge.outerHeight();
    var popupW = popup.outerWidth();
    var popupH = popup.outerHeight();
    var vw = window.innerWidth;
    var vh = window.innerHeight;
    var scrollTop = $(window).scrollTop();
    var scrollLeft = $(window).scrollLeft();
    var gap = 8;

    var left = pos.left + badgeW - popupW;
    left = Math.max(scrollLeft + gap, Math.min(left, scrollLeft + vw - popupW - gap));

    var belowTop = pos.top + badgeH + gap;
    var aboveTop = pos.top - popupH - gap;
    var viewBottom = scrollTop + vh;

    if (belowTop + popupH > viewBottom - gap && aboveTop >= scrollTop + gap) {
        popup.addClass('is-flipped').css({ top: aboveTop, left: left });
    } else {
        popup.removeClass('is-flipped').css({ top: belowTop, left: left });
    }

    var badgeCenter = pos.left + badgeW / 2;
    var arrowLeft = Math.max(12, Math.min(badgeCenter - left - 6, popupW - 24));
    popup.find('.poin-popup-arrow').css('left', arrowLeft);
}

function poinProgressHtml(f) {
    var checkedCount = poinCount(f);
    var totalItems = poinKeys.length;
    var pct = totalItems ? Math.round(checkedCount / totalItems * 100) : 0;
    return '<div class="poin-progress-top">' +
        '<span class="poin-progress-count">' + checkedCount + '/' + totalItems + '</span>' +
        '<span class="poin-progress-pct">' + pct + '%</span>' +
        '</div>' +
        '<div class="poin-progress-track" role="progressbar" aria-valuenow="' + checkedCount +
        '" aria-valuemin="0" aria-valuemax="' + totalItems + '">' +
        '<div class="poin-progress-bar" style="width:' + pct + '%;"></div>' +
        '</div>';
}

function poinChecklistHtml(f, key) {
    var dbId = (f && f.database_id !== undefined && f.database_id !== null && String(f.database_id) !== '') ? String(f.database_id) : '';
    var h = '';
    poinKeys.forEach(function(k) {
        h += '<label class="poin-list-item">';
        h += '<input type="checkbox" class="form-check-input poin-check" data-file-key="' + escAttr(key) + '" data-upload-id="' + escAttr(dbId) + '" data-poin="' + k + '" ' + (f[k] ? 'checked' : '') + (dbId ? '' : ' disabled') + '>';
        h += '<span class="poin-label-text">' + esc(poinLabels[k]) + '</span>';
        h += '</label>';
    });
    return h;
}

// Perbarui UI (badge, popup, filter) dari objek file yang benar saja.
function applyFilePoinUpdate(file) {
    var key = fileKeyOf(file);
    if (!key) return;

    var $popup = $('#poinPopup');
    if ($popup.length && $popup.attr('data-file-key') === key) {
        $popup.find('.poin-popup-progress').html(poinProgressHtml(file));
        $popup.find('.poin-popup-body').html(poinChecklistHtml(file, key));
    }

    var $row = $('#uploadsTable tr[data-file-key="' + key + '"]');
    if ($row.length) {
        destroyPoinTooltips();
        $row.find('.poin-cell').replaceWith(buildPoinCell(file));
        initPoinTooltips();

        var activeFilter = $('#poinFilterBar .active').data('filter');
        if (activeFilter && activeFilter !== 'all' && !file[activeFilter]) $row.hide();
    }

    updatePoinCounts();
}

var poinTooltipOptions = {
    html: true,
    sanitize: false,
    placement: 'top',
    fallbackPlacements: ['bottom'],
    container: 'body',
    trigger: 'manual',
    boundary: 'viewport'
};

function destroyPoinTooltips() {
    try {
        document.querySelectorAll('#uploadsTable .poin-badge[data-poin-tip]').forEach(function(el) {
            var tip = bootstrap.Tooltip.getInstance(el);
            if (tip) tip.dispose();
        });
        // Buang tooltip yang tertinggal di DOM setelah tabel dirender ulang.
        document.querySelectorAll('body > .tooltip').forEach(function(el) { el.remove(); });
    } catch(e) {}
}

function initPoinTooltips() {
    try {
        document.querySelectorAll('#uploadsTable .poin-badge[data-poin-tip]').forEach(function(el) {
            var opts = Object.assign({ title: el.getAttribute('data-poin-tip') || '' }, poinTooltipOptions);
            bootstrap.Tooltip.getOrCreateInstance(el, opts); // tidak menggandakan instance
        });
    } catch(e) {}
}

function updateProgress() {
    var files = getFilteredFiles();
    var total = files.length;
    if (total === 0) {
        $('#completenessProgress').css('width', '0%');
        $('#progressEmpty').show();
        $('#progressMain').hide();
        return;
    }
    $('#progressEmpty').hide();
    $('#progressMain').show();
    var approved = files.filter(function(f) { return f.status === 'approved'; }).length;
    var pct = total ? Math.round(approved / total * 100) : 0;
    var $bar = $('#completenessProgress');
    $bar.css('width', pct + '%').removeClass('bg-danger bg-warning bg-success bg-gold');
    if (pct === 100) $bar.addClass('bg-gold');
    else if (pct >= 80) $bar.addClass('bg-success');
    else if (pct >= 50) $bar.addClass('bg-warning');
    else $bar.addClass('bg-danger');
    $('#progressText').text(approved + ' dari ' + total + ' file disetujui');
    $('#progressPercent').text(pct + '%');
}

function updateStatCards() {
    var files = getFilteredFiles();
    $('#statTotal').text(files.length);
    $('#statApproved').text(files.filter(function(f) { return f.status === 'approved'; }).length);
    $('#statPending').text(files.filter(function(f) { return f.status === 'pending'; }).length);
    $('#statRevision').text(files.filter(function(f) { return f.status === 'revision'; }).length);
}

function updatePoinCounts() {
    var files = _allFiles || [];
    var poinCounts = {}, totalFiles = files.length;
    files.forEach(function(f) {
        poinKeys.forEach(function(k) {
            if (f[k]) poinCounts[k] = (poinCounts[k] || 0) + 1;
        });
    });
    $('#poinFilterBar [data-filter]').each(function() {
        var btn = $(this);
        var filter = btn.data('filter');
        var label = btn.text().replace(/\s*\(\d+\)$/, '');
        btn.text(label + ' (' + (filter === 'all' ? totalFiles : (poinCounts[filter] || 0)) + ')');
    });
}

function renderPage() {
    var files = getFilteredFiles();
    var page = _currentPage || 1;
    var perPage = _perPage || 20;
    var total = files.length;
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    if (page > totalPages) page = totalPages;
    if (page < 1) page = 1;
    _currentPage = page;

    var start = (page - 1) * perPage;
    var end = Math.min(start + perPage, total);
    var pageFiles = files.slice(start, end);

    destroyPoinTooltips();
    $('#uploadsBody').html(buildTableRows(pageFiles, page, perPage));
    renderPagination(total, page, perPage);

    initPoinTooltips();
    updateProgress();
    updateStatCards();
}

function renderPagination(total, page, perPage) {
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    var h = '<div class="d-flex align-items-center justify-content-between px-3 py-2 border-top">';
    h += '<span class="text-muted small">' + total + ' berkas</span>';
    h += '<div class="d-flex align-items-center gap-1">';
    h += '<button class="btn btn-sm btn-outline-secondary" onclick="goPage(' + (page - 1) + ')"' + (page <= 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left"></i></button>';
    h += '<span class="small text-muted px-2">' + page + ' / ' + totalPages + '</span>';
    h += '<button class="btn btn-sm btn-outline-secondary" onclick="goPage(' + (page + 1) + ')"' + (page >= totalPages ? ' disabled' : '') + '><i class="bi bi-chevron-right"></i></button>';
    h += '</div></div>';
    $('#paginationControls').html(h);
}

function goPage(p) {
    if (p < 1) p = 1;
    _currentPage = p;
    renderPage();
}

function buildTableRows(files, page, perPage) {
    if (!files || files.length === 0) {
        return '<tr><td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i><p class="mb-0">Tidak ada berkas ditemukan.</p></div></td></tr>';
    }
    var startOffset = ((page || 1) - 1) * (perPage || 1);
    let h = '';
    files.forEach(function(f, i) {
        h += '<tr data-status="' + esc(f.status) + '" data-db-id="' + esc(String(f.database_id || '')) + '" data-file-key="' + escAttr(fileKeyOf(f)) + '">';
        h += '<td>' + (startOffset + i + 1) + '</td>';
        h += '<td class="fw-medium text-truncate" title="' + esc(f.filename || '-') + '">' + esc(f.filename || '-') + '</td>';
        h += '<td><span class="chip chip-subfolder text-truncate" title="' + esc(f.subfolder || '(folder utama)') + '"><i class="bi bi-folder"></i> ' + esc(f.subfolder || '(folder utama)') + '</span></td>';
        h += '<td>' + badgeHtml(f.status) + '</td>';
        h += buildPoinCell(f);
        h += '<td class="text-muted small">' + esc(f.upload_time ? f.upload_time : '-') + '</td>';
        h += '<td class="text-muted small">' + esc(f.uploader_name || '-') + '</td>';
        h += '<td>' + buildActions(f) + '</td>';
        h += '</tr>';
    });
    return h;
}
</script>
@endpush