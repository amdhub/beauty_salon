<?php
use App\Models\Customer;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\{Computed,Url};
use Livewire\{Component,WithPagination};
new class extends Component {
 use WithPagination;
 #[Url] public string $search = '';
 public function updatedSearch(): void { $this->resetPage(); }
 #[Computed] public function customers(): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
 Gate::authorize('viewAny',Customer::class);
 $search = mb_substr(trim($this->search),0,100);
 $phone = preg_replace('/[\s().-]+/','',$search);
 return Customer::query()->when($search!=='',fn($query)=>$query->where(fn($q)=>$q->where('first_name','like','%'.$search.'%')->orWhere('last_name','like','%'.$search.'%')->orWhere(function($names) use ($search): void { foreach(preg_split('/\s+/', $search) as $part) { $names->where(fn($name)=>$name->where('first_name','like','%'.$part.'%')->orWhere('last_name','like','%'.$part.'%')); } })->orWhere('phone','like','%'.$phone.'%')->orWhere('email','like','%'.$search.'%')))->orderByDesc('id')->paginate(15);
 }
};
?>
<div class="panel overflow-hidden">
<div class="flex flex-wrap items-center justify-between gap-4 border-b border-stone-100 p-5"><div class="relative w-full max-w-md"><x-icon name="search" class="absolute left-3 top-3 size-5 text-muted"/><input aria-label="Rechercher un client" wire:model.live.debounce.300ms="search" type="search" placeholder="Rechercher par nom, téléphone ou e-mail…" class="!pl-10"/></div><span class="text-xs text-muted">{{ $this->customers->total() }} client(s)</span><span wire:loading class="text-xs text-wine" role="status">Recherche…</span></div>
@if($this->customers->isNotEmpty())<div class="overflow-x-auto"><table class="w-full"><thead class="bg-stone-50"><tr><th scope="col">N°</th><th>Client</th><th>Coordonnées</th><th>Inscription</th><th>Actions</th></tr></thead><tbody class="divide-y divide-stone-100">
@foreach($this->customers as $customer)<tr wire:key="customer-{{ $customer->id }}" class="hover:bg-stone-50/70"><td class="tabular-nums text-muted">{{ $this->customers->firstItem() + $loop->index }}</td><td><a href="{{ route('customers.show',$customer) }}" class="flex items-center gap-3"><span class="avatar">{{ mb_substr($customer->first_name,0,1) }}</span><span><span class="block font-semibold">{{ $customer->full_name }}</span><span class="text-xs text-muted">{{ $customer->number }}</span></span></a></td><td>{{ $customer->phone }}<span class="block text-xs text-muted">{{ $customer->email }}</span></td><td class="whitespace-nowrap text-muted">{{ $customer->created_at->format('d/m/Y') }}</td><td><x-icon-action :href="route('customers.show', $customer)" :label="'Voir la fiche de '.$customer->full_name"/></td></tr>@endforeach
</tbody></table></div><div class="p-5">{{ $this->customers->links() }}</div>
@else<x-empty-state title="Aucun client pour le moment" description="Ajoutez votre premier client ou ajustez votre recherche." icon="users"/>@endif
</div>
