<?php

declare(strict_types=1);

$buildStorefrontBrowseData = static function (App\Core\Application $app, array $input = []): array {
    $productService = new App\Services\ProductService($app);
    $criteria = $productService->storefrontCriteriaFromInput($input);

    return [
        'productService' => $productService,
        'criteria' => $criteria,
        'categoryOptions' => $productService->listCategories(),
        'occasionOptions' => $productService->listOccasions(),
        'sortOptions' => $productService->storefrontSortOptions(),
    ];
};

$consumePromoFlash = static function (string $flashKey): array {
    $flash = $_SESSION[$flashKey] ?? [];
    unset($_SESSION[$flashKey]);

    return is_array($flash) ? $flash : [];
};

$buildPromoState = static function (App\Core\Application $app, array $summary): array {
    $promoService = new App\Services\PromoService($app);

    return $promoService->getAppliedPromoForCart($summary);
};

$buildCheckoutReturnPath = static function (?string $flow = null): string {
    $params = [];
    $normalizedFlow = trim((string) $flow);

    if ($normalizedFlow === 'same-day') {
        $params['flow'] = 'same-day';
    }

    return '/checkout' . ($params !== [] ? '?' . http_build_query($params) : '');
};

$handlePromoApply = static function (App\Core\Application $app, string $redirectPath, string $flashKey): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION[$flashKey]['error'] = 'The form session expired. Please try again.';
        header('Location: ' . $redirectPath);
        exit;
    }

    $productService = new App\Services\ProductService($app);
    $promoService = new App\Services\PromoService($app);
    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        $cart = [];
    }

    $summary = $productService->summarizeCart($cart);
    $result = $promoService->applyPromoCode((string) ($_POST['promo_code'] ?? ''), $summary);

    if ($result['success']) {
        $_SESSION[$flashKey]['success'] = $result['message'];
    } else {
        $_SESSION[$flashKey]['error'] = $result['message'];
    }

    header('Location: ' . $redirectPath);
    exit;
};

$handlePromoRemove = static function (App\Core\Application $app, string $redirectPath, string $flashKey): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION[$flashKey]['error'] = 'The form session expired. Please try again.';
        header('Location: ' . $redirectPath);
        exit;
    }

    (new App\Services\PromoService($app))->clearSessionCode();
    $_SESSION[$flashKey]['success'] = 'Promo code removed.';
    header('Location: ' . $redirectPath);
    exit;
};

$renderOrderStatusPage = static function (
    App\Core\Application $app,
    array $lookup = [],
    ?array $order = null,
    ?array $items = null,
    ?string $paymentStatusLabel = null,
    ?string $error = null,
    ?string $success = null,
    bool $lookupAttempted = false
): string {
    $orderService = new App\Services\OrderService($app);

    return App\Core\View::render($app, 'order-status', [
        'pageTitle' => 'Order Tracking',
        'lookup' => $lookup,
        'order' => $order,
        'items' => $items ?? [],
        'paymentStatusLabel' => $paymentStatusLabel,
        'publicTracking' => is_array($order) ? $orderService->publicTrackingSummary($order) : null,
        'error' => $error,
        'success' => $success,
        'lookupAttempted' => $lookupAttempted,
    ], 'storefront');
};

$router->get('/', static function (App\Core\Application $app) use ($buildStorefrontBrowseData): string {
    $browse = $buildStorefrontBrowseData($app, $_GET);
    $homepageSectionService = new App\Services\HomepageSectionService($app);

    return App\Core\View::render($app, 'home', [
        'pageTitle' => 'Lily and Rose',
        'products' => $browse['productService']->listStorefrontProducts(6, $browse['criteria']),
        'homepageProductSections' => $homepageSectionService->listStorefrontSections(),
        'filters' => $browse['criteria'],
        'categoryOptions' => $browse['categoryOptions'],
        'occasionOptions' => $browse['occasionOptions'],
        'sortOptions' => $browse['sortOptions'],
    ], 'storefront');
});

$router->get('/occasions', static function (App\Core\Application $app) use ($buildStorefrontBrowseData): string {
    $browse = $buildStorefrontBrowseData($app, $_GET);

    return App\Core\View::render($app, 'occasions', [
        'pageTitle' => 'Shop by Occasion',
        'occasionCollections' => $browse['productService']->listOccasionCollections($browse['criteria']),
        'filters' => $browse['criteria'],
        'categoryOptions' => $browse['categoryOptions'],
        'occasionOptions' => $browse['occasionOptions'],
        'sortOptions' => $browse['sortOptions'],
    ], 'storefront');
});

$router->get('/best-sellers', static function (App\Core\Application $app) use ($buildStorefrontBrowseData): string {
    $browse = $buildStorefrontBrowseData($app, $_GET);

    return App\Core\View::render($app, 'best-sellers', [
        'pageTitle' => 'Best Selling Flowers',
        'products' => $browse['productService']->listFeaturedStorefrontProducts(6, $browse['criteria']),
        'filters' => $browse['criteria'],
        'categoryOptions' => $browse['categoryOptions'],
        'occasionOptions' => $browse['occasionOptions'],
        'sortOptions' => $browse['sortOptions'],
    ], 'storefront');
});

$router->get('/same-day', static function (App\Core\Application $app) use ($buildStorefrontBrowseData): string {
    $browse = $buildStorefrontBrowseData($app, array_merge($_GET, ['featured' => $_GET['featured'] ?? '1']));
    $orderService = new App\Services\OrderService($app);

    return App\Core\View::render($app, 'best-sellers', [
        'pageTitle' => 'Same Day Flowers',
        'products' => $browse['productService']->listFeaturedStorefrontProducts(6, $browse['criteria']),
        'filters' => $browse['criteria'],
        'categoryOptions' => $browse['categoryOptions'],
        'occasionOptions' => $browse['occasionOptions'],
        'sortOptions' => $browse['sortOptions'],
        'sameDayFlow' => true,
        'sameDayCutoff' => $orderService->sameDayCutoff(),
        'customerLoggedIn' => ($_SESSION['customer_logged_in'] ?? false) === true && !empty($_SESSION['customer_id']),
        'hasCartItems' => !empty($_SESSION['cart']) && is_array($_SESSION['cart']),
    ], 'storefront');
});

$router->get('/search', static function (App\Core\Application $app) use ($buildStorefrontBrowseData): string {
    $browse = $buildStorefrontBrowseData($app, $_GET);

    return App\Core\View::render($app, 'search-results', [
        'pageTitle' => 'Search Results',
        'products' => $browse['productService']->searchStorefrontProducts($browse['criteria']),
        'filters' => $browse['criteria'],
        'categoryOptions' => $browse['categoryOptions'],
        'occasionOptions' => $browse['occasionOptions'],
        'sortOptions' => $browse['sortOptions'],
    ], 'storefront');
});

$router->get('/account', 'Account\\DashboardController@index');
$router->get('/account/login', 'Account\\AuthController@showLogin');
$router->post('/account/login', 'Account\\AuthController@login');
$router->get('/account/register', 'Account\\AuthController@showRegister');
$router->post('/account/register', 'Account\\AuthController@register');
$router->get('/account/forgot-password', 'Account\\AuthController@showForgotPassword');
$router->post('/account/forgot-password', 'Account\\AuthController@forgotPassword');
$router->get('/account/reset-password', 'Account\\AuthController@showResetPassword');
$router->post('/account/reset-password', 'Account\\AuthController@resetPassword');
$router->get('/account/profile', 'Account\\ProfileController@showProfile');
$router->post('/account/profile', 'Account\\ProfileController@updateProfile');
$router->get('/account/password', 'Account\\ProfileController@showPassword');
$router->post('/account/password', 'Account\\ProfileController@updatePassword');
$router->get('/account/email-preferences', 'Account\\PreferenceController@showEmailPreferences');
$router->post('/account/email-preferences', 'Account\\PreferenceController@updateEmailPreferences');
$router->get('/account/orders', 'Account\\OrderController@index');
$router->get('/account/orders/view', 'Account\\OrderController@show');
$router->get('/account/addresses', 'Account\\AddressController@index');
$router->post('/account/addresses/create', 'Account\\AddressController@create');
$router->post('/account/addresses/update', 'Account\\AddressController@update');
$router->post('/account/addresses/delete', 'Account\\AddressController@delete');
$router->post('/account/addresses/default', 'Account\\AddressController@setDefault');
$router->get('/account/reminders', 'Account\\ReminderController@index');
$router->post('/account/reminders/create', 'Account\\ReminderController@create');
$router->post('/account/reminders/update', 'Account\\ReminderController@update');
$router->post('/account/reminders/delete', 'Account\\ReminderController@delete');
$router->post('/account/reminders/toggle', 'Account\\ReminderController@toggle');
$router->post('/account/logout', 'Account\\AuthController@logout');

$router->get('/order-status', static function (App\Core\Application $app) use ($renderOrderStatusPage): string {
    App\Core\CSRF::token();

    return $renderOrderStatusPage($app);
});

$router->post('/order-status', static function (App\Core\Application $app) use ($renderOrderStatusPage): string {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        return $renderOrderStatusPage($app, [], null, [], null, 'The form session expired. Please try again.');
    }

    $orderService = new App\Services\OrderService($app);
    $paymentService = new App\Services\PaymentService($app);
    $lookup = $orderService->normalizePublicLookupInput($_POST);
    $validationError = $orderService->validatePublicLookupInput($lookup);

    if ($validationError !== null) {
        return $renderOrderStatusPage($app, $lookup, null, [], null, $validationError);
    }

    $order = $orderService->findOrderForPublicLookup($lookup);

    if ($order === null) {
        return $renderOrderStatusPage($app, $lookup, null, [], null, null, null, true);
    }

    $payment = $paymentService->findLatestPaymentByOrderId((int) ($order['id'] ?? 0));
    $paymentStatus = ucfirst(str_replace('_', ' ', (string) ($payment['status'] ?? 'pending')));

    return $renderOrderStatusPage(
        $app,
        $lookup,
        $order,
        $orderService->listItemsByOrderId((int) ($order['id'] ?? 0)),
        $paymentStatus,
        null,
        'Order found.',
        true
    );
});

$router->get('/product', static function (App\Core\Application $app): string {
    $productService = new App\Services\ProductService($app);
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $product = $slug !== '' ? $productService->findStorefrontProductBySlug($slug) : null;
    App\Core\CSRF::token();

    $cartError = $_SESSION['_cart_flash']['error'] ?? null;
    unset($_SESSION['_cart_flash']['error']);

    if ($product === null) {
        http_response_code(404);

        return App\Core\View::render($app, 'product-detail', [
            'pageTitle' => 'Product Not Found',
            'product' => null,
            'cartError' => is_string($cartError) ? $cartError : null,
        ], 'storefront');
    }

    return App\Core\View::render($app, 'product-detail', [
        'pageTitle' => (string) ($product['name'] ?? 'Product'),
        'product' => $product,
        'cartError' => is_string($cartError) ? $cartError : null,
    ], 'storefront');
});

$router->get('/cart', static function (App\Core\Application $app) use ($consumePromoFlash, $buildPromoState): string {
    $productService = new App\Services\ProductService($app);
    App\Core\CSRF::token();

    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        $cart = [];
    }

    $summary = $productService->summarizeCart($cart);
    $promoState = $buildPromoState($app, $summary);
    $promoFlash = $consumePromoFlash('_cart_promo_flash');
    $success = $_SESSION['_cart_flash']['success'] ?? null;
    $error = $_SESSION['_cart_flash']['error'] ?? null;
    unset($_SESSION['_cart_flash']);

    return App\Core\View::render($app, 'cart', [
        'pageTitle' => 'Your Cart',
        'cartItems' => $summary['items'],
        'subtotal' => $summary['subtotal'],
        'itemCount' => $summary['item_count'],
        'success' => is_string($success) ? $success : null,
        'error' => is_string($error) ? $error : null,
        'promoSuccess' => is_string($promoFlash['success'] ?? null) ? $promoFlash['success'] : null,
        'promoError' => is_string($promoFlash['error'] ?? null) ? $promoFlash['error'] : (is_string($promoState['error'] ?? null) ? $promoState['error'] : null),
        'appliedPromo' => is_array($promoState['promo'] ?? null) ? $promoState['promo'] : null,
    ], 'storefront');
});

$router->get('/checkout', static function (App\Core\Application $app) use ($consumePromoFlash, $buildPromoState, $buildCheckoutReturnPath): string {
    $productService = new App\Services\ProductService($app);
    $orderService = new App\Services\OrderService($app);
    $customerAuthService = new App\Services\CustomerAuthService($app);
    $customerService = new App\Services\CustomerService($app);
    $customerAddressService = new App\Services\CustomerAddressService($app);
    App\Core\CSRF::token();

    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        $cart = [];
    }

    $summary = $productService->summarizeCart($cart);
    $promoState = $buildPromoState($app, $summary);
    $appliedPromo = is_array($promoState['promo'] ?? null) ? $promoState['promo'] : null;
    $oldInput = $_SESSION['_checkout_old'] ?? [];
    $error = $_SESSION['_checkout_flash']['error'] ?? null;
    $success = $_SESSION['_checkout_flash']['success'] ?? null;
    $orderNumber = $_SESSION['_checkout_flash']['order_number'] ?? null;
    $promoFlash = $consumePromoFlash('_checkout_promo_flash');
    unset($_SESSION['_checkout_old'], $_SESSION['_checkout_flash']);

    if ($summary['items'] === [] && !is_string($success)) {
        $_SESSION['_cart_flash']['error'] = 'Your cart is empty. Add an arrangement before checkout.';
        header('Location: /cart');
        exit;
    }

    $checkoutFlow = trim((string) ($_GET['flow'] ?? ''));
    $sameDayCheckout = $checkoutFlow === 'same-day';
    $customer = $customerAuthService->customer();

    if (!is_array($customer)) {
        return App\Core\View::render($app, 'checkout-account-required', [
            'pageTitle' => 'Sign In To Checkout',
            'cartItems' => $summary['items'],
            'subtotal' => $summary['subtotal'],
            'itemCount' => $summary['item_count'],
            'sameDayCheckout' => $sameDayCheckout,
            'returnTo' => $buildCheckoutReturnPath($checkoutFlow),
            'error' => is_string($error) ? $error : null,
        ], 'storefront');
    }

    if (!is_array($oldInput) || $oldInput === []) {
        $oldInput = $customerService->checkoutPrefillData($customer);

        $defaultAddress = $customerAddressService->findDefaultForCustomer((int) ($customer['id'] ?? 0));

        if (is_array($defaultAddress)) {
            $oldInput = array_merge($oldInput, [
                'recipient_name' => (string) ($defaultAddress['recipient_name'] ?? ''),
                'delivery_address' => (string) ($defaultAddress['delivery_address'] ?? ''),
                'delivery_zip' => (string) ($defaultAddress['delivery_zip'] ?? ''),
                'delivery_instructions' => (string) ($defaultAddress['delivery_instructions'] ?? ''),
            ]);
        }
    }

    $deliveryZip = is_array($oldInput) ? trim((string) ($oldInput['delivery_zip'] ?? '')) : '';
    $tipAmount = is_array($oldInput) ? (float) ($oldInput['tip_amount'] ?? '0.00') : 0.0;
    $pricing = $orderService->calculatePricing($summary, $deliveryZip, $tipAmount, $appliedPromo);

    return App\Core\View::render($app, 'checkout', [
        'pageTitle' => 'Checkout',
        'cartItems' => $summary['items'],
        'subtotal' => $summary['subtotal'],
        'itemCount' => $summary['item_count'],
        'error' => is_string($error) ? $error : null,
        'success' => is_string($success) ? $success : null,
        'orderNumber' => is_string($orderNumber) ? $orderNumber : null,
        'formData' => is_array($oldInput) ? $oldInput : [],
        'promoSuccess' => is_string($promoFlash['success'] ?? null) ? $promoFlash['success'] : null,
        'promoError' => is_string($promoFlash['error'] ?? null) ? $promoFlash['error'] : (is_string($promoState['error'] ?? null) ? $promoState['error'] : null),
        'appliedPromo' => $appliedPromo,
        'deliverySlots' => $orderService->allowedDeliverySlots(),
        'tipOptions' => $orderService->allowedTipAmounts(),
        'serviceableZips' => $orderService->allowedDeliveryZips(),
        'sameDayCutoff' => $orderService->sameDayCutoff(),
        'deliveryFee' => $pricing['delivery_fee'],
        'taxAmount' => $pricing['tax_amount'],
        'tipAmount' => $pricing['tip_amount'],
        'totalAmount' => $pricing['total_amount'],
        'taxRate' => $orderService->taxRate(),
        'sameDayCheckout' => $sameDayCheckout,
    ], 'storefront');
});

$router->post('/checkout', static function (App\Core\Application $app) use ($buildCheckoutReturnPath): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['_checkout_flash']['error'] = 'The form session expired. Please try again.';
        $checkoutFlow = trim((string) ($_POST['checkout_flow'] ?? $_GET['flow'] ?? ''));
        header('Location: ' . $buildCheckoutReturnPath($checkoutFlow));
        exit;
    }

    $productService = new App\Services\ProductService($app);
    $orderService = new App\Services\OrderService($app);
    $paymentService = new App\Services\PaymentService($app);
    $promoService = new App\Services\PromoService($app);
    $customerAuthService = new App\Services\CustomerAuthService($app);
    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart)) {
        $cart = [];
    }

    $summary = $productService->summarizeCart($cart);

    if ($summary['items'] === []) {
        $_SESSION['_cart_flash']['error'] = 'Your cart is empty. Add an arrangement before checkout.';
        header('Location: /cart');
        exit;
    }

    $checkoutFlow = trim((string) ($_POST['checkout_flow'] ?? $_GET['flow'] ?? ''));
    $customer = $customerAuthService->customer();

    if (!is_array($customer)) {
        $_SESSION['_checkout_flash']['error'] = $checkoutFlow === 'same-day'
            ? 'An account is required before you can complete an urgent same-day order. Sign in or create an account to continue to checkout.'
            : 'An account is required before you can continue to checkout. Sign in or create an account to place the order.';
        header('Location: ' . $buildCheckoutReturnPath($checkoutFlow));
        exit;
    }

    $input = $orderService->normalizeCheckoutInput($_POST);
    $input['customer_id'] = is_array($customer) ? (string) ((int) ($customer['id'] ?? 0)) : '';
    $_SESSION['_checkout_old'] = $input;
    $validationError = $orderService->validateCheckoutInput($input);

    if ($validationError !== null) {
        $_SESSION['_checkout_flash']['error'] = $validationError;
        header('Location: ' . $buildCheckoutReturnPath($checkoutFlow));
        exit;
    }

    $promoState = $promoService->getAppliedPromoForCart($summary);

    if ($promoState['error'] !== null) {
        $_SESSION['_checkout_promo_flash']['error'] = $promoState['error'];
        header('Location: ' . $buildCheckoutReturnPath($checkoutFlow));
        exit;
    }

    $pendingReminder = $_SESSION['reminder_draft'] ?? null;

    if (is_array($pendingReminder) && (int) ($pendingReminder['customer_id'] ?? 0) !== (int) ($input['customer_id'] ?? 0)) {
        unset($_SESSION['reminder_draft']);
        $pendingReminder = null;
    }

    try {
        $orderPayload = $orderService->buildOrderPayload(
            $input,
            $summary,
            is_array($promoState['promo'] ?? null) ? $promoState['promo'] : null
        );
        $order = $orderService->createOrder($orderPayload, $summary['items']);
        $payment = $paymentService->createPendingPaymentForOrder(
            (int) $order['id'],
            (float) ($orderPayload['total_amount'] ?? 0)
        );
    } catch (\Throwable $exception) {
        $_SESSION['_checkout_flash']['error'] = 'Unable to create your order right now. Please try again.';
        header('Location: ' . $buildCheckoutReturnPath($checkoutFlow));
        exit;
    }

    if (is_array($pendingReminder) && (int) ($pendingReminder['customer_id'] ?? 0) === (int) ($orderPayload['customer_id'] ?? 0)) {
        if ((int) ($pendingReminder['product_id'] ?? 0) <= 0 && isset($summary['items'][0]) && is_array($summary['items'][0])) {
            $_SESSION['reminder_draft']['product_id'] = (int) ($summary['items'][0]['product_id'] ?? 0);
            $_SESSION['reminder_draft']['product_slug'] = (string) ($summary['items'][0]['product_slug'] ?? '');
            $_SESSION['reminder_draft']['variant_id'] = (int) ($summary['items'][0]['variant_id'] ?? 0);
        }

        $_SESSION['reminder_draft']['order_id'] = (int) $order['id'];
        $_SESSION['reminder_draft']['order_number'] = (string) $order['order_number'];
        $_SESSION['reminder_draft']['access_token'] = (string) $order['public_access_token'];
    }

    unset($_SESSION['cart'], $_SESSION['_checkout_old']);
    $promoService->clearSessionCode();
    $_SESSION['_payment_flash']['success'] = 'Order created. Payment is still a placeholder and no real charge has been attempted.';
    $_SESSION['_payment_flash']['order_number'] = $order['order_number'];
    $_SESSION['_payment_flash']['access_token'] = $order['public_access_token'];
    $_SESSION['_payment_flash']['info'] = is_array($pendingReminder)
        ? 'Complete a successful payment to confirm the order and create the reminder.'
        : 'Complete a successful payment to finalize the order confirmation flow.';

    header('Location: /payment?' . http_build_query([
        'reference' => (string) $payment['payment_reference'],
        'token' => (string) $order['public_access_token'],
    ]));
    exit;
});

$router->get('/payment', static function (App\Core\Application $app): string {
    App\Core\CSRF::token();

    $paymentService = new App\Services\PaymentService($app);
    $orderService = new App\Services\OrderService($app);
    $reference = trim((string) ($_GET['reference'] ?? ''));
    $success = $_SESSION['_payment_flash']['success'] ?? null;
    $error = $_SESSION['_payment_flash']['error'] ?? null;
    $info = $_SESSION['_payment_flash']['info'] ?? null;
    $orderNumber = $_SESSION['_payment_flash']['order_number'] ?? null;
    $accessToken = $_SESSION['_payment_flash']['access_token'] ?? null;
    unset($_SESSION['_payment_flash']);
    $payment = $reference !== '' ? $paymentService->findPaymentByReference($reference) : null;
    $order = null;
    $token = trim((string) ($_GET['token'] ?? ''));

    if ($payment !== null) {
        $order = $orderService->findOrderById((int) ($payment['order_id'] ?? 0));
    }

    if (
        $payment === null
        || $order === null
        || !$orderService->hasValidPublicAccessToken($order, $token)
    ) {
        http_response_code(404);

        return App\Core\View::render($app, 'payment', [
            'pageTitle' => 'Payment Not Found',
            'payment' => null,
            'order' => null,
            'success' => is_string($success) ? $success : null,
            'error' => is_string($error) ? $error : null,
            'info' => is_string($info) ? $info : null,
            'orderNumber' => is_string($orderNumber) ? $orderNumber : null,
            'accessToken' => is_string($accessToken) ? $accessToken : null,
            'requestedReference' => $reference,
            'requestedToken' => $token,
            'simulationOptions' => [],
        ], 'storefront');
    }

    return App\Core\View::render($app, 'payment', [
        'pageTitle' => 'Payment',
        'payment' => $payment,
        'order' => $order,
        'items' => $orderService->listItemsByOrderId((int) ($order['id'] ?? 0)),
        'success' => is_string($success) ? $success : null,
        'error' => is_string($error) ? $error : null,
        'info' => is_string($info) ? $info : null,
        'orderNumber' => is_string($orderNumber) ? $orderNumber : null,
        'accessToken' => $orderService->publicAccessToken($order),
        'simulationOptions' => $paymentService->simulationTargetsForStatus((string) ($payment['status'] ?? '')),
    ], 'storefront');
    exit;
});

$router->post('/payment/simulate', static function (App\Core\Application $app): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['_payment_flash']['error'] = 'The form session expired. Please try again.';
        $redirectReference = trim((string) ($_POST['reference'] ?? ''));
        $redirectToken = trim((string) ($_POST['token'] ?? ''));
        header('Location: /payment?' . http_build_query([
            'reference' => $redirectReference,
            'token' => $redirectToken,
        ]));
        exit;
    }

    $paymentService = new App\Services\PaymentService($app);
    $orderService = new App\Services\OrderService($app);
    $notificationService = new App\Services\NotificationService($app);
    $reminderService = new App\Services\CustomerReminderService($app);
    $reference = trim((string) ($_POST['reference'] ?? ''));
    $token = trim((string) ($_POST['token'] ?? ''));
    $targetStatus = trim((string) ($_POST['status'] ?? ''));
    $payment = $reference !== '' ? $paymentService->findPaymentByReference($reference) : null;
    $order = null;

    if ($payment !== null) {
        $order = $orderService->findOrderById((int) ($payment['order_id'] ?? 0));
    }

    if ($payment === null || $order === null || !$orderService->hasValidPublicAccessToken($order, $token)) {
        $_SESSION['_payment_flash']['error'] = 'Payment record not found.';
        header('Location: /payment?' . http_build_query([
            'reference' => $reference,
            'token' => $token,
        ]));
        exit;
    }

    $currentStatus = (string) ($payment['status'] ?? 'pending');

    if (!$paymentService->isValidSimulatedStatus($targetStatus)) {
        $_SESSION['_payment_flash']['error'] = 'Invalid simulated payment status.';
        header('Location: /payment?' . http_build_query([
            'reference' => $reference,
            'token' => $token,
        ]));
        exit;
    }

    if (!$paymentService->canSimulateTransition($currentStatus, $targetStatus)) {
        $_SESSION['_payment_flash']['error'] = 'This simulated transition is not allowed. Only pending or authorized payments can move to paid or failed.';
        header('Location: /payment?' . http_build_query([
            'reference' => $reference,
            'token' => $token,
        ]));
        exit;
    }

    $providerReference = $paymentService->generateSimulationProviderReference($targetStatus);
    $failureMessage = null;

    if ($targetStatus === 'failed') {
        $failureMessage = 'Local QA simulation marked this placeholder payment as failed.';
    } elseif ($targetStatus === 'cancelled') {
        $failureMessage = 'Local QA simulation marked this placeholder payment as cancelled.';
    }

    try {
        $paymentService->updatePaymentStatus(
            (int) ($payment['id'] ?? 0),
            $targetStatus,
            $providerReference,
            $failureMessage
        );
        $consistencyMessage = $orderService->synchronizeStatusForPayment((int) ($order['id'] ?? 0), $targetStatus);
    } catch (\Throwable $exception) {
        $_SESSION['_payment_flash']['error'] = 'Unable to simulate the placeholder payment status right now.';
        header('Location: /payment?' . http_build_query([
            'reference' => $reference,
            'token' => $token,
        ]));
        exit;
    }

    $_SESSION['_payment_flash']['success'] = 'Placeholder payment status updated to ' . $targetStatus . ' for local QA.';
    $_SESSION['_payment_flash']['order_number'] = $order['order_number'];
    $_SESSION['_payment_flash']['access_token'] = $orderService->publicAccessToken($order);

    $infoMessages = [];

    if (is_string($consistencyMessage) && $consistencyMessage !== '') {
        $infoMessages[] = $consistencyMessage;
    }

    if ($targetStatus === 'paid') {
        $_SESSION['_order_confirmation_flash']['success'] = 'Payment completed. Your order is now confirmed.';

        try {
            $notificationSummary = $notificationService->sendOrderConfirmationNotifications((int) ($order['id'] ?? 0));
            $infoMessages[] = 'Order confirmation notifications processed in ' . $notificationSummary['delivery_mode']
                . ' mode. Customer: ' . $notificationSummary['customer_notification']
                . '. Store: ' . $notificationSummary['store_notification'] . '.';
        } catch (\Throwable $exception) {
            $infoMessages[] = 'Order confirmation notification processing could not be completed.';
        }

    $pendingReminder = $_SESSION['reminder_draft'] ?? null;

        if (is_array($pendingReminder) && (int) ($pendingReminder['order_id'] ?? 0) === (int) ($order['id'] ?? 0)) {
            $reminderResult = $reminderService->createForPaidOrderFromDraft(
                (int) ($pendingReminder['customer_id'] ?? 0),
                (int) ($pendingReminder['order_id'] ?? 0),
                $pendingReminder
            );

            if ($reminderResult['success'] && is_array($reminderResult['reminder'] ?? null)) {
                $reminder = $reminderResult['reminder'];
                $infoMessages[] = !empty($reminderResult['already_exists'])
                    ? 'Reminder already exists for this paid order.'
                    : 'Reminder created for ' . (string) ($reminder['reminder_date'] ?? '') . ' and linked to order ' . (string) ($reminder['order_number'] ?? '') . '.';

                try {
                    $reminderNotificationSummary = $notificationService->sendReminderConfirmationNotification((int) ($reminder['id'] ?? 0));
                    $infoMessages[] = 'Reminder confirmation notification processed in ' . $reminderNotificationSummary['delivery_mode']
                        . ' mode. Customer: ' . $reminderNotificationSummary['customer_notification'] . '.';
                } catch (\Throwable $exception) {
                    $infoMessages[] = 'Reminder confirmation notification processing could not be completed.';
                }

                unset($_SESSION['reminder_draft']);
            } else {
                $infoMessages[] = (string) ($reminderResult['error'] ?? 'Reminder was not created because the paid reminder requirements were not met.');
            }
        }
    }

    if ($infoMessages !== []) {
        $combinedInfo = implode(' ', $infoMessages);
        $_SESSION['_payment_flash']['info'] = $combinedInfo;
        $_SESSION['_order_confirmation_flash']['info'] = $combinedInfo;
    }

    header('Location: /payment?' . http_build_query([
        'reference' => $reference,
        'token' => $token,
    ]));
    exit;
});

$router->get('/order-confirmation', static function (App\Core\Application $app): string {
    App\Core\CSRF::token();

    $orderService = new App\Services\OrderService($app);
    $paymentService = new App\Services\PaymentService($app);
    $orderNumber = trim((string) ($_GET['number'] ?? ''));
    $token = trim((string) ($_GET['token'] ?? ''));
    $success = $_SESSION['_order_confirmation_flash']['success'] ?? null;
    $info = $_SESSION['_order_confirmation_flash']['info'] ?? null;
    unset($_SESSION['_order_confirmation_flash']);
    $order = $orderNumber !== '' ? $orderService->findOrderByNumber($orderNumber) : null;

    if ($order === null || !$orderService->hasValidPublicAccessToken($order, $token)) {
        http_response_code(404);

        return App\Core\View::render($app, 'order-confirmation', [
            'pageTitle' => 'Order Not Found',
            'order' => null,
            'items' => [],
            'payment' => null,
            'success' => is_string($success) ? $success : null,
            'info' => is_string($info) ? $info : null,
            'requestedOrderNumber' => $orderNumber,
            'requestedToken' => $token,
        ], 'storefront');
    }

    $orderId = (int) ($order['id'] ?? 0);
    $items = $orderService->listItemsByOrderId($orderId);
    $payment = $paymentService->findLatestPaymentByOrderId($orderId);

    return App\Core\View::render($app, 'order-confirmation', [
        'pageTitle' => 'Order Confirmation',
        'order' => $order,
        'items' => $items,
        'payment' => $payment,
        'success' => is_string($success) ? $success : null,
        'info' => is_string($info) ? $info : null,
        'accessToken' => $orderService->publicAccessToken($order),
        'publicTracking' => $orderService->publicTrackingSummary($order),
    ], 'storefront');
});

$router->post('/cart/promo/apply', static function (App\Core\Application $app) use ($handlePromoApply): never {
    $handlePromoApply($app, '/cart', '_cart_promo_flash');
});

$router->post('/cart/promo/remove', static function (App\Core\Application $app) use ($handlePromoRemove): never {
    $handlePromoRemove($app, '/cart', '_cart_promo_flash');
});

$router->post('/checkout/promo/apply', static function (App\Core\Application $app) use ($handlePromoApply): never {
    $handlePromoApply($app, '/checkout', '_checkout_promo_flash');
});

$router->post('/checkout/promo/remove', static function (App\Core\Application $app) use ($handlePromoRemove): never {
    $handlePromoRemove($app, '/checkout', '_checkout_promo_flash');
});

$router->post('/cart/add', static function (App\Core\Application $app): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['_cart_flash']['error'] = 'The form session expired. Please try again.';
        $redirectSlug = trim((string) ($_POST['product_slug'] ?? ''));
        header('Location: /product?slug=' . urlencode($redirectSlug));
        exit;
    }

    $productService = new App\Services\ProductService($app);
    $productSlug = trim((string) ($_POST['product_slug'] ?? ''));
    $variantId = (int) ($_POST['variant_id'] ?? 0);
    $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $addonIds = $_POST['addon_ids'] ?? [];

    if (!is_array($addonIds)) {
        $addonIds = [];
    }

    $addonIds = array_map(static fn (mixed $addonId): int => (int) $addonId, $addonIds);
    $item = $productService->buildCartItemFromSelection($productSlug, $variantId, $quantity, $addonIds);

    if ($item === null) {
        $_SESSION['_cart_flash']['error'] = 'Unable to add that product to the cart. Please select a valid size and add-on combination.';
        header('Location: /product?slug=' . urlencode($productSlug));
        exit;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $itemKey = (string) $item['key'];

    if (isset($_SESSION['cart'][$itemKey]) && is_array($_SESSION['cart'][$itemKey])) {
        $_SESSION['cart'][$itemKey]['quantity'] = max(
            1,
            (int) ($_SESSION['cart'][$itemKey]['quantity'] ?? 1) + $quantity
        );
    } else {
        $_SESSION['cart'][$itemKey] = $item;
    }

    if (isset($_SESSION['reminder_draft']) && is_array($_SESSION['reminder_draft'])) {
        $_SESSION['reminder_draft']['product_id'] = (int) ($item['product_id'] ?? 0);
        $_SESSION['reminder_draft']['product_slug'] = (string) ($item['product_slug'] ?? '');
        $_SESSION['reminder_draft']['variant_id'] = (int) ($item['variant_id'] ?? 0);
    }

    $_SESSION['_cart_flash']['success'] = 'Item added to cart.';
    header('Location: /cart');
    exit;
});

$router->post('/cart/update', static function (App\Core\Application $app): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['_cart_flash']['error'] = 'The form session expired. Please try again.';
        header('Location: /cart');
        exit;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['_cart_flash']['error'] = 'Your cart is empty.';
        header('Location: /cart');
        exit;
    }

    $itemKey = (string) ($_POST['item_key'] ?? '');
    $quantity = (int) ($_POST['quantity'] ?? 0);

    if ($itemKey === '' || !isset($_SESSION['cart'][$itemKey]) || !is_array($_SESSION['cart'][$itemKey])) {
        $_SESSION['_cart_flash']['error'] = 'Cart item not found.';
        header('Location: /cart');
        exit;
    }

    if ($quantity <= 0) {
        unset($_SESSION['cart'][$itemKey]);
        $_SESSION['_cart_flash']['success'] = 'Item removed from cart.';
        header('Location: /cart');
        exit;
    }

    $_SESSION['cart'][$itemKey]['quantity'] = $quantity;
    $_SESSION['_cart_flash']['success'] = 'Cart updated.';
    header('Location: /cart');
    exit;
});

$router->post('/cart/remove', static function (App\Core\Application $app): never {
    App\Core\CSRF::token();

    if (!App\Core\CSRF::validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['_cart_flash']['error'] = 'The form session expired. Please try again.';
        header('Location: /cart');
        exit;
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['_cart_flash']['error'] = 'Your cart is empty.';
        header('Location: /cart');
        exit;
    }

    $itemKey = (string) ($_POST['item_key'] ?? '');

    if ($itemKey === '' || !isset($_SESSION['cart'][$itemKey])) {
        $_SESSION['_cart_flash']['error'] = 'Cart item not found.';
        header('Location: /cart');
        exit;
    }

    unset($_SESSION['cart'][$itemKey]);
    $_SESSION['_cart_flash']['success'] = 'Item removed from cart.';
    header('Location: /cart');
    exit;
});

$router->get('/contact', static function (App\Core\Application $app): string {
    return App\Core\View::render($app, 'contact', [
        'pageTitle' => 'Contact',
    ], 'storefront');
});
