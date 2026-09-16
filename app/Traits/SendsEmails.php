<?php
// app/Traits/SendsEmails.php

namespace App\Traits;

use App\Mail\OrderCreatedMail;
use App\Mail\OrderStatusChangedMail;
use App\Mail\ChefRegistrationMail;
use App\Mail\CustomerRegistrationMail;
use App\Mail\ChefPayoutMail;
use App\Mail\OrderRefundMail;
use App\Mail\OrderReminderMail;
use App\Mail\PasswordResetMail;
use App\Models\Order;
use App\Models\User;
use App\Models\Chef;
use App\Models\Payout;
use App\Models\Refund;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

trait SendsEmails
{
    /**
     * Send order created email to customer
     */
    public function sendOrderCreatedEmail(Order $order, User $user, Chef $chef)
    {
        try {
            Mail::to($user->email)->send(new OrderCreatedMail($order, $user, $chef));
            Log::info('Order created email sent to customer: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send order created email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order status changed email
     */
    public function sendOrderStatusChangedEmail(Order $order, $user, $oldStatus, $newStatus)
    {
        try {
            // Send to customer
            if ($user && isset($user->email)) {
                Mail::to($user->email)->send(new OrderStatusChangedMail($order, $user, $oldStatus, $newStatus));
            }
            
            // Send to chef as well for certain status changes
            if (in_array($newStatus, ['accepted', 'rejected', 'preparing', 'ready'])) {
                $chef = Chef::find($order->chef_id);
                if ($chef && $chef->email) {
                    Mail::to($chef->email)->send(new OrderStatusChangedMail($order, $chef, $oldStatus, $newStatus));
                }
            }
            
            Log::info('Order status changed email sent for order: ' . $order->id);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send order status email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send chef registration welcome email
     */
    public function sendChefRegistrationEmail(Chef $chef, $password = null)
    {
        try {
            Mail::to($chef->email)->send(new ChefRegistrationMail($chef, $password));
            Log::info('Chef registration email sent to: ' . $chef->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send chef registration email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send customer registration welcome email
     */
    public function sendCustomerRegistrationEmail(User $user)
    {
        try {
            Mail::to($user->email)->send(new CustomerRegistrationMail($user));
            Log::info('Customer registration email sent to: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send customer registration email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send chef payout email
     */
    public function sendChefPayoutEmail(Chef $chef, Payout $payout, $status = 'processed')
    {
        try {
            Mail::to($chef->email)->send(new ChefPayoutMail($chef, $payout, $status));
            Log::info('Chef payout email sent to: ' . $chef->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send chef payout email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order refund email
     */
    public function sendOrderRefundEmail(Order $order, User $user, Refund $refund, $refundAmount)
    {
        try {
            Mail::to($user->email)->send(new OrderRefundMail($order, $user, $refund, $refundAmount));
            Log::info('Refund email sent to customer: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send refund email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send order reminder email
     */
    public function sendOrderReminderEmail(Order $order, User $user)
    {
        try {
            Mail::to($user->email)->send(new OrderReminderMail($order, $user));
            Log::info('Order reminder email sent to: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send order reminder email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($user, $token, $userType = 'customer')
    {
        try {
            Mail::to($user->email)->send(new PasswordResetMail($user, $token, $userType));
            Log::info('Password reset email sent to: ' . $user->email);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bulk send order reminders to customers with pending orders
     */
    public function sendBulkOrderReminders()
    {
        $pendingOrders = Order::whereIn('status', ['new', 'accepted', 'preparing'])
            ->where('created_at', '>', now()->subDays(1))
            ->with('user')
            ->get();

        foreach ($pendingOrders as $order) {
            if ($order->user && $order->user->email) {
                $this->sendOrderReminderEmail($order, $order->user);
            }
        }
    }
}