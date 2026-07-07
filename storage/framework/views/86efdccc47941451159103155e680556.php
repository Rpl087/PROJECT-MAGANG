<?php $__env->startSection('title', 'Surat Masuk'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header pre-hide" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
    <div>
        <h2 class="page-title">Surat Masuk</h2>
        <?php if($isAdmin ?? false): ?>
            <p class="page-description">Lihat daftar arsip tersimpan dan tambah surat masuk baru ke dalam sistem.</p>
        <?php else: ?>
            <p class="page-description">Gunakan formulir di bawah untuk mengarsipkan surat masuk ke dalam sistem.</p>
        <?php endif; ?>
    </div>
    
    <div class="live-date-badge" id="live-date-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span id="live-clock-sm">--:--:--</span>
        <span class="live-date-divider">|</span>
        <span id="live-date-text-sm"></span>
    </div>
</div>


<?php if($isAdmin ?? false): ?>
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

        <?php if(isset($fetchError)): ?>
            <div class="alert alert-error" style="margin:1rem 0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo e($fetchError); ?>

            </div>
        <?php endif; ?>

        
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
                    <?php
                        $tahunList = [];
                        foreach($rows as $item) {
                            $t = trim($item['data'][7] ?? '');
                            if(preg_match('/(\d{4})/', $t, $m)) $tahunList[$m[1]] = true;
                        }
                        krsort($tahunList);
                    ?>
                    <?php $__currentLoopData = $tahunList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $thn => $_): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($thn); ?>"><?php echo e($thn); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
            <?php for($i = 0; $i < 5; $i++): ?>
            <div class="skeleton-row">
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
                <div class="skeleton-cell skeleton-shimmer"></div>
            </div>
            <?php endfor; ?>
        </div>

        
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
                    <?php $__empty_1 = true; $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $no => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
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
                                $bulanIndo = ['januari'=>1,'februari'=>2,'maret'=>3,'april'=>4,'mei'=>5,'juni'=>6,'juli'=>7,'agustus'=>8,'september'=>9,'oktober'=>10,'november'=>11,'desember'=>12,'january'=>1,'february'=>2,'march'=>3,'may'=>5,'june'=>6,'july'=>7,'august'=>8,'october'=>10,'december'=>12];
                                preg_match('/\d{1,2}\s+(\w+)\s+\d{4}/i', $tanggal, $bm);
                                $bulanStr = strtolower($bm[1] ?? '');
                                $bulanNum = $bulanIndo[$bulanStr] ?? '';
                                $tahunNum = $dm[2];
                            } elseif (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $tanggal, $dm)) {
                                $tahunNum = $dm[1]; $bulanNum = (int)$dm[2];
                            } elseif (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $tanggal, $dm)) {
                                $bulanNum = (int)$dm[2]; $tahunNum = $dm[3];
                            }
                        ?>
                        <tr data-bulan="<?php echo e($bulanNum); ?>" data-tahun="<?php echo e($tahunNum); ?>" data-kode="<?php echo e($kodePrefix); ?>">
                            <td class="td-center td-no row-no"><?php echo e($no + 1); ?></td>
                            <td class="td-nowrap"><?php echo e($tanggal); ?></td>
                            <td><span class="badge-kode <?php echo e($kodeKlas === '—' ? 'badge-kode-empty' : ''); ?>"><?php echo e($kodeKlas); ?></span></td>
                            <td class="td-uraian"><?php echo e(Str::limit($uraian, 60)); ?></td>
                            <td class="td-center">
                                <?php if($pdfUrl): ?>
                                    <a href="<?php echo e($pdfUrl); ?>" target="_blank" class="btn-pdf" title="Lihat PDF">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                        PDF
                                    </a>
                                <?php else: ?>
                                    <span class="td-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="td-center">
                                <button type="button" class="btn-hapus"
                                    data-form="delete-sm-<?php echo e($item['index']); ?>"
                                    data-label="surat masuk baris ke-<?php echo e($no + 1); ?>"
                                    title="Hapus data ini">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    Hapus
                                </button>
                                <form id="delete-sm-<?php echo e($item['index']); ?>"
                                      action="<?php echo e(route('arsip.destroy', $item['index'])); ?>"
                                      method="POST" style="display:none;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="td-empty">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>Belum ada data surat masuk tersimpan.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>


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


<?php if($isAdmin ?? false): ?>
<div class="sm-form-divider">
    <div class="sm-form-divider-line"></div>
    <span class="sm-form-divider-label">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
        Tambah Surat Masuk Baru
    </span>
    <div class="sm-form-divider-line"></div>
</div>
<?php endif; ?>


<form action="<?php echo e(route('arsip.store')); ?>" method="POST" enctype="multipart/form-data" class="form-container" id="arsip-form">
    <?php echo csrf_field(); ?>

    
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
                <input type="text" name="kode_klasifikasi" id="kode_klasifikasi" class="form-input <?php $__errorArgs = ['kode_klasifikasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('kode_klasifikasi')); ?>" placeholder="Contoh: PR, PL, KP, dll." required>
                <?php $__errorArgs = ['kode_klasifikasi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="kode_surat" class="form-label">Kode Surat <span class="required">*</span></label>
                <input type="text" name="kode_surat" id="kode_surat" class="form-input <?php $__errorArgs = ['kode_surat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('kode_surat')); ?>" placeholder="Contoh: 02.02" required>
                <?php $__errorArgs = ['kode_surat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="tanggal_surat" class="form-label">Tanggal Surat <span class="required">*</span></label>
                <?php
                    // Tanggal hari ini dalam format Indonesia (misal: 7 Juli 2026)
                    $_bulanID = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    $_todaySM = now()->day . ' ' . $_bulanID[now()->month] . ' ' . now()->year;
                ?>
                
                <div class="date-input-wrap" id="tanggal-wrap-sm">
                    <input type="text" name="tanggal_surat" id="tanggal_surat"
                           class="form-input <?php $__errorArgs = ['tanggal_surat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                           value="<?php echo e(old('tanggal_surat') ?: $_todaySM); ?>"
                           placeholder="Contoh: 11 Mei 2026"
                           autocomplete="off" required
                           data-input>
                    <span class="date-icon" data-toggle style="cursor:pointer; pointer-events:auto;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </span>
                </div>
                <?php $__errorArgs = ['tanggal_surat'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="form-grid form-grid-2">
            <div class="form-group">
                <label for="uraian_informasi_berkas" class="form-label">Uraian Informasi Berkas <span class="required">*</span></label>
                <textarea name="uraian_informasi_berkas" id="uraian_informasi_berkas" class="form-input form-textarea <?php $__errorArgs = ['uraian_informasi_berkas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Contoh: Surat perihal pengajuan anggaran kegiatan pelatihan SDM triwulan I" rows="3" required><?php echo e(old('uraian_informasi_berkas')); ?></textarea>
                <?php $__errorArgs = ['uraian_informasi_berkas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="uraian_informasi_arsip" class="form-label">Uraian Informasi Arsip <span class="required">*</span></label>
                <textarea name="uraian_informasi_arsip" id="uraian_informasi_arsip" class="form-input form-textarea <?php $__errorArgs = ['uraian_informasi_arsip'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Contoh: Surat masuk dari Kementerian KOMINFO tentang pelaksanaan ujian sertifikasi" rows="3" required><?php echo e(old('uraian_informasi_arsip')); ?></textarea>
                <?php $__errorArgs = ['uraian_informasi_arsip'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                       class="form-input <?php $__errorArgs = ['kurun_waktu'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                       value="<?php echo e(old('kurun_waktu')); ?>"
                       placeholder="Contoh: 2024"
                       min="1900" max="2100" required>
                <?php $__errorArgs = ['kurun_waktu'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="jumlah_folder" class="form-label">Jumlah Folder <span class="required">*</span></label>
                <input type="text" name="jumlah_folder" id="jumlah_folder" class="form-input <?php $__errorArgs = ['jumlah_folder'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('jumlah_folder')); ?>" placeholder="Contoh: 3 folder" required>
                <?php $__errorArgs = ['jumlah_folder'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="nomor_item_arsip" class="form-label">Nomor: Item Arsip <span class="required">*</span></label>
                <input type="number" name="nomor_item_arsip" id="nomor_item_arsip" class="form-input <?php $__errorArgs = ['nomor_item_arsip'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('nomor_item_arsip')); ?>" placeholder="Contoh: 1" min="1" required>
                <?php $__errorArgs = ['nomor_item_arsip'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="jumlah_lembar" class="form-label">Jumlah Lembar <span class="required">*</span></label>
                <input type="text" name="jumlah_lembar" id="jumlah_lembar" class="form-input <?php $__errorArgs = ['jumlah_lembar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('jumlah_lembar')); ?>" placeholder="Contoh: 5 Lembar" required>
                <?php $__errorArgs = ['jumlah_lembar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="tingkat_perkembangan" class="form-label">Tingkat Perkembangan <span class="required">*</span></label>
                <input type="text" name="tingkat_perkembangan" id="tingkat_perkembangan" class="form-input <?php $__errorArgs = ['tingkat_perkembangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('tingkat_perkembangan')); ?>" placeholder="Contoh: Asli / Fotokopi / Salinan" required>
                <?php $__errorArgs = ['tingkat_perkembangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group form-group-upload">
                <label for="surat_masuk" class="form-label">Surat Masuk (PDF) <span class="required">*</span></label>
                <label for="surat_masuk" class="file-upload <?php $__errorArgs = ['surat_masuk'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> file-upload-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="upload-zone">
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
                <?php $__errorArgs = ['surat_masuk'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <span class="error-text"><?php echo e($message); ?></span>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
                <input type="text" name="lokasi_simpan" id="lokasi_simpan" class="form-input <?php $__errorArgs = ['lokasi_simpan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('lokasi_simpan')); ?>" placeholder="Contoh: OneDrive" required>
                <?php $__errorArgs = ['lokasi_simpan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="klasifikasi_keamanan" class="form-label">Klasifikasi Keamanan <span class="required">*</span></label>
                <select name="klasifikasi_keamanan" id="klasifikasi_keamanan" class="form-input form-select <?php $__errorArgs = ['klasifikasi_keamanan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <option value="" disabled <?php echo e(old('klasifikasi_keamanan') ? '' : 'selected'); ?>>— Pilih —</option>
                    <option value="Biasa" <?php echo e(old('klasifikasi_keamanan') == 'Biasa' ? 'selected' : ''); ?>>Biasa</option>
                    <option value="Terbatas" <?php echo e(old('klasifikasi_keamanan') == 'Terbatas' ? 'selected' : ''); ?>>Terbatas</option>
                    <option value="Rahasia" <?php echo e(old('klasifikasi_keamanan') == 'Rahasia' ? 'selected' : ''); ?>>Rahasia</option>
                    <option value="Sangat Rahasia" <?php echo e(old('klasifikasi_keamanan') == 'Sangat Rahasia' ? 'selected' : ''); ?>>Sangat Rahasia</option>
                </select>
                <?php $__errorArgs = ['klasifikasi_keamanan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="tingkat_akses" class="form-label">Tingkat Akses <span class="required">*</span></label>
                <select name="tingkat_akses" id="tingkat_akses" class="form-input form-select <?php $__errorArgs = ['tingkat_akses'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <option value="" disabled <?php echo e(old('tingkat_akses') ? '' : 'selected'); ?>>— Pilih —</option>
                    <option value="Terbuka" <?php echo e(old('tingkat_akses') == 'Terbuka' ? 'selected' : ''); ?>>Terbuka</option>
                    <option value="Tertutup" <?php echo e(old('tingkat_akses') == 'Tertutup' ? 'selected' : ''); ?>>Tertutup</option>
                    <option value="Terbatas" <?php echo e(old('tingkat_akses') == 'Terbatas' ? 'selected' : ''); ?>>Terbatas</option>
                </select>
                <?php $__errorArgs = ['tingkat_akses'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
        </div>

        <div class="form-grid form-grid-3">
            <div class="form-group">
                <label for="retensi_aktif" class="form-label">Retensi — Aktif <span class="required">*</span></label>
                <input type="text" name="retensi_aktif" id="retensi_aktif" class="form-input <?php $__errorArgs = ['retensi_aktif'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('retensi_aktif')); ?>" placeholder="Contoh: 2 Tahun" required>
                <?php $__errorArgs = ['retensi_aktif'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="retensi_inaktif" class="form-label">Retensi — Inaktif <span class="required">*</span></label>
                <input type="text" name="retensi_inaktif" id="retensi_inaktif" class="form-input <?php $__errorArgs = ['retensi_inaktif'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('retensi_inaktif')); ?>" placeholder="Contoh: 5 Tahun" required>
                <?php $__errorArgs = ['retensi_inaktif'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <div class="form-group">
                <label for="retensi_keterangan" class="form-label">Retensi — Keterangan <span class="required">*</span></label>
                <input type="text" name="retensi_keterangan" id="retensi_keterangan" class="form-input <?php $__errorArgs = ['retensi_keterangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> input-error <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('retensi_keterangan')); ?>" placeholder="Contoh: Musnah / Permanen" required>
                <?php $__errorArgs = ['retensi_keterangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="error-text"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
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
<?php $__env->startPush('styles'); ?>
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
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
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

    function applyFilter() {
        if (!tbody) return;
        const bulan = selBulan ? selBulan.value : '';
        const tahun = selTahun ? selTahun.value : '';
        const rows  = tbody.querySelectorAll('tr[data-bulan]');
        let visible = 0;
        rows.forEach(function (tr) {
            const rb = tr.dataset.bulan;
            const rt = tr.dataset.tahun;
            const show = (!bulan || rb == bulan) && (!tahun || rt == tahun);
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
            countEl.textContent = (bulan || tahun) ? (visible + ' dari ' + total + ' data ditampilkan') : '';
        }
    }

    if (selBulan) selBulan.addEventListener('change', applyFilter);
    if (selTahun) selTahun.addEventListener('change', applyFilter);
    if (btnReset) btnReset.addEventListener('click', function () {
        if (selBulan) selBulan.value = '';
        if (selTahun) selTahun.value = '';
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
});
</script>
<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
(function() {
    // ── Live Clock (berjalan independen, tidak bergantung flatpickr) ─────────────────────────────
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

    // ── Flatpickr (di-init setelah DOM ready, di dalam try-catch) ─────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        var BULAN_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        var BULAN_ID_SHORT = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        try {
            if (typeof flatpickr !== 'undefined' && document.getElementById('tanggal-wrap-sm')) {
                window._fpSM = flatpickr('#tanggal-wrap-sm', {
                    wrap: true,
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
            }
        } catch(e) { console.warn('Flatpickr SM init error:', e); }
    });
})();
</script>
<?php $__env->stopPush(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\project-magang\resources\views/arsip/index.blade.php ENDPATH**/ ?>