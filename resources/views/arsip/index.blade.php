@extends('layouts.app')

@section('title', 'Input Surat Masuk')

@section('content')
<div class="page-header">
    <h2 class="page-title">Input Surat Masuk</h2>
    <p class="page-description">Lengkapi formulir di bawah untuk mengarsipkan surat ke dalam sistem.</p>
</div>


<form action="{{ route('arsip.store') }}" method="POST" enctype="multipart/form-data" class="form-container" id="arsip-form">
    @csrf

    {{-- Section 1: Informasi Utama --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">01</div>
            <div>
                <h3 class="section-title">Informasi Utama</h3>
                <p class="section-desc">Data pokok surat yang akan diarsipkan</p>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="kode_klasifikasi" class="form-label">Kode Klasifikasi <span class="required">*</span></label>
                <input type="text" name="kode_klasifikasi" id="kode_klasifikasi" class="form-input @error('kode_klasifikasi') input-error @enderror" value="{{ old('kode_klasifikasi') }}" placeholder="Contoh: KP.01" required>
                @error('kode_klasifikasi')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="kode_surat" class="form-label">Kode Surat <span class="required">*</span></label>
                <input type="text" name="kode_surat" id="kode_surat" class="form-input @error('kode_surat') input-error @enderror" value="{{ old('kode_surat') }}" placeholder='Contoh: 01.01' required>
                @error('kode_surat')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="required">*</span></label>
                <div class="date-input-wrap">
                    <input type="text" name="tanggal_surat" id="tanggal_surat"
                           class="form-input flatpickr-input @error('tanggal_surat') input-error @enderror"
                           value="{{ old('tanggal_surat') }}"
                           placeholder="Contoh: 11 Mei 2026"
                           autocomplete="off" required readonly>
                    <span class="date-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </span>
                </div>
                @error('tanggal_surat')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="uraian_informasi_berkas" class="form-label">Uraian Informasi Berkas <span class="required">*</span></label>
                <textarea name="uraian_informasi_berkas" id="uraian_informasi_berkas" class="form-input form-textarea @error('uraian_informasi_berkas') input-error @enderror" placeholder="Contoh: Surat perihal pengajuan anggaran kegiatan pelatihan SDM triwulan I" rows="3" required>{{ old('uraian_informasi_berkas') }}</textarea>
                @error('uraian_informasi_berkas')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="uraian_informasi_arsip" class="form-label">Uraian Informasi Arsip <span class="required">*</span></label>
                <textarea name="uraian_informasi_arsip" id="uraian_informasi_arsip" class="form-input form-textarea @error('uraian_informasi_arsip') input-error @enderror" placeholder="Contoh: Surat masuk dari Kementerian KOMINFO tentang pelaksanaan ujian sertifikasi" rows="3" required>{{ old('uraian_informasi_arsip') }}</textarea>
                @error('uraian_informasi_arsip')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section 2: Detail Arsip --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">02</div>
            <div>
                <h3 class="section-title">Detail Arsip</h3>
                <p class="section-desc">Informasi detail mengenai berkas arsip</p>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="kurun_waktu" class="form-label">Kurun Waktu <span class="required">*</span></label>
                <input type="text" name="kurun_waktu" id="kurun_waktu" class="form-input @error('kurun_waktu') input-error @enderror" value="{{ old('kurun_waktu') }}" placeholder="Contoh: 2023–2025" required>
                @error('kurun_waktu')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="jumlah_folder" class="form-label">Jumlah Folder <span class="required">*</span></label>
                <input type="text" name="jumlah_folder" id="jumlah_folder" class="form-input @error('jumlah_folder') input-error @enderror" value="{{ old('jumlah_folder') }}" placeholder="Contoh: 3 folder" required>
                @error('jumlah_folder')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="nomor_item_arsip" class="form-label">Nomor: Item Arsip <span class="required">*</span></label>
                <input type="number" name="nomor_item_arsip" id="nomor_item_arsip" class="form-input @error('nomor_item_arsip') input-error @enderror" value="{{ old('nomor_item_arsip') }}" placeholder="Contoh: 1" min="1" required>
                @error('nomor_item_arsip')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="jumlah_lembar" class="form-label">Jumlah Lembar <span class="required">*</span></label>
                <input type="text" name="jumlah_lembar" id="jumlah_lembar" class="form-input @error('jumlah_lembar') input-error @enderror" value="{{ old('jumlah_lembar') }}" placeholder="Contoh: 5 Lembar" required>
                @error('jumlah_lembar')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="tingkat_perkembangan" class="form-label">Tingkat Perkembangan <span class="required">*</span></label>
                <input type="text" name="tingkat_perkembangan" id="tingkat_perkembangan" class="form-input @error('tingkat_perkembangan') input-error @enderror" value="{{ old('tingkat_perkembangan') }}" placeholder="Contoh: Asli / Fotokopi / Salinan" required>
                @error('tingkat_perkembangan')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group form-group-upload">
                <label for="surat_masuk" class="form-label">Surat Masuk (PDF) <span class="required">*</span></label>
                <div class="file-upload @error('surat_masuk') file-upload-error @enderror" id="upload-zone">
                    <input type="file" name="surat_masuk" id="surat_masuk" class="file-upload-input" accept="application/pdf" required style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;">
                    <div class="file-upload-placeholder" id="upload-placeholder">
                        <div class="file-upload-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <path d="M12 18v-6"/>
                                <path d="M9 15l3-3 3 3"/>
                            </svg>
                        </div>
                        <p class="file-upload-text">Klik atau seret file ke sini</p>
                        <p class="file-upload-hint">Hanya file <strong>PDF</strong> &middot; Maks. 10 MB</p>
                    </div>
                    <div class="file-upload-preview" id="upload-preview" style="display:none">
                        <div class="file-preview-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div class="file-preview-info">
                            <span class="file-preview-name" id="file-name"></span>
                            <span class="file-preview-size" id="file-size"></span>
                        </div>
                        <button type="button" class="file-preview-remove" id="file-remove" title="Hapus file">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @error('surat_masuk')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section 3: Keamanan & Retensi --}}
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">03</div>
            <div>
                <h3 class="section-title">Keamanan & Retensi</h3>
                <p class="section-desc">Pengaturan akses, lokasi penyimpanan, dan masa retensi</p>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="lokasi_simpan" class="form-label">Lokasi Simpan <span class="required">*</span></label>
                <input type="text" name="lokasi_simpan" id="lokasi_simpan" class="form-input @error('lokasi_simpan') input-error @enderror" value="{{ old('lokasi_simpan') }}" placeholder="Contoh: OneDrive" required>
                @error('lokasi_simpan')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="klasifikasi_keamanan" class="form-label">Klasifikasi Keamanan <span class="required">*</span></label>
                <select name="klasifikasi_keamanan" id="klasifikasi_keamanan" class="form-input form-select @error('klasifikasi_keamanan') input-error @enderror" required>
                    <option value="" disabled {{ old('klasifikasi_keamanan') ? '' : 'selected' }}>— Pilih —</option>
                    <option value="Biasa" {{ old('klasifikasi_keamanan') == 'Biasa' ? 'selected' : '' }}>Biasa</option>
                    <option value="Terbatas" {{ old('klasifikasi_keamanan') == 'Terbatas' ? 'selected' : '' }}>Terbatas</option>
                    <option value="Rahasia" {{ old('klasifikasi_keamanan') == 'Rahasia' ? 'selected' : '' }}>Rahasia</option>
                    <option value="Sangat Rahasia" {{ old('klasifikasi_keamanan') == 'Sangat Rahasia' ? 'selected' : '' }}>Sangat Rahasia</option>
                </select>
                @error('klasifikasi_keamanan')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="tingkat_akses" class="form-label">Tingkat Akses <span class="required">*</span></label>
                <select name="tingkat_akses" id="tingkat_akses" class="form-input form-select @error('tingkat_akses') input-error @enderror" required>
                    <option value="" disabled {{ old('tingkat_akses') ? '' : 'selected' }}>— Pilih —</option>
                    <option value="Terbuka" {{ old('tingkat_akses') == 'Terbuka' ? 'selected' : '' }}>Terbuka</option>
                    <option value="Tertutup" {{ old('tingkat_akses') == 'Tertutup' ? 'selected' : '' }}>Tertutup</option>
                    <option value="Terbatas" {{ old('tingkat_akses') == 'Terbatas' ? 'selected' : '' }}>Terbatas</option>
                </select>
                @error('tingkat_akses')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="retensi_aktif" class="form-label">Retensi — Aktif <span class="required">*</span></label>
                <input type="text" name="retensi_aktif" id="retensi_aktif" class="form-input @error('retensi_aktif') input-error @enderror" value="{{ old('retensi_aktif') }}" placeholder="Contoh: 2 Tahun" required>
                @error('retensi_aktif')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="retensi_inaktif" class="form-label">Retensi — Inaktif <span class="required">*</span></label>
                <input type="text" name="retensi_inaktif" id="retensi_inaktif" class="form-input @error('retensi_inaktif') input-error @enderror" value="{{ old('retensi_inaktif') }}" placeholder="Contoh: 5 Tahun" required>
                @error('retensi_inaktif')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="retensi_keterangan" class="form-label">Retensi — Keterangan <span class="required">*</span></label>
                <input type="text" name="retensi_keterangan" id="retensi_keterangan" class="form-input @error('retensi_keterangan') input-error @enderror" value="{{ old('retensi_keterangan') }}" placeholder="Contoh: Musnah / Permanen" required>
                @error('retensi_keterangan')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    {{-- Submit Button --}}
    <div class="form-actions">
        <button type="button" class="btn btn-secondary" id="btn-reset">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
            </svg>
            Reset
        </button>
        <button type="submit" class="btn btn-primary" id="btn-submit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>
            Simpan ke Arsip
        </button>
    </div>
</form>
@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.date-input-wrap { position: relative; }
.date-input-wrap .form-input { padding-right: 2.5rem; cursor: pointer; }
.date-icon {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    color: var(--color-text-muted); pointer-events: none; display: flex;
}
.flatpickr-calendar { font-family: var(--font-family) !important; border-radius: var(--radius-lg) !important; box-shadow: var(--shadow-lg) !important; border: 1px solid var(--color-border) !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: var(--color-primary) !important; border-color: var(--color-primary) !important; }
.flatpickr-day:hover { background: var(--color-primary-light) !important; color: var(--color-primary) !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── File Upload ──────────────────────────────────────────────
    const zone        = document.getElementById('upload-zone');
    const input       = document.getElementById('surat_masuk');
    const placeholder = document.getElementById('upload-placeholder');
    const preview     = document.getElementById('upload-preview');
    const fileName    = document.getElementById('file-name');
    const fileSize    = document.getElementById('file-size');
    const removeBtn   = document.getElementById('file-remove');

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(2) + ' MB';
    }

    function showPreview(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatBytes(file.size);
        placeholder.style.display = 'none';
        preview.style.display     = 'flex';
        zone.classList.add('file-upload-has-file');
        zone.classList.remove('file-upload-error');
    }

    function clearFile() {
        input.value           = '';
        preview.style.display = 'none';
        placeholder.style.display = '';
        zone.classList.remove('file-upload-has-file');
    }

    // Click zona → buka file picker (preventDefault mencegah double-trigger)
    zone.addEventListener('click', function (e) {
        if (e.target.closest('#file-remove')) return;
        e.preventDefault();
        e.stopPropagation();
        input.click();
    });

    // Blokir bubbling dari input ke zone agar tidak double-trigger
    input.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // Pilih file via input
    input.addEventListener('change', function () {
        if (this.files.length > 0) showPreview(this.files[0]);
    });

    // Tombol hapus file
    removeBtn.addEventListener('click', clearFile);

    // Drag & drop
    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.add('file-upload-drag');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.remove('file-upload-drag');
        });
    });

    zone.addEventListener('drop', function (e) {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        if (file.type !== 'application/pdf') {
            zone.classList.add('file-upload-error');
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    });

    // ── Reset form (termasuk file) ───────────────────────────────
    document.getElementById('btn-reset').addEventListener('click', function () {
        document.getElementById('arsip-form').reset();
        clearFile();
        if (window._fpSM) window._fpSM.clear();
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var BULAN_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_ID_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    window._fpSM = flatpickr('#tanggal_surat', {
        dateFormat: 'j F Y',
        locale: {
            months: { longhand: BULAN_ID, shorthand: BULAN_ID_SHORT },
            weekdays: {
                longhand: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
                shorthand: ['Min','Sen','Sel','Rab','Kam','Jum','Sab']
            },
            firstDayOfWeek: 1
        },
        allowInput: false,
        disableMobile: true,
    });
});
</script>
@endpush
@endsection
