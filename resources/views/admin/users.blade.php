@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@push('styles')
<style>

.page-header { margin-bottom: 1.75rem; }
.page-header h2 { font-size: 1.35rem; font-weight: 700; color: var(--color-text, #1e293b); margin-bottom: 4px; }
.page-header p  { font-size: 0.85rem; color: var(--color-text-muted, #64748b); }

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

#modal-reset.modal-overlay {
    background: rgba(15, 23, 42, 0.50);
    backdrop-filter: blur(10px) saturate(130%);
    -webkit-backdrop-filter: blur(10px) saturate(130%);
}
[data-theme="dark"] #modal-reset.modal-overlay {
    background: rgba(5, 8, 18, 0.72);
    backdrop-filter: blur(12px) saturate(120%);
    -webkit-backdrop-filter: blur(12px) saturate(120%);
}

#modal-reset .modal-box {
    background: var(--color-surface, #ffffff);
    border: 1px solid var(--color-border, #e2e5ea);
    border-radius: 1.125rem;
    padding: 2.25rem 2rem 1.75rem;
    max-width: 420px;
    width: 90%;
    text-align: center;
    box-shadow:
        0 0 0 1px rgba(37,99,235,.07),
        0 24px 64px rgba(0,0,0,.18),
        0 8px 20px rgba(0,0,0,.10);
    position: relative;
    overflow: hidden;
}
[data-theme="dark"] #modal-reset .modal-box {
    background: var(--color-surface, #1a1d27);
    border-color: var(--color-border, #2d3148);
    box-shadow:
        0 0 0 1px rgba(99,102,241,.12),
        0 24px 64px rgba(0,0,0,.55),
        0 8px 24px rgba(0,0,0,.35);
}

#modal-reset .modal-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 50%, #6366f1 100%);
    border-radius: 1.125rem 1.125rem 0 0;
}

#modal-reset .modal-icon-reset {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: rgba(37, 99, 235, .1);
    border: 1.5px solid rgba(37, 99, 235, .2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    color: #2563eb;
    transition: transform .3s cubic-bezier(.34,1.56,.64,1), background .2s;
}
#modal-reset .modal-box:hover .modal-icon-reset {
    transform: scale(1.1);
    background: rgba(37,99,235,.15);
}
[data-theme="dark"] #modal-reset .modal-icon-reset {
    background: rgba(99, 102, 241, .12);
    border-color: rgba(99, 102, 241, .25);
    color: #818cf8;
}

#modal-reset .modal-title-reset {
    font-size: 1.0625rem;
    font-weight: 700;
    color: var(--color-text, #1e293b);
    margin: 0 0 .5rem;
    letter-spacing: -.01em;
    text-align: center;
}
#modal-reset .modal-desc-reset {
    font-size: .875rem;
    color: var(--color-text-secondary, #64748b);
    margin: 0 0 1.5rem;
    line-height: 1.65;
    text-align: center;
}
#modal-reset .modal-desc-reset strong {
    color: var(--color-text, #1e293b);
    font-weight: 600;
}

#modal-reset .modal-form-group {
    margin-bottom: 1.1rem;
    text-align: left;
}
#modal-reset .modal-form-group label {
    display: block;
    font-size: .78rem;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--color-text-secondary, #64748b);
    text-transform: uppercase;
    letter-spacing: .04em;
}
#modal-reset .modal-form-group input {
    width: 100%;
    padding: .65rem .85rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-family: inherit;
    border: 1px solid var(--color-border, #e2e5ea);
    background: var(--color-surface, #fff);
    color: var(--color-text, #1e293b);
    outline: none;
    transition: border-color .2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow .2s cubic-bezier(0.4, 0, 0.2, 1);
    box-sizing: border-box;
}
#modal-reset .modal-form-group input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3.5px rgba(37,99,235,.18);
}

#modal-reset .modal-actions-reset {
    display: flex;
    gap: .75rem;
    justify-content: center;
    margin-top: 1.5rem;
}

#modal-reset-cancel {
    padding: .58rem 1.4rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all .18s ease;
    background: transparent;
    color: var(--color-text-muted, #94a3b8);
    border: 1.5px solid var(--color-border, #e2e5ea);
}
#modal-reset-cancel:hover {
    background: var(--color-primary-light, #e8edf4);
    color: var(--color-text, #1e293b);
    border-color: var(--color-border-focus, #94a3b8);
}
[data-theme="dark"] #modal-reset-cancel:hover {
    color: var(--color-text, #e2e8f0);
}

#modal-reset-confirm {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .58rem 1.4rem;
    border-radius: .5rem;
    font-size: .875rem;
    font-weight: 600;
    font-family: inherit;
    cursor: pointer;
    transition: all .18s cubic-bezier(.4,0,.2,1);
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: #fff;
    border: none;
    box-shadow: 0 3px 12px rgba(37,99,235,.3);
}
#modal-reset-confirm:hover {
    opacity: .92;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(37,99,235,.45);
}
#modal-reset-confirm:active { transform: translateY(0); }

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
    <p>Lihat daftar pengguna dan tindak lanjuti permintaan reset password.</p>
</div>

@if($pendingResets->count() > 0)
<div class="section-card" style="border-color:rgba(220, 38, 38, 0.7);box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);">
    <div class="section-title" style="color:#dc2626;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Permintaan Reset Password
        <span class="badge-count">{{ $pendingResets->count() }}</span>

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

                        @php
$matchUser = $users->firstWhere('name', $req->username);
@endphp
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
                <th>Akses Terakhir</th>
                <th>Dibuat</th>
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
                <td style="font-size:.82rem;color:var(--color-text-muted,#64748b)">
                    @if($user->last_seen_at)
                        <span title="{{ $user->last_seen_at->format('d M Y H:i:s') }}">
                            {{ $user->last_seen_at->diffForHumans() }}
                        </span>
                    @else
                        <span style="color:#94a3b8;font-style:italic">Belum pernah</span>
                    @endif
                </td>
                <td style="font-size:.82rem;color:var(--color-text-muted,#64748b)">{{ $user->created_at->format('d M Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--color-text-muted)">Tidak ada pengguna.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="modal-reset" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon-reset">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <h4 class="modal-title-reset">Reset Password</h4>
        <p class="modal-desc-reset">
            Set password baru untuk pengguna <strong id="reset-username-label">-</strong>.<br>
            Password lama akan langsung tidak berlaku.
        </p>
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
            <div class="modal-actions-reset">
                <button type="button" id="modal-reset-cancel" onclick="closeResetModal()">Batal</button>
                <button type="submit" id="modal-reset-confirm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12l5 5L20 7"/>
                    </svg>
                    Simpan Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-reset');
    if (modal) document.body.appendChild(modal);
});

function openResetModal(userId, username) {
    const modal  = document.getElementById('modal-reset');
    const form   = document.getElementById('form-reset-password');
    const label  = document.getElementById('reset-username-label');
    label.textContent = username;
    form.action = "{{ url('/admin/users') }}/" + userId + '/reset-password';
    document.getElementById('new_password').value = '';
    document.getElementById('new_password_confirmation').value = '';
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
        document.getElementById('new_password').focus();
    }, 100);
}
function closeResetModal() {
    const modal = document.getElementById('modal-reset');
    if (modal) modal.classList.remove('is-open');
    document.body.style.overflow = '';
}
document.getElementById('modal-reset').addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeResetModal();
});
</script>
@endpush
