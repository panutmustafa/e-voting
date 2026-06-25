<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';

verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit('admin_login')) {
        $_SESSION['login_error'] = 'Terlalu banyak percobaan login. Silakan coba lagi nanti.';
        header('Location: /e-voting/index.php?page=login');
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: /e-voting/public/admin/index.php');
        exit;
    }

    $_SESSION['login_error'] = 'Username atau password salah.';
    header('Location: /e-voting/index.php?page=login');
    exit;
}

header('Location: /e-voting/index.php?page=login');
