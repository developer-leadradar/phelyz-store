-- ============================================================
-- Migration: Analytics + Promo Banners + Parcel Tracking (Supabase / PostgreSQL)
-- ============================================================

-- ── 1. Customer birthday (day + month only; year is never collected) ────────
ALTER TABLE users ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL;

-- ── 2. Analytics: raw page views ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS page_views (
    id BIGSERIAL PRIMARY KEY,
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
    created_at   TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_pv_created ON page_views(created_at);
CREATE INDEX IF NOT EXISTS idx_pv_visitor ON page_views(visitor_id);
CREATE INDEX IF NOT EXISTS idx_pv_product ON page_views(product_id);
CREATE INDEX IF NOT EXISTS idx_pv_channel ON page_views(channel);
CREATE INDEX IF NOT EXISTS idx_pv_type    ON page_views(page_type);

-- ── 3. Attribution on orders ────────────────────────────────────────────────
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS channel      VARCHAR(30)  NULL,
  ADD COLUMN IF NOT EXISTS referrer     VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS utm_source   VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(100) NULL;

-- ── 4. Sequential order numbers ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_counters (
    year_key INT PRIMARY KEY,
    last_seq INT NOT NULL DEFAULT 0
);

-- ── 5. Promotional / festive banners ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promo_banners (
    id SERIAL PRIMARY KEY,
    title       VARCHAR(160) NOT NULL,
    subtitle    VARCHAR(255) NULL,
    cta_text    VARCHAR(60)  NULL,
    cta_url     VARCHAR(255) NULL,
    preset      VARCHAR(40)  NOT NULL DEFAULT 'gold',
    bg_image    VARCHAR(500) NULL,
    emoji       VARCHAR(16)  NULL,
    starts_at   DATE NULL,
    ends_at     DATE NULL,
    is_active   SMALLINT NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_banner_active ON promo_banners(is_active, sort_order);

-- ── 6. Parcels ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS parcels (
    id SERIAL PRIMARY KEY,
    order_id      INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
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
    created_at    TIMESTAMP DEFAULT NOW(),
    updated_at    TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_parcel_order ON parcels(order_id);
CREATE INDEX IF NOT EXISTS idx_parcel_track ON parcels(tracking_id);

-- ── 7. Parcel timeline events ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS parcel_events (
    id SERIAL PRIMARY KEY,
    parcel_id  INT NOT NULL REFERENCES parcels(id) ON DELETE CASCADE,
    status     VARCHAR(30) NOT NULL,
    label      VARCHAR(160) NULL,
    lat        DECIMAL(10,7) NULL,
    lng        DECIMAL(10,7) NULL,
    note       TEXT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_event_parcel ON parcel_events(parcel_id, created_at);

SELECT 'Analytics + banners + tracking migration completed!' AS message;
