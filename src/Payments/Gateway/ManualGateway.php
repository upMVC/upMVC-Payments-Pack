<?php

namespace BitsHost\UpmvcPayments\Gateway;

/**
 * Dev / offline driver — records a pending payment without calling a processor.
 * Useful for local smoke tests and products that collect bank transfer offline.
 */
final class ManualGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function createPayment(array $input): array
    {
        $ref = 'manual_' . bin2hex(random_bytes(8));
        return [
            'status'        => 'pending',
            'provider_ref'  => $ref,
            'client_secret' => null,
            'raw'           => [
                'driver'       => 'manual',
                'amount_cents' => (int) $input['amount_cents'],
                'currency'     => strtoupper((string) $input['currency']),
            ],
        ];
    }

    public function retrievePayment(string $providerRef): array
    {
        return [
            'status'       => 'pending',
            'provider_ref' => $providerRef,
            'raw'          => ['driver' => 'manual'],
        ];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        return null; // manual has no webhooks
    }
}
