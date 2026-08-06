-- ============================================================
-- Phelyz Store - DEMO DATA for the coupons page
--
-- Creates eight sample customers, two extra coupons, some orders and the
-- redemptions that tie them together, so you can see what "See who used it"
-- and the follow-up campaign actually look like with people in them.
--
-- Everything here is tagged so it can be removed in one go: demo accounts all
-- use @demo.phelyzstore.com, and orders use the DEMO- order-number prefix.
-- Run 07_remove_demo_data.sql when you have finished looking.
--
-- Safe to run more than once.
-- ============================================================

-- ── Two coupons to demonstrate with ─────────────────────────────────────────
INSERT INTO coupons (code, description, type, value, min_spend, max_uses_per_customer,
                     first_order_only, exclude_express, is_active, source, expires_at)
SELECT 'SUMMER15', 'Demo: mid-season offer', 'percent', 15, 20000, 1, 0, 1, 1, 'Instagram',
       DATE_ADD(NOW(), INTERVAL 45 DAY)
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE UPPER(code) = 'SUMMER15');

INSERT INTO coupons (code, description, type, value, min_spend, max_uses_per_customer,
                     first_order_only, exclude_express, is_active, source)
SELECT 'FREESHIP', 'Demo: free delivery offer', 'free_shipping', 0, 0, 1, 0, 1, 1, 'WhatsApp'
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE UPPER(code) = 'FREESHIP');

-- ── Eight sample customers ──────────────────────────────────────────────────
INSERT INTO users (email, password, first_name, last_name, phone, city, state, country, role, is_active, created_at)
SELECT * FROM (
  SELECT 'amaka.obi@demo.phelyzstore.com'      AS e, '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2' AS p, 'Amaka'    AS f, 'Obi'      AS l, '08031112222' AS ph, 'Uyo'     AS c, 'Akwa Ibom' AS s, 'Nigeria' AS co, 'customer' AS r, 1 AS a, DATE_SUB(NOW(), INTERVAL 40 DAY) AS cr
  UNION ALL SELECT 'chidi.eze@demo.phelyzstore.com',      '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Chidi',    'Eze',      '08032223333', 'Lagos',   'Lagos',     'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 36 DAY)
  UNION ALL SELECT 'ngozi.udo@demo.phelyzstore.com',      '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Ngozi',    'Udo',      '08033334444', 'Calabar', 'Cross River','Nigeria','customer', 1, DATE_SUB(NOW(), INTERVAL 31 DAY)
  UNION ALL SELECT 'tunde.bello@demo.phelyzstore.com',    '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Tunde',    'Bello',    '08034445555', 'Abuja',   'FCT',       'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 27 DAY)
  UNION ALL SELECT 'blessing.eton@demo.phelyzstore.com',  '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Blessing', 'Etim',     '08035556666', 'Uyo',     'Akwa Ibom', 'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 22 DAY)
  UNION ALL SELECT 'ifeoma.nwosu@demo.phelyzstore.com',   '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Ifeoma',   'Nwosu',    '08036667777', 'Enugu',   'Enugu',     'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 17 DAY)
  UNION ALL SELECT 'sade.adeyemi@demo.phelyzstore.com',   '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Sade',     'Adeyemi',  '08037778888', 'Ibadan',  'Oyo',       'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)
  UNION ALL SELECT 'grace.okon@demo.phelyzstore.com',     '$2y$12$4YhKc/vRzndFZ/4Ftjgc8OJvYPKFu5sxqgNnn7bOCxTHGiLNzu8D2', 'Grace',    'Okon',     '08038889999', 'Uyo',     'Akwa Ibom', 'Nigeria', 'customer', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)
) AS d
WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.email = d.e);

-- ── An order for each, with a discount already applied ──────────────────────
-- Totals are subtotal minus discount plus shipping, so the figures on the page
-- add up rather than just looking plausible.
INSERT INTO orders (user_id, order_number, status, subtotal, tax, shipping, discount, coupon_code,
                    total, payment_method, payment_status,
                    shipping_first_name, shipping_last_name, shipping_address, shipping_city,
                    shipping_state, shipping_country, shipping_phone, created_at, updated_at)
SELECT u.id, d.onum, d.st, d.sub, 0, d.ship, d.disc, d.code,
       (d.sub - d.disc + d.ship), 'paystack', 'paid',
       u.first_name, u.last_name, '12 Demo Street', u.city, u.state, 'Nigeria', u.phone,
       DATE_SUB(NOW(), INTERVAL d.ago DAY), DATE_SUB(NOW(), INTERVAL d.ago DAY)
FROM (
  SELECT 'amaka.obi@demo.phelyzstore.com'     AS e, 'DEMO-1001' AS onum, 'delivered'  AS st, 42000 AS sub, 0    AS ship, 4200 AS disc, 'WELCOME10' AS code, 38 AS ago
  UNION ALL SELECT 'chidi.eze@demo.phelyzstore.com',     'DEMO-1002', 'delivered',  28500, 2500, 2850, 'WELCOME10', 34
  UNION ALL SELECT 'ngozi.udo@demo.phelyzstore.com',     'DEMO-1003', 'delivered',  65000, 0,    6500, 'WELCOME10', 29
  UNION ALL SELECT 'tunde.bello@demo.phelyzstore.com',   'DEMO-1004', 'delivered',  31000, 2500, 3100, 'WELCOME10', 25
  UNION ALL SELECT 'blessing.eton@demo.phelyzstore.com', 'DEMO-1005', 'shipped',    54000, 0,    8100, 'SUMMER15',  19
  UNION ALL SELECT 'ifeoma.nwosu@demo.phelyzstore.com',  'DEMO-1006', 'delivered',  47500, 0,    7125, 'SUMMER15',  14
  UNION ALL SELECT 'sade.adeyemi@demo.phelyzstore.com',  'DEMO-1007', 'processing', 22000, 2500, 3300, 'SUMMER15',   8
  UNION ALL SELECT 'grace.okon@demo.phelyzstore.com',    'DEMO-1008', 'processing', 18500, 0,    0,    'FREESHIP',   3
) AS d
JOIN users u ON u.email = d.e
WHERE NOT EXISTS (SELECT 1 FROM orders o WHERE o.order_number = d.onum);

-- ── The redemptions that make the coupon page come alive ────────────────────
INSERT INTO coupon_redemptions (coupon_id, order_id, user_id, email, discount, created_at)
SELECT c.id, o.id, o.user_id, u.email, o.discount, o.created_at
FROM orders o
JOIN users u   ON u.id = o.user_id
JOIN coupons c ON UPPER(c.code) = UPPER(o.coupon_code)
WHERE o.order_number LIKE 'DEMO-%'
  AND NOT EXISTS (
      SELECT 1 FROM coupon_redemptions r
      WHERE r.order_id = o.id AND r.coupon_id = c.id
  );

-- Keep each coupon's own counter in step with the rows above.
UPDATE coupons c
SET used_count = (SELECT COUNT(*) FROM coupon_redemptions r WHERE r.coupon_id = c.id);

-- ── A few popup signups too, so that list is not empty either ───────────────
INSERT INTO leads (email, whatsapp, first_name, source, coupon_code, created_at)
SELECT * FROM (
  SELECT 'joy.effiong@demo.phelyzstore.com' AS e, '08039990000' AS w, 'Joy'    AS f, 'welcome_popup' AS s, 'WELCOME10' AS c, DATE_SUB(NOW(), INTERVAL 9 DAY)  AS cr
  UNION ALL SELECT 'peter.ani@demo.phelyzstore.com',   '08030001111', 'Peter',  'welcome_popup', 'WELCOME10', DATE_SUB(NOW(), INTERVAL 6 DAY)
  UNION ALL SELECT 'mary.udoh@demo.phelyzstore.com',   '08031112223', 'Mary',   'welcome_popup', 'WELCOME10', DATE_SUB(NOW(), INTERVAL 2 DAY)
) AS d
WHERE NOT EXISTS (SELECT 1 FROM leads l WHERE l.email = d.e);
