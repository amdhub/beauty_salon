<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'amount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'numeric', 'gt:0'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payee' => ['nullable', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'], 'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.regex' => 'Saisissez un montant avec au maximum 10 chiffres et 2 décimales.',
            'amount.gt' => 'Le montant de la dépense doit être supérieur à zéro.',
            'paid_on.before_or_equal' => 'La date du règlement ne peut pas être dans le futur.',
        ];
    }
}
