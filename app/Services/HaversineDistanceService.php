<?php

namespace App\Services;

class HaversineDistanceService
{
    /**
     * Mean Earth radius in kilometers (IUGG recommended value).
     */
    public const EARTH_RADIUS_KM = 6371.0088;

    /**
     * Calculate the great-circle distance between two geographic points using the Haversine formula.
     *
     * @param float $lat1 Latitude of origin point in degrees
     * @param float $lon1 Longitude of origin point in degrees
     * @param float $lat2 Latitude of destination point in degrees
     * @param float $lon2 Longitude of destination point in degrees
     * @return float Distance in kilometers rounded to 4 decimal places
     */
    public function calculate(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if ($lat1 === $lat2 && $lon1 === $lon2) {
            return 0.0;
        }

        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) ** 2 +
            cos($lat1Rad) * cos($lat2Rad) * (sin($deltaLon / 2) ** 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round(self::EARTH_RADIUS_KM * $c, 4);
    }
}
