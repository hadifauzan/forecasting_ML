<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles for different admin types
        $roles = [
            'superadmin' => 'Super Administrator - Full system access',
            'admin_content' => 'Content Administrator - Manage content and products',
            'admin_partner' => 'Partner Administrator - Manage affiliate partners',
            'admin_seller' => 'Seller Administrator - Manage sales and orders',
            'admin_inventory' => 'Inventory Administrator - Manage inventory and stock',
            'owner' => 'Owner - Full inventory and production oversight',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }

        // Define permissions for each role
        $permissions = [
            // Dashboard permissions
            'view_dashboard' => ['superadmin', '', 'admin_partner', 'admin_seller', 'admin_inventory', 'owner'],
            
            // User management
            'manage_users' => ['superadmin'],
            'view_users' => ['superadmin', 'admin_partner'],
            
            // Admin management
            'manage_admins' => ['superadmin'],
            'view_admins' => ['superadmin'],
            
            // Product management
            'manage_products' => ['superadmin', 'admin_content'],
            'view_products' => ['superadmin', 'admin_content', 'admin_seller'],
            
            // Order management
            'manage_orders' => ['superadmin', 'admin_seller'],
            'view_orders' => ['superadmin', 'admin_seller'],
            
            // Affiliate management
            'manage_affiliates' => ['superadmin', 'admin_partner'],
            'view_affiliates' => ['superadmin', 'admin_partner'],
            'approve_affiliates' => ['superadmin', 'admin_partner'],
            
            // Content management
            'manage_content' => ['superadmin', 'admin_content'],
            'manage_banners' => ['superadmin', 'admin_content'],
            
            // Inventory management
            'manage_inventory' => ['superadmin', 'admin_inventory', 'owner'],
            'view_inventory' => ['superadmin', 'admin_inventory', 'owner'],
            'manage_stock' => ['superadmin', 'admin_inventory', 'owner'],
            'view_stock' => ['superadmin', 'admin_inventory', 'owner'],

            // Reports and analytics
            'view_reports' => ['superadmin', 'owner'],
            'view_sales_reports' => ['superadmin', 'admin_seller', 'owner'],
            'view_affiliate_reports' => ['superadmin', 'admin_partner'],
            'view_inventory_reports' => ['superadmin', 'admin_inventory', 'owner'],
            
            // System settings
            'manage_settings' => ['superadmin'],
            'view_logs' => ['superadmin'],
        ];

        // Create permissions and assign to roles
        foreach ($permissions as $permissionName => $roleNames) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web'
            ]);

            foreach ($roleNames as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        $this->command->info('Admin roles and permissions created successfully!');
        $this->command->info('Created roles: ' . implode(', ', array_keys($roles)));
    }
}