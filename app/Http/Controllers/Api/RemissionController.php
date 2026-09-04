<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Remission\FinishRemissionRequest;
use App\Http\Requests\Remission\RecordLocationRequest;
use App\Http\Requests\Remission\StoreRemissionRequest;
use App\Models\Remission;
use App\Services\RemissionLifecycleService;
use App\Services\TelemetryProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemissionController extends Controller
{
    /**
     * Create a new controller instance with injected lifecycle and telemetry services.
     *
     * @param RemissionLifecycleService $lifecycleService
     * @param TelemetryProcessingService $telemetryService
     */
    public function __construct(
        protected RemissionLifecycleService $lifecycleService,
        protected TelemetryProcessingService $telemetryService
    ) {}

    /**
     * Display a listing of remissions with filters for status and driver.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Remission::with(['ambulance', 'driver', 'patient', 'occupants'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('driver_id')) {
            $query->forDriver((int) $request->input('driver_id'));
        }

        if ($request->boolean('active_only')) {
            $query->active();
        }

        $perPage = $request->input('per_page');
        $remissions = $perPage ? $query->paginate((int) $perPage) : $query->get();

        return response()->json([
            'success' => true,
            'data' => $remissions,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created remission and initiate dispatch.
     *
     * @param StoreRemissionRequest $request
     * @return JsonResponse
     */
    public function store(StoreRemissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $occupants = $request->input('occupants', []);

        $remission = $this->lifecycleService->createRemission($validated, $occupants);

        return response()->json([
            'success' => true,
            'message' => 'Remisión creada e iniciada exitosamente',
            'data' => $remission,
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified remission with its relationships and optional telemetry route.
     *
     * @param Request $request
     * @param Remission $remission
     * @return JsonResponse
     */
    public function show(Request $request, Remission $remission): JsonResponse
    {
        $relations = ['ambulance', 'driver', 'patient', 'occupants'];

        if ($request->boolean('include_locations') || $request->has('with_locations')) {
            $relations['locations'] = function ($q) {
                $q->orderBy('recorded_at');
            };
        }

        $remission->load($relations);

        return response()->json([
            'success' => true,
            'data' => $remission,
        ], Response::HTTP_OK);
    }

    /**
     * Transition a remission to in-transfer status ('trasladando').
     *
     * @param Remission $remission
     * @return JsonResponse
     */
    public function startTransfer(Remission $remission): JsonResponse
    {
        if ($remission->status !== 'en_camino') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede iniciar el traslado desde el estado "en_camino". Estado actual: ' . $remission->status,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updated = $this->lifecycleService->startTransfer($remission);

        return response()->json([
            'success' => true,
            'message' => 'Traslado del paciente iniciado correctamente',
            'data' => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * Finish a remission, calculate fuel consumed and release the ambulance.
     *
     * @param FinishRemissionRequest $request
     * @param Remission $remission
     * @return JsonResponse
     */
    public function finish(FinishRemissionRequest $request, Remission $remission): JsonResponse
    {
        if (!in_array($remission->status, ['en_camino', 'trasladando'])) {
            return response()->json([
                'success' => false,
                'message' => 'La remisión no se encuentra activa para ser finalizada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $closingNote = $request->getClosingNote();
        $finished = $this->lifecycleService->finishRemission($remission, $closingNote);

        return response()->json([
            'success' => true,
            'message' => 'Remisión finalizada exitosamente',
            'data' => $finished,
        ], Response::HTTP_OK);
    }

    /**
     * Cancel an active remission with a mandatory cancellation reason.
     *
     * @param Request $request
     * @param Remission $remission
     * @return JsonResponse
     */
    public function cancel(Request $request, Remission $remission): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'El motivo de cancelación es obligatorio.',
        ]);

        if (!in_array($remission->status, ['en_camino', 'trasladando'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar remisiones en curso.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $cancelled = $this->lifecycleService->cancelRemission($remission, $request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Remisión cancelada exitosamente',
            'data' => $cancelled,
        ], Response::HTTP_OK);
    }

    /**
     * Ingest a real-time GPS telemetry coordinate point for an active remission.
     *
     * @param RecordLocationRequest $request
     * @param Remission $remission
     * @return JsonResponse
     */
    public function recordLocation(RecordLocationRequest $request, Remission $remission): JsonResponse
    {
        if (!in_array($remission->status, ['en_camino', 'trasladando'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede registrar telemetría en una remisión inactiva o finalizada.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $location = $this->telemetryService->recordLocation($remission, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ubicación registrada y transmitida',
            'data' => [
                'remission_id' => $remission->id,
                'current_location' => [
                    'id' => $location->id,
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'speed' => $location->speed !== null ? (float) $location->speed : null,
                    'heading' => $location->heading !== null ? (float) $location->heading : null,
                    'recorded_at' => $location->recorded_at?->toIso8601String() ?? $location->created_at?->toIso8601String(),
                ],
                'accumulated_total_kilometers' => $remission->fresh()->total_kilometers,
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Get the historical telemetry coordinates trail for a remission.
     *
     * @param Remission $remission
     * @return JsonResponse
     */
    public function locations(Remission $remission): JsonResponse
    {
        $locations = $remission->locations()
            ->orderBy('recorded_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations,
        ], Response::HTTP_OK);
    }

    /**
     * Register additional fuel consumed during an active remission.
     * The driver can report extra fuel consumption beyond the calculated amount.
     *
     * @param Remission $remission
     * @return JsonResponse
     */
    public function recordFuelConsumed(Remission $remission): JsonResponse
    {
        if (!in_array($remission->status, ['en_camino', 'trasladando'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede registrar combustible en remisiones activas.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        request()->validate([
            'fuel_consumed_gallons' => ['required', 'numeric', 'min:0.001', 'max:999.999'],
        ], [
            'fuel_consumed_gallons.required' => 'El consumo de gasolina es requerido.',
            'fuel_consumed_gallons.numeric' => 'El consumo debe ser un número válido.',
            'fuel_consumed_gallons.min' => 'El consumo debe ser mayor a 0.',
        ]);

        $additionalFuel = (float) request()->input('fuel_consumed_gallons');
        $remission->fuel_consumed_gallons += $additionalFuel;
        $remission->save();

        return response()->json([
            'success' => true,
            'message' => 'Combustible registrado exitosamente',
            'data' => [
                'remission_id' => $remission->id,
                'fuel_added_gallons' => $additionalFuel,
                'total_fuel_consumed_gallons' => (float) $remission->fuel_consumed_gallons,
            ],
        ], Response::HTTP_OK);
    }
}
