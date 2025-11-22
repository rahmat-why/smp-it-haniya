<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
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
            'academic_year_id' => [
                'required',
                'string',
                'exists:mst_academic_years,academic_year_id',
            ],
            'day' => [
                'required',
                'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
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
            'academic_year_id.required' => 'Academic year is required.',
            'academic_year_id.exists' => 'Selected academic year does not exist.',
            'day.required' => 'Day is required.',
            'day.in' => 'Invalid day. Must be a valid weekday.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time format must be HH:MM.',
            'end_time.required' => 'End time is required.',
            'end_time.date_format' => 'End time format must be HH:MM.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }
}
