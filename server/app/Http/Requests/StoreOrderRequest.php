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
            'phone_number' => 'required|string',
            'address' => 'required|string',
            'design_type' => 'required|in:own-design,business-design,ai-generation',
            'order_option' => 'required|string',

            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|numeric',
            'products.*.product_unit_price' => 'required|numeric',
            'products.*.product_color' => 'required|string',
            'products.*.fabric_type_id' => 'nullable|numeric',
            'products.*.payment_attachment' => 'required|file',
            'products.*.total_quantity' => 'required|numeric',
            'products.*.total_price' => 'required|numeric',
            'products.*.sizes' => 'nullable|string',
            'products.*.own_design_url' => 'nullable|string',

        ];
    }
}
