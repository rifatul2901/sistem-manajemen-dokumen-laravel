@extends('layouts.main')

@section('page-title', 'Generator Dokumen & Upload')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div>
            <h4 class="page-title mb-0">Selamat datang, {{ Auth::user()->nama ?? Auth::user()->username }}!</h4>
            <p class="text-muted small mb-0 mt-1">Kelola dokumen dan upload berkas untuk direview Admin.</p>
        </div>
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

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100 section-card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-spreadsheet text-primary"></i> Sumber Data (Excel)
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.excel') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">File Excel (.xlsx)</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload Excel</button>
                    </form>
                    @if (session('excel_headers'))
                        <hr class="my-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="chip chip-subfolder"><i class="bi bi-list-columns"></i> {{ count(session('excel_headers')) }} kolom</span>
                            <span class="chip chip-subfolder"><i class="bi bi-rows"></i> {{ count(session('excel_data', [])) }} baris</span>
                        </div>
                        <p class="small text-muted mt-2 mb-0">Kolom: {{ implode(', ', session('excel_headers')) }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 section-card" style="border-left-color: var(--success);">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-arrow-up text-success"></i> Pembuatan Dokumen
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.generate') }}" enctype="multipart/form-data" id="generateForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Judul Dokumen</label>
                                <input type="text" name="judul" class="form-control" placeholder="Misal: Surat Tugas">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Format</label>
                                <div class="d-flex gap-3 pt-1">
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
                            <div class="col-12">
                                <label class="form-label">Template (.docx)</label>
                                <input type="file" name="template" class="form-control" accept=".docx">
                                <div class="form-text">Kosongkan untuk menggunakan template default.</div>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="detectPlaceholders"><i class="bi bi-search me-1"></i> Deteksi Placeholder</button>
                                <span id="placeholderResult" class="ms-2 small text-muted"></span>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Mode Data</label>
                                <select name="data_mode" class="form-select" id="dataMode">
                                    <option value="otomatis">Otomatis (semua data dari Excel)</option>
                                    <option value="manual">Manual (pilih baris)</option>
                                </select>
                            </div>
                        </div>
                        <div id="manualSelection" class="mt-3" style="display:none;">
                            <label class="form-label">Pilih Baris Data</label>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto; border: 1.5px solid #e2e8f0; border-radius: 8px;">
                                <table class="table table-sm table-bordered mb-0" id="dataTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                                            <th>#</th>
                                            @if (session('excel_headers'))
                                                @foreach (session('excel_headers') as $h)
                                                    <th>{{ $h }}</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $excelData = session('excel_data', []); @endphp
                                        @foreach ($excelData as $idx => $row)
                                            <tr>
                                                <td class="ps-3"><input type="checkbox" class="row-checkbox form-check-input" value="{{ $row['_index'] }}"></td>
                                                <td>{{ $idx + 1 }}</td>
                                                @foreach (session('excel_headers', []) as $h)
                                                    <td>{{ Str::limit($row[$h] ?? '', 30) }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <input type="hidden" name="selected_data" id="selectedData" value="">
                        <button type="submit" class="btn btn-success mt-3"><i class="bi bi-file-earmark-check me-1"></i> Generate Dokumen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card h-100 section-card" style="border-left-color: var(--warning);">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-cloud-upload text-warning"></i> Upload Berkas Final
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.upload') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Folder Tujuan</label>
                            <select name="folder_tujuan" class="form-select" id="folderSelect">
                                <option value="">-- Pilih Folder --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File (.docx / .pdf)</label>
                            <input type="file" name="file" class="form-control" accept=".docx,.pdf" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan untuk Admin..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-cloud-arrow-up me-1"></i> Upload ke Drive</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100 section-card" style="border-left-color: var(--info);">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-info"></i> Riwayat Dokumen Saya
                </div>
                <div class="card-body p-0">
                    @if (isset($histories) && $histories->count() > 0)
                        <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Dokumen</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($histories as $index => $history)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td class="fw-medium">{{ $history->filename }}</td>
                                            <td class="text-muted small">{{ $history->created_at ? $history->created_at->format('d/m/Y H:i') : $history->waktu ?? '-' }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('dashboard.download', $history->id) }}" class="btn btn-sm btn-outline-primary btn-icon" title="Download"><i class="bi bi-download"></i></a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p class="mb-0">Belum ada riwayat dokumen.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card section-card" style="border-left-color: var(--danger);">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-arrow-counterclockwise text-danger"></i> Upload Revisi
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.upload-revisi') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Pilih Berkas yang Direvisi</label>
                                <select name="upload_id" class="form-select" required>
                                    <option value="">-- Pilih Berkas --</option>
                                    @php
                                        $revisiUploads = \App\Models\Upload::where('user_id', Auth::id())->where('status', 'revision')->get();
                                    @endphp
                                    @foreach ($revisiUploads as $ru)
                                        <option value="{{ $ru->id }}">{{ $ru->filename }} ({{ $ru->upload_time }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">File Revisi (.docx / .pdf)</label>
                                <input type="file" name="file" class="form-control" accept=".docx,.pdf" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-danger w-100"><i class="bi bi-arrow-repeat me-1"></i> Upload</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#detectPlaceholders').on('click', function() {
        const formData = new FormData();
        const fileInput = $('input[name="template"]')[0];
        if (fileInput && fileInput.files[0]) {
            formData.append('template', fileInput.files[0]);
        } else {
            $('#placeholderResult').text('Upload template dulu.').addClass('text-danger');
            return;
        }
        formData.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: '{{ route("dashboard.detect-placeholders") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                const msg = res.detail ? res.detail + ' (maks ' + res.max + ' data)' : 'Tidak ada placeholder yang cocok.';
                $('#placeholderResult').text(msg).removeClass('text-danger').addClass(res.detail ? 'text-success' : 'text-muted');
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.error || 'Gagal mendeteksi placeholder.';
                $('#placeholderResult').text(msg).addClass('text-danger');
            }
        });
    });

    $('#dataMode').on('change', function() {
        $('#manualSelection').toggle($(this).val() === 'manual');
    });

    $('#selectAll').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#generateForm').on('submit', function() {
        if ($('#dataMode').val() === 'manual') {
            const selected = $('.row-checkbox:checked').map(function() { return $(this).val(); }).get();
            if (!selected.length) { alert('Pilih minimal 1 baris data.'); return false; }
            $('#selectedData').val(JSON.stringify(selected));
        }
    });

    $.ajax({
        url: '{{ route("dashboard.subfolders") }}',
        method: 'GET',
        success: function(res) {
            const sel = $('#folderSelect').empty().append('<option value="">-- Pilih Folder --</option>');
            const items = Array.isArray(res) ? res : (res.folders || []);
            items.forEach(function(f) { sel.append('<option value="' + (f.id || f.name) + '">' + f.name + '</option>'); });
        }
    });
});
</script>
@endpush