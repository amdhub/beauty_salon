<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('service');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', Service::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'], 'service_category_id' => ['required', Rule::exists('service_categories', 'id')->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'], 'default_duration' => ['required', 'integer', 'min:5', 'max:1440'],
            'base_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'], 'active' => ['required', 'boolean'], 'notes' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
