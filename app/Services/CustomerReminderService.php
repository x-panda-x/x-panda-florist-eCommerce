<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class CustomerReminderService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PURCHASED = 'purchased';
    public const STATUS_ACTION_NEEDED = 'action_needed';
    public const STATUS_EXPIRING_SOON = 'expiring_soon';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const UPCOMING_NOTICE_DAYS = 3;
    public const ACTION_WINDOW_HOURS = 48;

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
            'SELECT cr.id, cr.customer_id, cr.product_id, cr.order_id, cr.occasion_label, cr.recipient_name, cr.reminder_date, cr.note,
                    cr.status, cr.is_active, cr.last_sent_at, cr.upcoming_notice_sent_at, cr.action_required_by, cr.expired_at, cr.cancelled_at,
                    cr.created_at, cr.updated_at,
                    p.name AS product_name, p.slug AS product_slug,
                    o.order_number, o.status AS order_status, o.total_amount AS order_total_amount
             FROM customer_reminders cr
             LEFT JOIN products p ON p.id = cr.product_id
             LEFT JOIN orders o ON o.id = cr.order_id
             WHERE cr.customer_id = :customer_id
             ORDER BY reminder_date ASC, id DESC',
            ['customer_id' => $customerId]
        );

        return array_map([$this, 'normalizeReminder'], $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForCustomer(int $customerId, int $reminderId): ?array
    {
        if ($customerId <= 0 || $reminderId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT cr.id, cr.customer_id, cr.product_id, cr.order_id, cr.occasion_label, cr.recipient_name, cr.reminder_date, cr.note,
                    cr.status, cr.is_active, cr.last_sent_at, cr.upcoming_notice_sent_at, cr.action_required_by, cr.expired_at, cr.cancelled_at,
                    cr.created_at, cr.updated_at,
                    p.name AS product_name, p.slug AS product_slug,
                    o.order_number, o.status AS order_status, o.total_amount AS order_total_amount
             FROM customer_reminders cr
             LEFT JOIN products p ON p.id = cr.product_id
             LEFT JOIN orders o ON o.id = cr.order_id
             WHERE cr.id = :id AND cr.customer_id = :customer_id
             LIMIT 1',
            [
                'id' => $reminderId,
                'customer_id' => $customerId,
            ]
        )->fetch();

        return is_array($row) ? $this->normalizeReminder($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $reminderId): ?array
    {
        if ($reminderId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT cr.id, cr.customer_id, cr.product_id, cr.order_id, cr.occasion_label, cr.recipient_name, cr.reminder_date, cr.note,
                    cr.status, cr.is_active, cr.last_sent_at, cr.upcoming_notice_sent_at, cr.action_required_by, cr.expired_at, cr.cancelled_at,
                    cr.created_at, cr.updated_at,
                    p.name AS product_name, p.slug AS product_slug,
                    o.order_number, o.status AS order_status, o.total_amount AS order_total_amount
             FROM customer_reminders cr
             LEFT JOIN products p ON p.id = cr.product_id
             LEFT JOIN orders o ON o.id = cr.order_id
             WHERE cr.id = :id
             LIMIT 1',
            ['id' => $reminderId]
        )->fetch();

        return is_array($row) ? $this->normalizeReminder($row) : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, reminder: array<string, mixed>|null}
     */
    public function createForCustomer(int $customerId, array $input): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'error' => 'Customer account not found.', 'reminder' => null];
        }

        $data = $this->normalizeInput($input);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'reminder' => null];
        }

        $isActive = !array_key_exists('is_active', $input) || !empty($input['is_active']);
        $status = $isActive ? self::STATUS_ACTIVE : self::STATUS_DRAFT;

        (new QueryBuilder($this->app->database()))->insert('customer_reminders', [
            'customer_id' => $customerId,
            'product_id' => !empty($input['product_id']) ? (int) $input['product_id'] : null,
            'order_id' => !empty($input['order_id']) ? (int) $input['order_id'] : null,
            'occasion_label' => $data['occasion_label'],
            'recipient_name' => $data['recipient_name'],
            'reminder_date' => $data['reminder_date'],
            'note' => $data['note'],
            'status' => $status,
            'is_active' => $isActive ? 1 : 0,
            'cancelled_at' => null,
            'expired_at' => null,
            'upcoming_notice_sent_at' => null,
            'action_required_by' => null,
        ]);

        $reminderId = (int) $this->app->database()->connection()->lastInsertId();

        return [
            'success' => true,
            'error' => null,
            'reminder' => $this->findForCustomer($customerId, $reminderId),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, reminder: array<string, mixed>|null, already_exists: bool}
     */
    public function createForPaidOrderFromDraft(int $customerId, int $orderId, array $input): array
    {
        $existingReminderId = (int) ($input['reminder_id'] ?? 0);

        if ($existingReminderId > 0) {
            return $this->attachPaidOrderToReminder($customerId, $existingReminderId, $orderId, $input);
        }

        $preferredProductId = (int) ($input['product_id'] ?? 0);
        $productId = $this->resolveReminderProductIdForOrder($orderId, $preferredProductId);

        if ($productId <= 0) {
            return [
                'success' => false,
                'error' => 'A purchased product is required before the reminder can be finalized.',
                'reminder' => null,
                'already_exists' => false,
            ];
        }

        return $this->createForPaidOrder($customerId, $productId, $orderId, $input);
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, reminder: array<string, mixed>|null, already_exists: bool}
     */
    public function createForPaidOrder(int $customerId, int $productId, int $orderId, array $input): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'error' => 'Customer account not found.', 'reminder' => null, 'already_exists' => false];
        }

        if ($productId <= 0) {
            return ['success' => false, 'error' => 'You must select a product before creating a reminder.', 'reminder' => null, 'already_exists' => false];
        }

        if ($orderId <= 0) {
            return ['success' => false, 'error' => 'A successful paid order is required before creating a reminder.', 'reminder' => null, 'already_exists' => false];
        }

        $existing = $this->findForCustomerByOrderId($customerId, $orderId);

        if ($existing !== null) {
            return ['success' => true, 'error' => null, 'reminder' => $existing, 'already_exists' => true];
        }

        $data = $this->normalizeInput($input);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'reminder' => null, 'already_exists' => false];
        }

        (new QueryBuilder($this->app->database()))->insert('customer_reminders', [
            'customer_id' => $customerId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'occasion_label' => $data['occasion_label'],
            'recipient_name' => $data['recipient_name'],
            'reminder_date' => $data['reminder_date'],
            'note' => $data['note'],
            'status' => self::STATUS_PURCHASED,
            'is_active' => !array_key_exists('is_active', $input) || !empty($input['is_active']) ? 1 : 0,
            'cancelled_at' => null,
            'expired_at' => null,
            'upcoming_notice_sent_at' => null,
            'action_required_by' => null,
        ]);

        $reminderId = (int) $this->app->database()->connection()->lastInsertId();

        return [
            'success' => true,
            'error' => null,
            'reminder' => $this->findForCustomer($customerId, $reminderId),
            'already_exists' => false,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{success: bool, error: string|null, reminder: array<string, mixed>|null}
     */
    public function updateForCustomer(int $customerId, int $reminderId, array $input): array
    {
        $reminder = $this->findForCustomer($customerId, $reminderId);

        if ($reminder === null) {
            return ['success' => false, 'error' => 'Reminder not found for this customer account.', 'reminder' => null];
        }

        $data = $this->normalizeInput($input);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'reminder' => null];
        }

        (new QueryBuilder($this->app->database()))->update('customer_reminders', [
            'occasion_label' => $data['occasion_label'],
            'recipient_name' => $data['recipient_name'],
            'reminder_date' => $data['reminder_date'],
            'note' => $data['note'],
            'status' => $this->resolveStatusForManualUpdate($reminder, ($input['is_active'] ?? null) === '1'),
            'is_active' => ($input['is_active'] ?? null) === '1' ? 1 : 0,
            'cancelled_at' => ($input['is_active'] ?? null) === '1' ? null : date('Y-m-d H:i:s'),
            'expired_at' => null,
            'action_required_by' => null,
        ], [
            'id' => $reminderId,
            'customer_id' => $customerId,
        ]);

        return [
            'success' => true,
            'error' => null,
            'reminder' => $this->findForCustomer($customerId, $reminderId),
        ];
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function deleteForCustomer(int $customerId, int $reminderId): array
    {
        if ($this->findForCustomer($customerId, $reminderId) === null) {
            return ['success' => false, 'error' => 'Reminder not found for this customer account.'];
        }

        (new QueryBuilder($this->app->database()))->delete('customer_reminders', [
            'id' => $reminderId,
            'customer_id' => $customerId,
        ]);

        return ['success' => true, 'error' => null];
    }

    /**
     * @return array{success: bool, error: string|null}
     */
    public function toggleForCustomer(int $customerId, int $reminderId): array
    {
        $reminder = $this->findForCustomer($customerId, $reminderId);

        if ($reminder === null) {
            return ['success' => false, 'error' => 'Reminder not found for this customer account.'];
        }

        (new QueryBuilder($this->app->database()))->update('customer_reminders', [
            'is_active' => !empty($reminder['is_active']) ? 0 : 1,
            'status' => $this->resolveStatusForToggle($reminder),
            'cancelled_at' => !empty($reminder['is_active']) ? date('Y-m-d H:i:s') : null,
            'expired_at' => !empty($reminder['is_active']) ? ($reminder['expired_at'] ?? null) : null,
            'action_required_by' => !empty($reminder['is_active']) ? null : ($reminder['action_required_by'] ?? null),
        ], [
            'id' => $reminderId,
            'customer_id' => $customerId,
        ]);

        return ['success' => true, 'error' => null];
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizeInput(array $input): array
    {
        return [
            'occasion_label' => trim((string) ($input['occasion_label'] ?? '')),
            'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
            'reminder_date' => trim((string) ($input['reminder_date'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $data
     */
    public function validateInput(array $data): ?string
    {
        if ($data['occasion_label'] === '') {
            return 'Occasion label is required.';
        }

        if ($data['recipient_name'] === '') {
            return 'Recipient name is required.';
        }

        if ($data['reminder_date'] === '') {
            return 'Reminder date is required.';
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['reminder_date']);

        if (!$date instanceof \DateTimeImmutable || $date->format('Y-m-d') !== $data['reminder_date']) {
            return 'Reminder date must be a valid date.';
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listUpcomingReminderNotifications(\DateTimeImmutable $sendBeforeDate, ?\DateTimeImmutable $sendFromDate = null): array
    {
        $targetDate = $sendBeforeDate->format('Y-m-d');

        $rows = $this->app->database()->fetchAll(
            'SELECT cr.id, cr.customer_id, cr.product_id, cr.order_id, cr.occasion_label, cr.recipient_name, cr.reminder_date, cr.note,
                    cr.status, cr.is_active, cr.last_sent_at, cr.upcoming_notice_sent_at, cr.action_required_by, cr.expired_at, cr.cancelled_at,
                    cr.created_at, cr.updated_at,
                    p.name AS product_name, p.slug AS product_slug,
                    o.order_number, o.status AS order_status, o.total_amount AS order_total_amount
             FROM customer_reminders cr
             INNER JOIN customers c ON c.id = cr.customer_id
             LEFT JOIN products p ON p.id = cr.product_id
             LEFT JOIN orders o ON o.id = cr.order_id
             WHERE cr.is_active = 1
               AND c.reminder_email_opt_in = 1
               AND cr.reminder_date = :target_date
               AND cr.upcoming_notice_sent_at IS NULL
               AND cr.status IN (:active_status, :purchased_status)
             ORDER BY cr.reminder_date ASC, cr.id ASC',
            [
                'target_date' => $targetDate,
                'active_status' => self::STATUS_ACTIVE,
                'purchased_status' => self::STATUS_PURCHASED,
            ]
        );

        return array_map([$this, 'normalizeReminder'], $rows);
    }

    /**
     * @return array{marked: bool, reminder: array<string, mixed>|null}
     */
    public function markUpcomingReminderSent(int $reminderId, \DateTimeImmutable $sentAt): array
    {
        $reminder = $this->findById($reminderId);

        if ($reminder === null) {
            return ['marked' => false, 'reminder' => null];
        }

        $isPurchased = !empty($reminder['order_id']);
        $status = $isPurchased ? self::STATUS_PURCHASED : self::STATUS_ACTION_NEEDED;
        $actionRequiredBy = !$isPurchased
            ? $sentAt->modify('+' . self::ACTION_WINDOW_HOURS . ' hours')->format('Y-m-d H:i:s')
            : null;

        (new QueryBuilder($this->app->database()))->update('customer_reminders', [
            'status' => $status,
            'last_sent_at' => $sentAt->format('Y-m-d H:i:s'),
            'upcoming_notice_sent_at' => $sentAt->format('Y-m-d H:i:s'),
            'action_required_by' => $actionRequiredBy,
        ], ['id' => $reminderId]);

        return ['marked' => true, 'reminder' => $this->findById($reminderId)];
    }

    /**
     * @return array{expiring_soon: int, expired: int}
     */
    public function advanceReminderLifecycle(\DateTimeImmutable $referenceTime): array
    {
        $soonThreshold = $referenceTime->modify('+24 hours')->format('Y-m-d H:i:s');
        $reference = $referenceTime->format('Y-m-d H:i:s');
        $expiringSoon = 0;
        $expired = 0;

        $expiringSoonRows = $this->app->database()->fetchAll(
            'SELECT id
             FROM customer_reminders
             WHERE order_id IS NULL
               AND is_active = 1
               AND status = :action_needed
               AND action_required_by IS NOT NULL
               AND action_required_by > :now_time
               AND action_required_by <= :soon_threshold',
            [
                'action_needed' => self::STATUS_ACTION_NEEDED,
                'now_time' => $reference,
                'soon_threshold' => $soonThreshold,
            ]
        );

        foreach ($expiringSoonRows as $row) {
            $reminderId = (int) ($row['id'] ?? 0);
            if ($reminderId <= 0) {
                continue;
            }

            (new QueryBuilder($this->app->database()))->update('customer_reminders', [
                'status' => self::STATUS_EXPIRING_SOON,
            ], ['id' => $reminderId]);
            $expiringSoon++;
        }

        $expiredRows = $this->app->database()->fetchAll(
            'SELECT id
             FROM customer_reminders
             WHERE order_id IS NULL
               AND is_active = 1
               AND status IN (:action_needed, :expiring_soon)
               AND action_required_by IS NOT NULL
               AND action_required_by <= :now_time',
            [
                'action_needed' => self::STATUS_ACTION_NEEDED,
                'expiring_soon' => self::STATUS_EXPIRING_SOON,
                'now_time' => $reference,
            ]
        );

        foreach ($expiredRows as $row) {
            $reminderId = (int) ($row['id'] ?? 0);
            if ($reminderId <= 0) {
                continue;
            }

            (new QueryBuilder($this->app->database()))->update('customer_reminders', [
                'status' => self::STATUS_EXPIRED,
                'is_active' => 0,
                'expired_at' => $reference,
            ], ['id' => $reminderId]);
            $expired++;
        }

        return [
            'expiring_soon' => $expiringSoon,
            'expired' => $expired,
        ];
    }

    private function findForCustomerByOrderId(int $customerId, int $orderId): ?array
    {
        if ($customerId <= 0 || $orderId <= 0) {
            return null;
        }

        $row = $this->app->database()->query(
            'SELECT cr.id
             FROM customer_reminders cr
             WHERE cr.customer_id = :customer_id
               AND cr.order_id = :order_id
             LIMIT 1',
            [
                'customer_id' => $customerId,
                'order_id' => $orderId,
            ]
        )->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $this->findForCustomer($customerId, (int) ($row['id'] ?? 0));
    }

    private function resolveReminderProductIdForOrder(int $orderId, int $preferredProductId = 0): int
    {
        if ($orderId <= 0) {
            return 0;
        }

        $sql = 'SELECT product_id
                FROM order_items
                WHERE order_id = :order_id
                  AND product_id IS NOT NULL';
        $params = ['order_id' => $orderId];

        if ($preferredProductId > 0) {
            $sql .= '
                ORDER BY CASE WHEN product_id = :preferred_product_id THEN 0 ELSE 1 END ASC, id ASC';
            $params['preferred_product_id'] = $preferredProductId;
        } else {
            $sql .= '
                ORDER BY id ASC';
        }

        $sql .= '
                LIMIT 1';

        $row = $this->app->database()->query($sql, $params)->fetch();

        return is_array($row) ? (int) ($row['product_id'] ?? 0) : 0;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeReminder(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['customer_id'] = (int) ($row['customer_id'] ?? 0);
        $row['product_id'] = !empty($row['product_id']) ? (int) $row['product_id'] : null;
        $row['order_id'] = !empty($row['order_id']) ? (int) $row['order_id'] : null;
        $row['occasion_label'] = trim((string) ($row['occasion_label'] ?? ''));
        $row['recipient_name'] = trim((string) ($row['recipient_name'] ?? ''));
        $row['reminder_date'] = trim((string) ($row['reminder_date'] ?? ''));
        $row['note'] = trim((string) ($row['note'] ?? ''));
        $row['status'] = $this->normalizeStatus((string) ($row['status'] ?? ''));
        $row['is_active'] = (bool) ($row['is_active'] ?? false);
        $row['last_sent_at'] = trim((string) ($row['last_sent_at'] ?? ''));
        $row['upcoming_notice_sent_at'] = trim((string) ($row['upcoming_notice_sent_at'] ?? ''));
        $row['action_required_by'] = trim((string) ($row['action_required_by'] ?? ''));
        $row['expired_at'] = trim((string) ($row['expired_at'] ?? ''));
        $row['cancelled_at'] = trim((string) ($row['cancelled_at'] ?? ''));
        $row['product_name'] = trim((string) ($row['product_name'] ?? ''));
        $row['product_slug'] = trim((string) ($row['product_slug'] ?? ''));
        $row['order_number'] = trim((string) ($row['order_number'] ?? ''));
        $row['order_status'] = trim((string) ($row['order_status'] ?? ''));
        $row['order_total_amount'] = array_key_exists('order_total_amount', $row) ? (float) ($row['order_total_amount'] ?? 0) : 0.0;
        $row['has_purchase'] = !empty($row['order_id']);
        $row['status_label'] = $this->statusLabel((string) ($row['status'] ?? ''));
        $row['status_tone'] = $this->statusTone((string) ($row['status'] ?? ''));
        $row['mode_label'] = !empty($row['order_id']) ? 'Reminder With Purchase' : 'Reminder Without Purchase';
        $row['action_window_hours'] = self::ACTION_WINDOW_HOURS;

        return $row;
    }

    /**
     * @return array{success: bool, error: string|null, reminder: array<string, mixed>|null, already_exists: bool}
     */
    private function attachPaidOrderToReminder(int $customerId, int $reminderId, int $orderId, array $input): array
    {
        $reminder = $this->findForCustomer($customerId, $reminderId);

        if ($reminder === null) {
            return ['success' => false, 'error' => 'Reminder not found for this customer account.', 'reminder' => null, 'already_exists' => false];
        }

        $existingForOrder = $this->findForCustomerByOrderId($customerId, $orderId);

        if ($existingForOrder !== null && (int) ($existingForOrder['id'] ?? 0) !== $reminderId) {
            return ['success' => true, 'error' => null, 'reminder' => $existingForOrder, 'already_exists' => true];
        }

        $preferredProductId = (int) ($input['product_id'] ?? $reminder['product_id'] ?? 0);
        $productId = $this->resolveReminderProductIdForOrder($orderId, $preferredProductId);

        if ($productId <= 0) {
            return ['success' => false, 'error' => 'A purchased product is required before the reminder can be finalized.', 'reminder' => null, 'already_exists' => false];
        }

        $data = $this->normalizeInput($input + $reminder);
        $validationError = $this->validateInput($data);

        if ($validationError !== null) {
            return ['success' => false, 'error' => $validationError, 'reminder' => null, 'already_exists' => false];
        }

        (new QueryBuilder($this->app->database()))->update('customer_reminders', [
            'product_id' => $productId,
            'order_id' => $orderId,
            'occasion_label' => $data['occasion_label'],
            'recipient_name' => $data['recipient_name'],
            'reminder_date' => $data['reminder_date'],
            'note' => $data['note'],
            'status' => self::STATUS_PURCHASED,
            'is_active' => 1,
            'action_required_by' => null,
            'expired_at' => null,
            'cancelled_at' => null,
        ], [
            'id' => $reminderId,
            'customer_id' => $customerId,
        ]);

        return [
            'success' => true,
            'error' => null,
            'reminder' => $this->findForCustomer($customerId, $reminderId),
            'already_exists' => false,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_PURCHASED,
            self::STATUS_ACTION_NEEDED,
            self::STATUS_EXPIRING_SOON,
            self::STATUS_EXPIRED,
            self::STATUS_CANCELLED,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_ACTIVE;
    }

    private function statusLabel(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PURCHASED => 'Purchased',
            self::STATUS_ACTION_NEEDED => 'Action Needed',
            self::STATUS_EXPIRING_SOON => 'Expiring Soon',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Active',
        };
    }

    private function statusTone(string $status): string
    {
        return match ($this->normalizeStatus($status)) {
            self::STATUS_PURCHASED => 'success',
            self::STATUS_ACTION_NEEDED, self::STATUS_EXPIRING_SOON => 'warning',
            self::STATUS_EXPIRED, self::STATUS_CANCELLED => 'muted',
            default => 'info',
        };
    }

    /**
     * @param array<string, mixed> $reminder
     */
    private function resolveStatusForManualUpdate(array $reminder, bool $shouldBeActive): string
    {
        if (!$shouldBeActive) {
            return self::STATUS_CANCELLED;
        }

        if (!empty($reminder['order_id'])) {
            return self::STATUS_PURCHASED;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * @param array<string, mixed> $reminder
     */
    private function resolveStatusForToggle(array $reminder): string
    {
        if (!empty($reminder['is_active'])) {
            return self::STATUS_CANCELLED;
        }

        if (!empty($reminder['order_id'])) {
            return self::STATUS_PURCHASED;
        }

        return self::STATUS_ACTIVE;
    }
}
