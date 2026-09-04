<?php

namespace App\Http\Requests\Remission;

use Illuminate\Foundation\Http\FormRequest;

class StoreRemissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If driver_id is not provided, default to the authenticated user ID
        if (!$this->has('driver_id') && $this->user()) {
            $this->merge([
                'driver_id' => $this->user()->id,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ambulance_id' => ['required', 'integer', 'exists:ambulances,id'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'patient' => ['nullable', 'array'],
            'patient.identification' => ['required_if:patient_id,null', 'string', 'max:50'],
            'patient.first_name' => ['required_if:patient_id,null', 'string', 'max:100'],
            'patient.last_name' => ['required_if:patient_id,null', 'string', 'max:100'],
            'patient.eps' => ['nullable', 'string', 'max:100'],
            'patient.is_soat_case' => ['nullable', 'boolean'],
            'patient.notes' => ['nullable', 'string'],
            'origin_address' => ['required', 'string', 'max:255'],
            'destination_address' => ['required', 'string', 'max:255'],
            'is_out_of_city' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'occupants' => ['nullable', 'array'],
            'occupants.*.name' => ['required_with:occupants', 'string', 'max:150'],
            'occupants.*.identification' => ['nullable', 'string', 'max:50'],
            'occupants.*.role' => ['required_with:occupants', 'string', 'in:doctor,nurse,paramedic,companion,other,Médico,Enfermero,Paramédico,Familiar,Estudiante,Otro'],
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
            'ambulance_id.required' => 'La ambulancia es requerida.',
            'ambulance_id.exists' => 'La ambulancia seleccionada no existe.',
            'driver_id.required' => 'El conductor es requerido.',
            'driver_id.exists' => 'El conductor seleccionado no existe.',
            'patient_id.exists' => 'El paciente seleccionado no existe.',
            'patient.identification.required_if' => 'El documento del paciente es obligatorio.',
            'patient.first_name.required_if' => 'El nombre del paciente es obligatorio.',
            'patient.last_name.required_if' => 'El apellido del paciente es obligatorio.',
            'origin_address.required' => 'La dirección de origen es obligatoria.',
            'destination_address.required' => 'La dirección de destino es obligatoria.',
            'occupants.array' => 'Los ocupantes deben ser una lista válida.',
            'occupants.*.name.required_with' => 'El nombre de cada ocupante es obligatorio.',
            'occupants.*.role.required_with' => 'El rol de cada ocupante es obligatorio.',
        ];
    }
}
