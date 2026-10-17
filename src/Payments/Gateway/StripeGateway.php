<?php

namespace BitsHost\UpmvcPayments\Gateway;

use App\Etc\Config\Environment;

/**
 * Stripe driver — optional Composer package `stripe/stripe-php`.
 *
 *   composer require stripe/stripe-php
 *   PAYMENTS_DRIVER=stripe
 *   STRIPE_SECRET_KEY=sk_...
 *   STRIPE_WEBHOOK_SECRET=whsec_...   (for parseWebhook)
 */
final class StripeGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'stripe';
    }

    public function createPayment(array $input): array
    {
        $this->assertSdk();
        $secret = $this->secretKey();
        if ($secret === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY is not set in the environment');
        }

        /** @var class-string $stripe */
        $stripe = 'Stripe\\Stripe';
        $stripe::setApiKey($secret);

        /** @var class-string $paymentIntent */
        $paymentIntent = 'Stripe\\PaymentIntent';
        $intent = $paymentIntent::create([
            'amount'   => (int) $input['amount_cents'],
            'currency' => strtolower((string) $input['currency']),
            'metadata' => array_merge(
                ['tenant_id' => (string) $input['tenant_id']],
                $this->stringifyMeta($input['metadata'] ?? [])
            ),
            'description'   => $input['description'] ?? null,
            'receipt_email' => $input['customer_email'] ?? null,
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        return [
            'status'        => (string) ($intent->status ?? 'requires_action'),
            'provider_ref'  => (string) $intent->id,
            'client_secret' => isset($intent->client_secret) ? (string) $intent->client_secret : null,
            'raw'           => [
                'driver' => 'stripe',
                'id'     => $intent->id,
                'status' => $intent->status,
            ],
        ];
    }

    public function retrievePayment(string $providerRef): array
    {
        $this->assertSdk();
        $secret = $this->secretKey();
        if ($secret === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY is not set in the environment');
        }

        /** @var class-string $stripe */
        $stripe = 'Stripe\\Stripe';
        $stripe::setApiKey($secret);

        /** @var class-string $paymentIntent */
        $paymentIntent = 'Stripe\\PaymentIntent';
        $intent = $paymentIntent::retrieve($providerRef);

        return [
            'status'       => (string) ($intent->status ?? 'unknown'),
            'provider_ref' => (string) $intent->id,
            'raw'          => ['driver' => 'stripe', 'status' => $intent->status],
        ];
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        $this->assertSdk();
        $whSecret = $this->env('STRIPE_WEBHOOK_SECRET');
        if ($whSecret === null) {
            throw new \RuntimeException('STRIPE_WEBHOOK_SECRET is not set');
        }
        $sig = $headers['Stripe-Signature'] ?? $headers['stripe-signature'] ?? '';
        if (is_array($sig)) {
            $sig = (string) ($sig[0] ?? '');
        }

        /** @var class-string $webhook */
        $webhook = 'Stripe\\Webhook';
        $event = $webhook::constructEvent($payload, (string) $sig, $whSecret);
        $obj = $event->data->object ?? null;
        $ref = is_object($obj) && isset($obj->id) ? (string) $obj->id : null;
        $status = is_object($obj) && isset($obj->status) ? (string) $obj->status : null;

        return [
            'event'        => (string) $event->type,
            'provider_ref' => $ref,
            'status'       => $status,
            'raw'          => ['type' => $event->type],
        ];
    }

    private function assertSdk(): void
    {
        if (!class_exists('Stripe\\Stripe')) {
            throw new \RuntimeException(
                'Stripe SDK missing. Run: composer require stripe/stripe-php'
            );
        }
    }

    private function secretKey(): string
    {
        return (string) (
            getenv('STRIPE_SECRET_KEY')
            ?: ($_ENV['STRIPE_SECRET_KEY'] ?? '')
            ?: (class_exists(Environment::class) ? Environment::get('STRIPE_SECRET_KEY', '') : '')
        );
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

    /**
     * @param array<string,scalar|null> $meta
     * @return array<string,string>
     */
    private function stringifyMeta(array $meta): array
    {
        $out = [];
        foreach ($meta as $k => $v) {
            if ($v === null) {
                continue;
            }
            $out[(string) $k] = (string) $v;
        }
        return $out;
    }
}
