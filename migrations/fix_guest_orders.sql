-- ============================================================
-- Fix: allow guest (not-logged-in) orders (MySQL / XAMPP)
-- orders.user_id was NOT NULL with an FK to users(id), so guest
-- checkouts (user_id = 0/NULL) failed silently. Make it nullable.
-- ============================================================

ALTER TABLE orders MODIFY user_id INT NULL;

SELECT 'Guest orders fix completed!' AS message;
