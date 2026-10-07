<?php

namespace App\Http\Requests;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('category');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', ServiceCategory::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100', Rule::unique('service_categories')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:2000'], 'active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999']];
    }
}
