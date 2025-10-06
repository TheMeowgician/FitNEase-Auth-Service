<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'first_name' => 'required',
            'last_name' => 'required',
            'age' => 'required|integer|between:18,100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'phone_number' => 'nullable|string|max:20',
            'activity_level' => 'nullable|in:sedentary,lightly_active,moderately_active,very_active',
        ]);

        $verificationCode = sprintf('%06d', mt_rand(100000, 999999));

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'age' => $request->age,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'phone_number' => $request->phone_number,
            'activity_level' => $request->activity_level ?? 'sedentary',
            'email_verification_token' => Str::random(64),
            'email_verification_code' => $verificationCode,
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_sent_at' => now()
        ]);

        $this->sendEmailVerification($user);

        return response()->json([
            'message' => 'Registration successful. Please check your email for verification.',
            'user_id' => $user->user_id
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Account disabled'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'error' => 'Email not verified',
                'requires_verification' => true
            ], 403);
        }

        $user->update(['last_login' => now()]);

        $abilities = $this->getUserAbilities($user);
        $token = $user->createToken('fitnease-mobile', $abilities)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'abilities' => $abilities,
            'expires_at' => now()->addDays(365)
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out from all devices']);
    }

    public function user(Request $request)
    {
        // Get user data from request attributes (set by ValidateApiToken middleware)
        $userData = $request->attributes->get('user');

        if (!$userData) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($userData);
    }

    public function verifyEmail(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json(['error' => 'Verification token required'], 400);
        }

        $user = User::where('email_verification_token', $token)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired verification token'], 400);
        }

        if ($user->email_verification_sent_at->addHours(24)->isPast()) {
            return response()->json(['error' => 'Verification token expired'], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_sent_at' => null
        ]);

        $this->sendWelcomeEmail($user);

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)
            ->where('email_verification_code', $request->code)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid verification code'], 400);
        }

        if ($user->email_verification_code_expires_at->isPast()) {
            return response()->json(['error' => 'Verification code has expired'], 400);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
            'email_verification_token' => null,
            'email_verification_sent_at' => null
        ]);

        $this->sendWelcomeEmail($user);

        return response()->json(['message' => 'Email verified successfully with code']);
    }

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or already verified'], 400);
        }

        if ($user->email_verification_sent_at &&
            $user->email_verification_sent_at->addMinutes(5)->isFuture()) {
            return response()->json(['error' => 'Please wait before requesting another verification email'], 429);
        }

        $newVerificationCode = sprintf('%06d', mt_rand(100000, 999999));

        $user->update([
            'email_verification_token' => Str::random(64),
            'email_verification_code' => $newVerificationCode,
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_sent_at' => now()
        ]);

        $this->sendEmailVerification($user);

        return response()->json(['message' => 'Verification email sent']);
    }

    public function emailVerificationStatus($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'email_verified' => !is_null($user->email_verified_at),
            'email_verified_at' => $user->email_verified_at
        ]);
    }

    public function getVerificationCodeForDebug($email)
    {
        if (!env('APP_DEBUG', false)) {
            return response()->json(['error' => 'Debug mode required'], 403);
        }

        $user = User::where('email', $email)
            ->whereNull('email_verified_at')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or already verified'], 404);
        }

        if (!$user->email_verification_code) {
            return response()->json(['error' => 'No verification code found'], 404);
        }

        return response()->json([
            'email' => $user->email,
            'verification_code' => $user->email_verification_code,
            'expires_at' => $user->email_verification_code_expires_at,
            'is_expired' => $user->email_verification_code_expires_at->isPast()
        ]);
    }

    public function getUserStatusForDebug($email)
    {
        if (!env('APP_DEBUG', false)) {
            return response()->json(['error' => 'Debug mode required'], 403);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'email' => $user->email,
            'username' => $user->username,
            'is_verified' => !is_null($user->email_verified_at),
            'email_verified_at' => $user->email_verified_at,
            'has_verification_code' => !is_null($user->email_verification_code),
            'verification_code' => $user->email_verification_code,
            'code_expires_at' => $user->email_verification_code_expires_at,
            'code_is_expired' => $user->email_verification_code_expires_at ? $user->email_verification_code_expires_at->isPast() : null,
            'created_at' => $user->created_at
        ]);
    }

    public function resetVerificationForDebug($email)
    {
        if (!env('APP_DEBUG', false)) {
            return response()->json(['error' => 'Debug mode required'], 403);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $newVerificationCode = sprintf('%06d', mt_rand(100000, 999999));

        $user->update([
            'email_verified_at' => null,
            'email_verification_code' => $newVerificationCode,
            'email_verification_code_expires_at' => now()->addMinutes(15),
            'email_verification_token' => Str::random(64),
            'email_verification_sent_at' => now()
        ]);

        return response()->json([
            'message' => 'User verification status reset successfully',
            'email' => $user->email,
            'new_verification_code' => $newVerificationCode,
            'expires_at' => $user->email_verification_code_expires_at
        ]);
    }

    public function getTokens(Request $request)
    {
        $tokens = $request->user()->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']);

        return response()->json($tokens);
    }

    public function revokeToken(Request $request, $tokenId)
    {
        $token = $request->user()->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return response()->json(['error' => 'Token not found'], 404);
        }

        $token->delete();

        return response()->json(['message' => 'Token revoked successfully']);
    }

    public function createServiceToken(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string',
            'abilities' => 'array'
        ]);

        if (!$request->user()->tokenCan('admin-access')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $serviceUser = User::where('email', 'service@fitnease.local')->first();

        if (!$serviceUser) {
            $serviceUser = User::create([
                'username' => 'system_service',
                'email' => 'service@fitnease.local',
                'password_hash' => Hash::make(Str::random(32)),
                'first_name' => 'System',
                'last_name' => 'Service',
                'age' => 25,
                'email_verified_at' => now(),
                'is_active' => true
            ]);
        }

        $token = $serviceUser->createToken(
            $request->service_name,
            $request->abilities ?? ['read-data', 'write-data']
        )->plainTextToken;

        return response()->json([
            'service_token' => $token,
            'abilities' => $request->abilities ?? ['read-data', 'write-data']
        ]);
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $token = $request->user()->currentAccessToken();

        return response()->json([
            'valid' => true,
            'user_id' => $user->user_id,
            'email' => $user->email,
            'email_verified' => !is_null($user->email_verified_at),
            'abilities' => $token->abilities,
            'token_name' => $token->name,
            'last_used_at' => $token->last_used_at
        ]);
    }

    public function getUserProfile($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function updateProfile(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $request->validate([
            'first_name' => 'sometimes|required|string|max:50',
            'last_name' => 'sometimes|required|string|max:50',
            'age' => 'sometimes|required|integer|between:18,100',
            'date_of_birth' => 'sometimes|nullable|date',
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
            'onboarding_completed' => 'sometimes|boolean',
        ]);

        // Prepare update data (fitness_level removed - now managed via fitness assessments)
        $updateData = $request->only([
            'first_name', 'last_name', 'age', 'date_of_birth', 'gender',
            'target_muscle_groups', 'fitness_goals', 'activity_level',
            'medical_conditions', 'workout_experience_years', 'available_equipment',
            'time_constraints_minutes', 'phone_number', 'profile_picture'
        ]);

        // Handle onboarding completion
        if ($request->has('onboarding_completed')) {
            $updateData['onboarding_completed'] = $request->onboarding_completed;

            if ($request->onboarding_completed) {
                $updateData['onboarding_completed_at'] = now();
            }
        }

        $user->update($updateData);

        return response()->json($user);
    }

    private function getUserAbilities($user)
    {
        $abilities = [
            'access-workouts',
            'manage-profile',
            'social-features',
            'ml-access',
            'tracking-access',
            'planning-access'
        ];

        $userRoles = $user->roles()->pluck('role_name');

        if ($userRoles->contains('admin')) {
            $abilities[] = 'admin-access';
        }

        if ($userRoles->contains('premium')) {
            $abilities[] = 'premium-features';
        }

        return $abilities;
    }

    private function sendEmailVerification($user)
    {
        try {
            $commsClient = new Client();

            $commsClient->post(env('COMMS_SERVICE_URL') . '/api/comms/send-verification', [
                'json' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'token' => $user->email_verification_token,
                    'verification_code' => $user->email_verification_code,
                    'user_name' => $user->first_name,
                    'verification_url' => env('APP_URL') . '/verify-email?token=' . $user->email_verification_token
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }

    private function sendWelcomeEmail($user)
    {
        try {
            $commsClient = new Client();

            $commsClient->post(env('COMMS_SERVICE_URL') . '/comms/send-welcome', [
                'json' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'user_name' => $user->first_name
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    }
}
