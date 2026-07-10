@extends('layouts.main')

@section('page-title', 'Status Berkas')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Status Berkas</h4>
            <p class="text-muted small mb-0 mt-1">Pantau status dan upload berkas untuk direview Admin.</p>
        </div>
        <a href="{{ route('status.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i> Refresh</a>
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

    {{-- Upload Berkas Baru --}}
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-cloud-upload text-warning"></i> Upload Berkas Baru
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('dashboard.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Folder Tujuan</label>
                        <select name="folder_tujuan" class="form-select" id="folderSelect">
                            <option value="">-- Pilih Folder --</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">File (.docx / .pdf)</label>
                        <input type="file" name="file" class="form-control" accept=".docx,.pdf" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-warning"><i class="bi bi-cloud-arrow-up me-1"></i> Upload</button>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="form-label">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                    <textarea name="catatan" class="form-control" rows="1" placeholder="Catatan untuk Admin..."></textarea>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Dokumen --}}
    @forelse ($kejadianPerDokumen ?? [] as $doc)
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <i class="bi bi-file-earmark me-1"></i> {{ $doc['induk'] ?? 'Dokumen' }}
                    <span class="chip chip-subfolder ms-2"><i class="bi bi-folder"></i> {{ $doc['subfolder'] ?? '(folder utama)' }}</span>
                </div>
                @php
                    $badgeClass = $doc['statusLabel'] == 'Menunggu' ? 'badge-pending' : ($doc['statusLabel'] == 'Disetujui' ? 'badge-approved' : 'badge-revision');
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $doc['statusLabel'] ?? 'Pending' }}</span>
            </div>
            <div class="card-body">
                {{-- Timeline --}}
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
                @endif

                @php
                    $relatedUpload = $uploads->first(function($u) use ($doc) {
                        return $u->filename === $doc['induk'] || (isset($u->revisionHistories[0]) && $u->revisionHistories[0]->nama_file_sebelumnya === $doc['induk']);
                    });
                @endphp

                {{-- Catatan Admin & Upload Revisi --}}
                @if ($relatedUpload && $relatedUpload->status == 'revision')
                    {{-- Catatan Admin (collapse) --}}
                    @if (!empty($relatedUpload->catatan_admin))
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#catatan{{ $relatedUpload->id }}" aria-expanded="false">
                                <i class="bi bi-chat-dots me-1"></i> Lihat Catatan Admin
                            </button>
                            <div class="collapse mt-2" id="catatan{{ $relatedUpload->id }}">
                                <div class="alert alert-danger mb-0">
                                    <ul class="mb-0 ps-3">
                                        @foreach ((array) $relatedUpload->catatan_admin as $note)
                                            @if (trim($note))
                                                <li>{{ trim($note) }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                                @if ($relatedUpload->notifikasi_pdf && file_exists($relatedUpload->notifikasi_pdf))
                                    <a href="{{ route('status.download-notif', $relatedUpload->id) }}" class="btn btn-sm btn-outline-danger mt-2">
                                        <i class="bi bi-file-pdf me-1"></i> Download Notifikasi Revisi (PDF)
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Upload Revisi --}}
                    <div class="mt-3 p-3 bg-light rounded">
                        <form method="POST" action="{{ route('dashboard.upload-revisi') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="upload_id" value="{{ $relatedUpload->id }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label mb-0">Upload File Revisi (.docx / .pdf)</label>
                                    <input type="file" name="file" class="form-control form-control-sm" accept=".docx,.pdf" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-danger btn-sm w-100"><i class="bi bi-arrow-repeat me-1"></i> Kirim Revisi</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="card">
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p class="mb-0">Belum ada riwayat pengiriman.</p>
            </div>
        </div>
    @endforelse
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
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