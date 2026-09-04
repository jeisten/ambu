<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remission;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverController extends Controller
{
    /**
     * Get the current ambulance assigned to the authenticated driver.
     * Returns ambulance details including documentation status.
     */
    public function myAmbulance(): JsonResponse
    {
        $driver = auth()->user();

        // Get the active remission for this driver
        $activeRemission = Remission::where('driver_id', $driver->id)
            ->whereIn('status', ['en_camino', 'trasladando'])
            ->first();

        if (!$activeRemission) {
            return response()->json([
                'success' => false,
                'message' => 'No hay remisión activa asignada.',
            ], Response::HTTP_NOT_FOUND);
        }

        $ambulance = $activeRemission->ambulance;
        $soatExpiresIn = $ambulance->soat_expires_at?->diffInDays(now());
        $techExpiresIn = $ambulance->tech_review_expires_at?->diffInDays(now());

        return response()->json([
            'success' => true,
            'data' => [
                'ambulance_id' => $ambulance->id,
                'plate' => $ambulance->plate,
                'brand' => $ambulance->brand,
                'model' => $ambulance->model,
                'status' => $ambulance->status,
                'km_per_gallon' => (float) $ambulance->km_per_gallon,
                'documentation' => [
                    'soat_expires_at' => $ambulance->soat_expires_at?->toDateString(),
                    'soat_expires_in_days' => $soatExpiresIn,
                    'tech_review_expires_at' => $ambulance->tech_review_expires_at?->toDateString(),
                    'tech_review_expires_in_days' => $techExpiresIn,
                    'soat_alert' => $soatExpiresIn !== null && $soatExpiresIn <= 5,
                    'tech_alert' => $techExpiresIn !== null && $techExpiresIn <= 5,
                ],
            ],
        ], Response::HTTP_OK);
    }
}
