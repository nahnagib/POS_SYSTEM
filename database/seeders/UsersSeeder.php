<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $adminEmail   = env('ADMIN_EMAIL',   'admin@pos.local');
        $adminPass    = env('ADMIN_PASSWORD','admin123');
        $cashierEmail = env('CASHIER_EMAIL', 'cashier@pos.local');
        $cashierPass  = env('CASHIER_PASSWORD','cashier123');

        // Ensure roles exist (in case RolesSeeder not yet run)
        $adminRole   = Role::firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);

        // Admin (Owner)
        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'              => 'Owner (Admin)',
                'username'          => 'admin',
                'password'          => Hash::make($adminPass),
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
            ]
        );
        $admin->syncRoles([$adminRole]);

        // Cashier
        $cashier = User::updateOrCreate(
            ['email' => $cashierEmail],
            [
                'name'              => 'Cashier',
                'username'          => 'cashier',
                'password'          => Hash::make($cashierPass),
                'email_verified_at' => now(),
                'remember_token'    => Str::random(10),
            ]
        );
        $cashier->syncRoles([$cashierRole]);
    }
}
