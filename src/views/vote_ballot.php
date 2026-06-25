<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Memilih Kandidat</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="mx-auto max-w-6xl px-6 py-16">
    <div class="mb-8 rounded-3xl border border-white/10 bg-slate-900/80 p-6">
      <h1 class="text-2xl font-semibold">Halo, <?php echo htmlspecialchars($_SESSION['voter_name'] ?? 'Pemilih'); ?></h1>
      <p class="mt-2 text-sm text-slate-400">Pilih kandidat dengan bijak. Suara Anda akan tersimpan secara aman.</p>
    </div>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
      <?php foreach ($candidates as $candidate): ?>
        <form method="post" action="/vote.php?action=submit" class="vote-form rounded-3xl border border-white/10 bg-slate-900/80 p-6 shadow-xl" data-candidate-name="<?php echo htmlspecialchars($candidate['name']); ?>" data-candidate-position="<?php echo htmlspecialchars($candidate['position']); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
          <input type="hidden" name="candidate_id" value="<?php echo (int)$candidate['id']; ?>">
          <img src="/<?php echo htmlspecialchars($candidate['photo'] ?: 'uploads/default.png'); ?>" class="h-48 w-full rounded-2xl object-cover" onerror="this.src='/uploads/default.png'; this.style.objectFit='contain'; this.style.padding='2rem'; this.style.background='#0f172a'">
          <h2 class="mt-4 text-xl font-semibold"><?php echo htmlspecialchars($candidate['name']); ?></h2>
          <p class="mt-2 text-sm text-slate-400"><?php echo htmlspecialchars($candidate['position']); ?></p>
          <p class="mt-4 text-sm text-slate-300"><?php echo htmlspecialchars(substr($candidate['vision'], 0, 120)); ?>...</p>
          <button type="button" class="pick-btn mt-6 w-full rounded-2xl bg-amber-500 px-4 py-3 font-semibold text-slate-950">Pilih Kandidat Ini</button>
        </form>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Modal Konfirmasi -->
  <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4">
    <div class="w-full max-w-md rounded-3xl border border-white/10 bg-slate-900 p-8 shadow-2xl">
      <h2 class="text-xl font-semibold">Konfirmasi Pilihan</h2>
      <p class="mt-2 text-sm text-slate-400">Anda akan memilih:</p>
      <div class="mt-4 rounded-2xl bg-slate-800/80 p-4">
        <p id="modalCandidateName" class="text-lg font-semibold"></p>
        <p id="modalCandidatePosition" class="mt-1 text-sm text-slate-400"></p>
      </div>
      <p class="mt-4 text-sm text-amber-400">Setelah memilih, Anda tidak dapat mengubah suara.</p>
      <div class="mt-6 flex gap-3">
        <button id="cancelBtn" class="flex-1 rounded-2xl border border-slate-700 px-4 py-3 font-medium text-slate-300 hover:bg-slate-800">Batal</button>
        <button id="confirmBtn" class="flex-1 rounded-2xl bg-emerald-600 px-4 py-3 font-semibold text-white hover:bg-emerald-700">Yakin, Pilih</button>
      </div>
    </div>
  </div>

  <script>
    (function() {
      var modal = document.getElementById('confirmModal');
      var cancelBtn = document.getElementById('cancelBtn');
      var confirmBtn = document.getElementById('confirmBtn');
      var modalName = document.getElementById('modalCandidateName');
      var modalPosition = document.getElementById('modalCandidatePosition');
      var activeForm = null;

      document.querySelectorAll('.pick-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          activeForm = btn.closest('.vote-form');
          modalName.textContent = activeForm.getAttribute('data-candidate-name');
          modalPosition.textContent = activeForm.getAttribute('data-candidate-position');
          modal.classList.remove('hidden');
          modal.classList.add('flex');
        });
      });

      function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        activeForm = null;
      }

      cancelBtn.addEventListener('click', closeModal);

      modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
      });

      confirmBtn.addEventListener('click', function() {
        if (activeForm) {
          activeForm.submit();
        }
      });
    })();
  </script>
</body>
</html>
