-- ============================================================
-- Migration: Shipping Rates per State + Product Stock Status
-- Run this in your SUPABASE SQL Editor
-- ============================================================

-- 1. Add stock_status column to products
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS stock_status TEXT NOT NULL DEFAULT 'available'
  CHECK (stock_status IN ('available','express','out_of_stock'));

-- 2. Create shipping_rates table
CREATE TABLE IF NOT EXISTS shipping_rates (
    id SERIAL PRIMARY KEY,
    state VARCHAR(100) NOT NULL UNIQUE,
    rate DECIMAL(10,2) NOT NULL DEFAULT 4000.00,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Auto-update updated_at trigger
CREATE OR REPLACE FUNCTION update_shipping_rates_timestamp()
RETURNS TRIGGER AS $$
BEGIN NEW.updated_at = NOW(); RETURN NEW; END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_shipping_rates_updated ON shipping_rates;
CREATE TRIGGER trg_shipping_rates_updated
  BEFORE UPDATE ON shipping_rates
  FOR EACH ROW EXECUTE FUNCTION update_shipping_rates_timestamp();

-- 3. Seed default rates for all 36 states + FCT
INSERT INTO shipping_rates (state, rate) VALUES
('Abia', 4000.00),
('Adamawa', 4500.00),
('Akwa Ibom', 4000.00),
('Anambra', 3500.00),
('Bauchi', 4500.00),
('Bayelsa', 4000.00),
('Benue', 4000.00),
('Borno', 5000.00),
('Cross River', 4000.00),
('Delta', 3500.00),
('Ebonyi', 4000.00),
('Edo', 3500.00),
('Ekiti', 3500.00),
('Enugu', 3500.00),
('FCT (Abuja)', 3000.00),
('Gombe', 4500.00),
('Imo', 3500.00),
('Jigawa', 4500.00),
('Kaduna', 4000.00),
('Kano', 3500.00),
('Katsina', 4500.00),
('Kebbi', 5000.00),
('Kogi', 4000.00),
('Kwara', 3500.00),
('Lagos', 2500.00),
('Nasarawa', 4000.00),
('Niger', 4000.00),
('Ogun', 3000.00),
('Ondo', 3500.00),
('Osun', 3500.00),
('Oyo', 3500.00),
('Plateau', 4000.00),
('Rivers', 3500.00),
('Sokoto', 5000.00),
('Taraba', 4500.00),
('Yobe', 5000.00),
('Zamfara', 5000.00)
ON CONFLICT (state) DO UPDATE SET rate = EXCLUDED.rate;

SELECT 'Supabase migration completed!' AS message;
