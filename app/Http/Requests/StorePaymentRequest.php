<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow only if employee session exists
        return session()->has('employee_id');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_class_id' => [
                'required',
                'string',
                'exists:mst_student_classes,student_class_id',
            ],

            'payment_type' => [
                'required',
                'string',
                'max:100',
            ],

            'total_payment' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'required',
                'in:Paid,Instalment',
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
            'student_class_id.required' => 'Student is required.',
            'student_class_id.exists' => 'Selected student does not exist.',

            'payment_type.required' => 'Payment type is required.',
            'payment_type.max' => 'Payment type cannot exceed 100 characters.',

            'total_payment.required' => 'Total payment amount is required.',
            'total_payment.numeric' => 'Total payment must be a valid number.',
            'total_payment.min' => 'Total payment must be greater than 0.',

            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',

            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Payment method must be either Paid or Instalment.',

            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}