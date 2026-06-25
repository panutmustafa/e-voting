<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Voting - E-Voting</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="mx-auto flex min-h-screen max-w-2xl items-center justify-center px-6 py-16">
    <div class="w-full rounded-3xl border border-white/10 bg-slate-900/80 p-8 shadow-2xl">
      <h1 class="text-2xl font-semibold">Masuk ke Halaman Voting</h1>
      <p class="mt-2 text-sm text-slate-400">Gunakan nomor pemilih dan token rahasia Anda.</p>
      <form method="post" class="mt-8 space-y-4">
        <?php csrf_field(); ?>
        <input name="voter_number" placeholder="Nomor Pemilih" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3" required>
        <input name="token" placeholder="Token" class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3" required>
        <button class="w-full rounded-2xl bg-blue-600 px-4 py-3 font-semibold">Lanjutkan</button>
      </form>
      <?php if (!empty($_SESSION['vote_error'])): ?>
        <p class="mt-4 rounded-2xl bg-amber-500/10 p-3 text-sm text-amber-300"><?php echo htmlspecialchars($_SESSION['vote_error']); ?></p>
        <?php unset($_SESSION['vote_error']); ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
