<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiPatientAnalysisService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.deepseek.api_key', '');
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');
        $this->model   = config('services.deepseek.model', 'deepseek-chat');
        $this->timeout = (int) config('services.deepseek.timeout', 30);
    }

    /**
     * Analiza una observacion clinica en lenguaje natural y retorna
     * la clasificacion de triage y recomendaciones de traslado.
     */
    public function analyze(string $observation, ?string $patientContext = null): array
    {
        if (empty($this->apiKey)) {
            return $this->unavailableResponse('DEEPSEEK_API_KEY no configurado en .env');
        }

        $userMessage = $this->buildUserMessage($observation, $patientContext);

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->post('/chat/completions', [
                    'model'           => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'temperature'     => 0.2,
                    'max_tokens'      => 1024,
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('[AiPatientAnalysisService] Error en API DeepSeek', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->unavailableResponse("DeepSeek respondio con HTTP {$response->status()}");
            }

            $content = $response->json('choices.0.message.content', '{}');
            $parsed  = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
                return $this->unavailableResponse('Respuesta de IA no pudo ser parseada como JSON valido.');
            }

            return $this->normalizeResponse($parsed);

        } catch (\Throwable $e) {
            Log::error('[AiPatientAnalysisService] Excepcion al contactar DeepSeek', [
                'message' => $e->getMessage(),
            ]);
            return $this->unavailableResponse($e->getMessage());
        }
    }

    private function systemPrompt(): string
    {
        return 'Eres un asistente medico de emergencias especializado en clasificacion de triage prehospitalario (Sistema de Triage de Manchester). '
             . 'Analiza observaciones clinicas en lenguaje natural y responde UNICAMENTE con un JSON valido con este esquema: '
             . '{'
             . '"urgency_level": "critica|alta|moderada|baja",'
             . '"triage_code": "I|II|III|IV",'
             . '"clinical_summary": "Resumen clinico conciso.",'
             . '"recommended_specialties": ["Especialidad1"],'
             . '"immediate_protocols": ["Accion inmediata 1"],'
             . '"is_potential_emergency": true,'
             . '"alert_notes": "Observaciones para el equipo receptor o null"'
             . '}. '
             . 'ESCALA: critica=Triage I (riesgo vital inmediato), alta=Triage II (<10min), moderada=Triage III (<60min), baja=Triage IV (>60min). '
             . 'Tus respuestas son orientativas para personal prehospitalario, no emitas diagnosticos definitivos.';
    }

    private function buildUserMessage(string $observation, ?string $patientContext): string
    {
        $message = "OBSERVACION CLINICA:\n{$observation}";
        if (!empty($patientContext)) {
            $message .= "\n\nCONTEXTO DEL PACIENTE:\n{$patientContext}";
        }
        return $message;
    }

    private function normalizeResponse(array $data): array
    {
        return [
            'success'                 => true,
            'urgency_level'           => $data['urgency_level']           ?? 'moderada',
            'triage_code'             => $data['triage_code']             ?? 'III',
            'clinical_summary'        => $data['clinical_summary']        ?? '',
            'recommended_specialties' => $data['recommended_specialties'] ?? [],
            'immediate_protocols'     => $data['immediate_protocols']     ?? [],
            'is_potential_emergency'  => (bool) ($data['is_potential_emergency'] ?? false),
            'alert_notes'             => $data['alert_notes']             ?? null,
        ];
    }

    private function unavailableResponse(string $reason): array
    {
        return [
            'success'                 => false,
            'error'                   => 'Servicio de analisis clinico no disponible temporalmente.',
            'reason'                  => $reason,
            'urgency_level'           => null,
            'triage_code'             => null,
            'clinical_summary'        => null,
            'recommended_specialties' => [],
            'immediate_protocols'     => [],
            'is_potential_emergency'  => null,
            'alert_notes'             => null,
        ];
    }
}
