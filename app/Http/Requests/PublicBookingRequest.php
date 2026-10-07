<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $phone = preg_replace('/[\s().-]+/', '', trim($this->input('phone')));
            $this->merge(['phone' => str_starts_with($phone, '00') ? '+'.substr($phone, 2) : $phone]);
        }
    }

    public function rules(): array
    {
        return [
            'request_key' => ['required', 'uuid', Rule::in([$this->session()->get('booking_key')])],
            'service_option' => ['required', 'string', 'max:50', 'regex:/^\d{1,10}:(?:base|\d{1,10})$/D'],
            'customer_name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'regex:/^\+?[0-9]{7,15}$/D'],
            'email' => ['nullable', 'email', 'max:255'],
            'preferred_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:now', 'before_or_equal:'.now()->addDays(90)->format('Y-m-d H:i:s')],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_key.in' => 'Ce formulaire a expiré. Rechargez la page de réservation.',
            'service_option.required' => 'Choisissez une prestation et son tarif.',
            'phone.regex' => 'Saisissez un numéro de téléphone valide, avec son indicatif si nécessaire.',
            'preferred_at.after' => 'Choisissez une date et une heure à venir.',
            'preferred_at.before_or_equal' => 'Les demandes sont ouvertes pour les 90 prochains jours.',
        ];
    }
}
