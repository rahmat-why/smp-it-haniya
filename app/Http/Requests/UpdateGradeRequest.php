<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return session('user_type') === 'Employee';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'grade_value' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'grade_attitude' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'grade_value.required' => 'Grade value is required.',
            'grade_value.numeric' => 'Grade value must be a number.',
            'grade_value.min' => 'Grade value must be at least 0.',
            'grade_value.max' => 'Grade value cannot exceed 100.',
            'grade_attitude.max' => 'Grade attitude cannot exceed 255 characters.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
