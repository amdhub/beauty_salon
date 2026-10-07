<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('role');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Role::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('roles')->ignore($this->route('role'))],
            'permissions' => ['sometimes', 'array'], 'permissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')]];
    }
}
