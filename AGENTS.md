# upMVC-Payments-Pack — Agent context

Installable **payments** pack for upMVC SaaS apps — not the tenancy/ops pack, not a standalone app.

## Sibling packs

| Package | Role |
|---------|------|
| `bitshost/upmvc` | Kernel |
| `bitshost/upmvc-saas-pack` | Tenancy + Harvest ops (mail, notify, audit, MFA) |
| `bitshost/upmvc-payments-pack` | **This repo** — gateway contracts, payment records, thin APIs |

## Load first

- `README.md`
- `docs/01-architecture.md`
- `CHANGELOG.md`
- `src/Payments/PaymentsServiceProvider.php`

## Must

1. Keep provider SDKs (Stripe, PayPal, …) behind `PaymentGatewayInterface` drivers — do not hard-wire one vendor into the pack core.
2. Scope every payment row with `tenant_id` (SaaS shared-DB model).
3. Secrets (API keys) stay in host `.env` / sealed settings — never commit live keys.
4. Do not merge payments into `upmvc-saas-pack` — different risk and optional dependency.

## Never

- Commit `vault/`, `.env`, or live payment credentials.
- Put product-only pricing rules (CMMS plans, rental seasons) into this pack — apps own domain pricing.
