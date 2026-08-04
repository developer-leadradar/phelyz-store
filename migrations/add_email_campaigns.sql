-- ============================================================
-- Phelyz Store - mass email (campaigns)
-- Safe to run more than once.
-- ============================================================

-- One row per campaign the admin composes.
CREATE TABLE IF NOT EXISTS email_campaigns (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    subject          VARCHAR(255) NOT NULL,
    heading          VARCHAR(255) DEFAULT NULL,
    body             TEXT         NOT NULL,
    cta_text         VARCHAR(100) DEFAULT NULL,
    cta_url          VARCHAR(500) DEFAULT NULL,
    audience         VARCHAR(40)  NOT NULL DEFAULT 'all',
    status           ENUM('draft','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
    total_recipients INT NOT NULL DEFAULT 0,
    sent_count       INT NOT NULL DEFAULT 0,
    failed_count     INT NOT NULL DEFAULT 0,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    sent_at          TIMESTAMP    NULL DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The mailing list snapshot for a campaign, so sending can resume safely
-- and we never email the same person twice for the same campaign.
CREATE TABLE IF NOT EXISTS email_campaign_recipients (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id     INT NULL,
    email       VARCHAR(255) NOT NULL,
    first_name  VARCHAR(100) DEFAULT NULL,
    status      ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    sent_at     TIMESTAMP NULL DEFAULT NULL,
    error       VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uniq_campaign_email (campaign_id, email),
    INDEX idx_campaign_status (campaign_id, status),
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anyone who opted out of marketing. Checked before every campaign send.
-- Order and account emails are transactional and are never filtered by this.
CREATE TABLE IF NOT EXISTS email_unsubscribes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    reason     VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
