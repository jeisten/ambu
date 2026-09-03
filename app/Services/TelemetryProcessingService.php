<?php

namespace App\Services;

use App\Events\LocationUpdated;
use App\Models\Location;
use App\Models\Remission;
use Illuminate\Support\Facades\DB;

class TelemetryProcessingService
{
    /**
     * Minimum distance delta in km to filter out GPS jitter/noise (1 meter).
     */
    public const MIN_DISTANCE_THRESHOLD_KM = 0.001;

    /**
     * Create a new telemetry processing service instance.
     *
     * @param HaversineDistanceService $distanceService
     */
    public function __construct(
        protected HaversineDistanceService $distanceService
    ) {}

    /**
     * Process and record a telemetry location point for an active remission.
     *
     * @param Remission $remission
     * @param array<string, mixed> $data
     * @return Location
     */
    public function recordLocation(Remission $remission, array $data): Location
    {
        return DB::transaction(function () use ($remission, $data) {
            $lastLocation = $remission->locations()
                ->latest('recorded_at')
                ->first();

            $currentLat = (float) $data['latitude'];
            $currentLon = (float) $data['longitude'];

            if ($lastLocation !== null) {
                $incrementalKm = $this->distanceService->calculate(
                    (float) $lastLocation->latitude,
                    (float) $lastLocation->longitude,
                    $currentLat,
                    $currentLon
                );

                if ($incrementalKm > self::MIN_DISTANCE_THRESHOLD_KM) {
                    $remission->total_kilometers = round(((float) $remission->total_kilometers) + $incrementalKm, 3);
                    $remission->save();
                }
            }

            /** @var Location $location */
            $location = $remission->locations()->create([
                'ambulance_id' => $data['ambulance_id'] ?? $remission->ambulance_id,
                'latitude' => $currentLat,
                'longitude' => $currentLon,
                'speed' => isset($data['speed']) && $data['speed'] !== null ? (float) $data['speed'] : null,
                'heading' => isset($data['heading']) && $data['heading'] !== null ? (float) $data['heading'] : null,
                'recorded_at' => $data['recorded_at'] ?? now(),
            ]);

            LocationUpdated::dispatch($location, (float) $remission->total_kilometers);

            return $location;
        });
    }
}
