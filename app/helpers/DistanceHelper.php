<?php

namespace App\Helpers;

class DistanceHelper
{
    public static function haversine(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        string $unit = 'km'
    ): float {
        $earthRadius = $unit === 'km' ? 6371 : 3959;

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * asin(sqrt($a));

        return round($earthRadius * $c, 2);
    }

    public static function getDistanceSelectRaw(
        float $userLat,
        float $userLng,
        string $latColumn = 'latitude',
        string $lngColumn = 'longitude',
        bool $round = false
    ): string {
        $formula = "(6371 * acos(
            LEAST(1.0, GREATEST(-1.0,
                cos(radians({$userLat})) *
                cos(radians({$latColumn})) *
                cos(radians({$lngColumn}) - radians({$userLng})) +
                sin(radians({$userLat})) *
                sin(radians({$latColumn}))
            ))
        ))";

        return $round ? "ROUND({$formula}, 1)" : $formula;
    }

    public static function getDistanceWhereRaw(
        float $userLat,
        float $userLng,
        float $radius,
        string $latColumn = 'latitude',
        string $lngColumn = 'longitude'
    ): array {
        $formula = "(6371 * acos(
            LEAST(1.0, GREATEST(-1.0,
                cos(radians({$userLat})) *
                cos(radians({$latColumn})) *
                cos(radians({$lngColumn}) - radians({$userLng})) +
                sin(radians({$userLat})) *
                sin(radians({$latColumn}))
            ))
        ))";

        return ["{$formula} <= ?", [$radius]];
    }

    public static function getDistanceBindingRaw(
        string $latColumn = 'latitude',
        string $lngColumn = 'longitude'
    ): string {
        return "(6371 * acos(
            cos(radians(?)) *
            cos(radians({$latColumn})) *
            cos(radians({$lngColumn}) - radians(?)) +
            sin(radians(?)) *
            sin(radians({$latColumn}))
        ))";
    }
}
