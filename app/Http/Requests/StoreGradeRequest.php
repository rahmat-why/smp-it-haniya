<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => 'required|string',
            'subject_id' => 'required|string',
            'teacher_id' => 'required|string',
            'grade_type' => 'required|string',

            'grades' => 'required|array|min:1',
            'grades.*.student_class_id' => 'required|string',
            'grades.*.grade_value' => 'nullable|numeric',
            'grades.*.grade_attitude' => 'nullable|string|max:255',
            'grades.*.notes' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}