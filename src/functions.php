<?php
require_once __DIR__ . '/../vendor/autoload.php';

function init_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function check_rate_limit(string $key, int $maxAttempts = 5, int $window = 300): bool {
    $now = time();
    $attempts = $_SESSION['rate_limit'][$key] ?? [];
    $attempts = array_values(array_filter($attempts, fn($t) => $t > $now - $window));
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    $attempts[] = $now;
    $_SESSION['rate_limit'][$key] = $attempts;
    return true;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('CSRF token invalid');
    }
}

function require_admin(): void {
    if (empty($_SESSION['user_id'])) {
        $base = '/index.php?page=login';
        header('Location: ' . $base);
        exit;
    }
}

function get_settings(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $setting = $stmt->fetch();
    return $setting ?: [
        'voting_status' => 'draft',
        'theme' => 'light',
        'school_name' => 'SD Negeri Jomblang 2',
        'school_logo' => null,
        'welcome_text' => 'Selamat datang di sistem E-Voting.',
        'voting_start' => null,
        'voting_end' => null,
    ];
}

function get_site_theme(PDO $pdo): string {
    $settings = get_settings($pdo);
    return (($settings['theme'] ?? 'light') === 'dark') ? 'dark' : 'light';
}

function theme_class(string $theme, string $light, string $dark): string {
    return $theme === 'dark' ? $dark : $light;
}

function is_voting_open(PDO $pdo): bool {
    $settings = get_settings($pdo);
    if ($settings['voting_status'] !== 'open') {
        return false;
    }
    $now = time();
    if (!empty($settings['voting_start']) && strtotime($settings['voting_start']) > $now) {
        return false;
    }
    if (!empty($settings['voting_end']) && strtotime($settings['voting_end']) < $now) {
        return false;
    }
    return true;
}

function upload_file(array $file, string $targetDir = __DIR__ . '/../uploads', array $allowedExtensions = [], int $maxSize = 0): string {
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return '';
    }
    if (!empty($file['error'])) {
        return '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions, true)) {
        return '';
    }

    if ($maxSize > 0 && $file['size'] > $maxSize) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $imageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $videoMimes = ['video/mp4', 'video/webm', 'video/quicktime'];
    $docMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    $allowedMimes = array_merge($imageMimes, $videoMimes, $docMimes);

    if (!in_array($mimeType, $allowedMimes, true)) {
        return '';
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }
    $filename = uniqid('file_', true) . '.' . $extension;
    $destination = rtrim($targetDir, '/') . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return '';
    }
    return 'uploads/' . $filename;
}

function generate_voter_number(PDO $pdo): string {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM voters");
    $row = $stmt->fetch();
    $next = ((int)($row['max_id'] ?? 0)) + 1;
    return 'V-' . str_pad((string)$next, 5, '0', STR_PAD_LEFT);
}

function generate_token(): string {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $token = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < 6; $i++) {
        $token .= $characters[random_int(0, $max)];
    }
    return $token;
}

function generate_qr_png(string $payload, string $path): void {
    $qrCode = new Endroid\QrCode\QrCode(
        data: $payload,
        size: 250,
        margin: 10
    );
    $writer = new Endroid\QrCode\Writer\PngWriter();
    $result = $writer->write($qrCode);
    file_put_contents($path, $result->getString());
}

function log_action(PDO $pdo, string $action, string $description = ''): void {
    $stmt = $pdo->prepare('INSERT INTO logs (action, description) VALUES (?, ?)');
    $stmt->execute([$action, $description]);
}
