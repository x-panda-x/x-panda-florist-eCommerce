<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class CustomerAddressService
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listByCustomerId(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT id, customer_id, label, recipient_name, delivery_address, delivery_zip,
                    delivery_instructions, is_default, created_at, updated_at
             FROM customer_addresses
             WHERE customer_id = :customer_id
             ORDER BY is_default DESC, id DESC',
            ['customer_id' => $customerId]
        );

        return array_map([$this, 'normalizeAddress'], $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForCustomer(int $customerId, int $addressId): ?array
    {
        if ($customerId <= 0 || $addressId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT id, customer_id, label, recipient_name, delivery_address, delivery_zip,
                    delivery_instructions, is_default, created_at, updated_at
             FROM customer_addresses
             WHERE id = :id AND customer_id = :customer_id
             LIMIT 1',
            [
                'id' => $addressId,
                'customer_id' => $customerId,
            ]
        )->fetch();

        return is_array($row) ? $this->normalizeAddress($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDefaultForCustomer(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT id, customer_id, label, recipient_name, delivery_address, delivery_zip,
                    delivery_instructions, is_default, created_at, updated_at
             FROM customer_addresses
             WHERE customer_id = :customer_id AND is_default = 1
             ORDER BY id DESC
             LIMIT 1',
            ['customer_id' => $customerId]
        )->fetch();

        return is_array($row) ? $this->normalizeAddress($row) : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, address: array<string, mixed>|null}
     */
    public function createForCustomer(int $customerId, array $input): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'error' => 'Customer account not found.', 'address' => null];
        }

        $data = $this->normalizeInput($input);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'address' => null];
        }

        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $existingCount = (int) $this->app->database()->query(
                'SELECT COUNT(*) FROM customer_addresses WHERE customer_id = :customer_id',
                ['customer_id' => $customerId]
            )->fetchColumn();

            $isDefault = !empty($input['is_default']) || $existingCount === 0;

            if ($isDefault) {
                $this->clearDefaultAddress($customerId);
            }

            $queryBuilder->insert('customer_addresses', [
                'customer_id' => $customerId,
                'label' => $data['label'],
                'recipient_name' => $data['recipient_name'],
                'delivery_address' => $data['delivery_address'],
                'delivery_zip' => $data['delivery_zip'],
                'delivery_instructions' => $data['delivery_instructions'],
                'is_default' => $isDefault ? 1 : 0,
            ]);

            $addressId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return [
                'success' => true,
                'error' => null,
                'address' => $this->findForCustomer($customerId, $addressId),
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, address: array<string, mixed>|null}
     */
    public function updateForCustomer(int $customerId, int $addressId, array $input): array
    {
        $address = $this->findForCustomer($customerId, $addressId);

        if ($address === null) {
            return ['success' => false, 'error' => 'Address not found for this customer account.', 'address' => null];
        }

        $data = $this->normalizeInput($input);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'address' => null];
        }

        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $makeDefault = !empty($input['is_default']);

            if ($makeDefault) {
                $this->clearDefaultAddress($customerId);
            }

            $queryBuilder->update('customer_addresses', [
                'label' => $data['label'],
                'recipient_name' => $data['recipient_name'],
                'delivery_address' => $data['delivery_address'],
                'delivery_zip' => $data['delivery_zip'],
                'delivery_instructions' => $data['delivery_instructions'],
                'is_default' => $makeDefault ? 1 : (!empty($address['is_default']) ? 1 : 0),
            ], [
                'id' => $addressId,
                'customer_id' => $customerId,
            ]);

            $pdo->commit();

            return [
                'success' => true,
                'error' => null,
                'address' => $this->findForCustomer($customerId, $addressId),
            ];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function deleteForCustomer(int $customerId, int $addressId): array
    {
        $address = $this->findForCustomer($customerId, $addressId);

        if ($address === null) {
            return ['success' => false, 'error' => 'Address not found for this customer account.'];
        }

        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());

        $pdo->beginTransaction();

        try {
            $queryBuilder->delete('customer_addresses', [
                'id' => $addressId,
                'customer_id' => $customerId,
            ]);

            if (!empty($address['is_default'])) {
                $replacementId = (int) $this->app->database()->query(
                    'SELECT id
                     FROM customer_addresses
                     WHERE customer_id = :customer_id
                     ORDER BY id DESC
                     LIMIT 1',
                    ['customer_id' => $customerId]
                )->fetchColumn();

                if ($replacementId > 0) {
                    $this->app->database()->query(
                        'UPDATE customer_addresses
                         SET is_default = CASE WHEN id = :id THEN 1 ELSE 0 END
                         WHERE customer_id = :customer_id',
                        [
                            'id' => $replacementId,
                            'customer_id' => $customerId,
                        ]
                    );
                }
            }

            $pdo->commit();

            return ['success' => true, 'error' => null];
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function setDefaultForCustomer(int $customerId, int $addressId): array
    {
        if ($this->findForCustomer($customerId, $addressId) === null) {
            return ['success' => false, 'error' => 'Address not found for this customer account.'];
        }

        $this->app->database()->query(
            'UPDATE customer_addresses
             SET is_default = CASE WHEN id = :id THEN 1 ELSE 0 END
             WHERE customer_id = :customer_id',
            [
                'id' => $addressId,
                'customer_id' => $customerId,
            ]
        );

        return ['success' => true, 'error' => null];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizeInput(array $input): array
    {
        return [
            'label' => trim((string) ($input['label'] ?? '')),
            'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
            'delivery_address' => trim((string) ($input['delivery_address'] ?? '')),
            'delivery_zip' => $this->normalizeZip((string) ($input['delivery_zip'] ?? '')),
            'delivery_instructions' => trim((string) ($input['delivery_instructions'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $data
     */
    public function validateInput(array $data): ?string
    {
        if ($data['label'] === '') {
            return 'Address label is required.';
        }

        if ($data['recipient_name'] === '') {
            return 'Recipient name is required.';
        }

        if ($data['delivery_address'] === '') {
            return 'Delivery address is required.';
        }

        if ($data['delivery_zip'] === '' || strlen($data['delivery_zip']) !== 5) {
            return 'A valid 5-digit delivery ZIP code is required.';
        }

        return null;
    }

    private function clearDefaultAddress(int $customerId): void
    {
        $this->app->database()->query(
            'UPDATE customer_addresses
             SET is_default = 0
             WHERE customer_id = :customer_id',
            ['customer_id' => $customerId]
        );
    }

    private function normalizeZip(string $zip): string
    {
        return substr(preg_replace('/\D+/', '', $zip) ?? '', 0, 5);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['customer_id'] = (int) ($row['customer_id'] ?? 0);
        $row['label'] = trim((string) ($row['label'] ?? ''));
        $row['recipient_name'] = trim((string) ($row['recipient_name'] ?? ''));
        $row['delivery_address'] = trim((string) ($row['delivery_address'] ?? ''));
        $row['delivery_zip'] = $this->normalizeZip((string) ($row['delivery_zip'] ?? ''));
        $row['delivery_instructions'] = trim((string) ($row['delivery_instructions'] ?? ''));
        $row['is_default'] = (bool) ($row['is_default'] ?? false);

        return $row;
    }
}
