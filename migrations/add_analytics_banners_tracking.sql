-- ============================================================
-- Migration: Analytics + Promo Banners + Parcel Tracking (MySQL / XAMPP)
-- Also back-fills the Paystack columns if this DB never got them.
-- ============================================================

-- ── Catch-up: Paystack columns (no-ops if already present) ──────────────────
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS stock_reduced TINYINT(1) NOT NULL DEFAULT 0;

-- ── 1. Customer birthday (day + month only; year is never collected) ────────
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL;

-- ── 2. Analytics: raw page views ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    visitor_id   VARCHAR(64)  NOT NULL,
    session_id   VARCHAR(64)  NOT NULL,
    user_id      INT          NULL,
    path         VARCHAR(255) NOT NULL,
    page_type    VARCHAR(30)  NOT NULL DEFAULT 'other',
    product_id   INT          NULL,
    channel      VARCHAR(30)  NOT NULL DEFAULT 'direct',
    referrer     VARCHAR(500) NULL,
    utm_source   VARCHAR(100) NULL,
    utm_medium   VARCHAR(100) NULL,
    utm_campaign VARCHAR(100) NULL,
    country      VARCHAR(8)   NULL,
    device       VARCHAR(20)  NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pv_created  (created_at),
    INDEX idx_pv_visitor  (visitor_id),
    INDEX idx_pv_product  (product_id),
    INDEX idx_pv_channel  (channel),
    INDEX idx_pv_type     (page_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Attribution on orders (which channel produced the sale) ──────────────
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS channel       VARCHAR(30)  NULL,
  ADD COLUMN IF NOT EXISTS referrer      VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS utm_source    VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_campaign  VARCHAR(100) NULL;

-- ── 4. Sequential order numbers (replaces the old random generator) ─────────
CREATE TABLE IF NOT EXISTS order_counters (
    year_key INT PRIMARY KEY,
    last_seq INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. Promotional / festive banners ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promo_banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(160) NOT NULL,
    subtitle    VARCHAR(255) NULL,
    cta_text    VARCHAR(60)  NULL,
    cta_url     VARCHAR(255) NULL,
    preset      VARCHAR(40)  NOT NULL DEFAULT 'gold',
    bg_image    VARCHAR(500) NULL,
    emoji       VARCHAR(16)  NULL,
    starts_at   DATE NULL,
    ends_at     DATE NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_banner_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Parcels: one order may ship as one or more parcels ───────────────────
CREATE TABLE IF NOT EXISTS parcels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT NOT NULL,
    parcel_number VARCHAR(40) NOT NULL UNIQUE,
    tracking_id   VARCHAR(40) NOT NULL UNIQUE,
    courier       VARCHAR(80)  NULL,
    status        VARCHAR(30) NOT NULL DEFAULT 'processing',
    current_label VARCHAR(160) NULL,
    current_lat   DECIMAL(10,7) NULL,
    current_lng   DECIMAL(10,7) NULL,
    dest_label    VARCHAR(160) NULL,
    dest_lat      DECIMAL(10,7) NULL,
    dest_lng      DECIMAL(10,7) NULL,
    eta_date      DATE NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_parcel_order (order_id),
    INDEX idx_parcel_track (tracking_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. Parcel timeline events ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS parcel_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id  INT NOT NULL,
    status     VARCHAR(30) NOT NULL,
    label      VARCHAR(160) NULL,
    lat        DECIMAL(10,7) NULL,
    lng        DECIMAL(10,7) NULL,
    note       TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(id) ON DELETE CASCADE,
    INDEX idx_event_parcel (parcel_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Analytics + banners + tracking migration completed!' AS message;
