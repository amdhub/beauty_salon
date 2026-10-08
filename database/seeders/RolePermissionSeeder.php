<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'customers.view' => 'Consulter les clients', 'customers.manage' => 'Gérer les clients',
            'employees.view' => 'Consulter l’équipe', 'employees.manage' => 'Gérer l’équipe',
            'catalog.view' => 'Consulter le catalogue', 'catalog.manage' => 'Gérer les prestations et tarifs',
            'sales.view' => 'Consulter les ventes et reçus', 'sales.manage' => 'Utiliser le POS et enregistrer les paiements',
            'inventory.view' => 'Consulter les stocks et mouvements', 'inventory.manage' => 'Gérer les produits et approvisionnements', 'inventory.consume' => 'Enregistrer les consommations',
            'reservations.view' => 'Consulter les réservations', 'reservations.manage' => 'Traiter les réservations',
            'settings.manage' => 'Gérer les paramètres du salon',
            'staff_debts.view' => 'Consulter les dettes de l’équipe', 'staff_debts.manage' => 'Enregistrer les dettes et remboursements de l’équipe',
            'users.manage' => 'Gérer les comptes utilisateurs', 'roles.manage' => 'Gérer les rôles et permissions',
        ];
        foreach ($permissions as $slug => $name) {
            Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
        $roles = [
            'administrator' => ['Administrateur', array_keys($permissions)],
            'manager' => ['Responsable', ['customers.view', 'customers.manage', 'employees.view', 'employees.manage', 'catalog.view', 'catalog.manage', 'sales.view', 'sales.manage', 'inventory.view', 'inventory.manage', 'inventory.consume', 'reservations.view', 'reservations.manage', 'settings.manage', 'staff_debts.view', 'staff_debts.manage']],
            'receptionist' => ['Réception / Caisse', ['customers.view', 'customers.manage', 'employees.view', 'catalog.view', 'sales.view', 'sales.manage', 'inventory.view', 'inventory.consume', 'reservations.view', 'reservations.manage']],
            'beautician' => ['Esthéticienne / Employé', ['catalog.view']],
        ];
        foreach ($roles as $slug => [$name,$grants]) {
            $role = Role::firstOrCreate(['slug' => $slug], ['name' => $name]);
            if ($role->wasRecentlyCreated || $slug === 'administrator') {
                $role->permissions()->sync(Permission::whereIn('slug', $grants)->pluck('id'));
            }
        }
    }
}
