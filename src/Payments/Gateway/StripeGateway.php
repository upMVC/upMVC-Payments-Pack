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

        $this->callStripe('Stripe\\Stripe', 'setApiKey', [$secret]);
        $intent = $this->callStripe('Stripe\\PaymentIntent', 'create', [[
            'amount'   => (int) $input['amount_cents'],
            'currency' => strtolower((string) $input['currency']),
            'metadata' => array_merge(
                ['tenant_id' => (string) $input['tenant_id']],
                $this->stringifyMeta($input['metadata'] ?? [])
            ),
            'description'   => $input['description'] ?? null,
            'receipt_email' => $input['customer_email'] ?? null,
            'automatic_payment_methods' => ['enabled' => true],
        ]]);

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

        $this->callStripe('Stripe\\Stripe', 'setApiKey', [$secret]);
        $intent = $this->callStripe('Stripe\\PaymentIntent', 'retrieve', [$providerRef]);

        return [
            'status'       => (string) ($intent->status ?? 'unknown'),
            'provider_ref' => (string) $intent->id,
            'raw'          => ['driver' => 'stripe', 'status' => $intent->status],
        ];
    }

    public function parseWebhook(string $payload, array $headers): array
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

        $event = $this->callStripe('Stripe\\Webhook', 'constructEvent', [$payload, (string) $sig, $whSecret]);
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
        if (!$this->classAvailable('Stripe\\Stripe')) {
            throw new \RuntimeException(
                'Stripe SDK missing. Run: composer require stripe/stripe-php'
            );
        }
    }

    /**
     * Dynamic static call so analysis does not require stripe/stripe-php in require-dev.
     *
     * @param list<mixed> $args
     */
    private function callStripe(string $class, string $method, array $args = []): mixed
    {
        if (!$this->classAvailable($class)) {
            throw new \RuntimeException(
                'Stripe SDK missing. Run: composer require stripe/stripe-php'
            );
        }

        return $class::$method(...$args);
    }

    private function classAvailable(string $class): bool
    {
        return class_exists($class);
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
