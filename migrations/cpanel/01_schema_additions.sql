-- ============================================================
-- cPanel / MySQL — tables and columns that exist in the live
-- Supabase database but were never in database.sql.
--
-- Run AFTER database.sql on the new cPanel MySQL database.
-- Without these the auth flows break: register.php writes to
-- email_verifications and forgot-password.php writes to
-- password_resets.
-- ============================================================

-- ── Email verification (register.php -> verify-email.php) ───────────────────
CREATE TABLE IF NOT EXISTS email_verifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ev_token (token),
    KEY idx_ev_user (user_id),
    KEY idx_ev_email (email),
    CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Password reset (forgot-password.php -> reset-password.php) ──────────────
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pr_token (token),
    KEY idx_pr_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Saved customer addresses ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS addresses (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    type       VARCHAR(20) NOT NULL,
    first_name VARCHAR(100),
    last_name  VARCHAR(100),
    address    TEXT,
    city       VARCHAR(100),
    state      VARCHAR(100),
    zip_code   VARCHAR(20),
    country    VARCHAR(100),
    phone      VARCHAR(20),
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_addr_user (user_id),
    CONSTRAINT fk_addr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: no `sessions` table is needed on cPanel. PgSessionHandler is only
-- installed when DB_DRIVER=pgsql; on MySQL, PHP uses its normal file-based
-- sessions, which work correctly on traditional (non-serverless) hosting.

SELECT 'cPanel schema additions applied.' AS message;
