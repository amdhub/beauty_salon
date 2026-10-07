<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSalonSettingsRequest;
use App\Models\SalonSetting;
use App\Services\ReservationWhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SalonSettingController extends Controller
{
    public function edit(ReservationWhatsApp $whatsapp): View
    {
        Gate::authorize('viewAny', SalonSetting::class);

        return view('settings.edit', ['whatsappNumber' => $whatsapp->recipient()]);
    }

    public function update(UpdateSalonSettingsRequest $request): RedirectResponse
    {
        SalonSetting::updateOrCreate(['key' => SalonSetting::RESERVATION_WHATSAPP_NUMBER], ['value' => $request->validated('whatsapp_number')]);

        return redirect()->route('settings.edit')->with('success', 'Les paramètres du salon ont été enregistrés.');
    }
}
