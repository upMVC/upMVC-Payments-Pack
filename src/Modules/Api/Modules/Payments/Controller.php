<?php

namespace App\Modules\Api\Modules\Payments;

use BitsHost\UpmvcPayments\Gateway\GatewayResolver;
use BitsHost\UpmvcPayments\PaymentRecord;
use BitsHost\UpmvcSaas\Http\SaasApiController;

/**
 * Thin payments API — create / show intents for the JWT tenant.
 * Pricing and “what to charge for” stay in the product app.
 */
class Controller extends SaasApiController
{
    public function create(): void
    {
        $body = $this->body();
        $this->requireFields($body, ['amount_cents', 'currency']);

        $tenantId = (int) ($this->user['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            $this->error('Tenant context required', 403);
            return;
        }

        $amount = (int) $body['amount_cents'];
        if ($amount < 1) {
            $this->error('amount_cents must be >= 1', 422);
            return;
        }

        $currency = strtoupper((string) $body['currency']);
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            $this->error('currency must be ISO-4217 (3 letters)', 422);
            return;
        }

        $driverName = isset($body['driver']) ? (string) $body['driver'] : null;

        try {
            $gateway = GatewayResolver::driver($driverName);
            $result = $gateway->createPayment([
                'tenant_id'      => $tenantId,
                'amount_cents'   => $amount,
                'currency'       => $currency,
                'description'    => isset($body['description']) ? (string) $body['description'] : null,
                'metadata'       => is_array($body['metadata'] ?? null) ? $body['metadata'] : [],
                'customer_email' => isset($body['customer_email']) ? (string) $body['customer_email'] : null,
            ]);
        } catch (\Throwable $e) {
            $this->error('Payment driver error: ' . $e->getMessage(), 500);
            return;
        }

        $id = PaymentRecord::create([
            'tenant_id'    => $tenantId,
            'amount_cents' => $amount,
            'currency'     => $currency,
            'driver'       => $gateway->name(),
            'status'       => (string) ($result['status'] ?? 'pending'),
            'provider_ref' => $result['provider_ref'] ?? null,
            'description'  => isset($body['description']) ? (string) $body['description'] : null,
            'metadata'     => is_array($body['metadata'] ?? null) ? $body['metadata'] : null,
        ]);

        $this->success([
            'id'            => $id,
            'status'        => $result['status'] ?? 'pending',
            'provider_ref'  => $result['provider_ref'] ?? null,
            'client_secret' => $result['client_secret'] ?? null,
            'driver'        => $gateway->name(),
            'raw'           => $result['raw'] ?? null,
        ], 201);
    }

    public function show(int $id): void
    {
        $tenantId = (int) ($this->user['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            $this->error('Tenant context required', 403);
            return;
        }

        $row = PaymentRecord::findForTenant($tenantId, $id);
        if ($row === null) {
            $this->error('Not found', 404);
            return;
        }

        $this->success(['payment' => $row]);
    }

    /**
     * Mark offline payments (cash / bank_transfer / manual) as paid or canceled.
     * Card processors should prefer webhooks — this is for staff confirmation.
     */
    public function updateStatus(int $id): void
    {
        $tenantId = (int) ($this->user['tenant_id'] ?? 0);
        if ($tenantId < 1) {
            $this->error('Tenant context required', 403);
            return;
        }

        $body = $this->body();
        $this->requireFields($body, ['status']);
        $status = strtolower((string) $body['status']);
        $allowed = ['pending', 'succeeded', 'failed', 'canceled', 'refunded'];
        if (!in_array($status, $allowed, true)) {
            $this->error('Invalid status', 422);
            return;
        }

        $row = PaymentRecord::findForTenant($tenantId, $id);
        if ($row === null) {
            $this->error('Not found', 404);
            return;
        }

        if (!PaymentRecord::updateStatus($tenantId, $id, $status)) {
            $this->error('Update failed', 500);
            return;
        }

        $fresh = PaymentRecord::findForTenant($tenantId, $id);
        $this->success(['payment' => $fresh]);
    }

    public function drivers(): void
    {
        $this->success(['drivers' => GatewayResolver::available()]);
    }
}
