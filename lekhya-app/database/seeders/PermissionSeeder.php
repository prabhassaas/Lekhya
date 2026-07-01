<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder {
    public function run(): void {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $permissions = [
            'invoices.view','invoices.create','invoices.post','invoices.cancel',
            'journals.view','journals.create','journals.reverse',
            'accounts.view','accounts.create','accounts.edit',
            'reports.view','reports.export',
            'gst.view','gst.file',
            'connector.manage','connector.tokens.generate',
            'tally.import',
            'settings.manage',
            'ai.use',
            'pramaan.access',
        ];
        foreach ($permissions as $p) Permission::firstOrCreate(['name' => $p]);

        $roles = [
            'owner'      => $permissions,
            'accountant' => ['invoices.view','invoices.create','invoices.post','journals.view','journals.create','journals.reverse','accounts.view','reports.view','reports.export','gst.view','ai.use'],
            'ca'         => array_merge(['pramaan.access','gst.file'], $permissions),
            'viewer'     => ['invoices.view','journals.view','accounts.view','reports.view'],
        ];
        foreach ($roles as $roleName => $rolePerms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePerms);
        }
    }
}
