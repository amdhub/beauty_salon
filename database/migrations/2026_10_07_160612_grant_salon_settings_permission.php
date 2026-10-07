<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore(['slug' => 'settings.manage', 'name' => 'Gérer les paramètres du salon', 'created_at' => now(), 'updated_at' => now()]);
        $permissionId = DB::table('permissions')->where('slug', 'settings.manage')->value('id');
        foreach (DB::table('roles')->whereIn('slug', ['administrator', 'manager'])->pluck('id') as $roleId) {
            DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'settings.manage')->delete();
    }
};
