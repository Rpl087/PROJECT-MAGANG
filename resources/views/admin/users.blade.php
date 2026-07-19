@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@push('styles')
<style>
/* ── Page header ── */
.page-header { margin-bottom: 1.75rem; }
.page-header h2 { font-size: 1.35rem; font-weight: 700; color: var(--color-text, #1e293b); margin-bottom: 4px; }
.page-header p  { font-size: 0.85rem; color: var(--color-text-muted, #64748b); }

/* ── Section card ── */
.section-card {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #e2e5ea);
    border-radius: 14px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.section-title {
    font-size: 0.95rem; font-weight: 700;
    color: var(--color-text, #1e293b);
    margin-bottom: 1.1rem;
    display: flex; align-items: center; gap: 8px;
}
.section-title .badge-count {
    background: #ef4444; color: #fff;
    border-radius: 99px; font-size: 0.72rem;
    padding: 1px 7px; font-weight: 700;
}

/* ── Table ── */
.data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.data-table th {
    padding: 10px 14px; text-align: left;
    font-size: 0.75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
    color: var(--color-text-muted, #64748b);
    border-bottom: 2px solid var(--color-border, #e2e5ea);
}
.data-table td { padding: 12px 14px; border-bottom: 1px solid var(--color-border, #e2e5ea); }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: var(--color-primary-light, #f8fafc); }

.role-badge {
    display: inline-block; padding: 3px 9px; border-radius: 99px;
    font-size: 0.73rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase;
}
.role-admin       { background: rgba(59,130,246,.12); color: #1d4ed8; }
.role-internship  { background: rgba(5,150,105,.12);  color: #047857; }

/* ── Buttons ── */
.btn-sm {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 7px; font-size: 0.8rem; font-weight: 600;
    font-family: inherit; cursor: pointer; border: 1px solid transparent;
    transition: background .2s, border-color .2s, color .2s;
}
.btn-primary { background: #3b82f6; border-color: #2563eb; color: #fff; }
.btn-primary:hover { background: #2563eb; }
.btn-success { background: rgba(5,150,105,.1); border-color: rgba(5,150,105,.35); color: #047857; }
.btn-success:hover { background: rgba(5,150,105,.18); }
.btn-muted { background: rgba(100,116,139,.09); border-color: rgba(100,116,139,.3); color: #475569; }
.btn-muted:hover { background: rgba(100,116,139,.16); }

/* ── Reset modal ── */
.modal-box {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, #e2e5ea);
    border-radius: 16px; padding: 2rem; max-width: 420px; width: 100%;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
}
.modal-box h3 { font-size: 1rem; font-weight: 700; margin-bottom: 6px; color: var(--color-text, #1e293b); }
.modal-box p  { font-size: .84rem; color: var(--color-text-muted, #64748b); margin-bottom: 1.2rem; }
.modal-form-group { margin-bottom: 1rem; }
.modal-form-group label { display: block; font-size: .78rem; font-weight: 600; margin-bottom: 5px; color: var(--color-text-muted, #64748b); text-transform: uppercase; letter-spacing: .04em; }
.modal-form-group input {
    width: 100%; padding: 10px 12px; border-radius: 8px; font-size: .9rem; font-family: inherit;
    border: 1px solid var(--color-border, #e2e5ea);
    background: var(--color-bg, #f8fafc); color: var(--color-text, #1e293b);
    outline: none; transition: border-color .2s, box-shadow .2s;
}
.modal-form-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
.modal-actions { display: flex; gap: 8px; margin-top: 1.2rem; justify-content: flex-end; }

/* ── Pending alert ── */
.pending-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.3);
    border-radius: 8px; padding: 8px 12px; font-size: .82rem; color: #dc2626;
    margin-bottom: 1rem;
}

@keyframes livePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.4; transform: scale(1.3); }
}
</style>
@endpush

@section('content')
<div class="page-header">
    <h2>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Kelola Pengguna
    </h2>
    <p>Lihat daftar pengguna, reset password, dan tindak lanjuti permintaan reset.</p>
</div>

{{-- ── Permintaan Reset Password Pending ── --}}
@if($pendingResets->count() > 0)
<div class="section-card" style="border-color:rgba(239,68,68,.3);">
    <div class="section-title" style="color:#dc2626;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Permintaan Reset Password
        <span class="badge-count">{{ $pendingResets->count() }}</span>
        {{-- Indikator realtime: dot berkedip menandakan SSE aktif --}}
        <span id="reset-live-dot" title="Notifikasi realtime aktif" style="display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:600;color:#10b981;text-transform:none;letter-spacing:0;margin-left:4px">
            <span style="width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;animation:livePulse 1.4s ease-in-out infinite"></span>
            Live
        </span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>IP Pemohon</th>
                <th>Waktu Diminta</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pendingResets as $req)
            <tr>
                <td><strong>{{ $req->username }}</strong></td>
                <td style="font-family:monospace;font-size:.82rem">{{ $req->ip_address }}</td>
                <td style="font-size:.82rem;color:var(--color-text-muted,#64748b)">{{ $req->created_at->diffForHumans() }}</td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        {{-- Cari user yang matching untuk reset langsung --}}
                        @php $matchUser = $users->firstWhere('name', $req->username); @endphp
                        @if($matchUser)
                        <button type="button" class="btn-sm btn-primary" onclick="openResetModal({{ $matchUser->id }}, '{{ $matchUser->name }}')">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                            Reset Sekarang
                        </button>
                        @endif
                        <form method="POST" action="{{ route('admin.users.resolve-reset', $req) }}" style="margin:0">
                            @csrf
                            <button type="submit" class="btn-sm btn-muted">Tandai Selesai</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ── Daftar Pengguna ── --}}
<div class="section-card">
    <div class="section-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Daftar Pengguna Aktif
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Dibuat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            <tr>
                <td><strong>{{ $user->name }}</strong></td>
                <td style="font-size:.83rem;color:var(--color-text-muted,#64748b)">{{ $user->email }}</td>
                <td>
                    <span class="role-badge role-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                </td>
                <td style="font-size:.82rem;color:var(--color-text-muted,#64748b)">{{ $user->created_at->format('d M Y') }}</td>
                <td>
                    <button type="button" class="btn-sm btn-success"
                            onclick="openResetModal({{ $user->id }}, '{{ $user->name }}')">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Reset Password
                    </button>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--color-text-muted)">Tidak ada pengguna.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Modal Reset Password ── --}}
<div id="modal-reset" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box">
        <h3>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5" style="vertical-align:middle;margin-right:6px"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Reset Password
        </h3>
        <p>Set password baru untuk pengguna <strong id="reset-username-label">-</strong>. Password lama akan langsung tidak berlaku.</p>
        <form id="form-reset-password" method="POST" action="">
            @csrf
            <div class="modal-form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" id="new_password" required minlength="8" placeholder="Minimal 8 karakter">
            </div>
            <div class="modal-form-group">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" required minlength="8" placeholder="Ulangi password baru">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-muted" onclick="closeResetModal()">Batal</button>
                <button type="submit" class="btn-sm btn-primary">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12l5 5L20 7"/></svg>
                    Simpan Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openResetModal(userId, username) {
    const modal  = document.getElementById('modal-reset');
    const form   = document.getElementById('form-reset-password');
    const label  = document.getElementById('reset-username-label');
    label.textContent = username;
    form.action = "{{ url('/admin/users') }}/" + userId + '/reset-password';
    document.getElementById('new_password').value = '';
    document.getElementById('new_password_confirmation').value = '';
    modal.classList.add('is-open');
    document.getElementById('new_password').focus();
}
function closeResetModal() {
    document.getElementById('modal-reset').classList.remove('is-open');
}
document.getElementById('modal-reset').addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeResetModal();
});
</script>
@endpush
