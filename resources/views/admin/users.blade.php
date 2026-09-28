@extends('layouts.main')

@section('page-title', 'Manajemen User')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="page-title mb-0">Manajemen User</h4>
            <p class="text-muted small mb-0 mt-1">Kelola pengguna yang memiliki akses ke sistem.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle"></i>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @session('success')
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession
    @session('error')
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2">{{ $value }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
    @endsession

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="bi bi-people me-1"></i> Daftar User</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addUserCollapse">
                    <i class="bi bi-person-plus me-1"></i> Tambah User
                </button>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#resetPassCollapse">
                    <i class="bi bi-key me-1"></i> Reset Password
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users ?? [] as $index => $user)
                            <tr class="{{ $user->is_active ? '' : 'table-warning' }}">
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-medium">@<span>{{ $user->username }}</span></td>
                                <td>{{ $user->nama ?? '-' }}</td>
                                <td>
                                    @if ($user->role == 'admin')
                                        <span class="badge badge-approved"><i class="bi bi-shield me-1"></i>Admin</span>
                                    @else
                                        <span class="badge badge-pending"><i class="bi bi-person me-1"></i>User</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge badge-approved"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                                    @else
                                        <span class="badge badge-revision"><i class="bi bi-x-circle me-1"></i>Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" style="display:inline;" id="toggleForm-{{ $user->id }}">
                                        @csrf
                                        @if ($user->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger toggle-user-btn" data-form="toggleForm-{{ $user->id }}" data-username="{{ $user->username }}" data-action="Nonaktifkan"><i class="bi bi-pause-circle me-1"></i> Nonaktifkan</button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-success toggle-user-btn" data-form="toggleForm-{{ $user->id }}" data-username="{{ $user->username }}" data-action="Aktifkan"><i class="bi bi-play-circle me-1"></i> Aktifkan</button>
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state"><i class="bi bi-people"></i><p class="mb-0">Tidak ada user.</p></div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="collapse mb-4 {{ $errors->hasAny(['username', 'nama', 'role']) ? 'show' : '' }}" id="addUserCollapse">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-plus me-1"></i> Tambah User Baru</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" value="{{ old('nama') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="collapse mb-4 {{ $errors->has('user_id') ? 'show' : '' }}" id="resetPassCollapse">
        <div class="card">
            <div class="card-header"><i class="bi bi-key me-1"></i> Reset Password</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.reset-password') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Pilih User</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Pilih User --</option>
                                @foreach ($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->username }} ({{ $user->nama ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Konfirmasi</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-secondary"><i class="bi bi-arrow-repeat me-1"></i> Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="toggleUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <i class="bi bi-person-gear text-warning" style="font-size:3rem;"></i>
                    <h5 class="mt-3" id="toggleUserTitle">Ubah Status User</h5>
                    <p class="text-muted mb-0 small" id="toggleUserName"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4" id="toggleUserConfirm"><i class="bi bi-check-lg me-1"></i>Ya, Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var targetForm = null;
    document.querySelectorAll('.toggle-user-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            targetForm = document.getElementById(btn.getAttribute('data-form'));
            document.getElementById('toggleUserTitle').textContent = btn.getAttribute('data-action') + ' User';
            document.getElementById('toggleUserName').textContent = '@' + btn.getAttribute('data-username');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('toggleUserModal')).show();
        });
    });
    document.getElementById('toggleUserConfirm').addEventListener('click', function() {
        document.activeElement?.blur();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('toggleUserModal')).hide();
        if (targetForm) targetForm.submit();
    });
})();
</script>
@endpush