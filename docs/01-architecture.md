# 01 — Payments pack architecture

## Why a separate pack

`upmvc-saas-pack` owns tenancy + shared ops (Harvest). Payments are optional, vendor-heavy, and high-risk. Products that need charging `composer require` this pack; others stay lean.

## Boundary

| Layer | Owns |
|-------|------|
| **This pack** | Gateway contract, drivers, `payment_intents` persistence, thin create/show API |
| **saas-pack** | `tenant_id`, JWT, plans/features (e.g. gate custom processors later) |
| **Host app** | What to charge for (invoices, bookings, subscriptions domain), UI, success/cancel URLs |

## Driver model

```
GatewayResolver::driver()
    → ManualGateway     (v1 — offline / pending)
    → StripeGateway     (planned)
    → …
```

Apps and API controllers talk only to `PaymentGatewayInterface`. Register extra drivers with `GatewayResolver::register('stripe', fn() => new StripeGateway())`.

## Data

`payment_intents` is always scoped by `tenant_id` (FK → `tenants`). Status vocabulary (informative): `pending`, `requires_action`, `succeeded`, `failed`, `canceled`, `refunded`.

## Env

| Key | Default | Meaning |
|-----|---------|---------|
| `PAYMENTS_DRIVER` | `manual` | Active gateway name |

Stripe keys (future) stay host-only — never committed.

## Wire-up

1. Require pack + saas-pack  
2. Register `PaymentsServiceProvider` after `SaasServiceProvider`  
3. Migrate `001_payments_layer.sql`  
4. Call library API or `/api/payments`
