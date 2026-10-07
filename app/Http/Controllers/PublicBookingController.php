<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicBookingRequest;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\PublicReservations;
use App\Services\ReservationWhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    private function catalog(): Builder
    {
        return Service::where('active', true)
            ->whereHas('category', fn ($q) => $q->where('active', true)->whereNull('deleted_at'))
            ->where(fn ($q) => $q->whereNotNull('base_price')->orWhereHas('variants', fn ($v) => $v->where('active', true)))
            ->with(['category', 'variants' => fn ($q) => $q->where('active', true)])
            ->orderBy('name')->orderBy('id');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['category' => ['nullable', 'integer', 'min:1']]);
        $services = $this->catalog()->when(! empty($filters['category']), fn ($q) => $q->where('service_category_id', $filters['category']))->paginate(6)->withQueryString()->fragment('prestations');
        $categories = ServiceCategory::where('active', true)->whereHas('services', fn ($q) => $q->where('active', true))->orderBy('sort_order')->get();

        return view('public.home', compact('services', 'categories', 'filters'));
    }

    public function create(Request $request, ReservationWhatsApp $whatsapp): View
    {
        $selection = $request->validate(['service' => ['nullable', 'integer', 'min:1']]);
        if (! $request->session()->has('booking_key') || $request->session()->get('booking_key') === $request->session()->get('booking_submitted_key')) {
            $request->session()->put('booking_key', (string) Str::uuid());
        }

        return view('public.booking', [
            'services' => $this->catalog()->get(), 'selectedService' => $selection['service'] ?? null,
            'requestKey' => $request->session()->get('booking_key'),
            'whatsappEnabled' => $whatsapp->recipient() !== null,
        ]);
    }

    public function store(PublicBookingRequest $request, PublicReservations $reservations): RedirectResponse
    {
        $reservation = $reservations->create($request->validated());
        $request->session()->put(['last_reservation_id' => $reservation->id, 'booking_submitted_key' => $reservation->request_key]);

        return redirect()->route('booking.received')->with('booking_whatsapp_reservation_id', $reservation->id);
    }

    public function received(Request $request, ReservationWhatsApp $whatsapp): View|RedirectResponse
    {
        $reservation = Reservation::find($request->session()->get('last_reservation_id'));
        if (! $reservation) {
            return redirect()->route('booking.create');
        }

        $whatsappUrl = $whatsapp->url($reservation);
        $autoOpenWhatsApp = $request->session()->pull('booking_whatsapp_reservation_id') === $reservation->id && $whatsappUrl !== null;

        return view('public.booking-received', compact('reservation', 'whatsappUrl', 'autoOpenWhatsApp'));
    }
}
