<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\Category;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
          // 1) Roles then Users (order matters)
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
        ]);


    }
}
