-- ============================================================
-- Phelyz Store - master email log
--
-- One row for every message the store sends, whatever sent it. Logging
-- happens inside sendEmail(), the single point every email passes through,
-- so anything added later is recorded without anyone remembering to wire it up.
-- Safe to run more than once.
-- ============================================================

CREATE TABLE IF NOT EXISTS email_log (
    id            INT AUTO_INCREMENT PRIMARY KEY,

    -- A short public reference. Quoted to a customer, it finds the exact
    -- message again, which is the whole point of keeping this.
    token         CHAR(24) NOT NULL,

    to_email      VARCHAR(255) NOT NULL,
    to_name       VARCHAR(150) DEFAULT NULL,
    subject       VARCHAR(255) NOT NULL,
    body_html     MEDIUMTEXT   NULL COMMENT 'Exactly what was sent',

    -- Where it came from
    category      ENUM('transactional','campaign','automation','admin','other')
                  NOT NULL DEFAULT 'other',
    source_type   VARCHAR(50)  DEFAULT NULL COMMENT 'verification, order_status, abandoned_cart, ...',
    source_id     VARCHAR(60)  DEFAULT NULL COMMENT 'campaign id, order id, automation key',
    audience      VARCHAR(60)  DEFAULT NULL COMMENT 'which list a campaign went to',

    -- Outcome
    status        ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    transport     VARCHAR(20)  DEFAULT NULL COMMENT 'smtp, resend, phpmailer, mail',
    error         VARCHAR(255) DEFAULT NULL,

    -- Engagement
    opened_at     DATETIME DEFAULT NULL,
    open_count    INT NOT NULL DEFAULT 0,
    last_opened_at DATETIME DEFAULT NULL,

    -- Who they were at the time of sending
    user_id       INT DEFAULT NULL,
    was_subscribed TINYINT(1) NOT NULL DEFAULT 1,

    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_token (token),
    INDEX idx_to      (to_email),
    INDEX idx_created (created_at),
    INDEX idx_cat     (category),
    INDEX idx_source  (source_type),
    INDEX idx_status  (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
