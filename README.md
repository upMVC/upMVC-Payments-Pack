# upMVC Payments Pack

Installable payments pack for upMVC SaaS apps (`bitshost/upmvc-payments-pack`).

**Separate from** [`upmvc-saas-pack`](https://github.com/upMVC/upMVC-SaaS-Pack) (tenancy + Harvest ops). Payments pull vendor SDKs, webhooks, and refunds — optional per product.

## Architecture

```text
bitshost/upmvc                 → kernel
bitshost/upmvc-saas-pack       → tenancy + Harvest (mail, notify, audit, MFA)
bitshost/upmvc-payments-pack   → this pack (gateway contracts + payment_intents)
host app                       → domain pricing / “what to charge”
```

## v1.0 scope

| Ships | Does not ship (yet) |
|-------|---------------------|
| `PaymentGatewayInterface` + `ManualGateway` | Stripe / PayPal drivers |
| `GatewayResolver` (`PAYMENTS_DRIVER`) | Subscription billing engine |
| `PaymentRecord` + migration `001_payments_layer.sql` | Invoices / tax / dunning |
| Thin API `POST/GET /api/payments` | Checkout UI |
| `PaymentsServiceProvider` | Product-specific price rules |

## Install

Requires **upMVC ^2.4** and **upmvc-saas-pack ^1.1**.

```bash
composer require bitshost/upmvc-payments-pack
```

```php
// src/Etc/packages.php
return [
    \BitsHost\UpmvcSaas\SaasServiceProvider::class,
    \BitsHost\UpmvcPayments\PaymentsServiceProvider::class,
];
```

`.env`:

```env
PAYMENTS_DRIVER=manual
```

Run host migrations (kernel → saas-pack → payments-pack → app).

## Usage

```php
use BitsHost\UpmvcPayments\Gateway\GatewayResolver;
use BitsHost\UpmvcPayments\PaymentRecord;

$gateway = GatewayResolver::driver(); // manual | (later) stripe
$result  = $gateway->createPayment([
    'tenant_id'    => $tenantId,
    'amount_cents' => 1999,
    'currency'     => 'EUR',
    'description'  => 'Invoice #42',
]);

PaymentRecord::create([
    'tenant_id'    => $tenantId,
    'amount_cents' => 1999,
    'currency'     => 'EUR',
    'driver'       => $gateway->name(),
    'status'       => $result['status'],
    'provider_ref' => $result['provider_ref'],
]);
```

Or call `POST /api/payments` with JWT (`amount_cents`, `currency`).

## Next (roadmap)

1. `StripeGateway` behind the same interface (+ `stripe/stripe-php` suggest)
2. Webhook route + signature verify
3. Refund helper
4. Optional: platform vs tenant processor credentials (plan-gated)

## Docs

- [`docs/01-architecture.md`](docs/01-architecture.md)
- [`AGENTS.md`](AGENTS.md) — agent pointer
- [`CHANGELOG.md`](CHANGELOG.md)
