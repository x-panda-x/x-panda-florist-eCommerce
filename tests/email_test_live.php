<?php

declare(strict_types=1);

require __DIR__ . '/lib/LiveSiteHarness.php';

$configPath = is_file(__DIR__ . '/email_test_config.local.php')
    ? __DIR__ . '/email_test_config.local.php'
    : __DIR__ . '/email_test_config.example.php';
$config = require $configPath;

if (!is_array($config)) {
    fwrite(STDERR, "Invalid email test config.\n");
    exit(1);
}

$baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
$sftp = is_array($config['sftp'] ?? null) ? $config['sftp'] : [];
$customerConfig = is_array($config['customer'] ?? null) ? $config['customer'] : [];
$reminderConfig = is_array($config['reminder'] ?? null) ? $config['reminder'] : [];

if (
    $baseUrl === ''
    || ($sftp['host'] ?? '') === ''
    || ($sftp['user'] ?? '') === ''
    || ($sftp['key_path'] ?? '') === ''
    || ($sftp['remote_notification_log'] ?? '') === ''
) {
    fwrite(STDERR, "Email test config is incomplete.\n");
    exit(1);
}

$passed = [];
$failed = [];
$warnings = [];
$artifacts = [];

$record = static function (bool $ok, string $label, string $detail = '') use (&$passed, &$failed): void {
    $entry = $detail !== '' ? $label . ' — ' . $detail : $label;

    if ($ok) {
        $passed[] = $entry;
        return;
    }

    $failed[] = $entry;
};

/**
 * @return array<int, array<string, mixed>>
 */
function fetchNotificationEntries(array $sftp): array
{
    $batchFile = tempnam(sys_get_temp_dir(), 'sheyda-sftp-batch-');
    $localCopy = tempnam(sys_get_temp_dir(), 'sheyda-notifications-');
    $command = sprintf(
        'sftp -i %s -oBatchMode=yes -P %d -b %s %s@%s 2>&1',
        escapeshellarg((string) $sftp['key_path']),
        (int) ($sftp['port'] ?? 22),
        escapeshellarg($batchFile),
        escapeshellarg((string) $sftp['user']),
        escapeshellarg((string) $sftp['host'])
    );

    file_put_contents(
        $batchFile,
        sprintf(
            "get %s %s\nbye\n",
            (string) $sftp['remote_notification_log'],
            $localCopy
        )
    );

    $output = [];
    $exitCode = 0;
    exec($command, $output, $exitCode);
    @unlink($batchFile);

    if ($exitCode !== 0 || !is_file($localCopy)) {
        throw new RuntimeException('Unable to fetch remote notification log: ' . implode("\n", $output));
    }

    $lines = file($localCopy, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    @unlink($localCopy);
    $entries = [];

    foreach ($lines as $line) {
        $decoded = json_decode($line, true);

        if (is_array($decoded)) {
            $entries[] = $decoded;
        }
    }

    return $entries;
}

/**
 * @param array<int, array<string, mixed>> $entries
 * @return array<string, mixed>|null
 */
function latestMatchingEntry(array $entries, callable $predicate): ?array
{
    for ($index = count($entries) - 1; $index >= 0; $index--) {
        if ($predicate($entries[$index])) {
            return $entries[$index];
        }
    }

    return null;
}

/**
 * @param array<int, array<string, mixed>> $entries
 * @return array<int, array<string, mixed>>
 */
function newEntries(array $allEntries, int &$offset): array
{
    $entries = array_slice($allEntries, $offset);
    $offset = count($allEntries);

    return $entries;
}

function resultIsSent(?array $entry): bool
{
    if (!is_array($entry)) {
        return false;
    }

    return str_starts_with((string) ($entry['result'] ?? ''), 'sent_');
}

try {
    $allLogEntries = fetchNotificationEntries($sftp);
    $logOffset = count($allLogEntries);

    $configuredEmail = trim((string) ($customerConfig['email'] ?? ''));
    $email = $configuredEmail !== ''
        ? $configuredEmail
        : sprintf(
            '%s.%d.%d@example.test',
            (string) ($customerConfig['email_prefix'] ?? 'emailtest'),
            time(),
            random_int(1000, 9999)
        );
    $initialPassword = (string) ($customerConfig['initial_password'] ?? 'EmailSmoke!20260402');
    $resetPassword = (string) ($customerConfig['reset_password'] ?? 'EmailReset!20260402');
    $fullName = (string) ($customerConfig['full_name'] ?? 'Email Smoke Customer');
    $phone = (string) ($customerConfig['phone'] ?? '6155550102');

    $publicSession = new LiveSiteHarness($baseUrl);
    $registerPage = $publicSession->get('/account/register');
    $registerCsrf = LiveSiteHarness::extractCsrfToken($registerPage['body']);

    if ($registerCsrf === null) {
        throw new RuntimeException('Unable to extract register CSRF token.');
    }

    $register = $publicSession->post('/account/register', [
        'csrf_token' => $registerCsrf,
        'return_to' => '',
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'password' => $initialPassword,
        'password_confirmation' => $initialPassword,
    ]);
    $record($register['status'] === 302 && $register['location'] === '/account', 'Customer registration succeeds', 'status=' . $register['status']);
    $warnings[] = 'Email test customer created: ' . $email;

    $allLogEntries = fetchNotificationEntries($sftp);
    $welcomeEntries = newEntries($allLogEntries, $logOffset);
    $welcome = latestMatchingEntry($welcomeEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'customer_welcome' && ($entry['recipient'] ?? '') === $email
    );
    $record(resultIsSent($welcome), 'Welcome email processed', is_array($welcome) ? (string) ($welcome['result'] ?? '') : 'not logged');

    $forgotSession = new LiveSiteHarness($baseUrl);
    $forgotPage = $forgotSession->get('/account/forgot-password');
    $forgotCsrf = LiveSiteHarness::extractCsrfToken($forgotPage['body']);

    if ($forgotCsrf === null) {
        throw new RuntimeException('Unable to extract forgot-password CSRF token.');
    }

    $forgot = $forgotSession->post('/account/forgot-password', [
        'csrf_token' => $forgotCsrf,
        'email' => $email,
    ]);
    $record($forgot['status'] === 302 && $forgot['location'] === '/account/forgot-password', 'Forgot-password request succeeds', 'status=' . $forgot['status']);

    $allLogEntries = fetchNotificationEntries($sftp);
    $resetEntries = newEntries($allLogEntries, $logOffset);
    $resetNotification = latestMatchingEntry($resetEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'customer_password_reset' && ($entry['recipient'] ?? '') === $email
    );
    $record(resultIsSent($resetNotification), 'Password reset email processed', is_array($resetNotification) ? (string) ($resetNotification['result'] ?? '') : 'not logged');

    $resetLink = is_array($resetNotification)
        ? LiveSiteHarness::extractFirstMatch((string) ($resetNotification['body'] ?? ''), '#Reset Link: (https?://\S+)#')
        : null;

    if ($resetLink === null) {
        throw new RuntimeException('Unable to extract password reset link from notification log.');
    }

    $resetPage = $forgotSession->get($resetLink);
    $resetCsrf = LiveSiteHarness::extractCsrfToken($resetPage['body']);

    if ($resetCsrf === null) {
        throw new RuntimeException('Unable to extract reset-password CSRF token.');
    }

    parse_str((string) parse_url($resetLink, PHP_URL_QUERY), $resetQuery);
    $resetToken = (string) ($resetQuery['token'] ?? '');

    if ($resetToken === '') {
        throw new RuntimeException('Unable to extract reset token from reset link.');
    }

    $resetSubmit = $forgotSession->post('/account/reset-password', [
        'csrf_token' => $resetCsrf,
        'token' => $resetToken,
        'password' => $resetPassword,
        'password_confirmation' => $resetPassword,
    ]);
    $record($resetSubmit['status'] === 302 && $resetSubmit['location'] === '/account/login', 'Password reset completes', 'status=' . $resetSubmit['status']);

    $customerSession = new LiveSiteHarness($baseUrl);
    $loginPage = $customerSession->get('/account/login');
    $loginCsrf = LiveSiteHarness::extractCsrfToken($loginPage['body']);

    if ($loginCsrf === null) {
        throw new RuntimeException('Unable to extract customer login CSRF token.');
    }

    $login = $customerSession->post('/account/login', [
        'csrf_token' => $loginCsrf,
        'email' => $email,
        'password' => $resetPassword,
        'return_to' => '',
    ]);
    $record($login['status'] === 302 && $login['location'] === '/account', 'Customer login works after reset', 'status=' . $login['status']);

    $reminderPage = $customerSession->get('/account/reminders');
    $reminderCsrf = LiveSiteHarness::extractCsrfToken($reminderPage['body']);

    if ($reminderCsrf === null) {
        throw new RuntimeException('Unable to extract reminder CSRF token.');
    }

    $reminderDate = (new DateTimeImmutable('now', new DateTimeZone('America/Chicago')))
        ->modify('+3 days')
        ->format('Y-m-d');
    $createReminder = $customerSession->post('/account/reminders/create', [
        'csrf_token' => $reminderCsrf,
        'occasion_label' => (string) ($reminderConfig['occasion_label'] ?? 'Smoke Test Occasion'),
        'recipient_name' => (string) ($reminderConfig['recipient_name'] ?? 'Reminder Recipient'),
        'reminder_date' => $reminderDate,
        'note' => (string) ($reminderConfig['note'] ?? 'Email smoke reminder flow'),
        'is_active' => '1',
        'create_action' => 'shop',
    ]);
    $record($createReminder['status'] === 302, 'Reminder draft creation succeeds', 'status=' . $createReminder['status']);

    $productSlug = (string) ($reminderConfig['product_slug'] ?? 'same-day-spring-bouquet');
    $productPage = $customerSession->get('/product?slug=' . rawurlencode($productSlug));
    $productCsrf = LiveSiteHarness::extractCsrfToken($productPage['body']);
    $productSlugInput = LiveSiteHarness::extractInputValue($productPage['body'], 'product_slug');
    $variantId = LiveSiteHarness::extractCheckedVariantId($productPage['body']);

    if ($productCsrf === null || $productSlugInput === null || $variantId === null) {
        throw new RuntimeException('Unable to parse reminder checkout product form.');
    }

    $addToCart = $customerSession->post('/cart/add', [
        'csrf_token' => $productCsrf,
        'product_slug' => $productSlugInput,
        'variant_id' => $variantId,
        'quantity' => '1',
    ]);
    $record($addToCart['status'] === 302 && $addToCart['location'] === '/cart', 'Reminder checkout cart add succeeds', 'status=' . $addToCart['status']);

    $checkoutPage = $customerSession->get('/checkout');
    $checkoutCsrf = LiveSiteHarness::extractCsrfToken($checkoutPage['body']);

    if ($checkoutCsrf === null) {
        throw new RuntimeException('Unable to extract reminder checkout CSRF token.');
    }

    $checkoutSubmit = $customerSession->post('/checkout', [
        'csrf_token' => $checkoutCsrf,
        'customer_name' => $fullName,
        'customer_email' => $email,
        'customer_phone' => $phone,
        'recipient_name' => (string) ($reminderConfig['recipient_name'] ?? 'Reminder Recipient'),
        'delivery_address' => (string) ($reminderConfig['delivery_address'] ?? '123 Review Lane'),
        'delivery_zip' => (string) ($reminderConfig['delivery_zip'] ?? '37211'),
        'delivery_date' => (new DateTimeImmutable('tomorrow'))->format('Y-m-d'),
        'delivery_time_slot' => (string) ($reminderConfig['delivery_time_slot'] ?? '12:00-15:00'),
        'delivery_instructions' => (string) ($reminderConfig['delivery_instructions'] ?? 'Front desk'),
        'card_message' => (string) ($reminderConfig['card_message'] ?? 'Email smoke order'),
        'tip_amount' => '0.00',
        'policy_accepted' => '1',
    ]);
    $paymentLocation = (string) ($checkoutSubmit['location'] ?? '');
    $record($checkoutSubmit['status'] === 302 && str_starts_with($paymentLocation, '/payment?'), 'Reminder checkout reaches payment', 'status=' . $checkoutSubmit['status']);

    if ($paymentLocation === '') {
        throw new RuntimeException('Reminder checkout did not return a payment location.');
    }

    $paymentPage = $customerSession->get($paymentLocation);
    $paymentCsrf = LiveSiteHarness::extractCsrfToken($paymentPage['body']);

    if ($paymentCsrf === null) {
        throw new RuntimeException('Unable to extract payment simulation CSRF token.');
    }

    parse_str((string) parse_url($paymentLocation, PHP_URL_QUERY), $paymentQuery);
    $paymentReference = (string) ($paymentQuery['reference'] ?? '');
    $paymentToken = (string) ($paymentQuery['token'] ?? '');

    if ($paymentReference === '' || $paymentToken === '') {
        throw new RuntimeException('Payment redirect is missing the reference or token.');
    }

    $simulatePaid = $customerSession->post('/payment/simulate', [
        'csrf_token' => $paymentCsrf,
        'reference' => $paymentReference,
        'token' => $paymentToken,
        'status' => 'paid',
    ]);
    $record($simulatePaid['status'] === 302 && str_starts_with((string) ($simulatePaid['location'] ?? ''), '/payment?'), 'Placeholder payment simulation succeeds', 'status=' . $simulatePaid['status']);

    $allLogEntries = fetchNotificationEntries($sftp);
    $postOrderEntries = newEntries($allLogEntries, $logOffset);
    $orderNotification = latestMatchingEntry($postOrderEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'customer_order_confirmation' && ($entry['recipient'] ?? '') === $email
    );
    $storeNotification = latestMatchingEntry($postOrderEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'store_new_order_notification'
    );
    $reminderConfirmation = latestMatchingEntry($postOrderEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'customer_reminder_confirmation' && ($entry['recipient'] ?? '') === $email
    );

    $record(resultIsSent($orderNotification), 'Order confirmation email processed', is_array($orderNotification) ? (string) ($orderNotification['result'] ?? '') : 'not logged');
    $record(resultIsSent($storeNotification), 'Store order notification processed', is_array($storeNotification) ? (string) ($storeNotification['result'] ?? '') : 'not logged');
    $record(resultIsSent($reminderConfirmation), 'Reminder confirmation email processed', is_array($reminderConfirmation) ? (string) ($reminderConfirmation['result'] ?? '') : 'not logged');

    $artifacts[] = 'Payment reference: ' . $paymentReference;

    $processReminders = $customerSession->get('/process_reminders.php');
    $processPayload = json_decode($processReminders['body'], true);
    $record($processReminders['status'] === 200 && is_array($processPayload), 'Reminder processor responds', 'status=' . $processReminders['status']);

    $allLogEntries = fetchNotificationEntries($sftp);
    $reminderRunEntries = newEntries($allLogEntries, $logOffset);
    $upcomingReminder = latestMatchingEntry($reminderRunEntries, static fn (array $entry): bool =>
        ($entry['type'] ?? '') === 'customer_reminder_upcoming' && ($entry['recipient'] ?? '') === $email
    );
    $record(resultIsSent($upcomingReminder), 'Upcoming reminder email processed', is_array($upcomingReminder) ? (string) ($upcomingReminder['result'] ?? '') : 'not logged');
    $warnings[] = 'Email flow test created reminder-linked order data for ' . $email;
} catch (Throwable $exception) {
    $failed[] = 'Email test execution error — ' . $exception->getMessage();
}

echo "Passed checks:\n";
foreach ($passed as $entry) {
    echo "- $entry\n";
}

echo "\nFailed checks:\n";
if ($failed === []) {
    echo "- None\n";
} else {
    foreach ($failed as $entry) {
        echo "- $entry\n";
    }
}

echo "\nWarnings:\n";
if ($warnings === []) {
    echo "- None\n";
} else {
    foreach ($warnings as $entry) {
        echo "- $entry\n";
    }
}

echo "\nArtifacts:\n";
if ($artifacts === []) {
    echo "- None\n";
} else {
    foreach ($artifacts as $entry) {
        echo "- $entry\n";
    }
}

exit($failed === [] ? 0 : 1);
