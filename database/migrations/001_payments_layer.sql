-- ============================================================
-- upMVC Payments Pack — Migration 001
--
-- Tenant-scoped payment intents. Provider SDKs stay behind
-- BitsHost\UpmvcPayments\Gateway\* drivers.
-- Requires saas-pack tenants table (001_saas_layer.sql).
-- ============================================================

SET @db := DATABASE();

CREATE TABLE IF NOT EXISTS `payment_intents` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `amount_cents` int NOT NULL,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `driver` varchar(32) NOT NULL DEFAULT 'manual',
  `status` varchar(32) NOT NULL DEFAULT 'pending'
    COMMENT 'pending|requires_action|succeeded|failed|canceled|refunded',
  `provider_ref` varchar(191) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `metadata_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pay_tenant_at` (`tenant_id`, `created_at`),
  KEY `idx_pay_status` (`status`, `created_at`),
  KEY `idx_pay_provider` (`driver`, `provider_ref`),
  CONSTRAINT `fk_pay_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
