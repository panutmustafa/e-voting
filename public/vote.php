<?php
require_once __DIR__ . '/../src/functions.php';
init_session();
require_once __DIR__ . '/../config/database.php';

// Auto-login via GET parameters (e.g. from QR Code scan)
if (isset($_GET['voter_number']) && isset($_GET['token'])) {
    $voterNumber = trim($_GET['voter_number']);
    $token = trim($_GET['token']);
    
    $stmt = $pdo->prepare('SELECT * FROM voters WHERE voter_number = ? AND token = ? LIMIT 1');
    $stmt->execute([$voterNumber, $token]);
    $voter = $stmt->fetch();

    if ($voter) {
        if ($voter['has_voted'] === '1') {
            $_SESSION['vote_error'] = 'Anda sudah menggunakan hak suara.';
            header('Location: vote.php');
            exit;
        }

        if (!is_voting_open($pdo)) {
            $_SESSION['vote_error'] = 'Pemungutan suara belum dibuka.';
            header('Location: vote.php');
            exit;
        }

        $_SESSION['voter_id'] = $voter['id'];
        $_SESSION['voter_name'] = $voter['name'];
        header('Location: vote.php?step=ballot');
        exit;
    } else {
        $_SESSION['vote_error'] = 'Nomor pemilih atau token tidak valid.';
        header('Location: vote.php');
        exit;
    }
}

if (($_GET['action'] ?? '') === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $candidateId = (int)($_POST['candidate_id'] ?? 0);
    $voterId = (int)($_SESSION['voter_id'] ?? 0);
    if (!$voterId || !$candidateId) {
        header('Location: vote.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT has_voted FROM voters WHERE id = ?");
    $stmt->execute([$voterId]);
    $voter = $stmt->fetch();
    if ($voter && $voter['has_voted'] === '1') {
        session_destroy();
        header('Location: vote.php?status=already_voted');
        exit;
    }

    if (!is_voting_open($pdo)) {
        $_SESSION['vote_error'] = 'Pemungutan suara belum dibuka.';
        header('Location: vote.php');
        exit;
    }

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('INSERT INTO votes (voter_id, candidate_id) VALUES (?, ?)');
    $stmt->execute([$voterId, $candidateId]);
    $stmt = $pdo->prepare("UPDATE voters SET has_voted='1' WHERE id = ?");
    $stmt->execute([$voterId]);
    $pdo->commit();

    session_destroy();
    header('Location: vote.php?status=success');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $voterNumber = trim($_POST['voter_number'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM voters WHERE voter_number = ? AND token = ? LIMIT 1');
    $stmt->execute([$voterNumber, $token]);
    $voter = $stmt->fetch();

    if (!$voter) {
        $_SESSION['vote_error'] = 'Nomor pemilih atau token tidak valid.';
        header('Location: vote.php');
        exit;
    }

    if ($voter['has_voted'] === '1') {
        $_SESSION['vote_error'] = 'Anda sudah menggunakan hak suara.';
        header('Location: vote.php');
        exit;
    }

    if (!is_voting_open($pdo)) {
        $_SESSION['vote_error'] = 'Pemungutan suara belum dibuka.';
        header('Location: vote.php');
        exit;
    }

    $_SESSION['voter_id'] = $voter['id'];
    $_SESSION['voter_name'] = $voter['name'];
    header('Location: vote.php?step=ballot');
    exit;
}

if (($_GET['status'] ?? '') === 'success') {
    include __DIR__ . '/../src/views/vote_success.php';
    exit;
}

if (($_GET['step'] ?? '') === 'ballot') {
    $voterId = (int)($_SESSION['voter_id'] ?? 0);
    if (!$voterId) {
        header('Location: vote.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT has_voted FROM voters WHERE id = ?");
    $stmt->execute([$voterId]);
    $voter = $stmt->fetch();
    if ($voter && $voter['has_voted'] === '1') {
        $_SESSION['vote_error'] = 'Anda sudah menggunakan hak suara.';
        header('Location: vote.php');
        exit;
    }
    if (!is_voting_open($pdo)) {
        $_SESSION['vote_error'] = 'Pemungutan suara belum dibuka.';
        header('Location: vote.php');
        exit;
    }
    $candidates = $pdo->query("SELECT * FROM candidates WHERE is_active='1' ORDER BY id ASC")->fetchAll();
    include __DIR__ . '/../src/views/vote_ballot.php';
    exit;
}

if (($_GET['status'] ?? '') === 'already_voted') {
    include __DIR__ . '/../src/views/vote_already_voted.php';
    exit;
}

include __DIR__ . '/../src/views/vote_entry.php';
