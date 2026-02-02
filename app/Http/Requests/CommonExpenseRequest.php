<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommonExpenseRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'category_id' => ['required', 'exists:categories,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => __('messages.validation.name_required'),
            'amount.required' => __('messages.validation.amount_required'),
            'amount.numeric' => __('messages.validation.amount_numeric'),
            'amount.min' => __('messages.validation.amount_min'),
            'category_id.required' => __('messages.validation.category_required'),
            'category_id.exists' => __('messages.validation.category_invalid'),
        ];
    }
}
