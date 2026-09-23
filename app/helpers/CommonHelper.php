<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class CommonHelper
{
    public static function customerOrderStatus(?string $status, ?string $rejectedBy): ?string
    {
        if ($status === 'rejected' && $rejectedBy === 'customer') {
            return 'cancelled';
        }

        return $status;
    }
    
    public static function apiResponse($code, $status, $message, $data, $is_array = false, $server_code = 200)
    {
        if ($is_array == true) {
            $responseData[] = $data;
        } else {
            $responseData = $data;
        }
        return response()->json([
            'code' => $code,
            'success' => $status,
            'message' => $message,
            'data' => $responseData
        ], $server_code);
    }

    public static function cleanSpecialCharacters($string)
    {
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $specialChars = [
            '–' => '-',
            '—' => '-',
            '‘' => "'",
            '’' => "'",
            '“' => '"',
            '”' => '"',
            '…' => '...',
            '•' => '-',
            "\xC2\xA0" => ' ',
        ];
        return trim(strtr($string, $specialChars));
    }

    public static function sendMail($to, $from, $subject, $view, $data = [])
    {
        Mail::send($view, $data, function ($message) use ($to, $from, $subject) {
            $message->to($to)->subject($subject);
            if ($from) {
                $message->from($from);
            }
        });
    }

    public static function checkForeignKeyActive($modelClass, $id)
    {
        return $modelClass::where('id', $id)->whereNull('deleted_at')->exists();
    }

    public static function addModule($moduleName)
    {
        try {
            if (DB::table('modules')->where('name', $moduleName)->exists()) {
                return false;
            }

            DB::table('modules')->insert(['name' => $moduleName]);
            return true;
        } catch (\Exception $e) {
            Log::error("Module addition failed: " . $e->getMessage());
            return false;
        }
    }

    public static function getPermission($moduleName, $permissionName = null)
    {
        try {
            $role_id = Auth::user()->user_role ?? null;

            if (!$role_id) {
                return false;
            }

            $module = DB::table('modules')->where('name', $moduleName)->first();
            if (!$module) {
                return false;
            }

            $permissions = DB::table('permissions')
                ->where('module_id', $module->id)
                ->where('role_id', $role_id)
                ->first();

            if (!$permissions) {
                return false;
            }

            if ($permissionName) {
                $permissionColumn = "{$permissionName}_permission";
                return isset($permissions->$permissionColumn) && $permissions->$permissionColumn == 1;
            }

            return $permissions->list_permission == 1
                || $permissions->create_permission == 1
                || $permissions->edit_permission == 1
                || $permissions->delete_permission == 1;
        } catch (\Exception $e) {
            Log::error("Permission check failed: " . $e->getMessage());
            return false;
        }
    }
    public static function hasAnyPermission($moduleName)
    {
        return self::getPermission($moduleName);
    }

    public static function hasAllPermissions($moduleName)
    {
        try {
            $role_id = Auth::user()->user_role ?? null;
            if (!$role_id) return false;

            $module = DB::table('modules')->where('name', $moduleName)->first();
            if (!$module) return false;

            $permissions = DB::table('permissions')
                ->where('module_id', $module->id)
                ->where('role_id', $role_id)
                ->first();

            return $permissions &&
                $permissions->list_permission == 1 &&
                $permissions->create_permission == 1 &&
                $permissions->edit_permission == 1 &&
                $permissions->delete_permission == 1;
        } catch (\Exception $e) {
            Log::error("Permission check failed: " . $e->getMessage());
            return false;
        }
    }

    public static function hasSpecificPermissions($moduleName, $actions = [])
    {
        foreach ($actions as $action) {
            if (!self::getPermission($moduleName, $action)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Chef payout basis for a set of orders — the single source of truth for
     * "what the chef earns / is owed". Uses each line item's menu base price
     * (food_dishes.base_price) x quantity across ALL items in the order, then
     * splits 90% payable to the chef / 10% held as the security deposit. The
     * gross dish total remains available separately for earnings displays.
     *
     * Falls back to backing the commission out of the stored line price when a
     * dish has no base_price (deleted dish / legacy order), or out of the whole
     * order price when the order has no item breakdown at all.
     *
     * @param  iterable $orders   Order models/rows (need ->items and ->price)
     * @param  float    $commRate Chef commission % — used for the fallback only
     * @return array{dish_total: float, security: float, net_payout: float}
     */
    public static function chefPayoutBreakdown($orders, float $commRate = 0): array
    {
        $divisor = 1 + (max($commRate, 0) / 100);

        // Preload every referenced dish's base_price in one query.
        $dishIds = [];
        foreach ($orders as $order) {
            $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
            if (is_array($items)) {
                foreach ($items as $it) {
                    if (isset($it['id'])) {
                        $dishIds[] = $it['id'];
                    }
                }
            }
        }
        $basePrices = !empty($dishIds)
            ? DB::table('food_dishes')->whereIn('id', array_unique($dishIds))->pluck('base_price', 'id')
            : collect();

        $dishTotal = 0.0;
        foreach ($orders as $order) {
            $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;

            if (!is_array($items) || empty($items)) {
                $dishTotal += ((float) $order->price) / $divisor;
                continue;
            }

            foreach ($items as $it) {
                $qty = (float) ($it['quantity'] ?? 1);
                $id  = $it['id'] ?? null;

                // New orders snapshot base_price so historical earnings cannot
                // change when a chef later edits or deletes a menu item. Legacy
                // orders fall back to the current dish record, then line price.
                $snapshotBase = (float) ($it['base_price'] ?? 0);
                $base = $snapshotBase > 0
                    ? $snapshotBase
                    : (($id !== null && isset($basePrices[$id]) && (float) $basePrices[$id] > 0)
                        ? (float) $basePrices[$id]
                        : ((float) ($it['price'] ?? 0)) / $divisor);

                $dishTotal += $base * $qty;
            }
        }

        return [
            'dish_total' => round($dishTotal, 2),
            'security'   => round($dishTotal * 0.10, 2),
            'net_payout' => round($dishTotal * 0.90, 2),
        ];
    }

    public static function getLatLong($address)
{
    $apiKey = config('services.google_maps.api_key');
    $url = 'https://maps.googleapis.com/maps/api/geocode/json';

    $response = Http::get($url, [
        'address' => $address,
        'key'     => $apiKey,
    ]);

    if ($response->successful() && isset($response['results'][0])) {
        $location = $response['results'][0]['geometry']['location'];
        return [
            'latitude'  => $location['lat'],
            'longitude' => $location['lng'],
        ];
    }

    // 👇 Agar geocoding fail ho, toh error throw karo
    throw new \Exception('Unable to fetch coordinates for the given address.');
}

}
