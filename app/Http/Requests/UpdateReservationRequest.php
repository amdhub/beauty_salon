<?php

namespace App\Http\Requests;

use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('reservation'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_keys(Reservation::STATUSES))],
            'expected_status' => ['required', Rule::in(array_keys(Reservation::STATUSES))],
            'scheduled_at' => [Rule::excludeIf($this->input('status') !== 'confirmed'), 'required', 'date_format:Y-m-d\TH:i', 'after:now'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
