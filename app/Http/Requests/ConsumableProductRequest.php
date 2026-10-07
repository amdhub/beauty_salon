<?php

namespace App\Http\Requests;

use App\Models\ConsumableProduct;
use App\Services\StockQuantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConsumableProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product ? $this->user()->can('update', $product) : $this->user()->can('create', ConsumableProduct::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('reference'))) {
            $this->merge(['reference' => mb_strtoupper(trim($this->input('reference')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'reference' => ['required', 'string', 'max:60', Rule::unique('consumable_products')->ignore($this->route('product'))],
            'unit' => ['required', Rule::in(array_keys(ConsumableProduct::UNITS))],
            'minimum_stock' => ['required', 'regex:'.StockQuantity::PATTERN],
            'active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
