<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentInstallmentRequest extends FormRequest
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
            'installment_number' => [
                'required',
                'integer',
                'min:1',
            ],
            'total_payment' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'payment_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:today',
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
            'installment_number.required' => 'Installment number is required.',
            'installment_number.integer' => 'Installment number must be a whole number.',
            'installment_number.min' => 'Installment number must be at least 1.',
            'total_payment.required' => 'Payment amount is required.',
            'total_payment.numeric' => 'Payment amount must be a valid number.',
            'total_payment.min' => 'Payment amount must be greater than 0.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'payment_date.before_or_equal' => 'Payment date cannot be in the future.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
