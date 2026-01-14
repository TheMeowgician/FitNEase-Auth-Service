<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds mentor and member roles for FitNEase role-based access.
     */
    public function up(): void
    {
        // Insert member role if it doesn't exist
        $memberExists = DB::table('roles')->where('role_name', 'member')->exists();
        if (!$memberExists) {
            DB::table('roles')->insert([
                'role_name' => 'member',
                'role_description' => 'Regular fitness member',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Insert mentor role if it doesn't exist
        $mentorExists = DB::table('roles')->where('role_name', 'mentor')->exists();
        if (!$mentorExists) {
            DB::table('roles')->insert([
                'role_name' => 'mentor',
                'role_description' => 'Fitness mentor who leads training groups',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only delete if no users are assigned to these roles
        $memberRole = DB::table('roles')->where('role_name', 'member')->first();
        if ($memberRole) {
            $memberHasUsers = DB::table('user_roles')->where('role_id', $memberRole->role_id)->exists();
            if (!$memberHasUsers) {
                DB::table('roles')->where('role_name', 'member')->delete();
            }
        }

        $mentorRole = DB::table('roles')->where('role_name', 'mentor')->first();
        if ($mentorRole) {
            $mentorHasUsers = DB::table('user_roles')->where('role_id', $mentorRole->role_id)->exists();
            if (!$mentorHasUsers) {
                DB::table('roles')->where('role_name', 'mentor')->delete();
            }
        }
    }
};
