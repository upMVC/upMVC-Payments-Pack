<?php

namespace BitsHost\UpmvcPayments;

use App\Etc\Database;
use PDO;

/**
 * Thin persistence for payment_intents (tenant-scoped).
 * Domain apps decide *when* and *why* to charge; this pack stores the attempt.
 */
final class PaymentRecord
{
    /**
     * @param array{
     *   tenant_id:int,
     *   amount_cents:int,
     *   currency:string,
     *   driver:string,
     *   status:string,
     *   provider_ref:?string,
     *   description?:?string,
     *   metadata?:?array
     * } $row
     */
    public static function create(array $row): int
    {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO payment_intents
             (tenant_id, amount_cents, currency, driver, status, provider_ref, description, metadata_json, created_at, updated_at)
             VALUES
             (:tid, :amt, :cur, :drv, :st, :pref, :descr, :meta, NOW(), NOW())'
        );
        $meta = isset($row['metadata']) ? json_encode($row['metadata'], JSON_UNESCAPED_UNICODE) : null;
        $stmt->execute([
            ':tid'   => (int) $row['tenant_id'],
            ':amt'   => (int) $row['amount_cents'],
            ':cur'   => strtoupper((string) $row['currency']),
            ':drv'   => (string) $row['driver'],
            ':st'    => (string) $row['status'],
            ':pref'  => $row['provider_ref'] ?? null,
            ':descr' => $row['description'] ?? null,
            ':meta'  => $meta,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function findForTenant(int $tenantId, int $id): ?array
    {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM payment_intents WHERE id = :id AND tenant_id = :tid LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':tid' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    public static function updateStatus(int $tenantId, int $id, string $status, ?string $providerRef = null): bool
    {
        $pdo = (new Database())->getConnection();
        $stmt = $pdo->prepare(
            'UPDATE payment_intents
             SET status = :st,
                 provider_ref = COALESCE(:pref, provider_ref),
                 updated_at = NOW()
             WHERE id = :id AND tenant_id = :tid'
        );
        $stmt->execute([
            ':st'   => $status,
            ':pref' => $providerRef,
            ':id'   => $id,
            ':tid'  => $tenantId,
        ]);
        return $stmt->rowCount() > 0;
    }
}
