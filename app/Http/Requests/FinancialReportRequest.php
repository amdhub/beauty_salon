<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Services\FinancialReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', FinancialReport::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from' => $this->input('from') ?: today()->startOfMonth()->format('Y-m-d'),
            'to' => $this->input('to') ?: today()->format('Y-m-d'),
        ]);
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_method' => ['nullable', Rule::in([...array_column(PaymentMethod::cases(), 'value'), 'unknown'])],
        ];
    }
}
