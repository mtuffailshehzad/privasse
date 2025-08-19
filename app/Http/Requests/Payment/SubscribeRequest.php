<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_type' => ['required', 'in:basic,premium,vip'],
            'payment_method_id' => ['required', 'string'],
            'payment_method_type' => ['sometimes', 'string', 'in:card,bank_transfer'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_type.required' => 'Please select a subscription plan.',
            'plan_type.in' => 'Invalid subscription plan selected.',
            'payment_method_id.required' => 'Payment method is required.',
        ];
    }
}
