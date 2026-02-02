<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCommonExpenseRequest extends FormRequest
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
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'date.required' => __('messages.validation.date_required'),
            'date.date' => __('messages.validation.date_invalid'),
            'amount.numeric' => __('messages.validation.amount_numeric'),
            'amount.min' => __('messages.validation.amount_min'),
        ];
    }
}
