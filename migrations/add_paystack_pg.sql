-- ============================================================
-- Migration: Paystack payments (Supabase / PostgreSQL)
-- ============================================================

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS stock_reduced SMALLINT NOT NULL DEFAULT 0;

SELECT 'Paystack migration completed!' AS message;
