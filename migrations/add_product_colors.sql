-- ============================================================
-- Migration: Product Colors (MySQL / XAMPP)
-- Adds: products.colors, cart_items.selected_color, order_items.selected_color
-- ============================================================

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS colors TEXT NULL AFTER style;

ALTER TABLE cart_items
  ADD COLUMN IF NOT EXISTS selected_color VARCHAR(100) NULL AFTER quantity;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS selected_color VARCHAR(100) NULL AFTER quantity;

SELECT 'Product colors migration completed!' AS message;
