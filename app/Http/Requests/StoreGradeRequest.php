<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
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
            'class_id' => [
                'required',
                'string',
                'exists:mst_classes,class_id',
            ],
            'subject_id' => [
                'required',
                'string',
                'exists:mst_subjects,subject_id',
            ],
            'teacher_id' => [
                'required',
                'string',
                'exists:mst_teachers,teacher_id',
            ],
            'grade_type' => [
                'required',
                'in:Midterm,Final,Assignment,Practical',
            ],
            'grades' => [
                'required',
                'array',
                'min:1',
            ],
            'grades.*.student_class_id' => [
                'required',
                'string',
                'exists:mst_student_classes,student_class_id',
            ],
            'grades.*.grade_value' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'grades.*.grade_attitude' => [
                'nullable',
                'string',
                'max:255',
            ],
            'grades.*.notes' => [
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
            'class_id.required' => 'Class is required.',
            'class_id.exists' => 'Selected class does not exist.',
            'subject_id.required' => 'Subject is required.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'teacher_id.required' => 'Teacher is required.',
            'teacher_id.exists' => 'Selected teacher does not exist.',
            'grade_type.required' => 'Grade type is required.',
            'grade_type.in' => 'Invalid grade type. Must be Midterm, Final, Assignment, or Practical.',
            'grades.required' => 'At least one student grade record is required.',
            'grades.min' => 'At least one student grade record is required.',
            'grades.*.student_class_id.required' => 'Student is required for each record.',
            'grades.*.student_class_id.exists' => 'Selected student does not exist.',
            'grades.*.grade_value.required' => 'Grade value is required for each student.',
            'grades.*.grade_value.numeric' => 'Grade value must be a number.',
            'grades.*.grade_value.min' => 'Grade value must be at least 0.',
            'grades.*.grade_value.max' => 'Grade value cannot exceed 100.',
            'grades.*.grade_attitude.max' => 'Grade attitude cannot exceed 255 characters.',
            'grades.*.notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
