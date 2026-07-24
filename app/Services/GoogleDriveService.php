<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    /**
     * Upload file ke Google Drive dan backup ke local uploads/ directory di root project.
     *
     * @param UploadedFile $file
     * @param string $kodeKlasifikasi
     * @return array [filename, driveLink]
     */
    public static function uploadAndBackup(UploadedFile $file, string $kodeKlasifikasi): array
    {
        // 1. Generate filename yang unik & informatif
        $kodeKlasSlug  = preg_replace('/[^a-zA-Z0-9\-]/', '_', $kodeKlasifikasi);
        $originalName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $originalSlug  = preg_replace('/[^a-zA-Z0-9\-]/', '_', $originalName);
        $timestamp     = now()->format('Ymd_His');
        $filename      = strtoupper($kodeKlasSlug) . '_' . $timestamp . '_' . $originalSlug . '.' . $file->getClientOriginalExtension();

        // 2. Backup lokal ke folder uploads/ di root project (diluar sub-subfolder)
        $backupDir = base_path('uploads');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        // Simpan salinan ke root uploads/
        $file->move($backupDir, $filename);
        $localFilePath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        // Default fallback link is just the local filename (which triggers local view routing)
        $driveLink = $filename;

        try {
            // 3. Inisialisasi Google Drive API Client
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
            $client->addScope(Drive::DRIVE); // Memerlukan scope Drive
            $driveService = new Drive($client);

            // Ambil Folder ID dari config (aman saat config:cache — tidak pakai env() langsung)
            $parentFolderId = config('google.drive_folder_id');
            if (empty($parentFolderId)) {
                throw new \RuntimeException('GOOGLE_DRIVE_FOLDER_ID belum dikonfigurasi di .env. Service Account memerlukan ID folder tujuan yang dibagikan.');
            }

            // 4. Metadata file untuk Google Drive
            $driveFileMetadata = new DriveFile([
                'name'    => $filename,
                'parents' => [$parentFolderId]
            ]);

            // 5. Kirim file ke Google Drive — gunakan fopen() untuk streaming agar tidak
            //    membaca seluruh file ke RAM (BUG-02 fix: mencegah memory exhaustion file besar)
            $fileHandle = fopen($localFilePath, 'rb');
            if ($fileHandle === false) {
                throw new \RuntimeException("Gagal membuka file untuk upload: {$localFilePath}");
            }
            $driveFile = $driveService->files->create($driveFileMetadata, [
                'data'                => $fileHandle,
                'mimeType'            => 'application/pdf',
                'uploadType'          => 'multipart',
                'supportsAllDrives'   => true,
                'supportsTeamDrives'  => true,
                'fields'              => 'id, webViewLink',
            ]);
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }

            $fileId    = $driveFile->id;
            $driveLink = $driveFile->webViewLink;

            // 6. Set permission agar file bisa diakses oleh siapa saja yang memiliki link (anyone with link as reader)
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
            // driveLink tetap berupa $filename (fallback lokal)
        }

        return [
            'filename'  => $filename,
            'driveLink' => $driveLink
        ];
    }
}
