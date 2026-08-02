-- ============================================================
-- Fix: sessions table (Supabase / PostgreSQL)
--
-- includes/session_handler.php (PgSessionHandler) reads and writes to a
-- `sessions` table in production, but that table was never created — and the
-- handler swallows its own exceptions, so every read returned '' and every
-- write failed silently. Result: logged-in users were logged out on the very
-- next request, because Vercel's serverless PHP has no shared local /tmp.
-- ============================================================

CREATE TABLE IF NOT EXISTS sessions (
    id            VARCHAR(128) PRIMARY KEY,
    data          TEXT NOT NULL DEFAULT '',
    last_activity BIGINT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

SELECT 'Sessions table created — logins will now persist.' AS message;
