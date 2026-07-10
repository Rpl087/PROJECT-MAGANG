@extends('layouts.app')

@section('title', 'Surat Masuk')

@section('content')
<div class="page-header pre-hide" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 class="page-title">Surat Masuk</h2>
        @if($isAdmin ?? false)
            <p class="page-description">Lihat daftar arsip tersimpan dan tambah surat masuk baru ke dalam sistem.</p>
        @else
            <p class="page-description">Gunakan formulir di bawah untuk mengarsipkan surat masuk ke dalam sistem.</p>
        @endif
    </div>
    {{-- Live date/time --}}
    <div class="live-date-badge" id="live-date-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span id="live-clock-sm">--:--:--</span>
        <span class="live-date-divider">|</span>
        <span id="live-date-text-sm"></span>
    </div>
</div>

{{-- ── TABEL DAFTAR SURAT MASUK (hanya tampil untuk admin) ────────────────────── --}}
@if($isAdmin ?? false)
<div class="form-container pre-hide" style="margin-bottom:2rem;">
    <div class="form-section" style="margin-bottom:0;">
        <div class="section-header">
            <div class="section-number">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            </div>
            <div>
                <h3 class="section-title">Daftar Surat Masuk Tersimpan</h3>
                <p class="section-desc">Data diambil langsung dari Google Spreadsheet (sheet: SURAT MASUK)</p>
            </div>
        </div>

        @if(isset($fetchError))
            <div class="alert alert-error" style="margin:1rem 0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $fetchError }}
            </div>
        @endif

        {{-- ── Filter Bulan, Tahun & Kode Klasifikasi ── --}}
        <div class="table-filter-bar" id="filter-bar-sm">
            <div class="filter-group">
                <label for="filter-bulan-sm" class="filter-label">Filter Bulan:</label>
                <select id="filter-bulan-sm" class="filter-select">
                    <option value="">Semua Bulan</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-tahun-sm" class="filter-label">Tahun:</label>
                <select id="filter-tahun-sm" class="filter-select">
                    <option value="">Semua Tahun</option>
                    @php
                        $tahunList = [];
                        foreach($rows as $item) {
                            $t = trim($item['data'][7] ?? '');
                            if(preg_match('/(\d{4})/', $t, $m)) $tahunList[$m[1]] = true;
                        }
                        krsort($tahunList);
                    @endphp
                    @foreach($tahunList as $thn => $_)
                    <option value="{{ $thn }}">{{ $thn }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-kode-sm" class="filter-label">Kode Klasifikasi:</label>
                <select id="filter-kode-sm" class="filter-select">
                    <option value="">Semua Kode</option>
                    @php
                        $kodeList = [];
                        foreach($rows as $item) {
                            $k = trim($item['data'][1] ?? '');
                            if ($k !== '' && !str_starts_with($k, '#')) {
                                $kp = preg_match('/^([A-Za-z]+)/', $k, $km) ? strtoupper($km[1]) : strtoupper($k);
                                $kodeList[$kp] = true;
                            }
                        }
                        ksort($kodeList);
                    @endphp
                    @foreach($kodeList as $kd => $_)
                    <option value="{{ $kd }}">{{ $kd }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="filter-reset" id="filter-reset-sm">Reset Filter</button>
            <div class="filter-count" id="filter-count-sm"></div>
        </div>

        {{-- ── Skeleton Loader (tampil 1 tick sebelum tabel asli muncul) ── --}}
        <div id="table-skeleton-sm" class="skeleton-table-wrapper" aria-hidden="true">
            <div class="skeleton-header">
                <div class="skeleton-header-cell"></div>
                <div class="skeleton-header-cell"></div>
                <div class="skeleton-header-cell"></div>
                <div class="skeleton-header-cell"></div>
                <div class="skeleton-header-cell"></div>
                <div class="skeleton-header-cell"></div>
            </div>
            @for($i = 0; $i < 5; $i++)
            <div class="skeleton-row">
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
            </div>
            @endfor
        </div>

        {{-- ── Tabel Sesungguhnya (hidden sampai skeleton diremove) ── --}}
        <div id="table-content-sm" style="overflow-x:auto; margin-top:.5rem; display:none;">
            <table class="arsip-table" id="arsip-table-sm">
                <thead>
                    <tr>
                        <th class="th-sortable" data-col="no" style="cursor:default;">No</th>
                        <th class="th-sortable" data-col="tanggal">Tanggal Surat <span class="sort-icon">↕</span></th>
                        <th class="th-sortable" data-col="kode" id="th-kode-sm">Kode Klasifikasi <span class="sort-icon">↕</span></th>
                        <th>Uraian Berkas</th>
                        <th>Link Folder</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-sm">
                    @forelse($rows as $no => $item)
                        @php
                            $d           = $item['data'];
                            // Indeks kolom sesuai struktur SURAT MASUK (A–Q):
                            // A(0)=NO | B(1)=Kode Klas | C(2)=Uraian Berkas | D(3)=Kurun Waktu
                            // E(4)=Jumlah Folder | F(5)=Nomor Item | G(6)=Uraian Arsip
                            // H(7)=Tanggal Surat | I(8)=Jumlah Lembar | ... | Q(16)=Link Folder
                            $tanggal      = $d[7]  ?? '-';  // H: Tanggal Surat
                            $kodeKlasRaw  = $d[1]  ?? '';   // B: Kode Klasifikasi
                            $kodeKlas     = (str_starts_with($kodeKlasRaw, '#') || $kodeKlasRaw === '') ? '—' : $kodeKlasRaw;
                            $kodePrefix   = preg_match('/^([A-Za-z]+)/', $kodeKlasRaw, $km) ? strtoupper($km[1]) : '';
                            $uraian       = $d[2]  ?? '-';  // C: Uraian Berkas
                            $rawLink      = trim($d[16] ?? '');  // Q: Link Folder (filename atau URL lama)
                            // Generate URL dinamis jika berupa filename; gunakan apa adanya jika sudah URL
                            $pdfUrl = '';
                            if ($rawLink !== '') {
                                $pdfUrl = str_starts_with($rawLink, 'http')
                                    ? $rawLink  // URL lama (legacy) — tetap dipakai
                                    : route('pdf.view', ['type' => 'surat-masuk', 'filename' => $rawLink]);
                            }
                            // Parse bulan & tahun untuk filter
                            $bulanNum = '';
                            $tahunNum = '';
                            if (preg_match('/(\d{1,2})\s+\w+\s+(\d{4})/', $tanggal, $dm)) {
                                $bulanIndo = ['januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12,'january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12];
                                preg_match('/\d{1,2}\s+(\w+)\s+\d{4}/i', $tanggal, $bm);
                                $bulanStr = strtolower($bm[1] ?? '');
                                $bulanNum = $bulanIndo[$bulanStr] ?? '';
                                $tahunNum = $dm[2];
                            } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $tanggal, $dm)) {
                                $tahunNum = $dm[1]; $bulanNum = (int)$dm[2];
                            } elseif (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $tanggal, $dm)) {
                                $bulanNum = (int)$dm[2]; $tahunNum = $dm[3];
                            }
                        @endphp
                        <tr data-bulan="{{ $bulanNum }}" data-tahun="{{ $tahunNum }}" data-kode="{{ $kodePrefix }}">
                            <td class="td-center td-no row-no">{{ $no + 1 }}</td>
                            <td class="td-nowrap">{{ $tanggal }}</td>
                            <td><span class="badge-kode {{ $kodeKlas === '—' ? 'badge-kode-empty' : '' }}">{{ $kodeKlas }}</span></td>
                            <td class="td-uraian">{{ Str::limit($uraian, 60) }}</td>
                            <td class="td-center">
                                @if($pdfUrl)
                                    <a href="{{ $pdfUrl }}" target="_blank" class="btn-pdf" title="Lihat PDF">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        PDF
                                    </a>
                                @else
                                    <span class="td-muted">—</span>
                                @endif
                            </td>
                            <td class="td-center" style="white-space:nowrap;">
                                <button type="button" class="btn-edit-sm"
                                    data-index="{{ $item['index'] }}"
                                    data-label="surat masuk baris ke-{{ $no + 1 }}"
                                    title="Edit data ini">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    Edit
                                </button>
                                <button type="button" class="btn-hapus"
                                    data-form="delete-sm-{{ $item['index'] }}"
                                    data-label="surat masuk baris ke-{{ $no + 1 }}"
                                    title="Hapus data ini">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    Hapus
                                </button>
                                <form id="delete-sm-{{ $item['index'] }}"
                                      action="{{ route('arsip.destroy', $item['index']) }}"
                                      method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="td-empty">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>Belum ada data surat masuk tersimpan.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>{{-- /#table-content-sm --}}
    </div>
</div>
@endif

{{-- Modal Konfirmasi Hapus --}}
<div id="modal-hapus" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
        </div>
        <h4 class="modal-title">Konfirmasi Hapus</h4>
        <p class="modal-desc">Apakah Anda yakin ingin menghapus <strong id="modal-label"></strong>? Tindakan ini tidak dapat dibatalkan dan akan menghapus data dari Google Sheets.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="modal-cancel">Batal</button>
            <button type="button" class="btn btn-danger" id="modal-confirm">Ya, Hapus</button>
        </div>
    </div>
</div>

{{-- Modal Edit Surat Masuk --}}
<div id="modal-edit-sm" class="modal-overlay" style="display:none;">
    <div class="modal-box" style="max-width:640px;width:95%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;">
            <div class="modal-icon" style="background:rgba(59,130,246,.12);color:#3b82f6;flex-shrink:0;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </div>
            <div>
                <h4 class="modal-title" style="margin:0;">Edit Surat Masuk</h4>
                <p style="font-size:.8rem;color:var(--color-text-muted);margin:0;">Perubahan akan disimpan ke Google Sheets (dbSM)</p>
            </div>
            <button type="button" id="modal-edit-sm-close" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--color-text-muted);font-size:1.3rem;line-height:1;">&times;</button>
        </div>
        <div id="modal-edit-sm-loading" style="text-align:center;padding:2rem;display:none;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite;"><line x1="12" y1="2" x2="12" y2="6"/><line x1="12" y1="18" x2="12" y2="22"/><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"/><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"/><line x1="2" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="22" y2="12"/></svg>
            <p style="margin-top:.5rem;color:var(--color-text-muted);font-size:.85rem;">Memuat data...</p>
        </div>
        <form id="form-edit-sm" method="POST" style="display:none;">
            @csrf
            @method('PUT')
            <input type="hidden" name="dbsm_index" id="edit-sm-dbsm-index">
            <input type="hidden" name="pdf_filename" id="edit-sm-pdf-filename">
            <div class="edit-modal-grid">
                <div class="edit-field-group">
                    <label class="edit-field-label">Kode Klasifikasi <span class="req">*</span></label>
                    <input type="text" name="kode_klasifikasi" id="edit-sm-kode-klasifikasi" class="edit-field-input" maxlength="10" style="text-transform:uppercase;" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Kode Surat <span class="req">*</span></label>
                    <input type="text" name="kode_surat" id="edit-sm-kode-surat" class="edit-field-input" required>
                </div>
                <div class="edit-field-group edit-field-full">
                    <label class="edit-field-label">Uraian Informasi Berkas <span class="req">*</span></label>
                    <textarea name="uraian_informasi_berkas" id="edit-sm-uraian-berkas" class="edit-field-input" rows="2" required></textarea>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Kurun Waktu <span class="req">*</span></label>
                    <input type="number" name="kurun_waktu" id="edit-sm-kurun-waktu" class="edit-field-input" min="1900" max="2100" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Jumlah Folder <span class="req">*</span></label>
                    <input type="text" name="jumlah_folder" id="edit-sm-jumlah-folder" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Nomor Item Arsip <span class="req">*</span></label>
                    <input type="number" name="nomor_item_arsip" id="edit-sm-nomor-item" class="edit-field-input" min="1" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Tanggal Surat <span class="req">*</span></label>
                    <input type="text" name="tanggal_surat" id="edit-sm-tanggal" class="edit-field-input" required placeholder="contoh: 10 Juli 2025">
                </div>
                <div class="edit-field-group edit-field-full">
                    <label class="edit-field-label">Uraian Informasi Arsip <span class="req">*</span></label>
                    <textarea name="uraian_informasi_arsip" id="edit-sm-uraian-arsip" class="edit-field-input" rows="2" required></textarea>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Jumlah Lembar <span class="req">*</span></label>
                    <input type="text" name="jumlah_lembar" id="edit-sm-jumlah-lembar" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Tingkat Perkembangan <span class="req">*</span></label>
                    <input type="text" name="tingkat_perkembangan" id="edit-sm-tingkat-perkembangan" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Lokasi Simpan <span class="req">*</span></label>
                    <input type="text" name="lokasi_simpan" id="edit-sm-lokasi-simpan" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Klasifikasi Keamanan <span class="req">*</span></label>
                    <select name="klasifikasi_keamanan" id="edit-sm-klas-keamanan" class="edit-field-input" required>
                        <option value="">Pilih...</option>
                        <option value="Biasa">Biasa</option>
                        <option value="Terbatas">Terbatas</option>
                        <option value="Rahasia">Rahasia</option>
                        <option value="Sangat Rahasia">Sangat Rahasia</option>
                    </select>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Tingkat Akses <span class="req">*</span></label>
                    <select name="tingkat_akses" id="edit-sm-tingkat-akses" class="edit-field-input" required>
                        <option value="">Pilih...</option>
                        <option value="Terbuka">Terbuka</option>
                        <option value="Terbatas">Terbatas</option>
                        <option value="Tertutup">Tertutup</option>
                    </select>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Retensi Aktif <span class="req">*</span></label>
                    <input type="text" name="retensi_aktif" id="edit-sm-retensi-aktif" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Retensi Inaktif <span class="req">*</span></label>
                    <input type="text" name="retensi_inaktif" id="edit-sm-retensi-inaktif" class="edit-field-input" required>
                </div>
                <div class="edit-field-group edit-field-full">
                    <label class="edit-field-label">Retensi Keterangan <span class="req">*</span></label>
                    <textarea name="retensi_keterangan" id="edit-sm-retensi-keterangan" class="edit-field-input" rows="2" required></textarea>
                </div>
            </div>
            <div id="modal-edit-sm-error" style="display:none;margin-top:.75rem;padding:.65rem 1rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:.5rem;font-size:.82rem;color:#fca5a5;"></div>
            <div class="modal-actions" style="margin-top:1.25rem;">
                <button type="button" class="btn btn-secondary" id="modal-edit-sm-cancel">Batal</button>
                <button type="submit" class="btn btn-primary" id="modal-edit-sm-submit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v14a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── FORM INPUT SURAT MASUK ───────────────────────────────────────────── --}}
@if($isAdmin ?? false)
<div class="sm-form-divider">
    <div class="sm-form-divider-line"></div>
    <span class="sm-form-divider-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
        Tambah Surat Masuk Baru
    </span>
    <div class="sm-form-divider-line"></div>
</div>
@endif


<form action="{{ route('arsip.store') }}" method="POST" enctype="multipart/form-data" class="form-container" id="arsip-form">
    @csrf

    {{-- Section 1: Informasi Utama --}}
    <div class="form-section pre-hide">
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
                <input type="text" name="kode_klasifikasi" id="kode_klasifikasi"
                       class="form-input @error('kode_klasifikasi') input-error @enderror"
                       value="{{ old('kode_klasifikasi') }}"
                       placeholder="Contoh: PR" maxlength="2"
                       style="text-transform:uppercase;letter-spacing:.1em;"
                       autocomplete="off" required>
                @error('kode_klasifikasi')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="kode_surat" class="form-label">Kode Surat <span class="required">*</span></label>
                <input type="text" name="kode_surat" id="kode_surat" class="form-input @error('kode_surat') input-error @enderror" value="{{ old('kode_surat') }}" placeholder="Contoh: 02.02" required>
                @error('kode_surat')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="required">*</span></label>
                @php
                    // Tanggal hari ini dalam format Indonesia (misal: 7 Juli 2026)
                    $_bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    $_todaySM = now()->day . ' ' . $_bulanID[now()->month] . ' ' . now()->year;
                @endphp
                {{-- flatpickr wrap mode: klik input ATAU ikon = buka kalender --}}
                <div class="date-input-wrap" id="tanggal-wrap-sm">
                    <input type="text" name="tanggal_surat" id="tanggal_surat"
                           class="form-input @error('tanggal_surat') input-error @enderror"
                           value="{{ old('tanggal_surat') ?: $_todaySM }}"
                           placeholder="Contoh: 11 Mei 2026"
                           autocomplete="off" required
                           data-input>
                    <span class="date-icon" data-toggle style="cursor:pointer; pointer-events:auto;">
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
    <div class="form-section pre-hide">
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
                <input type="number" name="kurun_waktu" id="kurun_waktu"
                       class="form-input @error('kurun_waktu') input-error @enderror"
                       value="{{ old('kurun_waktu') }}"
                       placeholder="Contoh: 2024"
                       min="1900" max="2100" required>
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
                <label for="surat_masuk" class="file-upload @error('surat_masuk') file-upload-error @enderror" id="upload-zone">
                    <input type="file" name="surat_masuk" id="surat_masuk" class="file-upload-input" accept="application/pdf" required>
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
                </label>
                @error('surat_masuk')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Section 3: Keamanan & Retensi --}}
    <div class="form-section pre-hide">
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
    <div class="form-actions pre-hide">
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
@push('styles')
<link rel="preload" as="style"
      href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
      onload="this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</noscript>
<style>
/* ── Live Date Badge ──────────────────────────────────── */
.live-date-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .4rem .85rem;
    background: var(--color-primary-light);
    border: 1px solid var(--color-border);
    border-radius: 999px;
    font-size: .8rem; font-weight: 600;
    color: var(--color-text-muted);
    flex-shrink: 0; white-space: nowrap;
    transition: color .2s;
}
.live-date-badge svg { flex-shrink: 0; color: var(--color-primary); }
.live-date-divider { opacity: .4; }
#live-date-text-sm { font-weight: 500; }

/* ── Form Divider (pemisah tabel & form) ─── */
.sm-form-divider {
    display: flex;
    align-items: center;
    gap: .75rem;
    margin: 1.75rem 0 1.5rem;
}
.sm-form-divider-line {
    flex: 1;
    height: 1px;
    background: var(--color-border);
    border-radius: 1px;
}
.sm-form-divider-label {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .3rem .9rem;
    border: 1px solid var(--color-border);
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 600;
    color: var(--color-text-muted);
    background: var(--color-surface);
    white-space: nowrap;
    letter-spacing: .01em;
}

/* ── Filter Bar ─────────────────────────────────────── */
.table-filter-bar {
    display: flex; flex-wrap: wrap; align-items: center;
    gap: .6rem; padding: .75rem 1rem;
    background: var(--color-bg, #0f172a); border-bottom: 1px solid var(--color-border);
    border-radius: .5rem .5rem 0 0;
    margin-bottom: 0;
}
.filter-label { font-size: .78rem; font-weight: 600; color: var(--color-text-muted); white-space: nowrap; }
.filter-group { display: flex; align-items: center; gap: .4rem; }
.filter-select {
    padding: .35rem .65rem; font-size: .8rem; font-family: inherit;
    background: var(--color-surface); border: 1px solid var(--color-border);
    border-radius: .375rem; color: var(--color-text); cursor: pointer;
    outline: none; transition: border-color .2s;
}
.filter-select:focus { border-color: var(--color-primary); }
.filter-reset {
    padding: .35rem .75rem; font-size: .78rem; font-weight: 600; font-family: inherit;
    background: transparent; border: 1px solid var(--color-border);
    border-radius: .375rem; color: var(--color-text-muted); cursor: pointer;
    transition: all .2s;
}
.filter-reset:hover { border-color: var(--color-primary); color: var(--color-primary); }
.filter-count { font-size: .78rem; color: var(--color-text-muted); margin-left: auto; }

/* ── Tabel Arsip ───────────────────────────────────────── */
.arsip-table { width:100%; border-collapse:collapse; font-size:.875rem; }
.arsip-table th {
    background: var(--color-primary);
    color: #ffffff;
    font-weight:600; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em;
    padding:.7rem 1rem; text-align:left; border-bottom:none;
    white-space:nowrap;
}
[data-theme="dark"] .arsip-table th {
    background: var(--color-primary-hover, #15314f);
    color: #e2e8f0;
}
.th-sortable { cursor: pointer; user-select: none; }
.th-sortable:hover { background: var(--color-primary-hover, #15314f); }
.th-sortable.sort-asc .sort-icon::after { content: ' ↑'; }
.th-sortable.sort-desc .sort-icon::after { content: ' ↓'; }
.sort-icon { opacity: .6; font-size: .8em; }
.arsip-table td {
    padding:.7rem 1rem; border-bottom:1px solid var(--color-border, #2a3347);
    color: var(--color-text, #e2e8f0); vertical-align:middle;
}
.arsip-table tbody tr:last-child td { border-bottom:none; }
.arsip-table tbody tr:hover td { background: rgba(99,102,241,.06); }
.arsip-table tbody tr[style*="display:none"] { display:none !important; }
.td-center { text-align:center; }
.td-nowrap { white-space:nowrap; }
.td-no { width:48px; font-weight:600; color:var(--color-text-muted,#8892a4); }
.td-uraian { max-width:240px; word-break:break-word; overflow-wrap:break-word; white-space:normal; }
.td-muted { color:var(--color-text-muted,#8892a4); }
.td-empty { text-align:center; padding:2.5rem 1rem !important; color:var(--color-text-muted,#8892a4); }
.td-empty svg { display:block; margin:0 auto .75rem; opacity:.35; }
.td-empty span { display:block; }
.badge-kode {
    display:inline-block; padding:.2rem .55rem; border-radius:.375rem; font-size:.75rem;
    background: var(--color-primary-light); color: var(--color-primary); font-weight:600; white-space:nowrap;
    border: 1px solid var(--color-border);
}
/* Badge kosong / tidak ada data */
.badge-kode-empty {
    background: transparent;
    color: var(--color-text-muted);
    border-color: var(--color-border);
    font-weight: 400;
    opacity: 0.6;
}
.btn-pdf {
    display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .65rem;
    border-radius:.375rem; font-size:.75rem; font-weight:600; text-decoration:none;
    background:rgba(16,185,129,.12); color:#34d399; border:1px solid rgba(16,185,129,.2);
    transition:all .2s;
}
.btn-pdf:hover { background:rgba(16,185,129,.22); }
.btn-hapus {
    display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .65rem;
    border-radius:.375rem; font-size:.75rem; font-weight:600; cursor:pointer;
    background:rgba(239,68,68,.1); color:#f87171; border:1px solid rgba(239,68,68,.2);
    transition:all .2s;
}
.btn-hapus:hover { background:rgba(239,68,68,.2); }

/* ── Modal ────────────────────────────────────────────── */
/* .modal-overlay: didefinisikan di app.css secara global */
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
.modal-box {
    background:var(--color-surface, #1a2236); border:1px solid var(--color-border,#2a3347);
    border-radius:1rem; padding:2rem; max-width:420px; width:90%; text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.5); animation:slideUp .2s ease;
}
@keyframes slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.modal-icon {
    width:56px; height:56px; border-radius:50%; background:rgba(239,68,68,.12);
    display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;
    color:#f87171; border:1px solid rgba(239,68,68,.2);
}
.modal-title { font-size:1.125rem; font-weight:700; color:var(--color-text,#e2e8f0); margin:0 0 .5rem; }
.modal-desc { font-size:.875rem; color:var(--color-text-muted,#8892a4); margin:0 0 1.5rem; line-height:1.6; }
.modal-desc strong { color:var(--color-text,#e2e8f0); }
.modal-actions { display:flex; gap:.75rem; justify-content:center; }
.btn-danger {
    background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; border:none;
    padding:.6rem 1.4rem; border-radius:.5rem; font-weight:600; cursor:pointer; transition:all .2s;
}
.btn-danger:hover { opacity:.88; transform:translateY(-1px); }
.alert { display:flex; align-items:center; gap:.5rem; padding:.75rem 1rem; border-radius:.5rem; font-size:.875rem; }
.alert-error { background:rgba(239,68,68,.1); color:#f87171; border:1px solid rgba(239,68,68,.2); }

/* ── Flatpickr — wrap mode ───────────────────────────── */
.date-input-wrap { position: relative; }
.date-input-wrap .form-input { padding-right: 2.5rem; cursor: pointer; }
.date-icon {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    color: var(--color-text-muted);
    pointer-events: auto;   /* icon bisa diklik! */
    display: flex;
    cursor: pointer;
    z-index: 1;
}
.date-icon:hover { color: var(--color-primary); }
.flatpickr-calendar { font-family: var(--font-family) !important; border-radius: var(--radius-lg) !important; box-shadow: var(--shadow-lg) !important; border: 1px solid var(--color-border) !important; }
.flatpickr-day.selected, .flatpickr-day.selected:hover { background: var(--color-primary) !important; border-color: var(--color-primary) !important; }
.flatpickr-day:hover { background: var(--color-primary-light) !important; color: var(--color-primary) !important; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Skeleton → Tabel ───────────────────────────────────────────────
    (function () {
        var skeleton = document.getElementById('table-skeleton-sm');
        var content  = document.getElementById('table-content-sm');
        if (!skeleton || !content) return;
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                skeleton.style.transition = 'opacity 0.2s ease';
                skeleton.style.opacity = '0';
                content.style.display = '';
                content.classList.add('table-content-ready');
                setTimeout(function () {
                    if (skeleton.parentNode) skeleton.parentNode.removeChild(skeleton);
                    applyFilter(); // init filter setelah tabel muncul
                }, 220);
            });
        });
    })();

    // ── Modal Hapus ──────────────────────────────────────────────
    const modal       = document.getElementById('modal-hapus');
    const modalLabel  = document.getElementById('modal-label');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm= document.getElementById('modal-confirm');
    let   pendingForm = null;

    document.body.appendChild(modal);

    function openModal(label, form) {
        pendingForm = form;
        modalLabel.textContent = label;
        modal.style.display    = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        pendingForm = null;
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.btn-hapus').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.dataset.label, document.getElementById(btn.dataset.form));
        });
    });

    modalCancel.addEventListener('click', closeModal);
    modalConfirm.addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
    });
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.style.display === 'flex') closeModal();
    });

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
    removeBtn.addEventListener('click', function (e) { e.preventDefault(); clearFile(); });
    input.addEventListener('change', function () {
        if (this.files.length > 0) showPreview(this.files[0]);
    });
    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.add('file-upload-drag'); });
    });
    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) { e.preventDefault(); zone.classList.remove('file-upload-drag'); });
    });
    zone.addEventListener('drop', function (e) {
        const file = e.dataTransfer.files[0];
        if (!file) return;
        if (file.type !== 'application/pdf') { zone.classList.add('file-upload-error'); return; }
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
    });
    document.getElementById('btn-reset').addEventListener('click', function () {
        document.getElementById('arsip-form').reset();
        clearFile();
        if (window._fpSM) window._fpSM.clear();
    });

    // ── Filter Bulan & Tahun ─────────────────────────────────────
    const selBulan  = document.getElementById('filter-bulan-sm');
    const selTahun  = document.getElementById('filter-tahun-sm');
    const btnReset  = document.getElementById('filter-reset-sm');
    const countEl   = document.getElementById('filter-count-sm');
    const tbody     = document.getElementById('tbody-sm');

    const selKode  = document.getElementById('filter-kode-sm');

    function applyFilter() {
        if (!tbody) return;
        const bulan = selBulan ? selBulan.value : '';
        const tahun = selTahun ? selTahun.value : '';
        const kode  = selKode  ? selKode.value  : '';
        const rows  = tbody.querySelectorAll('tr[data-bulan]');
        let visible = 0;
        rows.forEach(function (tr) {
            const rb = tr.dataset.bulan;
            const rt = tr.dataset.tahun;
            const rk = (tr.dataset.kode || '').toUpperCase();
            const show = (!bulan || rb == bulan) && (!tahun || rt == tahun) && (!kode || rk === kode.toUpperCase());
            tr.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        // Renumber visible rows
        let no = 1;
        rows.forEach(function (tr) {
            if (tr.style.display !== 'none') {
                const noCell = tr.querySelector('.row-no');
                if (noCell) noCell.textContent = no++;
            }
        });
        if (countEl) {
            const total = rows.length;
            countEl.textContent = (bulan || tahun || kode) ? (visible + ' dari ' + total + ' data ditampilkan') : '';
        }
    }

    if (selBulan) selBulan.addEventListener('change', applyFilter);
    if (selTahun) selTahun.addEventListener('change', applyFilter);
    if (selKode)  selKode.addEventListener('change', applyFilter);
    if (btnReset) btnReset.addEventListener('click', function () {
        if (selBulan) selBulan.value = '';
        if (selTahun) selTahun.value = '';
        if (selKode)  selKode.value  = '';
        applyFilter();
    });

    // ── Sort per Klasifikasi ─────────────────────────────────────
    // Urutan prioritas kode klasifikasi
    const KODE_ORDER = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
    let sortDir = ''; // '' = no sort, 'asc' = ascending, 'desc' = descending

    function kodeRank(kode) {
        const idx = KODE_ORDER.indexOf(kode.toUpperCase());
        return idx === -1 ? 999 : idx;
    }

    const thKode = document.getElementById('th-kode-sm');
    if (thKode && tbody) {
        thKode.addEventListener('click', function () {
            // Cycle: none → asc → desc → none
            if (sortDir === '') sortDir = 'asc';
            else if (sortDir === 'asc') sortDir = 'desc';
            else sortDir = '';

            // Update visual indicator
            document.querySelectorAll('.th-sortable').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
            });
            if (sortDir === 'asc') thKode.classList.add('sort-asc');
            else if (sortDir === 'desc') thKode.classList.add('sort-desc');

            const rows = Array.from(tbody.querySelectorAll('tr[data-kode]'));
            if (sortDir === '') {
                // Kembalikan ke urutan semula (berdasarkan data-original-order)
                rows.sort(function (a, b) {
                    return parseInt(a.dataset.originalOrder || 0) - parseInt(b.dataset.originalOrder || 0);
                });
            } else {
                rows.sort(function (a, b) {
                    const ra = kodeRank(a.dataset.kode);
                    const rb = kodeRank(b.dataset.kode);
                    return sortDir === 'asc' ? ra - rb : rb - ra;
                });
            }
            rows.forEach(function (row) { tbody.appendChild(row); });
            applyFilter(); // reapply filter after sort
        });

        // Simpan urutan asli
        const rows = tbody.querySelectorAll('tr[data-kode]');
        rows.forEach(function (row, i) {
            row.dataset.originalOrder = i;
        });
    }

    // Sortable Tanggal
    const thTanggal = document.querySelector('#arsip-table-sm th[data-col="tanggal"]');
    let sortTanggalDir = '';
    if (thTanggal && tbody) {
        thTanggal.addEventListener('click', function () {
            if (sortTanggalDir === '') sortTanggalDir = 'asc';
            else if (sortTanggalDir === 'asc') sortTanggalDir = 'desc';
            else sortTanggalDir = '';
            document.querySelectorAll('.th-sortable').forEach(th => th.classList.remove('sort-asc','sort-desc'));
            if (sortTanggalDir === 'asc') thTanggal.classList.add('sort-asc');
            else if (sortTanggalDir === 'desc') thTanggal.classList.add('sort-desc');

            const rows = Array.from(tbody.querySelectorAll('tr[data-kode]'));
            if (sortTanggalDir === '') {
                rows.sort((a,b) => parseInt(a.dataset.originalOrder||0) - parseInt(b.dataset.originalOrder||0));
            } else {
                rows.sort(function(a,b) {
                    const ta = a.dataset.tahun ? (a.dataset.tahun * 100 + parseInt(a.dataset.bulan||0)) : 0;
                    const tb = b.dataset.tahun ? (b.dataset.tahun * 100 + parseInt(b.dataset.bulan||0)) : 0;
                    return sortTanggalDir === 'asc' ? ta - tb : tb - ta;
                });
            }
            rows.forEach(row => tbody.appendChild(row));
            applyFilter();
        });
    }
    // ── Modal Edit Surat Masuk ────────────────────────────────────
    const modalEditSM       = document.getElementById('modal-edit-sm');
    const formEditSM        = document.getElementById('form-edit-sm');
    const editSMLoading     = document.getElementById('modal-edit-sm-loading');
    const editSMError       = document.getElementById('modal-edit-sm-error');
    const editSMClose       = document.getElementById('modal-edit-sm-close');
    const editSMCancel      = document.getElementById('modal-edit-sm-cancel');

    if (modalEditSM) document.body.appendChild(modalEditSM);

    function openEditSM(rowIndex) {
        if (!modalEditSM) return;
        formEditSM.style.display   = 'none';
        editSMLoading.style.display = 'block';
        editSMError.style.display  = 'none';
        modalEditSM.style.display  = 'flex';
        document.body.style.overflow = 'hidden';

        fetch('/arsip/' + rowIndex + '/edit', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { throw new Error(data.error); }
            // Set action URL
            formEditSM.action = '/arsip/' + rowIndex;
            // Fill fields
            document.getElementById('edit-sm-dbsm-index').value           = data.dbsmIndex || '';
            document.getElementById('edit-sm-pdf-filename').value          = data.pdf_filename || '';
            document.getElementById('edit-sm-kode-klasifikasi').value      = data.kode_klasifikasi || '';
            document.getElementById('edit-sm-kode-surat').value            = data.kode_surat || '';
            document.getElementById('edit-sm-uraian-berkas').value         = data.uraian_informasi_berkas || '';
            document.getElementById('edit-sm-kurun-waktu').value           = data.kurun_waktu || '';
            document.getElementById('edit-sm-jumlah-folder').value         = data.jumlah_folder || '';
            document.getElementById('edit-sm-nomor-item').value            = data.nomor_item_arsip || '';
            document.getElementById('edit-sm-tanggal').value               = data.tanggal_surat || '';
            document.getElementById('edit-sm-uraian-arsip').value          = data.uraian_informasi_arsip || '';
            document.getElementById('edit-sm-jumlah-lembar').value         = data.jumlah_lembar || '';
            document.getElementById('edit-sm-tingkat-perkembangan').value  = data.tingkat_perkembangan || '';
            document.getElementById('edit-sm-lokasi-simpan').value         = data.lokasi_simpan || '';
            document.getElementById('edit-sm-retensi-aktif').value         = data.retensi_aktif || '';
            document.getElementById('edit-sm-retensi-inaktif').value       = data.retensi_inaktif || '';
            document.getElementById('edit-sm-retensi-keterangan').value    = data.retensi_keterangan || '';
            // Select fields
            ['edit-sm-klas-keamanan','edit-sm-tingkat-akses'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                var key = id === 'edit-sm-klas-keamanan' ? data.klasifikasi_keamanan : data.tingkat_akses;
                Array.from(el.options).forEach(function(opt) { opt.selected = opt.value === key; });
            });
            editSMLoading.style.display = 'none';
            formEditSM.style.display    = 'block';
        })
        .catch(function(err) {
            editSMLoading.style.display = 'none';
            editSMError.textContent     = 'Gagal memuat data: ' + err.message;
            editSMError.style.display   = 'block';
        });
    }

    function closeEditSM() {
        if (modalEditSM) modalEditSM.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.btn-edit-sm').forEach(function(btn) {
        btn.addEventListener('click', function() { openEditSM(btn.dataset.index); });
    });
    if (editSMClose)  editSMClose.addEventListener('click', closeEditSM);
    if (editSMCancel) editSMCancel.addEventListener('click', closeEditSM);
    if (modalEditSM)  modalEditSM.addEventListener('click', function(e) { if (e.target === modalEditSM) closeEditSM(); });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modalEditSM && modalEditSM.style.display === 'flex') closeEditSM();
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
(function() {
    // ── Live Clock ──────────────────────────────────────────────────────────
    var BIDS = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var HIDS = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    function pad(n) { return String(n).padStart(2,'0'); }
    var clockEl = document.getElementById('live-clock-sm');
    var dateEl  = document.getElementById('live-date-text-sm');
    function tickSM() {
        var d = new Date();
        if (clockEl) clockEl.textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds());
        if (dateEl)  dateEl.textContent  = HIDS[d.getDay()]+', '+d.getDate()+' '+BIDS[d.getMonth()]+' '+d.getFullYear();
    }
    tickSM();
    setInterval(tickSM, 1000);

    // ── Flatpickr: mode direct (lebih reliabel dari wrap mode) ───────────────
    var BULAN_ID       = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_ID_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    try {
        var fpInputSM = document.getElementById('tanggal_surat');
        if (typeof flatpickr !== 'undefined' && fpInputSM) {
            window._fpSM = flatpickr(fpInputSM, {
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
            // Klik icon kalender → buka flatpickr
            var fpIconSM = document.querySelector('#tanggal-wrap-sm .date-icon');
            if (fpIconSM) fpIconSM.addEventListener('click', function() { window._fpSM.open(); });
        }
    } catch(e) { console.warn('Flatpickr SM error:', e); }

    // ── Kode Klasifikasi: paksa uppercase & max 2 karakter ───────────────────
    var kodeInputSM = document.getElementById('kode_klasifikasi');
    if (kodeInputSM) {
        kodeInputSM.addEventListener('input', function() {
            var v = this.value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 2);
            if (this.value !== v) this.value = v;
        });
        kodeInputSM.addEventListener('keydown', function(e) {
            // Tolak karakter non-huruf dan tolak jika sudah 2 karakter
            if (this.value.length >= 2 && e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
            }
        });
    }
})();
</script>
@endpush

@endsection
