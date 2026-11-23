<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;

class PruneExpiredOffersCommand extends Command
{
    protected $signature = 'offers:prune-expired
        {--chunk=1000 : Rows per batch}
        {--sleep=100 : Milliseconds between batches}
        {--days=0 : Only process offers expired for at least N days}
        {--delete : Physically delete instead of deactivating}
        {--dry-run : Count eligible offers without changing data}';

    protected $description = 'Deactivate or delete expired offers in bounded batches';

    public function handle(): int
    {
        $chunk = max(100, (int) $this->option('chunk'));
        $sleepMs = max(0, (int) $this->option('sleep'));
        $cutoff = now()->subDays((int) $this->option('days'));
        $delete = (bool) $this->option('delete');
        $action = $delete ? 'Deleted' : 'Deactivated';

        $query = Offer::query()->where('expires_at', '<=', $cutoff);

        if (! $delete) {
            $query->where('is_active', true);
        }

        if ($this->option('dry-run')) {
            $this->info("Would process {$query->count()} expired offers.");

            return self::SUCCESS;
        }

        $total = 0;

        $query->chunkById($chunk, function ($offers) use (&$total, $delete, $action, $sleepMs) {
            $ids = $offers->modelKeys();

            if ($delete) {
                Offer::query()->whereKey($ids)->delete();
            } else {
                Offer::query()->whereKey($ids)->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);
            }

            $total += count($ids);
            $this->info("{$action} {$total} expired offers...");

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        });

        $this->info("Done. {$action} {$total} expired offers.");

        return self::SUCCESS;
    }
}
