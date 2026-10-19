@extends('layouts.main')

@section('page-title', 'Manajemen User')

@push('styles')
<style>
    /* === Manajemen User — scoped theme (konsisten dengan dashboard SIMADO) === */
    .user-page { --u-border: #E2EAF5; --u-muted: #64748B; }

    /* Header halaman */
    .user-page .user-head { display: flex; align-items: center; gap: .85rem; margin-bottom: 1.25rem; }
    .user-page .user-head .head-icon {
        width: 2.75rem; height: 2.75rem; border-radius: 0.75rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.25rem;
    }
    .user-page .user-head .page-title { font-size: 1.35rem; }
    .user-page .user-head p { color: var(--u-muted); font-size: .875rem; margin: .15rem 0 0; }

    .user-page .card { border: 1px solid var(--u-border); }
    .user-page .card-header { padding: 1rem 1.25rem; }
    .user-page .card-header .hdr-icon {
        width: 2.5rem; height: 2.5rem; border-radius: 0.625rem; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center;
        background: #E7F3FC; color: var(--primary); font-size: 1.05rem;
    }
    .user-page .card-header .hdr-title { font-weight: 600; font-size: .95rem; color: #0f172a; line-height: 1.2; }
    .user-page .card-header .hdr-title i { color: var(--primary); }
    .user-page .card-header .hdr-sub { font-size: .75rem; color: var(--u-muted); }

    /* Pencarian */
    .user-page .search-field { position: relative; min-width: 13.75rem; }
    .user-page .search-field > i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; pointer-events: none; }
    .user-page .search-field input { padding-left: 2.1rem; }
    @media (max-width: 575.98px) { .user-page .search-field { min-width: 0; flex: 1 1 auto; } }

    /* Tabel */
    .user-page .user-table { font-size: .875rem; margin: 0; }
    .user-page .user-table thead th {
        background: #EAF2FB; color: #475569; font-weight: 600;
        font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
        padding: .85rem 1.1rem; border-bottom: 1px solid var(--u-border); white-space: nowrap;
    }
    .user-page .user-table tbody td { padding: .85rem 1.1rem; vertical-align: middle; border-color: #f1f5f9; }
    .user-page .user-table tbody tr { transition: background .12s ease; }
    .user-page .user-table tbody tr:hover { background: #F8FAFC; }
    .user-page .user-table tbody tr.is-inactive { background: #FFFDF5; }
    .user-page .user-table .btn { border-radius: 0.5rem; }
    .user-page .u-username { font-weight: 600; color: #0f172a; }
    .user-page .u-muted { color: var(--u-muted); }

    /* Badge lembut */
    .user-page .badge-soft { display: inline-flex; align-items: center; gap: .3rem; padding: .35em .7em; border-radius: 62.4375rem; font-size: .75rem; font-weight: 500; }
    .user-page .badge-soft.blue { background: #E7F3FC; color: #0369a1; }
    .user-page .badge-soft.yellow { background: #FEF3C7; color: #92400e; }
    .user-page .badge-soft.green { background: #E6F6EC; color: #15803d; }
    .user-page .badge-soft.red { background: #FEE2E2; color: #b91c1c; }

    .user-page .user-footer { border-top: 1px solid var(--u-border); }

    /* Form tambah & reset */
    .user-page .form-hint { font-size: .75rem; color: #94a3b8; }

    /* Modal konfirmasi status */
    .user-page .user-modal .modal-body { padding: 1.5rem 1.25rem .5rem; }
    .user-page .user-modal .user-modal-icon { width: 3.5rem; height: 3.5rem; border-radius: 1rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.6rem; background: #EEF2F7; color: #64748b; }
    .user-page .user-modal .user-modal-name { font-weight: 600; color: #0f172a; margin: .25rem 0 .35rem; }
    .user-page .user-modal.state-danger .user-modal-icon { background: #FEE2E2; color: #dc2626; }
    .user-page .user-modal.state-success .user-modal-icon { background: #E6F6EC; color: #16a34a; }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 user-page">
    <div class="user-head">
        <span class="head-icon"><i class="bi bi-people"></i></span>
        <div>
            <h4 class="page-title">Manajemen User</h4>
            <p>Kelola pengguna yang memiliki akses ke sistem.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle mt-1"></i>
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

    {{-- Card Daftar User --}}
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="hdr-icon"><i class="bi bi-people"></i></span>
                <div>
                    <div class="hdr-title">Daftar User</div>
                    <div class="hdr-sub">Total {{ $users->total() }} user</div>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="search-field">
                    <i class="bi bi-search"></i>
                    <input type="search" id="userSearch" class="form-control form-control-sm" placeholder="Cari username atau nama..." aria-label="Cari user">
                </div>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addUserCollapse">
                    <i class="bi bi-person-plus me-1"></i> Tambah User
                </button>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#resetPassCollapse">
                    <i class="bi bi-key me-1"></i> Reset Password
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table user-table mb-0">
                    <thead>
                        <tr>
                            <th style="width:3.5rem;">No</th>
                            <th>Username</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th style="width:6.875rem;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="userTableBody">
                        @forelse ($users ?? [] as $index => $user)
                            <tr class="{{ $user->is_active ? '' : 'is-inactive' }}" data-user-row data-search="{{ strtolower($user->username . ' ' . ($user->nama ?? '')) }}">
                                <td class="u-muted">{{ $users->firstItem() + $index }}</td>
                                <td><span class="u-username">&#64;{{ $user->username }}</span></td>
                                <td>{{ $user->nama ?? '-' }}</td>
                                <td>
                                    @if ($user->role == 'admin')
                                        <span class="badge-soft blue"><i class="bi bi-shield"></i>Admin</span>
                                    @else
                                        <span class="badge-soft yellow"><i class="bi bi-person"></i>User</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="badge-soft green"><i class="bi bi-check-circle"></i>Aktif</span>
                                    @else
                                        <span class="badge-soft red"><i class="bi bi-x-circle"></i>Nonaktif</span>
                                    @endif
                                </td>
                                <td class="u-muted small">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" style="display:inline;" id="toggleForm-{{ $user->id }}">
                                        @csrf
                                        @if ($user->is_active)
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon toggle-user-btn" data-form="toggleForm-{{ $user->id }}" data-username="{{ $user->username }}" data-action="Nonaktifkan" title="Nonaktifkan user">
                                                <i class="bi bi-pause-circle"></i>
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-success btn-icon toggle-user-btn" data-form="toggleForm-{{ $user->id }}" data-username="{{ $user->username }}" data-action="Aktifkan" title="Aktifkan user">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
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
        @if ($users->hasPages())
            <div class="p-3 user-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Form Tambah User --}}
    <div class="collapse mb-4 {{ $errors->hasAny(['username', 'nama', 'role']) ? 'show' : '' }}" id="addUserCollapse">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3">
                <span class="hdr-icon"><i class="bi bi-person-plus"></i></span>
                <div class="hdr-title mb-0">Tambah User Baru</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="new_username">Username</label>
                            <input type="text" name="username" id="new_username" class="form-control" value="{{ old('username') }}" required>
                            <div class="form-hint">Hanya huruf, angka, dan garis bawah. Minimal 3 karakter.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new_nama">Nama</label>
                            <input type="text" name="nama" id="new_nama" class="form-control" value="{{ old('nama') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new_password">Password</label>
                            <input type="password" name="password" id="new_password" class="form-control" required>
                            <div class="form-hint">Minimal 8 karakter, mengandung huruf besar dan angka.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new_password_confirmation">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" id="new_password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="new_role">Role</label>
                            <select name="role" id="new_role" class="form-select" required>
                                <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>User</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#addUserCollapse">Batal</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Form Reset Password --}}
    <div class="collapse mb-4 {{ $errors->has('user_id') ? 'show' : '' }}" id="resetPassCollapse">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3">
                <span class="hdr-icon"><i class="bi bi-key"></i></span>
                <div class="hdr-title mb-0">Reset Password</div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.reset-password') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="reset_user_id">Pilih User</label>
                            <select name="user_id" id="reset_user_id" class="form-select" required>
                                <option value="">-- Pilih User --</option>
                                @foreach ($users ?? [] as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->username }} ({{ $user->nama ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="reset_password">Password Baru</label>
                            <input type="password" name="password" id="reset_password" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="reset_password_confirmation">Konfirmasi</label>
                            <input type="password" name="password_confirmation" id="reset_password_confirmation" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat me-1"></i> Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi ubah status (mekanisme & id dipertahankan) --}}
    <div class="modal fade" id="toggleUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content user-modal">
                <div class="modal-body text-center">
                    <span class="user-modal-icon" id="toggleUserIconWrap"><i class="bi bi-person-gear"></i></span>
                    <h5 class="mt-3" id="toggleUserTitle">Ubah Status User</h5>
                    <p class="user-modal-name" id="toggleUserName"></p>
                    <p class="text-muted small mb-0" id="toggleUserMsg"></p>
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
    // Pencarian client-side (filter baris tanpa request ke server)
    var search = document.getElementById('userSearch');
    if (search) {
        search.addEventListener('input', function() {
            var q = search.value.trim().toLowerCase();
            document.querySelectorAll('#userTableBody tr[data-user-row]').forEach(function(tr) {
                var txt = tr.getAttribute('data-search') || '';
                tr.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    var targetForm = null;
    var modalEl = document.getElementById('toggleUserModal');

    document.querySelectorAll('.toggle-user-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            targetForm = document.getElementById(btn.getAttribute('data-form'));

            var action = btn.getAttribute('data-action') || '';
            var isDeactivate = action.toLowerCase() === 'nonaktifkan';

            var content = modalEl.querySelector('.modal-content');
            content.classList.toggle('state-danger', isDeactivate);
            content.classList.toggle('state-success', !isDeactivate);

            document.getElementById('toggleUserIconWrap').innerHTML = '<i class="bi ' + (isDeactivate ? 'bi-person-x' : 'bi-person-check') + '"></i>';
            document.getElementById('toggleUserTitle').textContent = action + ' User';
            document.getElementById('toggleUserName').textContent = '@' + btn.getAttribute('data-username');
            document.getElementById('toggleUserMsg').textContent = isDeactivate
                ? 'Pengguna ini tidak akan dapat mengakses sistem selama statusnya nonaktif.'
                : 'Pengguna ini akan dapat mengakses sistem kembali.';

            var confirm = document.getElementById('toggleUserConfirm');
            confirm.className = 'btn px-4 ' + (isDeactivate ? 'btn-danger' : 'btn-success');
            confirm.innerHTML = '<i class="bi bi-check-lg me-1"></i>Ya, ' + action;

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });

    document.getElementById('toggleUserConfirm').addEventListener('click', function() {
        document.activeElement?.blur();
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        if (targetForm) targetForm.submit();
    });
})();
</script>
@endpush
