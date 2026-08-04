-- ============================================================
-- Phelyz Store - post-launch fixes
-- Run once in phpMyAdmin against cimedgec_phelyz.
-- Safe to run more than once.
-- ============================================================

-- 1. Admin sign-in address moves to the store domain.
UPDATE users
   SET email = 'admin@phelyzstore.com'
 WHERE role = 'admin'
   AND email = 'admin@phelyz.com';

-- 2. Spelling: the site uses "jewelry", so fix any stored copy that still
--    says "jewellery". Covers both capitalisations.
UPDATE products SET
    name        = REPLACE(REPLACE(name,        'Jewellery', 'Jewelry'), 'jewellery', 'jewelry'),
    description = REPLACE(REPLACE(description, 'Jewellery', 'Jewelry'), 'jewellery', 'jewelry'),
    style       = REPLACE(REPLACE(style,       'Jewellery', 'Jewelry'), 'jewellery', 'jewelry'),
    occasion    = REPLACE(REPLACE(occasion,    'Jewellery', 'Jewelry'), 'jewellery', 'jewelry')
WHERE name        LIKE '%jeweller%'
   OR description LIKE '%jeweller%'
   OR style       LIKE '%jeweller%'
   OR occasion    LIKE '%jeweller%';

UPDATE categories SET
    name        = REPLACE(REPLACE(name,        'Jewellery', 'Jewelry'), 'jewellery', 'jewelry'),
    description = REPLACE(REPLACE(description, 'Jewellery', 'Jewelry'), 'jewellery', 'jewelry')
WHERE name        LIKE '%jeweller%'
   OR description LIKE '%jeweller%';

-- 3. Long dashes in stored product copy read as machine-written, same as in
--    the page templates. Swap them for a plain hyphen.
UPDATE products SET
    name        = REPLACE(REPLACE(name,        '—', '-'), '–', '-'),
    description = REPLACE(REPLACE(description, '—', '-'), '–', '-')
WHERE name        REGEXP '[—–]'
   OR description REGEXP '[—–]';

UPDATE categories SET
    name        = REPLACE(REPLACE(name,        '—', '-'), '–', '-'),
    description = REPLACE(REPLACE(description, '—', '-'), '–', '-')
WHERE name        REGEXP '[—–]'
   OR description REGEXP '[—–]';

-- 4. Clear traffic recorded while the site was being built. Scanner hits and
--    your own test visits are what pushed the visitor count up.
DELETE FROM page_views;
