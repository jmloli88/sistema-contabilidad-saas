<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepaseRequest extends FormRequest
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
            // Campos principales del repase
            'clinica_id' => 'required|exists:clinicas,id',
            'fecha' => 'required|date|date_format:Y-m-d',
            'fecha_pago' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha',
            'tipo_precio' => 'required|in:sin_nota,con_nota',
            'estado' => 'required|in:pendiente,pagado',
            'total_consultas' => 'required|numeric|min:0',
            'pedidos_doctor' => 'required|integer|min:0',
            'observaciones' => 'nullable|string|max:1000',
            
            // Validación de exámenes (acepta nuevo formato)
            'examenes' => 'required|array',
            'examenes.*.cantidad' => 'nullable|integer|min:0',
            'examenes.*.examen_id' => 'nullable|exists:examenes,id',
            
            // Validación de gastos (acepta nuevo formato - valores numéricos)
            'gastos' => 'nullable|array',
            'gastos.*' => 'nullable|numeric|min:0',
            
            // Validación de comentarios de gastos
            'comentarios' => 'nullable|array',
            'comentarios.operativos' => 'nullable|string|max:1000',
            'comentarios.administrativos' => 'nullable|string|max:1000',
            'comentarios.caja_chica' => 'nullable|string|max:1000',
            'comentarios.insumios_medicos' => 'nullable|string|max:1000',
            
            // Nombres personalizados para técnicos enfermeros
            'nombres_tecnicos' => 'nullable|array',
            'nombres_tecnicos.*' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Mensajes para campos principales
            'clinica_id.required' => 'La clínica es obligatoria.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'fecha.date_format' => 'La fecha debe tener el formato YYYY-MM-DD.',
            'fecha_pago.date' => 'La fecha de pago debe ser una fecha válida.',
            'fecha_pago.date_format' => 'La fecha de pago debe tener el formato YYYY-MM-DD.',
            'fecha_pago.after_or_equal' => 'La fecha de pago debe ser igual o posterior a la fecha del repase.',
            'tipo_precio.required' => 'El tipo de precio es obligatorio.',
            'tipo_precio.in' => 'El tipo de precio debe ser "sin_nota" o "con_nota".',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser "pendiente" o "pagado".',
            'total_consultas.required' => 'El total de consultas es obligatorio.',
            'total_consultas.numeric' => 'El total de consultas debe ser un número.',
            'total_consultas.min' => 'El total de consultas no puede ser negativo.',
            'pedidos_doctor.required' => 'Los pedidos del doctor son obligatorios.',
            'pedidos_doctor.integer' => 'Los pedidos del doctor deben ser un número entero.',
            'pedidos_doctor.min' => 'Los pedidos del doctor no pueden ser negativos.',
            'observaciones.string' => 'Las observaciones deben ser texto.',
            'observaciones.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
            
            // Mensajes para exámenes
            'examenes.required' => 'Debe agregar al menos un examen.',
            'examenes.array' => 'Los exámenes deben ser un arreglo.',
            
            // Mensajes para gastos
            'gastos.array' => 'Los gastos deben ser un arreglo.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que al menos un examen tenga cantidad > 0
            $examenes = $this->input('examenes', []);
            $tieneExamenes = false;
            
            foreach ($examenes as $examen) {
                if (is_array($examen) && isset($examen['cantidad']) && (int)$examen['cantidad'] > 0) {
                    $tieneExamenes = true;
                    break;
                }
            }
            
            if (!$tieneExamenes) {
                $validator->errors()->add('examenes', 'Debe agregar al menos un examen con cantidad mayor a 0.');
            }
        });
    }
}
