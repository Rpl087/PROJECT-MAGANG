<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

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
