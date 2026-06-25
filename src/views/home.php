<?php $theme = get_site_theme($pdo); ?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>E-Voting SD Negeri Jomblang 2</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Inter, ui-sans-serif, system-ui; }
    .glass { backdrop-filter: blur(24px); }
    .glass-light { background: rgba(255,255,255,0.85); color: #0f172a; }
    .glass-dark { background: rgba(15,23,42,0.82); color: #f8fafc; }
  </style>
</head>
<body class="min-h-full <?php echo $theme === 'dark' ? 'bg-slate-950 text-slate-100' : 'bg-slate-50 text-slate-900'; ?>">
  <div class="relative overflow-hidden">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.35),_transparent_35%),radial-gradient(circle_at_bottom_right,_rgba(234,179,8,0.25),_transparent_30%)]"></div>
    <header class="relative mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
      <div>
        <h1 class="text-2xl font-semibold">E-Voting SDNJomblang 2</h1>
      </div>
    </header>

    <main class="relative mx-auto grid max-w-7xl gap-8 px-6 pb-16 lg:grid-cols-[1.1fr_0.9fr] lg:px-10">
      <section class="space-y-8 py-12 lg:py-20">
        <div class="inline-flex rounded-full border border-blue-400/40 bg-blue-500/10 px-4 py-2 text-sm text-blue-700">Pemilihan Pengurus Komite Sekolah</div>
        <div class="space-y-4">
          <h2 class="text-4xl font-semibold leading-tight sm:text-5xl">Pemilihan Pengurus Komite Sekolah SD Negeri Jomblang 2</h2>
          <p class="max-w-2xl text-lg <?php echo $theme === 'dark' ? 'text-slate-300' : 'text-slate-700'; ?>">Bersama membangun pendidikan yang berkualitas melalui sistem pemungutan suara digital, aman, kredibel, dan transparan.</p>
        </div>
        <div class="flex flex-wrap gap-4">
          <a href="/vote.php" class="rounded-full bg-amber-500 px-6 py-3 font-semibold text-slate-950">Mulai Voting</a>
          <a href="#candidates" class="rounded-full border <?php echo $theme === 'dark' ? 'border-slate-700 text-slate-100' : 'border-slate-300 text-slate-800'; ?> px-6 py-3">Lihat Kandidat</a>
          <a href="/index.php?page=voter-card" class="rounded-full border <?php echo $theme === 'dark' ? 'border-slate-700 text-slate-100' : 'border-slate-300 text-slate-800'; ?> px-6 py-3">Unduh Kartu Pemilih</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
          <div class="glass rounded-2xl p-5 <?php echo $theme === 'dark' ? 'glass-dark' : 'glass-light'; ?>">
            <p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Total Pemilih</p>
            <p class="mt-2 text-3xl font-semibold"><?php echo $pdo->query("SELECT COUNT(*) AS total FROM voters")->fetch()['total']; ?></p>
          </div>
          <div class="glass rounded-2xl p-5 <?php echo $theme === 'dark' ? 'glass-dark' : 'glass-light'; ?>">
            <p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Sudah Memilih</p>
            <p class="mt-2 text-3xl font-semibold"><?php echo $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='1'")->fetch()['total']; ?></p>
          </div>
          <div class="glass rounded-2xl p-5 <?php echo $theme === 'dark' ? 'glass-dark' : 'glass-light'; ?>">
            <p class="text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>">Kandidat</p>
            <p class="mt-2 text-3xl font-semibold"><?php echo $pdo->query("SELECT COUNT(*) AS total FROM candidates WHERE is_active='1'")->fetch()['total']; ?></p>
          </div>
        </div>
      </section>

      <section class="glass rounded-3xl border <?php echo $theme === 'dark' ? 'border-white/10' : 'border-slate-200'; ?> p-8 shadow-2xl <?php echo $theme === 'dark' ? 'glass-dark' : 'glass-light'; ?>">
        <h3 class="text-xl font-semibold">Statistik Singkat</h3>
        <canvas id="summaryChart" class="mt-6 h-64"></canvas>
      </section>
    </main>
  </div>

  <section id="candidates" class="mx-auto max-w-7xl px-6 py-16 lg:px-10">
    <div class="mb-10 flex items-end justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.35em] text-amber-400">Profil Kandidat</p>
        <h3 class="text-3xl font-semibold">Calon Pengurus Komite</h3>
      </div>
    </div>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($candidates as $candidate): ?>
        <article class="group overflow-hidden rounded-3xl border <?php echo $theme === 'dark' ? 'border-white/10 bg-slate-900/80' : 'border-slate-200 bg-white shadow-lg'; ?> shadow-xl">
          <img src="/<?php echo htmlspecialchars($candidate['photo'] ?: 'uploads/default.png'); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105" onerror="this.src='/uploads/default.png'; this.style.objectFit='contain'; this.style.padding='2rem'; this.style.background='#0f172a'">
          <div class="p-6">
            <p class="text-sm uppercase tracking-[0.25em] text-amber-500"><?php echo htmlspecialchars($candidate['position']); ?></p>
            <h4 class="mt-2 text-2xl font-semibold"><?php echo htmlspecialchars($candidate['name']); ?></h4>
            <p class="mt-4 text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>"><?php echo htmlspecialchars(substr($candidate['vision'], 0, 120)); ?>...</p>
            <a href="/index.php?page=candidate-profile&id=<?php echo (int)$candidate['id']; ?>" class="mt-6 inline-flex rounded-full bg-blue-600 px-4 py-2 text-sm font-medium">Lihat Profil Lengkap</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <script>
    const ctx = document.getElementById('summaryChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Sudah Memilih', 'Belum Memilih'],
          datasets: [{
            data: [<?php echo $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='1'")->fetch()['total']; ?>, <?php echo $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='0'")->fetch()['total']; ?>],
            backgroundColor: ['#2563eb', '#f59e0b']
          }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
      });
    }
  </script>
</body>
</html>
