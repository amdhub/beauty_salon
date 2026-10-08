<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('repay', $this->route('debt'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'numeric', 'gt:0'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.regex' => 'Saisissez un montant avec au maximum 10 chiffres et 2 décimales.',
            'amount.gt' => 'Le remboursement doit être supérieur à zéro.',
            'paid_on.before_or_equal' => 'La date du remboursement ne peut pas être dans le futur.',
        ];
    }
}
