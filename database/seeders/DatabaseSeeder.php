<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            ['permission_name' => 'admin-access', 'permission_description' => 'Full admin access'],
            ['permission_name' => 'premium-features', 'permission_description' => 'Access to premium features'],
            ['permission_name' => 'access-workouts', 'permission_description' => 'Access to workout features'],
            ['permission_name' => 'manage-profile', 'permission_description' => 'Manage user profile'],
            ['permission_name' => 'social-features', 'permission_description' => 'Access to social features'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $adminRole = Role::create([
            'role_name' => 'admin',
            'role_description' => 'Administrator with full access'
        ]);

        $premiumRole = Role::create([
            'role_name' => 'premium',
            'role_description' => 'Premium user with extended features'
        ]);

        $userRole = Role::create([
            'role_name' => 'user',
            'role_description' => 'Regular user'
        ]);

        // Create test users first
        $adminUser = User::create([
            'username' => 'admin',
            'email' => 'admin@fitnease.local',
            'password_hash' => Hash::make('password'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'age' => 30,
            'email_verified_at' => now(),
            'is_active' => true
        ]);

        $testUser = User::create([
            'username' => 'testuser',
            'email' => 'test@fitnease.local',
            'password_hash' => Hash::make('password'),
            'first_name' => 'Test',
            'last_name' => 'User',
            'age' => 25,
            'fitness_level' => 'beginner',
            'activity_level' => 'moderately_active',
            'email_verified_at' => now(),
            'is_active' => true
        ]);

        // Assign permissions to roles after users exist
        $adminPermissions = Permission::all();
        foreach ($adminPermissions as $permission) {
            RolePermission::create([
                'role_id' => $adminRole->role_id,
                'permission_id' => $permission->permission_id,
                'assigned_by' => $adminUser->user_id
            ]);
        }

        // Assign roles to users
        $adminUser->roles()->attach($adminRole->role_id, [
            'assigned_by' => $adminUser->user_id,
            'assigned_at' => now()
        ]);

        $testUser->roles()->attach($userRole->role_id, [
            'assigned_by' => $adminUser->user_id,
            'assigned_at' => now()
        ]);
    }
}
