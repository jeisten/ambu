<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ambulance;
use App\Models\Remission;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StatsController extends Controller
{
    /**
     * Get fleet health and distribution metrics.
     *
     * @return JsonResponse
     */
    public function fleet(): JsonResponse
    {
        $totalAmbulances = Ambulance::count();
        $availableAmbulances = Ambulance::where('status', 'available')->count();
        $inServiceAmbulances = Ambulance::where('status', 'in_service')->count();
        $maintenanceAmbulances = Ambulance::where('status', 'maintenance')->count();

        // Expiring documents count within 5 days
        $expiringCount = Ambulance::expiringDocs(5)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_ambulances' => $totalAmbulances,
                'available' => $availableAmbulances,
                'in_service' => $inServiceAmbulances,
                'maintenance' => $maintenanceAmbulances,
                'expiring_documentation_5_days' => $expiringCount,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get performance metrics for a specific ambulance.
     *
     * @param Ambulance $ambulance
     * @return JsonResponse
     */
    public function ambulance(Ambulance $ambulance): JsonResponse
    {
        $remissions = $ambulance->remissions();

        $totalTrips = (clone $remissions)->count();
        $completedTrips = (clone $remissions)->where('status', 'finalizado')->count();
        $totalKm = (float) (clone $remissions)->where('status', 'finalizado')->sum('total_kilometers');
        $totalFuel = (float) (clone $remissions)->where('status', 'finalizado')->sum('fuel_consumed_gallons');
        $tripsInCity = (clone $remissions)->where('is_out_of_city', false)->count();
        $tripsOutOfCity = (clone $remissions)->where('is_out_of_city', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'ambulance_id' => $ambulance->id,
                'plate' => $ambulance->plate,
                'brand' => $ambulance->brand,
                'model' => $ambulance->model,
                'km_per_gallon' => $ambulance->km_per_gallon,
                'status' => $ambulance->status,
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'total_kilometers' => round($totalKm, 3),
                'total_fuel_consumed_gallons' => round($totalFuel, 3),
                'trips_in_city' => $tripsInCity,
                'trips_out_of_city' => $tripsOutOfCity,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get global operational remission metrics.
     *
     * @return JsonResponse
     */
    public function remissions(): JsonResponse
    {
        $totalRemissions = Remission::count();
        $completedRemissions = Remission::where('status', 'finalizado')->count();
        $activeRemissions = Remission::active()->count();
        $cancelledRemissions = Remission::where('status', 'cancelado')->count();

        $totalKmTravelled = (float) Remission::where('status', 'finalizado')->sum('total_kilometers');
        $totalFuelConsumed = (float) Remission::where('status', 'finalizado')->sum('fuel_consumed_gallons');
        $outOfCityTrips = Remission::where('is_out_of_city', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_remissions' => $totalRemissions,
                'active_in_progress' => $activeRemissions,
                'completed' => $completedRemissions,
                'cancelled' => $cancelledRemissions,
                'total_kilometers' => round($totalKmTravelled, 3),
                'total_fuel_consumed_gallons' => round($totalFuelConsumed, 3),
                'out_of_city_transfers' => $outOfCityTrips,
            ],
        ], Response::HTTP_OK);
    }
}
