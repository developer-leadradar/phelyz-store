-- ============================================================
-- Phelyz Store - clear test data and repair stored product copy
-- Run once in phpMyAdmin against cimedgec_phelyz.
--
-- WARNING: this deletes every customer account and every order placed so far.
-- That is deliberate: everything on the site to date is build-phase testing.
-- The admin account, the products, the categories and the shipping rates all
-- stay exactly as they are.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Customer data. Orders are removed with the accounts they belong to, so
--    the reports stop counting test purchases as real ones.
DELETE FROM coupon_redemptions;
DELETE FROM email_campaign_recipients;
DELETE FROM email_campaigns;
DELETE FROM email_verifications;
DELETE FROM password_resets;
DELETE FROM reviews;
DELETE FROM wishlist;
DELETE FROM cart_items;
DELETE FROM cart;
DELETE FROM addresses;
DELETE FROM parcel_events;
DELETE FROM parcels;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM users WHERE role <> 'admin';

-- Order numbers start from the beginning again.
DELETE FROM order_counters;

-- Coupons keep their definitions but forget their usage counts.
UPDATE coupons SET used_count = 0;

SET FOREIGN_KEY_CHECKS = 1;

-- 2. Star ratings were seeded with invented numbers. With the reviews table
--    now empty, every product should read as unreviewed.
UPDATE products SET rating = 0, review_count = 0;

-- 3. Product copy saved through the old form had its apostrophes HTML-encoded
--    before being stored, so "Men's" came back as "Men&#039;s". Decode the
--    entities that could have been written.
UPDATE products SET
    name = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(name,
        '&#039;', ''''), '&#39;', ''''), '&quot;', '"'),
        '&lt;', '<'), '&gt;', '>'), '&amp;', '&'),
    description = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(description,
        '&#039;', ''''), '&#39;', ''''), '&quot;', '"'),
        '&lt;', '<'), '&gt;', '>'), '&amp;', '&')
WHERE name LIKE '%&#%' OR name LIKE '%&amp;%' OR name LIKE '%&quot;%'
   OR description LIKE '%&#%' OR description LIKE '%&amp;%' OR description LIKE '%&quot;%';

UPDATE categories SET
    name = REPLACE(REPLACE(REPLACE(name, '&#039;', ''''), '&#39;', ''''), '&amp;', '&'),
    description = REPLACE(REPLACE(REPLACE(description, '&#039;', ''''), '&#39;', ''''), '&amp;', '&')
WHERE name LIKE '%&#%' OR name LIKE '%&amp;%'
   OR description LIKE '%&#%' OR description LIKE '%&amp;%';

-- 4. Start visitor counting clean as well.
DELETE FROM page_views;
