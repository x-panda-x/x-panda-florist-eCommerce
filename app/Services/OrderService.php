<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\QueryBuilder;

final class OrderService
{
    public const POLICY_VERSION = '2026-03-final-sale-v1';
    public const ALLOWED_TIP_AMOUNTS = [
        '0.00',
        '5.00',
        '10.00',
        '15.00',
    ];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_STATUSES = [
        'pending',
        'confirmed',
        'preparing',
        'out_for_delivery',
        'completed',
        'cancelled',
    ];

    /**
     * @var array<int, string>
     */
    public const ALLOWED_DELIVERY_SLOTS = [
        '09:00-12:00',
        '12:00-15:00',
        '15:00-18:00',
    ];

    private Application $app;
    private SettingsService $settingsService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->settingsService = new SettingsService($app);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $items
     * @return array{id: int, order_number: string, public_access_token: string}
     */
    public function createOrder(array $payload, array $items): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('Order items are required.');
        }

        $pdo = $this->app->database()->connection();
        $queryBuilder = new QueryBuilder($this->app->database());
        $orderNumber = $this->generateOrderNumber();
        $publicAccessToken = $this->generatePublicAccessToken();

        $pdo->beginTransaction();

        try {
            $queryBuilder->insert('orders', [
                'order_number' => $orderNumber,
                'public_access_token' => $publicAccessToken,
                'customer_id' => $payload['customer_id'] ?? null,
                'customer_name' => $payload['customer_name'],
                'customer_email' => $payload['customer_email'],
                'customer_phone' => $payload['customer_phone'],
                'recipient_name' => $payload['recipient_name'],
                'delivery_address' => $payload['delivery_address'],
                'delivery_zip' => $payload['delivery_zip'],
                'delivery_date' => $payload['delivery_date'],
                'delivery_time_slot' => $payload['delivery_time_slot'],
                'delivery_instructions' => $payload['delivery_instructions'],
                'card_message' => $payload['card_message'],
                'subtotal' => $payload['subtotal'],
                'promo_code' => $payload['promo_code'],
                'promo_discount_amount' => $payload['promo_discount_amount'],
                'delivery_fee' => $payload['delivery_fee'],
                'tax_amount' => $payload['tax_amount'],
                'tip_amount' => $payload['tip_amount'],
                'total_amount' => $payload['total_amount'],
                'status' => $payload['status'],
                'tracking_status_label' => $payload['tracking_status_label'],
                'tracking_public_note' => $payload['tracking_public_note'],
                'status_updated_at' => $payload['status_updated_at'],
                'policy_version' => $payload['policy_version'],
                'policy_accepted' => $payload['policy_accepted'],
                'policy_accepted_at' => $payload['policy_accepted_at'],
                'customer_ip' => $payload['customer_ip'],
                'user_agent' => $payload['user_agent'],
            ]);

            $orderId = (int) $pdo->lastInsertId();

            foreach ($items as $item) {
                $queryBuilder->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_slug' => $item['product_slug'],
                    'product_name' => $item['product_name'],
                    'variant_id' => $item['variant_id'] ?: null,
                    'variant_name' => $item['variant_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);

                $orderItemId = (int) $pdo->lastInsertId();

                foreach (($item['addons'] ?? []) as $addon) {
                    if (!is_array($addon)) {
                        continue;
                    }

                    $quantity = max(1, (int) ($addon['quantity'] ?? $item['quantity'] ?? 1));
                    $unitPrice = max(0, (float) ($addon['unit_price'] ?? 0));

                    $queryBuilder->insert('order_addons', [
                        'order_id' => $orderId,
                        'order_item_id' => $orderItemId > 0 ? $orderItemId : null,
                        'addon_id' => !empty($addon['addon_id']) ? (int) $addon['addon_id'] : null,
                        'addon_name' => (string) ($addon['addon_name'] ?? ''),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => round($unitPrice * $quantity, 2),
                    ]);
                }
            }

            if (!empty($payload['promo_id'])) {
                $statement = $this->app->database()->query(
                    'UPDATE promo_codes
                     SET times_used = times_used + 1
                     WHERE id = :id
                       AND is_active = 1
                       AND (usage_limit IS NULL OR times_used < usage_limit)',
                    ['id' => (int) $payload['promo_id']]
                );

                if ($statement->rowCount() !== 1) {
                    throw new \RuntimeException('Promo code is no longer valid.');
                }
            }

            $pdo->commit();

            return [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'public_access_token' => $publicAccessToken,
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
     * @return array<string, string>
     */
    public function normalizeCheckoutInput(array $input): array
    {
        return [
            'customer_id' => !empty($input['customer_id']) ? (string) (int) $input['customer_id'] : '',
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'customer_email' => trim((string) ($input['customer_email'] ?? '')),
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
            'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
            'delivery_address' => trim((string) ($input['delivery_address'] ?? '')),
            'delivery_zip' => $this->normalizeZip((string) ($input['delivery_zip'] ?? '')),
            'delivery_date' => trim((string) ($input['delivery_date'] ?? '')),
            'delivery_time_slot' => trim((string) ($input['delivery_time_slot'] ?? '')),
            'delivery_instructions' => trim((string) ($input['delivery_instructions'] ?? '')),
            'card_message' => trim((string) ($input['card_message'] ?? '')),
            'tip_amount' => $this->normalizeTipAmount((string) ($input['tip_amount'] ?? '0.00')),
            'policy_accepted' => (($input['policy_accepted'] ?? null) === '1') ? '1' : '',
        ];
    }

    /**
     * @param array<string, string> $input
     */
    public function validateCheckoutInput(array $input): ?string
    {
        if ($input['customer_name'] === '') {
            return 'Customer name is required.';
        }

        if ($input['customer_email'] === '' || filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL) === false) {
            return 'A valid customer email is required.';
        }

        if ($input['customer_phone'] === '') {
            return 'Customer phone is required.';
        }

        if ($input['delivery_address'] === '') {
            return 'Delivery address is required.';
        }

        if ($input['delivery_zip'] === '') {
            return 'Delivery ZIP code is required.';
        }

        if (!$this->isServiceableZip($input['delivery_zip'])) {
            return 'We do not currently deliver to that ZIP code.';
        }

        if ($input['delivery_date'] === '') {
            return 'Delivery date is required.';
        }

        $deliveryDate = \DateTimeImmutable::createFromFormat('Y-m-d', $input['delivery_date']);

        if (!$deliveryDate instanceof \DateTimeImmutable || $deliveryDate->format('Y-m-d') !== $input['delivery_date']) {
            return 'Delivery date must be a valid date.';
        }

        $today = new \DateTimeImmutable('today');

        if ($deliveryDate < $today) {
            return 'Delivery date cannot be in the past.';
        }

        if (!$this->isValidDeliverySlot($input['delivery_time_slot'])) {
            return 'Please choose a valid delivery time slot.';
        }

        if (!$this->isValidTipAmount($input['tip_amount'])) {
            return 'Please choose a valid tip option.';
        }

        if ($input['policy_accepted'] !== '1') {
            return 'You must accept the final sale, cancellation, and refund policy before placing your order.';
        }

        if ($deliveryDate->format('Y-m-d') === $today->format('Y-m-d')) {
            $cutoff = $this->sameDayCutoff();

            if ($cutoff !== null) {
                $currentTime = new \DateTimeImmutable('now');
                $cutoffToday = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $today->format('Y-m-d') . ' ' . $cutoff);

                if ($cutoffToday instanceof \DateTimeImmutable && $currentTime > $cutoffToday) {
                    return 'Same-day delivery is no longer available after the current cutoff time.';
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $input
     * @param array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int} $cartSummary
     * @return array<string, mixed>
     */
    public function buildOrderPayload(array $input, array $cartSummary, ?array $appliedPromo = null): array
    {
        $pricing = $this->calculatePricing($cartSummary, $input['delivery_zip'], (float) $input['tip_amount'], $appliedPromo);
        $requestMetadata = $this->captureRequestMetadata();

        return [
            'customer_id' => !empty($input['customer_id']) ? (int) $input['customer_id'] : null,
            'customer_name' => $input['customer_name'],
            'customer_email' => $input['customer_email'],
            'customer_phone' => $input['customer_phone'],
            'recipient_name' => $input['recipient_name'],
            'delivery_address' => $input['delivery_address'],
            'delivery_zip' => $input['delivery_zip'],
            'delivery_date' => $input['delivery_date'],
            'delivery_time_slot' => $input['delivery_time_slot'],
            'delivery_instructions' => $input['delivery_instructions'],
            'card_message' => $input['card_message'],
            'subtotal' => $cartSummary['subtotal'],
            'promo_id' => $pricing['promo_id'],
            'promo_code' => $pricing['promo_code'],
            'promo_discount_amount' => $pricing['promo_discount_amount'],
            'delivery_fee' => $pricing['delivery_fee'],
            'tax_amount' => $pricing['tax_amount'],
            'tip_amount' => $pricing['tip_amount'],
            'total_amount' => $pricing['total_amount'],
            'status' => 'pending',
            'tracking_status_label' => $this->defaultPublicTrackingLabel('pending'),
            'tracking_public_note' => null,
            'status_updated_at' => date('Y-m-d H:i:s'),
            'policy_version' => self::POLICY_VERSION,
            'policy_accepted' => 1,
            'policy_accepted_at' => date('Y-m-d H:i:s'),
            'customer_ip' => $requestMetadata['customer_ip'],
            'user_agent' => $requestMetadata['user_agent'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listOrders(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, customer_id, order_number, customer_name, recipient_name, subtotal, status, created_at
             FROM orders
             ORDER BY created_at DESC, id DESC'
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizeAdminConsoleFilters(array $input): array
    {
        $today = $this->operationsToday();
        $calendarMonth = trim((string) ($input['calendar_month'] ?? $today->format('Y-m')));
        $calendarDay = trim((string) ($input['calendar_day'] ?? ''));

        if (!$this->isValidMonthString($calendarMonth)) {
            $calendarMonth = $today->format('Y-m');
        }

        if (!$this->isValidDateString($calendarDay)) {
            $calendarDay = '';
        }

        $quickFilter = trim((string) ($input['quick_filter'] ?? ''));
        $allowedQuickFilters = ['', 'today', 'tomorrow', 'weekend', 'next_7_days', 'same_day_only'];

        if (!in_array($quickFilter, $allowedQuickFilters, true)) {
            $quickFilter = '';
        }

        $deliveryType = trim((string) ($input['delivery_type'] ?? ''));
        $allowedDeliveryTypes = ['', 'same_day', 'scheduled', 'reminder_linked'];

        if (!in_array($deliveryType, $allowedDeliveryTypes, true)) {
            $deliveryType = '';
        }

        $status = trim((string) ($input['status'] ?? ''));

        if ($status !== '' && !$this->isValidStatus($status)) {
            $status = '';
        }

        $filters = [
            'order_number' => trim((string) ($input['order_number'] ?? '')),
            'customer_name' => trim((string) ($input['customer_name'] ?? '')),
            'recipient_name' => trim((string) ($input['recipient_name'] ?? '')),
            'customer_phone' => trim((string) ($input['customer_phone'] ?? '')),
            'status' => $status,
            'delivery_type' => $deliveryType,
            'order_date_from' => trim((string) ($input['order_date_from'] ?? '')),
            'order_date_to' => trim((string) ($input['order_date_to'] ?? '')),
            'delivery_date_from' => trim((string) ($input['delivery_date_from'] ?? '')),
            'delivery_date_to' => trim((string) ($input['delivery_date_to'] ?? '')),
            'quick_filter' => $quickFilter,
            'calendar_month' => $calendarMonth,
            'calendar_day' => $calendarDay,
        ];

        foreach (['order_date_from', 'order_date_to', 'delivery_date_from', 'delivery_date_to'] as $key) {
            if (!$this->isValidDateString($filters[$key])) {
                $filters[$key] = '';
            }
        }

        return $filters;
    }

    /**
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    public function buildAdminOrdersConsole(array $filters): array
    {
        $orders = $this->listOrdersForAdminConsole($filters);
        $orderIds = array_values(array_filter(array_map(
            static fn (array $order): int => (int) ($order['id'] ?? 0),
            $orders
        )));
        $previewItems = $this->listItemsByOrderIds($orderIds);

        foreach ($orders as $index => $order) {
            $orders[$index]['delivery_type'] = $this->deriveOperationalDeliveryType($order);
            $orders[$index]['delivery_type_label'] = $this->deliveryTypeLabel((string) $orders[$index]['delivery_type']);
            $orders[$index]['status_tone'] = $this->statusTone((string) ($order['status'] ?? 'pending'));
            $orders[$index]['is_same_day'] = $this->isSameDayOrder($order);
            $orders[$index]['is_reminder_linked'] = ((int) ($order['reminder_link_count'] ?? 0)) > 0;
            $orders[$index]['preview_items'] = $previewItems[(int) ($order['id'] ?? 0)] ?? [];
        }

        $calendar = $this->buildAdminOrderCalendar($filters);
        $calendarDay = (string) ($filters['calendar_day'] ?? '');

        return [
            'filters' => $filters,
            'summary' => $this->adminOrderSummary(),
            'orders' => $orders,
            'calendar' => $calendar,
            'selectedDayOrders' => $calendarDay !== '' ? ($calendar['orders_by_day'][$calendarDay] ?? []) : [],
            'selectedDay' => $calendarDay,
            'deliveryTypeOptions' => [
                'same_day' => 'Same Day',
                'scheduled' => 'Scheduled',
                'reminder_linked' => 'Reminder-Linked',
            ],
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function listOrdersForAdminConsole(array $filters): array
    {
        [$whereSql, $params] = $this->buildAdminOrderFilterClauses($filters);

        return $this->app->database()->fetchAll(
            'SELECT o.id, o.customer_id, o.order_number, o.customer_name, o.customer_email, o.customer_phone,
                    o.recipient_name, o.delivery_address, o.delivery_zip, o.delivery_date, o.delivery_time_slot,
                    o.delivery_instructions, o.card_message, o.subtotal, o.total_amount, o.status, o.created_at,
                    o.updated_at, COALESCE(rem.reminder_link_count, 0) AS reminder_link_count
             FROM orders o
             LEFT JOIN (
                SELECT order_id, COUNT(*) AS reminder_link_count
                FROM customer_reminders
                WHERE order_id IS NOT NULL
                GROUP BY order_id
             ) rem ON rem.order_id = o.id
             ' . $whereSql . '
             ORDER BY
                CASE WHEN o.delivery_date IS NULL THEN 1 ELSE 0 END ASC,
                o.delivery_date ASC,
                o.delivery_time_slot ASC,
                o.created_at DESC,
                o.id DESC',
            $params
        );
    }

    /**
     * @return array<string, int>
     */
    public function adminOrderSummary(): array
    {
        $today = $this->operationsToday();
        $tomorrow = $today->modify('+1 day');
        $weekEnd = $this->operationsWeekEnd($today);
        [$weekendStart, $weekendEnd] = $this->operationsWeekendWindow($today);

        $summary = $this->app->database()->query(
            'SELECT
                SUM(CASE WHEN delivery_date = :summary_today THEN 1 ELSE 0 END) AS today_count,
                SUM(CASE WHEN delivery_date = :tomorrow THEN 1 ELSE 0 END) AS tomorrow_count,
                SUM(CASE WHEN delivery_date BETWEEN :week_start AND :week_end THEN 1 ELSE 0 END) AS this_week_count,
                SUM(CASE WHEN delivery_date BETWEEN :weekend_start AND :weekend_end THEN 1 ELSE 0 END) AS weekend_count,
                SUM(CASE WHEN delivery_date = :same_day_delivery_today AND delivery_date IS NOT NULL AND DATE(created_at) = :same_day_created_today THEN 1 ELSE 0 END) AS same_day_today_count,
                SUM(CASE WHEN status = \'pending\' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = \'confirmed\' THEN 1 ELSE 0 END) AS confirmed_count,
                SUM(CASE WHEN status = \'out_for_delivery\' THEN 1 ELSE 0 END) AS out_for_delivery_count
             FROM orders',
            [
                'summary_today' => $today->format('Y-m-d'),
                'tomorrow' => $tomorrow->format('Y-m-d'),
                'week_start' => $today->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'weekend_start' => $weekendStart->format('Y-m-d'),
                'weekend_end' => $weekendEnd->format('Y-m-d'),
                'same_day_delivery_today' => $today->format('Y-m-d'),
                'same_day_created_today' => $today->format('Y-m-d'),
            ]
        )->fetch();

        if (!is_array($summary)) {
            return [
                'today' => 0,
                'tomorrow' => 0,
                'this_week' => 0,
                'weekend' => 0,
                'same_day_today' => 0,
                'pending' => 0,
                'confirmed' => 0,
                'out_for_delivery' => 0,
            ];
        }

        return [
            'today' => (int) ($summary['today_count'] ?? 0),
            'tomorrow' => (int) ($summary['tomorrow_count'] ?? 0),
            'this_week' => (int) ($summary['this_week_count'] ?? 0),
            'weekend' => (int) ($summary['weekend_count'] ?? 0),
            'same_day_today' => (int) ($summary['same_day_today_count'] ?? 0),
            'pending' => (int) ($summary['pending_count'] ?? 0),
            'confirmed' => (int) ($summary['confirmed_count'] ?? 0),
            'out_for_delivery' => (int) ($summary['out_for_delivery_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, string> $filters
     * @return array<string, mixed>
     */
    public function buildAdminOrderCalendar(array $filters): array
    {
        $month = (string) ($filters['calendar_month'] ?? $this->operationsToday()->format('Y-m'));
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01', $this->operationsTimeZone());

        if (!$monthStart instanceof \DateTimeImmutable) {
            $monthStart = $this->operationsToday()->modify('first day of this month');
        }

        $monthEnd = $monthStart->modify('last day of this month');
        [$whereSql, $params] = $this->buildAdminOrderFilterClauses($filters);
        $whereSql .= ($whereSql === '' ? ' WHERE ' : ' AND ') . 'o.delivery_date BETWEEN :calendar_start AND :calendar_end';
        $params['calendar_start'] = $monthStart->format('Y-m-d');
        $params['calendar_end'] = $monthEnd->format('Y-m-d');

        $orders = $this->app->database()->fetchAll(
            'SELECT o.id, o.order_number, o.customer_name, o.recipient_name, o.delivery_date, o.delivery_time_slot,
                    o.total_amount, o.status, o.created_at, COALESCE(rem.reminder_link_count, 0) AS reminder_link_count
             FROM orders o
             LEFT JOIN (
                SELECT order_id, COUNT(*) AS reminder_link_count
                FROM customer_reminders
                WHERE order_id IS NOT NULL
                GROUP BY order_id
             ) rem ON rem.order_id = o.id
             ' . $whereSql . '
             ORDER BY o.delivery_date ASC, o.delivery_time_slot ASC, o.created_at DESC, o.id DESC',
            $params
        );

        $ordersByDay = [];

        foreach ($orders as $order) {
            $day = (string) ($order['delivery_date'] ?? '');

            if ($day === '') {
                continue;
            }

            $order['status_tone'] = $this->statusTone((string) ($order['status'] ?? 'pending'));
            $order['delivery_type'] = $this->deriveOperationalDeliveryType($order);
            $order['delivery_type_label'] = $this->deliveryTypeLabel((string) $order['delivery_type']);
            $order['is_same_day'] = $this->isSameDayOrder($order);
            $ordersByDay[$day][] = $order;
        }

        $gridStart = $monthStart->modify('-' . ((int) $monthStart->format('w')) . ' days');
        $gridEnd = $monthEnd->modify('+' . (6 - (int) $monthEnd->format('w')) . ' days');
        $weeks = [];
        $cursor = $gridStart;
        $todayKey = $this->operationsToday()->format('Y-m-d');

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $dateKey = $cursor->format('Y-m-d');
                $week[] = [
                    'date' => $dateKey,
                    'day' => $cursor->format('j'),
                    'isCurrentMonth' => $cursor->format('Y-m') === $monthStart->format('Y-m'),
                    'isToday' => $dateKey === $todayKey,
                    'orders' => $ordersByDay[$dateKey] ?? [],
                ];
                $cursor = $cursor->modify('+1 day');
            }

            $weeks[] = $week;
        }

        return [
            'month' => $monthStart->format('Y-m'),
            'monthLabel' => $monthStart->format('F Y'),
            'previousMonth' => $monthStart->modify('-1 month')->format('Y-m'),
            'nextMonth' => $monthStart->modify('+1 month')->format('Y-m'),
            'weeks' => $weeks,
            'orders_by_day' => $ordersByDay,
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<int, array<string, mixed>>
     */
    public function listOrdersForCustomer(array $customer): array
    {
        $customerId = (int) ($customer['id'] ?? 0);
        $customerEmail = strtolower(trim((string) ($customer['email'] ?? '')));

        if ($customerId <= 0 || $customerEmail === '') {
            return [];
        }

        return $this->app->database()->fetchAll(
            'SELECT id, customer_id, order_number, customer_name, recipient_name, delivery_date, delivery_time_slot,
                    subtotal, promo_code, promo_discount_amount, delivery_fee, tax_amount, tip_amount,
                    total_amount, status, tracking_status_label, tracking_public_note, status_updated_at, created_at
             FROM orders
             WHERE customer_id = :customer_id
                OR (customer_id IS NULL AND LOWER(customer_email) = :customer_email)
             ORDER BY created_at DESC, id DESC',
            [
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
            ]
        );
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<string, mixed>|null
     */
    public function findOrderForCustomerById(array $customer, int $orderId): ?array
    {
        $customerId = (int) ($customer['id'] ?? 0);
        $customerEmail = strtolower(trim((string) ($customer['email'] ?? '')));

        if ($customerId <= 0 || $customerEmail === '') {
            return null;
        }

        $order = $this->app->database()->query(
            'SELECT id, customer_id, order_number, public_access_token, customer_name, customer_email, customer_phone, recipient_name,
                    delivery_address, delivery_zip, delivery_date, delivery_time_slot, delivery_instructions,
                    card_message, subtotal, promo_code, promo_discount_amount, delivery_fee, tax_amount, tip_amount,
                    total_amount, status, tracking_status_label, tracking_public_note, status_updated_at, policy_version,
                    policy_accepted, policy_accepted_at, created_at, updated_at
             FROM orders
             WHERE id = :id
               AND (
                    customer_id = :customer_id
                    OR (customer_id IS NULL AND LOWER(customer_email) = :customer_email)
               )
             LIMIT 1',
            [
                'id' => $orderId,
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
            ]
        )->fetch();

        return is_array($order) ? $order : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderById(int $orderId): ?array
    {
        $order = $this->app->database()->query(
            'SELECT id, customer_id, order_number, public_access_token, customer_name, customer_email, customer_phone, recipient_name,
                    delivery_address, delivery_zip, delivery_date, delivery_time_slot, delivery_instructions,
                    card_message, subtotal, promo_code, promo_discount_amount, delivery_fee, tax_amount, tip_amount,
                    total_amount, status, tracking_status_label, tracking_public_note, status_updated_at, policy_version,
                    policy_accepted, policy_accepted_at, customer_ip, user_agent, created_at, updated_at
             FROM orders
             WHERE id = :id
             LIMIT 1',
            ['id' => $orderId]
        )->fetch();

        return is_array($order) ? $order : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderByNumber(string $orderNumber): ?array
    {
        $order = $this->app->database()->query(
            'SELECT id, customer_id, order_number, public_access_token, customer_name, customer_email, customer_phone, recipient_name,
                    delivery_address, delivery_zip, delivery_date, delivery_time_slot, delivery_instructions,
                    card_message, subtotal, promo_code, promo_discount_amount, delivery_fee, tax_amount, tip_amount,
                    total_amount, status, tracking_status_label, tracking_public_note, status_updated_at, policy_version,
                    policy_accepted, policy_accepted_at, customer_ip, user_agent, created_at, updated_at
             FROM orders
             WHERE order_number = :order_number
             LIMIT 1',
            ['order_number' => $orderNumber]
        )->fetch();

        return is_array($order) ? $order : null;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizePublicLookupInput(array $input): array
    {
        return [
            'order_number' => strtoupper(trim((string) ($input['order_number'] ?? ''))),
            'customer_email' => trim((string) ($input['customer_email'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $input
     */
    public function validatePublicLookupInput(array $input): ?string
    {
        if ($input['order_number'] === '') {
            return 'Order number is required.';
        }

        if ($input['customer_email'] === '' || filter_var($input['customer_email'], FILTER_VALIDATE_EMAIL) === false) {
            return 'A valid customer email is required.';
        }

        return null;
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>|null
     */
    public function findOrderForPublicLookup(array $input): ?array
    {
        $order = $this->app->database()->query(
            'SELECT id, customer_id, order_number, public_access_token, customer_name, customer_email, recipient_name,
                    delivery_date, delivery_time_slot, subtotal, promo_code, promo_discount_amount,
                    delivery_fee, tax_amount, tip_amount, total_amount, status,
                    tracking_status_label, tracking_public_note, status_updated_at, created_at
             FROM orders
             WHERE order_number = :order_number AND customer_email = :customer_email
             LIMIT 1',
            [
                'order_number' => $input['order_number'],
                'customer_email' => $input['customer_email'],
            ]
        )->fetch();

        return is_array($order) ? $order : null;
    }

    public function hasValidPublicAccessToken(array $order, string $token): bool
    {
        $storedToken = trim((string) ($order['public_access_token'] ?? ''));

        if ($storedToken === '' || $token === '') {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public function publicAccessToken(array $order): string
    {
        return trim((string) ($order['public_access_token'] ?? ''));
    }

    /**
     * @param array<string, mixed> $order
     * @return array{label: string, note: string, updated_at: string}
     */
    public function publicTrackingSummary(array $order): array
    {
        $status = (string) ($order['status'] ?? 'pending');
        $label = trim((string) ($order['tracking_status_label'] ?? ''));

        if ($label === '' || $this->isDefaultPublicTrackingLabel($label)) {
            $label = $this->defaultPublicTrackingLabel($status);
        }

        return [
            'label' => $label,
            'note' => trim((string) ($order['tracking_public_note'] ?? '')),
            'updated_at' => trim((string) ($order['status_updated_at'] ?? '')),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listItemsByOrderId(int $orderId): array
    {
        $items = $this->app->database()->fetchAll(
            'SELECT oi.id, oi.product_id, oi.product_slug, oi.product_name, oi.variant_id, oi.variant_name, oi.quantity, oi.unit_price, oi.line_total,
                    (
                        SELECT pi.image_path
                        FROM product_images pi
                        WHERE pi.product_id = oi.product_id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image_path
             FROM order_items oi
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC',
            ['order_id' => $orderId]
        );

        if ($items === []) {
            return [];
        }

        $addonsByItemId = [];

        foreach ($this->listAddonsByOrderId($orderId) as $addon) {
            $itemId = (int) ($addon['order_item_id'] ?? 0);

            if ($itemId <= 0) {
                continue;
            }

            if (!isset($addonsByItemId[$itemId])) {
                $addonsByItemId[$itemId] = [];
            }

            $addonsByItemId[$itemId][] = $addon;
        }

        foreach ($items as $index => $item) {
            $itemId = (int) ($item['id'] ?? 0);
            $items[$index]['addons'] = $addonsByItemId[$itemId] ?? [];
        }

        return $items;
    }

    /**
     * @param array<int, int> $orderIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function listItemsByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_unique(array_map('intval', $orderIds)), static fn (int $id): bool => $id > 0));

        if ($orderIds === []) {
            return [];
        }

        [$inSql, $params] = $this->buildIntegerInClause('order_id', $orderIds);
        $items = $this->app->database()->query(
            'SELECT oi.id, oi.order_id, oi.product_id, oi.product_slug, oi.product_name, oi.variant_id, oi.variant_name, oi.quantity, oi.unit_price, oi.line_total,
                    (
                        SELECT pi.image_path
                        FROM product_images pi
                        WHERE pi.product_id = oi.product_id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1
                    ) AS image_path
             FROM order_items oi
             WHERE oi.order_id IN (' . $inSql . ')
             ORDER BY oi.order_id ASC, oi.id ASC',
            $params
        )->fetchAll();

        if (!is_array($items) || $items === []) {
            return [];
        }

        [$addonInSql, $addonParams] = $this->buildIntegerInClause('addon_order_id', $orderIds);
        $addons = $this->app->database()->query(
            'SELECT id, order_id, order_item_id, addon_id, addon_name, quantity, unit_price, line_total, created_at
             FROM order_addons
             WHERE order_id IN (' . $addonInSql . ')
             ORDER BY order_id ASC, order_item_id ASC, id ASC',
            $addonParams
        )->fetchAll();

        $addonsByItemId = [];

        if (is_array($addons)) {
            foreach ($addons as $addon) {
                if (!is_array($addon)) {
                    continue;
                }

                $itemId = (int) ($addon['order_item_id'] ?? 0);

                if ($itemId <= 0) {
                    continue;
                }

                $addonsByItemId[$itemId][] = $addon;
            }
        }

        $itemsByOrderId = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemId = (int) ($item['id'] ?? 0);
            $orderId = (int) ($item['order_id'] ?? 0);
            $item['addons'] = $addonsByItemId[$itemId] ?? [];
            $itemsByOrderId[$orderId][] = $item;
        }

        return $itemsByOrderId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAddonsByOrderId(int $orderId): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, order_id, order_item_id, addon_id, addon_name, quantity, unit_price, line_total, created_at
             FROM order_addons
             WHERE order_id = :order_id
             ORDER BY order_item_id ASC, id ASC',
            ['order_id' => $orderId]
        );
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
    public function allowedDeliverySlots(): array
    {
        return self::ALLOWED_DELIVERY_SLOTS;
    }

    /**
     * @return array<int, string>
     */
    public function allowedTipAmounts(): array
    {
        return self::ALLOWED_TIP_AMOUNTS;
    }

    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::ALLOWED_STATUSES, true);
    }

    public function isValidDeliverySlot(string $slot): bool
    {
        return in_array($slot, self::ALLOWED_DELIVERY_SLOTS, true);
    }

    public function isValidTipAmount(string $tipAmount): bool
    {
        return in_array($tipAmount, self::ALLOWED_TIP_AMOUNTS, true);
    }

    public function sameDayCutoff(): ?string
    {
        $cutoff = trim((string) $this->settingsService->get('same_day_cutoff', ''));

        if ($cutoff === '' || preg_match('/^\d{2}:\d{2}$/', $cutoff) !== 1) {
            return null;
        }

        return $cutoff;
    }

    /**
     * @return array<string, float>
     */
    public function allowedDeliveryZips(): array
    {
        $rows = $this->app->database()->fetchAll(
            'SELECT zip_code, delivery_fee
             FROM delivery_zones
             WHERE is_active = 1
             ORDER BY zip_code ASC'
        );

        $zones = [];

        foreach ($rows as $row) {
            $zipCode = (string) ($row['zip_code'] ?? '');

            if ($zipCode === '') {
                continue;
            }

            $zones[$zipCode] = (float) ($row['delivery_fee'] ?? 0);
        }

        return $zones;
    }

    public function isServiceableZip(string $zip): bool
    {
        return $this->findActiveDeliveryZoneByZip($zip) !== null;
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, subtotal: float, item_count: int} $cartSummary
     * @param array<string, mixed>|null $appliedPromo
     * @return array{promo_id: int|null, promo_code: string|null, promo_discount_amount: float, delivery_fee: float, tax_amount: float, tip_amount: float, total_amount: float}
     */
    public function calculatePricing(array $cartSummary, string $deliveryZip, float $tipAmount = 0.0, ?array $appliedPromo = null): array
    {
        $subtotal = round((float) ($cartSummary['subtotal'] ?? 0), 2);
        $zone = $this->findActiveDeliveryZoneByZip($deliveryZip);
        $deliveryFee = $zone !== null ? (float) ($zone['delivery_fee'] ?? 0) : 0.0;
        $taxAmount = round($subtotal * $this->taxRate(), 2);
        $tipAmount = round(max(0, $tipAmount), 2);
        $promoDiscountAmount = 0.0;
        $promoCode = null;
        $promoId = null;

        if (is_array($appliedPromo) && !empty($appliedPromo['code'])) {
            $promoDiscountAmount = round(max(0, min($subtotal, (float) ($appliedPromo['discount_amount'] ?? 0))), 2);
            $promoCode = (string) ($appliedPromo['code'] ?? '');
            $promoId = !empty($appliedPromo['id']) ? (int) $appliedPromo['id'] : null;
        }

        $totalAmount = round($subtotal - $promoDiscountAmount + $deliveryFee + $taxAmount + $tipAmount, 2);

        return [
            'promo_id' => $promoId,
            'promo_code' => $promoCode,
            'promo_discount_amount' => $promoDiscountAmount,
            'delivery_fee' => $deliveryFee,
            'tax_amount' => $taxAmount,
            'tip_amount' => $tipAmount,
            'total_amount' => $totalAmount,
        ];
    }

    public function taxRate(): float
    {
        $rate = trim((string) $this->settingsService->get('sales_tax_rate', '0'));

        if ($rate === '' || !is_numeric($rate)) {
            return 0.0;
        }

        $normalized = (float) $rate;

        if ($normalized < 0 || $normalized > 1) {
            return 0.0;
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDeliveryZones(): array
    {
        return $this->app->database()->fetchAll(
            'SELECT id, zip_code, delivery_fee, is_active
             FROM delivery_zones
             ORDER BY zip_code ASC, id ASC'
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findDeliveryZoneById(int $zoneId): ?array
    {
        $zone = $this->app->database()->query(
            'SELECT id, zip_code, delivery_fee, is_active
             FROM delivery_zones
             WHERE id = :id
             LIMIT 1',
            ['id' => $zoneId]
        )->fetch();

        return is_array($zone) ? $zone : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createDeliveryZone(array $data): bool
    {
        return (new QueryBuilder($this->app->database()))->insert('delivery_zones', [
            'zip_code' => $data['zip_code'],
            'delivery_fee' => $data['delivery_fee'],
            'is_active' => $data['is_active'],
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateDeliveryZone(int $zoneId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('delivery_zones', [
            'zip_code' => $data['zip_code'],
            'delivery_fee' => $data['delivery_fee'],
            'is_active' => $data['is_active'],
        ], [
            'id' => $zoneId,
        ]);
    }

    public function deleteDeliveryZone(int $zoneId): void
    {
        (new QueryBuilder($this->app->database()))->delete('delivery_zones', [
            'id' => $zoneId,
        ]);
    }

    private function normalizeZip(string $zip): string
    {
        $normalized = preg_replace('/\D+/', '', $zip) ?? '';

        return substr($normalized, 0, 5);
    }

    private function normalizeTipAmount(string $tipAmount): string
    {
        $tipAmount = trim($tipAmount);

        if ($tipAmount === '') {
            return '0.00';
        }

        if (!is_numeric($tipAmount)) {
            return '';
        }

        return number_format(max(0, (float) $tipAmount), 2, '.', '');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findActiveDeliveryZoneByZip(string $zip): ?array
    {
        $zone = $this->app->database()->query(
            'SELECT id, zip_code, delivery_fee, is_active
             FROM delivery_zones
             WHERE zip_code = :zip_code AND is_active = 1
             LIMIT 1',
            ['zip_code' => $zip]
        )->fetch();

        return is_array($zone) ? $zone : null;
    }

    public function updateOrderStatus(int $orderId, string $status): void
    {
        if (!$this->isValidStatus($status)) {
            throw new \InvalidArgumentException('Invalid order status.');
        }

        (new QueryBuilder($this->app->database()))->update('orders', [
            'status' => $status,
            'status_updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $orderId,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updatePublicTracking(int $orderId, array $data): void
    {
        (new QueryBuilder($this->app->database()))->update('orders', [
            'tracking_status_label' => $data['tracking_status_label'],
            'tracking_public_note' => $data['tracking_public_note'],
            'status_updated_at' => date('Y-m-d H:i:s'),
        ], [
            'id' => $orderId,
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function normalizePublicTrackingInput(array $input): array
    {
        return [
            'tracking_status_label' => trim((string) ($input['tracking_status_label'] ?? '')),
            'tracking_public_note' => trim((string) ($input['tracking_public_note'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $input
     */
    public function validatePublicTrackingInput(array $input): ?string
    {
        if (mb_strlen($input['tracking_status_label']) > 190) {
            return 'Public tracking label must be 190 characters or fewer.';
        }

        return null;
    }

    public function synchronizeStatusForPayment(int $orderId, string $paymentStatus): ?string
    {
        $order = $this->findOrderById($orderId);

        if ($order === null) {
            throw new \InvalidArgumentException('Order not found.');
        }

        $currentStatus = (string) ($order['status'] ?? 'pending');

        if ($paymentStatus === 'paid' && $currentStatus === 'pending') {
            $this->updateOrderStatus($orderId, 'confirmed');

            return 'Order status changed from pending to confirmed because the payment was marked paid.';
        }

        if (in_array($paymentStatus, ['failed', 'cancelled'], true)) {
            return 'Order status was left unchanged for this placeholder payment outcome.';
        }

        return null;
    }

    /**
     * @param array<string, string> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildAdminOrderFilterClauses(array $filters): array
    {
        $clauses = [];
        $params = [];

        if ($filters['order_number'] !== '') {
            $clauses[] = 'o.order_number LIKE :order_number';
            $params['order_number'] = '%' . $filters['order_number'] . '%';
        }

        if ($filters['customer_name'] !== '') {
            $clauses[] = 'o.customer_name LIKE :customer_name';
            $params['customer_name'] = '%' . $filters['customer_name'] . '%';
        }

        if ($filters['recipient_name'] !== '') {
            $clauses[] = 'o.recipient_name LIKE :recipient_name';
            $params['recipient_name'] = '%' . $filters['recipient_name'] . '%';
        }

        if ($filters['customer_phone'] !== '') {
            $clauses[] = 'o.customer_phone LIKE :customer_phone';
            $params['customer_phone'] = '%' . $filters['customer_phone'] . '%';
        }

        if ($filters['status'] !== '') {
            $clauses[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }

        if ($filters['order_date_from'] !== '') {
            $clauses[] = 'DATE(o.created_at) >= :order_date_from';
            $params['order_date_from'] = $filters['order_date_from'];
        }

        if ($filters['order_date_to'] !== '') {
            $clauses[] = 'DATE(o.created_at) <= :order_date_to';
            $params['order_date_to'] = $filters['order_date_to'];
        }

        if ($filters['delivery_date_from'] !== '') {
            $clauses[] = 'o.delivery_date >= :delivery_date_from';
            $params['delivery_date_from'] = $filters['delivery_date_from'];
        }

        if ($filters['delivery_date_to'] !== '') {
            $clauses[] = 'o.delivery_date <= :delivery_date_to';
            $params['delivery_date_to'] = $filters['delivery_date_to'];
        }

        $quickFilter = (string) ($filters['quick_filter'] ?? '');
        $today = $this->operationsToday();

        if ($quickFilter === 'today') {
            $clauses[] = 'o.delivery_date = :quick_today';
            $params['quick_today'] = $today->format('Y-m-d');
        } elseif ($quickFilter === 'tomorrow') {
            $clauses[] = 'o.delivery_date = :quick_tomorrow';
            $params['quick_tomorrow'] = $today->modify('+1 day')->format('Y-m-d');
        } elseif ($quickFilter === 'weekend') {
            [$weekendStart, $weekendEnd] = $this->operationsWeekendWindow($today);
            $clauses[] = 'o.delivery_date BETWEEN :quick_weekend_start AND :quick_weekend_end';
            $params['quick_weekend_start'] = $weekendStart->format('Y-m-d');
            $params['quick_weekend_end'] = $weekendEnd->format('Y-m-d');
        } elseif ($quickFilter === 'next_7_days') {
            $clauses[] = 'o.delivery_date BETWEEN :quick_next_7_start AND :quick_next_7_end';
            $params['quick_next_7_start'] = $today->format('Y-m-d');
            $params['quick_next_7_end'] = $today->modify('+6 days')->format('Y-m-d');
        } elseif ($quickFilter === 'same_day_only') {
            $clauses[] = 'o.delivery_date IS NOT NULL AND DATE(o.created_at) = o.delivery_date';
        }

        if ($filters['delivery_type'] === 'same_day') {
            $clauses[] = 'o.delivery_date IS NOT NULL AND DATE(o.created_at) = o.delivery_date';
        } elseif ($filters['delivery_type'] === 'reminder_linked') {
            $clauses[] = 'COALESCE(rem.reminder_link_count, 0) > 0';
        } elseif ($filters['delivery_type'] === 'scheduled') {
            $clauses[] = 'COALESCE(rem.reminder_link_count, 0) = 0 AND (o.delivery_date IS NULL OR DATE(o.created_at) <> o.delivery_date)';
        }

        $whereSql = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);

        return [$whereSql, $params];
    }

    /**
     * @param array<string, mixed> $order
     */
    private function deriveOperationalDeliveryType(array $order): string
    {
        if (((int) ($order['reminder_link_count'] ?? 0)) > 0) {
            return 'reminder_linked';
        }

        if ($this->isSameDayOrder($order)) {
            return 'same_day';
        }

        return 'scheduled';
    }

    /**
     * @param array<string, mixed> $order
     */
    private function isSameDayOrder(array $order): bool
    {
        $deliveryDate = trim((string) ($order['delivery_date'] ?? ''));
        $createdAt = trim((string) ($order['created_at'] ?? ''));

        if ($deliveryDate === '' || $createdAt === '') {
            return false;
        }

        try {
            $created = new \DateTimeImmutable($createdAt, $this->operationsTimeZone());
        } catch (\Throwable $exception) {
            return false;
        }

        return $created->format('Y-m-d') === $deliveryDate;
    }

    private function deliveryTypeLabel(string $type): string
    {
        return match ($type) {
            'same_day' => 'Same Day',
            'reminder_linked' => 'Reminder-Linked',
            default => 'Scheduled',
        };
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'pending' => 'pending',
            'confirmed' => 'confirmed',
            'preparing' => 'preparing',
            'out_for_delivery' => 'out-for-delivery',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'neutral',
        };
    }

    private function operationsTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone('America/Chicago');
    }

    private function operationsToday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->operationsTimeZone());
    }

    private function operationsWeekEnd(\DateTimeImmutable $today): \DateTimeImmutable
    {
        return $today->modify('sunday this week');
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function operationsWeekendWindow(\DateTimeImmutable $today): array
    {
        $dayOfWeek = (int) $today->format('w');

        if ($dayOfWeek === 0) {
            return [$today, $today];
        }

        return [
            $today->modify('saturday this week'),
            $today->modify('sunday this week'),
        ];
    }

    private function isValidMonthString(string $month): bool
    {
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01', $this->operationsTimeZone());

        return $date instanceof \DateTimeImmutable && $date->format('Y-m') === $month;
    }

    private function isValidDateString(string $date): bool
    {
        if ($date === '') {
            return false;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date, $this->operationsTimeZone());

        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    /**
     * @param array<int, int> $values
     * @return array{0: string, 1: array<string, int>}
     */
    private function buildIntegerInClause(string $prefix, array $values): array
    {
        $placeholders = [];
        $params = [];

        foreach (array_values($values) as $index => $value) {
            $key = $prefix . '_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = (int) $value;
        }

        return [implode(', ', $placeholders), $params];
    }

    private function generateOrderNumber(): string
    {
        return 'SF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    private function generatePublicAccessToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @return array{customer_ip: string, user_agent: string}
     */
    private function captureRequestMetadata(): array
    {
        $customerIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

        if (filter_var($customerIp, FILTER_VALIDATE_IP) === false) {
            $customerIp = '';
        }

        return [
            'customer_ip' => substr($customerIp, 0, 45),
            'user_agent' => substr($userAgent, 0, 255),
        ];
    }

    private function defaultPublicTrackingLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Order received',
            'confirmed' => 'Order confirmed',
            'preparing' => 'Designing your arrangement',
            'out_for_delivery' => 'Out for delivery',
            'completed' => 'Delivered',
            'cancelled' => 'Order cancelled',
            default => 'Order update available',
        };
    }

    private function isDefaultPublicTrackingLabel(string $label): bool
    {
        foreach (self::ALLOWED_STATUSES as $status) {
            if (strcasecmp($label, $this->defaultPublicTrackingLabel($status)) === 0) {
                return true;
            }
        }

        return strcasecmp($label, $this->defaultPublicTrackingLabel('')) === 0;
    }
}
