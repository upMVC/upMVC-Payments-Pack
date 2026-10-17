<?php

declare(strict_types=1);

namespace BitsHost\UpmvcPayments\Tests\Unit;

use BitsHost\UpmvcPayments\Gateway\BankTransferGateway;
use BitsHost\UpmvcPayments\Gateway\CashGateway;
use BitsHost\UpmvcPayments\Gateway\GatewayResolver;
use BitsHost\UpmvcPayments\Gateway\ManualGateway;
use BitsHost\UpmvcPayments\Gateway\PayPalGateway;
use BitsHost\UpmvcPayments\Gateway\StripeGateway;
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

    public function testCashGatewayPendingUntilStaffConfirms(): void
    {
        $g = new CashGateway();
        $out = $g->createPayment([
            'tenant_id'    => 1,
            'amount_cents' => 500,
            'currency'     => 'EUR',
        ]);
        $this->assertSame('cash', $g->name());
        $this->assertSame('pending', $out['status']);
        $this->assertStringStartsWith('cash_', (string) $out['provider_ref']);
        $this->assertArrayHasKey('instructions', $out['raw'] ?? []);
    }

    public function testBankTransferIncludesInstructions(): void
    {
        putenv('PAYMENTS_BANK_IBAN=RO49AAAA1B31007593840000');
        $_ENV['PAYMENTS_BANK_IBAN'] = 'RO49AAAA1B31007593840000';
        $g = new BankTransferGateway();
        $out = $g->createPayment([
            'tenant_id'    => 1,
            'amount_cents' => 9900,
            'currency'     => 'RON',
        ]);
        $this->assertSame('bank_transfer', $g->name());
        $this->assertSame('pending', $out['status']);
        $this->assertStringContainsString('RO49AAAA1B31007593840000', (string) ($out['raw']['instructions'] ?? ''));
        $this->assertSame('RO49AAAA1B31007593840000', $out['raw']['bank']['iban'] ?? null);
    }

    public function testStripeGatewayFailsClearlyWithoutSdk(): void
    {
        if (class_exists('Stripe\\Stripe')) {
            $this->markTestSkipped('stripe/stripe-php is installed in this environment');
        }
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('composer require stripe/stripe-php');
        (new StripeGateway())->createPayment([
            'tenant_id'    => 1,
            'amount_cents' => 100,
            'currency'     => 'eur',
        ]);
    }

    public function testPayPalGatewayFailsClearlyWithoutSdk(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('composer require paypal/paypal-server-sdk');
        (new PayPalGateway())->createPayment([
            'tenant_id'    => 1,
            'amount_cents' => 100,
            'currency'     => 'eur',
        ]);
    }

    public function testGatewayResolverListsBuiltIns(): void
    {
        $names = GatewayResolver::available();
        foreach (['manual', 'cash', 'bank_transfer', 'stripe', 'paypal'] as $d) {
            $this->assertContains($d, $names);
        }
        $this->assertInstanceOf(CashGateway::class, GatewayResolver::driver('cash'));
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
