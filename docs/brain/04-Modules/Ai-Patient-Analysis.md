---
title: "Modulo: Analisis Clinico Asistido por IA (DeepSeek)"
type: "module"
status: "active"
date: 2026-08-29
tags:
  - ambu-u
  - ai
  - deepseek
  - triage
  - clinical
links:
  - "[[Home]]"
  - "[[Architecture-MOC]]"
  - "[[Telemetry-Haversine]]"
---

# Modulo: Analisis Clinico Asistido por IA

## Descripcion
Servicio de analisis clinico prehospitalario que procesa observaciones medicas en lenguaje natural mediante la API de **DeepSeek** (`deepseek-chat`) y devuelve una clasificacion de triage estructurada para orientar al personal de ambulancia y al equipo receptor.

## Flujo de Datos

```
[Paramedico/Medico] -- observacion texto libre -->
  POST /api/ai/analyze-observation
    |
    v
  AiClinicalAnalysisController
    |
    v
  AiPatientAnalysisService
    |-- Prompt engineering (Triage Manchester MTS)
    |-- HTTP POST a DeepSeek /chat/completions
    |-- response_format: json_object
    v
  Respuesta estructurada JSON -> Cliente
```

## Endpoint

| Metodo | Ruta | Auth |
|--------|------|------|
| `POST` | `/api/ai/analyze-observation` | `auth:sanctum` |

### Request Body
```json
{
    "observation": "Paciente masculino 65 anios, dolor toracico irradiado al brazo izquierdo, diaforesis, TA 90/60.",
    "patient_id": 1,
    "remission_id": 3
}
```

### Response (200 - Exito)
```json
{
    "data": {
        "success": true,
        "urgency_level": "critica",
        "triage_code": "I",
        "clinical_summary": "Paciente con cuadro compatible con sindrome coronario agudo (SCA). Requiere atencion inmediata.",
        "recommended_specialties": ["Cardiologia", "Medicina de Urgencias"],
        "immediate_protocols": [
            "Monitoreo cardiaco continuo (ECG de 12 derivaciones si disponible)",
            "Oxigenoterapia si SpO2 < 94%",
            "Acceso venoso periferico, bolo de solucion salina si hipotension",
            "Notificar al hospital receptor: posible infarto agudo de miocardio (IAM)"
        ],
        "is_potential_emergency": true,
        "alert_notes": "Pre-avisar sala de hemodinamica. Preparar protocolo STEMI si se confirma."
    },
    "message": "Analisis clinico completado exitosamente."
}
```

### Response (503 - DeepSeek no disponible)
```json
{
    "data": {
        "success": false,
        "error": "Servicio de analisis clinico no disponible temporalmente.",
        "reason": "DEEPSEEK_API_KEY no configurado en .env"
    },
    "message": "El servicio de IA no esta disponible temporalmente."
}
```

## Escala de Urgencia (Triage Manchester MTS)

| Nivel | Codigo | Descripcion | Tiempo de Atencion |
|-------|--------|-------------|-------------------|
| `critica` | I | Riesgo vital inmediato | Inmediato |
| `alta` | II | Riesgo vital potencial | < 10 minutos |
| `moderada` | III | Urgente, sin riesgo inmediato | < 60 minutos |
| `baja` | IV | No urgente | > 60 minutos |

## Configuracion (.env)
```env
DEEPSEEK_API_KEY=your-api-key
DEEPSEEK_BASE_URL=https://api.deepseek.com
DEEPSEEK_MODEL=deepseek-chat
DEEPSEEK_TIMEOUT=30
```

## Archivos del Modulo
- `app/Services/AiPatientAnalysisService.php` (Logica y prompt engineering)
- `app/Http/Controllers/Api/AiClinicalAnalysisController.php`
- `app/Http/Requests/Patient/AnalyzePatientObservationRequest.php`
- `config/services.php` (seccion deepseek)
- `routes/api.php` (POST /api/ai/analyze-observation)

## Consideraciones de Diseno
- Las respuestas son **orientativas** para el personal prehospitalario. No reemplazan el juicio clinico.
- Fallback elegante: si la API de DeepSeek falla, se devuelve HTTP 503 con mensaje claro (nunca un HTTP 500 fatal).
- `response_format: json_object` fuerza a DeepSeek a responder JSON valido, eliminando alucinaciones de formato.
- Temperatura `0.2`: razonamiento deterministico, minima variabilidad para contextos medicos criticos.
