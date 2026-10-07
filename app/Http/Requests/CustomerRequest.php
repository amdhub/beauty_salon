<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('customer');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Customer::class);
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        foreach (['phone', 'secondary_phone'] as $field) {
            if (is_string($this->input($field)) && $this->filled($field)) {
                $number = preg_replace('/[\s().-]+/', '', trim((string) $this->input($field)));
                $values[$field] = str_starts_with($number, '00') ? '+'.substr($number, 2) : $number;
            }
        }
        if (is_string($this->input('email')) && $this->filled('email')) {
            $values['email'] = mb_strtolower(trim((string) $this->input('email')));
        }
        $this->merge($values);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:150'], 'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique('customers')->ignore($this->route('customer'))],
            'secondary_phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($this->route('customer'))],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])], 'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:1000'], 'notes' => ['nullable', 'string', 'max:5000'], 'allergies' => ['nullable', 'string', 'max:2000'],
            'preferred_beautician_id' => ['nullable', Rule::exists('employees', 'id')->whereNull('deleted_at')->where('active', true)],
        ];
    }
}
