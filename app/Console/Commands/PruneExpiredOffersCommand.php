<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;

class PruneExpiredOffersCommand extends Command
{
    protected $signature = 'offers:prune-expired';

    protected $description = 'Delete offers that have passed their expiry time';

    public function handle(): int
    {
        $deleted = Offer::query()->where('expires_at', '<=', now())->delete();
        $this->info("Deleted {$deleted} expired offers.");

        return self::SUCCESS;
    }
}
