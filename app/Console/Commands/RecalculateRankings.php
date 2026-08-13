<?php

namespace App\Console\Commands;

use App\Services\RankingService;
use Illuminate\Console\Command;

class RecalculateRankings extends Command
{
    protected $signature = 'rankings:recalculate';

    protected $description = 'Rebuild supplier rankings (items count + discount quality index)';

    public function handle(RankingService $rankingService): int
    {
        $count = $rankingService->recalculateAll();

        $this->info("Recalculated rankings for {$count} suppliers.");

        return self::SUCCESS;
    }
}
