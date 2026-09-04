<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Remission;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DriverController extends Controller
{
    /**
     * Get the list of available ambulances for the driver.
     * Returns ambulances with status='available' and documentation details.
     */
    public function myAmbulance(): JsonResponse
    {
        $ambulances = Ambulance::where('status', 'available')
            ->get()
            ->map(function ($ambulance) {
                $soatExpiresIn = $ambulance->soat_expires_at?->diffInDays(now());
                $techExpiresIn = $ambulance->tech_review_expires_at?->diffInDays(now());

                return [
                    'id' => $ambulance->id,
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
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ambulances,
        ], Response::HTTP_OK);
    }
}
