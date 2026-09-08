<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * GOOGLE DRIVE SERVICE
 * ============================================================
 *
 * Mengelola operasi file PDF ke Google Drive menggunakan Service Account.
 * Setiap file PDF yang diarsipkan disimpan di DUA tempat:
 *   1. Google Drive (folder yang dikonfigurasi via GOOGLE_DRIVE_FOLDER_ID)
 *   2. Backup lokal di folder uploads/ di root project
 *
 * KONVENSI NAMA FILE:
 *   Format: {KODE_KLASIFIKASI}_{TIMESTAMP}_{NAMA_ASLI}.pdf
 *   Contoh: KP_20240901_143022_SuratPermohonan.pdf
 *
 * MEKANISME FALLBACK:
 *   Jika upload Google Drive GAGAL (quota habis, API tidak aktif, dll),
 *   sistem TIDAK melempar error ke user. Data tetap tersimpan ke Sheets
 *   dengan kolom link berisi NAMA FILE LOKAL (bukan URL Drive).
 *   File lokal (uploads/) bisa diakses via route /pdf/{type}/{filename}.
 *
 * KONFIGURASI (via .env):
 *   GOOGLE_SERVICE_ACCOUNT_JSON = path/ke/credentials.json
 *   GOOGLE_DRIVE_FOLDER_ID      = ID folder Drive yang dibagikan ke Service Account
 *
 * IZIN FILE DI DRIVE:
 *   Setelah upload, file langsung diset public (anyone reader) agar
 *   link Drive bisa dibuka siapa saja tanpa perlu login Google.
 *   Jika gagal set izin, file tetap tersimpan tapi linknya mungkin private.
 *
 * CATATAN QUOTA:
 *   Service Account di akun Google Drive pribadi memiliki kuota terbatas.
 *   Gunakan Google Workspace / Shared Drive untuk kapasitas lebih besar.
 *   Error 403 storageQuotaExceeded berarti Drive penuh → sistem fallback ke lokal.
 */
class GoogleDriveService
{

    /**
     * Upload file PDF ke Google Drive sekaligus simpan backup di lokal (uploads/).
     *
     * @param UploadedFile $file            File PDF yang diupload user
     * @param string       $kodeKlasifikasi Kode klasifikasi arsip (untuk nama file)
     * @return array ['filename' => string, 'driveLink' => string]
     *               driveLink berisi URL Drive jika berhasil, atau nama file lokal jika gagal
     */
    public static function uploadAndBackup(UploadedFile $file, string $kodeKlasifikasi): array
    {

        $kodeKlasSlug  = preg_replace('/[^a-zA-Z0-9\-]/', '_', $kodeKlasifikasi);
        $originalName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalSlug  = preg_replace('/[^a-zA-Z0-9\-]/', '_', $originalName);
        $timestamp     = now()->format('Ymd_His');
        $filename      = strtoupper($kodeKlasSlug) . '_' . $timestamp . '_' . $originalSlug . '.' . $file->getClientOriginalExtension();

        $backupDir = base_path('uploads');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $file->move($backupDir, $filename);
        $localFilePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $driveLink = $filename;

        try {

            $credPath = config('google.service_account_json');
            if (empty($credPath)) {
                throw new \RuntimeException('GOOGLE_SERVICE_ACCOUNT_JSON belum dikonfigurasi di .env');
            }
            $credFullPath = base_path($credPath);
            if (!file_exists($credFullPath) || !is_file($credFullPath)) {
                throw new \RuntimeException("File kredensial Google tidak ditemukan: {$credFullPath}");
            }

            $client = new Client();
            $client->setAuthConfig($credFullPath);
            $client->addScope(Drive::DRIVE);
            $driveService = new Drive($client);

            $parentFolderId = config('google.drive_folder_id');
            if (empty($parentFolderId)) {
                throw new \RuntimeException('GOOGLE_DRIVE_FOLDER_ID belum dikonfigurasi di .env. Service Account memerlukan ID folder tujuan yang dibagikan.');
            }

            $driveFileMetadata = new DriveFile([
                'name'    => $filename,
                'parents' => [$parentFolderId]
            ]);

            $fileContents = file_get_contents($localFilePath);
            if ($fileContents === false) {
                throw new \RuntimeException("Gagal membaca file untuk upload ke Drive: {$localFilePath}");
            }
            $driveFile = $driveService->files->create($driveFileMetadata, [
                'data'                => $fileContents,
                'mimeType'            => 'application/pdf',
                'uploadType'          => 'multipart',
                'supportsAllDrives'   => true,
                'supportsTeamDrives'  => true,
                'fields'              => 'id, webViewLink',
            ]);

            $fileId    = $driveFile->id;
            $driveLink = $driveFile->webViewLink;

            try {
                $permission = new Permission();
                $permission->setType('anyone');
                $permission->setRole('reader');
                $driveService->permissions->create($fileId, $permission, [
                    'supportsAllDrives'  => true,
                    'supportsTeamDrives' => true,
                ]);
            } catch (\Exception $permissionEx) {
                Log::warning('Gagal menyetel izin publik untuk file di Google Drive: ' . $permissionEx->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Google Drive Upload gagal (mungkin API belum di-enable). Menggunakan backup lokal. Error: ' . $e->getMessage());

        }

        return [
            'filename'  => $filename,
            'driveLink' => $driveLink
        ];
    }

    /**
     * Mengubah nama file PDF baik di lokal maupun di Google Drive.
     * Dipanggil saat admin mengedit kode_klasifikasi pada arsip yang sudah ada.
     *
     * LOGIKA:
     *   - Jika $oldLinkOrFilename adalah URL (starts with http) → rename di Drive via API
     *   - Jika bukan URL → rename file di lokal (uploads/ directory)
     *   - Nama baru dibentuk dari kode baru + timestamp/suffix dari nama lama
     *
     * @param string $oldLinkOrFilename URL Drive atau nama file lokal
     * @param string $newKodeKlasifikasi Kode klasifikasi baru
     * @return string URL Drive yang sama (jika Drive), atau nama file lokal baru
     */
    public static function renameFile(string $oldLinkOrFilename, string $newKodeKlasifikasi): string
    {
        $oldLinkOrFilename = trim($oldLinkOrFilename);
        if (empty($oldLinkOrFilename)) {
            return $oldLinkOrFilename;
        }

        $isDriveUrl  = str_starts_with($oldLinkOrFilename, 'http');
        $oldFilename = $oldLinkOrFilename;
        $driveFileId = null;

        if ($isDriveUrl) {
            if (preg_match('/\/d\/([a-zA-Z0-9_\-]+)/', $oldLinkOrFilename, $matches)) {
                $driveFileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_\-]+)/', $oldLinkOrFilename, $matches)) {
                $driveFileId = $matches[1];
            }
        }

        $kodeKlasSlug = strtoupper(preg_replace('/[^a-zA-Z0-9\-]/', '_', $newKodeKlasifikasi));

        if (!$isDriveUrl && preg_match('/^[A-Z0-9_]+_(\d{8}_\d{6}_.+)$/i', $oldFilename, $parts)) {
            $newFilename = $kodeKlasSlug . '_' . $parts[1];
        } else {
            $timestamp   = now()->format('Ymd_His');
            $cleanOld    = preg_replace('/[^a-zA-Z0-9\.\-]/', '_', pathinfo($oldFilename, PATHINFO_FILENAME));
            $ext         = pathinfo($oldFilename, PATHINFO_EXTENSION) ?: 'pdf';
            $newFilename = $kodeKlasSlug . '_' . $timestamp . '_' . $cleanOld . '.' . $ext;
        }

        $uploadsDir   = base_path('uploads');
        $oldLocalPath = $uploadsDir . DIRECTORY_SEPARATOR . $oldFilename;
        $newLocalPath = $uploadsDir . DIRECTORY_SEPARATOR . $newFilename;

        if (!$isDriveUrl && File::exists($oldLocalPath)) {
            File::move($oldLocalPath, $newLocalPath);
        }

        if ($driveFileId !== null) {
            try {
                $credPath = config('google.service_account_json');
                if (!empty($credPath) && file_exists(base_path($credPath))) {
                    $client = new Client();
                    $client->setAuthConfig(base_path($credPath));
                    $client->addScope(Drive::DRIVE);
                    $driveService = new Drive($client);

                    $driveFileMetadata = new DriveFile(['name' => $newFilename]);
                    $driveService->files->update($driveFileId, $driveFileMetadata, [
                        'supportsAllDrives'  => true,
                        'supportsTeamDrives' => true,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Gagal mengubah nama file di Google Drive: ' . $e->getMessage());
            }
            return $oldLinkOrFilename;
        }

        return $newFilename;
    }

    /**
     * Menghapus file PDF dari lokal DAN Google Drive.
     * Dipanggil saat admin menghapus data arsip.
     *
     * LOGIKA:
     *   - Jika $linkOrFilename adalah URL Drive → hapus dari Drive + cari & hapus salinan lokal
     *   - Jika bukan URL → hapus langsung dari uploads/
     *   - Kegagalan penghapusan dari Drive hanya dicatat ke log (tidak throw exception)
     *
     * @param string $linkOrFilename URL Drive atau nama file lokal
     * @return bool Selalu true (kegagalan Drive tidak dianggap fatal)
     */
    public static function deleteFile(string $linkOrFilename): bool
    {
        $linkOrFilename = trim($linkOrFilename);
        if (empty($linkOrFilename)) {
            return false;
        }

        $isDriveUrl  = str_starts_with($linkOrFilename, 'http');
        $filename    = $linkOrFilename;
        $driveFileId = null;

        if ($isDriveUrl) {
            if (preg_match('/\/d\/([a-zA-Z0-9_\-]+)/', $linkOrFilename, $matches)) {
                $driveFileId = $matches[1];
            } elseif (preg_match('/id=([a-zA-Z0-9_\-]+)/', $linkOrFilename, $matches)) {
                $driveFileId = $matches[1];
            }
        }

        $uploadsDir = base_path('uploads');

        if (!$isDriveUrl) {
            $localPath = $uploadsDir . DIRECTORY_SEPARATOR . $filename;
            if (File::exists($localPath)) {
                File::delete($localPath);
            }
        }

        if ($driveFileId !== null) {
            try {
                $credPath = config('google.service_account_json');
                if (!empty($credPath) && file_exists(base_path($credPath))) {
                    $client = new Client();
                    $client->setAuthConfig(base_path($credPath));
                    $client->addScope(Drive::DRIVE);
                    $driveService = new Drive($client);

                    try {
                        $driveFile = $driveService->files->get($driveFileId, [
                            'fields'             => 'name',
                            'supportsAllDrives'  => true,
                            'supportsTeamDrives' => true,
                        ]);
                        $driveName = $driveFile->name ?? null;
                        if (!empty($driveName)) {
                            $localPath = $uploadsDir . DIRECTORY_SEPARATOR . $driveName;
                            if (File::exists($localPath)) {
                                File::delete($localPath);
                            }
                        }
                    } catch (\Exception $getEx) {
                        Log::warning('Gagal mengambil nama file dari Google Drive: ' . $getEx->getMessage());
                    }

                    $driveService->files->delete($driveFileId, [
                        'supportsAllDrives'  => true,
                        'supportsTeamDrives' => true,
                    ]);
                    Log::info("File PDF berhasil dihapus dari Google Drive (ID: {$driveFileId})");
                }
            } catch (\Exception $e) {
                Log::warning('Gagal menghapus file dari Google Drive: ' . $e->getMessage());
            }
        }

        return true;
    }
}
