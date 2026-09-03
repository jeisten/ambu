<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            'identification' => ['required', 'string', 'max:50', 'unique:patients,identification'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'eps' => ['nullable', 'string', 'max:100'],
            'is_soat_case' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
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
            'identification.required' => 'El número de identificación del paciente es obligatorio.',
            'identification.unique' => 'Ya existe un paciente registrado con este número de identificación.',
            'first_name.required' => 'El nombre del paciente es obligatorio.',
            'last_name.required' => 'El apellido del paciente es obligatorio.',
        ];
    }
}
