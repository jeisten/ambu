<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\AnalyzePatientObservationRequest;
use App\Models\Patient;
use App\Models\Remission;
use App\Services\AiPatientAnalysisService;
use Illuminate\Http\JsonResponse;

class AiClinicalAnalysisController extends Controller
{
    public function __construct(
        private readonly AiPatientAnalysisService $aiService
    ) {}

    /**
     * POST /api/ai/analyze-observation
     *
     * Recibe una observacion medica en texto libre y retorna el
     * analisis de triage de DeepSeek con recomendaciones de traslado.
     */
    public function analyzeObservation(AnalyzePatientObservationRequest $request): JsonResponse
    {
        $patientContext = null;

        // Si se envia patient_id, enriquecemos el contexto para DeepSeek
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
            if ($patient) {
                $patientContext = "Nombre: {$patient->full_name}. "
                    . "EPS: " . ($patient->eps ?? 'No registrada') . ". "
                    . "Caso SOAT: " . ($patient->is_soat_case ? 'Si' : 'No') . ".";
            }
        }

        // Si se envia remission_id, agregamos contexto del traslado
        if ($request->filled('remission_id')) {
            $remission = Remission::with('ambulance')->find($request->remission_id);
            if ($remission) {
                $patientContext .= " Traslado: {$remission->origin_address} -> {$remission->destination_address}."
                    . " Intermunicipal: " . ($remission->is_out_of_city ? 'Si' : 'No') . ".";
            }
        }

        $result = $this->aiService->analyze(
            observation: $request->input('observation'),
            patientContext: $patientContext
        );

        $httpStatus = $result['success'] ? 200 : 503;

        return response()->json([
            'data'    => $result,
            'message' => $result['success']
                ? 'Analisis clinico completado exitosamente.'
                : 'El servicio de IA no esta disponible temporalmente.',
        ], $httpStatus);
    }
}
