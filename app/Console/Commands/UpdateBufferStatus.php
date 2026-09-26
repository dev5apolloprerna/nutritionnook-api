<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class UpdateBufferStatus extends Command
{
    protected $signature = 'orders:update-buffer-status';

    protected $description = 'Release paid orders to chefs after the two-minute customer cancellation buffer';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(2);
        $updated = 0;

        Order::query()
            ->where('is_buffer', 0)
            ->where('payment_status', 'received')
            ->where('status', '!=', 'rejected')
            ->where('created_at', '<=', $cutoff)
            ->select('id')
            ->chunkById(500, function ($orders) use (&$updated): void {
                $updated += Order::query()
                    ->whereIn('id', $orders->pluck('id'))
                    // Re-apply the guards so a concurrent rejection cannot be released.
                    ->where('is_buffer', 0)
                    ->where('payment_status', 'received')
                    ->where('status', '!=', 'rejected')
                    ->update(['is_buffer' => 1]);
            });

        $this->info("Released {$updated} paid order(s) to chefs.");

        return self::SUCCESS;
    }
}