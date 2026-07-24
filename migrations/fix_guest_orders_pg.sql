-- ============================================================
-- Fix: allow guest (not-logged-in) orders (Supabase / PostgreSQL)
-- ============================================================

ALTER TABLE orders ALTER COLUMN user_id DROP NOT NULL;

SELECT 'Guest orders fix completed!' AS message;
