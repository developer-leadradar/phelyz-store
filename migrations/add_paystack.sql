-- ============================================================
-- Migration: Paystack payments (MySQL / XAMPP)
-- Adds a reference column so orders can be reconciled with Paystack
-- ============================================================

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL AFTER payment_status;

SELECT 'Paystack migration completed!' AS message;
