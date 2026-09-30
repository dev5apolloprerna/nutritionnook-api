<?php

use App\Services\ChefPayoutService;
use Carbon\Carbon;

it('uses the previous month end as the cutoff from the 7th through the 21st', function () {
    $cutoff = ChefPayoutService::payoutOrderCutoff(Carbon::parse('2026-08-07 10:00:00'));

    expect($cutoff->toDateString())->toBe('2026-07-31');
});

it('uses the current month 15th as the cutoff from the 22nd onward', function () {
    $cutoff = ChefPayoutService::payoutOrderCutoff(Carbon::parse('2026-08-22 10:00:00'));

    expect($cutoff->toDateString())->toBe('2026-08-15');
});

it('keeps the previous 22nd cutoff before the next 7th run', function () {
    $cutoff = ChefPayoutService::payoutOrderCutoff(Carbon::parse('2026-09-03 10:00:00'));

    expect($cutoff->toDateString())->toBe('2026-08-15');
});

it('reports the first half of the current month after the 22nd payout', function () {
    $cycle = ChefPayoutService::previousPayoutCycle(Carbon::parse('2026-09-30 12:00:00'));

    expect($cycle['start']->toDateString())->toBe('2026-09-01')
        ->and($cycle['end']->toDateString())->toBe('2026-09-15')
        ->and($cycle['payout_date']->toDateString())->toBe('2026-09-22');
});

it('reports the previous month second half after the 7th payout', function () {
    $cycle = ChefPayoutService::previousPayoutCycle(Carbon::parse('2026-09-07 10:00:00'));

    expect($cycle['start']->toDateString())->toBe('2026-08-16')
        ->and($cycle['end']->toDateString())->toBe('2026-08-31')
        ->and($cycle['payout_date']->toDateString())->toBe('2026-09-07');
});

it('reports the previous month first half before the 7th payout', function () {
    $cycle = ChefPayoutService::previousPayoutCycle(Carbon::parse('2026-09-06 23:59:59'));

    expect($cycle['start']->toDateString())->toBe('2026-08-01')
        ->and($cycle['end']->toDateString())->toBe('2026-08-15')
        ->and($cycle['payout_date']->toDateString())->toBe('2026-08-22');
});