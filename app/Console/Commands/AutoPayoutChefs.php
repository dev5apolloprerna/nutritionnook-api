<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ChefPayoutService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoPayoutChefs extends Command
{
    protected $signature = 'payout:auto-chefs';
    protected $description = 'Run auto payouts for chefs on 7th and 22nd';

    protected $payoutService;

    public function __construct(ChefPayoutService $payoutService)
    {
        parent::__construct();
        $this->payoutService = $payoutService;
    }

    public function handle()
    {
        $today = Carbon::now()->day;

        // Double check strict date (though the scheduler already restricts this).
        if ($today != 7 && $today != 22) {
            $this->info("Today is not a payout date (7th or 22nd). Skipping.");
            return self::SUCCESS;
        }

        $this->info('Starting Auto Payout...');
        Log::info('[AutoPayoutChefs] Scheduled bulk payout run started');

        // runBulkPayout() returns an array:
        //   ['processed' => int, 'skipped' => int, 'failed' => int, 'details' => string[]]
        $results = $this->payoutService->runBulkPayout();

        $summary = sprintf(
            'Auto Payout finished — processed: %d, skipped: %d, failed: %d',
            $results['processed'] ?? 0,
            $results['skipped'] ?? 0,
            $results['failed'] ?? 0
        );

        $this->info($summary);
        foreach (($results['details'] ?? []) as $line) {
            $this->line('  - ' . $line);
        }

        Log::info('[AutoPayoutChefs] ' . $summary, ['details' => $results['details'] ?? []]);

        return self::SUCCESS;
    }
}