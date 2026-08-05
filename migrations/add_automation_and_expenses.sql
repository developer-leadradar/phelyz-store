-- ============================================================
-- Phelyz Store - welcome leads, email automation, expenses
-- Safe to run more than once.
-- ============================================================

-- ── 1. Welcome popup leads ──────────────────────────────────────────────────
-- Someone who hands over an email and a WhatsApp number for a discount is a
-- warm lead even before they buy, so they are kept separately from accounts.
CREATE TABLE IF NOT EXISTS leads (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    whatsapp      VARCHAR(40)  DEFAULT NULL,
    first_name    VARCHAR(100) DEFAULT NULL,
    source        VARCHAR(40)  NOT NULL DEFAULT 'welcome_popup',
    coupon_code   VARCHAR(50)  DEFAULT NULL,
    converted     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Has since placed an order',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_lead_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Scheduled and seasonal campaigns ─────────────────────────────────────
-- A festive campaign is written now and sent on the day, so the shop is not
-- relying on somebody being at a laptop on Christmas morning.
ALTER TABLE email_campaigns ADD COLUMN IF NOT EXISTS scheduled_at DATETIME DEFAULT NULL;
ALTER TABLE email_campaigns ADD COLUMN IF NOT EXISTS season_key   VARCHAR(40) DEFAULT NULL;

-- ── 3. Lifecycle automations ────────────────────────────────────────────────
-- One row per automation, so each can be switched off or retimed without code.
CREATE TABLE IF NOT EXISTS email_automations (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    automation_key VARCHAR(50) NOT NULL UNIQUE,
    label         VARCHAR(120) NOT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    subject       VARCHAR(255) NOT NULL,
    heading       VARCHAR(255) DEFAULT NULL,
    body          TEXT NOT NULL,
    cta_text      VARCHAR(100) DEFAULT NULL,
    cta_url       VARCHAR(500) DEFAULT NULL,
    delay_hours   INT NOT NULL DEFAULT 24 COMMENT 'How long after the trigger to send',
    coupon_code   VARCHAR(50) DEFAULT NULL COMMENT 'Optional code to include',
    is_active     TINYINT(1) NOT NULL DEFAULT 0,
    sent_count    INT NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every automated email that has gone out, so nobody is ever sent the same
-- one twice and the daily cap can be enforced.
CREATE TABLE IF NOT EXISTS email_automation_log (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    automation_key VARCHAR(50)  NOT NULL,
    email          VARCHAR(255) NOT NULL,
    user_id        INT DEFAULT NULL,
    reference      VARCHAR(60)  DEFAULT NULL COMMENT 'Order id, cart id, year, etc',
    sent_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_automation_target (automation_key, email, reference),
    INDEX idx_email_sent (email, sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Expenses and landed cost ─────────────────────────────────────────────
-- Day-to-day money going out: transport, packaging, loan repayments, ads.
CREATE TABLE IF NOT EXISTS expenses (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    spent_on    DATE NOT NULL,
    category    VARCHAR(60) NOT NULL DEFAULT 'Operations',
    description VARCHAR(255) NOT NULL,
    amount      DECIMAL(12,2) NOT NULL,
    notes       VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_spent_on (spent_on),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A shipment of stock: what was paid for the goods and what it cost to get
-- them here. Shipping is spread across the items so each piece carries its
-- true landed cost, which is the number the sheet was really working out.
CREATE TABLE IF NOT EXISTS purchase_batches (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    reference      VARCHAR(80) NOT NULL,
    supplier       VARCHAR(120) DEFAULT NULL,
    ordered_on     DATE NOT NULL,
    arrived_on     DATE DEFAULT NULL,
    goods_cost     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Filled from the items',
    shipping_cost  DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Freight, customs, packing, local delivery',
    other_cost     DECIMAL(12,2) NOT NULL DEFAULT 0,
    allocation     ENUM('value','quantity') NOT NULL DEFAULT 'value'
                   COMMENT 'How shipping is spread over the items',
    notes          VARCHAR(255) DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordered (ordered_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS purchase_batch_items (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    batch_id      INT NOT NULL,
    product_id    INT DEFAULT NULL COMMENT 'Linked once the piece is listed',
    item_name     VARCHAR(200) NOT NULL,
    quantity      INT NOT NULL DEFAULT 1,
    unit_cost     DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Goods cost per unit, before shipping',
    expected_price DECIMAL(12,2) DEFAULT NULL COMMENT 'What you plan to sell it for',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch (batch_id),
    INDEX idx_product (product_id),
    FOREIGN KEY (batch_id) REFERENCES purchase_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- What each piece actually cost to put on the shelf, so an order can report
-- profit rather than only revenue.
ALTER TABLE products ADD COLUMN IF NOT EXISTS cost_price DECIMAL(12,2) NOT NULL DEFAULT 0;

-- ── 5. The code the welcome popup hands out ─────────────────────────────────
-- Created only if it is not already there, so re-running never overwrites
-- whatever the shop has since changed it to.
INSERT INTO coupons (code, description, type, value, min_spend, max_uses_per_customer,
                     first_order_only, exclude_express, is_active, source)
SELECT 'WELCOME10', 'First order discount, given by the welcome popup', 'percent', 10, 0, 1,
       1, 1, 1, 'welcome_popup'
WHERE NOT EXISTS (SELECT 1 FROM coupons WHERE UPPER(code) = 'WELCOME10');
