<?php

namespace App\Http\Requests\Ambulance;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmbulanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plate' => ['required', 'string', 'max:10', 'unique:ambulances,plate'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'km_per_gallon' => ['required', 'numeric', 'min:0.01'],
            'soat_expires_at' => ['required', 'date'],
            'tech_review_expires_at' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:available,in_service,maintenance'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate.required' => 'La placa es obligatoria.',
            'plate.unique' => 'La placa ingresada ya se encuentra registrada.',
            'km_per_gallon.required' => 'El rendimiento de km por galón es obligatorio.',
            'km_per_gallon.numeric' => 'El rendimiento de km por galón debe ser numérico.',
            'soat_expires_at.required' => 'La fecha de vencimiento del SOAT es obligatoria.',
            'tech_review_expires_at.required' => 'La fecha de vencimiento de la revisión tecnomecánica es obligatoria.',
            'status.in' => 'El estado debe ser: available, in_service o maintenance.',
        ];
    }
}
