<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes the fitness_level column from user_profiles table.
     * Fitness level is now stored ONLY in fitness_assessments.assessment_data
     * and accessed via the User model accessor.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fitness_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('fitness_level', ['beginner', 'medium', 'expert'])
                ->nullable()
                ->after('gender');
        });
    }
};