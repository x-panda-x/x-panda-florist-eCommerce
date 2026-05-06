<?php

declare(strict_types=1);

require __DIR__ . '/lib/LiveSiteHarness.php';

$configPath = is_file(__DIR__ . '/smoke_test_config.local.php')
    ? __DIR__ . '/smoke_test_config.local.php'
    : __DIR__ . '/smoke_test_config.example.php';
$config = require $configPath;

if (!is_array($config)) {
    fwrite(STDERR, "Invalid smoke test config.\n");
    exit(1);
}

$baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
$productSlugs = is_array($config['product_slugs'] ?? null) ? $config['product_slugs'] : [];
$admin = is_array($config['admin'] ?? null) ? $config['admin'] : [];
$checkout = is_array($config['checkout'] ?? null) ? $config['checkout'] : [];

if ($baseUrl === '' || $productSlugs === [] || ($admin['email'] ?? '') === '' || ($admin['password'] ?? '') === '') {
    fwrite(STDERR, "Smoke test config is incomplete.\n");
    exit(1);
}

$passed = [];
$failed = [];
$warnings = [];
$screens = [];

$record = static function (bool $ok, string $label, string $detail = '') use (&$passed, &$failed): void {
    $entry = $detail !== '' ? $label . ' — ' . $detail : $label;

    if ($ok) {
        $passed[] = $entry;
        return;
    }

    $failed[] = $entry;
};

try {
    $anonymous = new LiveSiteHarness($baseUrl);
    $homepage = $anonymous->get('/');
    $screens[] = '/';
    $record($homepage['status'] === 200, 'Homepage loads', 'status=' . $homepage['status']);

    $css = $anonymous->get('/assets/css/storefront.css');
    $screens[] = '/assets/css/storefront.css';
    $record($css['status'] === 200 && LiveSiteHarness::contains($css['body'], 'body'), 'Main CSS loads', 'status=' . $css['status']);

    $navPages = [
        '/occasions',
        '/best-sellers',
        '/same-day',
        '/search?q=gift',
        '/contact',
        '/account/login',
        '/account/register',
        '/cart',
    ];

    foreach ($navPages as $path) {
        $response = $anonymous->get($path);
        $screens[] = $path;
        $record($response['status'] === 200, 'Navigation page loads', $path . ' status=' . $response['status']);
    }

$firstProductHtml = '';
$firstProductSlug = null;
$firstVariantId = null;
$firstUploadPath = null;

    foreach (array_slice($productSlugs, 0, 3) as $index => $slug) {
        $path = '/product?slug=' . rawurlencode((string) $slug);
        $response = $anonymous->get($path);
        $screens[] = $path;
        $record($response['status'] === 200, 'Product page loads', $slug . ' status=' . $response['status']);

        if ($index === 0 && $response['status'] === 200) {
            $firstProductHtml = $response['body'];
            $firstProductSlug = LiveSiteHarness::extractInputValue($response['body'], 'product_slug');
            $firstVariantId = LiveSiteHarness::extractCheckedVariantId($response['body']);
        }

        if ($firstUploadPath === null && $response['status'] === 200) {
            $firstUploadPath = LiveSiteHarness::extractFirstMatch($response['body'], '#"(\/uploads\/[^"]+)"#');
        }
    }

    if ($firstUploadPath !== null) {
        $uploadResponse = $anonymous->get($firstUploadPath);
        $screens[] = $firstUploadPath;
        $record($uploadResponse['status'] === 200, 'Uploaded product image loads', 'status=' . $uploadResponse['status']);
    } else {
        $warnings[] = 'No uploaded product image path was detected on the first tested product page.';
    }

    if ($firstProductSlug === null || $firstVariantId === null) {
        throw new RuntimeException('Unable to parse product form inputs for cart and checkout smoke tests.');
    }

    $cartSession = new LiveSiteHarness($baseUrl);
    $productPage = $cartSession->get('/product?slug=' . rawurlencode((string) $productSlugs[0]));
    $csrf = LiveSiteHarness::extractCsrfToken($productPage['body']);

    if ($csrf === null) {
        throw new RuntimeException('Unable to extract product-page CSRF token.');
    }

    $add = $cartSession->post('/cart/add', [
        'csrf_token' => $csrf,
        'product_slug' => $firstProductSlug,
        'variant_id' => $firstVariantId,
        'quantity' => '1',
    ]);
    $record($add['status'] === 302 && $add['location'] === '/cart', 'Cart add redirects correctly', 'status=' . $add['status']);

    $cartPage = $cartSession->get('/cart');
    $screens[] = '/cart';
    $itemKey = LiveSiteHarness::extractInputValue($cartPage['body'], 'item_key');
    $record($cartPage['status'] === 200 && LiveSiteHarness::contains($cartPage['body'], 'Item added to cart.'), 'Cart add confirmed', 'status=' . $cartPage['status']);

    if ($itemKey === null) {
        throw new RuntimeException('Unable to extract cart item key.');
    }

    $cartCsrf = LiveSiteHarness::extractCsrfToken($cartPage['body']);

    if ($cartCsrf === null) {
        throw new RuntimeException('Unable to extract cart CSRF token.');
    }

    $update = $cartSession->post('/cart/update', [
        'csrf_token' => $cartCsrf,
        'item_key' => $itemKey,
        'quantity' => '2',
    ]);
    $record($update['status'] === 302 && $update['location'] === '/cart', 'Cart update redirects correctly', 'status=' . $update['status']);

    $cartUpdated = $cartSession->get('/cart');
    $record(
        $cartUpdated['status'] === 200
        && LiveSiteHarness::contains($cartUpdated['body'], 'Cart updated.')
        && LiveSiteHarness::contains($cartUpdated['body'], 'value="2"'),
        'Cart update confirmed',
        'status=' . $cartUpdated['status']
    );

    $remove = $cartSession->post('/cart/remove', [
        'csrf_token' => LiveSiteHarness::extractCsrfToken($cartUpdated['body']) ?? '',
        'item_key' => $itemKey,
    ]);
    $record($remove['status'] === 302 && $remove['location'] === '/cart', 'Cart remove redirects correctly', 'status=' . $remove['status']);

    $cartRemoved = $cartSession->get('/cart');
    $record(
        $cartRemoved['status'] === 200
        && (
            LiveSiteHarness::contains($cartRemoved['body'], 'Item removed.')
            || LiveSiteHarness::contains($cartRemoved['body'], 'Item removed from cart.')
        ),
        'Cart remove confirmed',
        'status=' . $cartRemoved['status']
    );

    $customerSession = new LiveSiteHarness($baseUrl);
    $registerPage = $customerSession->get('/account/register');
    $registerCsrf = LiveSiteHarness::extractCsrfToken($registerPage['body']);

    if ($registerCsrf === null) {
        throw new RuntimeException('Unable to extract register-page CSRF token.');
    }

    $email = sprintf(
        '%s.%d.%d@example.test',
        (string) ($checkout['email_prefix'] ?? 'smoketest'),
        time(),
        random_int(1000, 9999)
    );
    $password = (string) ($checkout['password'] ?? 'SmokeTest!20260402');

    $register = $customerSession->post('/account/register', [
        'csrf_token' => $registerCsrf,
        'return_to' => '',
        'full_name' => (string) ($checkout['full_name'] ?? 'Smoke Test Checkout'),
        'email' => $email,
        'phone' => (string) ($checkout['phone'] ?? '6155550101'),
        'password' => $password,
        'password_confirmation' => $password,
    ]);
    $screens[] = '/account/register';
    $record($register['status'] === 302 && $register['location'] === '/account', 'Customer registration works', 'status=' . $register['status']);
    $warnings[] = 'Smoke test customer created: ' . $email;

    $customerProductPage = $customerSession->get('/product?slug=' . rawurlencode((string) $productSlugs[1]));
    $customerCsrf = LiveSiteHarness::extractCsrfToken($customerProductPage['body']);
    $customerProductSlug = LiveSiteHarness::extractInputValue($customerProductPage['body'], 'product_slug');
    $customerVariantId = LiveSiteHarness::extractCheckedVariantId($customerProductPage['body']);

    if ($customerCsrf === null || $customerProductSlug === null || $customerVariantId === null) {
        throw new RuntimeException('Unable to parse checkout product form inputs.');
    }

    $customerAdd = $customerSession->post('/cart/add', [
        'csrf_token' => $customerCsrf,
        'product_slug' => $customerProductSlug,
        'variant_id' => $customerVariantId,
        'quantity' => '1',
    ]);
    $record($customerAdd['status'] === 302 && $customerAdd['location'] === '/cart', 'Checkout cart add works', 'status=' . $customerAdd['status']);

    $checkoutPage = $customerSession->get('/checkout');
    $screens[] = '/checkout';
    $checkoutCsrf = LiveSiteHarness::extractCsrfToken($checkoutPage['body']);
    $record($checkoutPage['status'] === 200 && LiveSiteHarness::contains($checkoutPage['body'], 'Checkout'), 'Checkout page loads', 'status=' . $checkoutPage['status']);

    if ($checkoutCsrf === null) {
        throw new RuntimeException('Unable to extract checkout CSRF token.');
    }

    $deliveryDate = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
    $checkoutSubmit = $customerSession->post('/checkout', [
        'csrf_token' => $checkoutCsrf,
        'customer_name' => (string) ($checkout['full_name'] ?? 'Smoke Test Checkout'),
        'customer_email' => $email,
        'customer_phone' => (string) ($checkout['phone'] ?? '6155550101'),
        'recipient_name' => (string) ($checkout['recipient_name'] ?? 'Review Recipient'),
        'delivery_address' => (string) ($checkout['delivery_address'] ?? '123 Review Lane'),
        'delivery_zip' => (string) ($checkout['delivery_zip'] ?? '37211'),
        'delivery_date' => $deliveryDate,
        'delivery_time_slot' => (string) ($checkout['delivery_time_slot'] ?? '12:00-15:00'),
        'delivery_instructions' => (string) ($checkout['delivery_instructions'] ?? 'Front desk'),
        'card_message' => (string) ($checkout['card_message'] ?? 'Smoke test order'),
        'tip_amount' => '0.00',
        'policy_accepted' => '1',
    ]);

    $paymentLocation = $checkoutSubmit['location'] ?? '';
    $record(
        $checkoutSubmit['status'] === 302 && str_starts_with((string) $paymentLocation, '/payment?'),
        'Checkout reaches payment stage',
        'status=' . $checkoutSubmit['status']
    );

    if (!is_string($paymentLocation) || $paymentLocation === '') {
        throw new RuntimeException('Checkout did not return a payment redirect.');
    }

    $paymentPage = $customerSession->get($paymentLocation);
    $screens[] = $paymentLocation;
    $record(
        $paymentPage['status'] === 200
        && LiveSiteHarness::contains($paymentPage['body'], 'Payment Placeholder')
        && LiveSiteHarness::contains($paymentPage['body'], 'placeholder'),
        'Payment page remains placeholder-only',
        'status=' . $paymentPage['status']
    );
    $warnings[] = 'Smoke test checkout created a review order for ' . $email;

    $contactPage = $anonymous->get('/contact');
    $screens[] = '/contact';
    $record($contactPage['status'] === 200, 'Contact page loads', 'status=' . $contactPage['status']);

    $adminSession = new LiveSiteHarness($baseUrl);
    $adminLoginPage = $adminSession->get('/admin/login');
    $screens[] = '/admin/login';
    $adminCsrf = LiveSiteHarness::extractCsrfToken($adminLoginPage['body']);

    if ($adminCsrf === null) {
        throw new RuntimeException('Unable to extract admin login CSRF token.');
    }

    $adminLogin = $adminSession->post('/admin/login', [
        'csrf_token' => $adminCsrf,
        'email' => (string) ($admin['email'] ?? ''),
        'password' => (string) ($admin['password'] ?? ''),
    ]);
    $record($adminLogin['status'] === 302 && $adminLogin['location'] === '/admin', 'Admin login works', 'status=' . $adminLogin['status']);

    $adminPages = [
        '/admin' => 'Admin dashboard loads',
        '/admin/products' => 'Admin products loads',
        '/admin/orders' => 'Admin orders loads',
        '/admin/site-settings' => 'Admin site settings loads',
        '/admin/settings' => 'Admin store settings loads',
        '/admin/theme' => 'Admin theme settings loads',
    ];

    foreach ($adminPages as $path => $label) {
        $response = $adminSession->get($path, ['follow_location' => true]);
        $screens[] = $path;
        $record($response['status'] === 200, $label, 'status=' . $response['status']);
    }

    if (LiveSiteHarness::contains($homepage['body'], 'Application error') || LiveSiteHarness::contains($paymentPage['body'], 'Fatal error')) {
        $failed[] = 'Fatal error marker detected in tested HTML output.';
    }
} catch (Throwable $exception) {
    $failed[] = 'Smoke test execution error — ' . $exception->getMessage();
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

echo "\nScreens/pages tested:\n";
foreach (array_values(array_unique($screens)) as $entry) {
    echo "- $entry\n";
}

exit($failed === [] ? 0 : 1);
