<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StorePatientRequest;
use App\Http\Requests\Patient\UpdatePatientRequest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PatientController extends Controller
{
    /**
     * Display a listing of patients with search and pagination support.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Patient::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $q) use ($search) {
                $q->where('identification', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('eps', 'like', "%{$search}%");
            });
        }

        if ($request->filled('identification')) {
            $query->where('identification', $request->input('identification'));
        }

        $perPage = $request->input('per_page');
        $patients = $perPage ? $query->paginate((int) $perPage) : $query->get();

        return response()->json([
            'success' => true,
            'data' => $patients,
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created patient in storage.
     *
     * @param StorePatientRequest $request
     * @return JsonResponse
     */
    public function store(StorePatientRequest $request): JsonResponse
    {
        $patient = Patient::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paciente registrado con éxito',
            'data' => $patient,
        ], Response::HTTP_CREATED);
    }

    /**
     * Search for a patient by identification (cédula/document).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchByIdentification(Request $request): JsonResponse
    {
        $request->validate([
            'identification' => ['required', 'string', 'max:50'],
        ]);

        $patient = Patient::where('identification', $request->input('identification'))->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $patient,
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified patient.
     *
     * @param Patient $patient
     * @return JsonResponse
     */
    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $patient->load(['remissions' => function ($q) {
                $q->latest()->limit(10);
            }]),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified patient in storage.
     *
     * @param UpdatePatientRequest $request
     * @param Patient $patient
     * @return JsonResponse
     */
    public function update(UpdatePatientRequest $request, Patient $patient): JsonResponse
    {
        $patient->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Paciente actualizado con éxito',
            'data' => $patient,
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified patient from storage.
     *
     * @param Patient $patient
     * @return JsonResponse
     */
    public function destroy(Patient $patient): JsonResponse
    {
        $hasActiveRemissions = $patient->remissions()
            ->whereIn('status', ['en_camino', 'trasladando'])
            ->exists();

        if ($hasActiveRemissions) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un paciente con remisiones en curso.',
            ], Response::HTTP_CONFLICT);
        }

        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paciente eliminado con éxito',
        ], Response::HTTP_OK);
    }
}
