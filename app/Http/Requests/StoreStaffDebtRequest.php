<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\StaffDebt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StaffDebt::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')->where('active', true)],
            'amount' => ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/D', 'numeric', 'gt:0'],
            'issued_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'due_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issued_on'],
            'reason' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:3000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Sélectionnez un membre actif de l’équipe.',
            'amount.regex' => 'Saisissez un montant avec au maximum 10 chiffres et 2 décimales.',
            'amount.gt' => 'Le montant doit être supérieur à zéro.',
            'issued_on.before_or_equal' => 'La date de la dette ne peut pas être dans le futur.',
            'due_on.after_or_equal' => 'L’échéance doit être égale ou postérieure à la date de la dette.',
        ];
    }
}
