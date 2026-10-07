<?php
use App\Enums\PaymentMethod;
use App\Models\{Customer,Employee,SalonVisit,Service,ServiceCategory};
use App\Services\{CatalogPrice,Money,VisitCheckout};
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\{Computed,Locked};
use Livewire\{Component,WithPagination};

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public string $customerMode = 'walk_in';
    public string $customerSearch = '';
    public string $customerId = '';
    public string $firstName = '';
    public string $phone = '';
    public array $employees = [];
    public string $discount = '0.00';
    public string $amount = '';
    public string $paymentMethod = 'cash';
    public string $reference = '';
    public string $notes = '';
    public bool $paymentOpen = false;
    #[Locked] public array $cart = [];
    #[Locked] public string $checkoutKey;

    public function boot(): void { Gate::authorize('create',SalonVisit::class); }
    public function mount(): void { $this->checkoutKey=(string)Str::uuid(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedCustomerSearch(): void { $this->customerId=''; }
    public function updatedCustomerMode(): void { $this->customerId=''; $this->customerSearch=''; $this->resetValidation(); }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection {
        return ServiceCategory::where('active',true)->orderBy('sort_order')->orderBy('id')->get();
    }
    #[Computed]
    public function services(): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        return Service::where('active',true)
            ->whereHas('category',fn($q)=>$q->where('active',true)->whereNull('deleted_at'))
            ->with(['category','variants'=>fn($q)=>$q->where('active',true)])
            ->when($this->search!=='',fn($q)=>$q->where('name','like','%'.mb_substr($this->search,0,100).'%'))
            ->when($this->category!=='',fn($q)=>$q->where('service_category_id',$this->category))
            ->orderBy('name')->orderBy('id')->paginate(9);
    }
    #[Computed]
    public function customerResults(): \Illuminate\Support\Collection {
        if($this->customerMode!=='existing' || !auth()->user()->can('viewAny',Customer::class) || mb_strlen(trim($this->customerSearch))<2) { return collect(); }
        $search=mb_substr(trim($this->customerSearch),0,100);
        $phone=preg_replace('/[\s().-]+/','',$search);
        return Customer::select(['id','first_name','last_name','phone'])
            ->where(fn($q)=>$q->where('first_name','like','%'.$search.'%')->orWhere('last_name','like','%'.$search.'%')->orWhere('phone','like','%'.($phone ?: $search).'%'))
            ->orderBy('first_name')->orderBy('id')->limit(8)->get();
    }
    #[Computed]
    public function staff(): \Illuminate\Database\Eloquent\Collection {
        return Employee::select(['id','name'])->where('active',true)->orderBy('name')->get();
    }
    #[Computed]
    public function subtotalMinor(): int {
        return collect($this->cart)->sum(fn(array $item): int=>Money::minor($item['price_snapshot'])*$item['quantity']);
    }
    #[Computed]
    public function totalMinor(): int {
        $discount=preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$this->discount) ? Money::minor($this->discount) : 0;
        return max(0,$this->subtotalMinor-$discount);
    }
    public function add(int $serviceId,?int $variantId,CatalogPrice $pricing): void {
        $this->resetValidation();
        $key=$serviceId.'-'.($variantId ?? 'base');
        if(isset($this->cart[$key])) { $this->changeQuantity($key,1); return; }
        if(count($this->cart)>=50) { throw ValidationException::withMessages(['cart'=>'Le panier est limité à 50 lignes.']); }
        $this->cart[$key]=[...$pricing->quote($serviceId,$variantId),'quantity'=>1];
        $this->paymentOpen=false;
    }
    public function changeQuantity(string $key,int $change): void {
        if(!isset($this->cart[$key]) || !in_array($change,[-1,1],true)) { return; }
        $quantity=$this->cart[$key]['quantity']+$change;
        if($quantity<1) { $this->remove($key); return; }
        if($quantity>99) { $this->addError('cart','Maximum 99 unités par prestation.'); return; }
        $this->cart[$key]['quantity']=$quantity;
        $this->paymentOpen=false;
    }
    public function remove(string $key): void {
        unset($this->cart[$key],$this->employees[$key]);
        $this->paymentOpen=false; $this->resetValidation();
    }
    public function refreshPrices(CatalogPrice $pricing): void {
        $updated=[];
        foreach($this->cart as $key=>$item) { $updated[$key]=[...$pricing->quote($item['service_id'],$item['service_variant_id']),'quantity'=>$item['quantity']]; }
        $this->cart=$updated; $this->paymentOpen=false; $this->resetValidation();
    }
    public function reviewPayment(): void {
        $this->resetValidation();
        if(!$this->cart) { throw ValidationException::withMessages(['cart'=>'Ajoutez au moins une prestation au panier.']); }
        $this->validate(['discount'=>['required','regex:/^\d{1,10}(?:\.\d{1,2})?$/D']]);
        if(Money::minor($this->discount)>$this->subtotalMinor) { throw ValidationException::withMessages(['discount'=>'La remise ne peut pas dépasser le sous-total.']); }
        $this->amount=Money::decimal($this->totalMinor); $this->paymentOpen=true;
    }
    public function checkout(VisitCheckout $checkout): void {
        $items=[];
        foreach($this->cart as $key=>$item) {
            $items[]=['service_id'=>$item['service_id'],'service_variant_id'=>$item['service_variant_id'],'quantity'=>$item['quantity'],'expected_price'=>$item['price_snapshot'],'employee_id'=>($this->employees[$key]??null) ?: null];
        }
        $visit=$checkout->create(auth()->user(),[
            'checkout_key'=>$this->checkoutKey,'customer_mode'=>$this->customerMode,'customer_id'=>$this->customerId ?: null,
            'first_name'=>$this->firstName,'phone'=>$this->phone,'items'=>$items,'discount'=>$this->discount,
            'amount'=>$this->amount,'payment_method'=>$this->paymentMethod,'reference'=>$this->reference,'notes'=>$this->notes,
        ]);
        session()->flash('success','Vente enregistrée. Votre reçu est disponible.');
        $this->redirectRoute('visits.receipt.thermal', ['visit' => $visit, 'print' => 1]);
    }
};
?>
<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="serif text-3xl md:text-4xl">Caisse / POS</h1><p class="mt-2 text-sm text-muted">Un client arrive. Quelques gestes, et tout est enregistré.</p></div>
        <a href="{{ route('visits.index') }}" class="btn-secondary"><x-icon name="receipt"/>Ventes & reçus</a>
    </div>
    <div class="sticky top-2 z-20 mb-5 flex items-center justify-between gap-3 rounded-xl border border-stone-200 bg-white p-3 shadow-sm xl:hidden">
        <a href="#pos-catalog" class="link flex min-h-11 items-center text-sm">Catalogue</a>
        <a href="#pos-cart" class="btn min-h-11 !px-3 !text-xs"><x-icon name="cart" class="size-4"/>Panier ({{ count($cart) }}) · {{ Money::decimal($this->totalMinor) }} {{ config('salon.currency') }}</a>
    </div>
    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_400px]">
        <section id="pos-catalog" class="min-w-0 scroll-mt-24" aria-label="Catalogue des prestations">
            <div class="panel mb-5 flex flex-wrap gap-3 p-4">
                <div class="relative min-w-40 flex-1"><x-icon name="search" class="absolute left-3 top-3 text-muted"/><input wire:model.live.debounce.300ms="search" type="search" aria-label="Rechercher une prestation" placeholder="Rechercher une prestation…" class="!pl-10"/></div>
                <select wire:model.live="category" aria-label="Catégorie" class="sm:!w-52"><option value="">Toutes les catégories</option>@foreach($this->categories as $group)<option wire:key="pos-category-{{ $group->id }}" value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select>
            </div>
            <div class="mb-4 flex items-center justify-between text-xs text-muted"><span>{{ $this->services->total() }} prestation(s)</span><span wire:loading role="status">Mise à jour…</span></div>
            <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3">
                @forelse($this->services as $service)
                    <article wire:key="pos-service-{{ $service->id }}" class="panel flex flex-col overflow-hidden">
                        <div class="flex items-center gap-3 bg-[#f8f2f3] p-4">
                            @if($service->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($service->image_path) }}" alt="" class="size-11 rounded-xl object-cover" loading="lazy">@else<span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-wine"><x-icon name="scissors"/></span>@endif
                            <span class="eyebrow !tracking-wider">{{ $service->category->name }}</span>
                        </div>
                        <div class="flex flex-1 flex-col gap-4 p-4">
                            <h2 class="text-sm font-semibold leading-6">{{ $service->name }}</h2>
                            <div class="mt-auto flex flex-col gap-2">
                                @if($service->base_price !== null)
                                    <button type="button" wire:click="add({{ $service->id }}, null)" wire:loading.attr="disabled" class="flex min-h-11 items-center justify-between gap-2 rounded-xl border border-stone-200 px-3 py-2 text-left text-sm hover:border-wine hover:bg-[#f8f2f3]"><span>Tarif de base</span><span class="font-semibold text-wine">{{ $service->base_price }} {{ config('salon.currency') }} +</span></button>
                                @endif
                                @foreach($service->variants as $variant)
                                    <button type="button" wire:key="pos-variant-{{ $variant->id }}" wire:click="add({{ $service->id }}, {{ $variant->id }})" wire:loading.attr="disabled" class="flex min-h-11 items-center justify-between gap-2 rounded-xl border border-stone-200 px-3 py-2 text-left text-sm hover:border-wine hover:bg-[#f8f2f3]"><span>{{ $variant->name }}</span><span class="shrink-0 font-semibold text-wine">{{ $variant->price }} {{ config('salon.currency') }} +</span></button>
                                @endforeach
                                @if($service->base_price === null && $service->variants->isEmpty())<p class="text-xs text-muted">Aucun tarif actif disponible.</p>@endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="panel col-span-full"><x-empty-state title="Aucune prestation disponible" description="Modifiez votre recherche ou ajoutez des prestations et tarifs actifs au catalogue."/></div>
                @endforelse
            </div>
            <div class="mt-5">{{ $this->services->links() }}</div>
        </section>

        <section id="pos-cart" class="panel min-w-0 scroll-mt-24" aria-label="Panier et paiement">
            <div class="flex items-center justify-between border-b border-stone-100 p-5"><h2 class="serif text-xl">{{ $paymentOpen ? 'Encaissement' : 'Nouvelle vente' }}</h2><span class="badge-muted">{{ count($cart) }} ligne(s)</span></div>
            <div class="space-y-5 p-5">
                @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="field"><label for="pos-customer-mode">Client</label><select id="pos-customer-mode" wire:model.live="customerMode"><option value="walk_in">Client de passage — sans fiche</option>@can('viewAny',\App\Models\Customer::class)<option value="existing">Client existant</option>@endcan @can('create',\App\Models\Customer::class)<option value="new">Nouveau client</option>@endcan</select></div>
                @if($customerMode==='existing')
                    <div class="space-y-3"><input wire:model.live.debounce.300ms="customerSearch" aria-label="Rechercher un client" placeholder="Nom ou téléphone (2 caractères min.)" type="search"/>
                        @if($this->customerResults->isNotEmpty())<select wire:model="customerId" aria-label="Choisir le client"><option value="">Sélectionner le client</option>@foreach($this->customerResults as $customer)<option wire:key="pos-customer-{{ $customer->id }}" value="{{ $customer->id }}">{{ $customer->full_name }} · {{ $customer->phone }}</option>@endforeach</select>
                        @elseif(mb_strlen(trim($customerSearch))>=2)<p class="text-xs text-muted">Aucun client trouvé.</p>@endif
                    </div>
                @elseif($customerMode==='new')
                    <div class="grid gap-3"><div class="field"><label for="pos-first-name">Prénom / nom du client</label><input id="pos-first-name" wire:model="firstName" maxlength="100" autocomplete="given-name"/></div><div class="field"><label for="pos-phone">Téléphone</label><input id="pos-phone" wire:model="phone" type="tel" placeholder="+243…" autocomplete="tel"/></div></div>
                @endif

                <div class="space-y-4 border-y border-stone-100 py-4">
                    @forelse($cart as $key=>$item)
                        <div wire:key="cart-{{ $key }}" class="space-y-3">
                            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-sm font-semibold">{{ $item['service_name_snapshot'] }}</p><p class="mt-1 text-xs text-muted">{{ $item['variant_name_snapshot'] ?? 'Tarif de base' }} · {{ $item['price_snapshot'] }} {{ config('salon.currency') }}</p></div><button type="button" wire:click="remove('{{ $key }}')" wire:loading.attr="disabled" aria-label="Retirer {{ $item['service_name_snapshot'] }}" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-rose-50 hover:text-wine"><x-icon name="close" class="size-4"/></button></div>
                            <div class="flex items-center justify-between gap-3"><div class="flex items-center rounded-lg border border-stone-200"><button type="button" aria-label="Diminuer la quantité de {{ $item['service_name_snapshot'] }}" wire:click="changeQuantity('{{ $key }}', -1)" wire:loading.attr="disabled" class="size-9 text-lg">−</button><span class="min-w-7 text-center text-sm">{{ $item['quantity'] }}</span><button type="button" aria-label="Augmenter la quantité de {{ $item['service_name_snapshot'] }}" wire:click="changeQuantity('{{ $key }}', 1)" wire:loading.attr="disabled" class="size-9 text-lg">+</button></div><strong class="text-sm">{{ Money::decimal(Money::minor($item['price_snapshot'])*$item['quantity']) }} {{ config('salon.currency') }}</strong></div>
                            <select wire:model="employees.{{ $key }}" aria-label="Employé pour {{ $item['service_name_snapshot'] }}"><option value="">Employé non affecté</option>@foreach($this->staff as $employee)<option wire:key="assignment-{{ $key }}-{{ $employee->id }}" value="{{ $employee->id }}">{{ $employee->name }}</option>@endforeach</select>
                        </div>
                    @empty
                        <div class="py-6 text-center"><x-icon name="cart" class="mx-auto mb-3 size-8 text-wine"/><p class="text-sm font-medium">Votre panier est vide</p><p class="mt-2 text-xs leading-5 text-muted">Touchez un tarif pour ajouter une prestation.</p></div>
                    @endforelse
                </div>
                @if($cart)
                    <button type="button" wire:click="refreshPrices" wire:loading.attr="disabled" class="link text-xs">Actualiser les tarifs</button>
                    <div class="flex justify-between text-sm text-muted"><span>Sous-total</span><span>{{ Money::decimal($this->subtotalMinor) }} {{ config('salon.currency') }}</span></div>
                    <div class="field"><label for="pos-discount">Remise ({{ config('salon.currency') }})</label><input id="pos-discount" wire:model.live.debounce.300ms="discount" type="number" min="0" step="0.01" @disabled($paymentOpen)/></div>
                    <div class="flex items-center justify-between rounded-xl bg-[#f8f2f3] p-4"><span class="font-semibold">Total à payer</span><span class="text-xl font-semibold text-wine">{{ Money::decimal($this->totalMinor) }} <span class="text-sm">{{ config('salon.currency') }}</span></span></div>
                    @if(!$paymentOpen)
                        <button type="button" wire:click="reviewPayment" wire:loading.attr="disabled" class="btn min-h-12 w-full">Continuer vers le paiement <x-icon name="arrow" class="size-4"/></button>
                    @else
                        <form wire:submit="checkout" class="space-y-4">
                            <div class="field"><label for="pos-method">Mode de paiement</label><select id="pos-method" wire:model="paymentMethod">@foreach(PaymentMethod::cases() as $method)<option value="{{ $method->value }}">{{ $method->label() }}</option>@endforeach</select></div>
                            <div class="field"><label for="pos-amount">Montant encaissé ({{ config('salon.currency') }})</label><input id="pos-amount" wire:model="amount" type="number" min="0" step="0.01" required/><p class="text-xs leading-5 text-muted">Un paiement partiel conserve un solde à régler. Saisissez 0 pour payer plus tard.</p></div>
                            <div class="field"><label for="pos-reference">Référence du paiement (facultatif)</label><input id="pos-reference" wire:model="reference" maxlength="255"/></div>
                            <div class="field"><label for="pos-notes">Note de la vente (facultatif)</label><textarea id="pos-notes" wire:model="notes" rows="2" maxlength="2000"></textarea></div>
                            <p class="rounded-xl bg-stone-50 p-3 text-xs leading-5 text-muted">Confirmez uniquement les montants effectivement reçus. Pour un paiement mixte, enregistrez une première part puis complétez depuis le reçu.</p>
                            <button type="submit" wire:loading.attr="disabled" class="btn min-h-12 w-full"><x-icon name="check"/>Confirmer et créer le reçu</button>
                            <button type="button" wire:click="$set('paymentOpen', false)" class="btn-secondary w-full">Retour au panier</button>
                        </form>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>
