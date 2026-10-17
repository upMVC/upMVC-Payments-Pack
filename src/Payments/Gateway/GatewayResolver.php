<?php

namespace BitsHost\UpmvcPayments\Gateway;

use App\Etc\Config\Environment;

/**
 * Resolve the active gateway from PAYMENTS_DRIVER (.env) or an explicit name.
 *
 * Built-ins: manual, cash, bank_transfer, stripe, paypal.
 * Stripe/PayPal SDKs are optional Composer packages (see composer suggest).
 */
final class GatewayResolver
{
    /** @var array<string,callable():PaymentGatewayInterface> */
    private static array $factories = [];

    public static function register(string $name, callable $factory): void
    {
        self::$factories[strtolower($name)] = $factory;
    }

    public static function clear(): void
    {
        self::$factories = [];
    }

    /** @return list<string> */
    public static function available(): array
    {
        if (self::$factories === []) {
            self::registerDefaults();
        }
        return array_keys(self::$factories);
    }

    public static function driver(?string $name = null): PaymentGatewayInterface
    {
        if (self::$factories === []) {
            self::registerDefaults();
        }

        if ($name === null || $name === '') {
            $fromEnv = (string) (getenv('PAYMENTS_DRIVER') ?: ($_ENV['PAYMENTS_DRIVER'] ?? ''));
            if ($fromEnv === '' && class_exists(Environment::class)) {
                $fromEnv = (string) Environment::get('PAYMENTS_DRIVER', '');
            }
            $name = $fromEnv !== '' ? $fromEnv : 'manual';
        }
        $key = strtolower($name);
        if (!isset(self::$factories[$key])) {
            throw new \InvalidArgumentException(
                "Unknown payments driver: {$key}. Known: " . implode(', ', array_keys(self::$factories))
            );
        }

        return (self::$factories[$key])();
    }

    private static function registerDefaults(): void
    {
        self::register('manual', static fn(): PaymentGatewayInterface => new ManualGateway());
        self::register('cash', static fn(): PaymentGatewayInterface => new CashGateway());
        self::register('bank_transfer', static fn(): PaymentGatewayInterface => new BankTransferGateway());
        self::register('stripe', static fn(): PaymentGatewayInterface => new StripeGateway());
        self::register('paypal', static fn(): PaymentGatewayInterface => new PayPalGateway());
    }
}
