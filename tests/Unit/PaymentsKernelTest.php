<?php

declare(strict_types=1);

namespace BitsHost\UpmvcPayments\Tests\Unit;

use BitsHost\UpmvcPayments\Gateway\GatewayResolver;
use BitsHost\UpmvcPayments\Gateway\ManualGateway;
use BitsHost\UpmvcPayments\PaymentsServiceProvider;
use PHPUnit\Framework\TestCase;

final class PaymentsKernelTest extends TestCase
{
    protected function tearDown(): void
    {
        GatewayResolver::clear();
    }

    public function testManualGatewayCreatePayment(): void
    {
        $g = new ManualGateway();
        $this->assertSame('manual', $g->name());
        $out = $g->createPayment([
            'tenant_id'    => 1,
            'amount_cents' => 1500,
            'currency'     => 'eur',
        ]);
        $this->assertSame('pending', $out['status']);
        $this->assertNotEmpty($out['provider_ref']);
        $this->assertStringStartsWith('manual_', (string) $out['provider_ref']);
        $this->assertNull($out['client_secret']);
    }

    public function testGatewayResolverDefaultsToManual(): void
    {
        putenv('PAYMENTS_DRIVER=manual');
        $_ENV['PAYMENTS_DRIVER'] = 'manual';
        $g = GatewayResolver::driver();
        $this->assertInstanceOf(ManualGateway::class, $g);
    }

    public function testGatewayResolverUnknownThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        GatewayResolver::driver('nope');
    }

    public function testProviderClassExists(): void
    {
        $this->assertTrue(class_exists(PaymentsServiceProvider::class));
    }

    public function testMigration001MentionsPaymentIntents(): void
    {
        $sql = (string) file_get_contents(dirname(__DIR__, 2) . '/database/migrations/001_payments_layer.sql');
        $this->assertStringContainsString('payment_intents', $sql);
        $this->assertStringContainsString('tenant_id', $sql);
        $this->assertStringContainsString('fk_pay_tenant', $sql);
    }
}
