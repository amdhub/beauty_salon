<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales.view' => 'Consulter les ventes et reçus', 'sales.manage' => 'Utiliser le POS et enregistrer les paiements'] as $slug => $name) {
            DB::table('permissions')->insertOrIgnore(['slug' => $slug, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            foreach (DB::table('roles')->whereIn('slug', ['administrator', 'manager', 'receptionist'])->pluck('id') as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['sales.view', 'sales.manage'])->delete();
    }
};
