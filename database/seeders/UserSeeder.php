<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    protected $table = 'master_users';
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Role 1: Superadmin
            [
                'company_id' => 3, // Gentle Living
                'role_id' => 5,
                'name' => 'Super Administrator',
                'email' => 'superadmin@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'phone' => '081234567890',
                'status' => 'Aktif',
            ],
            // Role 2: Admin Konten
            [
                'company_id' => 3,
                'role_id' => 7,
                'name' => 'Content Manager',
                'email' => 'content@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'phone' => '081234567891',
                'status' => 'Aktif',
            ],
            // Role 3: Admin Partner
            [
                'company_id' => 3,
                'role_id' => 8,
                'name' => 'Partner Manager',
                'email' => 'partner@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'phone' => '081234567892',
                'status' => 'Aktif',
            ],
            // Role 4: Admin Seller
            [
                'company_id' => 3,
                'role_id' => 9,
                'name' => 'Sales Manager',
                'email' => 'sales@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'phone' => '081234567893',
                'status' => 'Aktif',
            ],
            // Role 5: User (Regular User)
            [
                'company_id' => 3,
                'role_id' => 6,
                'name' => 'Regular User',
                'email' => 'user@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('user123'),
                'phone' => '081234567894',
                'status' => 'Aktif',
            ],
            // Role 6: Affiliator
            [
                'company_id' => 3,
                'role_id' => 4,
                'name' => 'Affiliate User',
                'email' => 'affiliate@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('affiliate123'),
                'phone' => '081234567895',
                'status' => 'Aktif',
            ],
            // Role 10: Admin Inventory
            [
                'company_id' => 3,
                'role_id' => 10,
                'name' => 'Inventory Admin',
                'email' => 'inventory@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('admin123'),
                'phone' => '081234567896',
                'status' => 'Aktif',
            ],
            // Role 11: Owner
            [
                'company_id' => 3,
                'role_id' => 11,
                'name' => 'Owner',
                'email' => 'owner@gentleliving.com',
                'email_verified_at' => now(),
                'password' => Hash::make('owner123'),
                'phone' => '081234567897',
                'status' => 'Aktif',
            ],
        ];

        foreach ($users as $user) {
            DB::table('master_users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, ['email_verified_at' => now()])
            );
        }
    }
}
