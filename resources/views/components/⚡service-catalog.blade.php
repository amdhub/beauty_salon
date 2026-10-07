<?php
use App\Models\{Service,ServiceCategory};
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\{Computed,Url};
use Livewire\{Component,WithPagination};
new class extends Component {
 use WithPagination;
 #[Url] public string $search = '';
 #[Url] public string $category = '';
 #[Url] public string $status = '';
 public function updatedSearch(): void { $this->resetPage(); }
 public function updatedCategory(): void { $this->resetPage(); }
 public function updatedStatus(): void { $this->resetPage(); }
 #[Computed] public function categories(): \Illuminate\Database\Eloquent\Collection { Gate::authorize('viewAny',Service::class); return ServiceCategory::orderBy('sort_order')->orderBy('id')->get(); }
 #[Computed] public function services(): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
 Gate::authorize('viewAny',Service::class);
 return Service::with('category')->withCount('variants')->withMin(['variants'=>fn($q)=>$q->where('active',true)],'price')
 ->when($this->search!=='',fn($q)=>$q->where('name','like','%'.mb_substr($this->search,0,100).'%'))
 ->when($this->category!=='',fn($q)=>$q->where('service_category_id',$this->category))
 ->when(in_array($this->status,['active','inactive'],true),fn($q)=>$q->where('active',$this->status==='active'))
 ->orderBy('name')->orderBy('id')->paginate(12);
 }
};
?>
<div>
<div class="panel mb-6 flex flex-wrap gap-3 p-4"><div class="relative min-w-52 flex-1"><x-icon name="search" class="absolute left-3 top-3 text-muted"/><input aria-label="Rechercher une prestation" type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher une prestation…" class="!pl-10"/></div><select aria-label="Filtrer par catégorie" wire:model.live="category" class="!w-full sm:!w-60"><option value="">Toutes les catégories</option>@foreach($this->categories as $group)<option value="{{ $group->id }}" wire:key="category-{{ $group->id }}">{{ $group->name }}</option>@endforeach</select><select aria-label="Filtrer par statut" wire:model.live="status" class="!w-full sm:!w-40"><option value="">Tous les statuts</option><option value="active">Actives</option><option value="inactive">Inactives</option></select></div>
<p class="mb-4 text-xs text-muted">{{ $this->services->total() }} prestation(s) <span wire:loading class="ml-3 text-wine" role="status">Recherche…</span></p>
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">@forelse($this->services as $service)<article wire:key="service-{{ $service->id }}" class="panel group flex flex-col overflow-hidden transition hover:border-wine/40 hover:shadow-sm">
<div class="flex h-28 items-center justify-center overflow-hidden bg-[#f3ebe6] text-[#9c7b6a]">@if($service->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($service->image_path) }}" class="size-full object-cover" alt="" loading="lazy">@else<x-icon name="flower" class="size-12 opacity-60"/>@endif</div>
<div class="flex flex-1 flex-col p-5"><div class="mb-3"><x-list-number :number="$this->services->firstItem() + $loop->index"/></div><div class="flex items-center justify-between gap-3"><p class="eyebrow !tracking-wider">{{ $service->category->name }}</p><span class="{{ $service->active && $service->category->active && !$service->category->trashed() ? 'badge' : 'badge-muted' }}">{{ $service->active && $service->category->active && !$service->category->trashed() ? 'Active' : 'Inactive' }}</span></div><h2 class="serif mt-3 text-xl leading-7 group-hover:text-wine">{{ $service->name }}</h2><p class="mt-2 line-clamp-2 text-xs leading-5 text-muted">{{ $service->description ?: 'Un soin personnalisé, adapté à vos besoins.' }}</p><div class="mt-auto flex items-end justify-between gap-3 pt-6"><div><p class="text-[10px] text-muted">{{ $service->variants_min_price !== null ? 'À partir de' : 'Prix de base' }}</p><p class="mt-1 font-semibold">{{ $service->variants_min_price ?? $service->base_price ?? '—' }} <span class="text-xs font-normal text-muted">{{ config('salon.currency') }}</span></p></div><span class="flex items-center gap-1.5 text-xs text-muted"><x-icon name="clock" class="size-3.5"/>{{ $service->default_duration }} min</span></div><div class="mt-4 flex justify-end border-t border-stone-100 pt-3"><x-icon-action :href="route('services.show', $service)" :label="'Voir la prestation '.$service->name"/></div></div></article>
@empty<div class="panel col-span-full"><x-empty-state title="Votre catalogue prend forme" description="Aucune prestation ne correspond à cette sélection. Modifiez les filtres ou créez une prestation."/></div>@endforelse</div><div class="mt-6">{{ $this->services->links() }}</div>
</div>
