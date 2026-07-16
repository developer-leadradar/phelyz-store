-- ============================================================
-- Migration: Paystack payments (Supabase / PostgreSQL)
-- ============================================================

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL;

SELECT 'Paystack migration completed!' AS message;
