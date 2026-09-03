<?php

namespace App\Http\Requests\Remission;

use Illuminate\Foundation\Http\FormRequest;

class RecordLocationRequest extends FormRequest
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
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'recorded_at' => ['nullable', 'date'],
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
            'latitude.required' => 'La latitud es requerida.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',
            'longitude.required' => 'La longitud es requerida.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',
            'speed.min' => 'La velocidad no puede ser negativa.',
            'heading.between' => 'El rumbo (heading) debe estar entre 0 y 360 grados.',
        ];
    }
}
