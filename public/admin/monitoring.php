<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();
$theme = get_site_theme($pdo);
$currentPage = 'monitoring';

$stats = [
    'total_voters' => $pdo->query("SELECT COUNT(*) AS total FROM voters")->fetch()['total'],
    'has_voted' => $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='1'")->fetch()['total'],
    'not_voted' => $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='0'")->fetch()['total'],
];
$recentVotes = $pdo->query("SELECT v.id, v.created_at, vo.name, c.name AS candidate_name FROM votes v JOIN voters vo ON vo.id=v.voter_id JOIN candidates c ON c.id=v.candidate_id ORDER BY v.id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monitoring</title>
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

      <!-- Theme Toggle -->
      <form method="post" action="/public/admin/toggle_theme.php" class="block">
        <?php csrf_field(); ?>
        <button type="submit" class="w-full flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 <?php echo $theme === 'dark' ? 'text-amber-400 hover:bg-slate-800' : 'text-blue-600 hover:bg-slate-100'; ?>">
          <?php if ($theme === 'dark'): ?>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
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
      <h1 class="text-2xl font-semibold">Monitoring Voting</h1>
      <div class="mt-8 grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200 bg-slate-50'; ?> p-5"><p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Jumlah Suara Masuk</p><p class="mt-2 text-3xl font-semibold"><?php echo (int)$stats['has_voted']; ?></p></div>
        <div class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200 bg-slate-50'; ?> p-5"><p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Pemilih Belum Memilih</p><p class="mt-2 text-3xl font-semibold"><?php echo (int)$stats['not_voted']; ?></p></div>
        <div class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200 bg-slate-50'; ?> p-5"><p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Partisipasi</p><p class="mt-2 text-3xl font-semibold"><?php echo $stats['total_voters'] ? round(($stats['has_voted'] / $stats['total_voters']) * 100, 1) : 0; ?>%</p></div>
      </div>
      <div class="mt-8 overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead><tr class="text-left <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>"><th class="px-3 py-3">Waktu</th><th class="px-3 py-3">Pemilih</th><th class="px-3 py-3">Kandidat</th></tr></thead>
          <tbody>
            <?php foreach ($recentVotes as $vote): ?>
              <tr class="border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200'; ?>"><td class="px-3 py-3"><?php echo htmlspecialchars($vote['created_at']); ?></td><td class="px-3 py-3"><?php echo htmlspecialchars($vote['name']); ?></td><td class="px-3 py-3"><?php echo htmlspecialchars($vote['candidate_name']); ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('sidebarOverlay').classList.toggle('hidden');}</script>
</body>
</html>
