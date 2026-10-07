<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'inventory.view' => 'Consulter les stocks et mouvements',
            'inventory.manage' => 'Gérer les produits et approvisionnements',
            'inventory.consume' => 'Enregistrer les consommations',
        ];
        foreach ($permissions as $slug => $name) {
            DB::table('permissions')->insertOrIgnore(['slug' => $slug, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            $roles = $slug === 'inventory.manage' ? ['administrator', 'manager'] : ['administrator', 'manager', 'receptionist'];
            foreach (DB::table('roles')->whereIn('slug', $roles)->pluck('id') as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['inventory.view', 'inventory.manage', 'inventory.consume'])->delete();
    }
};
