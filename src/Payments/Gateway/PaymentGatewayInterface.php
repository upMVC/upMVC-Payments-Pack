<?php

namespace BitsHost\UpmvcPayments\Gateway;

/**
 * Vendor-agnostic payment gateway contract.
 * Drivers (Manual, Stripe, …) implement this — apps never call Stripe SDK directly from domain code.
 */
interface PaymentGatewayInterface
{
    public function name(): string;

    /**
     * Create a charge / PaymentIntent-style object.
     *
     * @param array{
     *   tenant_id:int,
     *   amount_cents:int,
     *   currency:string,
     *   description?:string,
     *   metadata?:array<string,scalar|null>,
     *   customer_email?:string,
     *   return_url?:string
     * } $input
     * @return array{
     *   status:string,
     *   provider_ref:?string,
     *   client_secret:?string,
     *   raw?:array<string,mixed>
     * }
     */
    public function createPayment(array $input): array;

    /**
     * @return array{status:string, provider_ref:?string, raw?:array<string,mixed>}
     */
    public function retrievePayment(string $providerRef): array;

    /**
     * Verify and normalize an inbound webhook payload.
     *
     * @return array{event:string, provider_ref:?string, status:?string, raw:array<string,mixed>}|null
     */
    public function parseWebhook(string $payload, array $headers): ?array;
}
