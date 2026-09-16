<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payout;
use App\Models\Order;
use App\Models\Chef;
use App\Models\Refund;
use App\Services\FCMService;
use Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RazorpayWebhookController extends Controller
{
    public function handlePayout(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';
        $webhookSecret = config('services.razorpay.webhook_secret', '');

        if (!empty($webhookSecret)) {
            $signature = $request->header('X-Razorpay-Signature', '');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Razorpay Webhook: Invalid signature');
                return response()->json(['status' => 'invalid_signature'], 400);
            }
        }

        Log::info("Razorpay Webhook received: {$event}", ['payload_id' => $payload['payload']['payout']['entity']['id'] ?? 'unknown']);

        switch ($event) {
            case 'payout.processed':
                return $this->handlePayoutProcessed($payload);
            case 'payout.failed':
                return $this->handlePayoutFailed($payload);
            case 'payout.reversed':
                return $this->handlePayoutReversed($payload);
            case 'payout.queued':
                return $this->handlePayoutQueued($payload);
            default:
                Log::info("Razorpay Webhook: Unhandled event {$event}");
                return response()->json(['status' => 'ignored'], 200);
        }
    }

    /**
     * Route target for POST /webhooks/razorpay/refund. Refunds are created
     * synchronously in RefundService::refundOrder() when an admin rejects an
     * order; this webhook is Razorpay's async confirmation that the refund
     * actually settled (or failed) on their side, since a refund can stay in
     * "processed" from the initial API call for days before it's final.
     */
    public function handleRefund(Request $request)
    {
        $payload = $request->all();
        $event = $payload['event'] ?? '';
        $webhookSecret = config('services.razorpay.webhook_secret', '');

        if (!empty($webhookSecret)) {
            $signature = $request->header('X-Razorpay-Signature', '');
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Razorpay Refund Webhook: Invalid signature');
                return response()->json(['status' => 'invalid_signature'], 400);
            }
        }

        $entity = $payload['payload']['refund']['entity'] ?? [];
        $razorpayRefundId = $entity['id'] ?? null;

        Log::info("Razorpay Refund Webhook received: {$event}", ['refund_id' => $razorpayRefundId ?? 'unknown']);

        if (!$razorpayRefundId) {
            return response()->json(['status' => 'missing_refund_id'], 400);
        }

        $refund = Refund::where('razorpay_refund_id', $razorpayRefundId)->first();
        if (!$refund) {
            Log::warning("Razorpay Refund Webhook: Refund not found for ID {$razorpayRefundId}");
            return response()->json(['status' => 'not_found'], 404);
        }

        switch ($event) {
            case 'refund.processed':
                // Idempotency: Razorpay retries a webhook if the previous delivery was
                // slow/failed. Without this check, a retry re-stamps refunded_at with a
                // later timestamp every time it fires.
                if ($refund->status === 'processed') {
                    Log::info("Refund #{$refund->id} webhook: already processed, skipping duplicate update");
                    return response()->json(['status' => 'already_processed'], 200);
                }

                $refund->update([
                    'status' => 'processed',
                    'gateway_response' => json_encode($entity),
                ]);

                if ($refund->order && !$refund->order->is_refunded) {
                    $refund->order->update([
                        'is_refunded' => true,
                        'refunded_at' => now(),
                    ]);
                }

                Log::info("Refund #{$refund->id} confirmed PROCESSED via webhook | Order #{$refund->order_id}");
                return response()->json(['status' => 'processed'], 200);

            case 'refund.failed':
                if ($refund->status === 'failed') {
                    Log::info("Refund #{$refund->id} webhook: already recorded as failed, skipping duplicate update");
                    return response()->json(['status' => 'already_processed'], 200);
                }

                $failureReason = $entity['status_details']['description'] ?? 'Unknown failure from Razorpay';

                $refund->update([
                    'status' => 'failed',
                    'reason' => $failureReason,
                    'gateway_response' => json_encode($entity),
                ]);

                Log::error("Refund #{$refund->id} FAILED via webhook | Order #{$refund->order_id} | Reason: {$failureReason}");
                return response()->json(['status' => 'failed_recorded'], 200);

            default:
                Log::info("Razorpay Refund Webhook: Unhandled event {$event}");
                return response()->json(['status' => 'ignored'], 200);
        }
    }

    protected function handlePayoutProcessed(array $payload)
    {
        $razorpayPayoutId = $payload['payload']['payout']['entity']['id'] ?? null;
        if (!$razorpayPayoutId) {
            return response()->json(['status' => 'missing_payout_id'], 400);
        }

        $payout = Payout::where('razorpay_payout_id', $razorpayPayoutId)->first();
        if (!$payout) {
            Log::warning("Razorpay Webhook: Payout not found for ID {$razorpayPayoutId}");
            return response()->json(['status' => 'not_found'], 404);
        }

        // Idempotency: Razorpay retries a webhook if the previous delivery was
        // slow/failed. Without this check, every retry re-sends the "Payout
        // Received!" push notification and email to the chef.
        if ($payout->status === 'success') {
            Log::info("Payout #{$payout->id} webhook: already marked success, skipping duplicate notification");
            return response()->json(['status' => 'already_processed'], 200);
        }

        $payout->update([
            'status' => 'success',
            'failure_reason' => null,
        ]);

        Log::info("Payout #{$payout->id} marked SUCCESS via webhook | Chef #{$payout->chef_id} | ₹{$payout->payout_amount}");

        try {
            $chef = Chef::find($payout->chef_id);
            $monthName = $payout->payout_month
                ? date('F Y', mktime(0, 0, 0, $payout->payout_month, 1, $payout->payout_year))
                : now()->format('F Y');

            $setting = DB::table('settings')->first();
            $logo = ($setting && $setting->logo)
                ? url('public/images/' . $setting->logo)
                : '';

            $notifyTitle = 'Payout Received!';
            $notifyBody  = "Your payout of ₹" . number_format($payout->payout_amount, 2) . " for {$monthName} has been credited to your bank account.";

            $notifyData = [
                'title' => $notifyTitle,
                'body'  => $notifyBody,
                'type'  => 'payout_success',
                'payout_id' => (string) $payout->id,
                'amount' => (string) $payout->payout_amount,
                'month' => $monthName,
                'order_count' => (string) $payout->order_count,
                'logo' => $logo,
            ];

            (new FCMService())->sendNotificationToUser(
                $payout->chef_id,
                $notifyTitle,
                $notifyBody,
                $notifyData
            );

            Log::info("Payout success notification sent to Chef #{$payout->chef_id}");
            
            Mail::send('emails.payout-success', [
                    'name' => $chef->name ?? 'Chef',
                    'amount' => number_format($payout->payout_amount, 2),
                    'month' => $monthName,
                    'orderCount' => $payout->order_count
                ], function ($message) use ($chef, $monthName) {

                    $message->to($chef->email)
                        ->subject("Payout Received for {$monthName} 💰");

                });
            
        } catch (\Throwable $e) {
            Log::error("Failed to send payout notification to Chef #{$payout->chef_id}: " . $e->getMessage());
        }

        return response()->json(['status' => 'processed'], 200);
    }

    // protected function handlePayoutFailed(array $payload)
    // {
    //     $entity = $payload['payload']['payout']['entity'] ?? [];
    //     $razorpayPayoutId = $entity['id'] ?? null;
    //     if (!$razorpayPayoutId) {
    //         return response()->json(['status' => 'missing_payout_id'], 400);
    //     }

    //     $payout = Payout::where('razorpay_payout_id', $razorpayPayoutId)->first();
    //     if (!$payout) {
    //         Log::warning("Razorpay Webhook: Payout not found for ID {$razorpayPayoutId}");
    //         return response()->json(['status' => 'not_found'], 404);
    //     }

    //     $failureReason = $entity['failure_reason'] ?? $entity['status_details']['description'] ?? 'Unknown failure from Razorpay';

    //     $payout->update([
    //         'status' => 'failed',
    //         'failure_reason' => $failureReason,
    //     ]);

    //     Order::where('payout_id', $payout->id)->update([
    //         'is_payout_completed' => 0,
    //         'payout_id' => null,
    //     ]);

    //     Log::error("Payout #{$payout->id} FAILED via webhook | Chef #{$payout->chef_id} | Reason: {$failureReason}");
    //     return response()->json(['status' => 'failed_recorded'], 200);
    // }
    
    protected function handlePayoutFailed(array $payload)
{
    $entity = $payload['payload']['payout']['entity'] ?? [];
    $razorpayPayoutId = $entity['id'] ?? null;

    if (!$razorpayPayoutId) {
        return response()->json(['status' => 'missing_payout_id'], 400);
    }

    $payout = Payout::where('razorpay_payout_id', $razorpayPayoutId)->first();
    if (!$payout) {
        Log::warning("Razorpay Webhook: Payout not found for ID {$razorpayPayoutId}");
        return response()->json(['status' => 'not_found'], 404);
    }

    // Idempotency: skip if this event was already handled (see handlePayoutProcessed).
    if ($payout->status === 'failed') {
        Log::info("Payout #{$payout->id} webhook: already marked failed, skipping duplicate notification");
        return response()->json(['status' => 'already_processed'], 200);
    }

    $failureReason = $entity['failure_reason'] ?? $entity['status_details']['description'] ?? 'Unknown failure from Razorpay';

    $payout->update([
        'status' => 'failed',
        'failure_reason' => $failureReason,
    ]);

    Order::where('payout_id', $payout->id)->update([
        'is_payout_completed' => 0,
        'payout_id' => null,
    ]);

    Log::error("Payout #{$payout->id} FAILED via webhook | Chef #{$payout->chef_id} | Reason: {$failureReason}");

    try {

        $chef = Chef::find($payout->chef_id);

        $monthName = $payout->payout_month
            ? date('F Y', mktime(0, 0, 0, $payout->payout_month, 1, $payout->payout_year))
            : now()->format('F Y');

        $setting = DB::table('settings')->first();
        $logo = ($setting && $setting->logo)
            ? url('public/images/' . $setting->logo)
            : '';

        $notifyTitle = 'Payout Failed ❌';
        $notifyBody  = "Your payout of ₹" . number_format($payout->payout_amount, 2) . " for {$monthName} could not be processed. Reason: {$failureReason}";

        $notifyData = [
            'title' => $notifyTitle,
            'body'  => $notifyBody,
            'type'  => 'payout_failed',
            'payout_id' => (string) $payout->id,
            'amount' => (string) $payout->payout_amount,
            'month' => $monthName,
            'reason' => $failureReason,
            'logo' => $logo,
        ];

        (new FCMService())->sendNotificationToUser(
            $payout->chef_id,
            $notifyTitle,
            $notifyBody,
            $notifyData
        );

        Log::info("Payout failure notification sent to Chef #{$payout->chef_id}");

        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL TO CHEF
        |--------------------------------------------------------------------------
        */

        if ($chef) {
                Mail::send('emails.payout-failed', [
                    'name' => $user->name ?? 'Chef',
                    'amount' => number_format($payout->payout_amount, 2),
                    'month' => $monthName,
                    'reason' => $failureReason
                ], function ($message) use ($chef, $monthName) {

                    $message->to($chef->email)
                        ->subject("Payout Failed for {$monthName} ❌");

                });

                Log::info("Payout failure email sent to Chef #{$chef->id}");
        }

    } catch (\Throwable $e) {

        Log::error("Failed to send payout failure notification/email to Chef #{$payout->chef_id}: " . $e->getMessage());

    }

    return response()->json(['status' => 'failed_recorded'], 200);
}

    protected function handlePayoutReversed(array $payload)
    {
        $razorpayPayoutId = $payload['payload']['payout']['entity']['id'] ?? null;
        if (!$razorpayPayoutId) {
            return response()->json(['status' => 'missing_payout_id'], 400);
        }

        $payout = Payout::where('razorpay_payout_id', $razorpayPayoutId)->first();
        if (!$payout) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $payout->update([
            'status' => 'reversed',
            'failure_reason' => 'Payout was reversed by bank or Razorpay',
        ]);

        Order::where('payout_id', $payout->id)->update([
            'is_payout_completed' => 0,
            'payout_id' => null,
        ]);

        Log::warning("Payout #{$payout->id} REVERSED via webhook | Chef #{$payout->chef_id}");
        return response()->json(['status' => 'reversed_recorded'], 200);
    }

    protected function handlePayoutQueued(array $payload)
    {
        $razorpayPayoutId = $payload['payload']['payout']['entity']['id'] ?? null;
        if (!$razorpayPayoutId) {
            return response()->json(['status' => 'missing_payout_id'], 400);
        }

        $payout = Payout::where('razorpay_payout_id', $razorpayPayoutId)->first();
        if ($payout && $payout->status !== 'success') {
            $payout->update(['status' => 'queued']);
            Log::info("Payout #{$payout->id} QUEUED (low balance) via webhook | Chef #{$payout->chef_id}");
        }

        return response()->json(['status' => 'queued_recorded'], 200);
    }
}
