<?php

namespace Database\Seeders;

use App\Models\MasterRole as Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected $table = 'master_roles';
    public function run(): void
    {
        $roles = [
            ['role_id' => 1, 'name_role' => 'admin'],
            ['role_id' => 2, 'name_role' => 'manager'],
            ['role_id' => 3, 'name_role' => 'staff'],
            ['role_id' => 4, 'name_role' => 'affiliator'],
            ['role_id' => 5, 'name_role' => 'superadmin'],
            ['role_id' => 6, 'name_role' => 'user'],
            ['role_id' => 7, 'name_role' => 'admin_content'],
            ['role_id' => 8, 'name_role' => 'admin_partner'],
            ['role_id' => 9, 'name_role' => 'admin_seller'],
            ['role_id' => 10, 'name_role' => 'admin_inventory'],
            ['role_id' => 11, 'name_role' => 'owner'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['role_id' => $role['role_id']], 
                ['name_role' => $role['name_role']]
            );
        }
    }
}
