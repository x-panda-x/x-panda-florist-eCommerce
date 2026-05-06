<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class NotificationService
{
    private const DELIVERY_MODE_LOG_ONLY = 'log_only';
    private const DELIVERY_MODE_PHP_MAIL = 'php_mail';
    private const DELIVERY_MODE_SMTP = 'smtp';
    private const LOG_FILE = 'storage/logs/notifications.log';

    private Application $app;
    private SettingsService $settingsService;
    private OrderService $orderService;
    private PaymentService $paymentService;
    private CustomerService $customerService;
    private CustomerReminderService $customerReminderService;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->settingsService = new SettingsService($app);
        $this->orderService = new OrderService($app);
        $this->paymentService = new PaymentService($app);
        $this->customerService = new CustomerService($app);
        $this->customerReminderService = new CustomerReminderService($app);
    }

    /**
     * @return array{delivery_mode: string, customer_notification: string, store_notification: string}
     */
    public function sendOrderCreatedNotifications(int $orderId): array
    {
        return $this->sendOrderConfirmationNotifications($orderId);
    }

    /**
     * @return array{delivery_mode: string, customer_notification: string, store_notification: string}
     */
    public function sendOrderConfirmationNotifications(int $orderId): array
    {
        $order = $this->orderService->findOrderById($orderId);

        if ($order === null) {
            throw new \InvalidArgumentException('Order not found.');
        }

        $items = $this->orderService->listItemsByOrderId($orderId);
        $payment = $this->paymentService->findLatestPaymentByOrderId($orderId);
        $storeName = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $storeEmail = trim((string) $this->settingsService->get('store_email', 'lilyandrose76@gmail.com'));
        $deliveryMode = $this->deliveryMode();
        $customer = $this->resolveCustomerForOrder($order);
        $customerNotification = $this->shouldSendOrderEmail($customer)
            ? $this->deliverMessage(
                $this->buildCustomerMessage($order, $items, $payment, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            )
            : 'skipped_customer_opt_out';

        return [
            'delivery_mode' => $deliveryMode,
            'customer_notification' => $customerNotification,
            'store_notification' => $this->deliverMessage(
                $this->buildStoreMessage($order, $items, $payment, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @return array{delivery_mode: string, customer_notification: string}
     */
    public function sendCustomerWelcomeNotification(array $customer): array
    {
        $storeName = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $storeEmail = trim((string) $this->settingsService->get('store_email', 'lilyandrose76@gmail.com'));
        $deliveryMode = $this->deliveryMode();

        return [
            'delivery_mode' => $deliveryMode,
            'customer_notification' => $this->deliverMessage(
                $this->buildCustomerWelcomeMessage($customer, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @return array{delivery_mode: string, customer_notification: string}
     */
    public function sendCustomerPasswordResetNotification(array $customer, string $resetUrl): array
    {
        $storeName = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $storeEmail = trim((string) $this->settingsService->get('store_email', 'lilyandrose76@gmail.com'));
        $deliveryMode = $this->deliveryMode();

        return [
            'delivery_mode' => $deliveryMode,
            'customer_notification' => $this->deliverMessage(
                $this->buildCustomerPasswordResetMessage($customer, $resetUrl, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            ),
        ];
    }

    /**
     * @return array{delivery_mode: string, customer_notification: string}
     */
    public function sendReminderConfirmationNotification(int $reminderId): array
    {
        $reminder = $this->customerReminderService->findById($reminderId);

        if ($reminder === null) {
            throw new \InvalidArgumentException('Reminder not found.');
        }

        $customer = $this->customerService->findById((int) ($reminder['customer_id'] ?? 0));

        if ($customer === null) {
            throw new \InvalidArgumentException('Reminder customer not found.');
        }

        $storeName = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $storeEmail = trim((string) $this->settingsService->get('store_email', 'lilyandrose76@gmail.com'));
        $deliveryMode = $this->deliveryMode();
        $customerNotification = !empty($customer['reminder_email_opt_in'])
            ? $this->deliverMessage(
                $this->buildReminderConfirmationMessage($customer, $reminder, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            )
            : 'skipped_customer_opt_out';

        return [
            'delivery_mode' => $deliveryMode,
            'customer_notification' => $customerNotification,
        ];
    }

    /**
     * @return array{delivery_mode: string, customer_notification: string}
     */
    public function sendUpcomingReminderNotification(int $reminderId): array
    {
        $reminder = $this->customerReminderService->findById($reminderId);

        if ($reminder === null) {
            throw new \InvalidArgumentException('Reminder not found.');
        }

        $customer = $this->customerService->findById((int) ($reminder['customer_id'] ?? 0));

        if ($customer === null) {
            throw new \InvalidArgumentException('Reminder customer not found.');
        }

        $storeName = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $storeEmail = trim((string) $this->settingsService->get('store_email', 'lilyandrose76@gmail.com'));
        $deliveryMode = $this->deliveryMode();
        $customerNotification = !empty($customer['reminder_email_opt_in'])
            ? $this->deliverMessage(
                $this->buildUpcomingReminderMessage($customer, $reminder, $storeName, $storeEmail),
                $deliveryMode,
                $storeEmail
            )
            : 'skipped_customer_opt_out';

        return [
            'delivery_mode' => $deliveryMode,
            'customer_notification' => $customerNotification,
        ];
    }

    /**
     * @return array{delivery_mode: string, due: int, sent: int, expiring_soon: int, expired: int, results: array<int, array<string, mixed>>}
     */
    public function processReminderLifecycle(?\DateTimeImmutable $referenceTime = null): array
    {
        $timezone = new \DateTimeZone('America/Chicago');
        $now = $referenceTime instanceof \DateTimeImmutable
            ? $referenceTime->setTimezone($timezone)
            : new \DateTimeImmutable('now', $timezone);
        $targetDate = $now->modify('+' . CustomerReminderService::UPCOMING_NOTICE_DAYS . ' days');
        $deliveryMode = $this->deliveryMode();
        $dueReminders = $this->customerReminderService->listUpcomingReminderNotifications($targetDate, $now);
        $sent = 0;
        $results = [];

        foreach ($dueReminders as $reminder) {
            $reminderId = (int) ($reminder['id'] ?? 0);

            if ($reminderId <= 0) {
                continue;
            }

            $notification = $this->sendUpcomingReminderNotification($reminderId);
            $this->customerReminderService->markUpcomingReminderSent($reminderId, $now);
            $results[] = [
                'reminder_id' => $reminderId,
                'status' => (string) ($reminder['status'] ?? ''),
                'customer_notification' => $notification['customer_notification'],
            ];
            $sent++;
        }

        $lifecycle = $this->customerReminderService->advanceReminderLifecycle($now);

        return [
            'delivery_mode' => $deliveryMode,
            'due' => count($dueReminders),
            'sent' => $sent,
            'expiring_soon' => (int) ($lifecycle['expiring_soon'] ?? 0),
            'expired' => (int) ($lifecycle['expired'] ?? 0),
            'results' => $results,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function allowedDeliveryModes(): array
    {
        return [
            self::DELIVERY_MODE_LOG_ONLY,
            self::DELIVERY_MODE_PHP_MAIL,
            self::DELIVERY_MODE_SMTP,
        ];
    }

    public function deliveryMode(): string
    {
        $mode = trim((string) $this->settingsService->get('email_delivery_mode', self::DELIVERY_MODE_LOG_ONLY));

        if (!in_array($mode, $this->allowedDeliveryModes(), true)) {
            return self::DELIVERY_MODE_LOG_ONLY;
        }

        return $mode;
    }

    public function campaignPreviewHtml(
        string $subject,
        string $preheader,
        string $messageBody,
        string $ctaLabel = '',
        string $ctaUrl = ''
    ): string {
        $details = $preheader !== '' ? ['Preview' => $preheader] : [];
        return $this->brandedEmailHtml(
            $subject !== '' ? $subject : 'Lily and Rose Update',
            trim($messageBody) !== '' ? trim($messageBody) : 'We have a new update for you.',
            $details,
            $ctaLabel,
            $ctaUrl,
            false
        );
    }

    public function sendCampaignEmail(
        string $recipient,
        string $subject,
        string $preheader,
        string $messageBody,
        string $ctaLabel = '',
        string $ctaUrl = ''
    ): string {
        $brand = $this->emailBrandSettings();
        $prefsUrl = rtrim($brand['website_url'], '/') . '/account/email-preferences';
        $text = trim($messageBody) . "\n\n";
        if ($preheader !== '') {
            $text .= "Preview: " . trim($preheader) . "\n\n";
        }
        if ($ctaLabel !== '' && $ctaUrl !== '') {
            $text .= $ctaLabel . ': ' . $ctaUrl . "\n\n";
        }
        $text .= 'Manage Email Preferences: ' . $prefsUrl . "\n";
        $html = $this->campaignPreviewHtml($subject, $preheader, $messageBody, $ctaLabel, $ctaUrl)
            . '<div style="max-width:620px;margin:0 auto 20px;padding:0 24px;color:#6b7280;font-size:12px;">'
            . '<p style="margin:0;">Manage preferences: <a href="' . htmlspecialchars($prefsUrl, ENT_QUOTES, 'UTF-8') . '">'
            . htmlspecialchars($prefsUrl, ENT_QUOTES, 'UTF-8') . '</a></p></div>';

        return $this->deliverMessage([
            'type' => 'customer_campaign',
            'recipient' => trim($recipient),
            'subject' => trim($subject),
            'body' => $text,
            'html_body' => $html,
        ], $this->deliveryMode(), $brand['contact_email']);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>|null $payment
     * @return array<string, string>
     */
    private function buildCustomerMessage(array $order, array $items, ?array $payment, string $storeName, string $storeEmail): array
    {
        $emailSettings = $this->emailBrandSettings();
        $orderNumber = (string) ($order['order_number'] ?? '');
        $subject = sprintf(
            '%s order confirmation %s',
            $emailSettings['brand_name'],
            $orderNumber
        );
        $ctaUrl = $this->orderConfirmationPath($order);

        return [
            'type' => 'customer_order_confirmation',
            'recipient' => trim((string) ($order['customer_email'] ?? '')),
            'subject' => $subject,
            'body' => $this->buildPlainTextBody(
                'Customer Order Confirmation',
                $order,
                $items,
                $payment,
                $storeName,
                $storeEmail
            ),
            'html_body' => $this->brandedEmailHtml(
                'Order Confirmed',
                'Thank you for your order. We received order ' . $orderNumber . '.',
                [
                    'Order Number' => $orderNumber,
                    'Delivery Date' => trim((string) ($order['delivery_date'] ?? '')),
                    'Delivery Time' => trim((string) ($order['delivery_time_slot'] ?? '')),
                    'Recipient' => trim((string) ($order['recipient_name'] ?? '')),
                    'Total' => '$' . number_format((float) ($order['total_amount'] ?? 0), 2),
                ],
                'View Order Status',
                $ctaUrl,
                false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>|null $payment
     * @return array<string, string>
     */
    private function buildStoreMessage(array $order, array $items, ?array $payment, string $storeName, string $storeEmail): array
    {
        $subject = sprintf('New order received %s', (string) ($order['order_number'] ?? ''));
        $emailSettings = $this->emailBrandSettings();

        return [
            'type' => 'store_new_order_notification',
            'recipient' => $storeEmail,
            'subject' => $subject,
            'body' => $this->buildPlainTextBody(
                'Store New Order Notification',
                $order,
                $items,
                $payment,
                $storeName,
                $storeEmail
            ),
            'html_body' => $this->brandedEmailHtml(
                'New Store Order',
                'A new customer order is ready for review.',
                [
                    'Order Number' => trim((string) ($order['order_number'] ?? '')),
                    'Customer' => trim((string) ($order['customer_name'] ?? '')),
                    'Customer Email' => trim((string) ($order['customer_email'] ?? '')),
                    'Recipient' => trim((string) ($order['recipient_name'] ?? '')),
                    'Delivery Date' => trim((string) ($order['delivery_date'] ?? '')),
                    'Total' => '$' . number_format((float) ($order['total_amount'] ?? 0), 2),
                ],
                'Open Admin Orders',
                rtrim($emailSettings['website_url'], '/') . '/admin/orders',
                true
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<string, string>
     */
    private function buildCustomerPasswordResetMessage(array $customer, string $resetUrl, string $storeName, string $storeEmail): array
    {
        $storeLabel = $this->emailBrandSettings()['brand_name'];

        return [
            'type' => 'customer_password_reset',
            'recipient' => trim((string) ($customer['email'] ?? '')),
            'subject' => $storeLabel . ' password reset',
            'body' => implode("\n", [
                'Customer Password Reset',
                '',
                'Store: ' . $storeLabel,
                'Customer: ' . trim((string) ($customer['full_name'] ?? '')),
                'Email: ' . trim((string) ($customer['email'] ?? '')),
                '',
                'A password reset was requested for this customer account.',
                'Use the link below to choose a new password. This link expires in 60 minutes and can only be used once.',
                '',
                'Reset Link: ' . $resetUrl,
                '',
                'If you did not request this reset, you can ignore this message.',
                $storeEmail !== '' ? 'Store Contact: ' . $storeEmail : '',
            ]) . "\n",
            'html_body' => $this->brandedEmailHtml(
                'Reset Your Password',
                'We received a request to reset your account password.',
                [
                    'Customer' => trim((string) ($customer['full_name'] ?? '')),
                    'Email' => trim((string) ($customer['email'] ?? '')),
                    'Security Notice' => 'Ignore this email if you did not request a reset.',
                ],
                'Reset Password',
                $resetUrl,
                false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @return array<string, string>
     */
    private function buildCustomerWelcomeMessage(array $customer, string $storeName, string $storeEmail): array
    {
        $storeLabel = $this->emailBrandSettings()['brand_name'];
        $loginUrl = public_url('/account/login');
        $resetUrl = public_url('/account/forgot-password');

        return [
            'type' => 'customer_welcome',
            'recipient' => trim((string) ($customer['email'] ?? '')),
            'subject' => $storeLabel . ' account created',
            'body' => implode("\n", [
                'Customer Account Confirmation',
                '',
                'Store: ' . $storeLabel,
                'Customer: ' . trim((string) ($customer['full_name'] ?? '')),
                'Email: ' . trim((string) ($customer['email'] ?? '')),
                '',
                'Your account has been created successfully.',
                'For security, passwords are never sent by email.',
                'Sign in here: ' . $loginUrl,
                'If you ever need to set a new password, use: ' . $resetUrl,
                '',
                'You can now review orders, manage reminders, and update account details from your customer dashboard.',
                $storeEmail !== '' ? 'Store Contact: ' . $storeEmail : '',
            ]) . "\n",
            'html_body' => $this->brandedEmailHtml(
                'Welcome To ' . $storeLabel,
                'Your account is ready. You can track orders and manage reminders anytime.',
                [
                    'Customer' => trim((string) ($customer['full_name'] ?? '')),
                    'Email' => trim((string) ($customer['email'] ?? '')),
                    'Quick Access' => 'Use your dashboard for orders, reminders, and account updates.',
                ],
                'Sign In',
                $loginUrl,
                false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $reminder
     * @return array<string, string>
     */
    private function buildReminderConfirmationMessage(array $customer, array $reminder, string $storeName, string $storeEmail): array
    {
        $storeLabel = $this->emailBrandSettings()['brand_name'];

        return [
            'type' => 'customer_reminder_confirmation',
            'recipient' => trim((string) ($customer['email'] ?? '')),
            'subject' => sprintf('%s reminder confirmed for %s', $storeLabel, (string) ($reminder['reminder_date'] ?? '')),
            'body' => implode("\n", [
                'Reminder Confirmation',
                '',
                'Store: ' . $storeLabel,
                'Customer: ' . trim((string) ($customer['full_name'] ?? '')),
                'Reminder Date: ' . trim((string) ($reminder['reminder_date'] ?? '')),
                'Occasion: ' . trim((string) ($reminder['occasion_label'] ?? '')),
                'Recipient: ' . trim((string) ($reminder['recipient_name'] ?? '')),
                'Product: ' . trim((string) ($reminder['product_name'] ?? '')),
                'Order Reference: ' . trim((string) ($reminder['order_number'] ?? '')),
                'Reminder Status: ' . trim((string) ($reminder['status_label'] ?? 'Purchased')),
                !empty($reminder['note']) ? 'Note: ' . trim((string) ($reminder['note'] ?? '')) : '',
                '',
                'Your reminder is saved and a paid order is already linked to it.',
                'You are all set for this occasion unless you need to update the reminder details with the shop.',
                $storeEmail !== '' ? 'Store Contact: ' . $storeEmail : '',
            ]) . "\n",
            'html_body' => $this->brandedEmailHtml(
                'Reminder Confirmed',
                'Your reminder has been saved successfully.',
                [
                    'Reminder Date' => trim((string) ($reminder['reminder_date'] ?? '')),
                    'Occasion' => trim((string) ($reminder['occasion_label'] ?? '')),
                    'Recipient' => trim((string) ($reminder['recipient_name'] ?? '')),
                    'Linked Order' => trim((string) ($reminder['order_number'] ?? 'Not linked')),
                ],
                'Open My Account',
                public_url('/account/reminders'),
                false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $customer
     * @param array<string, mixed> $reminder
     * @return array<string, string>
     */
    private function buildUpcomingReminderMessage(array $customer, array $reminder, string $storeName, string $storeEmail): array
    {
        $storeLabel = $this->emailBrandSettings()['brand_name'];
        $hasPurchase = !empty($reminder['order_id']);
        $actionWindow = (int) ($reminder['action_window_hours'] ?? CustomerReminderService::ACTION_WINDOW_HOURS);
        $subject = $hasPurchase
            ? sprintf('%s reminder coming up for %s', $storeLabel, (string) ($reminder['reminder_date'] ?? ''))
            : sprintf('%s reminder needs your order for %s', $storeLabel, (string) ($reminder['reminder_date'] ?? ''));
        $lines = [
            'Upcoming Reminder',
            '',
            'Store: ' . $storeLabel,
            'Customer: ' . trim((string) ($customer['full_name'] ?? '')),
            'Reminder Date: ' . trim((string) ($reminder['reminder_date'] ?? '')),
            'Occasion: ' . trim((string) ($reminder['occasion_label'] ?? '')),
            'Recipient: ' . trim((string) ($reminder['recipient_name'] ?? '')),
            'Reminder Status: ' . trim((string) ($reminder['status_label'] ?? '')),
            !empty($reminder['product_name']) ? 'Product: ' . trim((string) ($reminder['product_name'] ?? '')) : '',
            !empty($reminder['order_number']) ? 'Order Reference: ' . trim((string) ($reminder['order_number'] ?? '')) : '',
            '',
        ];

        if ($hasPurchase) {
            $lines[] = 'This reminder already has a completed purchase linked to it.';
            $lines[] = 'Your order is already scheduled for the occasion shown above.';
        } else {
            $lines[] = 'This reminder is coming up and no purchase has been completed yet.';
            $lines[] = 'Please complete your order soon to keep this reminder actionable.';
            $lines[] = 'If no purchase is completed within the next ' . $actionWindow . ' hours, this reminder will move toward expiry.';
        }

        if ($storeEmail !== '') {
            $lines[] = 'Store Contact: ' . $storeEmail;
        }

        return [
            'type' => 'customer_reminder_upcoming',
            'recipient' => trim((string) ($customer['email'] ?? '')),
            'subject' => $subject,
            'body' => implode("\n", array_filter($lines, static fn ($line): bool => $line !== '')) . "\n",
            'html_body' => $this->brandedEmailHtml(
                'Reminder Coming Soon',
                $hasPurchase
                    ? 'Your reminder already has a linked paid order.'
                    : 'Your reminder is coming soon and no purchase is linked yet.',
                [
                    'Reminder Date' => trim((string) ($reminder['reminder_date'] ?? '')),
                    'Occasion' => trim((string) ($reminder['occasion_label'] ?? '')),
                    'Recipient' => trim((string) ($reminder['recipient_name'] ?? '')),
                    'Status' => trim((string) ($reminder['status_label'] ?? '')),
                ],
                $hasPurchase ? 'View Reminder' : 'Shop Now',
                $hasPurchase ? public_url('/account/reminders') : public_url('/same-day'),
                false
            ),
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed>|null $payment
     */
    private function buildPlainTextBody(
        string $heading,
        array $order,
        array $items,
        ?array $payment,
        string $storeName,
        string $storeEmail
    ): string {
        $lines = [
            $heading,
            '',
            'Store: ' . ($storeName !== '' ? $storeName : 'Lily and Rose'),
            'Order Number: ' . (string) ($order['order_number'] ?? ''),
            'Order Status: ' . (string) ($order['status'] ?? 'pending'),
            'Customer Name: ' . (string) ($order['customer_name'] ?? ''),
            'Customer Email: ' . (string) ($order['customer_email'] ?? ''),
            'Customer Phone: ' . (string) ($order['customer_phone'] ?? ''),
            'Recipient Name: ' . (string) ($order['recipient_name'] ?? ''),
            'Delivery Address: ' . preg_replace('/\s+/', ' ', trim((string) ($order['delivery_address'] ?? ''))),
            'Delivery ZIP: ' . (string) ($order['delivery_zip'] ?? ''),
            'Delivery Date: ' . (string) ($order['delivery_date'] ?? ''),
            'Delivery Time Slot: ' . (string) ($order['delivery_time_slot'] ?? ''),
            'Delivery Instructions: ' . preg_replace('/\s+/', ' ', trim((string) ($order['delivery_instructions'] ?? ''))),
            'Card Message: ' . preg_replace('/\s+/', ' ', trim((string) ($order['card_message'] ?? ''))),
            '',
            'Order Totals',
            'Subtotal: $' . number_format((float) ($order['subtotal'] ?? 0), 2),
        ];

        if (!empty($order['promo_code']) && (float) ($order['promo_discount_amount'] ?? 0) > 0) {
            $lines[] = 'Promo Code: ' . (string) ($order['promo_code'] ?? '');
            $lines[] = 'Promo Discount: -$' . number_format((float) ($order['promo_discount_amount'] ?? 0), 2);
        }

        $lines = array_merge($lines, [
            'Delivery Fee: $' . number_format((float) ($order['delivery_fee'] ?? 0), 2),
            'Tax: $' . number_format((float) ($order['tax_amount'] ?? 0), 2),
            'Tip: $' . number_format((float) ($order['tip_amount'] ?? 0), 2),
            'Total: $' . number_format((float) ($order['total_amount'] ?? 0), 2),
            '',
            'Payment Snapshot',
            'Payment Reference: ' . (string) ($payment['payment_reference'] ?? 'Not available'),
            'Payment Status: ' . (string) ($payment['status'] ?? 'pending'),
            'Payment Amount: ' . (string) ($payment['currency'] ?? 'USD') . ' ' . number_format((float) ($payment['amount'] ?? 0), 2),
            'Provider: ' . (string) ($payment['provider_name'] ?? 'placeholder'),
        ]);

        if (!empty($payment['provider_reference'])) {
            $lines[] = 'Provider Reference: ' . (string) $payment['provider_reference'];
        }

        if (!empty($payment['failure_message'])) {
            $lines[] = 'Payment Note: ' . preg_replace('/\s+/', ' ', trim((string) $payment['failure_message']));
        }

        $lines[] = '';
        $lines[] = 'Items';

        foreach ($items as $item) {
            $variant = trim((string) ($item['variant_name'] ?? ''));
            $line = '- ' . (string) ($item['product_name'] ?? '') . ' x' . (string) ($item['quantity'] ?? 0);

            if ($variant !== '') {
                $line .= ' [' . $variant . ']';
            }

            $line .= ' $' . number_format((float) ($item['line_total'] ?? 0), 2);
            $lines[] = $line;

            foreach (($item['addons'] ?? []) as $addon) {
                if (!is_array($addon)) {
                    continue;
                }

                $lines[] = '  * Add-on: '
                    . (string) ($addon['addon_name'] ?? '')
                    . ' x' . (string) ($addon['quantity'] ?? 0)
                    . ' $' . number_format((float) ($addon['line_total'] ?? 0), 2);
            }
        }

        $lines[] = '';
        $lines[] = 'Payment remains placeholder-only until the real custom payment API is integrated.';
        $lines[] = 'Payment Page: ' . $this->paymentPath($order, $payment);
        $lines[] = 'Order Confirmation Page: ' . $this->orderConfirmationPath($order);

        if ($storeEmail !== '') {
            $lines[] = 'Store Contact: ' . $storeEmail;
        }

        return implode("\n", array_values(array_filter($lines, static fn ($line): bool => $line !== ''))) . "\n";
    }

    /**
     * @param array<string, string> $message
     */
    private function deliverMessage(array $message, string $deliveryMode, string $storeEmail): string
    {
        $recipient = trim((string) ($message['recipient'] ?? ''));
        $subject = (string) ($message['subject'] ?? '');
        $body = (string) ($message['body'] ?? '');
        $htmlBody = trim((string) ($message['html_body'] ?? ''));
        $hasHtmlBody = $htmlBody !== '';
        $type = (string) ($message['type'] ?? 'notification');

        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->logMessage($type, $recipient, $subject, $body, $deliveryMode, 'skipped_invalid_recipient');

            return 'skipped_invalid_recipient';
        }

        if ($deliveryMode === self::DELIVERY_MODE_PHP_MAIL && $this->canUsePhpMail($storeEmail)) {
            $sender = $this->phpMailSenderProfile($storeEmail);
            $boundary = '=_LR_' . bin2hex(random_bytes(8));
            $headers = $this->mailHeaders($sender, $hasHtmlBody, $boundary);
            $mailBody = $hasHtmlBody ? $this->multipartMailBody($body, $htmlBody, $boundary) : $body;
            $sent = mail($recipient, $subject, $mailBody, $headers, '-f' . $sender['from_email']);

            if (!$sent) {
                // Fallback for hosts that reject envelope sender flags for specific flows.
                $sent = mail($recipient, $subject, $mailBody, $headers);
            }

            $result = $sent ? 'sent_php_mail' : 'php_mail_failed';

            if (!$sent) {
                $smtpResult = $this->deliverViaSmtp($message, $storeEmail);
                if ($smtpResult === 'sent_smtp') {
                    $result = $smtpResult;
                }
            }

            $this->logMessage($type, $recipient, $subject, $body, $deliveryMode, $result);

            return $result;
        }

        if ($deliveryMode === self::DELIVERY_MODE_SMTP) {
            $result = $this->deliverViaSmtp($message, $storeEmail);
            $this->logMessage($type, $recipient, $subject, $body, $deliveryMode, $result);

            return $result;
        }

        $result = $deliveryMode === self::DELIVERY_MODE_PHP_MAIL ? 'logged_php_mail_not_available' : 'logged_only';
        $this->logMessage($type, $recipient, $subject, $body, $deliveryMode, $result);

        return $result;
    }

    private function canUsePhpMail(string $storeEmail): bool
    {
        $sender = $this->phpMailSenderProfile($storeEmail);

        return $sender['from_email'] !== ''
            && filter_var($sender['from_email'], FILTER_VALIDATE_EMAIL) !== false
            && function_exists('mail');
    }

    /**
     * @param array{from_email: string, reply_to: string} $sender
     */
    private function mailHeaders(array $sender, bool $hasHtmlBody, string $boundary): string
    {
        $fromDisplay = trim((string) $sender['from_name']) !== ''
            ? '"' . str_replace('"', '\"', (string) $sender['from_name']) . '" <' . $sender['from_email'] . '>'
            : $sender['from_email'];
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: ' . ($hasHtmlBody ? 'multipart/alternative; boundary="' . $boundary . '"' : 'text/plain; charset=UTF-8'),
            'From: ' . $fromDisplay,
        ];

        if ($sender['reply_to'] !== '') {
            $headers[] = 'Reply-To: ' . $sender['reply_to'];
        }

        return implode("\r\n", $headers);
    }

    /**
     * @return array{from_email: string, reply_to: string}
     */
    private function phpMailSenderProfile(string $storeEmail): array
    {
        $emailSettings = $this->emailBrandSettings();
        $configuredReplyTo = trim((string) $emailSettings['reply_to_email']);
        $replyTo = filter_var($configuredReplyTo, FILTER_VALIDATE_EMAIL) !== false
            ? $configuredReplyTo
            : (filter_var($storeEmail, FILTER_VALIDATE_EMAIL) !== false ? $storeEmail : '');
        $host = parse_url($this->publicBaseUrl(), PHP_URL_HOST);
        $host = is_string($host) ? strtolower(trim($host)) : '';
        $host = preg_replace('/^www\./', '', $host) ?? '';
        $fromEmail = '';

        if ($replyTo !== '' && $host !== '') {
            $replyDomain = strtolower((string) substr(strrchr($replyTo, '@') ?: '', 1));

            if ($replyDomain === $host) {
                $fromEmail = $replyTo;
            }
        }

        if ($fromEmail === '' && $host !== '') {
            $candidate = 'no-reply@' . $host;

            if (filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
                $fromEmail = $candidate;
            }
        }

        if ($fromEmail === '' && $replyTo !== '') {
            $fromEmail = $replyTo;
        }

        return [
            'from_email' => $fromEmail,
            'reply_to' => $replyTo,
            'from_name' => $emailSettings['sender_name'],
        ];
    }

    /**
     * @param array<string, mixed> $order
     * @return array<string, mixed>|null
     */
    private function resolveCustomerForOrder(array $order): ?array
    {
        $customerId = (int) ($order['customer_id'] ?? 0);

        if ($customerId > 0) {
            return $this->customerService->findById($customerId);
        }

        $customerEmail = trim((string) ($order['customer_email'] ?? ''));

        if ($customerEmail === '') {
            return null;
        }

        return $this->customerService->findByEmail($customerEmail);
    }

    /**
     * @param array<string, mixed>|null $customer
     */
    private function shouldSendOrderEmail(?array $customer): bool
    {
        if ($customer === null) {
            return true;
        }

        return !array_key_exists('order_email_opt_in', $customer)
            || !empty($customer['order_email_opt_in']);
    }

    /**
     * @param array<string, string> $message
     */
    private function deliverViaSmtp(array $message, string $storeEmail): string
    {
        $config = $this->smtpConfig($storeEmail);

        if (!$config['is_configured']) {
            return 'logged_smtp_config_missing';
        }

        $transport = $config['encryption'] === 'ssl'
            ? 'ssl://' . $config['host'] . ':' . $config['port']
            : 'tcp://' . $config['host'] . ':' . $config['port'];

        $socket = @stream_socket_client($transport, $errno, $error, 15, STREAM_CLIENT_CONNECT);

        if (!is_resource($socket)) {
            return 'logged_smtp_connect_failed';
        }

        stream_set_timeout($socket, 15);

        try {
            $this->smtpExpect($socket, [220]);
            $this->smtpCommand($socket, 'EHLO lily-and-rose.local', [250]);

            if ($config['encryption'] === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);

                if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                    throw new \RuntimeException('Unable to start TLS.');
                }

                $this->smtpCommand($socket, 'EHLO lily-and-rose.local', [250]);
            }

            if ($config['auth_required']) {
                $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
                $this->smtpCommand($socket, base64_encode($config['username']), [334]);
                $this->smtpCommand($socket, base64_encode($config['password']), [235]);
            }

            $this->smtpCommand($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . trim((string) ($message['recipient'] ?? '')) . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $headers = [
                'From: ' . ($config['from_name'] !== '' ? '"' . str_replace('"', '\"', $config['from_name']) . '" <' . $config['from_email'] . '>' : $config['from_email']),
                'Reply-To: ' . $config['reply_to'],
                'To: ' . trim((string) ($message['recipient'] ?? '')),
                'Subject: ' . (string) ($message['subject'] ?? ''),
                'MIME-Version: 1.0',
            ];

            $textBody = (string) ($message['body'] ?? '');
            $htmlBody = trim((string) ($message['html_body'] ?? ''));
            if ($htmlBody !== '') {
                $boundary = '=_LRSMTP_' . bin2hex(random_bytes(8));
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                $body = $this->multipartMailBody($textBody, $htmlBody, $boundary);
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $body = $textBody;
            }

            $body = str_replace(["\r\n.", "\n."], ["\r\n..", "\n.."], $body);
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
            $this->smtpExpect($socket, [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);
        } catch (\Throwable $exception) {
            fclose($socket);

            return 'logged_smtp_failed';
        }

        fclose($socket);

        return 'sent_smtp';
    }

    /**
     * @return array{host: string, port: int, encryption: string, username: string, password: string, from_email: string, reply_to: string, from_name: string, auth_required: bool, is_configured: bool}
     */
    private function smtpConfig(string $storeEmail): array
    {
        $host = trim((string) $this->settingsService->get('smtp_host', 'smtp.gmail.com'));
        $port = (int) $this->settingsService->get('smtp_port', '587');
        $encryption = strtolower(trim((string) $this->settingsService->get('smtp_encryption', 'tls')));
        $username = trim((string) $this->settingsService->get('smtp_username', $storeEmail !== '' ? $storeEmail : 'lilyandrose76@gmail.com'));
        $password = trim((string) $this->settingsService->get('smtp_password', ''));
        $sender = $this->phpMailSenderProfile($storeEmail);
        $fromEmail = filter_var($username, FILTER_VALIDATE_EMAIL) !== false
            ? $username
            : (filter_var($storeEmail, FILTER_VALIDATE_EMAIL) !== false ? $storeEmail : $sender['from_email']);
        $replyTo = filter_var($storeEmail, FILTER_VALIDATE_EMAIL) !== false
            ? $storeEmail
            : ($sender['reply_to'] !== '' ? $sender['reply_to'] : $fromEmail);
        $authRequired = $username !== '' || $password !== '';

        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }

        return [
            'host' => $host,
            'port' => $port > 0 ? $port : 587,
            'encryption' => $encryption,
            'username' => $username,
            'password' => $password,
            'from_email' => $fromEmail,
            'reply_to' => $replyTo,
            'from_name' => trim((string) ($this->emailBrandSettings()['sender_name'] ?? '')),
            'auth_required' => $authRequired,
            'is_configured' => $host !== ''
                && $fromEmail !== ''
                && (!$authRequired || ($username !== '' && $password !== '')),
        ];
    }

    /**
     * @param resource $socket
     * @param array<int, int> $codes
     */
    private function smtpCommand($socket, string $command, array $codes): string
    {
        fwrite($socket, $command . "\r\n");

        return $this->smtpExpect($socket, $codes);
    }

    /**
     * @param resource $socket
     * @param array<int, int> $codes
     */
    private function smtpExpect($socket, array $codes): string
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;

            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $status = (int) substr($response, 0, 3);

        if (!in_array($status, $codes, true)) {
            throw new \RuntimeException('Unexpected SMTP response: ' . trim($response));
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<string, mixed>|null $payment
     */
    private function paymentPath(array $order, ?array $payment): string
    {
        if ($payment === null || empty($payment['payment_reference'])) {
            return 'Not available';
        }

        return $this->publicBaseUrl() . '/payment?' . http_build_query([
            'reference' => (string) $payment['payment_reference'],
            'token' => $this->orderService->publicAccessToken($order),
        ]);
    }

    /**
     * @param array<string, mixed> $order
     */
    private function orderConfirmationPath(array $order): string
    {
        return $this->publicBaseUrl() . '/order-confirmation?' . http_build_query([
            'number' => (string) ($order['order_number'] ?? ''),
            'token' => $this->orderService->publicAccessToken($order),
        ]);
    }

    private function publicBaseUrl(): string
    {
        return app_base_url();
    }

    /**
     * @return array{brand_name: string, sender_name: string, reply_to_email: string, contact_email: string, contact_phone: string, contact_address: string, website_url: string, footer_text: string, support_message: string, social_links: array<string, string>}
     */
    private function emailBrandSettings(): array
    {
        $brand = trim((string) $this->settingsService->get('store_name', 'Lily and Rose'));
        $sender = trim((string) $this->settingsService->get('email_sender_name', $brand !== '' ? $brand : 'Lily and Rose Florist'));
        $website = trim((string) $this->settingsService->get('public_base_url', app_base_url()));
        $social = [];
        foreach (['instagram_url' => 'Instagram', 'facebook_url' => 'Facebook', 'x_url' => 'X', 'tiktok_url' => 'TikTok'] as $key => $label) {
            $value = trim((string) $this->settingsService->get($key, ''));
            if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) !== false) {
                $social[$label] = $value;
            }
        }

        return [
            'brand_name' => $brand !== '' ? $brand : 'Lily and Rose',
            'sender_name' => $sender,
            'reply_to_email' => trim((string) $this->settingsService->get('email_reply_to', '')),
            'contact_email' => trim((string) $this->settingsService->get('store_email', '')),
            'contact_phone' => trim((string) $this->settingsService->get('store_phone', '')),
            'contact_address' => trim((string) $this->settingsService->get('store_address', '')),
            'website_url' => $website !== '' ? $website : app_base_url(),
            'footer_text' => trim((string) $this->settingsService->get('email_footer_text', '')),
            'support_message' => trim((string) $this->settingsService->get('email_support_message', '')),
            'social_links' => $social,
        ];
    }

    private function multipartMailBody(string $textBody, string $htmlBody, string $boundary): string
    {
        return '--' . $boundary . "\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . trim($textBody) . "\r\n\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit\r\n\r\n"
            . trim($htmlBody) . "\r\n\r\n"
            . '--' . $boundary . "--\r\n";
    }

    /**
     * @param array<string, string> $details
     */
    private function brandedEmailHtml(string $title, string $intro, array $details, string $ctaLabel = '', string $ctaUrl = '', bool $isStoreMessage = false): string
    {
        $brand = $this->emailBrandSettings();
        $safe = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $rows = '';
        foreach ($details as $label => $value) {
            if (trim($value) === '') {
                continue;
            }
            $rows .= '<tr><td style="padding:8px 0;color:#6b7280;font-size:13px;width:38%;">' . $safe((string) $label) . '</td><td style="padding:8px 0;color:#111827;font-size:14px;font-weight:600;">' . $safe((string) $value) . '</td></tr>';
        }
        $support = $brand['support_message'] !== '' ? '<p style="margin:14px 0 0;color:#4b5563;font-size:13px;line-height:1.5;">' . $safe($brand['support_message']) . '</p>' : '';
        $footer = $brand['footer_text'] !== '' ? $brand['footer_text'] : ('Sent by ' . $brand['brand_name']);
        $social = '';
        foreach ($brand['social_links'] as $label => $url) {
            $social .= '<a href="' . $safe($url) . '" style="color:#7c3f58;text-decoration:none;margin-right:12px;">' . $safe($label) . '</a>';
        }
        $cta = ($ctaLabel !== '' && $ctaUrl !== '') ? '<p style="margin:18px 0 0;"><a href="' . $safe($ctaUrl) . '" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:999px;font-size:13px;font-weight:700;">' . $safe($ctaLabel) . '</a></p>' : '';
        $storeContext = $isStoreMessage ? '<p style="margin:10px 0 0;color:#92400e;font-size:12px;">Store-facing notification</p>' : '';

        return '<!doctype html><html><body style="margin:0;padding:0;background:#f6f5f3;font-family:Arial,sans-serif;">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #ece8e2;border-radius:14px;overflow:hidden;">'
            . '<tr><td style="padding:24px 24px 12px;background:#faf7f2;"><p style="margin:0;color:#7c3f58;font-size:11px;letter-spacing:1.2px;text-transform:uppercase;">Lily and Rose Florist</p><h1 style="margin:8px 0 0;color:#111827;font-size:24px;line-height:1.2;">' . $safe($title) . '</h1></td></tr>'
            . '<tr><td style="padding:20px 24px;"><p style="margin:0;color:#374151;font-size:15px;line-height:1.6;">' . $safe($intro) . '</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:14px;">' . $rows . '</table>'
            . $cta . $storeContext . $support . '</td></tr>'
            . '<tr><td style="padding:16px 24px 24px;border-top:1px solid #ece8e2;background:#fcfbf9;">'
            . '<p style="margin:0;color:#4b5563;font-size:12px;line-height:1.6;">' . $safe($brand['brand_name']) . '<br>' . $safe($brand['contact_email']) . ($brand['contact_phone'] !== '' ? ' • ' . $safe($brand['contact_phone']) : '') . '<br>' . $safe($brand['contact_address']) . '</p>'
            . ($social !== '' ? '<p style="margin:10px 0 0;font-size:12px;">' . $social . '</p>' : '')
            . '<p style="margin:10px 0 0;color:#6b7280;font-size:11px;">' . $safe($footer) . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    private function logMessage(
        string $type,
        string $recipient,
        string $subject,
        string $body,
        string $deliveryMode,
        string $result
    ): void {
        $payload = [
            'timestamp' => date('c'),
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'delivery_mode' => $deliveryMode,
            'result' => $result,
            'body' => $body,
        ];

        $logPath = $this->app->getBasePath(self::LOG_FILE);
        $logDirectory = dirname($logPath);

        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            error_log('Unable to create notification log directory.');
            return;
        }

        if (!is_writable($logDirectory)) {
            error_log('Notification log directory is not writable.');
            return;
        }

        if (file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('Unable to write notification log.');
        }
    }
}
