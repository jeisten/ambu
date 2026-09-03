<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ambulance\StoreAmbulanceRequest;
use App\Http\Requests\Ambulance\UpdateAmbulanceRequest;
use App\Models\Ambulance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AmbulanceController extends Controller
{
    /**
     * Display a listing of ambulances with optional status and expiration filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ambulance::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('expiring_docs')) {
            $days = (int) $request->input('days', 5);
            $query->expiringDocs($days);
        }

        $perPage = $request->input('per_page');
        $ambulances = $perPage ? $query->paginate((int) $perPage) : $query->get();

        return response()->json([
            'success' => true,
            'data' => $ambulances,
        ], Response::HTTP_OK);
    }

    /**
     * Display a list of available ambulances ready for dispatch.
     *
     * @return JsonResponse
     */
    public function available(): JsonResponse
    {
        $ambulances = Ambulance::available()->get();

        return response()->json([
            'success' => true,
            'data' => $ambulances,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created ambulance in storage.
     *
     * @param StoreAmbulanceRequest $request
     * @return JsonResponse
     */
    public function store(StoreAmbulanceRequest $request): JsonResponse
    {
        $ambulance = Ambulance::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ambulancia registrada con éxito',
            'data' => $ambulance,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified ambulance.
     *
     * @param Ambulance $ambulance
     * @return JsonResponse
     */
    public function show(Ambulance $ambulance): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $ambulance,
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified ambulance in storage.
     *
     * @param UpdateAmbulanceRequest $request
     * @param Ambulance $ambulance
     * @return JsonResponse
     */
    public function update(UpdateAmbulanceRequest $request, Ambulance $ambulance): JsonResponse
    {
        $ambulance->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ambulancia actualizada con éxito',
            'data' => $ambulance,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified ambulance from storage.
     *
     * @param Ambulance $ambulance
     * @return JsonResponse
     */
    public function destroy(Ambulance $ambulance): JsonResponse
    {
        if ($ambulance->status === 'in_service') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una ambulancia que se encuentra en servicio activo.',
            ], Response::HTTP_CONFLICT);
        }

        $ambulance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ambulancia eliminada con éxito',
        ], Response::HTTP_OK);
    }
}
