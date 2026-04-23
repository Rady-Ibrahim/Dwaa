<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\NormalizerService;
use Illuminate\Console\Command;

class ComputePhoneticKeys extends Command
{
    protected $signature = 'products:compute-phonetic-keys';

    protected $description = 'Compute phonetic_key for all existing products without it';

    public function handle(NormalizerService $normalizer)
    {
        $this->info('Computing phonetic keys for existing products...');

        $total = Product::query()->whereNull('phonetic_key')->count();
        if ($total === 0) {
            $this->info('All products already have phonetic keys. ✅');

            return;
        }

        $this->info("Found {$total} products to process.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Product::query()
            ->whereNull('phonetic_key')
            ->chunkById(500, function ($products) use ($normalizer, $bar) {
                foreach ($products as $product) {
                    $product->update([
                        'phonetic_key' => $normalizer->phoneticConsonantKey(
                            $product->name_ar ?? ''
                        ),
                    ]);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->info("\n\nDone! All products now have phonetic keys. ✅");
    }
}
