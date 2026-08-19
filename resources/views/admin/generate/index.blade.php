@extends('layouts.main')

@section('page-title', 'Generate Dokumen')

@push('styles')
<style>
    .cell-checkbox:checked + .cell-content { background: #d4edda; border-radius: 4px; padding: 0 4px; }
    .cell-content { cursor: pointer; }
    .cell-content:hover { background: #e8f4fd; border-radius: 4px; }
    .selected-item { padding: 4px 8px; background: #f8fafc; border-radius: 4px; border-left: 3px solid var(--primary); margin-bottom: 4px; font-size: .75rem; }
    .selected-item .num { font-weight: 600; color: var(--primary); margin-right: 6px; }
    #selectedPanel { flex: 1 1 0; min-height: 0; overflow: auto; }
    #colFilterPanel { flex: 1 1 0; min-height: 0; overflow: auto; }
    #dataCard { display: block; }
    .data-table-wrap { max-height: 320px; overflow: auto; }
    .data-table-wrap table { font-size: .75rem; }
    .data-table-wrap td, .data-table-wrap th { padding: .35rem .5rem !important; vertical-align: top; white-space: nowrap; }
    .col-header-check { cursor: pointer; }
    .col-filter-badge { display: inline-block; padding: 3px 10px; margin: 2px; font-size: .75rem; border-radius: 12px; cursor: pointer; border: 1px solid #dee2e6; user-select: none; white-space: nowrap; }
    .col-filter-badge.active { background: #0d6efd; color: #fff; border-color: #0d6efd; }
    .col-filter-badge.inactive { background: #f8f9fa; color: #6c757d; }
    .history-table-wrap { max-height: 250px; overflow: auto; }
    .history-table { font-size: .75rem; }
    .history-table td, .history-table th { padding: .3rem .5rem !important; vertical-align: middle; white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Generate Dokumen</h4>
            <p class="text-muted small mb-0 mt-1">Buat dokumen dari data spreadsheet atau Excel dengan pemilihan sel manual.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div id="generateAlerts"></div>

    {{-- ROW 1: Sumber Data | Filter Kolom --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><i class="bi bi-database me-1"></i> Sumber Data</div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sourceType" id="sourceSpreadsheet" value="spreadsheet" checked>
                            <label class="form-check-label" for="sourceSpreadsheet">Google Spreadsheet</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="sourceType" id="sourceExcel" value="excel">
                            <label class="form-check-label" for="sourceExcel">Upload Excel</label>
                        </div>
                    </div>

                    <div id="spreadsheetSection">
                        <div class="input-group mb-2">
                            <input type="url" id="spreadsheetUrl" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/..." value="">
                            <button class="btn btn-primary" id="fetchSpreadsheetBtn"><i class="bi bi-cloud-download me-1"></i> Ambil Data</button>
                        </div>
                        <div class="form-text">Spreadsheet harus di-share ke email Google yang sudah terhubung (Drive Config).</div>
                    </div>

                    <div id="excelSection" style="display:none;">
                        <div class="input-group mb-2">
                            <input type="file" id="excelFile" class="form-control" accept=".xlsx,.xls">
                            <button class="btn btn-primary" id="fetchExcelBtn"><i class="bi bi-upload me-1"></i> Ambil Data</button>
                        </div>
                        <div class="form-text">Upload file Excel (.xlsx, maks 10MB).</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex">
            <div class="card h-100 w-100 d-flex flex-column">
                <div class="card-header"><i class="bi bi-funnel me-1"></i> Filter Kolom</div>
                <div class="card-body py-2" id="colFilterPanel">
                    <p class="text-muted small mb-0 fst-italic">Muat data terlebih dahulu.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 2: Data Tersedia --}}
    <div class="mb-4">
        <div class="card" id="dataCard" style="display:none;">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-table me-1"></i> Data Tersedia</span>
                <span class="badge bg-light text-dark" id="totalCells">0 sel</span>
            </div>
            <div class="card-body p-0">
                <div class="data-table-wrap">
                    <table class="table table-bordered mb-0" id="dataTable">
                        <thead id="dataTableHead"></thead>
                        <tbody id="dataTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 3: Data Terpilih | Generate Dokumen --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8 d-flex">
            <div class="card h-100 w-100 d-flex flex-column">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-check2-square me-1"></i> Data Terpilih</span>
                    <span class="badge bg-primary" id="selectedCount">0</span>
                </div>
                <div class="card-body" id="selectedPanel">
                    <p class="text-muted small mb-0 fst-italic">Centang sel pada tabel data untuk memilih.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex">
            <div class="card h-100 w-100">
                <div class="card-header"><i class="bi bi-file-earmark-arrow-up me-1"></i> Generate Dokumen</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.generate.generate') }}" id="generateForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="selected_data" id="selectedDataInput">

                        <div class="mb-3">
                            <label class="form-label">Upload Template (.docx)</label>
                            <input type="file" name="template" class="form-control" accept=".docx" required>
                            <div class="form-text">Gunakan placeholder @{{data 1}}, @{{data 2}} sesuai urutan centang.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Dokumen
                                <span class="text-muted fw-light" style="font-size:.75rem;">(opsional)</span>
                            </label>
                            <input type="text" name="document_name" class="form-control"
                                   placeholder="Kosongkan untuk mengikuti nama template" maxlength="200">
                            <div class="form-text">Jika dikosongkan, nama file akan mengikuti nama template yang diupload.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Format Output</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" value="docx" id="formatDocx" checked>
                                    <label class="form-check-label" for="formatDocx">DOCX</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" value="pdf" id="formatPdf">
                                    <label class="form-check-label" for="formatPdf">PDF</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="generateBtn" disabled>
                            <i class="bi bi-file-earmark-check me-1"></i> Generate
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 4: Riwayat Generate --}}
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span><i class="bi bi-clock-history me-1"></i> Riwayat Generate</span>
                <div class="dropdown ms-auto">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Opsi riwayat">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item text-danger" href="#" id="deleteSelectedBtn" role="button">
                                <i class="bi bi-trash me-1"></i> Hapus Terpilih <span id="deleteSelectedCount" class="badge bg-danger ms-1 d-none">0</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="history-table-wrap">
                    <table class="table table-hover history-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:36px;"><input type="checkbox" id="historySelectAll" class="form-check-input mt-0" title="Pilih semua di halaman ini"></th>
                                <th>#</th>
                                <th>Nama File</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            @forelse($history as $h)
                            <tr data-id="{{ $h->id }}">
                                <td><input type="checkbox" class="history-check form-check-input mt-0" value="{{ $h->id }}"></td>
                                <td>{{ $loop->iteration + ($history->currentPage() - 1) * $history->perPage() }}</td>
                                <td>{{ $h->filename }}</td>
                                <td>{{ \Carbon\Carbon::parse($h->waktu)->format('d M Y H:i') }}</td>
                                <td>
                                    @if (file_exists($h->filepath))
                                        <a href="{{ route('admin.generate.download', $h->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @else
                                        <span class="text-muted small" title="File sudah tidak tersedia">
                                            <i class="bi bi-x-lg"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-3">Belum ada riwayat generate.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($history->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $history->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var selectedData = [];
var currentRows = [];
var dataCounter = 1;
var selectionIdCounter = 0;

$(document).ready(function() {
    var saved = sessionStorage.getItem('generateSuccess');
    if (saved) {
        sessionStorage.removeItem('generateSuccess');
        var dl = sessionStorage.getItem('generateDownloadId');
        if (dl) {
            sessionStorage.removeItem('generateDownloadId');
            setTimeout(function() {
                window.location.href = '{{ route("admin.generate.download", ":id") }}'.replace(':id', dl);
            }, 300);
        }
        showGenerateAlert('success', saved);
    }

    $('input[name="sourceType"]').on('change', function() {
        if ($(this).val() === 'spreadsheet') {
            $('#spreadsheetSection').show();
            $('#excelSection').hide();
        } else {
            $('#spreadsheetSection').hide();
            $('#excelSection').show();
        }
        // Reset data sebelumnya saat ganti sumber
        selectedData = [];
        currentRows = [];
        selectionIdCounter = 0;
        dataCounter = 1;
        $('#dataCard').hide();
        $('#colFilterPanel').html('<p class="text-muted small mb-0 fst-italic">Muat data terlebih dahulu.</p>');
        updateSelectedPanel();
    });
});

function esc(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

$('#fetchSpreadsheetBtn').on('click', function() {
    var url = $('#spreadsheetUrl').val().trim();
    if (!url) { alert('Masukkan link Google Spreadsheet.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');

    $.ajax({
        url: '{{ route("admin.generate.fetch-spreadsheet") }}',
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), url: url },
        success: function(res) { renderDataTable(res); },
        error: function(xhr) { alert(xhr.responseJSON?.error || 'Gagal membaca spreadsheet.'); },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-cloud-download me-1"></i> Ambil Data'); }
    });
});

$('#fetchExcelBtn').on('click', function() {
    var file = $('#excelFile')[0].files[0];
    if (!file) { alert('Pilih file Excel terlebih dahulu.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    var fd = new FormData();
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    fd.append('file', file);

    $.ajax({
        url: '{{ route("admin.generate.fetch-excel") }}',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) { renderDataTable(res); },
        error: function(xhr) { alert(xhr.responseJSON?.error || 'Gagal membaca Excel.'); },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Ambil Data'); }
    });
});

function renderDataTable(res) {
    selectedData = [];
    dataCounter = 1;
    currentRows = res.rows || [];
    updateSelectedPanel();

    var cols = res.columns || [];
    var rows = res.rows || [];

    if (rows.length === 0) {
        alert('Tidak ada data ditemukan.');
        return;
    }

    // Column filter panel — badge style
    var filterHtml = '';
    cols.forEach(function(c) {
        filterHtml += '<span class="col-filter-badge active" data-col="' + esc(c) + '">' + esc(c) + ' <i class="bi bi-eye"></i></span>';
    });
    $('#colFilterPanel').html(filterHtml);

    // Build table header with "select all" checkbox per column
    var thead = '<tr><th style="width:40px;">No</th>';
    cols.forEach(function(c) {
        var safeCol = esc(c).replace(/\s+/g, '-');
        thead += '<th data-colkey="' + esc(c) + '">';
        thead += '<div class="d-flex align-items-center gap-1">';
        thead += '<input type="checkbox" class="col-header-check" data-col="' + esc(c) + '" id="chk-all-' + safeCol + '" title="Centang semua di kolom ini">';
        thead += '<label class="col-header-check mb-0 small text-muted" for="chk-all-' + safeCol + '" style="cursor:pointer;">' + esc(c) + '</label>';
        thead += '</div>';
        thead += '</th>';
    });
    thead += '</tr>';
    $('#dataTableHead').html(thead);

    var tbody = '';
    rows.forEach(function(row, ri) {
        tbody += '<tr class="data-row" data-row="' + ri + '">';
        tbody += '<td class="text-muted text-center">' + (ri + 1) + '</td>';
        cols.forEach(function(col) {
            var val = row[col] || '';
            var cellId = 'cell-' + ri + '-' + esc(col).replace(/\s+/g, '-');
            tbody += '<td data-colkey="' + esc(col) + '">';
            tbody += '<div class="d-flex align-items-start gap-1">';
            tbody += '<input type="checkbox" class="cell-checkbox me-1 mt-1" data-row="' + ri + '" data-col="' + esc(col) + '" data-val="' + esc(val) + '" id="' + cellId + '">';
            tbody += '<label class="cell-content" for="' + cellId + '">' + (val ? esc(val) : '<span class="text-muted fst-italic">(kosong)</span>') + '</label>';
            tbody += '</div>';
            tbody += '</td>';
        });
        tbody += '</tr>';
    });
    $('#dataTableBody').html(tbody);

    var cellCount = rows.length * cols.length;
    $('#totalCells').text(cellCount + ' sel');

    // Individual cell checkbox
    $('.cell-checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            selectionIdCounter++;
            $(this).data('selid', selectionIdCounter);
            selectedData.push({ id: selectionIdCounter, val: $(this).data('val'), col: $(this).data('col') });
        } else {
            var selId = $(this).data('selid');
            selectedData = selectedData.filter(function(item) { return item.id !== selId; });
        }
        dataCounter = 1;
        updateSelectedPanel();
    });

    // "Select all in column" header checkbox
    $('.col-header-check').on('change', function() {
        var col = $(this).data('col');
        var checked = $(this).is(':checked');
        $('.cell-checkbox').filter(function() {
            return $(this).data('col') === col;
        }).each(function() {
            if ($(this).is(':checked') !== checked) {
                $(this).prop('checked', checked).trigger('change');
            }
        });
    });

    // Column filter toggle — badge click
    $('#colFilterPanel').on('click', '.col-filter-badge', function() {
        var col = $(this).data('col');
        var visible = $(this).hasClass('active');
        $(this).toggleClass('active inactive');
        $(this).find('i').toggleClass('bi-eye bi-eye-slash');
        $('#dataTable th[data-colkey], #dataTable td[data-colkey]').filter(function() {
            return $(this).data('colkey') === col;
        }).toggle(!visible);
    });

    $('#dataCard').show();
}

function updateSelectedPanel() {
    var panel = $('#selectedPanel');
    var count = selectedData.length;

    $('#selectedCount').text(count);
    $('#generateBtn').prop('disabled', count === 0);
    $('#selectedDataInput').val(JSON.stringify(selectedData.map(function(item) { return item.val; })));

    if (count === 0) {
        panel.html('<p class="text-muted small mb-0 fst-italic">Centang sel pada tabel data untuk memilih.</p>');
        return;
    }

    var h = '';
    selectedData.forEach(function(item, i) {
        h += '<div class="selected-item">';
        h += '<span class="num">Data ' + (i + 1) + ':</span> ';
        h += esc(item.val || '(kosong)');
        h += ' <span class="text-muted fw-light" style="font-size:.7rem;">(' + esc(item.col) + ')</span>';
        h += '</div>';
    });
    panel.html(h);
}

// Generate via AJAX: auto-download + riwayat instan tanpa refresh
$('#generateForm').on('submit', function(e) {
    e.preventDefault();

    var fd = new FormData(this);
    var btn = $('#generateBtn');

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Membuat dokumen...');

    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res && res.success) {
                var msg = 'Dokumen "' + res.filename + '" berhasil dibuat dan diunduh.';
                var page = parseInt(new URLSearchParams(window.location.search).get('page') || '1', 10);
                if (page > 1) {
                    sessionStorage.setItem('generateSuccess', msg);
                    sessionStorage.setItem('generateDownloadId', String(res.history_id));
                    window.location.href = '{{ route("admin.generate") }}';
                    return;
                }
                showGenerateAlert('success', msg);
                prependHistoryRow(res);
                window.location.href = '{{ route("admin.generate.download", ":id") }}'.replace(':id', res.history_id);
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Gagal generate dokumen. Silakan coba lagi.';
            showGenerateAlert('danger', msg);
        },
        complete: function() {
            btn.html('<i class="bi bi-file-earmark-check me-1"></i> Generate');
            updateSelectedPanel();
        }
    });
});

function showGenerateAlert(type, msg) {
    var box = $('#generateAlerts');
    if (!box.length) return;
    box.html('<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center gap-2">' + esc(msg) + '<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>');
    clearTimeout(showGenerateAlert._t);
    showGenerateAlert._t = setTimeout(function() { box.empty(); }, 6000);
}

function prependHistoryRow(res) {
    $('#historyBody tr:has(td[colspan])').remove();
    var downloadUrl = '{{ route("admin.generate.download", ":id") }}'.replace(':id', res.history_id);
    var rows = $('#historyBody tr');
    var offset = 0;
    if (rows.length) {
        var firstNum = parseInt(rows.first().find('td:nth-child(2)').text(), 10);
        if (!isNaN(firstNum)) offset = firstNum - 1;
    }
    rows.each(function(i) {
        $(this).find('td:nth-child(2)').text(offset + i + 2);
    });
    var row = '<tr data-id="' + res.history_id + '">' +
        '<td><input type="checkbox" class="history-check form-check-input mt-0" value="' + res.history_id + '"></td>' +
        '<td>' + (offset + 1) + '</td>' +
        '<td>' + esc(res.filename) + '</td>' +
        '<td>' + esc(res.waktu) + '</td>' +
        '<td><a href="' + downloadUrl + '" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a></td>' +
        '</tr>';
    $('#historyBody').prepend(row);
    updateHistorySelectionUI();
}

// === Hapus Riwayat Generate (checkbox + dropdown) ===
function updateHistorySelectionUI() {
    var checks = $('#historyBody .history-check');
    var checked = checks.filter(':checked');
    var n = checked.length;
    var total = checks.length;

    $('#deleteSelectedCount').text(n).toggleClass('d-none', n === 0);
    $('#deleteSelectedBtn').toggleClass('disabled', n === 0);

    var selAll = $('#historySelectAll');
    selAll.prop('checked', total > 0 && n === total);
    selAll.prop('indeterminate', n > 0 && n < total);
}

$('#historyBody').on('change', '.history-check', function() {
    updateHistorySelectionUI();
});

$('#historySelectAll').on('change', function() {
    var checked = $(this).is(':checked');
    $('#historyBody .history-check').prop('checked', checked);
    updateHistorySelectionUI();
});

$('#deleteSelectedBtn').on('click', function(e) {
    e.preventDefault();
    var ids = [];
    $('#historyBody .history-check:checked').each(function() {
        ids.push($(this).val());
    });
    if (!ids.length) return;

    if (!confirm('Hapus ' + ids.length + ' riwayat terpilih? File dokumen juga akan dihapus dan tidak dapat dikembalikan.')) {
        return;
    }

    $.ajax({
        url: '{{ route("admin.generate.delete") }}',
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), ids: ids },
        success: function(res) {
            if (res && res.success) {
                $('#historyBody .history-check:checked').closest('tr').remove();
                showGenerateAlert('success', res.deleted + ' riwayat berhasil dihapus.');

                var remaining = $('#historyBody .history-check').length;
                if (remaining === 0) {
                    location.reload();
                    return;
                }

                var rows = $('#historyBody tr');
                var offset = 0;
                var firstNum = parseInt(rows.first().find('td:nth-child(2)').text(), 10);
                if (!isNaN(firstNum)) offset = firstNum - 1;
                rows.each(function(i) {
                    $(this).find('td:nth-child(2)').text(offset + i + 1);
                });
                updateHistorySelectionUI();
            }
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Gagal menghapus riwayat. Silakan coba lagi.';
            showGenerateAlert('danger', msg);
        }
    });
});
</script>
@endpush
