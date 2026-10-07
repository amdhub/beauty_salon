<?php

namespace App\Http\Requests;

use App\Services\StockQuantity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->route('type'), ['supply', 'consume'], true)
            && $this->user()->can($this->route('type'), $this->route('product'));
    }

    public function rules(): array
    {
        $supply = $this->route('type') === 'supply';

        return [
            'quantity' => ['required', 'regex:'.StockQuantity::PATTERN, 'numeric', 'gt:0'],
            'movement_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'idempotency_key' => ['required', 'uuid'],
            'supplier' => [Rule::excludeIf(! $supply), 'nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'total_cost' => [Rule::excludeIf(! $supply), 'nullable', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D'],
            'employee_id' => [Rule::excludeIf($supply), 'nullable', 'integer', Rule::exists('employees', 'id')->where('active', true)->whereNull('deleted_at')],
            'notes' => [$supply ? 'nullable' : 'required', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.regex' => 'La quantité doit comporter au maximum 9 chiffres et 3 décimales.',
            'quantity.gt' => 'La quantité doit être supérieure à zéro.',
            'notes.required' => 'Indiquez le motif de la consommation.',
            'movement_date.before_or_equal' => 'La date ne peut pas être dans le futur.',
        ];
    }
}
