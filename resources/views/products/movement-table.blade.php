<div class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-stone-50"><tr><th scope="col">N°</th><th>Date / Mouvement</th><th>Produit</th><th>Quantité</th><th>Stock après</th><th>Détails</th><th>Enregistré par</th></tr></thead>
            <tbody class="divide-y divide-stone-100">
                @forelse($movements as $movement)
                    <tr>
                        <td class="tabular-nums text-muted">{{ $movements->firstItem() + $loop->index }}</td>
                        <td><p class="whitespace-nowrap">{{ $movement->movement_date->format('d/m/Y') }}</p><span class="mt-2 {{ $movement->type === 'supply' ? 'badge' : 'badge-muted' }}">{{ $movement->label }}</span><p class="mt-1 text-xs text-muted">MVT-{{ str_pad($movement->id, 6, '0', STR_PAD_LEFT) }}</p></td>
                        <td><a href="{{ route('products.show', $movement->consumable_product_id) }}" class="link">{{ $movement->product_name_snapshot }}</a></td>
                        <td class="whitespace-nowrap font-semibold">{{ $movement->type === 'supply' ? '+' : '−' }}{{ $movement->quantity }} {{ $movement->unit_snapshot }}</td>
                        <td class="whitespace-nowrap">{{ $movement->stock_after }} {{ $movement->unit_snapshot }}</td>
                        <td class="min-w-52 max-w-sm break-words text-xs">
                            @if($movement->supplier)<p>Fournisseur : {{ $movement->supplier }}</p>@endif
                            @if($movement->total_cost !== null)<p class="mt-1">Coût total : {{ $movement->total_cost }} {{ $movement->currency }}</p>@endif
                            @if($movement->employee_name_snapshot)<p>Utilisé par : {{ $movement->employee_name_snapshot }}</p>@endif
                            @if($movement->reference)<p class="mt-1">Réf. : {{ $movement->reference }}</p>@endif
                            @if($movement->notes)<p class="mt-1 whitespace-pre-line text-muted">{{ $movement->notes }}</p>@endif
                        </td>
                        <td><p>{{ $movement->actor_name_snapshot }}</p><p class="mt-1 whitespace-nowrap text-xs text-muted">{{ $movement->created_at->format('d/m/Y H:i') }}</p></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="Aucun mouvement" description="Les approvisionnements et consommations enregistrés apparaîtront ici." icon="layers"/></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $movements->links() }}</div>
