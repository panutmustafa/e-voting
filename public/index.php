<?php
require_once __DIR__ . '/../src/functions.php';
init_session();
require_once __DIR__ . '/../config/database.php';

$action = $_GET['page'] ?? 'home';

if ($action === 'home') {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch();
    $candidates = $pdo->query("SELECT * FROM candidates WHERE is_active='1' ORDER BY id ASC")->fetchAll();
    include __DIR__ . '/../src/views/home.php';
} elseif ($action === 'login') {
    include __DIR__ . '/../src/views/login.php';
} elseif ($action === 'logout') {
    session_destroy();
    header('Location: /e-voting/index.php?page=login');
    exit;
} elseif ($action === 'voter-card') {
    include __DIR__ . '/voter-card.php';
    exit;
} elseif ($action === 'candidate-profile') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
    $stmt->execute([$id]);
    $candidate = $stmt->fetch();
    include __DIR__ . '/../src/views/candidate-profile.php';
} else {
    $stmt = $pdo->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
    $settings = $stmt->fetch();
    $candidates = $pdo->query("SELECT * FROM candidates WHERE is_active='1' ORDER BY id ASC")->fetchAll();
    include __DIR__ . '/../src/views/home.php';
}
