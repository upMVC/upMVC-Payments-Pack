# 01 — Payments pack architecture

## Why a separate pack

`upmvc-saas-pack` owns tenancy + shared ops (Harvest). Payments are optional, vendor-heavy, and high-risk. Products that need charging `composer require` this pack; others stay lean.

## Boundary

| Layer | Owns |
|-------|------|
| **This pack** | Gateway contract, drivers, `payment_intents`, thin APIs |
| **saas-pack** | `tenant_id`, JWT, plans/features |
| **Host app** | What to charge for, UI, success/cancel URLs |

## Driver model

```
GatewayResolver::driver($name?)
    → ManualGateway
    → CashGateway              (offline — staff mark-paid)
    → BankTransferGateway      (offline — IBAN from .env)
    → StripeGateway            (optional stripe/stripe-php)
    → PayPalGateway            (optional paypal SDK — wiring TBD)
```

Apps talk only to `PaymentGatewayInterface`. Register extra drivers with `GatewayResolver::register(...)`.

### Optional Composer SDKs

```bash
composer require stripe/stripe-php          # StripeGateway
composer require paypal/paypal-server-sdk   # PayPalGateway (when ready)
```

If the SDK is missing, the driver throws a **clear** `RuntimeException` with the `composer require …` hint — it does not fatally load at boot.

### Offline flow

1. `POST /api/payments` with `driver=cash` or `bank_transfer` → status `pending`  
2. Show `raw.instructions` / bank details to the customer  
3. Staff `PATCH /api/payments/{id}/status` `{ "status": "succeeded" }` when money is received  

Card processors should prefer webhooks later (`parseWebhook` on Stripe is ready once `STRIPE_WEBHOOK_SECRET` is set).

## Env

| Key | Meaning |
|-----|---------|
| `PAYMENTS_DRIVER` | Default driver (`manual`, `cash`, `bank_transfer`, `stripe`, `paypal`) |
| `PAYMENTS_BANK_*` | IBAN / BIC / beneficiary / bank name for `bank_transfer` |
| `STRIPE_SECRET_KEY` | Stripe secret |
| `STRIPE_WEBHOOK_SECRET` | Stripe webhook signing secret |
| `PAYPAL_CLIENT_ID` / `PAYPAL_CLIENT_SECRET` | Reserved for PayPal wiring |

## Data

`payment_intents` is always scoped by `tenant_id`. Statuses: `pending`, `requires_action`, `succeeded`, `failed`, `canceled`, `refunded`.
