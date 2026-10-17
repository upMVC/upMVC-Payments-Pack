# Changelog

All notable changes to `bitshost/upmvc-payments-pack`.

## [Unreleased]

### v1.0.0 — Scaffold (payments contracts)

Initial public skeleton. Separate from `upmvc-saas-pack` by design.

#### Added
- **`BitsHost\UpmvcPayments\PaymentsServiceProvider`** — module + migration paths
- **`Gateway\PaymentGatewayInterface`**, **`ManualGateway`**, **`GatewayResolver`**
- **`PaymentRecord`** — tenant-scoped persistence helper
- **Migration `001_payments_layer.sql`** — `payment_intents`
- **API** `POST /api/payments`, `GET /api/payments/{id}` (JWT + tenant)
- **Docs** — README, `docs/01-architecture.md`, `AGENTS.md`
- **Tests** — `PaymentsKernelTest` (manual driver + migration guard)

#### Requires
- PHP >= 8.1
- `bitshost/upmvc` ^2.4
- `bitshost/upmvc-saas-pack` ^1.1

#### Not in v1
- Stripe / PayPal drivers, webhooks, refunds, subscription engine, checkout UI
