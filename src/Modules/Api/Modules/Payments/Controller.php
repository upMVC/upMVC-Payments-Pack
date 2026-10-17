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

        try {
            $gateway = GatewayResolver::driver();
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
}
