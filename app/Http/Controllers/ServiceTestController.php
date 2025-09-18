<?php

namespace App\Http\Controllers;

use App\Services\EngagementService;
use App\Services\CommsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ServiceTestController extends Controller
{
    protected EngagementService $engagementService;
    protected CommsService $commsService;

    public function __construct(
        EngagementService $engagementService,
        CommsService $commsService
    ) {
        $this->engagementService = $engagementService;
        $this->commsService = $commsService;
    }

    public function testEngagementService(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authentication token provided'
                ], 401);
            }

            $user = $request->attributes->get('user');
            $userId = $user['user_id'] ?? 1;

            $userData = [
                'user_id' => $userId,
                'username' => 'testuser',
                'email' => 'testuser@example.com',
                'first_name' => 'Test',
                'last_name' => 'User'
            ];

            $tests = [
                'user_registration' => $this->engagementService->notifyUserRegistration($userData, $token),
                'email_verification' => $this->engagementService->notifyEmailVerification($userId, $token),
                'user_login' => $this->engagementService->trackUserLogin($userId, $token)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Engagement service test completed',
                'service' => 'engagement',
                'results' => $tests,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Engagement service test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testCommsService(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authentication token provided'
                ], 401);
            }

            $user = $request->attributes->get('user');
            $userId = $user['user_id'] ?? 1;

            $userData = [
                'user_id' => $userId,
                'username' => $user['username'] ?? 'testuser',
                'email' => $user['email'] ?? 'testuser@example.com',
                'first_name' => $user['first_name'] ?? 'Test',
                'last_name' => $user['last_name'] ?? 'User'
            ];

            $verificationUrl = 'https://fitnease.com/verify-email?token=demo-token';

            $tests = [
                'verification_email' => $this->commsService->sendVerificationEmail($userData, $verificationUrl, $token),
                'welcome_email' => $this->commsService->sendWelcomeEmail($userData, $token),
                'notification' => $this->commsService->sendNotification($userId, 'account_created', ['message' => 'Your account has been created successfully'], $token)
            ];

            return response()->json([
                'success' => true,
                'message' => 'Comms service test completed',
                'service' => 'comms',
                'results' => $tests,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comms service test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function testAllServices(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authentication token provided'
                ], 401);
            }

            $allTests = [
                'engagement_service' => $this->testEngagementService($request)->getData(),
                'comms_service' => $this->testCommsService($request)->getData()
            ];

            $overallSuccess = true;
            foreach ($allTests as $test) {
                if (!$test->success) {
                    $overallSuccess = false;
                    break;
                }
            }

            return response()->json([
                'success' => $overallSuccess,
                'message' => $overallSuccess ? 'All service tests completed successfully' : 'Some service tests failed',
                'results' => $allTests,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service testing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}