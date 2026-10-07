<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationCustomers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Reservation::class);
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_keys(Reservation::STATUSES))], 'search' => ['nullable', 'string', 'max:150'], 'date' => ['nullable', 'date_format:Y-m-d']]);
        $reservations = Reservation::query()
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['date']), fn ($q) => $q->where(fn ($q) => $q->whereDate('scheduled_at', $filters['date'])->orWhere(fn ($q) => $q->whereNull('scheduled_at')->whereDate('preferred_at', $filters['date']))))
            ->when(! empty($filters['search']), fn ($q) => $q->where(fn ($q) => $q->where('customer_name', 'like', '%'.$filters['search'].'%')->orWhere('phone', 'like', '%'.$filters['search'].'%')))
            ->latest('id')->paginate(20)->withQueryString();

        return view('reservations.index', ['reservations' => $reservations, 'filters' => $filters, 'pendingCount' => Reservation::where('status', 'pending')->count()]);
    }

    public function show(Reservation $reservation): View
    {
        Gate::authorize('view', $reservation);
        $reservation->load('customer');

        return view('reservations.show', compact('reservation'));
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation, ReservationCustomers $customers): RedirectResponse
    {
        DB::transaction(function () use ($request, $reservation, $customers): void {
            $reservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            if ($data['expected_status'] !== $reservation->status || ! in_array($data['status'], Reservation::TRANSITIONS[$reservation->status], true)) {
                throw ValidationException::withMessages(['status' => 'Cette réservation a changé ou cette action n’est plus possible. Rechargez la fiche.']);
            }
            if ($data['status'] === 'completed' && $reservation->scheduled_at?->isFuture()) {
                throw ValidationException::withMessages(['status' => 'Un rendez-vous à venir ne peut pas être marqué comme terminé.']);
            }
            if ($data['status'] === 'confirmed') {
                $reservation->customer()->associate($customers->resolve($reservation));
            }
            $reservation->update([
                'status' => $data['status'], 'scheduled_at' => $data['scheduled_at'] ?? $reservation->scheduled_at,
                'internal_notes' => $data['internal_notes'] ?? null,
                'processed_by' => $request->user()->id, 'processed_by_name' => $request->user()->name,
            ]);
        }, 3);

        $message = $request->validated('status') === 'confirmed'
            ? 'Réservation confirmée et rattachée à une fiche client. Pensez à informer le client de votre décision.'
            : 'Réservation mise à jour. Pensez à informer le client de votre décision.';

        return redirect()->route('reservations.show', $reservation)->with('success', $message);
    }
}
