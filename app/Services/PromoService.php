<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class PromoService
{
    public const SESSION_KEY = 'cart_promo_code';

    /**
     * @var array<int, string>
     */
    private const ALLOWED_DISCOUNT_TYPES = [
        'percentage',
        'fixed_amount',
    ];

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<int, string>
     */
    public function allowedDiscountTypes(): array
    {
        return self::ALLOWED_DISCOUNT_TYPES;
    }

    public function isValidDiscountType(string $discountType): bool
    {
        return in_array($discountType, self::ALLOWED_DISCOUNT_TYPES, true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPromoCodes(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, code, description, discount_type, discount_value, minimum_subtotal,
                    starts_at, expires_at, usage_limit, times_used, is_active, created_at, updated_at
             FROM promo_codes
             ORDER BY created_at DESC, id DESC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPromoById(int $promoId): ?array
    {
        $promo = $this->app->database()->query(
            'SELECT id, code, description, discount_type, discount_value, minimum_subtotal,
                    starts_at, expires_at, usage_limit, times_used, is_active, created_at, updated_at
             FROM promo_codes
             WHERE id = :id
             LIMIT 1',
            ['id' => $promoId]
        )->fetch();

        return is_array($promo) ? $promo : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPromoByCode(string $code): ?array
    {
        $normalizedCode = $this->normalizeCode($code);

        if ($normalizedCode === '') {
            return null;
        }

        $promo = $this->app->database()->query(
            'SELECT id, code, description, discount_type, discount_value, minimum_subtotal,
                    starts_at, expires_at, usage_limit, times_used, is_active, created_at, updated_at
             FROM promo_codes
             WHERE code = :code
             LIMIT 1',
            ['code' => $normalizedCode]
        )->fetch();

        return is_array($promo) ? $promo : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPromoCode(array $data): bool
    {
        return (new QueryBuilder($this->app->database()))->insert('promo_codes', [
            'code' => $data['code'],
            'description' => $data['description'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'minimum_subtotal' => $data['minimum_subtotal'],
            'starts_at' => $data['starts_at'],
            'expires_at' => $data['expires_at'],
            'usage_limit' => $data['usage_limit'],
            'times_used' => $data['times_used'],
            'is_active' => $data['is_active'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePromoCode(int $promoId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('promo_codes', [
            'code' => $data['code'],
            'description' => $data['description'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'minimum_subtotal' => $data['minimum_subtotal'],
            'starts_at' => $data['starts_at'],
            'expires_at' => $data['expires_at'],
            'usage_limit' => $data['usage_limit'],
            'is_active' => $data['is_active'],
        ], [
            'id' => $promoId,
        ]);
    }

    public function deletePromoCode(int $promoId): void
    {
        (new QueryBuilder($this->app->database()))->delete('promo_codes', [
            'id' => $promoId,
        ]);
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int} $cartSummary
     * @return array{promo: array<string, mixed>|null, error: string|null}
     */
    public function getAppliedPromoForCart(array $cartSummary): array
    {
        $code = $this->currentSessionCode();
        $subtotal = round((float) ($cartSummary['subtotal'] ?? 0), 2);

        if ($code === '') {
            return [
                'promo' => null,
                'error' => null,
            ];
        }

        if ($subtotal <= 0) {
            $this->clearSessionCode();

            return [
                'promo' => null,
                'error' => null,
            ];
        }

        $validation = $this->validatePromoCode($code, $cartSummary);

        if (!$validation['is_valid']) {
            $this->clearSessionCode();

            return [
                'promo' => null,
                'error' => $validation['message'],
            ];
        }

        return [
            'promo' => $validation['promo'],
            'error' => null,
        ];
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int} $cartSummary
     * @return array{success: bool, message: string, promo: array<string, mixed>|null}
     */
    public function applyPromoCode(string $code, array $cartSummary): array
    {
        $validation = $this->validatePromoCode($code, $cartSummary);

        if (!$validation['is_valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'promo' => null,
            ];
        }

        $_SESSION[self::SESSION_KEY] = (string) ($validation['promo']['code'] ?? '');

        return [
            'success' => true,
            'message' => 'Promo code applied.',
            'promo' => $validation['promo'],
        ];
    }

    public function clearSessionCode(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function currentSessionCode(): string
    {
        return $this->normalizeCode((string) ($_SESSION[self::SESSION_KEY] ?? ''));
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int} $cartSummary
     * @return array{is_valid: bool, message: string, promo: array<string, mixed>|null}
     */
    public function validatePromoCode(string $code, array $cartSummary): array
    {
        $normalizedCode = $this->normalizeCode($code);
        $subtotal = round((float) ($cartSummary['subtotal'] ?? 0), 2);

        if ($subtotal <= 0) {
            return [
                'is_valid' => false,
                'message' => 'Add an arrangement before applying a promo code.',
                'promo' => null,
            ];
        }

        if ($normalizedCode === '') {
            return [
                'is_valid' => false,
                'message' => 'Enter a promo code.',
                'promo' => null,
            ];
        }

        $promo = $this->findPromoByCode($normalizedCode);

        if ($promo === null) {
            return [
                'is_valid' => false,
                'message' => 'That promo code was not recognized.',
                'promo' => null,
            ];
        }

        if (empty($promo['is_active'])) {
            return [
                'is_valid' => false,
                'message' => 'That promo code is inactive.',
                'promo' => null,
            ];
        }

        $now = new \DateTimeImmutable('now');
        $startsAt = $this->parseDateTime($promo['starts_at'] ?? null);
        $expiresAt = $this->parseDateTime($promo['expires_at'] ?? null);

        if ($startsAt !== null && $now < $startsAt) {
            return [
                'is_valid' => false,
                'message' => 'That promo code is not active yet.',
                'promo' => null,
            ];
        }

        if ($expiresAt !== null && $now > $expiresAt) {
            return [
                'is_valid' => false,
                'message' => 'That promo code has expired.',
                'promo' => null,
            ];
        }

        $minimumSubtotal = round((float) ($promo['minimum_subtotal'] ?? 0), 2);

        if ($subtotal < $minimumSubtotal) {
            return [
                'is_valid' => false,
                'message' => sprintf(
                    'This promo code requires a cart subtotal of at least $%s.',
                    number_format($minimumSubtotal, 2)
                ),
                'promo' => null,
            ];
        }

        $usageLimit = $promo['usage_limit'];
        $timesUsed = (int) ($promo['times_used'] ?? 0);

        if ($usageLimit !== null && $timesUsed >= (int) $usageLimit) {
            return [
                'is_valid' => false,
                'message' => 'That promo code has reached its usage limit.',
                'promo' => null,
            ];
        }

        $discountAmount = $this->calculateDiscountAmount($promo, $subtotal);

        if ($discountAmount <= 0) {
            return [
                'is_valid' => false,
                'message' => 'That promo code does not apply to the current cart.',
                'promo' => null,
            ];
        }

        $promo['code'] = $normalizedCode;
        $promo['discount_amount'] = $discountAmount;

        return [
            'is_valid' => true,
            'message' => 'Promo code applied.',
            'promo' => $promo,
        ];
    }

    /**
     * @param array<string, mixed> $promo
     */
    public function calculateDiscountAmount(array $promo, float $subtotal): float
    {
        $normalizedSubtotal = round(max(0, $subtotal), 2);

        if ($normalizedSubtotal <= 0) {
            return 0.0;
        }

        $discountType = (string) ($promo['discount_type'] ?? '');
        $discountValue = round(max(0, (float) ($promo['discount_value'] ?? 0)), 2);

        if ($discountType === 'percentage') {
            $percentage = min(100, $discountValue);
            $discountAmount = round($normalizedSubtotal * ($percentage / 100), 2);

            return min($discountAmount, $normalizedSubtotal);
        }

        if ($discountType === 'fixed_amount') {
            return min($discountValue, $normalizedSubtotal);
        }

        return 0.0;
    }

    public function normalizeCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/\s+/', '', $normalized) ?? '';

        return substr($normalized, 0, 64);
    }

    private function parseDateTime(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
