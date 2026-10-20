@extends('layouts.main')

@section('page-title', 'Generate Dokumen')

@push('styles')
<style>
    /* === Generate Dokumen — scoped theme (konsisten dengan dashboard SIMADO) === */
    .generate-page { --gen-border: #E2E8F0; --gen-muted: #64748B; }

    /* Heading */
    .generate-page .gen-head { margin-bottom: 1.25rem; }
    .generate-page .gen-head p { color: var(--gen-muted); font-size: .875rem; margin: .25rem 0 0; }

    /* Card header: judul berikon */
    .generate-page .card-header .hdr-title { display: inline-flex; align-items: center; gap: .5rem; font-weight: 600; }
    .generate-page .card-header .hdr-title i { color: var(--primary); font-size: 1rem; }

    /* Sumber Data: segmented control */
    .generate-page .source-seg .btn,
    .generate-page .mode-seg .btn { font-size: .8rem; padding: .45rem .6rem; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; }
    @media (max-width: 575.98px) {
        .generate-page .source-seg .btn,
        .generate-page .mode-seg .btn { font-size: .72rem; padding: .4rem .35rem; }
    }

    /* Baris "Ambil Data": tombol turun ke baris berikutnya pada layar kecil */
    .generate-page .fetch-row { display: flex; gap: .5rem; }
    .generate-page .fetch-row .form-control { flex: 1 1 auto; min-width: 0; }
    .generate-page .fetch-row .btn { flex: 0 0 auto; white-space: nowrap; }
    @media (max-width: 575.98px) {
        .generate-page .fetch-row { flex-direction: column; }
        .generate-page .fetch-row .btn { width: 100%; }
    }
    .generate-page .source-help { font-size: .72rem; color: #94a3b8; margin-top: .5rem; }

    /* Filter Kolom: chips */
    .generate-page #colFilterPanel { display: flex; flex-wrap: wrap; align-content: flex-start; gap: .4rem; max-height: 13.75rem; overflow-y: auto; }
    .generate-page .col-filter-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem; font-size: .75rem; font-weight: 500;
        border-radius: 62.4375rem; cursor: pointer; border: 1px solid transparent;
        user-select: none; max-width: 100%; transition: all .15s ease;
    }
    .generate-page .col-filter-badge .cfb-label { max-width: 11.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .generate-page .col-filter-badge.active { background: #E7F3FC; color: #0369A1; border-color: #BAE0F5; }
    .generate-page .col-filter-badge.active:hover { background: #D6EBFA; }
    .generate-page .col-filter-badge.inactive { background: #F1F5F9; color: #94a3b8; border-color: var(--gen-border); }
    .generate-page .col-filter-badge.inactive:hover { background: #E2E8F0; color: var(--gen-muted); }

    /* Data Tersedia: tabel */
    .generate-page .data-table-wrap { max-height: 23.75rem; overflow: auto; }
    .generate-page .data-table-wrap table { font-size: .78rem; }
    .generate-page .data-table-wrap td, .generate-page .data-table-wrap th { padding: .45rem .55rem !important; vertical-align: top; white-space: nowrap; }
    .generate-page .data-table-wrap thead th { position: sticky; top: 0; background: #F8FAFC; z-index: 2; box-shadow: inset 0 -1px 0 var(--gen-border); }
    .generate-page .data-table-wrap td.row-num, .generate-page .data-table-wrap th.row-num { position: sticky; left: 0; background: #fff; z-index: 1; }
    .generate-page .data-table-wrap thead th.row-num { background: #F8FAFC; z-index: 3; }
    .generate-page .cell-content { cursor: pointer; }
    .generate-page .cell-content:hover { background: #e8f4fd; border-radius: 0.25rem; }
    .generate-page .cell-checkbox:checked + .cell-content { background: #d4edda; border-radius: 0.25rem; padding: 0 0.25rem; }
    .generate-page .cell-flash { outline: 0.125rem solid var(--primary); outline-offset: -0.125rem; border-radius: 0.25rem; }
    .generate-page .col-header-check { cursor: pointer; }
    .generate-page .cell-checkbox:disabled, .generate-page .row-check:disabled, .generate-page .col-header-check:disabled { opacity: .35; cursor: not-allowed; }
    .generate-page .cell-checkbox:disabled + .cell-content { opacity: .6; cursor: not-allowed; }
    .generate-page .cell-checkbox:disabled + .cell-content:hover { background: transparent; }
    .generate-page .gen-massal-hint {
        display: flex; align-items: center; gap: .45rem;
        padding: .6rem .9rem; font-size: .78rem;
        color: #0369a1; background: #E7F3FC; border-bottom: 1px solid var(--gen-border);
    }

    /* Data Terpilih */
    .generate-page #selectedPanel { max-height: 27.5rem; overflow-y: auto; }
    .generate-page .selected-item {
        display: flex; align-items: flex-start; gap: .5rem;
        padding: .5rem .65rem; margin-bottom: .4rem; font-size: .78rem; cursor: pointer;
        background: #F8FAFC; border: 1px solid var(--gen-border); border-left: 0.1875rem solid var(--primary);
        border-radius: 0.5rem; transition: background .15s, border-color .15s;
    }
    .generate-page .selected-item:hover { background: #EFF6FF; border-color: #BAE0F5; }
    .generate-page .selected-item .num { flex: 0 0 auto; font-weight: 600; color: var(--primary); white-space: nowrap; }
    .generate-page .selected-item .si-val { flex: 1 1 auto; min-width: 0; color: #334155; overflow-wrap: anywhere; word-break: break-word; }
    .generate-page .selected-item .si-meta { flex: 0 0 auto; color: #94a3b8; font-size: .68rem; white-space: nowrap; }
    .generate-page .selected-item .selected-remove { flex: 0 0 auto; line-height: 1; }
    @media (max-width: 575.98px) { .generate-page #selectedPanel { max-height: 320px; } }

    /* Generate Dokumen */
    .generate-page .btn-generate { font-weight: 600; padding: .6rem 1rem; }

    /* Riwayat */
    .generate-page .history-table-wrap { max-height: 20rem; overflow: auto; }
    .generate-page .history-table { font-size: .8rem; }
    .generate-page .history-table td, .generate-page .history-table th { padding: .55rem .75rem !important; vertical-align: middle; white-space: nowrap; }
    .generate-page .history-filename { max-width: 26.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* Empty state */
    .generate-page .gen-empty { width: 100%; color: #94a3b8; padding: .5rem 0; }
    .generate-page .gen-empty i { display: block; font-size: 1.5rem; color: #cbd5e1; margin-bottom: .35rem; }

    /* Pemetaan tag (generate massal) */
    .generate-page .tag-map-top { display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; }
    .generate-page .tag-map-count { font-size: .74rem; font-weight: 600; color: #92400e; white-space: nowrap; }
    .generate-page .tag-map-count.is-done { color: #15803d; }
    .generate-page .tag-progress { flex: 1 1 auto; height: .3rem; max-width: 7rem; background: #E2E8F0; border-radius: 999px; overflow: hidden; }
    .generate-page .tag-progress > span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #0EA5E9, #22C55E); transition: width .25s ease; }
    .generate-page #tagMappingList { max-height: 23.5rem; overflow-y: auto; overflow-x: hidden; padding-right: .15rem; }
    .generate-page .tag-section-title { display: flex; align-items: center; gap: .4rem; margin: .3rem 0 .2rem; font-size: .68rem; font-weight: 600; letter-spacing: .03em; text-transform: uppercase; color: #64748B; }
    .generate-page .tag-section-title .badge { font-size: .62rem; }
    .generate-page .tag-map-row { display: flex; align-items: center; gap: .45rem; min-height: 2.7rem; padding: .18rem .3rem; border: 1px solid transparent; border-radius: .45rem; }
    .generate-page .tag-map-row:hover { background: #F8FAFC; border-color: #E6ECF5; }
    .generate-page .tag-map-row .badge { flex: 0 0 auto; max-width: 46%; overflow: hidden; text-overflow: ellipsis; font-size: .68rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; white-space: nowrap; }
    .generate-page .tag-map-row.tag-unmapped .badge { background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A; }
    .generate-page .tag-map-row:not(.tag-unmapped) .badge { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
    .generate-page .tag-select { position: relative; flex: 1 1 auto; min-width: 0; }
    .generate-page .tag-select-btn { display: flex; align-items: center; gap: .35rem; width: 100%; height: 2.1rem; padding: 0 .5rem; font-size: .76rem; color: #334155; text-align: left; background: #fff; border: 1px solid #E2EAF5; border-radius: .45rem; }
    .generate-page .tag-select-btn:hover { background: #F8FBFE; border-color: #BAE0F5; }
    .generate-page .tag-select-btn.is-empty { color: #94A3B8; background: #FFFDF5; border-color: #FCD34D; }
    .generate-page .tag-select-btn.is-open { border-color: #0EA5E9; box-shadow: 0 0 0 .15rem rgba(14,165,233,.14); }
    .generate-page .col-select-btn { height: 2.4rem; }

    .generate-page .tag-select-btn .tag-select-label { flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .generate-page .tag-select-btn > i { flex: 0 0 auto; font-size: .68rem; color: #94A3B8; }
    .generate-page .tag-state-ico { flex: 0 0 auto; font-size: .8rem; }
    .generate-page .tag-state-ico.warn { color: #F59E0B; }
    .generate-page .tag-state-ico.ok { color: #22C55E; }
    .generate-page .tag-show-all { display: inline-flex; align-items: center; gap: .25rem; margin: .15rem 0 .05rem .3rem; padding: .1rem 0; font-size: .72rem; color: #0369A1; background: none; border: 0; }
    .generate-page .tag-show-all:hover { text-decoration: underline; }
    .generate-page .tag-matched { margin-top: .35rem; padding-top: .3rem; border-top: 1px dashed #E2EAF5; }
    .generate-page .tag-matched > summary { display: flex; align-items: center; gap: .35rem; padding: .2rem 0; font-size: .72rem; color: #15803D; cursor: pointer; list-style: none; }
    .generate-page .tag-matched > summary::-webkit-details-marker { display: none; }
    .generate-page .tag-matched-rows { padding-top: .15rem; }
    .generate-page .tag-all-done { display: flex; align-items: center; gap: .4rem; margin-top: .3rem; padding: .55rem .6rem; font-size: .76rem; color: #15803D; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: .5rem; }
    .generate-page .bulk-footer { margin-top: .2rem; padding-top: .6rem; border-top: 1px solid #E6ECF5; }

    /* Popover searchable pemetaan tag */
    .generate-page .tag-pop { position: fixed; z-index: 1085; width: 18rem; max-width: calc(100vw - 1rem); background: #fff; border: 1px solid #E2EAF5; border-radius: .6rem; box-shadow: 0 .75rem 1.5rem rgba(15,23,42,.14); overflow: hidden; }
    .generate-page .tag-pop-search { display: flex; align-items: center; gap: .35rem; padding: .4rem .55rem; border-bottom: 1px solid #EEF2F7; }
    .generate-page .tag-pop-search i { flex: 0 0 auto; font-size: .72rem; color: #94A3B8; }
    .generate-page .tag-pop-search input { flex: 1 1 auto; min-width: 0; padding: 0; font-size: .78rem; color: #0F172A; background: transparent; border: 0; outline: 0; }
    .generate-page .tag-pop-list { max-height: 15.5rem; overflow-y: auto; padding: .2rem; }
    .generate-page .tag-pop-opt { display: flex; align-items: center; gap: .4rem; padding: .38rem .45rem; font-size: .76rem; color: #334155; border-radius: .4rem; cursor: pointer; }
    .generate-page .tag-pop-opt:hover,
    .generate-page .tag-pop-opt.active { color: #0369A1; background: #E7F3FC; }
    .generate-page .tag-pop-opt.is-selected { font-weight: 600; }
    .generate-page .tag-pop-opt.is-ignore { color: #92400E; }
    .generate-page .tag-pop-opt > span { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .generate-page .tag-pop-opt .bi-check2 { flex: 0 0 auto; margin-left: auto; color: #0EA5E9; }
    .generate-page .tag-pop-empty { padding: .6rem; font-size: .75rem; color: #94A3B8; text-align: center; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 generate-page">
    <div class="gen-head">
        <h4 class="page-title">Generate Dokumen</h4>
        <p>Buat dokumen dari data spreadsheet atau Excel dengan pemilihan sel manual.</p>
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
        <div class="col-lg-8 d-flex">
            <div class="card h-100 w-100">
                <div class="card-header"><span class="hdr-title"><i class="bi bi-database"></i> Sumber Data</span></div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="btn-group source-seg w-100" role="group" aria-label="Sumber Data">
                            <input class="btn-check" type="radio" name="sourceType" id="sourceSpreadsheet" value="spreadsheet" checked autocomplete="off">
                            <label class="btn btn-outline-primary" for="sourceSpreadsheet"><i class="bi bi-google"></i>Google Spreadsheet</label>
                            <input class="btn-check" type="radio" name="sourceType" id="sourceExcel" value="excel" autocomplete="off">
                            <label class="btn btn-outline-primary" for="sourceExcel"><i class="bi bi-file-earmark-spreadsheet"></i>Upload Excel</label>
                        </div>
                    </div>

                    <div id="spreadsheetSection">
                        <div class="fetch-row">
                            <input type="url" id="spreadsheetUrl" class="form-control" placeholder="https://docs.google.com/spreadsheets/d/..." value="">
                            <button class="btn btn-primary" id="fetchSpreadsheetBtn"><i class="bi bi-cloud-download me-1"></i> Ambil Data</button>
                        </div>
                        <div class="source-help">Spreadsheet harus di-share ke email Google yang sudah terhubung (Drive Config).</div>
                    </div>

                    <div id="excelSection" style="display:none;">
                        <div class="fetch-row">
                            <input type="file" id="excelFile" class="form-control" accept=".xlsx,.xls">
                            <button class="btn btn-primary" id="fetchExcelBtn"><i class="bi bi-upload me-1"></i> Ambil Data</button>
                        </div>
                        <div class="source-help">Upload file Excel (.xlsx, maks 10MB).</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex">
            <div class="card h-100 w-100 d-flex flex-column">
                <div class="card-header"><span class="hdr-title"><i class="bi bi-funnel"></i> Filter Kolom</span></div>
                <div class="card-body" id="colFilterPanel">
                    <div class="gen-empty text-center"><i class="bi bi-funnel"></i><p class="mb-0 small">Muat data terlebih dahulu.</p></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 2: Data Tersedia --}}
    <div class="mb-4">
        <div class="card" id="dataCard" style="display:none;">
            <div class="card-header">
                <span class="hdr-title"><i class="bi bi-table"></i> Data Tersedia</span>
                <div class="card-header-actions">
                    <input type="search" id="dataSearch" class="form-control form-control-sm" placeholder="Cari di tabel..." style="max-width:12.5rem;">
                    <span class="badge bg-light text-dark border" id="totalCells">0 sel</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="gen-massal-hint" id="massalTableHint" style="display:none;">
                    <i class="bi bi-info-circle"></i> Mode massal memproses semua baris data. Pemilihan sel dinonaktifkan.
                </div>
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
        <div class="col-lg-8 d-flex" id="selectedCol">
            <div class="card h-100 w-100 d-flex flex-column">
                <div class="card-header">
                    <span class="hdr-title"><i class="bi bi-check2-square"></i> Data Terpilih</span>
                    <div class="card-header-actions">
                        <span class="badge bg-primary" id="selectedCount">0</span>
                        <button class="btn btn-sm btn-outline-danger" id="clearSelectedBtn" title="Bersihkan semua pilihan"><i class="bi bi-eraser me-1"></i>Bersihkan</button>
                    </div>
                </div>
                <div class="card-body" id="selectedPanel">
                    <div class="gen-empty text-center"><i class="bi bi-check2-square"></i><p class="mb-0 small">Centang sel pada tabel data untuk memilih.</p></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 d-flex">
            <div class="card h-100 w-100">
                <div class="card-header"><span class="hdr-title"><i class="bi bi-file-earmark-arrow-up"></i> Generate Dokumen</span></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Mode Generate</label>
                        <div class="btn-group mode-seg w-100" role="group" aria-label="Mode Generate">
                            <input type="radio" class="btn-check" name="generateMode" value="satuan" id="modeSatuan" checked autocomplete="off">
                            <label class="btn btn-outline-primary" for="modeSatuan"><i class="bi bi-file-earmark"></i>Single Generate</label>
                            <input type="radio" class="btn-check" name="generateMode" value="massal" id="modeMassal" autocomplete="off">
                            <label class="btn btn-outline-primary" for="modeMassal"><i class="bi bi-files"></i>Multiple</label>
                        </div>
                        <div class="form-text" id="modeHelpText">Satuan: 1 dokumen dari sel tercentang. Hasil 1 file.</div>
                    </div>
                    <form method="POST" action="{{ route('admin.generate.generate') }}" id="generateForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="selected_data" id="selectedDataInput">

                        <div class="mb-3">
                            <label class="form-label">Upload Template (.docx)</label>
                            <input type="file" name="template" id="templateFile" class="form-control" accept=".docx" required>
                            <div class="form-text" id="templateHelpSatuan">Gunakan placeholder @{{data 1}}, @{{data 2}} sesuai urutan centang.</div>
                            <div class="form-text" id="templateHelpMassal" style="display:none;">Gunakan placeholder nama kolom, mis. @{{Nama}}, @{{Nilai}}.</div>
                        </div>

                        <div id="satuanFields">
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

                            <button type="submit" class="btn btn-primary w-100 btn-generate" id="generateBtn" disabled>
                                <i class="bi bi-file-earmark-check me-1"></i> Generate
                            </button>
                        </div>
                    </form>
                    <div id="massalFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Kolom Nama File</label>
                            <select id="bulkFilenameCol" class="d-none"></select>
                            <button type="button" class="tag-select-btn col-select-btn" id="bulkFilenameBtn" aria-haspopup="listbox">
                                <span class="tag-select-label" id="bulkFilenameLabel">Pilih kolom...</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="form-text">Nama tiap dokumen diambil dari kolom ini. Duplikat otomatis diberi -2, -3.</div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted" id="bulkRowInfo">Belum ada data. Ambil data terlebih dahulu (maks 50 baris).</small>
                        </div>
                        <div class="mb-3" id="tagMappingWrap" style="display:none;">
                            <label class="form-label">Pemetaan Placeholder</label>

                            <div class="form-text mb-2" id="tagMappingHint">Pilih template untuk membaca tag-nya.</div>
                            <div id="tagMappingList"></div>
                        </div>
                        <div class="bulk-footer">
                            <button type="button" class="btn btn-primary w-100 btn-generate" id="generateBulkBtn">
                                <i class="bi bi-files me-1"></i> Generate Massal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Popover searchable untuk pemetaan placeholder --}}
    <div class="tag-pop" id="tagPop" style="display:none;" role="listbox" aria-label="Pilih kolom">

        <div class="tag-pop-search">
            <i class="bi bi-search"></i>
            <input type="text" id="tagPopSearch" placeholder="Cari kolom..." autocomplete="off">
        </div>
        <div class="tag-pop-list" id="tagPopList"></div>
    </div>

    {{-- ROW 4: Riwayat Generate --}}
    <div>
        <div class="card">
            <div class="card-header">
                <span class="hdr-title"><i class="bi bi-clock-history"></i> Riwayat Generate</span>
                <div class="dropdown">
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
                                <th style="width:2.25rem;"><input type="checkbox" id="historySelectAll" class="form-check-input mt-0" title="Pilih semua di halaman ini"></th>
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
                                <td class="history-filename" title="{{ $h->filename }}">{{ $h->filename }}</td>
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
var currentSource = 'spreadsheet';
var bulkCols = [];
var bulkTags = [];
var tagMapState = [];
var tagShowAll = false;
var tagPopKey = null;
var tagPopMode = 'tag';
var tagPopOptions = [];
var tagPopIndex = 0;


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
        var next = $(this).val();
        if ((selectedData.length || currentRows.length) && !confirm('Ganti sumber akan menghapus data dan pilihan saat ini. Lanjutkan?')) {
            $('input[name="sourceType"][value="' + currentSource + '"]').prop('checked', true);
            return;
        }
        currentSource = next;
        if (next === 'spreadsheet') {
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
        $('#colFilterPanel').html('<div class="gen-empty text-center"><i class="bi bi-funnel"></i><p class="mb-0 small">Muat data terlebih dahulu.</p></div>');
        $('#bulkFilenameCol').empty();
        syncFilenameLabel();

        $('#bulkRowInfo').text('Belum ada data. Ambil data terlebih dahulu (maks 50 baris).');
        bulkCols = [];
        bulkTags = [];
        $('#tagMappingWrap').hide();
        $('#tagMappingList').empty();
        updateSelectedPanel();
    });

    $('input[name="generateMode"]').on('change', function() {
        var massal = $(this).val() === 'massal';
        $('#satuanFields').toggle(!massal);
        $('#massalFields').toggle(massal);
        $('#templateHelpSatuan').toggle(!massal);
        $('#templateHelpMassal').toggle(massal);
        $('#selectedCol').toggle(!massal);
        $('#modeHelpText').text(massal
            ? 'Massal: 1 baris = 1 dokumen. Hasil 1 file ZIP.'

            : 'Satuan: 1 dokumen dari sel tercentang. Hasil 1 file.');
        applySelectionMode();
        detectBulkTags();
    });

    $('#templateFile').on('change', function() {
        detectBulkTags();
    });

    $('#tagMappingList').on('click', '.tag-select-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openTagPop(this);
    });

    $('#bulkFilenameBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openFilenamePop(this);
    });


    $('#tagMappingList').on('click', '#tagShowAll', function(e) {
        e.preventDefault();
        tagShowAll = !tagShowAll;
        renderTagSections();
    });

    $('#tagMappingList').on('scroll', function() { closeTagPop(); });

    $('#tagPopSearch').on('input', function() {
        tagPopIndex = 0;
        renderTagPopOptions($(this).val());
    });

    $('#tagPopSearch').on('keydown', function(e) {
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!tagPopOptions.length) return;
            var step = e.key === 'ArrowDown' ? 1 : -1;
            tagPopIndex = (tagPopIndex + step + tagPopOptions.length) % tagPopOptions.length;
            renderTagPopOptions($(this).val());
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (tagPopOptions[tagPopIndex]) chooseTagValue(tagPopOptions[tagPopIndex].value);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            var focusKey = tagPopKey;
            closeTagPop();
            if (focusKey !== null) {
                $('#tagMappingList .tag-map-row').filter(function() {
                    return String($(this).data('key')) === String(focusKey);
                }).find('.tag-select-btn').trigger('focus');
            }
        }

    });

    $('#tagPopList').on('click', '.tag-pop-opt', function() {
        chooseTagValue($(this).attr('data-value') || '');
    });

    $(document).on('click', function(e) {
        if (!$('#tagPop').is(':visible')) return;
        if ($(e.target).closest('.tag-pop, .tag-select-btn').length) return;
        closeTagPop();
    });

    $(window).on('resize scroll', closeTagPop);


    $('#clearSelectedBtn').on('click', function() {
        $('.cell-checkbox:checked').prop('checked', false);
        $('.row-check').prop('checked', false);
        selectedData = [];
        dataCounter = 1;
        updateSelectedPanel();
    });

    $('#selectedPanel').on('click', '.selected-remove', function(e) {
        e.stopPropagation();
        var selId = $(this).data('selid');
        $('.cell-checkbox').filter(function() {
            return $(this).data('selid') === selId;
        }).prop('checked', false).trigger('change');
    });

    $('#selectedPanel').on('click', '.selected-item', function() {
        var selId = $(this).data('selid');
        var cb = $('.cell-checkbox').filter(function() {
            return $(this).data('selid') === selId;
        }).first();
        if (!cb.length) return;
        var td = cb.closest('td');
        var wrap = $('.data-table-wrap');
        wrap.scrollTop(td.position().top + wrap.scrollTop() - wrap.height() / 2);
        wrap.scrollLeft(td.position().left + wrap.scrollLeft() - 80);
        td.addClass('cell-flash');
        setTimeout(function() { td.removeClass('cell-flash'); }, 1200);
    });

    $('#dataSearch').on('input', function() {
        var q = $(this).val().toLowerCase();
        $('#dataTableBody tr.data-row').each(function() {
            var txt = $(this).text().toLowerCase();
            $(this).toggle(!q || txt.indexOf(q) !== -1);
        });
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
    if (!url) { showGenerateAlert('danger', 'Masukkan link Google Spreadsheet.'); return; }
    if (!/docs\.google\.com\/spreadsheets/i.test(url)) { showGenerateAlert('danger', 'Link harus berupa URL Google Spreadsheet (docs.google.com/spreadsheets).'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    showDataSkeleton();

    $.ajax({
        url: '{{ route("admin.generate.fetch-spreadsheet") }}',
        method: 'POST',
        data: { _token: $('meta[name="csrf-token"]').attr('content'), url: url },
        success: function(res) { renderDataTable(res); },
        error: function(xhr) { $('#dataCard').hide(); showGenerateAlert('danger', xhr.responseJSON?.error || 'Gagal membaca spreadsheet.'); },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-cloud-download me-1"></i> Ambil Data'); }
    });
});

$('#fetchExcelBtn').on('click', function() {
    var file = $('#excelFile')[0].files[0];
    if (!file) { showGenerateAlert('danger', 'Pilih file Excel terlebih dahulu.'); return; }

    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
    showDataSkeleton();
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
        error: function(xhr) { $('#dataCard').hide(); showGenerateAlert('danger', xhr.responseJSON?.error || 'Gagal membaca Excel.'); },
        complete: function() { btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Ambil Data'); }
    });
});

function showDataSkeleton() {
    var h = '';
    for (var i = 0; i < 5; i++) {
        h += '<tr><td class="text-muted text-center">...</td>';
        for (var j = 0; j < 4; j++) {
            h += '<td><div class="placeholder-glow"><span class="placeholder col-12"></span></div></td>';
        }
        h += '</tr>';
    }
    $('#dataTableHead').html('');
    $('#dataTableBody').html(h);
    $('#dataCard').show();
}

function applySelectionMode() {
    var massal = $('input[name="generateMode"]:checked').val() === 'massal';
    if (massal) {
        $('.cell-checkbox, .row-check, .col-header-check').prop('checked', false);
        selectedData = [];
        dataCounter = 1;
        updateSelectedPanel();
    }
    $('.cell-checkbox, .row-check, .col-header-check').prop('disabled', massal);
    $('#massalTableHint').css('display', massal ? 'flex' : 'none');
}

function renderDataTable(res) {
    closeTagPop();
    selectedData = [];
    dataCounter = 1;
    currentRows = res.rows || [];
    updateSelectedPanel();
    $('#dataSearch').val('');
    bulkCols = [];
    bulkTags = [];
    $('#tagMappingWrap').hide();
    $('#tagMappingList').empty();

    var cols = res.columns || [];
    var rows = res.rows || [];

    if (rows.length === 0) {
        showGenerateAlert('danger', 'Tidak ada data ditemukan di sumber ini.');
        return;
    }

    // Column filter panel — badge style
    var filterHtml = '';
    cols.forEach(function(c) {
        filterHtml += '<span class="col-filter-badge active" data-col="' + esc(c) + '"><span class="cfb-label">' + esc(c) + '</span><i class="bi bi-eye"></i></span>';
    });
    $('#colFilterPanel').html(filterHtml);

    // Bulk filename column dropdown
    var bulkSel = $('#bulkFilenameCol');
    bulkSel.empty();
    var defIdx = 0;
    cols.forEach(function(c, i) {
        bulkSel.append($('<option>', { value: c, text: c }));
        if (/^(nama|name)$/i.test(String(c).trim())) defIdx = i;
    });
    bulkSel.prop('selectedIndex', defIdx);
    syncFilenameLabel();

    $('#bulkRowInfo').text(rows.length + ' baris siap diproses massal (maks 50).');
    bulkCols = cols.slice();
    detectBulkTags();

    // Build table header with "select all" checkbox per column
    var thead = '<tr><th style="width:2.5rem;" class="row-num">No</th>';
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
        tbody += '<td class="text-muted text-center row-num"><input type="checkbox" class="row-check me-1" data-row="' + ri + '" title="Centang semua sel baris ini">' + (ri + 1) + '</td>';
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
    $('#totalCells').text(rows.length + ' baris × ' + cols.length + ' kolom (' + cellCount + ' sel)');

    applySelectionMode();

    // Individual cell checkbox
    $('.cell-checkbox').on('change', function() {
        if ($(this).is(':checked')) {
            selectionIdCounter++;
            $(this).data('selid', selectionIdCounter);
            selectedData.push({ id: selectionIdCounter, val: $(this).data('val'), col: $(this).data('col'), row: $(this).data('row') });
        } else {
            var selId = $(this).data('selid');
            selectedData = selectedData.filter(function(item) { return item.id !== selId; });
        }
        dataCounter = 1;
        updateSelectedPanel();
    });

    // "Select all in row" checkbox
    $('.row-check').on('change', function() {
        var ri = $(this).data('row');
        var checked = $(this).is(':checked');
        $('.cell-checkbox').filter(function() {
            return $(this).data('row') === ri;
        }).each(function() {
            if ($(this).is(':checked') !== checked) {
                $(this).prop('checked', checked).trigger('change');
            }
        });
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
        panel.html('<div class="gen-empty text-center"><i class="bi bi-check2-square"></i><p class="mb-0 small">Centang sel pada tabel data untuk memilih.</p></div>');
        return;
    }

    var h = '';
    selectedData.forEach(function(item, i) {
        h += '<div class="selected-item" data-selid="' + item.id + '" title="Klik untuk melihat sel asal">';
        h += '<span class="num">Data ' + (i + 1) + '</span>';
        h += '<span class="si-val">' + esc(item.val || '(kosong)') + '</span>';
        h += '<span class="si-meta">(Baris ' + (parseInt(item.row, 10) + 1) + ' &middot; ' + esc(item.col) + ')</span>';
        h += '<button class="btn btn-sm btn-link text-danger text-decoration-none p-0 selected-remove" data-selid="' + item.id + '" title="Hapus pilihan ini"><i class="bi bi-x-lg"></i></button>';
        h += '</div>';
    });
    panel.html(h);
}

function detectBulkTags() {
    closeTagPop();
    var wrap = $('#tagMappingWrap');
    var isMassal = $('input[name="generateMode"]:checked').val() === 'massal';
    if (!isMassal) { wrap.hide(); return; }
    var fileInput = $('#templateFile')[0];
    var tpl = fileInput && fileInput.files[0];
    if (!currentRows.length || !tpl || !bulkCols.length) { wrap.hide(); return; }
    tagMapState = [];
    tagShowAll = false;
    $('#tagMappingHint').text('Membaca tag template...');
    $('#tagMappingList').html('<div class="placeholder-glow"><span class="placeholder col-12"></span></div>');
    wrap.show();
    var fd = new FormData();
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    fd.append('template', tpl);
    fd.append('columns', JSON.stringify(bulkCols));
    $.ajax({
        url: '{{ route("admin.generate.detect-tags") }}',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) { renderTagMapping(res.tags || []); },
        error: function() {
            $('#tagMappingHint').text('Gagal membaca tag template.');
            $('#tagMappingList').empty();
        }
    });
}

function renderTagMapping(tags) {
    bulkTags = tags || [];
    closeTagPop();
    tagShowAll = false;
    tagMapState = bulkTags.map(function(t) {
        var auto = t.matched || '';
        return { key: String(t.key), tag: t.tag, value: auto };
    });
    var list = $('#tagMappingList');
    if (!tagMapState.length) {
        $('#tagMappingHint').text('Tidak ada tag {..} di template selain tag sistem.');
        list.empty();
        return;
    }
    $('#tagMappingHint').text('Tag sistem & {data N} otomatis. Pilih kolom untuk tiap placeholder.');
    renderTagSections();
}

function tagRowHtml(t, hidden) {
    var v = t.value || '';
    var label = !v ? '— Pilih —' : (v === '__ignore__' ? 'Abaikan (biarkan apa adanya)' : v);
    var cls = 'tag-map-row' + (v ? '' : ' tag-unmapped') + (hidden ? ' tag-extra' : '');
    var style = hidden ? ' style="display:none"' : '';
    return '<div class="' + cls + '" data-key="' + esc(String(t.key)) + '"' + style + '>'
        + '<span class="badge">' + '{' + '{' + esc(t.tag) + '}' + '}</span>'
        + '<div class="tag-select">'
        + '<input type="hidden" class="tag-map-select" value="' + esc(v) + '">'
        + '<button type="button" class="tag-select-btn' + (v ? '' : ' is-empty') + '">'
        + '<span class="tag-select-label">' + esc(label) + '</span>'
        + '<i class="bi bi-chevron-down"></i>'
        + '</button>'
        + '</div>'
        + '<i class="bi ' + (v ? 'bi-check-circle-fill tag-state-ico ok' : 'bi-exclamation-circle-fill tag-state-ico warn') + '"></i>'
        + '</div>';
}

function renderTagSections() {
    var list = $('#tagMappingList');
    var scrollTop = list.scrollTop();
    var matchedOpen = $('#tagMappingList .tag-matched').prop('open') || false;
    var unresolved = tagMapState.filter(function(t) { return !t.value; });
    var resolved = tagMapState.filter(function(t) { return !!t.value; });
    var total = tagMapState.length;
    var need = unresolved.length;
    var pct = total ? Math.round(((total - need) / total) * 100) : 100;

    var h = '<div class="tag-map-top">'
        + '<span class="tag-map-count' + (need ? '' : ' is-done') + '">'
        + (need ? need + ' belum cocok dari ' + total + ' tag' : 'Semua ' + total + ' tag sudah cocok')
        + '</span>'
        + '<span class="tag-progress"><span style="width:' + pct + '%"></span></span>'
        + '</div>';

    if (need) {
        h += '<div class="tag-section-title">Belum dicocokkan <span class="badge bg-warning text-dark">' + need + '</span></div>';
        unresolved.forEach(function(t, i) {
            h += tagRowHtml(t, !tagShowAll && i >= 5);
        });
        if (unresolved.length > 5) {
            h += '<button type="button" class="tag-show-all" id="tagShowAll">'
                + '<i class="bi bi-chevron-' + (tagShowAll ? 'up' : 'down') + '"></i> '
                + (tagShowAll ? 'Sembunyikan sebagian' : 'Lihat semua tag (' + unresolved.length + ')')
                + '</button>';
        }
    } else {
        h += '<div class="tag-all-done"><i class="bi bi-check-circle-fill"></i> Semua tag sudah cocok atau diabaikan. Siap generate.</div>';
    }

    if (resolved.length) {
        var open = need ? matchedOpen : true;
        h += '<details class="tag-matched"' + (open ? ' open' : '') + '>'
            + '<summary><i class="bi bi-check2-circle"></i> Sudah dicocokkan <span class="badge bg-success">' + resolved.length + '</span></summary>'
            + '<div class="tag-matched-rows">';
        resolved.forEach(function(t) { h += tagRowHtml(t, false); });
        h += '</div></details>';
    }

    list.html(h);
    list.scrollTop(scrollTop);
}

function openTagPop(btn) {
    var $btn = $(btn);
    tagPopMode = 'tag';
    var key = String($btn.closest('.tag-map-row').data('key'));
    var state = tagMapState.filter(function(t) { return String(t.key) === key; })[0];
    tagPopKey = key;
    positionPickerPop($btn, state ? (state.value || '') : '');
}

function openFilenamePop(btn) {
    tagPopMode = 'filename';
    tagPopKey = null;
    positionPickerPop($(btn), $('#bulkFilenameCol').val() || '');
}

function positionPickerPop($btn, current) {
    var pop = $('#tagPop');
    var rect = $btn[0].getBoundingClientRect();
    var width = Math.max(rect.width, 288);
    width = Math.min(width, window.innerWidth - 16);
    var left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8));
    pop.css({ left: left + 'px', top: '0px', width: width + 'px' }).show();

    $('.tag-select-btn').removeClass('is-open');
    $btn.addClass('is-open');
    $('#tagPopSearch').val('');
    tagPopIndex = 0;
    renderTagPopOptions('');
    var idx = 0;
    tagPopOptions.forEach(function(o, i) { if (o.value === current) idx = i; });
    tagPopIndex = idx;
    renderTagPopOptions('');

    var popH = pop.outerHeight() || 300;
    var top = rect.bottom + 6;
    if (top + popH > window.innerHeight - 8 && rect.top > popH) {
        top = rect.top - popH - 6;
    }
    top = Math.max(8, Math.min(top, window.innerHeight - popH - 8));
    pop.css('top', top + 'px');

    setTimeout(function() { $('#tagPopSearch').trigger('focus'); }, 0);
}


function closeTagPop() {
    $('#tagPop').hide();
    tagPopKey = null;
    $('.tag-select-btn').removeClass('is-open');
}

function renderTagPopOptions(query) {
    var q = String(query || '').toLowerCase().trim();
    var current = '';
    var opts = [];
    if (tagPopMode === 'filename') {
        current = $('#bulkFilenameCol').val() || '';
        bulkCols.forEach(function(c) { opts.push({ value: c, label: c }); });
    } else {
        var state = tagMapState.filter(function(t) { return String(t.key) === String(tagPopKey); })[0];
        current = state ? (state.value || '') : '';
        opts.push({ value: '', label: '— Pilih —' });
        bulkCols.forEach(function(c) { opts.push({ value: c, label: c }); });
        opts.push({ value: '__ignore__', label: 'Abaikan (biarkan apa adanya)' });
    }
    tagPopOptions = opts.filter(function(o) {
        return !q || String(o.label).toLowerCase().indexOf(q) !== -1;
    });

    if (tagPopIndex >= tagPopOptions.length) tagPopIndex = tagPopOptions.length - 1;
    if (tagPopIndex < 0) tagPopIndex = 0;

    var h = '';
    tagPopOptions.forEach(function(o, i) {
        var cls = 'tag-pop-opt'
            + (i === tagPopIndex ? ' active' : '')
            + (o.value === current ? ' is-selected' : '')
            + (o.value === '__ignore__' ? ' is-ignore' : '');
        h += '<div class="' + cls + '" data-value="' + esc(o.value) + '">'
            + '<span>' + esc(o.label) + '</span>'
            + (o.value === current ? '<i class="bi bi-check2"></i>' : '')
            + '</div>';
    });
    if (!h) h = '<div class="tag-pop-empty">Tidak ada kolom yang cocok.</div>';
    $('#tagPopList').html(h);
    var active = $('#tagPopList .tag-pop-opt.active')[0];
    if (active && active.scrollIntoView) active.scrollIntoView({ block: 'nearest' });
}

function syncFilenameLabel() {
    var v = $('#bulkFilenameCol').val();
    $('#bulkFilenameLabel').text(v ? v : 'Pilih kolom...');
    $('#bulkFilenameBtn').toggleClass('is-empty', !v);
}

function chooseTagValue(value) {
    if (tagPopMode === 'filename') {
        closeTagPop();
        $('#bulkFilenameCol').val(value || '');
        syncFilenameLabel();
        $('#bulkFilenameBtn').trigger('focus');
        return;
    }
    var key = tagPopKey;
    var state = tagMapState.filter(function(t) { return String(t.key) === String(key); })[0];
    closeTagPop();
    if (!state) return;
    state.value = value || '';
    renderTagSections();
    var $row = $('#tagMappingList .tag-map-row').filter(function() {
        return String($(this).data('key')) === String(key);
    });
    if ($row.length && $row.is(':visible')) {
        $row.find('.tag-select-btn').trigger('focus');
        return;
    }
    var next = tagMapState.filter(function(t) { return !t.value; })[0];
    if (next) {
        $('#tagMappingList .tag-map-row').filter(function() {
            return String($(this).data('key')) === String(next.key);
        }).find('.tag-select-btn').trigger('focus');
    }
}


// Generate massal via AJAX: ZIP + riwayat instan tanpa refresh
$('#generateBulkBtn').on('click', function() {
    if (!currentRows.length) { showGenerateAlert('danger', 'Ambil data terlebih dahulu.'); return; }
    if (currentRows.length > 50) { showGenerateAlert('danger', 'Maksimal 50 baris per sekali generate massal.'); return; }
    var tpl = $('#templateFile')[0].files[0];
    if (!tpl) { showGenerateAlert('danger', 'Upload template (.docx) terlebih dahulu.'); return; }
    var col = $('#bulkFilenameCol').val();
    if (!col) { showGenerateAlert('danger', 'Pilih kolom nama file terlebih dahulu.'); return; }

    var mapping = {};
    var incomplete = false;
    if ($('#tagMappingWrap').is(':visible') && $('#tagMappingList .tag-map-row').length) {
        $('#tagMappingList .tag-map-row').each(function() {
            var v = $(this).find('.tag-map-select').val();
            if (!v) { incomplete = true; return false; }
            if (v !== '__ignore__') mapping[$(this).data('key')] = v;
        });
        if (incomplete) {
            tagShowAll = true;
            renderTagSections();
            showGenerateAlert('danger', 'Masih ada placeholder yang belum dipetakan. Pilih kolom atau Abaikan.');
            $('#tagMappingWrap')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    }

    var btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Membuat ' + currentRows.length + ' dokumen...');

    var fd = new FormData();
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
    fd.append('rows', JSON.stringify(currentRows));
    fd.append('filename_column', col);
    fd.append('mapping', JSON.stringify(mapping));
    fd.append('template', tpl);

    $.ajax({
        url: '{{ route("admin.generate.bulk") }}',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res && res.success) {
                var msg = res.count + ' dokumen "' + res.filename + '" berhasil dibuat dan diunduh.';
                if (res.skipped) msg += ' (' + res.skipped + ' baris kosong dilewati)';
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
            var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)) || 'Gagal generate massal. Silakan coba lagi.';
            showGenerateAlert('danger', msg);
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="bi bi-files me-1"></i> Generate Massal');
        }
    });
});

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
        '<td class="history-filename" title="' + esc(res.filename).replace(/"/g, '&quot;') + '">' + esc(res.filename) + '</td>' +
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
