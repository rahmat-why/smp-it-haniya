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
        return session('user_type') === 'Employee';
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
            'status' => [
                'required',
                'in:Pending,Partial,Paid',
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
            'status.required' => 'Payment status is required.',
            'status.in' => 'Invalid status. Must be Pending, Partial, or Paid.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
