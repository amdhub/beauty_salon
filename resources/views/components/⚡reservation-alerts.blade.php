<?php

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public string $mode = 'bell';

    public function boot(): void
    {
        abort_unless(auth()->check(), 403);
    }

    #[Computed]
    public function canViewReservations(): bool
    {
        return Gate::allows('viewAny', Reservation::class);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return $this->canViewReservations ? Reservation::where('status', 'pending')->count() : 0;
    }

    #[Computed]
    public function latestReservations(): Collection
    {
        return $this->canViewReservations
            ? Reservation::where('status', 'pending')->latest('id')->limit(5)
                ->get(['id', 'customer_name', 'service_name_snapshot', 'preferred_at', 'created_at'])
            : new Collection;
    }
};
?>

<div @if($this->canViewReservations) wire:poll.15s @endif>
    @if($mode === 'banner')
        <div role="status" aria-live="polite" aria-atomic="true">
            @if($this->pendingCount > 0)
                <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3"><span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-800"><x-icon name="bell"/></span><div><p class="font-semibold text-amber-950">{{ $this->pendingCount }} {{ $this->pendingCount === 1 ? 'réservation attend votre attention' : 'réservations attendent votre attention' }}</p><p class="mt-1 text-sm text-amber-900">Des clients ont demandé un rendez-vous en ligne. Vérifiez les horaires et répondez à leurs demandes.</p></div></div>
                    <a href="{{ route('reservations.index', ['status' => 'pending']) }}" class="btn-secondary shrink-0">Voir les demandes<x-icon name="arrow" class="size-4"/></a>
                </div>
            @endif
        </div>
    @else
        <div class="relative" x-data="{ notificationsOpen: false }" @click.outside="notificationsOpen = false" @keydown.escape.window="if (notificationsOpen) { notificationsOpen = false; $refs.notificationsButton.focus(); }">
            <button type="button" x-ref="notificationsButton" @click="notificationsOpen = !notificationsOpen" :aria-expanded="notificationsOpen" aria-expanded="false" aria-controls="notifications-panel" aria-label="{{ $this->pendingCount > 0 ? 'Notifications : '.$this->pendingCount.' réservation(s) en attente' : 'Notifications' }}" title="Notifications" class="relative flex size-11 items-center justify-center rounded-xl text-muted transition hover:bg-[#f5eaed] hover:text-wine">
                <x-icon name="bell"/>
                @if($this->pendingCount > 0)<span class="absolute right-0 top-0 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold text-white" aria-hidden="true">{{ $this->pendingCount > 99 ? '99+' : $this->pendingCount }}</span>@endif
            </button>
            <section id="notifications-panel" aria-labelledby="notifications-title" x-cloak x-show="notificationsOpen" x-transition.opacity class="fixed right-4 top-20 z-50 w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-lg sm:absolute sm:right-0 sm:top-full sm:mt-3 sm:w-80">
                <div class="flex items-center justify-between border-b border-stone-100 px-5 py-3"><h2 id="notifications-title" class="text-sm font-semibold">Notifications</h2><button type="button" @click="notificationsOpen = false; $refs.notificationsButton.focus()" aria-label="Fermer les notifications" class="flex size-11 items-center justify-center rounded-xl text-muted hover:bg-stone-50"><x-icon name="close" class="size-4"/></button></div>
                @if($this->pendingCount > 0)
                    <p class="px-5 py-3 text-xs font-semibold text-wine" role="status">{{ $this->pendingCount }} réservation(s) à traiter</p>
                    <ul class="max-h-72 divide-y divide-stone-100 overflow-y-auto">
                        @foreach($this->latestReservations as $reservation)
                            <li wire:key="reservation-alert-{{ $reservation->id }}"><a href="{{ route('reservations.show', $reservation) }}" class="flex gap-3 px-5 py-4 hover:bg-stone-50"><span class="avatar !size-9"><x-icon name="calendar" class="size-4"/></span><span class="min-w-0"><span class="block text-sm font-semibold">{{ $reservation->customer_name }}</span><span class="mt-1 block truncate text-xs text-muted">{{ $reservation->service_name_snapshot }}</span><span class="mt-1 block text-xs text-wine">Souhaité : {{ $reservation->preferred_at->format('d/m/Y à H:i') }}</span><span class="mt-1 block text-[11px] text-muted">Reçue le {{ $reservation->created_at->format('d/m à H:i') }}</span></span></a></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('reservations.index', ['status' => 'pending']) }}" class="block border-t border-stone-100 px-5 py-4 text-center text-sm font-semibold text-wine hover:bg-stone-50">Toutes les demandes en attente</a>
                @else
                    <div class="flex flex-col items-center gap-3 px-5 py-8 text-center"><span class="flex size-12 items-center justify-center rounded-full bg-[#f8f2f3] text-wine"><x-icon name="bell"/></span><p class="text-sm font-medium">Aucune notification pour le moment</p><p class="text-xs leading-5 text-muted">Vous êtes à jour.</p></div>
                @endif
            </section>
        </div>
    @endif
</div>
