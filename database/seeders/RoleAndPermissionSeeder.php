<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'suspend users',
            
            // Business permissions
            'view businesses',
            'create businesses',
            'edit businesses',
            'delete businesses',
            'verify businesses',
            
            // Venue permissions
            'view venues',
            'create venues',
            'edit venues',
            'delete venues',
            'approve venues',
            
            // Offer permissions
            'view offers',
            'create offers',
            'edit offers',
            'delete offers',
            'approve offers',
            
            // Review permissions
            'view reviews',
            'moderate reviews',
            'delete reviews',
            
            // Category permissions
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Analytics permissions
            'view analytics',
            'view revenue',
            'export data',
            
            // System permissions
            'manage settings',
            'view logs',
            'manage backups',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - all permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - most permissions except system management
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view users', 'create users', 'edit users', 'suspend users',
            'view businesses', 'edit businesses', 'verify businesses',
            'view venues', 'edit venues', 'approve venues',
            'view offers', 'edit offers', 'approve offers',
            'view reviews', 'moderate reviews', 'delete reviews',
            'view categories', 'create categories', 'edit categories',
            'view analytics', 'view revenue',
        ]);

        // Moderator - content moderation
        $moderator = Role::create(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'view users', 'view businesses', 'view venues', 'approve venues',
            'view offers', 'approve offers', 'view reviews', 'moderate reviews',
            'view categories',
        ]);

        // Business Owner - manage own business
        $businessOwner = Role::create(['name' => 'business-owner']);
        $businessOwner->givePermissionTo([
            'create venues', 'edit venues', 'create offers', 'edit offers',
        ]);

        // User - basic user permissions
        $user = Role::create(['name' => 'user']);
        // Users don't need explicit permissions for basic actions
    }
}