<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PruneExpiredSessionsCommand extends Command
{
    protected $signature = 'session:prune-expired {--minutes= : Batas masa aktif dalam menit (default: sesuai config session.lifetime)}';

    protected $description = 'Hapus record session yang telah kadaluarsa dari tabel database sessions';

    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: config('session.lifetime', 240));
        $expiredTimestamp = now()->subMinutes($minutes)->getTimestamp();
        $tableName = config('session.table', 'sessions');

        $this->info("Memeriksa sesi yang tidak aktif lebih dari {$minutes} menit (sebelum " . date('Y-m-d H:i:s', $expiredTimestamp) . ")...");

        $dbCount = 0;
        if (Schema::hasTable($tableName)) {
            $dbCount = DB::table($tableName)
                ->where('last_activity', '<', $expiredTimestamp)
                ->delete();

            $this->info("✓ Sesi database dibersihkan: {$dbCount} record terhapus dari tabel `{$tableName}`.");
        } else {
            $this->warn("Tabel `{$tableName}` tidak ditemukan di database.");
        }

        $fileCount = 0;
        $sessionPath = storage_path('framework/sessions');
        if (File::exists($sessionPath)) {
            foreach (File::files($sessionPath) as $file) {
                if (!in_array($file->getFilename(), ['.gitignore', '.gitkeep'])) {
                    if ($file->getMTime() < $expiredTimestamp) {
                        File::delete($file->getPathname());
                        $fileCount++;
                    }
                }
            }
            if ($fileCount > 0) {
                $this->info("✓ Sesi file storage dibersihkan: {$fileCount} file kadaluarsa terhapus.");
            }
        }

        Log::info('Pembersihan session kadaluarsa selesai.', [
            'expired_db_sessions'   => $dbCount,
            'expired_file_sessions' => $fileCount,
            'lifetime_minutes'      => $minutes,
        ]);

        $this->output->success("Pembersihan selesai: {$dbCount} sesi database kadaluarsa berhasil dihapus.");
        return Command::SUCCESS;
    }
}
