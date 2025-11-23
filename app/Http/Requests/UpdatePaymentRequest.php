<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only employees can update
        if (session()->has('employee_id')) {
            return true;
        }

        $userType = session('user_type');
        return is_string($userType) && strtolower($userType) === 'employee';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_type' => [
                'required',
                'string',
                'max:100',
            ],

            'payment_method' => [
                'required',
                'in:Paid,Instalment',
            ],

            'total_payment' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'total_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_date' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'payment_type.required' => 'Payment type is required.',
            'payment_type.max' => 'Payment type cannot exceed 100 characters.',

            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Payment method must be Paid or Instalment.',

            'total_payment.required' => 'Total payment amount is required.',
            'total_payment.numeric' => 'Total payment must be a valid number.',
            'total_payment.min' => 'Total payment must be greater than 0.',

            'total_price.numeric' => 'Total price must be numeric.',
            'total_price.min' => 'Total price cannot be negative.',

            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',

            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}