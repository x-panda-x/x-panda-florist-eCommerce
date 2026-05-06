<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\PromoService;

final class PromoCodeController extends BaseAdminController
{
    private PromoService $promoService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->promoService = new PromoService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-promos', [
            'pageTitle' => 'Promo Codes',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'promoCodes' => $this->promoService->listPromoCodes(),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Promo Code',
            'formAction' => '/admin/promo-codes',
            'promoCode' => $this->emptyPromoCode(),
            'formMode' => 'create',
            'discountTypes' => $this->promoService->allowedDiscountTypes(),
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $promoId = (int) ($_GET['id'] ?? 0);
        $promoCode = $this->promoService->findPromoById($promoId);

        if ($promoCode === null) {
            $this->flash('error', 'Promo code not found.');
            $this->redirect('/admin/promo-codes');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Promo Code',
            'formAction' => '/admin/promo-codes/update',
            'promoCode' => $promoCode,
            'promoId' => $promoId,
            'formMode' => 'edit',
            'discountTypes' => $this->promoService->allowedDiscountTypes(),
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/promo-codes/create');
        }

        $promoCode = $this->normalizePromoCode($_POST);
        $validationError = $this->validatePromoCode($promoCode);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/promo-codes/create');
        }

        try {
            $this->promoService->createPromoCode($promoCode);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save promo code. Check for a duplicate code and try again.');
            $this->redirect('/admin/promo-codes/create');
        }

        $this->flash('success', 'Promo code created.');
        $this->redirect('/admin/promo-codes');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/promo-codes');
        }

        $promoId = (int) ($_POST['id'] ?? 0);

        if ($promoId <= 0 || $this->promoService->findPromoById($promoId) === null) {
            $this->flash('error', 'Promo code not found.');
            $this->redirect('/admin/promo-codes');
        }

        $promoCode = $this->normalizePromoCode($_POST);
        $validationError = $this->validatePromoCode($promoCode);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/promo-codes/edit?id=' . $promoId);
        }

        try {
            $this->promoService->updatePromoCode($promoId, $promoCode);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update promo code. Check for a duplicate code and try again.');
            $this->redirect('/admin/promo-codes/edit?id=' . $promoId);
        }

        $this->flash('success', 'Promo code updated.');
        $this->redirect('/admin/promo-codes');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/promo-codes');
        }

        $promoId = (int) ($_POST['id'] ?? 0);

        if ($promoId <= 0 || $this->promoService->findPromoById($promoId) === null) {
            $this->flash('error', 'Promo code not found.');
            $this->redirect('/admin/promo-codes');
        }

        try {
            $this->promoService->deletePromoCode($promoId);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to delete promo code right now.');
            $this->redirect('/admin/promo-codes');
        }

        $this->flash('success', 'Promo code deleted.');
        $this->redirect('/admin/promo-codes');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizePromoCode(array $input): array
    {
        $usageLimit = trim((string) ($input['usage_limit'] ?? ''));

        return [
            'code' => $this->promoService->normalizeCode((string) ($input['code'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'discount_type' => trim((string) ($input['discount_type'] ?? 'percentage')),
            'discount_value' => trim((string) ($input['discount_value'] ?? '0')),
            'minimum_subtotal' => trim((string) ($input['minimum_subtotal'] ?? '0')),
            'starts_at' => $this->normalizeDateTimeInput((string) ($input['starts_at'] ?? '')),
            'expires_at' => $this->normalizeDateTimeInput((string) ($input['expires_at'] ?? '')),
            'usage_limit' => $usageLimit === '' ? null : $usageLimit,
            'times_used' => max(0, (int) ($input['times_used'] ?? 0)),
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $promoCode
     */
    private function validatePromoCode(array $promoCode): ?string
    {
        $code = (string) ($promoCode['code'] ?? '');

        if ($code === '') {
            return 'Code is required.';
        }

        if (preg_match('/^[A-Z0-9_-]+$/', $code) !== 1) {
            return 'Code must use uppercase letters, numbers, hyphens, or underscores only.';
        }

        if (!$this->promoService->isValidDiscountType((string) ($promoCode['discount_type'] ?? ''))) {
            return 'Invalid discount type.';
        }

        if (!is_numeric((string) ($promoCode['discount_value'] ?? '')) || (float) $promoCode['discount_value'] <= 0) {
            return 'Discount value must be a valid number greater than zero.';
        }

        if (
            (string) ($promoCode['discount_type'] ?? '') === 'percentage'
            && (float) $promoCode['discount_value'] > 100
        ) {
            return 'Percentage discounts must be 100 or less.';
        }

        if (!is_numeric((string) ($promoCode['minimum_subtotal'] ?? '')) || (float) $promoCode['minimum_subtotal'] < 0) {
            return 'Minimum subtotal must be a valid non-negative number.';
        }

        if (
            $promoCode['usage_limit'] !== null
            && (
                !ctype_digit((string) $promoCode['usage_limit'])
                || (int) $promoCode['usage_limit'] <= 0
            )
        ) {
            return 'Usage limit must be empty or a whole number greater than zero.';
        }

        $startsAt = $this->parseDateTime((string) ($promoCode['starts_at'] ?? ''));
        $expiresAt = $this->parseDateTime((string) ($promoCode['expires_at'] ?? ''));

        if ((string) ($promoCode['starts_at'] ?? '') !== '' && $startsAt === null) {
            return 'Start date must be a valid date and time.';
        }

        if ((string) ($promoCode['expires_at'] ?? '') !== '' && $expiresAt === null) {
            return 'End date must be a valid date and time.';
        }

        if ($startsAt !== null && $expiresAt !== null && $expiresAt < $startsAt) {
            return 'End date must be later than the start date.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPromoCode(): array
    {
        return [
            'code' => '',
            'description' => '',
            'discount_type' => 'percentage',
            'discount_value' => '10.00',
            'minimum_subtotal' => '0.00',
            'starts_at' => null,
            'expires_at' => null,
            'usage_limit' => null,
            'times_used' => 0,
            'is_active' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-promo-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }

    private function normalizeDateTimeInput(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $dateTime = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        if (!$dateTime instanceof \DateTimeImmutable || $dateTime->format('Y-m-d\TH:i') !== $value) {
            return null;
        }

        return $dateTime->format('Y-m-d H:i:s');
    }

    private function parseDateTime(string $value): ?\DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
