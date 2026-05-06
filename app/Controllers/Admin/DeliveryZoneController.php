<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\CSRF;
use App\Services\OrderService;

final class DeliveryZoneController extends BaseAdminController
{
    private OrderService $orderService;

    public function __construct(\App\Core\Application $app)
    {
        parent::__construct($app);
        $this->orderService = new OrderService($app);
    }

    public function index(): string
    {
        $this->requireAdmin();

        return $this->renderAdmin('admin-delivery-zones', [
            'pageTitle' => 'Delivery Zones',
            'error' => $this->consumeFlash('error'),
            'success' => $this->consumeFlash('success'),
            'zones' => $this->orderService->listDeliveryZones(),
        ]);
    }

    public function create(): string
    {
        $this->requireAdmin();

        return $this->renderForm([
            'pageTitle' => 'Create Delivery Zone',
            'formAction' => '/admin/delivery-zones',
            'indexPath' => '/admin/delivery-zones',
            'zone' => $this->emptyZone(),
            'formMode' => 'create',
        ]);
    }

    public function edit(): string
    {
        $this->requireAdmin();

        $zoneId = (int) ($_GET['id'] ?? 0);
        $zone = $this->orderService->findDeliveryZoneById($zoneId);

        if ($zone === null) {
            $this->flash('error', 'Delivery zone not found.');
            $this->redirect('/admin/delivery-zones');
        }

        return $this->renderForm([
            'pageTitle' => 'Edit Delivery Zone',
            'formAction' => '/admin/delivery-zones/update',
            'indexPath' => '/admin/delivery-zones',
            'zone' => $zone,
            'zoneId' => $zoneId,
            'formMode' => 'edit',
        ]);
    }

    public function store(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/delivery-zones/create');
        }

        $zone = $this->normalizeZone($_POST);
        $validationError = $this->validateZone($zone);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/delivery-zones/create');
        }

        try {
            $this->orderService->createDeliveryZone($zone);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to save delivery zone. Check for a duplicate ZIP code and try again.');
            $this->redirect('/admin/delivery-zones/create');
        }

        $this->flash('success', 'Delivery zone created.');
        $this->redirect('/admin/delivery-zones');
    }

    public function update(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/delivery-zones');
        }

        $zoneId = (int) ($_POST['id'] ?? 0);

        if ($zoneId <= 0 || $this->orderService->findDeliveryZoneById($zoneId) === null) {
            $this->flash('error', 'Delivery zone not found.');
            $this->redirect('/admin/delivery-zones');
        }

        $zone = $this->normalizeZone($_POST);
        $validationError = $this->validateZone($zone);

        if ($validationError !== null) {
            $this->flash('error', $validationError);
            $this->redirect('/admin/delivery-zones/edit?id=' . $zoneId);
        }

        try {
            $this->orderService->updateDeliveryZone($zoneId, $zone);
        } catch (\Throwable $exception) {
            $this->flash('error', 'Unable to update delivery zone. Check for a duplicate ZIP code and try again.');
            $this->redirect('/admin/delivery-zones/edit?id=' . $zoneId);
        }

        $this->flash('success', 'Delivery zone updated.');
        $this->redirect('/admin/delivery-zones');
    }

    public function delete(): string
    {
        $this->requireAdmin();

        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'The form session expired. Please try again.');
            $this->redirect('/admin/delivery-zones');
        }

        $zoneId = (int) ($_POST['id'] ?? 0);

        if ($zoneId <= 0 || $this->orderService->findDeliveryZoneById($zoneId) === null) {
            $this->flash('error', 'Delivery zone not found.');
            $this->redirect('/admin/delivery-zones');
        }

        $this->orderService->deleteDeliveryZone($zoneId);
        $this->flash('success', 'Delivery zone deleted.');
        $this->redirect('/admin/delivery-zones');
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeZone(array $input): array
    {
        $zipCode = preg_replace('/\D+/', '', (string) ($input['zip_code'] ?? '')) ?? '';

        return [
            'zip_code' => substr($zipCode, 0, 5),
            'delivery_fee' => trim((string) ($input['delivery_fee'] ?? '0')),
            'is_active' => isset($input['is_active']) ? 1 : 0,
        ];
    }

    /**
     * @param array<string, mixed> $zone
     */
    private function validateZone(array $zone): ?string
    {
        if ((string) ($zone['zip_code'] ?? '') === '' || preg_match('/^\d{5}$/', (string) $zone['zip_code']) !== 1) {
            return 'ZIP code must be exactly 5 digits.';
        }

        if (!is_numeric((string) ($zone['delivery_fee'] ?? '')) || (float) $zone['delivery_fee'] < 0) {
            return 'Delivery fee must be a valid non-negative number.';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyZone(): array
    {
        return [
            'zip_code' => '',
            'delivery_fee' => '0.00',
            'is_active' => 1,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderForm(array $data): string
    {
        return $this->renderAdmin('admin-delivery-zone-form', array_merge([
            'error' => $this->consumeFlash('error'),
        ], $data));
    }
}
