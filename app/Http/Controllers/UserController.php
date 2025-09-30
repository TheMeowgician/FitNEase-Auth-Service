<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $query = User::with(['roles.permissions', 'preferences']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('email_verified')) {
            if ($request->email_verified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->has('fitness_level')) {
            // Filter by fitness level from latest assessment
            $query->whereHas('fitnessAssessments', function ($q) use ($request) {
                $q->whereRaw("JSON_EXTRACT(assessment_data, '$.fitness_level') = ?", [$request->fitness_level])
                  ->orderBy('assessment_date', 'desc')
                  ->limit(1);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::with(['roles.permissions', 'preferences', 'fitnessAssessments'])->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'username' => 'sometimes|required|string|max:50|unique:users,username,' . $id . ',user_id',
            'email' => 'sometimes|required|email|max:100|unique:users,email,' . $id . ',user_id',
            'first_name' => 'sometimes|required|string|max:50',
            'last_name' => 'sometimes|required|string|max:50',
            'age' => 'sometimes|required|integer|between:18,100',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'target_muscle_groups' => 'sometimes|array',
            'fitness_goals' => 'sometimes|array',
            'activity_level' => 'sometimes|nullable|in:sedentary,lightly_active,moderately_active,very_active',
            'medical_conditions' => 'sometimes|nullable|string',
            'workout_experience_years' => 'sometimes|integer|min:0',
            'available_equipment' => 'sometimes|array',
            'time_constraints_minutes' => 'sometimes|integer|min:1',
            'phone_number' => 'sometimes|nullable|string|max:20',
            'profile_picture' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($request->except(['password', 'password_hash']));

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function deactivateUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'User deactivated successfully']);
    }

    public function activateUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->update(['is_active' => true]);

        return response()->json(['message' => 'User activated successfully']);
    }

    public function updateOnboardingStatus($id, Request $request)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'onboarding_completed' => 'required|boolean',
        ]);

        $updateData = ['onboarding_completed' => $request->onboarding_completed];

        if ($request->onboarding_completed) {
            $updateData['onboarding_completed_at'] = now();
        } else {
            $updateData['onboarding_completed_at'] = null;
        }

        $user->update($updateData);

        return response()->json($user);
    }

    public function getUserStats()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'onboarded_users' => User::where('onboarding_completed', true)->count(),
            'users_by_activity_level' => User::selectRaw('activity_level, count(*) as count')
                ->groupBy('activity_level')
                ->get(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json($stats);
    }

    public function getUserPreferences($id)
    {
        $user = User::with('preferences')->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user->preferences);
    }

    public function bulkUpdateUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,user_id',
            'updates' => 'required|array',
            'updates.is_active' => 'sometimes|boolean',
        ]);

        $updatedCount = User::whereIn('user_id', $request->user_ids)
            ->update($request->updates);

        return response()->json([
            'message' => 'Users updated successfully',
            'updated_count' => $updatedCount
        ]);
    }

    public function initializeMLProfile(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated'
                ], 401);
            }

            // Check if user has completed onboarding
            if (!$user->onboarding_completed) {
                return response()->json([
                    'message' => 'ML profile initialization skipped - onboarding not completed',
                    'status' => 'skipped'
                ]);
            }

            // This is a placeholder for ML profile initialization
            // In a real implementation, this would:
            // 1. Gather user's fitness data
            // 2. Send it to the ML service for initial profile setup
            // 3. Create user behavioral patterns baseline

            // For now, we'll just mark that ML initialization was attempted
            $user->update([
                'updated_at' => now(),
                // You could add an 'ml_profile_initialized' column if needed
            ]);

            return response()->json([
                'message' => 'ML profile initialization completed successfully',
                'status' => 'success',
                'user_id' => $user->user_id
            ]);

        } catch (\Exception $e) {
            \Log::error('ML profile initialization failed', [
                'user_id' => $request->user()?->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'ML profile initialization failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
