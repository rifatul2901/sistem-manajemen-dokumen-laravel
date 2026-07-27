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
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
#poinPopup {
    animation: poinPopupFade .18s ease-out;
    font-size: .85rem;
}
#poinPopup .poin-popup-arrow {
    position: absolute;
    top: -6px;
    width: 12px;
    height: 12px;
    background: inherit;
    border-left: 1px solid #dee2e6;
    border-top: 1px solid #dee2e6;
    transform: rotate(45deg);
    z-index: 1;
    pointer-events: none;
}
#poinPopup .poin-popup-header {
    background: #f8f9fa;
    border-radius: 6px 6px 0 0;
}
#poinPopup .poin-popup-body {
    padding: .4rem 0;
}
#poinPopup .poin-popup-body .list-group-item {
    padding: .5rem .75rem !important;
    font-size: .8rem;
}
#poinPopup .poin-popup-body .list-group-item:hover {
    background-color: #f0f7ff !important;
}

.tooltip-inner > div {
    border-left: 3px solid #62B846;
    padding-left: 8px;
    margin-bottom: 2px;
}

#poinFilterBar .btn.active {
    background: #e7f1ff;
    border-color: #0d6efd;
    color: #0d6efd;
}
#poinFilterBar .btn.active::before {
    content: "\F26A";
    font-family: "bootstrap-icons";
    margin-right: 4px;
}

.card-compact .card-body {
    padding: .75rem 1rem .5rem;
}
.card-compact .card-body .form-label {
    font-size: .75rem;
    margin-bottom: .2rem;
}
.card-compact .btn-sm {
    font-size: .75rem;
    padding: .3rem .6rem;
}
.card-compact .badge {
    font-size: .7rem;
    padding: .25rem .5rem;
}
.card-compact .text-muted.small {
    font-size: .7rem;
}
.card-compact .fw-medium.text-truncate {
    font-size: .75rem;
}

#completenessProgress {
    border-radius: 7px;
    transition: width .5s ease;
}
#completenessProgress.bg-danger { background: #ef4444; }
#completenessProgress.bg-warning { background: #f59e0b; }
#completenessProgress.bg-success { background: #62B846; }
#completenessProgress.bg-gold {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.stat-card .card-body {
    padding: .45rem .70rem;
}
.stat-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    flex-shrink: 0;
}
.stat-icon-pending {
    background: #fff3cd;
    color: #856404;
}
.stat-icon-approved {
    background: #d4edda;
    color: #155724;
}
.stat-icon-revision {
    background: #f8d7da;
    color: #721c24;
}
.stat-number {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.2;
    color: #0f172a;
}
.stat-label {
    font-size: .60rem;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: .03em;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Dashboard</h4>
            <p class="text-muted small mb-0 mt-1">Pindai folder, review kelengkapan dokumen, dan kelola revisi.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 card-compact">
                <div class="card-header d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-google text-muted"></i> Konfigurasi Folder Google Drive
                </div>
                <div class="card-body d-flex flex-column">
                    @if ($driveConfig && $driveConfig->folder_link)
                        <div class="text-muted small mb-1">Folder aktif:</div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-folder2 text-primary" style="font-size:.8rem;"></i>
                            <span class="fw-medium text-truncate">{{ $driveConfig->folder_link }}</span>
                        </div>
                        <div class="text-muted small mb-2">
                            <i class="bi bi-hdd me-1"></i> {{ $config['folder_size'] ?? '0 B' }}
                        </div>
                    @else
                        <div class="d-flex align-items-center justify-content-center mb-2" style="min-height:2rem;">
                            <div class="text-center">
                                <p class="text-muted small mb-0">Belum ada folder dikonfigurasi.</p>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('admin.drive-config') }}" class="btn btn-outline-primary btn-sm mt-auto">
                        <i class="bi bi-gear me-1"></i> Atur
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 card-compact">
                <div class="card-header d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-folder2-open text-muted"></i> Pindai Folder
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Navigasi Folder</label>
                        <div id="breadcrumbNav" class="d-flex flex-wrap align-items-center gap-1 mb-1"></div>
                        <div id="subfolderButtons" class="d-flex flex-wrap gap-1"></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary py-1" onclick="scanCurrent(false)"><i class="bi bi-search me-1"></i> Pindai</button>
                        <button class="btn btn-sm btn-outline-primary py-1" onclick="scanCurrent(true)"><i class="bi bi-files me-1"></i> Scan All</button>
                    </div>
                    <input type="hidden" id="currentFolderId" value="">
                    <input type="hidden" id="currentFolderName" value="(folder utama)">
                </div>
            </div>
        </div>
    </div>

    <div class="card card-compact mb-4" id="progressCard">
        <div class="card-header d-flex align-items-center gap-2 py-2">
            <i class="bi bi-bar-chart-line text-muted"></i> Progress Kelengkapan
        </div>
        <div class="card-body">
            <div id="progressEmpty">
                <div class="text-center py-1">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Lakukan scan folder untuk melihat progress kelengkapan.</small>
                </div>
            </div>
            <div id="progressMain" style="display:none;">
                <div class="progress" style="height:14px;border-radius:7px;">
                    <div class="progress-bar" id="completenessProgress" style="width:0%;"></div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted" id="progressText">0 dari 0 file disetujui</small>
                    <small class="fw-bold" id="progressPercent">0%</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4" id="statCards" style="display:none;">
        <div class="col-md-4">
            <div class="card card-compact h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon stat-icon-pending"><i class="bi bi-clock"></i></div>
                    <div>
                        <div class="stat-number" id="statPending">0</div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-compact h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon stat-icon-approved"><i class="bi bi-check-circle"></i></div>
                    <div>
                        <div class="stat-number" id="statApproved">0</div>
                        <div class="stat-label">Disetujui</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-compact h-100 stat-card">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon stat-icon-revision"><i class="bi bi-pencil"></i></div>
                    <div>
                        <div class="stat-number" id="statRevision">0</div>
                        <div class="stat-label">Butuh Revisi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card" id="berkasCard">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="bi bi-files me-1"></i> Daftar Berkas</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark px-3 py-2" id="scanTime" style="display:none;"><i class="bi bi-clock me-1"></i><span></span></span>
                <span class="badge bg-light text-dark px-3 py-2" id="fileCount" style="display:none;"><i class="bi bi-file-earmark me-1"></i><span></span></span>
                <span class="badge bg-light text-dark px-3 py-2">Ukuran: {{ $config['folder_size'] ?? '0 B' }}</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="px-3 pt-2 pb-1 border-bottom d-flex flex-wrap align-items-center gap-1" id="poinFilterBar" style="display:none;">
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
                            <th style="width:50px;">No</th>
                            <th>Nama Berkas</th>
                            <th>Subfolder</th>
                            <th>Status</th>
                            <th>Poin</th>
                            <th>Diupload</th>
                            <th>Oleh</th>
                            <th style="width:180px;">Aksi</th>
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
            </div>
        </div>
    </div>
</div>

<form id="approveForm" method="POST" style="display:none;">@csrf</form>
<form id="deleteForm" method="POST" style="display:none;">@csrf</form>

<div class="modal fade" id="revisionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="revisionForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-1"></i> Catatan Revisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tugaskan ke User</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Pilih User --</option>
                            @foreach (\App\Models\User::where('role', 'user')->where('is_active', true)->orderBy('nama')->get() as $u)
                                <option value="{{ $u->id }}">{{ $u->nama }} (@<span>{{ $u->username }}</span>)</option>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i> Kirim Revisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var navStack = []; // {id, name}

$(document).ready(function() {
    $(document).on('click', function(e) {
        var popup = $('#poinPopup');
        if (popup.length && !$(e.target).closest('.poin-badge, #poinPopup').length) {
            popup.remove();
        }
    });
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

window.approve = function(id) {
    if (!confirm('Setujui berkas ini?')) return;
    $.ajax({
        url: '{{ url("admin/approve") }}/' + id,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(res) {
            if (!res.success) return;
            var f = res.file;
            var $row = $('tr[data-db-id="' + id + '"]');
            $row.attr('data-status', 'approved');
            $row.find('td').eq(3).html(badgeHtml('approved'));
            var actions = '<div class="action-group">';
            if (f.database_id) {
                actions += '<a href="{{ url("admin/download") }}/' + f.database_id + '" class="btn btn-sm btn-outline-primary btn-icon" title="Download"><i class="bi bi-download"></i></a>';
                actions += '<a href="{{ url("admin/preview") }}/' + f.database_id + '" class="btn btn-sm btn-outline-info btn-icon" title="Preview" target="_blank"><i class="bi bi-eye"></i></a>';
                actions += '<button type="button" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus" onclick="deleteUpload(\'' + f.database_id + '\')"><i class="bi bi-trash"></i></button>';
            }
            if (f.revision_histories && f.revision_histories.length > 0) {
                var revId = 'rev-' + f.database_id;
                actions += '<div class="mt-2"><button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#' + revId + '" aria-expanded="false">';
                actions += '<i class="bi bi-arrow-clockwise me-1"></i> Riwayat Revisi (' + f.revision_histories.length + ')</button>';
                actions += '<div class="collapse mt-1" id="' + revId + '"><div class="bg-light rounded-2 p-2 small">';
                f.revision_histories.forEach(function(r) {
                    var notes = Array.isArray(r.catatan_admin) ? r.catatan_admin.join('; ') : (r.catatan_admin || '-');
                    actions += '<div class="d-flex align-items-start gap-2 mb-1 pb-1 border-bottom border-light"><span class="text-nowrap text-muted" style="font-size:.7rem;">' + esc(r.tanggal || '-') + '</span><span>' + esc(notes) + '</span></div>';
                });
                actions += '</div></div></div>';
            }
            if (f.catatan_user) {
                actions += '<div class="mt-1 small text-muted"><i class="bi bi-chat-dots me-1"></i> <strong>Catatan:</strong> ' + esc(f.catatan_user) + '</div>';
            }
            $row.find('td').eq(7).html(actions);
            updateProgress();
            updateStatCards();
        }
    });
};

window.showRevision = function(id) {
    $('#revisionForm').attr('action', '{{ url("admin/revision") }}/' + id);
    $('#revisionModal').modal('show');
};

window.deleteUpload = function(id) {
    if (confirm('Hapus berkas ini dari sistem? (File di Drive tidak akan terhapus)')) {
        $('#deleteForm').attr('action', '{{ url("admin/delete-upload") }}/' + id).submit();
    }
};

window.scanFolder = function(folderId, folderName, scanAll) {
    $('#currentFolderId').val(folderId);
    $('#currentFolderName').val(folderName);
    doScan(folderId, folderName, scanAll);
};

window.scanCurrent = function(scanAll) {
    var id = $('#currentFolderId').val();
    var name = $('#currentFolderName').val();
    doScan(id, name, scanAll);
};

function doScan(folderId, folderName, scanAll) {
    $.ajax({
        url: '{{ route("admin.scan-folder") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            parent_id: folderId,
            parent_name: folderName,
            scan_all: scanAll ? 1 : 0
        },
        beforeSend: function() {
            $('#uploadsBody').html('<tr><td colspan="8"><div class="empty-state"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0">Memindai folder...</p></div></td></tr>');
        },
        success: function(res) {
            $('#uploadsBody').html(buildTableRows(res.files));
            if (res.files && res.files.length > 0) {
                $('#scanTime').show().find('span').text(res.waktu || '-');
                $('#fileCount').show().find('span').text(res.files.length + ' berkas');
        $('#poinFilterBar').show();
        $('[data-filter="all"]').addClass('active').siblings().removeClass('active');
        initPoinTooltips();
        var poinCounts = {};
        var totalFiles = res.files.length;
        res.files.forEach(function(f) {
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
        updateProgress();
        updateStatCards();
            } else {
                $('#scanTime').hide();
                $('#fileCount').hide();
                $('#poinFilterBar').hide();
            }

            $('#currentFolderId').val(res.folder_id || '');
            $('#currentFolderName').val(res.folder_name || '(folder utama)');

            if (res.subfolders && res.subfolders.length > 0) {
                var h = '';
                res.subfolders.forEach(function(sf) {
                    h += '<button class="btn btn-sm btn-outline-secondary btn-scan-folder" data-folder-id="' + esc(sf.id) + '" data-folder-name="' + esc(sf.name) + '"><i class="bi bi-folder me-1"></i> ' + esc(sf.name) + '</button>';
                });
                $('#subfolderButtons').html(h);
            } else {
                $('#subfolderButtons').html('<span class="text-muted small">Tidak ada subfolder</span>');
            }

            updateBreadcrumb(res.folder_id || '', res.folder_name || '(folder utama)');
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal memindai folder.';
            $('#uploadsBody').html('<tr><td colspan="8"><div class="empty-state text-danger"><i class="bi bi-exclamation-triangle"></i><p class="mb-0">' + msg + '</p></div></td></tr>');
        }
    });
}

function updateBreadcrumb(folderId, folderName) {
    var idx = -1;
    for (var i = 0; i < navStack.length; i++) {
        if (navStack[i].id === folderId) {
            idx = i;
            break;
        }
    }

    if (idx === -1) {
        if (folderId) {
            navStack.push({ id: folderId, name: folderName });
        }
    } else {
        navStack = navStack.slice(0, idx + 1);
    }

    var h = '<span class="badge bg-primary px-3 py-2 btn-breadcrumb-folder" style="cursor:pointer;" data-folder-id="" data-folder-name="(folder utama)"><i class="bi bi-house-door me-1"></i> Root</span>';
    navStack.forEach(function(n) {
        h += ' <i class="bi bi-chevron-right text-muted small"></i> ';
        h += '<span class="badge bg-secondary px-3 py-2 btn-breadcrumb-folder" style="cursor:pointer;" data-folder-id="' + esc(n.id) + '" data-folder-name="' + esc(n.name) + '">' + esc(n.name) + '</span>';
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
        const revId = 'rev-' + esc(dbId || driveId).replace(/[^a-zA-Z0-9]/g, '');
        h += '<div class="mt-2"><button class="btn btn-sm btn-link text-muted p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#' + revId + '" aria-expanded="false">';
        h += '<i class="bi bi-arrow-clockwise me-1"></i> Riwayat Revisi (' + f.revision_histories.length + ')</button>';
        h += '<div class="collapse mt-1" id="' + revId + '"><div class="bg-light rounded-2 p-2 small">';
        f.revision_histories.forEach(function(r) {
            const notes = Array.isArray(r.catatan_admin) ? r.catatan_admin.join('; ') : (r.catatan_admin || '-');
            h += '<div class="d-flex align-items-start gap-2 mb-1 pb-1 border-bottom border-light"><span class="text-nowrap text-muted" style="font-size:.7rem;">' + esc(r.tanggal || '-') + '</span><span>' + esc(notes) + '</span></div>';
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

function buildPoinCell(f) {
    var count = poinCount(f);
    var dbId = f.database_id;
    var uid = dbId ? esc(String(dbId)) : 'fallback-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
    var popId = 'poin-pop-' + uid;
    var badgeHtml = '';

    if (count === 0) {
        badgeHtml = '<span class="badge bg-light text-dark px-2 py-1 border poin-badge poin-badge-empty" style="cursor:pointer;" title="Poin" data-popin-id="' + popId + '"><i class="bi bi-square me-1"></i>Poin</span>';
    } else if (count === 1) {
        var key = firstCheckedPoin(f);
        badgeHtml = '<span class="badge bg-success px-2 py-1 poin-badge" style="cursor:pointer;" title="' + esc(poinLabels[key]) + '" data-popin-id="' + popId + '"><i class="bi bi-check-square me-1"></i>' + esc(poinShortLabels[key]) + '</span>';
    } else {
        var key = firstCheckedPoin(f);
        var rest = count - 1;
        var tipHtml = '';
        for (var ti = 0; ti < poinKeys.length; ti++) {
            if (f[poinKeys[ti]]) {
                tipHtml += '<div class="d-flex align-items-center gap-1 py-1"><i class="bi bi-check-circle-fill text-success"></i> ' + esc(poinLabels[poinKeys[ti]]) + '</div>';
            }
        }
        var cls = count === 5 ? 'poin-badge-full' : 'bg-success';
        badgeHtml = '<span class="badge ' + cls + ' px-2 py-1 poin-badge" style="cursor:pointer;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" title=\'' + tipHtml + '\' data-popin-id="' + popId + '"><i class="bi bi-check-square me-1"></i>' + esc(poinShortLabels[key]) + ' +' + rest + '</span>';
    }

    var popoverContent = '<div class="poin-popin-content" id="' + popId + '" style="display:none;"><div class="list-group list-group-flush">';
    for (var i = 0; i < poinKeys.length; i++) {
        var k = poinKeys[i];
        var checked = f[k] ? 'checked' : '';
        popoverContent += '<label class="list-group-item list-group-item-action d-flex align-items-start gap-2 px-2 py-1" style="cursor:pointer;font-size:.8rem;">';
        popoverContent += '<input type="checkbox" class="form-check-input mt-1 poin-check" data-upload-id="' + esc(String(dbId || '')) + '" data-poin="' + k + '" ' + checked + '>';
        popoverContent += '<span class="poin-label-text">' + esc(poinLabels[k]) + '</span>';
        popoverContent += '</label>';
    }
    popoverContent += '</div></div>';

    return '<td class="poin-cell">' + badgeHtml + popoverContent + '</td>';
}

function initPoinTooltips() {
    $('.poin-cell .poin-badge[data-bs-toggle="tooltip"]').tooltip('dispose');
    setTimeout(function() {
        $('.poin-cell .poin-badge[data-bs-toggle="tooltip"]').tooltip({ html: true, sanitize: false });
    }, 50);
}

$(document).on('click', '.poin-badge', function(e) {
    e.stopPropagation();
    e.preventDefault();
    if ($(this).data('bs.tooltip')) {
        $(this).tooltip('hide');
    }
    $('#poinPopup').remove();
    var badge = $(this);
    var contentId = badge.data('popin-id');
    var $contentDiv = $('#' + contentId);
    var checkedCount = $contentDiv.find('input.poin-check:checked').length;
    var totalItems = poinKeys.length;
    var pct = Math.round(checkedCount / totalItems * 100);

    var arrowHtml = '<div class="poin-popup-arrow"></div>';
    var headerHtml = '<div class="poin-popup-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">';
    headerHtml += '<strong class="small"><i class="bi bi-check2-square me-1"></i>Poin Checklist</strong>';
    headerHtml += '<button type="button" class="btn-close btn-close-sm" id="poinPopupClose" aria-label="Close"></button>';
    headerHtml += '</div>';
    headerHtml += '<div class="px-3 pt-2 pb-1" style="background:#fff;">';
    headerHtml += '<div class="d-flex align-items-center gap-2"><small class="text-muted">' + checkedCount + '/' + totalItems + '</small><div class="progress flex-grow-1" style="height:6px;"><div class="progress-bar" role="progressbar" style="width:' + pct + '%;" aria-valuenow="' + checkedCount + '" aria-valuemin="0" aria-valuemax="' + totalItems + '"></div></div></div>';
    headerHtml += '</div>';

    var bodyHtml = '<div class="poin-popup-body">' + $contentDiv.html() + '</div>';
    var popup = $('<div id="poinPopup">').html(arrowHtml + headerHtml + bodyHtml).appendTo('body');
    var pos = badge.offset();
    var popupLeft = Math.max(4, pos.left + badge.outerWidth() - 420);
    var badgeCenter = pos.left + badge.outerWidth() / 2;
    var arrowLeft = badgeCenter - popupLeft - 6;
    popup.css({
        position: 'absolute',
        top: pos.top + badge.outerHeight() + 4,
        left: popupLeft,
        zIndex: 1060,
        background: '#fff',
        border: '1px solid #dee2e6',
        borderRadius: '6px',
        boxShadow: '0 4px 16px rgba(0,0,0,0.12)',
        width: '420px'
    });
    popup.find('.poin-popup-arrow').css('left', Math.max(10, Math.min(arrowLeft, 408)));
});

$(document).on('click', '#poinPopupClose', function() {
    $('#poinPopup').remove();
});

$(document).on('click', '#poinPopup label', function(e) {
    e.preventDefault();
    var cb = $(this).find('.poin-check');
    if (cb.length) {
        cb.prop('checked', !cb.prop('checked')).trigger('change');
    }
});

$(document).on('change', '.poin-check', function() {
    var cb = $(this);
    var uploadId = cb.data('upload-id');
    var poin = cb.data('poin');
    var row = $('.poin-check[data-upload-id="' + uploadId + '"]').first().closest('tr');

    $.ajax({
        url: '{{ route("admin.toggle-poin") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            upload_id: uploadId,
            poin: poin
        },
        success: function(res) {
            // Update popup in-place — jangan remove
            var $popup = $('#poinPopup');
            $popup.find('.poin-check[data-poin="' + poin + '"]').prop('checked', res.value);

            var checkedCount = $popup.find('.poin-check:checked').length;
            var totalItems = poinKeys.length;
            var pct = Math.round(checkedCount / totalItems * 100);
            $popup.find('.text-muted').first().text(checkedCount + '/' + totalItems);
            $popup.find('.progress-bar').css('width', pct + '%').attr('aria-valuenow', checkedCount);

            row.find('.poin-check[data-poin="' + poin + '"]').prop('checked', res.value);

            var file = { database_id: uploadId };
            poinKeys.forEach(function(k) { file[k] = row.find('.poin-check[data-poin="' + k + '"]').is(':checked'); });
            row.find('.poin-cell').replaceWith(buildPoinCell(file));
            initPoinTooltips();

            var poinCounts = {}, totalFiles = 0;
            $('#uploadsBody tr').each(function() {
                totalFiles++;
                var $tr = $(this);
                poinKeys.forEach(function(k) {
                    var cb = $tr.find('.poin-check[data-poin="' + k + '"]');
                    if (cb.length && cb.is(':checked')) poinCounts[k] = (poinCounts[k] || 0) + 1;
                });
            });
            $('#poinFilterBar [data-filter]').each(function() {
                var btn = $(this);
                var filter = btn.data('filter');
                var label = btn.text().replace(/\s*\(\d+\)$/, '');
                btn.text(label + ' (' + (filter === 'all' ? totalFiles : (poinCounts[filter] || 0)) + ')');
            });

            var activeFilter = $('#poinFilterBar .active').data('filter');
            if (activeFilter && activeFilter !== 'all') {
                var fc = row.find('.poin-check[data-poin="' + activeFilter + '"]');
                if (!fc.length || !fc.is(':checked')) row.hide();
            }
        },
        error: function() {
            cb.prop('checked', !cb.prop('checked'));
            row.find('.poin-check[data-poin="' + poin + '"]').prop('checked', cb.prop('checked'));
        }
    });
});

$(document).on('click', '#poinFilterBar [data-filter]', function() {
    var btn = $(this);
    var filter = btn.data('filter');
    btn.addClass('active').siblings().removeClass('active');

    if (filter === 'all') {
        $('#uploadsBody tr').show();
    } else {
        $('#uploadsBody tr').each(function() {
            var row = $(this);
            var cb = row.find('.poin-check[data-poin="' + filter + '"]');
            if (cb.length && cb.is(':checked')) {
                row.show();
            } else {
                row.hide();
            }
        });
    }
});

$(document).on('click', '.btn-scan-folder', function() {
    scanFolder($(this).data('folder-id'), $(this).data('folder-name'), false);
});

$(document).on('click', '.btn-breadcrumb-folder', function() {
    scanFolder($(this).data('folder-id'), $(this).data('folder-name'), false);
});

function updateProgress() {
    var rows = $('#uploadsBody tr').has('.poin-check');
    var total = rows.length;
    if (total === 0) {
        $('#completenessProgress').css('width', '0%');
        $('#progressEmpty').show();
        $('#progressMain').hide();
        return;
    }
    $('#progressEmpty').hide();
    $('#progressMain').show();
    var approved = rows.filter('[data-status="approved"]').length;
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
    var rows = $('#uploadsBody tr').has('.poin-check');
    $('#statPending').text(rows.filter('[data-status="pending"]').length);
    $('#statApproved').text(rows.filter('[data-status="approved"]').length);
    $('#statRevision').text(rows.filter('[data-status="revision"]').length);
    if (rows.length > 0) {
        $('#statCards').show();
    } else {
        $('#statCards').hide();
    }
}

function buildTableRows(files) {
    if (!files || files.length === 0) {
        return '<tr><td colspan="8"><div class="empty-state"><i class="bi bi-inbox"></i><p class="mb-0">Tidak ada berkas ditemukan.</p></div></td></tr>';
    }
    let h = '';
    files.forEach(function(f, i) {
        h += '<tr data-status="' + esc(f.status) + '" data-db-id="' + esc(String(f.database_id || '')) + '">';
        h += '<td>' + (i + 1) + '</td>';
        h += '<td class="fw-medium">' + esc(f.filename || '-') + '</td>';
        h += '<td><span class="chip chip-subfolder"><i class="bi bi-folder"></i> ' + esc(f.subfolder || '(folder utama)') + '</span></td>';
        h += '<td>' + badgeHtml(f.status) + '</td>';
        h += buildPoinCell(f);
        h += '<td class="text-muted small">' + esc(f.upload_time ? f.upload_time.replace('T', ' ').substring(0, 16) : '-') + '</td>';
        h += '<td class="text-muted small">' + esc(f.uploader_name || '-') + '</td>';
        h += '<td>' + buildActions(f) + '</td>';
        h += '</tr>';
    });
    return h;
}
</script>
@endpush