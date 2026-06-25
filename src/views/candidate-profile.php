<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($candidate['name'] ?? 'Kandidat'); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="mx-auto max-w-6xl px-6 py-16">
    <a href="/index.php?page=home" class="mb-8 inline-flex rounded-full border border-slate-700 px-4 py-2 text-sm">← Kembali</a>
    <div class="overflow-hidden rounded-3xl border border-white/10 bg-slate-900/80 shadow-2xl lg:grid lg:grid-cols-[0.85fr_1.15fr]">
      <img src="/<?php echo htmlspecialchars($candidate['photo'] ?: 'uploads/default.png'); ?>" alt="<?php echo htmlspecialchars($candidate['name']); ?>" class="h-full w-full object-cover" onerror="this.src='/uploads/default.png'; this.style.objectFit='contain'; this.style.padding='2rem'; this.style.background='#0f172a'">
      <div class="p-8 lg:p-10">
        <p class="text-sm uppercase tracking-[0.25em] text-amber-400"><?php echo htmlspecialchars($candidate['position']); ?></p>
        <h1 class="mt-3 text-3xl font-semibold"><?php echo htmlspecialchars($candidate['name']); ?></h1>
        <div class="mt-6 space-y-4 text-slate-300">
          <div><h2 class="font-semibold text-white">Visi</h2><p><?php echo nl2br(htmlspecialchars($candidate['vision'])); ?></p></div>
          <div><h2 class="font-semibold text-white">Misi</h2><p><?php echo nl2br(htmlspecialchars($candidate['mission'])); ?></p></div>
          <div><h2 class="font-semibold text-white">Program Kerja</h2><p><?php echo nl2br(htmlspecialchars($candidate['program'])); ?></p></div>
          <div><h2 class="font-semibold text-white">Motto</h2><p><?php echo htmlspecialchars($candidate['motto']); ?></p></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
