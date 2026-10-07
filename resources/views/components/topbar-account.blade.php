<nav aria-label="Menu du compte" class="flex items-center gap-1 sm:gap-2">
    <livewire:reservation-alerts mode="bell"/>

    <div
        class="relative"
        x-data="{ accountOpen: false }"
        @click.outside="accountOpen = false"
        @focusout="if (!$el.contains($event.relatedTarget)) accountOpen = false"
        @keydown.escape.window="if (accountOpen) { accountOpen = false; $refs.accountButton.focus(); }"
    >
        <button
            type="button"
            x-ref="accountButton"
            @click="accountOpen = !accountOpen"
            @keydown.arrow-down.prevent="accountOpen = true; $nextTick(() => $refs.profileLink.focus())"
            :aria-expanded="accountOpen"
            aria-expanded="false"
            aria-controls="account-panel"
            aria-label="Ouvrir le menu du compte"
            class="flex min-h-11 items-center gap-2 rounded-xl px-1.5 py-1 text-ink transition hover:bg-stone-50 sm:gap-3 sm:px-3"
        >
            <span class="avatar !size-9" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="hidden min-w-0 text-left md:block">
                <span class="block max-w-36 truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                <span class="block text-xs text-muted">Mon compte</span>
            </span>
            <x-icon name="chevron-down" class="size-4 text-muted transition-transform" x-bind:class="{ 'rotate-180': accountOpen }"/>
        </button>

        <div
            id="account-panel"
            x-cloak
            x-show="accountOpen"
            x-transition.opacity
            class="absolute right-0 top-full z-50 mt-3 w-64 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-lg"
        >
            <div class="border-b border-stone-100 px-4 py-4">
                <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                <p class="mt-1 truncate text-xs text-muted">{{ auth()->user()->email }}</p>
            </div>
            <div class="p-2">
                <a x-ref="profileLink" href="{{ route('account') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-ink hover:bg-stone-50" @if(request()->routeIs('account')) aria-current="page" @endif>
                    <x-icon name="user" class="size-5 text-muted"/>
                    Profil
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-stone-100 pt-1">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-medium text-wine hover:bg-[#f5eaed]">
                        <x-icon name="logout"/>
                        Déconnexion
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
