<?php

namespace BitsHost\UpmvcPayments\Gateway;

/**
 * PayPal driver — optional SDK via Composer.
 *
 *   composer require paypal/paypal-server-sdk
 *   PAYMENTS_DRIVER=paypal
 *   PAYPAL_CLIENT_ID=...
 *   PAYPAL_CLIENT_SECRET=...
 *
 * Until the SDK is installed (and Order/Capture is finished), methods fail with a clear hint.
 */
final class PayPalGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'paypal';
    }

    public function createPayment(array $input): array
    {
        $this->assertSdk();
        throw new \RuntimeException(
            'PayPalGateway: SDK detected but Order/Capture wiring is not implemented yet. See docs/01-architecture.md.'
        );
    }

    public function retrievePayment(string $providerRef): array
    {
        $this->assertSdk();
        throw new \RuntimeException('PayPalGateway retrieve is not implemented yet.');
    }

    public function parseWebhook(string $payload, array $headers): ?array
    {
        $this->assertSdk();
        throw new \RuntimeException('PayPalGateway webhooks are not implemented yet.');
    }

    private function assertSdk(): void
    {
        $candidates = [
            'PaypalServerSdk\\PaypalServerSdkClient',
            'PayPalCheckoutSdk\\Core\\PayPalHttpClient',
            'PayPal\\Rest\\ApiContext',
        ];
        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return;
            }
        }
        throw new \RuntimeException(
            'PayPal SDK missing. Run: composer require paypal/paypal-server-sdk'
        );
    }
}
