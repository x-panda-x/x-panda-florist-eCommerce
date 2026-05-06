<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class PaymentService
{
    /**
     * @var array<int, string>
     */
    public const ALLOWED_STATUSES = [
        'pending',
        'authorized',
        'paid',
        'failed',
        'cancelled',
    ];

    /**
     * @var array<int, string>
     */
    public const SIMULATED_STATUSES = [
        'paid',
        'failed',
    ];

    private const DEFAULT_PROVIDER = 'placeholder';
    private const DEFAULT_CURRENCY = 'USD';

    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array{id: int, payment_reference: string}
     */
    public function createPendingPaymentForOrder(int $orderId, float $amount, string $currency = self::DEFAULT_CURRENCY): array
    {
        $reference = $this->generatePaymentReference();
        $queryBuilder = new QueryBuilder($this->app->database());

        $queryBuilder->insert('payments', [
            'order_id' => $orderId,
            'payment_reference' => $reference,
            'provider_name' => self::DEFAULT_PROVIDER,
            'amount' => round($amount, 2),
            'currency' => strtoupper($currency),
            'status' => 'pending',
        ]);

        return [
            'id' => (int) $this->app->database()->connection()->lastInsertId(),
            'payment_reference' => $reference,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPaymentByReference(string $reference): ?array
    {
        $payment = $this->app->database()->query(
            'SELECT id, order_id, payment_reference, provider_name, provider_reference, amount, currency, status, failure_message, created_at, updated_at
             FROM payments
             WHERE payment_reference = :payment_reference
             LIMIT 1',
            ['payment_reference' => $reference]
        )->fetch();

        return is_array($payment) ? $payment : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestPaymentByOrderId(int $orderId): ?array
    {
        $payment = $this->app->database()->query(
            'SELECT id, order_id, payment_reference, provider_name, provider_reference, amount, currency, status, failure_message, created_at, updated_at
             FROM payments
             WHERE order_id = :order_id
             ORDER BY created_at DESC, id DESC
             LIMIT 1',
            ['order_id' => $orderId]
        )->fetch();

        return is_array($payment) ? $payment : null;
    }

    /**
     * @return array<int, string>
     */
    public function allowedStatuses(): array
    {
        return self::ALLOWED_STATUSES;
    }

    /**
     * @return array<int, string>
     */
    public function allowedSimulatedStatuses(): array
    {
        return self::SIMULATED_STATUSES;
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::ALLOWED_STATUSES, true);
    }

    public function isValidSimulatedStatus(string $status): bool
    {
        return in_array($status, self::SIMULATED_STATUSES, true);
    }

    /**
     * @return array<int, string>
     */
    public function simulationTargetsForStatus(string $currentStatus): array
    {
        if (!in_array($currentStatus, ['pending', 'authorized'], true)) {
            return [];
        }

        return self::SIMULATED_STATUSES;
    }

    public function canSimulateTransition(string $currentStatus, string $targetStatus): bool
    {
        return in_array($targetStatus, $this->simulationTargetsForStatus($currentStatus), true);
    }

    public function updatePaymentStatus(int $paymentId, string $status, ?string $providerReference = null, ?string $failureMessage = null): void
    {
        if (!$this->isValidStatus($status)) {
            throw new \InvalidArgumentException('Invalid payment status.');
        }

        $data = [
            'status' => $status,
            'provider_reference' => $providerReference,
            'failure_message' => $failureMessage,
        ];

        (new QueryBuilder($this->app->database()))->update('payments', $data, [
            'id' => $paymentId,
        ]);
    }

    private function generatePaymentReference(): string
    {
        return 'PAY-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    public function generateSimulationProviderReference(string $status): string
    {
        return 'SIM-' . strtoupper($status) . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }
}
