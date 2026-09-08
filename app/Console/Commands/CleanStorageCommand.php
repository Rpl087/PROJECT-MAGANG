<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * ============================================================
 * COMMAND: storage:clean
 * ============================================================
 *
 * Membersihkan file-file temporary di storage Laravel.
 * Dijalankan terjadwal setiap hari via Laravel Scheduler
 * (didaftarkan di bootstrap/app.php atau routes/console.php).
 *
 * YANG DIBERSIHKAN:
 *   1. Compiled views (storage/framework/views/*.php)
 *      → Diregenerasi otomatis saat halaman diakses berikutnya.
 *   2. Session files (storage/framework/sessions/*)
 *      → Menghapus sesi yang sudah tidak aktif.
 *      → File .gitignore dan .gitkeep TIDAK ikut dihapus.
 *   3. Cache data (via php artisan cache:clear)
 *      → Termasuk cache Google Sheets (SheetsCache).
 *
 * CARA MENJALANKAN MANUAL:
 *   php artisan storage:clean
 *
 * JADWAL OTOMATIS:
 *   Perintah ini dijadwalkan berjalan setiap hari (daily) via Laravel Scheduler.
 *   Untuk mengaktifkan scheduler di Windows, daftarkan di Task Scheduler:
 *     php artisan schedule:run
 *   Atau tambahkan ke cron (Linux):
 *     * * * * * php /path/to/artisan schedule:run
 *
 * DAMPAK:
 *   - User yang sedang aktif TIDAK otomatis logout (session DB tetap aman).
 *   - Cache Google Sheets bersih → request pertama setelah clean akan
 *     sedikit lebih lambat karena harus fetch ulang dari API.
 */
class CleanStorageCommand extends Command
{

    protected $signature = 'storage:clean';

    protected $description = 'Bersihkan berkas temporary pada storage/framework/views, sessions, dan cache/data';

    public function handle()
    {
        $this->info('Memulai pembersihan berkas temporary storage...');

        Artisan::call('view:clear');
        $this->info('✓ Compiled views berhasil dibersihkan.');

        $sessionPath = storage_path('framework/sessions');
        $sessionCount = 0;
        if (File::exists($sessionPath)) {
            foreach (File::files($sessionPath) as $file) {
                // Jangan hapus file system (.gitignore, .gitkeep)
                if (!in_array($file->getFilename(), ['.gitignore', '.gitkeep'])) {
                    File::delete($file->getPathname());
                    $sessionCount++;
                }
            }
        }
        $this->info("✓ Sesi temporary dibersihkan ({$sessionCount} berkas).");

        Artisan::call('cache:clear');
        $this->info('✓ Cache data berhasil dibersihkan.');

        Artisan::call('session:prune-expired');
        $this->info('✓ Sesi database yang kadaluarsa berhasil dibersihkan.');

        $this->output->success('Pembersihan storage selesai!');
        return Command::SUCCESS;
    }
}
