<?php
class PgSessionHandler implements SessionHandlerInterface {
    private static $pdo = null;
    private $ttl = 86400;

    private function getPdo() {
        if (!self::$pdo) {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdo;
    }

    public function open($path, $name) { return true; }
    public function close() { return true; }

    public function read($id) {
        try {
            $stmt = $this->getPdo()->prepare(
                "SELECT data FROM sessions WHERE id = ? AND last_activity > ?"
            );
            $stmt->execute([$id, time() - $this->ttl]);
            $row = $stmt->fetch();
            return $row ? ($row['data'] ?? '') : '';
        } catch (Exception $e) {
            return '';
        }
    }

    public function write($id, $data) {
        try {
            $stmt = $this->getPdo()->prepare(
                "INSERT INTO sessions (id, data, last_activity)
                 VALUES (?, ?, ?)
                 ON CONFLICT (id) DO UPDATE
                 SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity"
            );
            $stmt->execute([$id, $data, time()]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy($id) {
        try {
            $stmt = $this->getPdo()->prepare("DELETE FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc($maxlifetime) {
        try {
            $stmt = $this->getPdo()->prepare("DELETE FROM sessions WHERE last_activity < ?");
            $stmt->execute([time() - $maxlifetime]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
