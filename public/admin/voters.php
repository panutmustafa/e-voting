<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();
$theme = get_site_theme($pdo);
$currentPage = 'voters';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_POST['create_voter'])) {
        $stmt = $pdo->prepare('INSERT INTO voters (voter_number, nik, name, student_name, class_name, phone, email, token, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $voterNumber = generate_voter_number($pdo);
        $token = generate_token();
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $qrPayload = $protocol . $httpHost . '/public/vote.php?voter_number=' . urlencode($voterNumber) . '&token=' . urlencode($token);
        $qrPath = 'uploads/qr_' . uniqid() . '.png';
        generate_qr_png($qrPayload, __DIR__ . '/../../' . $qrPath);
        $stmt->execute([
            $voterNumber,
            trim($_POST['nik']),
            trim($_POST['name']),
            trim($_POST['student_name'] ?? ''),
            trim($_POST['class_name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['email'] ?? ''),
            $token,
            $qrPath,
        ]);
        log_action($pdo, 'create_voter', 'Created voter ' . $voterNumber);
    } elseif (isset($_POST['update_voter'])) {
        $id = (int)$_POST['voter_id'];
        $stmt = $pdo->prepare('UPDATE voters SET nik = ?, name = ?, student_name = ?, class_name = ?, phone = ?, email = ? WHERE id = ?');
        $stmt->execute([
            trim($_POST['nik']),
            trim($_POST['name']),
            trim($_POST['student_name'] ?? ''),
            trim($_POST['class_name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['email'] ?? ''),
            $id
        ]);
        log_action($pdo, 'update_voter', 'Updated voter ID ' . $id);
    } elseif (isset($_POST['delete_voter'])) {
        $id = (int)$_POST['voter_id'];
        $stmt = $pdo->prepare('SELECT qr_code FROM voters WHERE id = ?');
        $stmt->execute([$id]);
        $voter = $stmt->fetch();
        if ($voter && $voter['qr_code'] && file_exists(__DIR__ . '/../../' . $voter['qr_code'])) {
            unlink(__DIR__ . '/../../' . $voter['qr_code']);
        }
        $stmt = $pdo->prepare('DELETE FROM voters WHERE id = ?');
        $stmt->execute([$id]);
        log_action($pdo, 'delete_voter', 'Deleted voter ID ' . $id);
    } elseif (isset($_POST['import_csv'])) {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (!empty($_FILES['csv_file']['tmp_name'])) {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($handle) {
                $header = fgetcsv($handle);
                if ($header) {
                    $header = array_map('trim', $header);
                    $line = 1;
                    while (($row = fgetcsv($handle)) !== false) {
                        $line++;
                        $data = array_combine($header, array_map('trim', $row));

                        $nik = $data['nik'] ?? '';
                        $name = $data['name'] ?? '';

                        if (empty($nik) || empty($name)) {
                            $errors[] = "Baris $line: NIK dan Nama wajib diisi";
                            $skipped++;
                            continue;
                        }

                        $check = $pdo->prepare("SELECT id FROM voters WHERE nik = ?");
                        $check->execute([$nik]);
                        if ($check->fetch()) {
                            $errors[] = "Baris $line: NIK $nik sudah terdaftar";
                            $skipped++;
                            continue;
                        }

                        $voterNumber = generate_voter_number($pdo);
                        $token = generate_token();
                        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $qrPayload = $protocol . $httpHost . '/public/vote.php?voter_number=' . urlencode($voterNumber) . '&token=' . urlencode($token);
                        $qrPath = 'uploads/qr_' . uniqid() . '.png';
                        generate_qr_png($qrPayload, __DIR__ . '/../../' . $qrPath);

                        $stmt = $pdo->prepare('INSERT INTO voters (voter_number, nik, name, student_name, class_name, phone, email, token, qr_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([
                            $voterNumber,
                            $nik,
                            $name,
                            $data['student_name'] ?? '',
                            $data['class_name'] ?? '',
                            $data['phone'] ?? '',
                            $data['email'] ?? '',
                            $token,
                            $qrPath,
                        ]);
                        $imported++;
                    }
                }
                fclose($handle);
            }
            log_action($pdo, 'import_csv', "Imported $imported voters, skipped $skipped");
        }

        $_SESSION['import_result'] = [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
    header('Location: /public/admin/voters.php');
    exit;
}

$voters = $pdo->query('SELECT * FROM voters ORDER BY id DESC')->fetchAll();
$importResult = $_SESSION['import_result'] ?? null;
unset($_SESSION['import_result']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Pemilih</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Outfit', sans-serif;
    }
  </style>
</head>
<body class="min-h-screen <?php echo $theme === 'dark' ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900'; ?>">
<div class="flex min-h-screen">
  <!-- Mobile menu button -->
  <button id="menuBtn" onclick="toggleSidebar()" class="fixed top-4 left-4 z-50 lg:hidden rounded-xl bg-blue-600 p-2.5 text-white shadow-lg">
    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>
  <!-- Overlay -->
  <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 z-30 bg-slate-950/60 hidden lg:hidden"></div>
  <!-- Sidebar -->
  <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full lg:static lg:translate-x-0 lg:z-auto border-r <?php echo $theme === 'dark' ? 'border-slate-800 bg-slate-900/80' : 'border-slate-200 bg-white/90'; ?> p-6 flex flex-col justify-between transition-transform duration-300">
    <div>
      <div class="flex items-center gap-3 px-2 py-1">
        <div class="h-9 w-9 rounded-xl bg-blue-600/10 border border-blue-500/20 flex items-center justify-center text-blue-500">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <h2 class="text-lg font-bold tracking-tight <?php echo $theme === 'dark' ? 'text-white' : 'text-slate-900'; ?>">E-Voting Admin</h2>
      </div>
      
      <nav class="mt-8 space-y-1.5 text-sm">
        <a href="/public/admin/index.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'dashboard' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
          </svg>
          Dashboard
        </a>
        
        <a href="/public/admin/voters.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'voters' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Data Pemilih
        </a>
        
        <a href="/public/admin/candidates.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'candidates' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Kandidat
        </a>
        
        <a href="/public/admin/monitoring.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'monitoring' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Monitoring
        </a>
        
        <a href="/public/admin/results.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'results' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Hasil Voting
        </a>
        
        <a href="/public/admin/settings.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'settings' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Pengaturan
        </a>
      </nav>
    </div>

    <div class="mt-auto space-y-1.5 border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200'; ?> pt-4">
      <a href="/index.php?page=home" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 <?php echo $theme === 'dark' ? 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
        <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Halaman Beranda
      </a>
      
      <!-- Theme Toggle Togle -->
      <form method="post" action="/public/admin/toggle_theme.php" class="block">
        <?php csrf_field(); ?>
        <button type="submit" class="w-full flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 <?php echo $theme === 'dark' ? 'text-amber-400 hover:bg-slate-800' : 'text-blue-600 hover:bg-slate-100'; ?>">
          <?php if ($theme === 'dark'): ?>
            <svg class="h-5 w-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.728 12.728A9 9 0 115.636 5.636a9 9 0 0112.728 12.728z" />
            </svg>
            Mode Terang (Light)
          <?php else: ?>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            Mode Gelap (Dark)
          <?php endif; ?>
        </button>
      </form>
      
      <a href="/index.php?page=logout" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 text-red-500 hover:bg-red-500/10">
        <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Keluar (Logout)
      </a>
    </div>
  </aside>
  <main class="flex-1 p-8 pt-16 lg:pt-8">
    <div class="rounded-3xl border <?php echo $theme === 'dark' ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-white'; ?> p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Kelola Pemilih</p>
          <h1 class="text-2xl font-semibold">Data Pemilih</h1>
        </div>
        <div class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">Total: <?php echo count($voters); ?></div>
      </div>
      <form method="post" class="mt-8 grid gap-4 md:grid-cols-2">
        <?php csrf_field(); ?>
        <input type="hidden" name="create_voter" value="1">
        <input name="nik" placeholder="NIK" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3" required>
        <input name="name" placeholder="Nama Pemilih" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3" required>
        <input name="student_name" placeholder="Nama Siswa" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="class_name" placeholder="Kelas" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="phone" placeholder="Nomor HP" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="email" placeholder="Email" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <div class="md:col-span-2">
          <button class="rounded-2xl bg-blue-600 px-5 py-3 font-semibold">Tambah Pemilih</button>
        </div>
      </form>
      <?php if ($importResult): ?>
        <div class="mt-6 rounded-2xl border <?php echo $importResult['imported'] > 0 ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-red-500/30 bg-red-500/5'; ?> p-4">
          <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 <?php echo $importResult['imported'] > 0 ? 'text-emerald-500' : 'text-red-500'; ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
              <p class="font-semibold <?php echo $importResult['imported'] > 0 ? 'text-emerald-500' : 'text-red-500'; ?>">
                <?php echo $importResult['imported']; ?> data berhasil diimpor, <?php echo $importResult['skipped']; ?> dilewati.
              </p>
              <?php if (!empty($importResult['errors'])): ?>
                <ul class="mt-2 space-y-1 text-red-400">
                  <?php foreach (array_slice($importResult['errors'], 0, 10) as $err): ?>
                    <li>&bull; <?php echo htmlspecialchars($err); ?></li>
                  <?php endforeach; ?>
                  <?php if (count($importResult['errors']) > 10): ?>
                    <li>&bull; ...dan <?php echo count($importResult['errors']) - 10; ?> error lainnya</li>
                  <?php endif; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Import CSV -->
      <div class="mt-6 rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-900/50' : 'border-slate-200 bg-slate-50'; ?> p-5">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
            </svg>
            <span class="text-sm font-semibold">Import Data dari CSV</span>
          </div>
          <a href="/public/admin/templates/voters_template.csv" class="text-xs font-semibold text-blue-500 hover:underline">Download Template</a>
        </div>
        <form method="post" enctype="multipart/form-data" class="mt-4 flex items-end gap-3">
          <?php csrf_field(); ?>
          <input type="hidden" name="import_csv" value="1">
          <input type="file" name="csv_file" accept=".csv" required class="flex-1 rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm file:mr-3 file:rounded-xl file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white">
          <button type="submit" class="rounded-2xl bg-blue-600 hover:bg-blue-500 px-5 py-3 text-sm font-semibold text-white transition-colors">Import</button>
        </form>
      </div>

      <div class="mt-8 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">
              <th class="px-3 py-3">Nomor Pemilih</th><th class="px-3 py-3">Nama</th><th class="px-3 py-3">NIK</th><th class="px-3 py-3">Status</th><th class="px-3 py-3">Token</th><th class="px-3 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($voters as $voter): ?>
              <tr class="border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200'; ?>">
                <td class="px-3 py-3"><?php echo htmlspecialchars($voter['voter_number']); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($voter['name']); ?></td>
                <td class="px-3 py-3"><?php echo htmlspecialchars($voter['nik']); ?></td>
                <td class="px-3 py-3"><?php echo $voter['has_voted'] === '1' ? 'Sudah Memilih' : 'Belum Memilih'; ?></td>
                <td class="px-3 py-3">
                  <?php echo htmlspecialchars($voter['token']); ?> 
                  <a href="/public/admin/voter_card.php?id=<?php echo (int)$voter['id']; ?>" class="ml-2 text-blue-500 hover:underline">Lihat Kartu</a>
                </td>
                <td class="px-3 py-3 text-right flex items-center justify-end gap-2">
                  <button onclick='openEditModal(<?php echo json_encode($voter, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                          class="rounded-lg bg-yellow-500/10 border border-yellow-500/25 hover:bg-yellow-500/25 px-3 py-1.5 text-xs font-semibold text-yellow-500 transition-colors">
                    Edit
                  </button>
                  <form method="post" action="voters.php" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pemilih ini?')">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="delete_voter" value="1">
                    <input type="hidden" name="voter_id" value="<?php echo $voter['id']; ?>">
                    <button type="submit" 
                            class="rounded-lg bg-red-500/10 border border-red-500/25 hover:bg-red-500/25 px-3 py-1.5 text-xs font-semibold text-red-500 transition-colors">
                      Hapus
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Edit Voter Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="w-full max-w-lg rounded-3xl border <?php echo $theme === 'dark' ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-white'; ?> p-6 shadow-2xl">
    <div class="flex items-center justify-between border-b <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-100'; ?> pb-4">
      <h2 class="text-xl font-bold">Edit Data Pemilih</h2>
      <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-200">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <form method="post" action="voters.php" class="mt-4 grid gap-4 sm:grid-cols-2">
      <?php csrf_field(); ?>
      <input type="hidden" name="update_voter" value="1">
      <input type="hidden" id="edit_voter_id" name="voter_id">
      
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">NIK</label>
        <input id="edit_nik" name="nik" required class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Pemilih</label>
        <input id="edit_name" name="name" required class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Siswa</label>
        <input id="edit_student_name" name="student_name" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Kelas</label>
        <input id="edit_class_name" name="class_name" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nomor HP</label>
        <input id="edit_phone" name="phone" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</label>
        <input id="edit_email" name="email" type="email" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      
      <div class="sm:col-span-2 mt-4 flex justify-end gap-3 border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-100'; ?> pt-4">
        <button type="button" onclick="closeEditModal()" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 hover:bg-slate-800' : 'border-slate-300 hover:bg-slate-100'; ?> px-5 py-3 font-semibold text-sm transition-colors">Batal</button>
        <button type="submit" class="rounded-2xl bg-blue-600 hover:bg-blue-500 px-5 py-3 font-semibold text-sm text-white transition-colors">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(voter) {
    document.getElementById('edit_voter_id').value = voter.id;
    document.getElementById('edit_nik').value = voter.nik;
    document.getElementById('edit_name').value = voter.name;
    document.getElementById('edit_student_name').value = voter.student_name || '';
    document.getElementById('edit_class_name').value = voter.class_name || '';
    document.getElementById('edit_phone').value = voter.phone || '';
    document.getElementById('edit_email').value = voter.email || '';
    document.getElementById('editModal').classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
  }
</script>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('sidebarOverlay').classList.toggle('hidden');}</script>
</body>
</html>
