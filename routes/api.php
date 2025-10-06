<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceTestController;
use App\Http\Controllers\ServiceCommunicationTestController;

// Health check endpoint for Docker and service monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'fitnease-auth',
        'timestamp' => now()
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/verify-code', [AuthController::class, 'verifyEmailCode']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
    Route::get('/email-verification-status/{userId}', [AuthController::class, 'emailVerificationStatus']);
    Route::get('/debug-verification-code/{email}', [AuthController::class, 'getVerificationCodeForDebug']);
    Route::get('/debug-user-status/{email}', [AuthController::class, 'getUserStatusForDebug']);
    Route::post('/debug-reset-verification/{email}', [AuthController::class, 'resetVerificationForDebug']);

    Route::middleware('auth.api')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::delete('/logout', [AuthController::class, 'logout']);
        Route::delete('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/tokens', [AuthController::class, 'getTokens']);
        Route::delete('/tokens/{tokenId}', [AuthController::class, 'revokeToken']);
        Route::get('/validate', [AuthController::class, 'validateToken']);
        Route::get('/user-profile/{id}', [AuthController::class, 'getUserProfile']);
    });

    Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {
        Route::put('/user-profile/{id}', [AuthController::class, 'updateProfile']);
    });

    Route::middleware(['auth:sanctum', 'ability:admin-access'])->group(function () {
        Route::post('/create-service-token', [AuthController::class, 'createServiceToken']);
    });
});

Route::middleware(['auth:sanctum', 'verified.email'])->group(function () {
    Route::post('/fitness-assessment', [AssessmentController::class, 'store']);
    Route::get('/fitness-assessments', [AssessmentController::class, 'index']);
    Route::get('/fitness-assessments/{id}', [AssessmentController::class, 'show']);
    Route::put('/fitness-assessments/{id}', [AssessmentController::class, 'update']);
    Route::delete('/fitness-assessments/{id}', [AssessmentController::class, 'destroy']);
    Route::get('/users/{userId}/assessments', [AssessmentController::class, 'getUserAssessments']);
    Route::get('/users/{userId}/assessments/{type}', [AssessmentController::class, 'getAssessmentsByType']);
});

// ML Profile Management - accessible to authenticated users
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/users/initialize-ml-profile', [UserController::class, 'initializeMLProfile']);
});

Route::middleware(['auth:sanctum', 'ability:admin-access'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::post('/assign-role', [RoleController::class, 'assignRole']);
    Route::post('/revoke-role', [RoleController::class, 'revokeRole']);
    Route::post('/assign-permission', [RoleController::class, 'assignPermission']);
    Route::post('/revoke-permission', [RoleController::class, 'revokePermission']);
    Route::get('/users/{userId}/roles', [RoleController::class, 'getUserRoles']);
    Route::get('/roles/{roleId}/permissions', [RoleController::class, 'getRolePermissions']);

    Route::get('/all-users', [UserController::class, 'getAllUsers']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/deactivate', [UserController::class, 'deactivateUser']);
    Route::post('/users/{id}/activate', [UserController::class, 'activateUser']);
    Route::put('/users/{id}/onboarding', [UserController::class, 'updateOnboardingStatus']);
    Route::get('/user-stats', [UserController::class, 'getUserStats']);
    Route::get('/users/{id}/preferences', [UserController::class, 'getUserPreferences']);
    Route::post('/bulk-update-users', [UserController::class, 'bulkUpdateUsers']);
});

// Service testing routes - for validating inter-service communication
Route::middleware('auth.api')->prefix('service-tests')->group(function () {
    Route::get('/engagement', [ServiceTestController::class, 'testEngagementService']);
    Route::get('/comms', [ServiceTestController::class, 'testCommsService']);
    Route::get('/all', [ServiceTestController::class, 'testAllServices']);

    Route::get('/connectivity', [ServiceCommunicationTestController::class, 'testServiceConnectivity']);
    Route::get('/token-validation', [ServiceCommunicationTestController::class, 'testAuthTokenValidation']);
    Route::get('/integration', [ServiceCommunicationTestController::class, 'testServiceIntegration']);
});

// ML Internal Endpoints - For ML service internal calls (no auth required)
Route::prefix('ml-internal')->group(function () {
    Route::get('/user-profile/{id}', [AuthController::class, 'getUserProfile']);
});
