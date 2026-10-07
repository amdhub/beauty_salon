<?php

namespace App\Http\Requests;

use App\Models\ServiceVariant;
use Illuminate\Foundation\Http\FormRequest;

class ServiceVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('variant');

        return $record ? $this->user()->can('update', $record) : $this->user()->can('create', ServiceVariant::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:9999999999.99'], 'estimated_duration' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999']];
    }
}
