<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class CustomerService
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $customerId): ?array
    {
        $row = $this->app->database()->query(
            'SELECT id, email, password_hash, full_name, phone, is_active,
                    marketing_opt_in, reminder_email_opt_in, order_email_opt_in,
                    last_login_at, created_at, updated_at
             FROM customers
             WHERE id = :id
             LIMIT 1',
            ['id' => $customerId]
        )->fetch();

        return is_array($row) ? $this->normalizeCustomer($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $normalizedEmail = $this->normalizeEmail($email);

        if ($normalizedEmail === '') {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT id, email, password_hash, full_name, phone, is_active,
                    marketing_opt_in, reminder_email_opt_in, order_email_opt_in,
                    last_login_at, created_at, updated_at
             FROM customers
             WHERE email = :email
             LIMIT 1',
            ['email' => $normalizedEmail]
        )->fetch();

        return is_array($row) ? $this->normalizeCustomer($row) : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        $search = trim($search);
        $params = [];
        $where = '';

        if ($search !== '') {
            $searchTerm = '%' . $search . '%';
            $params['search_name'] = $searchTerm;
            $params['search_email'] = $searchTerm;
            $where = 'WHERE c.full_name LIKE :search_name OR c.email LIKE :search_email';
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT c.id, c.email, c.full_name, c.phone, c.is_active,
                    c.marketing_opt_in, c.reminder_email_opt_in, c.order_email_opt_in,
                    c.last_login_at, c.created_at, c.updated_at,
                    (
                        SELECT COUNT(*)
                        FROM orders o
                        WHERE o.customer_id = c.id
                           OR (o.customer_id IS NULL AND LOWER(o.customer_email) = c.email)
                    ) AS order_count
             FROM customers c
             ' . $where . '
             ORDER BY c.created_at DESC, c.id DESC',
            $params
        );

        return array_map(function (array $row): array {
            $customer = $this->normalizeCustomer($row);
            $customer['order_count'] = (int) ($row['order_count'] ?? 0);

            return $customer;
        }, $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCampaignRecipients(string $search = '', string $filter = 'subscribed'): array
    {
        $search = trim($search);
        $params = [];
        $where = ['c.is_active = 1'];

        if ($search !== '') {
            $where[] = '(c.full_name LIKE :search_name OR c.email LIKE :search_email)';
            $params['search_name'] = '%' . $search . '%';
            $params['search_email'] = '%' . $search . '%';
        }

        if ($filter === 'subscribed') {
            $where[] = 'c.marketing_opt_in = 1';
        } elseif ($filter === 'orders') {
            $where[] = 'EXISTS (SELECT 1 FROM orders o WHERE o.customer_id = c.id OR (o.customer_id IS NULL AND LOWER(o.customer_email) = c.email))';
        } elseif ($filter === 'reminders') {
            $where[] = 'EXISTS (SELECT 1 FROM customer_reminders cr WHERE cr.customer_id = c.id)';
        } elseif ($filter === 'all') {
            // keep base where only
        } else {
            $where[] = 'c.marketing_opt_in = 1';
        }

        $rows = $this->app->database()->fetchAll(
            'SELECT c.id, c.email, c.full_name, c.is_active, c.marketing_opt_in, c.reminder_email_opt_in, c.order_email_opt_in,
                (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id OR (o.customer_id IS NULL AND LOWER(o.customer_email) = c.email)) AS order_count,
                (SELECT COUNT(*) FROM customer_reminders cr WHERE cr.customer_id = c.id) AS reminder_count
             FROM customers c
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY c.created_at DESC, c.id DESC',
            $params
        );

        return array_map(function (array $row): array {
            $customer = $this->normalizeCustomer($row);
            $customer['order_count'] = (int) ($row['order_count'] ?? 0);
            $customer['reminder_count'] = (int) ($row['reminder_count'] ?? 0);
            return $customer;
        }, $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAdminProfileById(int $customerId): ?array
    {
        $customer = $this->findById($customerId);

        if ($customer === null) {
            return null;
        }

        $counts = $this->app->database()->query(
            'SELECT
                (
                    SELECT COUNT(*)
                    FROM orders o
                    WHERE o.customer_id = c.id
                       OR (o.customer_id IS NULL AND LOWER(o.customer_email) = c.email)
                ) AS order_count,
                (
                    SELECT COUNT(*)
                    FROM customer_addresses ca
                    WHERE ca.customer_id = c.id
                ) AS address_count,
                (
                    SELECT COUNT(*)
                    FROM customer_reminders cr
                    WHERE cr.customer_id = c.id
                ) AS reminder_count
             FROM customers c
             WHERE c.id = :id
             LIMIT 1',
            ['id' => $customerId]
        )->fetch();

        if (!is_array($counts)) {
            return null;
        }

        $customer['order_count'] = (int) ($counts['order_count'] ?? 0);
        $customer['address_count'] = (int) ($counts['address_count'] ?? 0);
        $customer['reminder_count'] = (int) ($counts['reminder_count'] ?? 0);

        return $customer;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        (new QueryBuilder($this->app->database()))->insert('customers', [
            'email' => $this->normalizeEmail((string) ($data['email'] ?? '')),
            'password_hash' => (string) ($data['password_hash'] ?? ''),
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'phone' => $this->normalizePhone((string) ($data['phone'] ?? '')),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'marketing_opt_in' => !empty($data['marketing_opt_in']) ? 1 : 0,
            'reminder_email_opt_in' => !array_key_exists('reminder_email_opt_in', $data) || !empty($data['reminder_email_opt_in']) ? 1 : 0,
            'order_email_opt_in' => !array_key_exists('order_email_opt_in', $data) || !empty($data['order_email_opt_in']) ? 1 : 0,
        ]);

        return (int) $this->app->database()->connection()->lastInsertId();
    }

    public function updateLastLoginAt(int $customerId): void
    {
        (new QueryBuilder($this->app->database()))->update('customers', [
            'last_login_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $customerId,
        ]);
    }

    public function updatePasswordHash(int $customerId, string $passwordHash): void
    {
        (new QueryBuilder($this->app->database()))->update('customers', [
            'password_hash' => $passwordHash,
        ], [
            'id' => $customerId,
        ]);
    }

    public function setActiveStatus(int $customerId, bool $isActive): void
    {
        (new QueryBuilder($this->app->database()))->update('customers', [
            'is_active' => $isActive ? 1 : 0,
        ], [
            'id' => $customerId,
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, customer: array<string, mixed>|null}
     */
    public function updateProfile(int $customerId, array $input): array
    {
        $customer = $this->findById($customerId);

        if ($customer === null) {
            return ['success' => false, 'error' => 'Customer account not found.', 'customer' => null];
        }

        $fullName = trim((string) ($input['full_name'] ?? ''));
        $email = $this->normalizeEmail((string) ($input['email'] ?? ''));
        $phone = $this->normalizePhone((string) ($input['phone'] ?? ''));

        if ($fullName === '') {
            return ['success' => false, 'error' => 'Full name is required.', 'customer' => null];
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return ['success' => false, 'error' => 'A valid email address is required.', 'customer' => null];
        }

        $existing = $this->findByEmail($email);

        if ($existing !== null && (int) ($existing['id'] ?? 0) !== $customerId) {
            return ['success' => false, 'error' => 'Another account already uses that email address.', 'customer' => null];
        }

        (new QueryBuilder($this->app->database()))->update('customers', [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
        ], [
            'id' => $customerId,
        ]);

        return ['success' => true, 'error' => null, 'customer' => $this->findById($customerId)];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, customer: array<string, mixed>|null}
     */
    public function updateEmailPreferences(int $customerId, array $input): array
    {
        $customer = $this->findById($customerId);

        if ($customer === null) {
            return ['success' => false, 'error' => 'Customer account not found.', 'customer' => null];
        }

        (new QueryBuilder($this->app->database()))->update('customers', [
            'marketing_opt_in' => ($input['marketing_opt_in'] ?? null) === '1' ? 1 : 0,
            'reminder_email_opt_in' => ($input['reminder_email_opt_in'] ?? null) === '1' ? 1 : 0,
            'order_email_opt_in' => ($input['order_email_opt_in'] ?? null) === '1' ? 1 : 0,
        ], [
            'id' => $customerId,
        ]);

        return ['success' => true, 'error' => null, 'customer' => $this->findById($customerId)];
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function changePassword(int $customerId, string $currentPassword, string $newPassword, string $newPasswordConfirmation): array
    {
        $customer = $this->findById($customerId);

        if ($customer === null) {
            return ['success' => false, 'error' => 'Customer account not found.'];
        }

        if ($currentPassword === '' || !password_verify($currentPassword, (string) ($customer['password_hash'] ?? ''))) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters.'];
        }

        if (!hash_equals($newPassword, $newPasswordConfirmation)) {
            return ['success' => false, 'error' => 'New password confirmation does not match.'];
        }

        $this->updatePasswordHash($customerId, password_hash($newPassword, PASSWORD_DEFAULT));

        return ['success' => true, 'error' => null];
    }

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function normalizePhone(string $phone): ?string
    {
        $normalized = trim($phone);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<string, string>
     */
    public function checkoutPrefillData(array $customer): array
    {
        return [
            'customer_name' => trim((string) ($customer['full_name'] ?? '')),
            'customer_email' => $this->normalizeEmail((string) ($customer['email'] ?? '')),
            'customer_phone' => (string) ($this->normalizePhone((string) ($customer['phone'] ?? '')) ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeCustomer(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_active'] = (bool) ($row['is_active'] ?? false);
        $row['marketing_opt_in'] = (bool) ($row['marketing_opt_in'] ?? false);
        $row['reminder_email_opt_in'] = (bool) ($row['reminder_email_opt_in'] ?? false);
        $row['order_email_opt_in'] = (bool) ($row['order_email_opt_in'] ?? false);
        $row['email'] = $this->normalizeEmail((string) ($row['email'] ?? ''));
        $row['full_name'] = trim((string) ($row['full_name'] ?? ''));
        $row['phone'] = $this->normalizePhone((string) ($row['phone'] ?? ''));

        return $row;
    }
}
