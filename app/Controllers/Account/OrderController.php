<?php

declare(strict_types=1);

namespace App\Controllers\Account;

use App\Services\OrderService;
use App\Services\PaymentService;

final class OrderController extends BaseAccountController
{
    private OrderService $orderService;
    private PaymentService $paymentService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->orderService = new OrderService($app);
        $this->paymentService = new PaymentService($app);
    }

    public function index(): string
    {
        $this->requireCustomer();

        $customer = $this->authService->customer();

        return $this->renderStorefront('account-orders', [
            'pageTitle' => 'My Orders',
            'orders' => is_array($customer) ? $this->orderService->listOrdersForCustomer($customer) : [],
        ]);
    }

    public function show(): string
    {
        $this->requireCustomer();

        $customer = $this->authService->customer();
        $orderId = (int) ($_GET['id'] ?? 0);
        $order = is_array($customer) ? $this->orderService->findOrderForCustomerById($customer, $orderId) : null;

        if ($order === null) {
            http_response_code(404);

            return $this->renderStorefront('account-order-detail', [
                'pageTitle' => 'Order Not Found',
                'order' => null,
                'items' => [],
                'payment' => null,
                'publicTracking' => null,
            ]);
        }

        return $this->renderStorefront('account-order-detail', [
            'pageTitle' => 'Order ' . (string) ($order['order_number'] ?? ''),
            'order' => $order,
            'items' => $this->orderService->listItemsByOrderId((int) ($order['id'] ?? 0)),
            'payment' => $this->paymentService->findLatestPaymentByOrderId((int) ($order['id'] ?? 0)),
            'publicTracking' => $this->orderService->publicTrackingSummary($order),
        ]);
    }
}
