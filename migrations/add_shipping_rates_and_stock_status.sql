-- ============================================================
-- Migration: Shipping Rates per State + Product Stock Status
-- Run this on your LOCAL MySQL (XAMPP phpMyAdmin)
-- ============================================================

-- 1. Add stock_status column to products
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS stock_status ENUM('available','express','out_of_stock')
  NOT NULL DEFAULT 'available'
  AFTER stock_quantity;

-- 2. Create shipping_rates table
CREATE TABLE IF NOT EXISTS shipping_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    state VARCHAR(100) NOT NULL UNIQUE,
    rate DECIMAL(10,2) NOT NULL DEFAULT 4000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_state (state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
ON DUPLICATE KEY UPDATE rate = VALUES(rate);

SELECT 'Migration completed successfully!' AS Message;
