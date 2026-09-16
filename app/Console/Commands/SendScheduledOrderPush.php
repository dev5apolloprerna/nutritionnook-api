<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScheduledOrderPushService;
use Illuminate\Support\Facades\Log;

class SendScheduledOrderPush extends Command
{
    protected $signature = 'orders:send-scheduled-push';
    protected $description = 'Send push notifications for scheduled orders';

    public function handle(ScheduledOrderPushService $service)
    {
        Log::info('[ScheduledPush] Command executed manually');

        $service->send();

        $this->info('Scheduled order push completed.');
    }
}
