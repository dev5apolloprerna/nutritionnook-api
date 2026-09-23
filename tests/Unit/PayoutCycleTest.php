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