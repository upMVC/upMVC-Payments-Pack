<?php

namespace BitsHost\UpmvcPayments\Gateway;

use App\Etc\Config\Environment;

/**
 * Offline bank / wire transfer — pending until funds clear and staff (or reconciliation) marks succeeded.
 * Bank details come from host .env (never hard-code IBANs in the pack).
 */
final class BankTransferGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'bank_transfer';
    }

    public function createPayment(array $input): array
    {
        $ref = 'bank_' . bin2hex(random_bytes(8));
        $amount = (int) $input['amount_cents'];
        $currency = strtoupper((string) $input['currency']);

        return [
            'status'        => 'pending',
            'provider_ref'  => $ref,
            'client_secret' => null,
            'raw'           => [
                'driver'       => 'bank_transfer',
                'amount_cents' => $amount,
                'currency'     => $currency,
                'instructions' => $this->buildInstructions($ref, $amount, $currency),
                'bank'         => $this->bankDetails(),
            ],
        ];
    }

    public function retrievePayment(string $providerRef): array
    {
        return [
            'status'       => 'pending',
            'provider_ref' => $providerRef,
            'raw'          => ['driver' => 'bank_transfer'],
        ];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        return null;
    }

    /** @return array{iban:?string,bic:?string,beneficiary:?string,bank_name:?string} */
    private function bankDetails(): array
    {
        return [
            'iban'        => $this->env('PAYMENTS_BANK_IBAN'),
            'bic'         => $this->env('PAYMENTS_BANK_BIC'),
            'beneficiary' => $this->env('PAYMENTS_BANK_BENEFICIARY'),
            'bank_name'   => $this->env('PAYMENTS_BANK_NAME'),
        ];
    }

    private function buildInstructions(string $ref, int $amountCents, string $currency): string
    {
        $iban = $this->env('PAYMENTS_BANK_IBAN') ?? '(configure PAYMENTS_BANK_IBAN)';
        $maj = number_format($amountCents / 100, 2, '.', '');
        return "Transfer {$maj} {$currency} to {$iban} with reference {$ref}. Payment stays pending until confirmed.";
    }

    private function env(string $key): ?string
    {
        $v = (string) (
            getenv($key)
            ?: ($_ENV[$key] ?? '')
            ?: (class_exists(Environment::class) ? (string) Environment::get($key, '') : '')
        );
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
