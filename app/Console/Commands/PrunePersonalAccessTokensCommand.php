<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrunePersonalAccessTokensCommand extends Command
{
    protected $signature = 'sanctum:prune-tokens {--days=30} {--chunk=1000} {--dry-run}';

    protected $description = 'Delete personal access tokens that are expired or inactive for N days';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $chunk = max(1, (int) $this->option('chunk'));
        $inactiveBefore = now()->subDays($days);
        $expiredBefore = now()->subHour();

        $query = DB::table('personal_access_tokens')
            ->where(function ($query) use ($inactiveBefore, $expiredBefore) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', $expiredBefore)
                    ->orWhere(function ($query) use ($inactiveBefore) {
                        $query->whereNull('expires_at')
                            ->whereRaw(
                                'COALESCE(last_used_at, updated_at, created_at) < ?',
                                [$inactiveBefore]
                            );
                    });
            });

        if ($this->option('dry-run')) {
            $this->info("Would delete {$query->count()} personal access tokens.");

            return self::SUCCESS;
        }

        $total = 0;

        do {
            $deleted = (clone $query)
                ->orderBy('id')
                ->limit($chunk)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        $this->info("Deleted {$total} personal access tokens.");

        return self::SUCCESS;
    }
}
