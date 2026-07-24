<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class CleanStorageCommand extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     *
     * @var string
     */
    protected $signature = 'storage:clean';

    /**
     * Deskripsi perintah console.
     *
     * @var string
     */
    protected $description = 'Bersihkan berkas temporary pada storage/framework/views, sessions, dan cache/data';

    /**
     * Eksekusi perintah console.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan berkas temporary storage...');

        // 1. Views
        Artisan::call('view:clear');
        $this->info('✓ Compiled views berhasil dibersihkan.');

        // 2. Sessions
        $sessionPath = storage_path('framework/sessions');
        $sessionCount = 0;
        if (File::exists($sessionPath)) {
            foreach (File::files($sessionPath) as $file) {
                if (!in_array($file->getFilename(), ['.gitignore', '.gitkeep'])) {
                    File::delete($file->getPathname());
                    $sessionCount++;
                }
            }
        }
        $this->info("✓ Sesi temporary dibersihkan ({$sessionCount} berkas).");

        // 3. Cache Data — artisan cache:clear sudah cukup menangani seluruh cache
        //    (BUG-05 fix: loop manual setelah cache:clear bersifat redundan dan berisiko)
        Artisan::call('cache:clear');
        $this->info('✓ Cache data berhasil dibersihkan.');

        $this->output->success('Pembersihan storage selesai!');
        return Command::SUCCESS;
    }
}
