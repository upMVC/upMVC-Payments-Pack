# Changelog

All notable changes to `bitshost/upmvc-payments-pack`.

## [Unreleased]

## [0.2.0] — Drivers + saas-kit + PHP 8.3 (2026-11-02)

Requires **PHP 8.3+**, **upMVC ^2.4** (2.6+ on Packagist), **saas-pack ^1.1** (1.2+ recommended).

### Changed
- **PHP floor raised to `>=8.3`** — Composer + CI (`8.3` / `8.4`). Aligns with kernel and saas-pack.

### Compose
- **`saas-kit.json`** — optional-pack manifest for `saas-next` discovery (env keys, provider, agent hints)

### Drivers expansion

- **`CashGateway`** — cash / COD; pending until staff confirms
- **`BankTransferGateway`** — wire transfer; IBAN instructions from `PAYMENTS_BANK_*`
- **`StripeGateway`** — PaymentIntent create/retrieve + webhook parse when `stripe/stripe-php` is installed; clear error if missing
- **`PayPalGateway`** — stub with clear missing-SDK / not-wired errors; `suggest` paypal-server-sdk
- **`GatewayResolver`** — registers all built-ins; `available()` list
- **API** — optional `driver` on create; `GET /api/payments/drivers`; `PATCH /api/payments/{id}/status` for offline confirm

## [0.1.0] — Scaffold

Initial public skeleton (separate from `upmvc-saas-pack`).

#### Added
- **`PaymentsServiceProvider`**, **`PaymentGatewayInterface`**, **`ManualGateway`**, **`GatewayResolver`**
- **`PaymentRecord`** + migration **`001_payments_layer.sql`**
- Thin API `POST/GET /api/payments`
- Docs, CI, unit tests

#### Requires
- PHP >= 8.3 (current line; was >= 8.1 at 0.1.0 scaffold)
- `bitshost/upmvc` ^2.4
- `bitshost/upmvc-saas-pack` ^1.1
