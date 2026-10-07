<?php

namespace App\Http\Requests;

use App\Models\SalonSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalonSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', SalonSetting::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('whatsapp_number'))) {
            $number = preg_replace('/[\s().-]+/', '', trim($this->input('whatsapp_number')));
            if (str_starts_with($number, '00')) {
                $number = '+'.substr($number, 2);
            }
            if ($number !== '' && ! str_starts_with($number, '+')) {
                $number = '+'.$number;
            }
            $this->merge(['whatsapp_number' => $number ?: null]);
        }
    }

    public function rules(): array
    {
        return ['whatsapp_number' => ['present', 'nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/D']];
    }

    public function messages(): array
    {
        return ['whatsapp_number.regex' => 'Saisissez le numéro WhatsApp avec son indicatif pays, par exemple +243 suivi du numéro, sans le zéro local.'];
    }
}
