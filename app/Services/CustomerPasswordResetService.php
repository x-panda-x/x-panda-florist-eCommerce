<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class CustomerPasswordResetService
{
    private const TOKEN_TTL_SECONDS = 3600;

    private Application $app;
    private CustomerService $customerService;
    private NotificationService $notificationService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->customerService = new CustomerService($app);
        $this->notificationService = new NotificationService($app);
    }

    /**
     * @return array{success: bool, message: string, delivery_mode: string|null, notification_result: string|null}
     */
    public function requestReset(string $email): array
    {
        $genericMessage = 'If an account exists for that email address, a reset link has been sent.';
        $customer = $this->customerService->findByEmail($email);

        if ($customer === null || ($customer['is_active'] ?? false) !== true) {
            return [
                'success' => true,
                'message' => $genericMessage,
                'delivery_mode' => null,
                'notification_result' => null,
            ];
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = $this->hashToken($token);
        $expiresAt = date('Y-m-d H:i:s', time() + self::TOKEN_TTL_SECONDS);

        (new QueryBuilder($this->app->database()))->insert('customer_password_reset_tokens', [
            'customer_id' => (int) ($customer['id'] ?? 0),
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        $delivery = $this->notificationService->sendCustomerPasswordResetNotification($customer, $this->resetUrl($token));

        return [
            'success' => true,
            'message' => $genericMessage,
            'delivery_mode' => $delivery['delivery_mode'],
            'notification_result' => $delivery['customer_notification'],
        ];
    }

    /**
     * @return array{valid: bool, error: string|null, token_record: array<string, mixed>|null}
     */
    public function validateToken(string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            return [
                'valid' => false,
                'error' => 'This password reset link is invalid or has expired.',
                'token_record' => null,
            ];
        }

        $record = $this->findTokenRecordByRawToken($token);

        if ($record === null) {
            return [
                'valid' => false,
                'error' => 'This password reset link is invalid or has expired.',
                'token_record' => null,
            ];
        }

        if (!empty($record['used_at'])) {
            return [
                'valid' => false,
                'error' => 'This password reset link has already been used.',
                'token_record' => $record,
            ];
        }

        $expiresAt = strtotime((string) ($record['expires_at'] ?? ''));

        if ($expiresAt === false || $expiresAt < time()) {
            return [
                'valid' => false,
                'error' => 'This password reset link is invalid or has expired.',
                'token_record' => $record,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'token_record' => $record,
        ];
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function resetPassword(string $token, string $password, string $passwordConfirmation): array
    {
        $validation = $this->validateToken($token);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
            ];
        }

        if (strlen($password) < 8) {
            return [
                'success' => false,
                'error' => 'Password must be at least 8 characters.',
            ];
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            return [
                'success' => false,
                'error' => 'Password confirmation does not match.',
            ];
        }

        $record = is_array($validation['token_record']) ? $validation['token_record'] : null;

        if ($record === null) {
            return [
                'success' => false,
                'error' => 'This password reset link is invalid or has expired.',
            ];
        }

        $customerId = (int) ($record['customer_id'] ?? 0);

        if ($customerId <= 0) {
            return [
                'success' => false,
                'error' => 'This password reset link is invalid or has expired.',
            ];
        }

        $pdo = $this->app->database()->connection();
        $pdo->beginTransaction();

        try {
            $current = $this->app->database()->query(
                'SELECT id, customer_id, used_at, expires_at
                 FROM customer_password_reset_tokens
                 WHERE id = :id
                 LIMIT 1',
                ['id' => (int) ($record['id'] ?? 0)]
            )->fetch();

            if (!is_array($current) || !empty($current['used_at'])) {
                throw new \RuntimeException('used_token');
            }

            $expiresAt = strtotime((string) ($current['expires_at'] ?? ''));

            if ($expiresAt === false || $expiresAt < time()) {
                throw new \RuntimeException('expired_token');
            }

            $this->customerService->updatePasswordHash($customerId, password_hash($password, PASSWORD_DEFAULT));

            (new QueryBuilder($this->app->database()))->update('customer_password_reset_tokens', [
                'used_at' => date('Y-m-d H:i:s'),
            ], [
                'id' => (int) ($current['id'] ?? 0),
            ]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = match ($exception->getMessage()) {
                'used_token' => 'This password reset link has already been used.',
                'expired_token' => 'This password reset link is invalid or has expired.',
                default => 'Unable to reset the password right now.',
            };

            return [
                'success' => false,
                'error' => $message,
            ];
        }

        return [
            'success' => true,
            'error' => null,
        ];
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTokenRecordByRawToken(string $token): ?array
    {
        $row = $this->app->database()->query(
            'SELECT id, customer_id, token_hash, expires_at, used_at, created_at
             FROM customer_password_reset_tokens
             WHERE token_hash = :token_hash
             LIMIT 1',
            ['token_hash' => $this->hashToken($token)]
        )->fetch();

        return is_array($row) ? $row : null;
    }

    private function resetUrl(string $token): string
    {
        return public_url('/account/reset-password', [
            'token' => $token,
        ]);
    }
}
