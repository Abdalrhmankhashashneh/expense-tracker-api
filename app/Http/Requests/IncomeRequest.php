<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomeRequest extends FormRequest
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
            'monthly_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'effective_from' => ['required', 'date', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'monthly_amount.required' => __('messages.validation.amount_required'),
            'monthly_amount.numeric' => __('messages.validation.amount_numeric'),
            'monthly_amount.min' => __('messages.validation.amount_min'),
            'effective_from.required' => __('messages.validation.date_required'),
            'effective_from.date' => __('messages.validation.date_invalid'),
        ];
    }
}
