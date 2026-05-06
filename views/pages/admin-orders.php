<?php
declare(strict_types=1);

$filters = is_array($filters ?? null) ? $filters : [];
$summary = is_array($summary ?? null) ? $summary : [];
$orders = is_array($orders ?? null) ? $orders : [];
$calendar = is_array($calendar ?? null) ? $calendar : [];
$selectedDayOrders = is_array($selectedDayOrders ?? null) ? $selectedDayOrders : [];
$selectedDay = (string) ($selectedDay ?? '');
$statusOptions = is_array($statusOptions ?? null) ? $statusOptions : [];
$deliveryTypeOptions = is_array($deliveryTypeOptions ?? null) ? $deliveryTypeOptions : [];

$buildOrdersQuery = static function (array $overrides = []) use ($filters): string {
    $query = array_merge($filters, $overrides);

    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }

    $queryString = http_build_query($query);

    return '/admin/orders' . ($queryString !== '' ? '?' . $queryString : '');
};

$formatDate = static function (?string $value, string $format = 'M j, Y'): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($value))->format($format);
    } catch (Throwable $exception) {
        return $value;
    }
};

$formatDateTime = static function (?string $value): string {
    $value = trim((string) $value);

    if ($value === '') {
        return '—';
    }

    try {
        return (new DateTimeImmutable($value))->format('M j, Y g:i A');
    } catch (Throwable $exception) {
        return $value;
    }
};

$humanizeStatus = static function (string $status): string {
    return ucwords(str_replace('_', ' ', $status));
};

$summaryCardTone = static function (array $card): string {
    $label = strtolower((string) ($card['label'] ?? ''));
    $status = (string) ($card['status'] ?? '');

    if (in_array($label, ['today', 'tomorrow', 'same day today'], true)) {
        return 'priority';
    }

    if ($label === 'weekend') {
        return 'weekend';
    }

    if ($label === 'this week') {
        return 'planning';
    }

    return match ($status) {
        'pending' => 'pending',
        'confirmed' => 'confirmed',
        'out_for_delivery' => 'out-for-delivery',
        default => 'neutral',
    };
};

$quickFilterOptions = [
    'today' => 'Today',
    'tomorrow' => 'Tomorrow',
    'weekend' => 'Weekend',
    'next_7_days' => 'Next 7 Days',
    'same_day_only' => 'Same Day',
];

$summaryCards = [
    ['label' => 'Today', 'value' => (int) ($summary['today'] ?? 0), 'quick_filter' => 'today'],
    ['label' => 'Tomorrow', 'value' => (int) ($summary['tomorrow'] ?? 0), 'quick_filter' => 'tomorrow'],
    ['label' => 'This Week', 'value' => (int) ($summary['this_week'] ?? 0), 'quick_filter' => 'next_7_days'],
    ['label' => 'Weekend', 'value' => (int) ($summary['weekend'] ?? 0), 'quick_filter' => 'weekend'],
    ['label' => 'Same Day Today', 'value' => (int) ($summary['same_day_today'] ?? 0), 'quick_filter' => 'same_day_only'],
    ['label' => 'Pending', 'value' => (int) ($summary['pending'] ?? 0), 'status' => 'pending'],
    ['label' => 'Confirmed', 'value' => (int) ($summary['confirmed'] ?? 0), 'status' => 'confirmed'],
    ['label' => 'Out for Delivery', 'value' => (int) ($summary['out_for_delivery'] ?? 0), 'status' => 'out_for_delivery'],
];

$weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>

<div class="admin-card">
    <p class="admin-kicker">Orders Management Console</p>
    <h2 class="admin-title">Delivery Planning</h2>
    <p class="admin-subtitle">Work from delivery date first, keep order date visible, and plan florist workload without opening every order one by one.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="admin-alert error"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="admin-alert success"><?php echo htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="orders-console-summary">
    <?php foreach ($summaryCards as $card): ?>
        <?php
        $cardHref = $buildOrdersQuery([
            'quick_filter' => $card['quick_filter'] ?? '',
            'status' => $card['status'] ?? '',
            'calendar_day' => '',
        ]);
        $cardClasses = [
            'orders-summary-card',
            'is-' . $summaryCardTone($card),
        ];
        ?>
        <a href="<?php echo htmlspecialchars($cardHref, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars(implode(' ', $cardClasses), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="orders-summary-card__eyebrow"><?php echo !empty($card['status']) ? 'Status' : 'Delivery'; ?></span>
            <span class="orders-summary-card__label"><?php echo htmlspecialchars((string) $card['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <strong class="orders-summary-card__value"><?php echo htmlspecialchars((string) $card['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <span class="orders-summary-card__hint"><?php echo !empty($card['status']) ? 'Focus this status queue' : 'Open filtered delivery workload'; ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <div class="admin-topbar orders-console-topbar orders-console-topbar--compact">
        <div>
            <p class="admin-kicker">Search & Filters</p>
            <h3 class="orders-console-heading">Find orders by delivery workload</h3>
            <p class="orders-console-subtitle">Use quick planning shortcuts first, then narrow with customer, recipient, status, and date ranges.</p>
        </div>
        <div class="orders-console-actions">
            <a href="/admin/orders" class="admin-button-secondary">Reset Filters</a>
        </div>
    </div>

    <div class="orders-quick-filters" aria-label="Quick delivery filters">
        <?php foreach ($quickFilterOptions as $quickFilterValue => $quickFilterLabel): ?>
            <?php
            $quickFilterClasses = ['orders-quick-filter'];
            if (($filters['quick_filter'] ?? '') === $quickFilterValue) {
                $quickFilterClasses[] = 'is-active';
            }
            ?>
            <a
                href="<?php echo htmlspecialchars($buildOrdersQuery(['quick_filter' => $quickFilterValue, 'calendar_day' => '']), ENT_QUOTES, 'UTF-8'); ?>"
                class="<?php echo htmlspecialchars(implode(' ', $quickFilterClasses), ENT_QUOTES, 'UTF-8'); ?>"
            >
                <?php echo htmlspecialchars($quickFilterLabel, ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="/admin/orders" class="orders-filters-grid">
        <div class="admin-field orders-filter-field orders-filter-field--primary">
            <label for="order_number">Order Number</label>
            <input id="order_number" name="order_number" type="text" value="<?php echo htmlspecialchars((string) ($filters['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="SF-2026...">
        </div>
        <div class="admin-field orders-filter-field orders-filter-field--primary">
            <label for="customer_name">Customer</label>
            <input id="customer_name" name="customer_name" type="text" value="<?php echo htmlspecialchars((string) ($filters['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Customer name">
        </div>
        <div class="admin-field orders-filter-field orders-filter-field--primary">
            <label for="recipient_name">Recipient</label>
            <input id="recipient_name" name="recipient_name" type="text" value="<?php echo htmlspecialchars((string) ($filters['recipient_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Recipient name">
        </div>
        <div class="admin-field orders-filter-field orders-filter-field--primary">
            <label for="customer_phone">Phone</label>
            <input id="customer_phone" name="customer_phone" type="text" value="<?php echo htmlspecialchars((string) ($filters['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Phone">
        </div>
        <div class="admin-field orders-filter-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statusOptions as $statusOption): ?>
                    <option value="<?php echo htmlspecialchars((string) $statusOption, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filters['status'] ?? '') === $statusOption ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($humanizeStatus((string) $statusOption), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field orders-filter-field">
            <label for="delivery_type">Delivery Type</label>
            <select id="delivery_type" name="delivery_type">
                <option value="">All delivery types</option>
                <?php foreach ($deliveryTypeOptions as $deliveryTypeValue => $deliveryTypeLabel): ?>
                    <option value="<?php echo htmlspecialchars((string) $deliveryTypeValue, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filters['delivery_type'] ?? '') === $deliveryTypeValue ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string) $deliveryTypeLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field orders-filter-field">
            <label for="order_date_from">Order Date From</label>
            <input id="order_date_from" name="order_date_from" type="date" value="<?php echo htmlspecialchars((string) ($filters['order_date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="admin-field orders-filter-field">
            <label for="order_date_to">Order Date To</label>
            <input id="order_date_to" name="order_date_to" type="date" value="<?php echo htmlspecialchars((string) ($filters['order_date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="admin-field orders-filter-field">
            <label for="delivery_date_from">Delivery Date From</label>
            <input id="delivery_date_from" name="delivery_date_from" type="date" value="<?php echo htmlspecialchars((string) ($filters['delivery_date_from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="admin-field orders-filter-field">
            <label for="delivery_date_to">Delivery Date To</label>
            <input id="delivery_date_to" name="delivery_date_to" type="date" value="<?php echo htmlspecialchars((string) ($filters['delivery_date_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="admin-field orders-filter-field">
            <label for="quick_filter">Quick Filter</label>
            <select id="quick_filter" name="quick_filter">
                <option value="">None</option>
                <option value="today" <?php echo ($filters['quick_filter'] ?? '') === 'today' ? 'selected' : ''; ?>>Today</option>
                <option value="tomorrow" <?php echo ($filters['quick_filter'] ?? '') === 'tomorrow' ? 'selected' : ''; ?>>Tomorrow</option>
                <option value="weekend" <?php echo ($filters['quick_filter'] ?? '') === 'weekend' ? 'selected' : ''; ?>>Weekend</option>
                <option value="next_7_days" <?php echo ($filters['quick_filter'] ?? '') === 'next_7_days' ? 'selected' : ''; ?>>Next 7 Days</option>
                <option value="same_day_only" <?php echo ($filters['quick_filter'] ?? '') === 'same_day_only' ? 'selected' : ''; ?>>Same Day Only</option>
            </select>
        </div>
        <input type="hidden" name="calendar_month" value="<?php echo htmlspecialchars((string) ($calendar['month'] ?? ($filters['calendar_month'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (($filters['calendar_day'] ?? '') !== ''): ?>
            <input type="hidden" name="calendar_day" value="<?php echo htmlspecialchars((string) ($filters['calendar_day'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
        <div class="orders-filters-actions">
            <button type="submit" class="admin-button">Apply Filters</button>
            <a href="/admin/orders" class="admin-button-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-topbar orders-console-topbar orders-console-topbar--compact">
        <div>
            <p class="admin-kicker">Calendar View</p>
            <h3 class="orders-console-heading"><?php echo htmlspecialchars((string) ($calendar['monthLabel'] ?? 'Month View'), ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="admin-subtitle">Calendar uses real delivery dates. Click a day to inspect that day’s orders.</p>
        </div>
        <div class="orders-calendar-nav">
            <a href="<?php echo htmlspecialchars($buildOrdersQuery(['calendar_month' => (string) ($calendar['previousMonth'] ?? '')]), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button-secondary">Previous</a>
            <a href="<?php echo htmlspecialchars($buildOrdersQuery(['calendar_month' => (string) ($calendar['nextMonth'] ?? '')]), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button-secondary">Next</a>
        </div>
    </div>

    <div class="orders-calendar-legend" aria-label="Order status legend">
        <span class="orders-calendar-legend__item"><span class="orders-calendar-legend__swatch status-confirmed"></span>Confirmed</span>
        <span class="orders-calendar-legend__item"><span class="orders-calendar-legend__swatch status-preparing"></span>Preparing</span>
        <span class="orders-calendar-legend__item"><span class="orders-calendar-legend__swatch status-out-for-delivery"></span>Out for Delivery</span>
        <span class="orders-calendar-legend__item"><span class="orders-calendar-legend__swatch status-pending"></span>Pending</span>
        <span class="orders-calendar-legend__item"><span class="orders-calendar-legend__swatch status-cancelled"></span>Cancelled</span>
    </div>

    <div class="orders-calendar">
        <?php foreach ($weekdayLabels as $weekdayLabel): ?>
            <div class="orders-calendar__weekday"><?php echo htmlspecialchars($weekdayLabel, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>

        <?php foreach (($calendar['weeks'] ?? []) as $week): ?>
            <?php foreach ($week as $day): ?>
                <?php
                $dayOrders = is_array($day['orders'] ?? null) ? $day['orders'] : [];
                $dayHref = $buildOrdersQuery([
                    'calendar_month' => (string) ($calendar['month'] ?? ''),
                    'calendar_day' => (string) ($day['date'] ?? ''),
                ]);
                $dayClasses = ['orders-calendar__day'];
                if (empty($day['isCurrentMonth'])) {
                    $dayClasses[] = 'is-outside-month';
                }
                if (!empty($day['isToday'])) {
                    $dayClasses[] = 'is-today';
                }
                if ($selectedDay !== '' && $selectedDay === (string) ($day['date'] ?? '')) {
                    $dayClasses[] = 'is-selected';
                }
                ?>
                <a href="<?php echo htmlspecialchars($dayHref, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo htmlspecialchars(implode(' ', $dayClasses), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="orders-calendar__day-head">
                        <span class="orders-calendar__day-number"><?php echo htmlspecialchars((string) ($day['day'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if ($dayOrders !== []): ?>
                            <span class="orders-calendar__day-count"><?php echo count($dayOrders); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="orders-calendar__markers">
                        <?php foreach (array_slice($dayOrders, 0, 3) as $dayOrder): ?>
                            <span class="orders-calendar__marker status-<?php echo htmlspecialchars((string) ($dayOrder['status_tone'] ?? 'neutral'), ENT_QUOTES, 'UTF-8'); ?>">
                                <strong><?php echo htmlspecialchars((string) ($dayOrder['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars((string) ($dayOrder['recipient_name'] ?? $dayOrder['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($dayOrder['is_same_day'])): ?>
                                    <em>Same Day</em>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($dayOrders) > 3): ?>
                            <span class="orders-calendar__more">+<?php echo count($dayOrders) - 3; ?> more</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($selectedDay !== ''): ?>
    <div class="admin-card">
        <div class="admin-topbar orders-console-topbar" style="margin-bottom:1.25rem;">
            <div>
                <p class="admin-kicker">Selected Day</p>
                <h3 class="orders-console-heading"><?php echo htmlspecialchars($formatDate($selectedDay), ENT_QUOTES, 'UTF-8'); ?></h3>
            </div>
            <a href="<?php echo htmlspecialchars($buildOrdersQuery(['calendar_day' => '']), ENT_QUOTES, 'UTF-8'); ?>" class="admin-button-secondary">Clear Day Focus</a>
        </div>
        <?php if ($selectedDayOrders === []): ?>
            <p class="admin-note">No delivery orders match the current filters for this day.</p>
        <?php else: ?>
            <div class="orders-day-focus">
                <?php foreach ($selectedDayOrders as $dayOrder): ?>
                    <div class="orders-day-focus__card">
                        <div>
                            <strong><?php echo htmlspecialchars((string) ($dayOrder['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($dayOrder['recipient_name'] ?? $dayOrder['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <div class="orders-day-focus__meta">
                            <span class="admin-status-pill is-<?php echo htmlspecialchars((string) ($dayOrder['status_tone'] ?? 'neutral'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($humanizeStatus((string) ($dayOrder['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><?php echo htmlspecialchars((string) ($dayOrder['delivery_time_slot'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="/admin/orders/view?id=<?php echo urlencode((string) ($dayOrder['id'] ?? '')); ?>" class="admin-text-button">Open</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="admin-table-wrap">
    <table class="admin-table orders-console-table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Recipient</th>
                <th>Order Date</th>
                <th>Delivery Date</th>
                <th>Delivery Window</th>
                <th>Delivery Type</th>
                <th>Status</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders === []): ?>
                <tr>
                    <td colspan="10">No orders match the current filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <?php $previewId = 'order-preview-' . (int) ($order['id'] ?? 0); ?>
                    <tr>
                        <td data-label="Order Number">
                            <strong><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($order['customer_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td data-label="Customer">
                            <?php echo htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($order['customer_phone'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td data-label="Recipient">
                            <?php echo htmlspecialchars((string) ($order['recipient_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?>
                            <div class="admin-note"><?php echo htmlspecialchars((string) ($order['delivery_zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                        </td>
                        <td data-label="Order Date"><?php echo htmlspecialchars($formatDateTime((string) ($order['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td data-label="Delivery Date"><?php echo htmlspecialchars($formatDate((string) ($order['delivery_date'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td data-label="Delivery Window"><?php echo htmlspecialchars((string) ($order['delivery_time_slot'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td data-label="Delivery Type">
                            <span class="orders-delivery-type"><?php echo htmlspecialchars((string) ($order['delivery_type_label'] ?? 'Scheduled'), ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($order['is_same_day']) && ($order['delivery_type'] ?? '') !== 'same_day'): ?>
                                <div class="orders-delivery-flag">Same Day</div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Status"><span class="admin-status-pill is-<?php echo htmlspecialchars((string) ($order['status_tone'] ?? 'neutral'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($humanizeStatus((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td data-label="Total">$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td data-label="Actions">
                            <div class="orders-console-actions">
                                <button type="button" class="admin-text-button orders-preview-toggle" data-preview-target="<?php echo htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8'); ?>">Preview</button>
                                <a href="/admin/orders/view?id=<?php echo urlencode((string) ($order['id'] ?? '')); ?>" class="admin-text-button">View</a>
                            </div>
                        </td>
                    </tr>
                    <tr id="<?php echo htmlspecialchars($previewId, ENT_QUOTES, 'UTF-8'); ?>" class="orders-preview-row" hidden>
                        <td colspan="10">
                            <div class="orders-preview">
                                <div class="orders-preview__header">
                                    <div>
                                        <p class="admin-kicker">Quick Preview</p>
                                <h4 class="orders-preview__title"><?php echo htmlspecialchars((string) ($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h4>
                            </div>
                            <span class="admin-status-pill is-<?php echo htmlspecialchars((string) ($order['status_tone'] ?? 'neutral'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($humanizeStatus((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                                <div class="orders-preview__grid">
                                    <div class="orders-preview__panel">
                                        <div class="orders-preview__panel-head">
                                            <p class="orders-preview__section-title">People & Timing</p>
                                        </div>
                                        <p class="orders-preview__label">Customer</p>
                                        <p class="orders-preview__text"><?php echo htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($order['customer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="orders-preview__label">Recipient</p>
                                        <p class="orders-preview__text"><?php echo htmlspecialchars((string) ($order['recipient_name'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="orders-preview__label">Order Date</p>
                                        <p class="orders-preview__text"><?php echo htmlspecialchars($formatDateTime((string) ($order['created_at'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="orders-preview__label">Delivery Date</p>
                                        <p class="orders-preview__text"><?php echo htmlspecialchars($formatDate((string) ($order['delivery_date'] ?? '')), ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars((string) ($order['delivery_time_slot'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                    <div class="orders-preview__panel">
                                        <div class="orders-preview__panel-head">
                                            <p class="orders-preview__section-title">Delivery & Total</p>
                                        </div>
                                        <p class="orders-preview__label">Address</p>
                                        <p class="orders-preview__text"><?php echo nl2br(htmlspecialchars(trim((string) ($order['delivery_address'] ?? '')), ENT_QUOTES, 'UTF-8')); ?></p>
                                        <p class="orders-preview__label">Card Message</p>
                                        <p class="orders-preview__text"><?php echo nl2br(htmlspecialchars(trim((string) ($order['card_message'] ?? '—')), ENT_QUOTES, 'UTF-8')); ?></p>
                                        <p class="orders-preview__label">Delivery Type</p>
                                        <p class="orders-preview__text"><?php echo htmlspecialchars((string) ($order['delivery_type_label'] ?? 'Scheduled'), ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p class="orders-preview__label">Total</p>
                                        <p class="orders-preview__text">$<?php echo htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                </div>
                                <div class="orders-preview__items">
                                    <div class="orders-preview__panel-head">
                                        <p class="orders-preview__section-title">Items</p>
                                    </div>
                                    <?php if (empty($order['preview_items'])): ?>
                                        <p class="orders-preview__text">No item data found for this order.</p>
                                    <?php else: ?>
                                        <?php foreach (($order['preview_items'] ?? []) as $item): ?>
                                            <div class="orders-preview__item">
                                                <div>
                                                    <strong><?php echo htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <div class="admin-note"><?php echo htmlspecialchars((string) ($item['variant_name'] ?? 'Standard'), ENT_QUOTES, 'UTF-8'); ?> · Qty <?php echo htmlspecialchars((string) ($item['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php foreach (($item['addons'] ?? []) as $addon): ?>
                                                        <div class="admin-note">Add-on: <?php echo htmlspecialchars((string) ($addon['addon_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> x<?php echo htmlspecialchars((string) ($addon['quantity'] ?? 1), ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="orders-preview__item-total">$<?php echo htmlspecialchars(number_format((float) ($item['line_total'] ?? 0), 2), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.orders-preview-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-preview-target');
            if (!targetId) {
                return;
            }

            const row = document.getElementById(targetId);
            if (!row) {
                return;
            }

            const isHidden = row.hasAttribute('hidden');
            document.querySelectorAll('.orders-preview-row').forEach((previewRow) => {
                previewRow.setAttribute('hidden', 'hidden');
            });

            if (isHidden) {
                row.removeAttribute('hidden');
            }
        });
    });
});
</script>
