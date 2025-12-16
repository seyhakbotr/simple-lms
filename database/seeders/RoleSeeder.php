<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ["name" => "admin", "description" => "Admin Privilege"],
            ["name" => "staff", "description" => "Staff Privilege"],
            ["name" => "borrower", "description" => "Borrower Privilege"],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(["name" => $role["name"]], $role);
        }
    }
}
