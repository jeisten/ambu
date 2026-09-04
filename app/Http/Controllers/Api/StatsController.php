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

    /**
     * Get admin fleet statistics for today.
     * Requires admin role.
     *
     * @return JsonResponse
     */
    public function adminFleetStats(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta información.',
            ], Response::HTTP_FORBIDDEN);
        }

        $today = now()->startOfDay();

        $todayRemissions = Remission::whereDate('created_at', $today)->count();
        $todayCompletedRemissions = Remission::whereDate('created_at', $today)
            ->where('status', 'finalizado')->count();
        $todayKm = (float) Remission::whereDate('created_at', $today)
            ->where('status', 'finalizado')
            ->sum('total_kilometers');
        $todayFuel = (float) Remission::whereDate('created_at', $today)
            ->where('status', 'finalizado')
            ->sum('fuel_consumed_gallons');

        $expiringDocsCount = Ambulance::expiringDocs(5)->count();
        $inMaintenanceCount = Ambulance::where('status', 'maintenance')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today->toDateString(),
                'today_remissions' => $todayRemissions,
                'today_completed' => $todayCompletedRemissions,
                'today_total_kilometers' => round($todayKm, 3),
                'today_total_fuel_gallons' => round($todayFuel, 3),
                'alerts' => [
                    'expiring_documentation_count' => $expiringDocsCount,
                    'ambulances_in_maintenance' => $inMaintenanceCount,
                ],
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get list of ambulances with expiring documents.
     * Requires admin role.
     *
     * @return JsonResponse
     */
    public function documentAlerts(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a esta información.',
            ], Response::HTTP_FORBIDDEN);
        }

        $ambulances = Ambulance::expiringDocs(30)
            ->orderBy('soat_expires_at')
            ->orderBy('tech_review_expires_at')
            ->get()
            ->map(function ($ambulance) {
                $soatExpiresIn = $ambulance->soat_expires_at ? now()->diffInDays($ambulance->soat_expires_at) : null;
                $techExpiresIn = $ambulance->tech_review_expires_at ? now()->diffInDays($ambulance->tech_review_expires_at) : null;

                return [
                    'ambulance_id' => $ambulance->id,
                    'plate' => $ambulance->plate,
                    'brand' => $ambulance->brand,
                    'model' => $ambulance->model,
                    'status' => $ambulance->status,
                    'soat_expires_at' => $ambulance->soat_expires_at?->toDateString(),
                    'soat_expires_in_days' => $soatExpiresIn,
                    'soat_urgent' => $soatExpiresIn !== null && $soatExpiresIn <= 5,
                    'tech_review_expires_at' => $ambulance->tech_review_expires_at?->toDateString(),
                    'tech_expires_in_days' => $techExpiresIn,
                    'tech_urgent' => $techExpiresIn !== null && $techExpiresIn <= 5,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ambulances,
            'total' => $ambulances->count(),
        ], Response::HTTP_OK);
    }
}
