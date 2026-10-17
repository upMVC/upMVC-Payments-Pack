<?php

namespace BitsHost\UpmvcPayments\Gateway;

/**
 * Cash / pay-at-counter / COD — no processor.
 * Stays `pending` until staff marks paid (PATCH status) so the ledger stays honest.
 */
final class CashGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'cash';
    }

    public function createPayment(array $input): array
    {
        $ref = 'cash_' . bin2hex(random_bytes(8));
        return [
            'status'        => 'pending',
            'provider_ref'  => $ref,
            'client_secret' => null,
            'raw'           => [
                'driver'       => 'cash',
                'amount_cents' => (int) $input['amount_cents'],
                'currency'     => strtoupper((string) $input['currency']),
                'instructions' => 'Collect cash and mark this payment as succeeded when received.',
            ],
        ];
    }

    public function retrievePayment(string $providerRef): array
    {
        return [
            'status'       => 'pending',
            'provider_ref' => $providerRef,
            'raw'          => ['driver' => 'cash'],
        ];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        return null;
    }
}
