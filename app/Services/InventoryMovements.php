<?php

namespace App\Services;

use App\Models\ConsumableProduct;
use App\Models\Employee;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class InventoryMovements
{
    /** @param array<string, mixed> $data Validated StockMovementRequest input. */
    public function record(ConsumableProduct $product, User $actor, string $type, array $data): StockMovement
    {
        abort_unless(in_array($type, ['supply', 'consume'], true), 404);
        Gate::forUser($actor)->authorize($type, $product);

        return DB::transaction(function () use ($product, $actor, $type, $data): StockMovement {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $existing = StockMovement::where('created_by', $actor->id)->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                if ($existing->consumable_product_id !== $product->id || $existing->type !== $type) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Ce formulaire a déjà servi pour un autre mouvement. Rechargez la page.']);
                }

                return $existing;
            }
            $product = ConsumableProduct::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (! $product->active) {
                throw ValidationException::withMessages(['quantity' => 'Réactivez ce produit avant de saisir un mouvement.']);
            }
            $quantity = StockQuantity::minor((string) $data['quantity']);
            if ($quantity <= 0 || ($product->unit === 'piece' && $quantity % 1000 !== 0)) {
                throw ValidationException::withMessages(['quantity' => 'Saisissez une quantité positive, entière pour les pièces.']);
            }
            $stock = StockQuantity::minor($product->stock);
            $newStock = $type === 'supply' ? $stock + $quantity : $stock - $quantity;
            if ($newStock < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock insuffisant : '.$product->stock.' '.$product->unit.' disponible(s).']);
            }
            if ($newStock > StockQuantity::MAX_MINOR) {
                throw ValidationException::withMessages(['quantity' => 'La quantité dépasse la capacité maximale du stock.']);
            }
            $employee = null;
            if ($type === 'consume' && ! empty($data['employee_id'])) {
                $employee = Employee::whereKey($data['employee_id'])->where('active', true)->lockForUpdate()->first();
                if (! $employee) {
                    throw ValidationException::withMessages(['employee_id' => 'Sélectionnez un membre actif de l’équipe.']);
                }
            }
            $product->stock = StockQuantity::decimal($newStock);
            $product->save();

            return $product->movements()->create([
                'type' => $type, 'quantity' => StockQuantity::decimal($quantity), 'stock_after' => $product->stock,
                'product_name_snapshot' => $product->name, 'unit_snapshot' => $product->unit,
                'movement_date' => $data['movement_date'], 'supplier' => $type === 'supply' ? ($data['supplier'] ?? null) : null,
                'reference' => $data['reference'] ?? null, 'total_cost' => $type === 'supply' ? ($data['total_cost'] ?? null) : null,
                'currency' => config('salon.currency'), 'employee_id' => $employee?->id, 'employee_name_snapshot' => $employee?->name,
                'notes' => $data['notes'] ?? null, 'created_by' => $actor->id, 'actor_name_snapshot' => $actor->name,
                'idempotency_key' => $data['idempotency_key'],
            ]);
        }, 3);
    }
}
