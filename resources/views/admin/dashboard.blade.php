@extends('layouts.main')

@section('page-title', 'Dashboard')

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
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-google text-muted"></i> Konfigurasi Folder Google Drive
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.save-drive-config') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Link Folder Google Drive</label>
                            <input type="url" name="folder_link" class="form-control" value="{{ $driveConfig->folder_link ?? '' }}" placeholder="https://drive.google.com/drive/folders/...">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy me-1"></i> Simpan</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-folder2-open text-muted"></i> Pindai Folder
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Navigasi Folder</label>
                        <div id="breadcrumbNav" class="d-flex flex-wrap align-items-center gap-1 mb-2">
                            <span class="badge bg-primary px-3 py-2" style="cursor:pointer;" onclick="scanFolder('', '(folder utama)', false)">
                                <i class="bi bi-house-door me-1"></i> Root
                            </span>
                        </div>
                        <div id="subfolderButtons" class="d-flex flex-wrap gap-1"></div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="scanCurrent(false)"><i class="bi bi-search me-1"></i> Pindai Folder Ini</button>
                        <button class="btn btn-outline-primary" onclick="scanCurrent(true)"><i class="bi bi-files me-1"></i> Scan All (Recursive)</button>
                    </div>
                    <input type="hidden" id="currentFolderId" value="">
                    <input type="hidden" id="currentFolderName" value="(folder utama)">
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
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="uploadsTable">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Nama Berkas</th>
                            <th>Subfolder</th>
                            <th>Status</th>
                            <th>Diupload</th>
                            <th>Oleh</th>
                            <th style="width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="uploadsBody">
                        <tr id="scanPromptRow">
                            <td colspan="7">
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
    // Card sudah terlihat dengan prompt "Pilih folder dan klik Pindai"
    // User harus klik Root, folder, atau tombol Pindai untuk memulai
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
    if (confirm('Setujui berkas ini?')) {
        $('#approveForm').attr('action', '{{ url("admin/approve") }}/' + id).submit();
    }
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
            $('#uploadsBody').html('<tr><td colspan="7"><div class="empty-state"><div class="spinner-border text-primary mb-2" role="status"></div><p class="mb-0">Memindai folder...</p></div></td></tr>');
        },
        success: function(res) {
            $('#uploadsBody').html(buildTableRows(res.files));
            if (res.files && res.files.length > 0) {
                $('#scanTime').show().find('span').text(res.waktu || '-');
                $('#fileCount').show().find('span').text(res.files.length + ' berkas');
            } else {
                $('#scanTime').hide();
                $('#fileCount').hide();
            }

            $('#currentFolderId').val(res.folder_id || '');
            $('#currentFolderName').val(res.folder_name || '(folder utama)');

            if (res.subfolders && res.subfolders.length > 0) {
                var h = '';
                res.subfolders.forEach(function(sf) {
                    h += '<button class="btn btn-sm btn-outline-secondary" onclick="scanFolder(\'' + esc(sf.id) + '\', \'' + esc(sf.name) + '\', false)"><i class="bi bi-folder me-1"></i> ' + esc(sf.name) + '</button>';
                });
                $('#subfolderButtons').html(h);
            } else {
                $('#subfolderButtons').html('<span class="text-muted small">Tidak ada subfolder</span>');
            }

            updateBreadcrumb(res.folder_id || '', res.folder_name || '(folder utama)');
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal memindai folder.';
            $('#uploadsBody').html('<tr><td colspan="7"><div class="empty-state text-danger"><i class="bi bi-exclamation-triangle"></i><p class="mb-0">' + msg + '</p></div></td></tr>');
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

    var h = '<span class="badge bg-primary px-3 py-2" style="cursor:pointer;" onclick="scanFolder(\'\', \'(folder utama)\', false)"><i class="bi bi-house-door me-1"></i> Root</span>';
    navStack.forEach(function(n) {
        h += ' <i class="bi bi-chevron-right text-muted small"></i> ';
        h += '<span class="badge bg-secondary px-3 py-2" style="cursor:pointer;" onclick="scanFolder(\'' + esc(n.id) + '\', \'' + esc(n.name) + '\', false)">' + esc(n.name) + '</span>';
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

function buildTableRows(files) {
    if (!files || files.length === 0) {
        return '<tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox"></i><p class="mb-0">Tidak ada berkas ditemukan.</p></div></td></tr>';
    }
    let h = '';
    files.forEach(function(f, i) {
        h += '<tr>';
        h += '<td>' + (i + 1) + '</td>';
        h += '<td class="fw-medium">' + esc(f.filename || '-') + '</td>';
        h += '<td><span class="chip chip-subfolder"><i class="bi bi-folder"></i> ' + esc(f.subfolder || '(folder utama)') + '</span></td>';
        h += '<td>' + badgeHtml(f.status) + '</td>';
        h += '<td class="text-muted small">' + esc(f.upload_time ? f.upload_time.replace('T', ' ').substring(0, 16) : '-') + '</td>';
        h += '<td class="text-muted small">' + esc(f.uploader_name || '-') + '</td>';
        h += '<td>' + buildActions(f) + '</td>';
        h += '</tr>';
    });
    return h;
}
</script>
@endpush