# upMVC Payments Pack

Installable payments pack for upMVC SaaS apps (`bitshost/upmvc-payments-pack`).

**Separate from** [`upmvc-saas-pack`](https://github.com/upMVC/upMVC-SaaS-Pack) (tenancy + Harvest ops).

## Architecture

```text
bitshost/upmvc                 → kernel
bitshost/upmvc-saas-pack       → tenancy + Harvest
bitshost/upmvc-payments-pack   → this pack
host app                       → domain pricing / “what to charge”
```

## Drivers (v1)

| Driver | Composer | Behavior |
|--------|----------|----------|
| `manual` | — | Generic offline pending |
| `cash` | — | Cash / COD — pending until staff marks succeeded |
| `bank_transfer` | — | Wire transfer — pending + IBAN instructions from `.env` |
| `stripe` | `composer require stripe/stripe-php` | PaymentIntent + webhook parse when SDK present |
| `paypal` | `composer require paypal/paypal-server-sdk` | Stub until Order/Capture wired — clear error if SDK missing |

**Do not** hard-require Stripe/PayPal in this pack — apps that only need cash/bank stay lean.

## Install

```bash
composer require bitshost/upmvc-payments-pack
# optional:
composer require stripe/stripe-php
```

```php
// src/Etc/packages.php
return [
    \BitsHost\UpmvcSaas\SaasServiceProvider::class,
    \BitsHost\UpmvcPayments\PaymentsServiceProvider::class,
];
```

### `.env`

```env
PAYMENTS_DRIVER=manual
# PAYMENTS_DRIVER=cash
# PAYMENTS_DRIVER=bank_transfer
# PAYMENTS_DRIVER=stripe

# bank_transfer
PAYMENTS_BANK_IBAN=
PAYMENTS_BANK_BIC=
PAYMENTS_BANK_BENEFICIARY=
PAYMENTS_BANK_NAME=

# stripe (only if driver=stripe)
STRIPE_SECRET_KEY=
STRIPE_WEBHOOK_SECRET=
```

Run host migrations (kernel → saas-pack → payments-pack → app).

## API

| Method | Path | Role |
|--------|------|------|
| GET | `/api/payments/drivers` | List built-in drivers |
| POST | `/api/payments` | Create intent (`amount_cents`, `currency`, optional `driver`) |
| GET | `/api/payments/{id}` | Show (tenant-scoped) |
| PATCH | `/api/payments/{id}/status` | Staff confirm offline (`status=succeeded` etc.) |

## Library usage

```php
use BitsHost\UpmvcPayments\Gateway\GatewayResolver;
use BitsHost\UpmvcPayments\PaymentRecord;

$gateway = GatewayResolver::driver('bank_transfer'); // or null → PAYMENTS_DRIVER
$result  = $gateway->createPayment([/* … */]);
```

## Compose discovery

This pack ships **`saas-kit.json`** so host `saas-next` (from saas-pack) can see payments as an optional layer:

```bash
php vendor/bitshost/upmvc-saas-pack/bin/saas-next.php packs
php vendor/bitshost/upmvc-saas-pack/bin/saas-next.php doctor
```

## Docs

- [`docs/01-architecture.md`](docs/01-architecture.md)
- [`AGENTS.md`](AGENTS.md)
- [`CHANGELOG.md`](CHANGELOG.md)
