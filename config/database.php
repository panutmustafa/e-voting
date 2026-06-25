<?php
// Set aplikasi ke WIB (UTC+7) agar konsisten dengan input admin
date_default_timezone_set('Asia/Jakarta');
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'evoting';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'moslem78';

function createMysqlPdo(string $host, int $port, string $dbname, string $username, string $password): PDO {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // Samakan timezone MySQL session dengan WIB (UTC+7)
    $pdo->exec("SET time_zone = '+07:00'");
    return $pdo;
}

try {
    $pdo = createMysqlPdo($host, (int)$port, $dbname, $username, $password);
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = createMysqlPdo($host, (int)$port, $dbname, $username, $password);
    } catch (PDOException $e2) {
        $sqlitePath = __DIR__ . '/../database.sqlite';
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $schema = file_get_contents(__DIR__ . '/../schema.sqlite.sql');
        if ($schema !== false) {
            $pdo->exec($schema);
        }
    }
}

if (str_contains($pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql')) {
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    if ($schema !== false) {
        $statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $schema)));
        foreach ($statements as $statement) {
            // Only run CREATE TABLE statements on every request (they use IF NOT EXISTS, idempotent).
            // Skip INSERT statements — they create duplicate rows (settings has no UNIQUE constraint).
            if (!preg_match('/^CREATE\s+TABLE/i', $statement)) {
                continue;
            }
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                if (stripos($e->getMessage(), 'duplicate entry') === false && stripos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
    }

    // Seed default admin user only if no admin exists
    $check = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE username='admin'")->fetch();
    if ((int)$check['cnt'] === 0) {
        $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '\$2y\$10\$T58R2PlXMfQp9aIXYPf7DuyG7lq7ordPzo.4s0c2M8uUjOy03q4uS', 'admin')");
    }

    // Seed default settings row only if no settings exist
    $check = $pdo->query("SELECT COUNT(*) AS cnt FROM settings")->fetch();
    if ((int)$check['cnt'] === 0) {
        $pdo->exec("INSERT INTO settings (voting_status, theme, school_name) VALUES ('draft', 'light', 'SD Negeri Jomblang 2')");
    }
}
