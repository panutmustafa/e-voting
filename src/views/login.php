<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - E-Voting</title>
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
<body class="min-h-screen bg-slate-950 text-slate-100 relative overflow-hidden flex items-center justify-center px-4">
  <!-- Glowing Background Orbs -->
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.25),_transparent_40%),radial-gradient(circle_at_bottom_right,_rgba(234,179,8,0.18),_transparent_35%)]"></div>
  
  <!-- Subtle Grid Pattern -->
  <div class="absolute inset-0 bg-[linear-gradient(to_right,#0f172a_1px,transparent_1px),linear-gradient(to_bottom,#0f172a_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] opacity-60"></div>

  <div class="w-full max-w-md relative z-10">
    <!-- Back to Beranda Link at the top -->
    <div class="mb-4 flex justify-start">
      <a href="/e-voting/index.php?page=home" class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition-colors group">
        <svg class="h-4 w-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Kembali ke Beranda
      </a>
    </div>

    <!-- Login Card -->
    <div class="rounded-3xl border border-slate-800 bg-slate-900/75 backdrop-blur-xl p-8 shadow-2xl shadow-blue-950/20">
      <div class="flex items-center gap-3 mb-6">
        <div class="h-10 w-10 rounded-2xl bg-blue-600/10 border border-blue-500/20 flex items-center justify-center text-blue-500">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-white">Login Administrator</h1>
          <p class="text-xs text-slate-400">Masuk untuk mengelola pemilihan digital.</p>
        </div>
      </div>

      <!-- Error Alert -->
      <?php if (isset($_SESSION['login_error'])): ?>
        <div class="mb-5 rounded-2xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400 flex items-start gap-3">
          <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <span><?php echo htmlspecialchars($_SESSION['login_error']); ?></span>
        </div>
        <?php unset($_SESSION['login_error']); ?>
      <?php endif; ?>

      <form method="post" action="/e-voting/public/admin/login.php" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        
        <div>
          <label for="username" class="mb-2 block text-sm font-medium text-slate-300">Username</label>
          <div class="relative">
            <input type="text" id="username" name="username" autocomplete="username" required
                   placeholder="Masukkan username"
                   class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all duration-200 text-sm placeholder-slate-500">
          </div>
        </div>

        <div>
          <label for="current-password" class="mb-2 block text-sm font-medium text-slate-300">Password</label>
          <div class="relative">
            <input type="password" id="current-password" name="password" autocomplete="current-password" required
                   placeholder="Masukkan password"
                   class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 pl-4 pr-12 py-3.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all duration-200 text-sm placeholder-slate-500">
            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-200 transition-colors" title="Tampilkan/Sembunyikan password">
              <!-- Eye Icon (Open) -->
              <svg id="eye-icon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              <!-- Eye Icon (Closed) -->
              <svg id="eye-slash-icon" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858-.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" 
                class="w-full mt-2 rounded-2xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] py-3.5 font-semibold text-white shadow-lg shadow-blue-600/10 hover:shadow-blue-600/20 outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200">
          Masuk
        </button>
      </form>
    </div>
  </div>

  <script>
    const passwordInput = document.getElementById('current-password');
    const togglePasswordBtn = document.getElementById('toggle-password');
    const eyeIcon = document.getElementById('eye-icon');
    const eyeSlashIcon = document.getElementById('eye-slash-icon');

    togglePasswordBtn.addEventListener('click', () => {
      const isPassword = passwordInput.getAttribute('type') === 'password';
      passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
      if (isPassword) {
        eyeIcon.classList.add('hidden');
        eyeSlashIcon.classList.remove('hidden');
      } else {
        eyeIcon.classList.remove('hidden');
        eyeSlashIcon.classList.add('hidden');
      }
    });
  </script>
</body>
</html>
