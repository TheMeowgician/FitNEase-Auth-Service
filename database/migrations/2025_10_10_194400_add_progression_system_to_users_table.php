<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add fitness_level column (it was removed in a previous migration)
            if (!Schema::hasColumn('users', 'fitness_level')) {
                $table->enum('fitness_level', ['beginner', 'intermediate', 'advanced'])->default('beginner')->after('gender');
            }

            // Add progression tracking columns
            $table->timestamp('fitness_level_updated_at')->nullable()->after('fitness_level');
            $table->integer('total_workouts_completed')->default(0)->after('fitness_level_updated_at');
            $table->integer('total_workout_minutes')->default(0)->after('total_workouts_completed');
            $table->integer('advanced_workouts_completed')->default(0)->after('total_workout_minutes');
            $table->integer('longest_streak_days')->default(0)->after('advanced_workouts_completed');
            $table->integer('current_streak_days')->default(0)->after('longest_streak_days');
            $table->integer('goals_achieved_count')->default(0)->after('current_streak_days');
            $table->integer('group_workouts_count')->default(0)->after('goals_achieved_count');
            $table->date('last_workout_date')->nullable()->after('group_workouts_count');
            $table->integer('profile_completeness_percentage')->default(0)->after('last_workout_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop progression columns
            $table->dropColumn([
                'fitness_level',
                'fitness_level_updated_at',
                'total_workouts_completed',
                'total_workout_minutes',
                'advanced_workouts_completed',
                'longest_streak_days',
                'current_streak_days',
                'goals_achieved_count',
                'group_workouts_count',
                'last_workout_date',
                'profile_completeness_percentage',
            ]);
        });
    }
};
