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
</div>

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

                        $kodePriorityOrderSM = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
                        $kodeListSorted = [];
                        foreach ($kodePriorityOrderSM as $pk) {
                            if (isset($kodeList[$pk])) $kodeListSorted[$pk] = true;
                        }
                        foreach ($kodeList as $kk => $_) {
                            if (!isset($kodeListSorted[$kk])) $kodeListSorted[$kk] = true;
                        }
@endphp
                    @foreach($kodeListSorted as $kd => $_)
                    <option value="{{ $kd }}">{{ $kd }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="filter-reset" id="filter-reset-sm">Reset Filter</button>
            <div class="filter-count" id="filter-count-sm"></div>
        </div>

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

        <div id="table-content-sm" style="overflow-x:auto; margin-top:.5rem; display:none;">
            <table class="arsip-table" id="arsip-table-sm">
                <thead>
                    <tr>
                        <th class="th-sortable" data-col="no" style="cursor:default;">No</th>
                        <th class="th-sortable" data-col="tanggal">Tanggal Surat <span class="sort-icon"></span></th>
                        <th class="th-sortable" data-col="kode" id="th-kode-sm">Kode Klasifikasi <span class="sort-icon"></span></th>
                        <th>Uraian Berkas</th>
                        <th>Link Folder</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tbody-sm">
                    @forelse($rows as $no => $item)
                        @php
$d           = $item['data'];

                            $tanggal      = $d[7]  ?? '-';
                            $kodeKlasRaw  = $d[1]  ?? '';
                            $kodeKlas     = (str_starts_with($kodeKlasRaw, '#') || $kodeKlasRaw === '') ? '—' : $kodeKlasRaw;
                            $kodePrefix   = preg_match('/^([A-Za-z]+)/', $kodeKlasRaw, $km) ? strtoupper($km[1]) : '';
                            $uraian       = $d[2]  ?? '-';
                            $rawLink      = trim($d[16] ?? '');

                            $pdfUrl = '';
                            if ($rawLink !== '') {
                                $pdfUrl = str_starts_with($rawLink, 'http')
                                    ? $rawLink
                                    : route('pdf.view', ['type' => 'surat-masuk', 'filename' => $rawLink]);
                            }

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
            <div class="table-collapse-bar" id="collapse-bar-sm" style="display:none;">
                <button type="button" class="btn-toggle-collapse" id="btn-toggle-sm">
                    <span class="btn-toggle-text">Lihat Selengkapnya</span>
                    <svg class="btn-toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

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
                    <select name="kode_klasifikasi" id="edit-sm-kode-klasifikasi" class="edit-field-input" required>
                        <option value="">-- Pilih Kode Klasifikasi --</option>
                        <option value="PR">PR</option>
                        <option value="KP">KP</option>
                        <option value="OT">OT</option>
                        <option value="KU">KU</option>
                        <option value="HK">HK</option>
                        <option value="UM">UM</option>
                        <option value="PL">PL</option>
                        <option value="UP">UP</option>
                        <option value="HM">HM</option>
                        <option value="PW">PW</option>
                        <option value="LT">LT</option>
                        <option value="DL">DL</option>
                        <option value="DT">DT</option>
                        <option value="KI">KI</option>
                        <option value="__lainnya__">Lainnya (isi manual)...</option>
                    </select>

                    <input type="text" id="edit-sm-kode-klasifikasi-custom"
                           class="edit-field-input"
                           placeholder="Kode kustom (2 huruf, contoh: XX)"
                           maxlength="2"
                           autocomplete="off"
                           style="display:none;margin-top:.4rem;"
                           oninput="this.value=this.value.toUpperCase().replace(/[^A-Z]/g,'').slice(0,2)">
                    <small id="edit-sm-kode-custom-hint" style="display:none;color:var(--color-text-muted);font-size:.72rem;">Isi kode klasifikasi kustom (2 huruf) — data retensi perlu diisi manual.</small>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Kode Surat <span class="req">*</span></label>
                    <input type="text" name="kode_surat" id="edit-sm-kode-surat" class="edit-field-input" pattern="[0-9.]*" oninput="this.value=this.value.replace(/[^0-9.]/g,'').replace(/\.{2,}/g,'.');" placeholder="Contoh: 02.02" required>
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
                    <select name="tingkat_perkembangan" id="edit-sm-tingkat-perkembangan" class="edit-field-input" required>
                        <option value="">-- Pilih Tingkat Perkembangan --</option>
                        <option value="Asli">Asli</option>
                        <option value="Fotokopi">Fotokopi</option>
                        <option value="Salinan">Salinan</option>
                    </select>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Lokasi Simpan <span class="req">*</span></label>
                    <input type="text" name="lokasi_simpan" id="edit-sm-lokasi-simpan" class="edit-field-input" required>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Klasifikasi Keamanan <span class="req">*</span></label>
                    <input type="text" name="klasifikasi_keamanan" id="edit-sm-klas-keamanan" class="edit-field-input edit-kode-auto" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                    <small style="color:var(--color-text-muted);font-size:.72rem;">Diisi otomatis dari kode klasifikasi</small>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Tingkat Akses <span class="req">*</span></label>
                    <input type="text" name="tingkat_akses" id="edit-sm-tingkat-akses" class="edit-field-input edit-kode-auto" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                    <small style="color:var(--color-text-muted);font-size:.72rem;">Diisi otomatis dari kode klasifikasi</small>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Retensi Aktif <span class="req">*</span></label>
                    <input type="text" name="retensi_aktif" id="edit-sm-retensi-aktif" class="edit-field-input edit-retensi-auto" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                    <small style="color:var(--color-text-muted);font-size:.72rem;">Diisi otomatis dari kode klasifikasi</small>
                </div>
                <div class="edit-field-group">
                    <label class="edit-field-label">Retensi Inaktif <span class="req">*</span></label>
                    <input type="text" name="retensi_inaktif" id="edit-sm-retensi-inaktif" class="edit-field-input edit-retensi-auto" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                    <small style="color:var(--color-text-muted);font-size:.72rem;">Diisi otomatis dari kode klasifikasi</small>
                </div>
                <div class="edit-field-group edit-field-full">
                    <label class="edit-field-label">Retensi Keterangan <span class="req">*</span></label>
                    <input type="text" name="retensi_keterangan" id="edit-sm-retensi-keterangan" class="edit-field-input edit-retensi-auto" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                    <small style="color:var(--color-text-muted);font-size:.72rem;">Diisi otomatis dari kode klasifikasi</small>
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
                <select name="kode_klasifikasi" id="kode_klasifikasi"
                        class="form-input form-select @error('kode_klasifikasi') input-error @enderror"
                        required>
                    <option value="">-- Pilih Kode Klasifikasi --</option>
                    <option value="PR" {{ old('kode_klasifikasi') == 'PR' ? 'selected' : '' }}>PR</option>
                    <option value="KP" {{ old('kode_klasifikasi') == 'KP' ? 'selected' : '' }}>KP</option>
                    <option value="OT" {{ old('kode_klasifikasi') == 'OT' ? 'selected' : '' }}>OT</option>
                    <option value="KU" {{ old('kode_klasifikasi') == 'KU' ? 'selected' : '' }}>KU</option>
                    <option value="HK" {{ old('kode_klasifikasi') == 'HK' ? 'selected' : '' }}>HK</option>
                    <option value="UM" {{ old('kode_klasifikasi') == 'UM' ? 'selected' : '' }}>UM</option>
                    <option value="PL" {{ old('kode_klasifikasi') == 'PL' ? 'selected' : '' }}>PL</option>
                    <option value="UP" {{ old('kode_klasifikasi') == 'UP' ? 'selected' : '' }}>UP</option>
                    <option value="HM" {{ old('kode_klasifikasi') == 'HM' ? 'selected' : '' }}>HM</option>
                    <option value="PW" {{ old('kode_klasifikasi') == 'PW' ? 'selected' : '' }}>PW</option>
                    <option value="LT" {{ old('kode_klasifikasi') == 'LT' ? 'selected' : '' }}>LT</option>
                    <option value="DL" {{ old('kode_klasifikasi') == 'DL' ? 'selected' : '' }}>DL</option>
                    <option value="DT" {{ old('kode_klasifikasi') == 'DT' ? 'selected' : '' }}>DT</option>
                    <option value="KI" {{ old('kode_klasifikasi') == 'KI' ? 'selected' : '' }}>KI</option>
                    <option value="__lainnya__" {{ !in_array(old('kode_klasifikasi'), ['','PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI']) && old('kode_klasifikasi') ? 'selected' : '' }}>Lainnya (isi manual)...</option>
                </select>

                @php
$oldKodeSM = old('kode_klasifikasi'); $isCustomKodeSM = $oldKodeSM && !in_array($oldKodeSM, ['','PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI','__lainnya__']);
@endphp
                <input type="text" id="kode_klasifikasi_custom_sm"
                       class="form-input mt-1"
                       placeholder="Contoh: XX (2 huruf)"
                       maxlength="2"
                       autocomplete="off"
                       value="{{ $isCustomKodeSM ? $oldKodeSM : '' }}"
                       style="{{ $isCustomKodeSM ? '' : 'display:none;' }}margin-top:.4rem;"
                       oninput="this.value=this.value.toUpperCase().replace(/[^A-Z]/g,'').slice(0,2)">
                @error('kode_klasifikasi')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="kode_surat" class="form-label">Kode Surat <span class="required">*</span></label>
                <input type="text" name="kode_surat" id="kode_surat" class="form-input @error('kode_surat') input-error @enderror" value="{{ old('kode_surat') }}" placeholder="Contoh: 02.02" pattern="[0-9.]*" oninput="this.value=this.value.replace(/[^0-9.]/g,'').replace(/\.{2,}/g,'.');" autocomplete="off" required>
                @error('kode_surat')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="required">*</span></label>
                @php
$_bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    $_todaySM = now()->day . ' ' . $_bulanID[now()->month] . ' ' . now()->year;
@endphp

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
                       placeholder="Contoh: 2026"
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
                <select name="tingkat_perkembangan" id="tingkat_perkembangan" class="form-input @error('tingkat_perkembangan') input-error @enderror" required>
                    <option value="">-- Pilih Tingkat Perkembangan --</option>
                    <option value="Asli" {{ old('tingkat_perkembangan') == 'Asli' ? 'selected' : '' }}>Asli</option>
                    <option value="Fotokopi" {{ old('tingkat_perkembangan') == 'Fotokopi' ? 'selected' : '' }}>Fotokopi</option>
                    <option value="Salinan" {{ old('tingkat_perkembangan') == 'Salinan' ? 'selected' : '' }}>Salinan</option>
                </select>
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
                <input type="text" name="klasifikasi_keamanan" id="klasifikasi_keamanan" class="form-input kode-auto @error('klasifikasi_keamanan') input-error @enderror" value="{{ old('klasifikasi_keamanan') }}" placeholder="Otomatis dari Kode Klasifikasi" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                <span style="font-size:.72rem;color:var(--color-text-muted);">Diisi otomatis berdasarkan Kode Klasifikasi</span>
                @error('klasifikasi_keamanan')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="tingkat_akses" class="form-label">Tingkat Akses <span class="required">*</span></label>
                <input type="text" name="tingkat_akses" id="tingkat_akses" class="form-input kode-auto @error('tingkat_akses') input-error @enderror" value="{{ old('tingkat_akses') }}" placeholder="Otomatis dari Kode Klasifikasi" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                <span style="font-size:.72rem;color:var(--color-text-muted);">Diisi otomatis berdasarkan Kode Klasifikasi</span>
                @error('tingkat_akses')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="retensi_aktif" class="form-label">Retensi — Aktif <span class="required">*</span></label>
                <input type="text" name="retensi_aktif" id="retensi_aktif" class="form-input retensi-auto @error('retensi_aktif') input-error @enderror" value="{{ old('retensi_aktif') }}" placeholder="Otomatis dari Kode Klasifikasi" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                <span class="error-text" style="font-size:.72rem;color:var(--color-text-muted);">Diisi otomatis berdasarkan Kode Klasifikasi</span>
                @error('retensi_aktif')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="retensi_inaktif" class="form-label">Retensi — Inaktif <span class="required">*</span></label>
                <input type="text" name="retensi_inaktif" id="retensi_inaktif" class="form-input retensi-auto @error('retensi_inaktif') input-error @enderror" value="{{ old('retensi_inaktif') }}" placeholder="Otomatis dari Kode Klasifikasi" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                <span class="error-text" style="font-size:.72rem;color:var(--color-text-muted);">Diisi otomatis berdasarkan Kode Klasifikasi</span>
                @error('retensi_inaktif')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="retensi_keterangan" class="form-label">Retensi — Keterangan <span class="required">*</span></label>
                <input type="text" name="retensi_keterangan" id="retensi_keterangan" class="form-input retensi-auto @error('retensi_keterangan') input-error @enderror" value="{{ old('retensi_keterangan') }}" placeholder="Otomatis dari Kode Klasifikasi" readonly style="background:var(--color-bg,#f8fafc);cursor:not-allowed;opacity:.85;" required>
                <span class="error-text" style="font-size:.72rem;color:var(--color-text-muted);">Diisi otomatis berdasarkan Kode Klasifikasi</span>
                @error('retensi_keterangan')<span class="error-text">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>

    <div class="form-actions pre-hide">
        <button type="button" class="btn btn-secondary" id="btn-reset">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
            </svg>
            Reset
        </button>
        <button type="submit" class="btn btn-primary btn-save-green" id="btn-submit">
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
.th-sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.th-sortable:hover { background: var(--color-primary-hover, #15314f); }
.th-sortable[data-col="no"]:hover { background: var(--color-primary); cursor: default; }
.sort-icon { opacity: .6; font-size: .8em; }
.sort-icon::before { content: '↕'; }
.th-sortable.sort-asc .sort-icon::before { content: '↑'; }
.th-sortable.sort-desc .sort-icon::before { content: '↓'; }
.arsip-table td {
    padding:.7rem 1rem; border-bottom:1px solid var(--color-border, #2a3347);
    color: var(--color-text, #e2e8f0); vertical-align:middle;
}
.arsip-table tbody tr:last-child td { border-bottom:none; }
.arsip-table tbody tr:hover td { background: rgba(99,102,241,.06); }
.arsip-table tbody tr:hover td.td-no { background: transparent; }
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
.btn-edit-sm {
    display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .65rem;
    border-radius:.375rem; font-size:.75rem; font-weight:600; cursor:pointer;
    background:rgba(59,130,246,.1); color:#60a5fa; border:1px solid rgba(59,130,246,.2);
    transition:all .2s;
}
.btn-edit-sm:hover { background:rgba(59,130,246,.22); }

.modal-box {
    background:var(--color-surface, #1a2236); border:1px solid var(--color-border,#2a3347);
    border-radius:1rem; padding:2rem; max-width:420px; width:90%; text-align:center;
    box-shadow:0 20px 60px rgba(0,0,0,.5);

}

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

.date-input-wrap { position: relative; }
.date-input-wrap .form-input { padding-right: 2.5rem; cursor: pointer; }
.date-icon {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    color: var(--color-text-muted);
    pointer-events: auto;
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
                    applyFilter();
                }, 220);
            });
        });
    })();

    const modal       = document.getElementById('modal-hapus');
    const modalLabel  = document.getElementById('modal-label');
    const modalCancel = document.getElementById('modal-cancel');
    const modalConfirm= document.getElementById('modal-confirm');
    let   pendingForm = null;

    if (modal) document.body.appendChild(modal);

    function openModal(label, form) {
        if (!modal) return;
        pendingForm = form;
        if (modalLabel) modalLabel.textContent = label;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.add('is-open');
            });
        });
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        pendingForm = null;

        setTimeout(function () {
            if (!modal.classList.contains('is-open')) {
                modal.style.display = 'none';
            }
            document.body.style.overflow = '';
        }, 240);
    }

    document.querySelectorAll('.btn-hapus').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.dataset.label, document.getElementById(btn.dataset.form));
        });
    });

    if (modalCancel) modalCancel.addEventListener('click', closeModal);
    if (modalConfirm) modalConfirm.addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
    });
    if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') closeModal();
    });

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
        placeholder.style.opacity = '0';
        setTimeout(function() {
            placeholder.style.display = 'none';
            preview.style.display = 'flex';
            preview.style.opacity = '0';
            requestAnimationFrame(function() { preview.style.opacity = '1'; });
        }, 180);
        zone.classList.add('file-upload-has-file');
        zone.classList.remove('file-upload-error');
    }
    function clearFile() {
        input.value = '';
        preview.style.opacity = '0';
        setTimeout(function() {
            preview.style.display = 'none';
            placeholder.style.display = '';
            placeholder.style.opacity = '0';
            requestAnimationFrame(function() { placeholder.style.opacity = '1'; });
        }, 180);
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

    const selBulan  = document.getElementById('filter-bulan-sm');
    const selTahun  = document.getElementById('filter-tahun-sm');
    const btnReset  = document.getElementById('filter-reset-sm');
    const countEl   = document.getElementById('filter-count-sm');
    const tbody     = document.getElementById('tbody-sm');
    const selKode   = document.getElementById('filter-kode-sm');
    const collapseBarSM = document.getElementById('collapse-bar-sm');
    const toggleBtnSM   = document.getElementById('btn-toggle-sm');
    let isSMExpanded    = false;

    function updateSMCollapse() {
        if (!tbody) return;
        const allRows = Array.from(tbody.querySelectorAll('tr[data-bulan]'));
        const matchingRows = allRows.filter(tr => tr.dataset.matchesFilter !== 'false');
        const limit = 5;

        if (matchingRows.length > limit) {
            if (collapseBarSM) collapseBarSM.style.display = 'flex';
            if (!isSMExpanded) {
                matchingRows.forEach((tr, index) => {
                    tr.style.display = index < limit ? '' : 'none';
                });
                const remaining = matchingRows.length - limit;
                if (toggleBtnSM) {
                    toggleBtnSM.classList.remove('is-expanded');
                    toggleBtnSM.querySelector('.btn-toggle-text').textContent = 'Lihat Selengkapnya (' + remaining + ' data lagi)';
                }
            } else {
                matchingRows.forEach(tr => tr.style.display = '');
                if (toggleBtnSM) {
                    toggleBtnSM.classList.add('is-expanded');
                    toggleBtnSM.querySelector('.btn-toggle-text').textContent = 'Sembunyikan Sebagian';
                }
            }
        } else {
            if (collapseBarSM) collapseBarSM.style.display = 'none';
            matchingRows.forEach(tr => tr.style.display = '');
        }

        let no = 1;
        allRows.forEach(tr => {
            if (tr.style.display !== 'none') {
                const noCell = tr.querySelector('.row-no');
                if (noCell) noCell.textContent = no++;
            }
        });
    }

    if (toggleBtnSM) {
        toggleBtnSM.addEventListener('click', function () {
            isSMExpanded = !isSMExpanded;
            updateSMCollapse();
        });
    }

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
            tr.dataset.matchesFilter = show ? 'true' : 'false';
            if (!show) tr.style.display = 'none';
            if (show) visible++;
        });

        if (countEl) {
            const total = rows.length;
            countEl.textContent = (bulan || tahun || kode) ? (visible + ' dari ' + total + ' data ditampilkan') : '';
        }

        updateSMCollapse();
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

    applyFilter();

    const KODE_ORDER = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
    let sortDir = '';

    function kodeRank(kode) {
        const idx = KODE_ORDER.indexOf(kode.toUpperCase());
        return idx === -1 ? 999 : idx;
    }

    const thKode = document.getElementById('th-kode-sm');
    if (thKode && tbody) {
        thKode.addEventListener('click', function () {

            if (sortDir === '') sortDir = 'asc';
            else if (sortDir === 'asc') sortDir = 'desc';
            else sortDir = '';

            document.querySelectorAll('.th-sortable').forEach(th => {
                th.classList.remove('sort-asc', 'sort-desc');
            });
            if (sortDir === 'asc') thKode.classList.add('sort-asc');
            else if (sortDir === 'desc') thKode.classList.add('sort-desc');

            const rows = Array.from(tbody.querySelectorAll('tr[data-kode]'));
            if (sortDir === '') {

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
            applyFilter();
        });

        const rows = tbody.querySelectorAll('tr[data-kode]');
        rows.forEach(function (row, i) {
            row.dataset.originalOrder = i;
        });
    }

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

    const modalEditSM       = document.getElementById('modal-edit-sm');
    const formEditSM        = document.getElementById('form-edit-sm');
    const editSMLoading     = document.getElementById('modal-edit-sm-loading');
    const editSMError       = document.getElementById('modal-edit-sm-error');
    const editSMClose       = document.getElementById('modal-edit-sm-close');
    const editSMCancel      = document.getElementById('modal-edit-sm-cancel');

    if (modalEditSM) document.body.appendChild(modalEditSM);

    function openEditSM(rowIndex) {
        if (!modalEditSM) return;
        formEditSM.style.display    = 'none';
        editSMLoading.style.display = 'block';
        editSMError.style.display   = 'none';
        if (modal && modal.classList) modal.classList.remove('is-open');
        modalEditSM.style.display   = 'flex';
        document.body.style.overflow = 'hidden';

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modalEditSM.classList.add('is-open');
            });
        });

        fetch("{{ url('/arsip') }}/" + rowIndex + "/edit", {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { throw new Error(data.error); }

            formEditSM.action = "{{ url('/arsip') }}/" + rowIndex;

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
            document.getElementById('edit-sm-jumlah-lembar').value         = (window.formatJumlahLembarStr ? window.formatJumlahLembarStr(data.jumlah_lembar || '') : (data.jumlah_lembar || ''));
            var tingkatVal = data.tingkat_perkembangan || '';
            var tingkatNormalized = tingkatVal.trim().toLowerCase();
            if (tingkatNormalized === 'asli') {
                tingkatVal = 'Asli';
            } else if (tingkatNormalized === 'fotokopi' || tingkatNormalized === 'foto kopi') {
                tingkatVal = 'Fotokopi';
            } else if (tingkatNormalized === 'salinan') {
                tingkatVal = 'Salinan';
            }
            document.getElementById('edit-sm-tingkat-perkembangan').value  = tingkatVal;
            document.getElementById('edit-sm-lokasi-simpan').value         = data.lokasi_simpan || '';

            var kodeRaw    = (data.kode_klasifikasi || '').trim();
            var kodeForMap = kodeRaw.replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,2);
            var mapData    = window.KODE_MAP && window.KODE_MAP[kodeForMap] ? window.KODE_MAP[kodeForMap] : null;
            var stdKodes   = ['PR','KP','OT','KU','HK','UM','PL','UP','HM','PW','LT','DL','DT','KI'];
            var editKodeSelEl    = document.getElementById('edit-sm-kode-klasifikasi');
            var editKodeCustEl   = document.getElementById('edit-sm-kode-klasifikasi-custom');
            var editKodeHintEl   = document.getElementById('edit-sm-kode-custom-hint');
            if (editKodeSelEl) {
                if (stdKodes.indexOf(kodeForMap) !== -1) {

                    editKodeSelEl.value = kodeForMap;
                    if (editKodeCustEl) { editKodeCustEl.style.display = 'none'; editKodeCustEl.value = ''; }
                    if (editKodeHintEl) editKodeHintEl.style.display = 'none';
                } else if (kodeRaw !== '') {

                    editKodeSelEl.value = '__lainnya__';
                    if (editKodeCustEl) { editKodeCustEl.style.display = 'block'; editKodeCustEl.value = kodeRaw; }
                    if (editKodeHintEl) editKodeHintEl.style.display = 'block';
                } else {
                    editKodeSelEl.value = '';
                    if (editKodeCustEl) { editKodeCustEl.style.display = 'none'; editKodeCustEl.value = ''; }
                    if (editKodeHintEl) editKodeHintEl.style.display = 'none';
                }
            }

            var autoFillLocked = !!mapData;
            var editAutoIds = ['edit-sm-retensi-aktif','edit-sm-retensi-inaktif','edit-sm-retensi-keterangan','edit-sm-klas-keamanan','edit-sm-tingkat-akses'];
            editAutoIds.forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                if (autoFillLocked) {
                    el.setAttribute('readonly', '');
                    el.style.background = 'var(--color-bg,#f8fafc)';
                    el.style.cursor = 'not-allowed';
                    el.style.opacity = '.85';
                } else {
                    el.removeAttribute('readonly');
                    el.style.background = '';
                    el.style.cursor = '';
                    el.style.opacity = '';
                }
            });
            document.getElementById('edit-sm-retensi-aktif').value         = mapData ? mapData[0] : (data.retensi_aktif       || '');
            document.getElementById('edit-sm-retensi-inaktif').value       = mapData ? mapData[1] : (data.retensi_inaktif     || '');
            document.getElementById('edit-sm-retensi-keterangan').value    = mapData ? mapData[2] : (data.retensi_keterangan  || '');
            document.getElementById('edit-sm-klas-keamanan').value         = mapData ? mapData[3] : (data.klasifikasi_keamanan|| '');
            document.getElementById('edit-sm-tingkat-akses').value         = mapData ? mapData[4] : (data.tingkat_akses        || '');
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
        if (modalEditSM) {
            modalEditSM.classList.remove('is-open');
            setTimeout(function () {
                if (!modalEditSM.classList.contains('is-open')) {
                    modalEditSM.style.display = 'none';
                }
            }, 240);
        }
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

    var BULAN_ID       = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var BULAN_ID_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    try {
        var fpInputSM = document.getElementById('tanggal_surat');
        if (typeof flatpickr !== 'undefined' && fpInputSM) {
            var currYearSM = new Date().getFullYear();
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
                minDate: new Date(currYearSM, 0, 1),
                maxDate: new Date(),
                allowInput: false,
                disableMobile: true,
            });

            var fpIconSM = document.querySelector('#tanggal-wrap-sm .date-icon');
            if (fpIconSM) fpIconSM.addEventListener('click', function() { window._fpSM.open(); });
        }
        var fpEditSM = document.getElementById('edit-sm-tanggal');
        if (typeof flatpickr !== 'undefined' && fpEditSM) {
            var currYearSM2 = new Date().getFullYear();
            window._fpEditSM = flatpickr(fpEditSM, {
                dateFormat: 'j F Y',
                locale: {
                    months: { longhand: BULAN_ID, shorthand: BULAN_ID_SHORT },
                    weekdays: {
                        longhand: ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
                        shorthand: ['Min','Sen','Sel','Rab','Kam','Jum','Sab']
                    },
                    firstDayOfWeek: 1
                },
                minDate: new Date(currYearSM2, 0, 1),
                maxDate: new Date(),
                allowInput: false,
                disableMobile: true,
            });
        }
    } catch(e) { console.warn('Flatpickr SM error:', e); }

    ['kode_surat', 'edit-sm-kode-surat'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            var v = this.value.replace(/[^0-9.]/g, '').replace(/\.{2,}/g, '.');
            if (this.value !== v) this.value = v;
        });
        el.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey || ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Enter','Home','End'].includes(e.key)) return;
            if (!/[0-9.]/.test(e.key)) e.preventDefault();
        });
    });

    window.RETENSI_MAP = window.KODE_MAP = {

        'PR': ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
        'KP': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'OT': ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
        'KU': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'HK': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'UM': ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
        'PL': ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
        'UP': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'HM': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'PW': ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
        'LT': ['5 Tahun',  '10 Tahun', 'Musnah',    'Biasa',   'Terbatas'],
        'DL': ['2 Tahun',  '3 Tahun',  'Musnah',    'Biasa',   'Terbuka' ],
        'DT': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
        'KI': ['5 Tahun',  '10 Tahun', 'Permanen',  'Biasa',   'Terbatas'],
    };

    var AUTO_FILL_IDS_SM = ['retensi_aktif','retensi_inaktif','retensi_keterangan','klasifikasi_keamanan','tingkat_akses'];
    function setAutoFillLocked(ids, locked) {
        ids.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (locked) {
                el.setAttribute('readonly', '');
                el.style.background  = 'var(--color-bg,#f8fafc)';
                el.style.cursor      = 'not-allowed';
                el.style.opacity     = '.85';
            } else {
                el.removeAttribute('readonly');
                el.style.background  = '';
                el.style.cursor      = '';
                el.style.opacity     = '';
            }
        });
    }

    function applyRetensiFromKode(kode) {
        var map    = window.KODE_MAP || {};
        var data   = map[kode] || null;
        var locked = !!data;
        setAutoFillLocked(AUTO_FILL_IDS_SM, locked);
        if (data) {
            var fields = {
                'retensi_aktif'        : data[0],
                'retensi_inaktif'      : data[1],
                'retensi_keterangan'   : data[2],
                'klasifikasi_keamanan' : data[3],
                'tingkat_akses'        : data[4],
            };
            Object.keys(fields).forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = fields[id];
            });
        } else {

            AUTO_FILL_IDS_SM.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) el.value = '';
            });
        }
    }

    var kodeSelectSM = document.getElementById('kode_klasifikasi');
    var kodeCustomSM = document.getElementById('kode_klasifikasi_custom_sm');
    var formSM = document.getElementById('arsip-form');

    function handleKodeSelectSM() {
        if (!kodeSelectSM) return;
        var val = kodeSelectSM.value;
        if (val === '__lainnya__') {
            if (kodeCustomSM) { kodeCustomSM.style.display = 'block'; kodeCustomSM.focus(); }
            applyRetensiFromKode('');
        } else {
            if (kodeCustomSM) { kodeCustomSM.style.display = 'none'; kodeCustomSM.value = ''; }
            applyRetensiFromKode(val.replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,2));
        }
    }

    if (kodeSelectSM) {
        kodeSelectSM.addEventListener('change', handleKodeSelectSM);

        handleKodeSelectSM();
    }

    function setCustomSelectValue(selectEl, customVal) {
        if (!selectEl) return;
        customVal = customVal.toUpperCase().trim();
        var optExists = Array.from(selectEl.options).some(function(opt) { return opt.value === customVal; });
        if (!optExists) {
            var newOpt = document.createElement('option');
            newOpt.value = customVal;
            newOpt.textContent = customVal;
            selectEl.appendChild(newOpt);
        }
        selectEl.value = customVal;
    }

    if (kodeCustomSM) {
        kodeCustomSM.addEventListener('input', function() {
            var v = this.value.toUpperCase().replace(/[^A-Z]/g,'').slice(0,2);
            if (this.value !== v) this.value = v;
            applyRetensiFromKode(v);
        });
    }

    if (formSM) {
        formSM.addEventListener('submit', function(e) {
            if (kodeSelectSM && kodeSelectSM.value === '__lainnya__') {
                var customVal = (kodeCustomSM ? kodeCustomSM.value.toUpperCase().trim() : '');
                if (!/^[A-Z]{2}$/.test(customVal)) {
                    e.preventDefault();
                    if (kodeCustomSM) {
                        kodeCustomSM.focus();
                        kodeCustomSM.style.borderColor = 'var(--color-danger, #ef4444)';
                    }
                    alert('Mohon isi kode klasifikasi kustom berupa 2 huruf kapital (contoh: XX).');
                    return;
                }
                setCustomSelectValue(kodeSelectSM, customVal);
            }
        });
    }

    var AUTO_FILL_IDS_EDIT_SM = ['edit-sm-retensi-aktif','edit-sm-retensi-inaktif','edit-sm-retensi-keterangan','edit-sm-klas-keamanan','edit-sm-tingkat-akses'];
    function setAutoFillLockedEditSM(locked) {
        AUTO_FILL_IDS_EDIT_SM.forEach(function(id) {
            var el = document.getElementById(id);
            if (!el) return;
            if (locked) {
                el.setAttribute('readonly', '');
                el.style.background = 'var(--color-bg,#f8fafc)';
                el.style.cursor     = 'not-allowed';
                el.style.opacity    = '.85';
            } else {
                el.removeAttribute('readonly');
                el.style.background = '';
                el.style.cursor     = '';
                el.style.opacity    = '';
            }
        });
    }

    var editKodeEl       = document.getElementById('edit-sm-kode-klasifikasi');
    var editKodeCustomEl = document.getElementById('edit-sm-kode-klasifikasi-custom');
    var editKodeHint     = document.getElementById('edit-sm-kode-custom-hint');

    if (editKodeEl) {
        editKodeEl.addEventListener('change', function() {
            var val = this.value;
            if (val === '__lainnya__') {
                if (editKodeCustomEl) { editKodeCustomEl.style.display = 'block'; editKodeCustomEl.focus(); }
                if (editKodeHint)     editKodeHint.style.display = 'block';
                applyKodeMapEdit('');
            } else {
                if (editKodeCustomEl) { editKodeCustomEl.style.display = 'none'; editKodeCustomEl.value = ''; }
                if (editKodeHint)     editKodeHint.style.display = 'none';
                var kode = val.replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,2);
                applyKodeMapEdit(kode);
            }
        });
    }
    if (editKodeCustomEl) {
        editKodeCustomEl.addEventListener('input', function() {
            var v = this.value.toUpperCase().replace(/[^A-Z]/g,'').slice(0,2);
            if (this.value !== v) this.value = v;
            applyKodeMapEdit(v);
        });
    }

    var formEditSMEl = document.getElementById('form-edit-sm');
    if (formEditSMEl) {
        formEditSMEl.addEventListener('submit', function(e) {
            if (editKodeEl && editKodeEl.value === '__lainnya__') {
                var customVal = (editKodeCustomEl ? editKodeCustomEl.value.toUpperCase().trim() : '');
                if (!/^[A-Z]{2}$/.test(customVal)) {
                    e.preventDefault();
                    if (editKodeCustomEl) editKodeCustomEl.focus();
                    alert('Mohon isi kode klasifikasi kustom berupa 2 huruf kapital (contoh: XX).');
                    return;
                }
                setCustomSelectValue(editKodeEl, customVal);
            }
        });
    }

    function applyKodeMapEdit(kode) {
        var map    = window.KODE_MAP || {};
        var data   = map[kode] || null;
        var locked = !!data;
        setAutoFillLockedEditSM(locked);
        var fields = {
            'edit-sm-retensi-aktif'      : data ? data[0] : null,
            'edit-sm-retensi-inaktif'    : data ? data[1] : null,
            'edit-sm-retensi-keterangan' : data ? data[2] : null,
            'edit-sm-klas-keamanan'      : data ? data[3] : null,
            'edit-sm-tingkat-akses'      : data ? data[4] : null,
        };
        Object.keys(fields).forEach(function(id) {
            var el  = document.getElementById(id);
            var val = fields[id];
            if (el && val !== null) el.value = val;
            else if (el && !locked) el.value = '';
        });
    }
})();

(function() {
    function formatJumlahLembarStr(val) {
        if (!val) return '';
        val = String(val).trim().replace(/\s+/g, ' ');
        if (/^\d+$/.test(val)) {
            return val + ' Lembar';
        }
        var m = val.match(/^(\d+)\s*lembar$/i);
        if (m) {
            return m[1] + ' Lembar';
        }
        return val;
    }
    window.formatJumlahLembarStr = formatJumlahLembarStr;

    function setupJumlahLembarAuto(inputEl, formEl) {
        if (!inputEl) return;

        function applyFormat() {
            var formatted = formatJumlahLembarStr(inputEl.value);
            if (formatted !== inputEl.value) {
                inputEl.value = formatted;
            }
        }

        inputEl.addEventListener('blur', applyFormat);
        inputEl.addEventListener('change', applyFormat);
        inputEl.addEventListener('focus', function () {
            if (/^\d+\s*lembar$/i.test(this.value.trim())) {
                this.select();
            }
        });

        if (formEl) {
            formEl.addEventListener('submit', function () {
                applyFormat();
            });
        }
    }

    setupJumlahLembarAuto(document.getElementById('jumlah_lembar'), document.getElementById('arsip-form'));
    setupJumlahLembarAuto(document.getElementById('edit-sm-jumlah-lembar'), document.getElementById('form-edit-sm'));
})();
</script>
@endpush

@endsection
