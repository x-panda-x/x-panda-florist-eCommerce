<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PrintCardNoteSettingsService;
use App\Services\SettingsService;

final class OrderController extends BaseAdminController
{
    private OrderService $orderService;
    private PaymentService $paymentService;
    private SettingsService $settingsService;
    private PrintCardNoteSettingsService $printCardNoteSettingsService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->orderService = new OrderService($app);
        $this->paymentService = new PaymentService($app);
        $this->settingsService = new SettingsService($app);
        $this->printCardNoteSettingsService = new PrintCardNoteSettingsService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();
        $filters = $this->orderService->normalizeAdminConsoleFilters($_GET);
        $console = $this->orderService->buildAdminOrdersConsole($filters);

        return $this->renderAdmin('admin-orders', [
            'pageTitle' => 'Orders',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'filters' => $console['filters'],
            'summary' => $console['summary'],
            'orders' => $console['orders'],
            'calendar' => $console['calendar'],
            'selectedDayOrders' => $console['selectedDayOrders'],
            'selectedDay' => $console['selectedDay'],
            'statusOptions' => $this->orderService->allowedStatuses(),
            'deliveryTypeOptions' => $console['deliveryTypeOptions'],
        ]);
    }

    public function show(): string
    {
        $this->requireAdmin();

        $orderId = (int) ($_GET['id'] ?? 0);
        $order = $this->orderService->findOrderById($orderId);

        if ($order === null) {
            $this->flash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        return $this->renderAdmin('admin-order-view', [
            'pageTitle' => 'Order ' . (string) ($order['order_number'] ?? ''),
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'order' => $order,
            'items' => $this->orderService->listItemsByOrderId($orderId),
            'payment' => $this->paymentService->findLatestPaymentByOrderId($orderId),
            'statusOptions' => $this->orderService->allowedStatuses(),
        ]);
    }

    public function cardNote(): string
    {
        $this->requireAdmin();

        $orderId = (int) ($_GET['id'] ?? 0);
        $order = $this->orderService->findOrderById($orderId);

        if ($order === null) {
            $this->flash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        return $this->view('admin-order-card-note-print', [
            'pageTitle' => 'Print Card Note ' . (string) ($order['order_number'] ?? ''),
            'order' => $order,
            'items' => $this->orderService->listItemsByOrderId($orderId),
            'store' => [
                'name' => trim((string) $this->settingsService->get('store_name', 'Lily and Rose')),
                'address' => trim((string) $this->settingsService->get('store_address', '')),
                'phone' => trim((string) $this->settingsService->get('store_phone', '')),
                'email' => trim((string) $this->settingsService->get('store_email', '')),
            ],
            'cardText' => $this->printCardNoteSettingsService->effectiveTexts(),
            'autoPrint' => (string) ($_GET['print'] ?? '1') !== '0',
        ]);
    }

    public function updateStatus(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/orders');
        }

        $orderId = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));
        $order = $this->orderService->findOrderById($orderId);

        if ($order === null) {
            $this->flash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        if (!$this->orderService->isValidStatus($status)) {
            $this->flash('error', 'Invalid order status.');
            $this->redirect('/admin/orders/view?id=' . $orderId);
        }

        try {
            $this->orderService->updateOrderStatus($orderId, $status);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update order status.');
            $this->redirect('/admin/orders/view?id=' . $orderId);
        }

        $this->flash('success', 'Order status updated.');
        $this->redirect('/admin/orders/view?id=' . $orderId);
    }

    public function updatePublicTracking(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/orders');
        }

        $orderId = (int) ($_POST['id'] ?? 0);
        $order = $this->orderService->findOrderById($orderId);

        if ($order === null) {
            $this->flash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        $tracking = $this->orderService->normalizePublicTrackingInput($_POST);
        $validationError = $this->orderService->validatePublicTrackingInput($tracking);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/orders/view?id=' . $orderId);
        }

        try {
            $this->orderService->updatePublicTracking($orderId, $tracking);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update public tracking details.');
            $this->redirect('/admin/orders/view?id=' . $orderId);
        }

        $this->flash('success', 'Public tracking details updated.');
        $this->redirect('/admin/orders/view?id=' . $orderId);
    }
}
