<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOldUploads extends Command
{
    protected $signature = 'uploads:prune-old {--days=30}';

    protected $description = 'Delete upload files and DB rows older than N days (finished_at).';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $uploads = Upload::query()
            ->whereNotNull('finished_at')
            ->where('finished_at', '<=', $cutoff)
            ->whereNotIn('status', ['processing'])
            ->get();

        $this->info('Found ' . $uploads->count() . ' uploads to prune.');

        $deleted = 0;
        foreach ($uploads as $u) {
            if ($u->file_path && Storage::disk('local')->exists($u->file_path)) {
                Storage::disk('local')->delete($u->file_path);
            }
            $u->delete();
            $deleted++;
        }

        $this->info("Pruned {$deleted} uploads.");

        return 0;
    }
}
