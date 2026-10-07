<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('user');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('user'))],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', Password::min(12)->letters()->numbers()],
            'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')]];
    }
}
