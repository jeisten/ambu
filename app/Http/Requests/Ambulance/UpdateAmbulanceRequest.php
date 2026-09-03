<?php

namespace App\Http\Requests\Ambulance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAmbulanceRequest extends FormRequest
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
        $ambulanceId = $this->route('ambulance')?->id ?? $this->route('ambulance');

        return [
            'plate' => ['sometimes', 'required', 'string', 'max:10', Rule::unique('ambulances', 'plate')->ignore($ambulanceId)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'km_per_gallon' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'soat_expires_at' => ['sometimes', 'required', 'date'],
            'tech_review_expires_at' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:available,in_service,maintenance'],
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
            'plate.unique' => 'La placa ingresada ya se encuentra registrada por otra ambulancia.',
            'km_per_gallon.numeric' => 'El rendimiento de km por galón debe ser numérico.',
            'status.in' => 'El estado debe ser: available, in_service o maintenance.',
        ];
    }
}
