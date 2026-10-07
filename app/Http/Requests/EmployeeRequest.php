<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('employee');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Employee::class);
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
            'name' => ['required', 'string', 'max:150'], 'phone' => ['nullable', 'regex:/^\+?[0-9]{7,15}$/'], 'email' => ['nullable', 'email', 'max:255'],
            'position' => ['required', 'string', 'max:100'], 'hire_date' => ['nullable', 'date'], 'active' => ['required', 'boolean'], 'notes' => ['nullable', 'string', 'max:5000'],
            'user_id' => ['nullable', Rule::exists('users', 'id'), Rule::unique('employees')->ignore($this->route('employee'))],
            'specialties' => ['sometimes', 'array'], 'specialties.*' => ['integer', 'distinct', Rule::exists('service_categories', 'id')->whereNull('deleted_at')],
        ];
    }
}
