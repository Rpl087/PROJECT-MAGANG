@extends('layouts.app')

@section('title', 'Panel Maintenance')

@push('styles')
<style>
/* ── Page header ── */
.page-header { margin-bottom: 1.75rem; }
.page-header h2 { font-size: 1.35rem; font-weight: 700; color: var(--color-text,#1e293b); margin-bottom:4px; display:flex;align-items:center;gap:10px; }
.page-header p  { font-size: 0.85rem; color: var(--color-text-muted,#64748b); }
.maint-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.35);
    border-radius:99px; padding:3px 10px; font-size:.72rem; font-weight:700;
    color:#dc2626; letter-spacing:.04em; text-transform:uppercase;
}

/* ── Section card ── */
.section-card {
    background:var(--color-surface,#fff);
    border:1px solid var(--color-border,#e2e5ea);
    border-radius:14px; padding:1.5rem; margin-bottom:1.5rem;
    box-shadow:0 1px 4px rgba(0,0,0,.06);
}
.section-title { font-size:.95rem;font-weight:700;color:var(--color-text,#1e293b);margin-bottom:1.1rem;display:flex;align-items:center;gap:8px; }

/* ── Form add menu ── */
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
@media(max-width:600px) { .form-row { grid-template-columns:1fr; } }
.form-group { margin-bottom:1rem; }
.form-group label { display:block;font-size:.78rem;font-weight:600;margin-bottom:5px;color:var(--color-text-muted,#64748b);text-transform:uppercase;letter-spacing:.04em; }
.form-group input, .form-group select {
    width:100%;padding:10px 12px;border-radius:8px;font-size:.9rem;font-family:inherit;
    border:1px solid var(--color-border,#e2e5ea);
    background:var(--color-bg,#f8fafc);color:var(--color-text,#1e293b);
    outline:none;transition:border-color .2s,box-shadow .2s;
}
.form-group input:focus, .form-group select:focus { border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15); }
.checkbox-group { display:flex;gap:12px;flex-wrap:wrap;margin-top:4px; }
.checkbox-item { display:flex;align-items:center;gap:6px;font-size:.875rem;color:var(--color-text,#1e293b);cursor:pointer; }
.checkbox-item input[type=checkbox] { width:16px;height:16px;accent-color:#3b82f6;cursor:pointer; }

/* ── Buttons ── */
.btn-sm {
    display:inline-flex;align-items:center;gap:5px;
    padding:6px 12px;border-radius:7px;font-size:.8rem;font-weight:600;
    font-family:inherit;cursor:pointer;border:1px solid transparent;
    transition:background .2s,border-color .2s,color .2s;
    text-decoration:none;
}
.btn-primary { background:#3b82f6;border-color:#2563eb;color:#fff; }
.btn-primary:hover { background:#2563eb; }
.btn-warning { background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.4);color:#b45309; }
.btn-warning:hover { background:rgba(245,158,11,.18); }
.btn-danger  { background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.35);color:#dc2626; }
.btn-danger:hover  { background:rgba(239,68,68,.18); }
.btn-success { background:rgba(5,150,105,.1);border-color:rgba(5,150,105,.35);color:#047857; }
.btn-success:hover { background:rgba(5,150,105,.18); }
.btn-muted   { background:rgba(100,116,139,.09);border-color:rgba(100,116,139,.3);color:#475569; }
.btn-muted:hover { background:rgba(100,116,139,.16); }

/* ── Menu table ── */
.menu-table { width:100%;border-collapse:collapse;font-size:.875rem; }
.menu-table th { padding:10px 14px;text-align:left;font-size:.75rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--color-text-muted,#64748b);border-bottom:2px solid var(--color-border,#e2e5ea); }
.menu-table td { padding:12px 14px;border-bottom:1px solid var(--color-border,#e2e5ea); }
.menu-table tr:last-child td { border-bottom:none; }
.menu-table tr:hover td { background:var(--color-primary-light,#f8fafc); }
.status-dot { width:8px;height:8px;border-radius:50%;display:inline-block;margin-right:5px; }
.dot-active   { background:#10b981; }
.dot-inactive { background:#94a3b8; }

/* ── Icon preview grid ── */
.icon-grid { display:flex;flex-wrap:wrap;gap:8px;margin-top:6px; }
.icon-opt {
    width:40px;height:40px;border-radius:8px;
    border:2px solid transparent;
    background:var(--color-bg,#f8fafc);
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;transition:border-color .2s,background .2s;
    color:var(--color-text-muted,#64748b);
}
.icon-opt:hover { border-color:#3b82f6;background:rgba(59,130,246,.08); }
.icon-opt.selected { border-color:#3b82f6;background:rgba(59,130,246,.12);color:#1d4ed8; }

/* ── Modal ── */
.modal-box { background:var(--color-surface,#fff);border:1px solid var(--color-border,#e2e5ea);border-radius:16px;padding:2rem;max-width:560px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto; }
.modal-box h3 { font-size:1rem;font-weight:700;margin-bottom:1.2rem;color:var(--color-text,#1e293b); }
.modal-actions { display:flex;gap:8px;margin-top:1.2rem;justify-content:flex-end; }
</style>
@endpush

@section('content')
<div class="page-header">
    <h2>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 0 4.93 19.07M4.93 4.93A10 10 0 0 0 19.07 19.07"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2"/></svg>
        Panel Maintenance
        <span class="maint-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Superuser
        </span>
    </h2>
    <p>Kelola item navigasi sidebar secara dinamis. Perubahan akan terlihat oleh semua pengguna secara realtime.</p>
</div>

{{-- ── INFO REALTIME ── --}}
<div style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:10px;padding:12px 16px;margin-bottom:1.5rem;display:flex;align-items:center;gap:10px;font-size:.83rem;color:var(--color-text-muted,#64748b);">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
    <span><strong style="color:#1d4ed8">Realtime aktif.</strong> Setiap perubahan menu akan langsung diterima oleh semua pengguna yang sedang online tanpa perlu reload halaman.</span>
</div>

{{-- ── TAMBAH MENU ITEM ── --}}
<div class="section-card">
    <div class="section-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Menu Item Baru
    </div>
    <form method="POST" action="{{ route('maintenance.menu.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Nama Menu *</label>
                <input type="text" name="label" placeholder="cth: Laporan Bulanan" required maxlength="100" value="{{ old('label') }}">
            </div>
            <div class="form-group">
                <label>Route Name *</label>
                <input type="text" name="route_name" placeholder="cth: arsip.index" maxlength="100" value="{{ old('route_name') }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Icon</label>
                <select name="icon">
                    @foreach(['grid'=>'Dashboard/Grid','inbox'=>'Inbox/Masuk','send'=>'Send/Keluar','file-text'=>'File/Laporan','users'=>'Users/Pengguna','settings'=>'Settings/Pengaturan','bar-chart'=>'Bar Chart','folder'=>'Folder','key'=>'Key/Password','link'=>'Link External'] as $val => $lbl)
                    <option value="{{ $val }}" {{ old('icon')==$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Urutan Tampil *</label>
                <input type="number" name="sort_order" min="0" max="999" value="{{ old('sort_order', 10) }}" required>
            </div>
        </div>
        <div class="form-group">
            <label>Visible untuk Role *</label>
            <div class="checkbox-group">
                <label class="checkbox-item">
                    <input type="checkbox" name="visible_to[]" value="admin" {{ !old('visible_to') || in_array('admin', old('visible_to',[])) ? 'checked' : '' }}> Admin
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="visible_to[]" value="internship" {{ !old('visible_to') || in_array('internship', old('visible_to',[])) ? 'checked' : '' }}> Internship
                </label>
                <label class="checkbox-item">
                    <input type="checkbox" name="visible_to[]" value="maintenance" {{ !old('visible_to') || in_array('maintenance', old('visible_to',[])) ? 'checked' : '' }}> Maintenance
                </label>
            </div>
        </div>
        <div class="form-row" style="margin-bottom:.5rem">
            <div class="form-group">
                <label>Judul Konfirmasi Navigasi <span style="font-weight:400;text-transform:none">(opsional)</span></label>
                <input type="text" name="confirm_title" placeholder="cth: Buka Laporan?" maxlength="150" value="{{ old('confirm_title') }}">
            </div>
            <div class="form-group">
                <label>Deskripsi Konfirmasi <span style="font-weight:400;text-transform:none">(opsional)</span></label>
                <input type="text" name="confirm_desc" placeholder="cth: Anda akan berpindah halaman..." maxlength="300" value="{{ old('confirm_desc') }}">
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <label class="checkbox-item">
                <input type="checkbox" name="is_active" value="1" checked> Aktif langsung
            </label>
            <button type="submit" class="btn-sm btn-primary">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Menu
            </button>
        </div>
    </form>
</div>

{{-- ── DAFTAR MENU ITEMS ── --}}
<div class="section-card">
    <div class="section-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Daftar Menu Items ({{ $menuItems->count() }})
    </div>
    @if($menuItems->isEmpty())
    <p style="text-align:center;padding:2rem;color:var(--color-text-muted)">Belum ada menu item.</p>
    @else
    <div style="overflow-x:auto">
        <table class="menu-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Label</th>
                    <th>Route</th>
                    <th>Icon</th>
                    <th>Visible To</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($menuItems as $item)
                <tr>
                    <td style="color:var(--color-text-muted,#94a3b8);font-size:.82rem">{{ $item->sort_order }}</td>
                    <td><strong>{{ $item->label }}</strong></td>
                    <td style="font-family:monospace;font-size:.8rem;color:var(--color-text-muted,#64748b)">{{ $item->route_name ?? '—' }}</td>
                    <td style="font-size:.82rem;color:var(--color-text-muted,#64748b)">{{ $item->icon }}</td>
                    <td style="font-size:.78rem">
                        @foreach($item->visible_to as $role)
                        <span style="display:inline-block;padding:2px 7px;border-radius:99px;margin-right:3px;background:rgba(99,102,241,.1);color:#4f46e5;font-size:.7rem;font-weight:600;">{{ $role }}</span>
                        @endforeach
                    </td>
                    <td>
                        <span class="status-dot {{ $item->is_active ? 'dot-active' : 'dot-inactive' }}"></span>
                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            {{-- Toggle aktif/nonaktif --}}
                            <form method="POST" action="{{ route('maintenance.menu.toggle', $item) }}" id="form-toggle-{{ $item->id }}" style="margin:0">
                                @csrf @method('PATCH')
                                <button type="button" class="btn-sm {{ $item->is_active ? 'btn-muted' : 'btn-success' }}" onclick="confirmToggle({{ $item->id }}, '{{ $item->label }}', {{ $item->is_active ? 'true' : 'false' }})">
                                    {{ $item->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            {{-- Edit --}}
                            <button type="button" class="btn-sm btn-warning" onclick="openEditModal({{ $item->toJson() }})">Edit</button>
                            {{-- Hapus --}}
                            <form method="POST" action="{{ route('maintenance.menu.destroy', $item) }}" id="form-delete-{{ $item->id }}" style="margin:0">
                                @csrf @method('DELETE')
                                <button type="button" class="btn-sm btn-danger" onclick="confirmDelete({{ $item->id }}, '{{ $item->label }}')">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ── Modal Edit Menu ── --}}
<div id="modal-edit" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box">
        <h3>Edit Menu Item</h3>
        <form id="form-edit-menu" method="POST" action="">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Menu *</label>
                    <input type="text" name="label" id="edit-label" required maxlength="100">
                </div>
                <div class="form-group">
                    <label>Route Name <span style="font-size:0.75rem;color:var(--color-text-muted,#94a3b8);">(Terkunci)</span></label>
                    <input type="text" name="route_name" id="edit-route" maxlength="100" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:0.85;">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Icon</label>
                    <select name="icon" id="edit-icon">
                        @foreach(['grid'=>'Dashboard/Grid','inbox'=>'Inbox/Masuk','send'=>'Send/Keluar','file-text'=>'File/Laporan','users'=>'Users/Pengguna','settings'=>'Settings/Pengaturan','bar-chart'=>'Bar Chart','folder'=>'Folder','key'=>'Key/Password','link'=>'Link External'] as $val => $lbl)
                        <option value="{{ $val }}">{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Urutan</label>
                    <input type="number" name="sort_order" id="edit-order" min="0" max="999" required>
                </div>
            </div>
            <div class="form-group">
                <label>Visible untuk Role</label>
                <div class="checkbox-group">
                    <label class="checkbox-item"><input type="checkbox" id="edit-role-admin" name="visible_to[]" value="admin"> Admin</label>
                    <label class="checkbox-item"><input type="checkbox" id="edit-role-internship" name="visible_to[]" value="internship"> Internship</label>
                    <label class="checkbox-item"><input type="checkbox" id="edit-role-maintenance" name="visible_to[]" value="maintenance"> Maintenance</label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Judul Konfirmasi</label>
                    <input type="text" name="confirm_title" id="edit-confirm-title" maxlength="150">
                </div>
                <div class="form-group">
                    <label>Deskripsi Konfirmasi</label>
                    <input type="text" name="confirm_desc" id="edit-confirm-desc" maxlength="300">
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-item" style="gap:8px">
                    <input type="checkbox" name="is_active" id="edit-active" value="1"> Aktif
                </label>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-sm btn-muted" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn-sm btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Konfirmasi Aksi ── --}}
<div id="modal-action-confirm" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:400px; text-align:center; padding: 2.25rem 2rem 1.75rem;">
        <div class="modal-icon" style="width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.25rem; transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);">
            <!-- Icon will be set dynamically via Javascript -->
        </div>
        <h3 id="confirm-title" style="font-size:1.0625rem; font-weight:700; margin-bottom:8px; color:var(--color-text,#1e293b);">Konfirmasi Aksi</h3>
        <p id="confirm-desc" style="font-size:0.84rem; color:var(--color-text-muted,#64748b); margin-bottom:1.5rem; line-height:1.6;"></p>
        <div class="modal-actions" style="justify-content:center; gap: 8px;">
            <button type="button" class="btn-sm btn-muted" id="confirm-btn-cancel">Batal</button>
            <button type="button" class="btn-sm" id="confirm-btn-submit">Ya, Lanjutkan</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let activeConfirmForm = null;

function openEditModal(item) {
    const modal = document.getElementById('modal-edit');
    const form  = document.getElementById('form-edit-menu');
    form.action = "{{ url('/maintenance/menu') }}/" + item.id;
    document.getElementById('edit-label').value = item.label || '';
    document.getElementById('edit-route').value = item.route_name || '';
    document.getElementById('edit-icon').value  = item.icon || 'file-text';
    document.getElementById('edit-order').value = item.sort_order || 0;
    document.getElementById('edit-confirm-title').value = item.confirm_title || '';
    document.getElementById('edit-confirm-desc').value  = item.confirm_desc || '';
    document.getElementById('edit-active').checked = !!item.is_active;
    const roles = item.visible_to || [];
    document.getElementById('edit-role-admin').checked       = roles.includes('admin');
    document.getElementById('edit-role-internship').checked  = roles.includes('internship');
    document.getElementById('edit-role-maintenance').checked = roles.includes('maintenance');
    modal.classList.add('is-open');
}

function closeEditModal() {
    document.getElementById('modal-edit').classList.remove('is-open');
}

function showActionConfirm(title, desc, btnText, btnClass, iconHtml, formElement) {
    const modal = document.getElementById('modal-action-confirm');
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-desc').innerHTML = desc;
    
    const submitBtn = document.getElementById('confirm-btn-submit');
    submitBtn.textContent = btnText;
    submitBtn.className = 'btn-sm ' + btnClass;
    
    const iconContainer = modal.querySelector('.modal-icon');
    iconContainer.innerHTML = iconHtml;
    
    if (btnClass.includes('btn-danger')) {
        iconContainer.style.background = 'rgba(239,68,68,0.12)';
        iconContainer.style.color = '#ef4444';
        iconContainer.style.border = '1px solid rgba(239,68,68,0.25)';
    } else if (btnClass.includes('btn-warning')) {
        iconContainer.style.background = 'rgba(245,158,11,0.12)';
        iconContainer.style.color = '#d97706';
        iconContainer.style.border = '1px solid rgba(245,158,11,0.25)';
    } else {
        iconContainer.style.background = 'rgba(5,150,105,0.12)';
        iconContainer.style.color = '#047857';
        iconContainer.style.border = '1px solid rgba(5,150,105,0.25)';
    }
    
    activeConfirmForm = formElement;
    modal.classList.add('is-open');
}

function confirmDelete(id, label) {
    const desc = 'Apakah Anda yakin ingin menghapus menu <strong>' + label + '</strong>? Tindakan ini tidak dapat dibatalkan.';
    const icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    const form = document.getElementById('form-delete-' + id);
    showActionConfirm('Hapus Menu', desc, 'Ya, Hapus', 'btn-danger', icon, form);
}

function confirmToggle(id, label, isActive) {
    const statusText = isActive ? 'menonaktifkan' : 'mengaktifkan';
    const desc = 'Apakah Anda yakin ingin <strong>' + statusText + '</strong> menu <strong>' + label + '</strong>?';
    const icon = isActive 
        ? '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
        : '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    const form = document.getElementById('form-toggle-' + id);
    const btnClass = isActive ? 'btn-warning' : 'btn-success';
    showActionConfirm('Ubah Status Menu', desc, isActive ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan', btnClass, icon, form);
}

document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('modal-edit');
    if (editModal) {
        document.body.appendChild(editModal);
    }
    
    const confirmModal = document.getElementById('modal-action-confirm');
    if (confirmModal) {
        document.body.appendChild(confirmModal);
        
        // Bind click events inside confirm modal
        document.getElementById('confirm-btn-cancel').addEventListener('click', function() {
            confirmModal.classList.remove('is-open');
        });
        document.getElementById('confirm-btn-submit').addEventListener('click', function() {
            if (activeConfirmForm) {
                activeConfirmForm.submit();
            }
        });
        confirmModal.addEventListener('click', function(e) {
            if (e.target === this) {
                confirmModal.classList.remove('is-open');
            }
        });
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
        const confirmModal = document.getElementById('modal-action-confirm');
        if (confirmModal) {
            confirmModal.classList.remove('is-open');
        }
    }
});
</script>
@endpush
