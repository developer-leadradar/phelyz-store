-- ============================================================
-- Phelyz Store - coupon system
-- Safe to run more than once.
-- ============================================================

CREATE TABLE IF NOT EXISTS coupons (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    code                  VARCHAR(50)  NOT NULL UNIQUE,
    description           VARCHAR(255) DEFAULT NULL COMMENT 'Internal note, e.g. Influencer: Ada',

    -- What it gives
    type                  ENUM('percent','fixed','free_shipping') NOT NULL DEFAULT 'percent',
    value                 DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Percent 0-100, or naira amount',
    max_discount          DECIMAL(10,2) DEFAULT NULL COMMENT 'Optional cap on a percent discount',

    -- When it may be used
    min_spend             DECIMAL(10,2) NOT NULL DEFAULT 0,
    starts_at             DATETIME DEFAULT NULL,
    expires_at            DATETIME DEFAULT NULL,
    max_uses              INT DEFAULT NULL COMMENT 'Total redemptions allowed, NULL = unlimited',
    max_uses_per_customer INT NOT NULL DEFAULT 1,

    -- Who may use it
    first_order_only      TINYINT(1) NOT NULL DEFAULT 0,
    birthday_only         TINYINT(1) NOT NULL DEFAULT 0,
    birthday_window_days  INT NOT NULL DEFAULT 7,

    -- What it applies to
    category_id           INT DEFAULT NULL COMMENT 'Restrict to one category, NULL = all',
    exclude_express       TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Made-to-order pieces carry thinner margin',

    -- Reporting
    source                VARCHAR(60) DEFAULT NULL COMMENT 'Influencer or channel label',

    is_active             TINYINT(1) NOT NULL DEFAULT 1,
    used_count            INT NOT NULL DEFAULT 0,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per successful use, so per-customer limits and per-code revenue
-- reporting both have something solid to read from.
CREATE TABLE IF NOT EXISTS coupon_redemptions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id  INT NOT NULL,
    order_id   INT DEFAULT NULL,
    user_id    INT DEFAULT NULL,
    email      VARCHAR(255) DEFAULT NULL,
    discount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_coupon (coupon_id),
    INDEX idx_user (user_id),
    INDEX idx_email (email),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record what was taken off each order.
ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount    DECIMAL(10,2) NOT NULL DEFAULT 0;
ALTER TABLE orders ADD COLUMN IF NOT EXISTS coupon_code VARCHAR(50) DEFAULT NULL;
