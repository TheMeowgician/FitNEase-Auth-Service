<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CommsService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('COMMS_SERVICE_URL', 'http://fitnease-comms');
    }

    public function sendVerificationEmail($userData, $verificationUrl, $token)
    {
        try {
            Log::info('Sending verification email via comms service', [
                'service' => 'fitnease-auth',
                'user_id' => $userData['user_id'] ?? 'unknown',
                'email' => $userData['email'] ?? 'unknown',
                'comms_service_url' => $this->baseUrl
            ]);

            $emailData = [
                'to_email' => $userData['email'],
                'to_name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'email_type' => 'email_verification',
                'verification_url' => $verificationUrl,
                'user_data' => $userData,
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/comms/send-verification', $emailData);

            if ($response->successful()) {
                Log::info('Verification email sent successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userData['user_id'] ?? 'unknown',
                    'email' => $userData['email'] ?? 'unknown'
                ]);

                return $response->json();
            }

            Log::warning('Failed to send verification email', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Comms service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'email_data' => $emailData ?? [],
                'comms_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }

    public function sendWelcomeEmail($userData, $token)
    {
        try {
            Log::info('Sending welcome email via comms service', [
                'service' => 'fitnease-auth',
                'user_id' => $userData['user_id'] ?? 'unknown',
                'email' => $userData['email'] ?? 'unknown',
                'comms_service_url' => $this->baseUrl
            ]);

            $emailData = [
                'to_email' => $userData['email'],
                'to_name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'email_type' => 'welcome',
                'user_data' => $userData,
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/comms/send-welcome-email', $emailData);

            if ($response->successful()) {
                Log::info('Welcome email sent successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userData['user_id'] ?? 'unknown',
                    'email' => $userData['email'] ?? 'unknown'
                ]);

                return $response->json();
            }

            Log::warning('Failed to send welcome email', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Comms service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'email_data' => $emailData ?? [],
                'comms_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }

    public function sendNotification($userId, $notificationType, $notificationData, $token)
    {
        try {
            Log::info('Sending notification via comms service', [
                'service' => 'fitnease-auth',
                'user_id' => $userId,
                'notification_type' => $notificationType,
                'comms_service_url' => $this->baseUrl
            ]);

            $notificationPayload = [
                'user_id' => $userId,
                'notification_type' => $notificationType,
                'data' => $notificationData,
                'timestamp' => now()->toISOString()
            ];

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/api/comms/notification', $notificationPayload);

            if ($response->successful()) {
                Log::info('Notification sent successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userId,
                    'notification_type' => $notificationType
                ]);

                return $response->json();
            }

            Log::warning('Failed to send notification', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Comms service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'notification_data' => $notificationPayload ?? [],
                'comms_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }

    public function deleteEmailVerificationNotification($userId, $token)
    {
        try {
            Log::info('Deleting email verification notification via comms service', [
                'service' => 'fitnease-auth',
                'user_id' => $userId,
                'comms_service_url' => $this->baseUrl
            ]);

            $response = Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->delete($this->baseUrl . '/api/comms/notifications/email-verification/' . $userId);

            if ($response->successful()) {
                Log::info('Email verification notification deleted successfully', [
                    'service' => 'fitnease-auth',
                    'user_id' => $userId
                ]);

                return $response->json();
            }

            Log::warning('Failed to delete email verification notification', [
                'service' => 'fitnease-auth',
                'status' => $response->status(),
                'response_body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Comms service communication error', [
                'service' => 'fitnease-auth',
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'comms_service_url' => $this->baseUrl
            ]);

            return null;
        }
    }
}