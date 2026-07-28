@extends('layouts.main')

@section('page-title', 'Rekapitulasi Catatan')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Rekapitulasi Catatan</h4>
            <p class="text-muted small mb-0 mt-1">Download rekapitulasi catatan per user dalam format Excel.</p>
        </div>
    </div>

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="card">
        <div class="card-header"><i class="bi bi-download me-1"></i> Download Rekapitulasi</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Nama User</th>
                            <th>Username</th>
                            <th>Jumlah Dokumen</th>
                            <th style="width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users ?? [] as $index => $user)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $user->nama ?? '-' }}</td>
                                <td>@<span>{{ $user->username }}</span></td>
                                <td>{{ ($countMap[$user->id] ?? 0) }} dokumen</td>
                                <td>
                                    <a href="{{ route('admin.rekapitulasi.download', $user) }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state"><i class="bi bi-people"></i><p class="mb-0">Tidak ada user.</p></div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="p-3 border-top">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
