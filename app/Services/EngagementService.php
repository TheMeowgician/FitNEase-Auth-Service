<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EngagementService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('ENGAGEMENT_SERVICE_URL', 'http://fitnease-engagement');
    }

    public function notifyUserRegistration($userData, $token)
    {
        try {
            Log::info('Notifying engagement service of user registration', [
                'service' => 'fitnease-auth',
                'user_id' => $userData['user_id'] ?? 'unknown',
                'engagement_service_url' => $this->baseUrl
            ]);

            $registrationData = [
                'user_id' => $userData['user_id'],
                'event_type' => 'user_registration',
                'user_data' => $userData,
                'registration_date' => now()->toISOString(),
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/engagement/user-registration', $registrationData);

            if ($response->successful()) {
                Log::info('User registration notification sent successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userData['user_id'] ?? 'unknown'
                ]);

                return $response->json();
            }

            Log::warning('Failed to notify user registration', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Engagement service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'user_data' => $userData,
                'engagement_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }

    public function notifyEmailVerification($userId, $token)
    {
        try {
            Log::info('Notifying engagement service of email verification', [
                'service' => 'fitnease-auth',
                'user_id' => $userId,
                'engagement_service_url' => $this->baseUrl
            ]);

            $verificationData = [
                'user_id' => $userId,
                'event_type' => 'email_verified',
                'verified_at' => now()->toISOString(),
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/engagement/email-verification', $verificationData);

            if ($response->successful()) {
                Log::info('Email verification notification sent successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userId
                ]);

                return $response->json();
            }

            Log::warning('Failed to notify email verification', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Engagement service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'engagement_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }

    public function trackUserLogin($userId, $token)
    {
        try {
            Log::info('Tracking user login in engagement service', [
                'service' => 'fitnease-auth',
                'user_id' => $userId,
                'engagement_service_url' => $this->baseUrl
            ]);

            $loginData = [
                'user_id' => $userId,
                'event_type' => 'user_login',
                'login_time' => now()->toISOString(),
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/engagement/user-login', $loginData);

            if ($response->successful()) {
                Log::info('User login tracked successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userId
                ]);

                return $response->json();
            }

            Log::warning('Failed to track user login', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Engagement service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'engagement_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }
}