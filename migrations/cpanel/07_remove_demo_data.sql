-- ============================================================
-- Phelyz Store - remove the demo data added by 06_demo_coupon_data.sql
--
-- Everything demo is tagged, so this touches nothing real:
--   accounts  -> email ends @demo.phelyzstore.com
--   orders    -> order_number starts DEMO-
--   coupons   -> SUMMER15 and FREESHIP, both created by the demo script
--
-- WELCOME10 is deliberately left alone: the welcome popup uses it for real.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DELETE r FROM coupon_redemptions r
JOIN orders o ON o.id = r.order_id
WHERE o.order_number LIKE 'DEMO-%';

DELETE oi FROM order_items oi
JOIN orders o ON o.id = oi.order_id
WHERE o.order_number LIKE 'DEMO-%';

DELETE FROM orders WHERE order_number LIKE 'DEMO-%';

DELETE FROM coupon_redemptions
WHERE email LIKE '%@demo.phelyzstore.com';

DELETE FROM email_log            WHERE to_email LIKE '%@demo.phelyzstore.com';
DELETE FROM email_automation_log WHERE email    LIKE '%@demo.phelyzstore.com';
DELETE FROM email_campaign_recipients WHERE email LIKE '%@demo.phelyzstore.com';
DELETE FROM leads                WHERE email    LIKE '%@demo.phelyzstore.com';
DELETE FROM users                WHERE email    LIKE '%@demo.phelyzstore.com';

DELETE FROM coupons WHERE UPPER(code) IN ('SUMMER15', 'FREESHIP');

SET FOREIGN_KEY_CHECKS = 1;

-- Put every remaining coupon's counter back in step with reality.
UPDATE coupons c
SET used_count = (SELECT COUNT(*) FROM coupon_redemptions r WHERE r.coupon_id = c.id);
