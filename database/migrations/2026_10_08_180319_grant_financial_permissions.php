<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses.view' => 'Consulter les dépenses', 'expenses.manage' => 'Enregistrer les dépenses', 'reports.financial' => 'Consulter les rapports financiers'] as $slug => $name) {
            DB::table('permissions')->insertOrIgnore(['slug' => $slug, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
            foreach (DB::table('roles')->whereIn('slug', ['administrator', 'manager'])->pluck('id') as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['expenses.view', 'expenses.manage', 'reports.financial'])->delete();
    }
};
