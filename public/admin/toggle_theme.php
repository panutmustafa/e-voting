<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = get_settings($pdo);
    $newTheme = ($current['theme'] ?? 'light') === 'dark' ? 'light' : 'dark';

    $stmt = $pdo->prepare('UPDATE settings SET voting_start=?, voting_end=?, voting_status=?, theme=?, school_name=?, school_logo=?, welcome_text=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
    $stmt->execute([
        !empty($current['voting_start']) ? $current['voting_start'] : null,
        !empty($current['voting_end']) ? $current['voting_end'] : null,
        $current['voting_status'] ?? 'draft',
        $newTheme,
        $current['school_name'] ?? 'SD Negeri Jomblang 2',
        $current['school_logo'] ?? null,
        $current['welcome_text'] ?? null,
        $current['id'],
    ]);

    $referrer = $_SERVER['HTTP_REFERER'] ?? '/e-voting/public/admin/index.php';
    header('Location: ' . $referrer);
    exit;
}

header('Location: /e-voting/public/admin/index.php');
exit;
