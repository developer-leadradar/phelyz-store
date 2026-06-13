-- ============================================================
-- Migration: Configurable Payment Methods (MySQL / XAMPP)
-- ============================================================

-- Per-state toggles (NULL on product means inherit from state)
ALTER TABLE shipping_rates
  ADD COLUMN IF NOT EXISTS cod_enabled TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS bank_enabled TINYINT(1) NOT NULL DEFAULT 1;

-- Per-product overrides (NULL = inherit from state)
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS cod_enabled TINYINT(1) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS bank_enabled TINYINT(1) NULL DEFAULT NULL;

-- Global defaults via settings.json (no schema change needed)
SELECT 'Payment methods migration completed!' AS message;
