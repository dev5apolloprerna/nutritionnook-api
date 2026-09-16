<?php

namespace App\Helpers;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;

class UserAddressHelper
{
    public static function getSelectedAddress(?int $userId = null): ?UserAddress
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return null;
        }

        return UserAddress::forUser($userId)->selected()->first();
    }

    public static function getCoordinates(?int $userId = null): ?array
    {
        $address = self::getSelectedAddress($userId);
        
        if (!$address) {
            return null;
        }

        return [
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
        ];
    }
}
