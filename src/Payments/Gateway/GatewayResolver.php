<?php

namespace BitsHost\UpmvcPayments\Gateway;

use App\Etc\Config\Environment;

/**
 * Resolve the active gateway from PAYMENTS_DRIVER (.env).
 * v1 ships `manual` only; Stripe (etc.) register later without changing call sites.
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

    public static function driver(?string $name = null): PaymentGatewayInterface
    {
        if (self::$factories === []) {
            self::registerDefaults();
        }

        if ($name === null) {
            $name = (string) (
                getenv('PAYMENTS_DRIVER')
                ?: ($_ENV['PAYMENTS_DRIVER'] ?? '')
                ?: (class_exists(Environment::class) ? Environment::get('PAYMENTS_DRIVER', 'manual') : 'manual')
                ?: 'manual'
            );
        }
        $key = strtolower($name !== '' ? $name : 'manual');
        if (!isset(self::$factories[$key])) {
            throw new \InvalidArgumentException("Unknown payments driver: {$key}");
        }

        return (self::$factories[$key])();
    }

    private static function registerDefaults(): void
    {
        self::register('manual', static fn(): PaymentGatewayInterface => new ManualGateway());
    }
}
