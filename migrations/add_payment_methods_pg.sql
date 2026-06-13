-- ============================================================
-- Migration: Configurable Payment Methods (Supabase / PostgreSQL)
-- ============================================================

ALTER TABLE shipping_rates
  ADD COLUMN IF NOT EXISTS cod_enabled SMALLINT NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS bank_enabled SMALLINT NOT NULL DEFAULT 1;

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS cod_enabled SMALLINT NULL,
  ADD COLUMN IF NOT EXISTS bank_enabled SMALLINT NULL;

SELECT 'Payment methods migration completed!' AS message;
