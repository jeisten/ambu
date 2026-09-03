<?php

declare(strict_types=1);

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzePatientObservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observation'  => ['required', 'string', 'min:10', 'max:2000'],
            'patient_id'   => ['nullable', 'integer', 'exists:patients,id'],
            'remission_id' => ['nullable', 'integer', 'exists:remissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'observation.required' => 'La observacion clinica es obligatoria.',
            'observation.min'      => 'La observacion debe tener al menos 10 caracteres.',
        ];
    }
}
