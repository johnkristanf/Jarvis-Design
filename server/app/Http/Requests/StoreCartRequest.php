<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'fabric_type_id' => ['nullable', 'integer', 'exists:materials,id'],
            'size_id' => ['nullable', 'integer', 'exists:sizes,id'],
            'color' => ['required', 'string'],
            'own_design_file' => ['nullable', 'file'],
            'own_design_url' => ['nullable', 'string'],
            'quantity' => ['required', 'integer', 'min:1'],
            'selected_styles' => ['nullable', 'string'],
            'customizations' => ['nullable', 'string'],
        ];
    }
}
