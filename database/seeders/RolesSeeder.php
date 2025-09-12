<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // === Define permissions ===
        $permissions = [
            ['name' => 'pos.menu',              'group_name' => 'pos'],
            ['name' => 'orders.menu',           'group_name' => 'orders'],
            ['name' => 'products.menu',         'group_name' => 'products'],
            ['name' => 'product_variants.menu', 'group_name' => 'product_variants'],
            ['name' => 'categories.menu',       'group_name' => 'categories'],
            ['name' => 'stock.menu',            'group_name' => 'stock'],
            ['name' => 'roles.menu',            'group_name' => 'roles'],
            ['name' => 'user.menu',             'group_name' => 'user'],
            ['name' => 'database.menu',         'group_name' => 'database'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p['name']], ['group_name' => $p['group_name']]);
        }

        // === Roles ===
        $roleAdmin   = Role::firstOrCreate(['name' => 'admin']);
        $roleCashier = Role::firstOrCreate(['name' => 'cashier']);

        // Admin gets everything
        $roleAdmin->syncPermissions(Permission::all());

        // Cashier gets a subset
        $cashierPerms = Permission::whereIn('name', [
            'pos.menu',
            'orders.menu',
            'products.menu',
            'product_variants.menu',
            'categories.menu',
            'stock.menu',
        ])->get();
        $roleCashier->syncPermissions($cashierPerms);
    }
}
