<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
           'color' => 'required|string',
            'phone_number' => 'required|string',
            'product_unit_price' => 'required|numeric',
            'product_id' => 'required|numeric',
            'address' => 'required|string',
            'design_type' => 'required|in:own-design,business-design,ai-generation',
            'order_option' => 'required|string',
            'total_quantity' => 'required|numeric|min:1',
            'total_price' => 'required|numeric|min:1',
            'fabric_type_id' => 'nullable|numeric',
            'solo_quantity' => 'nullable|numeric',
            'sizes' => 'nullable|array',
            'sizes.*' => 'nullable|numeric|min:0',
            'own_design_file' => 'nullable|file',
            'business_design_url' => 'nullable|string',
            'payment_attachment' => 'required|file',
        ];
    }
}
