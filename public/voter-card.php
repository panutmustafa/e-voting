<?php
require_once __DIR__ . '/../src/functions.php';
init_session();
require_once __DIR__ . '/../config/database.php';

// Handle PDF download
if (isset($_GET['download']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare('SELECT * FROM voters WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $voter = $stmt->fetch();
    if (!$voter) {
        exit('Pemilih tidak ditemukan');
    }

    $settings = get_settings($pdo);
    $schoolName = $settings['school_name'] ?? 'SD Negeri Jomblang 2';
    $logo = $settings['school_logo'] ?? '';
    $logoPath = $logo ? __DIR__ . '/../' . $logo : '';

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f1f5f9; padding: 30px; }
        .card { max-width: 520px; margin: 0 auto; border-radius: 28px; padding: 36px 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.2); position: relative; overflow: hidden; }
        .card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 6px; background: linear-gradient(90deg, #2563eb, #3b82f6, #60a5fa); }
        .header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 18px; border-bottom: 2px solid #e2e8f0; }
        .logo-area { width: 54px; height: 54px; border-radius: 14px; background: #dbeafe; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
        .logo-area img { width: 100%; height: 100%; object-fit: cover; }
        .header-text { flex: 1; }
        .header-text h1 { font-size: 22px; font-weight: 800; color: #1e3a5f; margin: 0; line-height: 1.2; }
        .header-text p { font-size: 12px; color: #64748b; margin: 3px 0 0; }
        .badge { display: inline-flex; align-items: center; gap: 5px; background: #dbeafe; color: #1d4ed8; padding: 4px 14px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; height: fit-content; }
        .badge-voted { background: #d1fae5; color: #059669; }
        .fields { display: grid; gap: 10px; }
        .field { padding: 11px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; }
        .field-label { font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; margin: 0 0 2px; }
        .field-value { font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; }
        .token-value { font-family: "Courier New", monospace; letter-spacing: 0.15em; font-size: 18px; color: #1d4ed8; background: #dbeafe; padding: 2px 12px; border-radius: 6px; display: inline-block; }
        .footer { margin-top: 20px; padding-top: 14px; border-top: 2px solid #e2e8f0; text-align: center; font-size: 9px; color: #94a3b8; line-height: 1.5; }
    </style></head><body>
    <div class="card">
        <div class="header">'
        . ($logoPath && file_exists($logoPath)
            ? '<div class="logo-area"><img src="file://' . realpath($logoPath) . '"></div>'
            : '<div class="logo-area"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>')
        . '<div class="header-text"><h1>Kartu Pemilih</h1><p>' . htmlspecialchars($schoolName) . '</p></div>
            <div class="badge' . ($voter['has_voted'] === '1' ? ' badge-voted' : '') . '">' . ($voter['has_voted'] === '1' ? '&#10003; Sudah Memilih' : 'Belum Memilih') . '</div>
        </div>
        <div class="fields">
            <div class="field"><p class="field-label">Nomor Pemilih</p><p class="field-value">' . htmlspecialchars($voter['voter_number']) . '</p></div>
            <div class="field"><p class="field-label">Nama Lengkap</p><p class="field-value">' . htmlspecialchars($voter['name']) . '</p></div>
            <div class="field"><p class="field-label">NIK</p><p class="field-value">' . htmlspecialchars($voter['nik']) . '</p></div>'
        . ($voter['student_name'] ? '<div class="field"><p class="field-label">Nama Siswa</p><p class="field-value">' . htmlspecialchars($voter['student_name']) . '</p></div>' : '')
        . ($voter['class_name'] ? '<div class="field"><p class="field-label">Kelas</p><p class="field-value">' . htmlspecialchars($voter['class_name']) . '</p></div>' : '')
        . '<div class="field"><p class="field-label">Token Akses</p><p class="field-value"><span class="token-value">' . htmlspecialchars($voter['token']) . '</span></p></div>
        </div>
        <div class="footer">Kartu ini adalah bukti terdaftar sebagai pemilih dalam pemilihan ' . htmlspecialchars($schoolName) . '</div>
    </div>
    </body></html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper([0, 0, 420, 620], 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="kartu-pemilih-' . $voter['voter_number'] . '.pdf"');
    echo $dompdf->output();
    exit;
}

$voters = $pdo->query('SELECT id, voter_number, nik, name, student_name, class_name, has_voted FROM voters ORDER BY name ASC')->fetchAll();
$settings = get_settings($pdo);
$schoolName = $settings['school_name'] ?? 'SD Negeri Jomblang 2';
$logo = $settings['school_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Unduh Kartu Pemilih - <?php echo htmlspecialchars($schoolName); ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    [x-cloak] { display: none !important; }
    .autocomplete-items { max-height: 240px; overflow-y: auto; scrollbar-width: thin; }
    .autocomplete-items::-webkit-scrollbar { width: 6px; }
    .autocomplete-items::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 text-slate-900">
  <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6">
    <!-- Header -->
    <div class="text-center">
      <?php if ($logo && file_exists(__DIR__ . '/../' . $logo)): ?>
        <img src="/e-voting/<?php echo htmlspecialchars($logo); ?>" class="mx-auto h-16 w-16 rounded-2xl object-cover shadow-sm">
      <?php endif; ?>
      <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-900">Kartu Pemilih</h1>
      <p class="mt-1 text-sm text-slate-500">Cari nama Anda untuk mengunduh kartu pemilih</p>
    </div>

    <!-- Search + Card -->
    <div x-data="voterSearch()">
      <div class="relative mt-8" x-cloak>
        <div class="relative">
          <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            x-ref="input"
            x-model="query"
            @input="search"
            @click.away="open = false"
            @keydown.escape="open = false"
            @keydown.down.prevent="$nextTick(() => $refs.list.children[0]?.focus())"
            type="text"
            placeholder="Ketik nama pemilih..."
            class="w-full rounded-2xl border border-slate-300 bg-white py-4 pl-12 pr-4 text-base shadow-sm transition-shadow focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/20">
        </div>

        <!-- Autocomplete dropdown -->
        <div x-show="open && filtered.length" class="autocomplete-items absolute z-10 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
          <template x-for="(v, i) in filtered" :key="v.id">
            <button
              x-ref="list"
              @click="select(v)"
              @keydown.down.prevent="$el.nextElementSibling?.focus()"
              @keydown.up.prevent="$el.previousElementSibling?.focus()"
              class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm transition-colors hover:bg-blue-50 focus:bg-blue-50 focus:outline-none"
              :class="{ 'border-t border-slate-100': i > 0 }">
              <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600 shrink-0" x-text="v.name.charAt(0)"></div>
              <div class="min-w-0 flex-1">
                <p class="font-medium text-slate-900 truncate" x-text="v.name"></p>
                <p class="text-xs text-slate-400" x-text="v.voter_number + (v.class_name ? ' \u00B7 ' + v.class_name : '')"></p>
              </div>
              <span class="shrink-0 text-xs font-medium text-slate-400" x-text="v.has_voted === '1' ? '\u2713 Sudah' : 'Belum'"></span>
            </button>
          </template>
        </div>
        <div x-show="open && query.length >= 1 && filtered.length === 0" class="absolute z-10 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-xl">
          <p class="text-sm text-slate-400">Pemilih dengan nama "<span class="font-medium" x-text="query"></span>" tidak ditemukan</p>
        </div>
      </div>

      <!-- Selected Voter Card Preview -->
      <div x-show="selected" x-cloak class="mt-10" x-transition>
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Kartu Pemilih Anda</h2>
        <a :href="'/e-voting/index.php?page=voter-card&download=1&id=' + selected.id" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-500">
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Unduh PDF
        </a>
      </div>

      <!-- Card Preview -->
      <div class="relative mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-white p-8 shadow-lg">
        <!-- Top gradient bar -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-600 via-blue-400 to-blue-300"></div>

        <div class="flex items-center gap-3 pb-5 border-b border-slate-100">
          <?php if ($logo && file_exists(__DIR__ . '/../' . $logo)): ?>
            <img src="/e-voting/<?php echo htmlspecialchars($logo); ?>" class="h-12 w-12 rounded-xl object-cover shrink-0">
          <?php else: ?>
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 shrink-0">
              <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
          <?php endif; ?>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-slate-800">Kartu Pemilih</h3>
            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($schoolName); ?></p>
          </div>
          <span :class="selected.has_voted === '1' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'" class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide shrink-0" x-text="selected.has_voted === '1' ? '\u2713 Sudah Memilih' : 'Belum Memilih'"></span>
        </div>

        <div class="mt-5 grid gap-3">
          <template x-for="(f, i) in fields" :key="i">
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400" x-text="f.label"></p>
              <p class="mt-0.5 text-base font-bold text-slate-900" x-text="f.value" :class="f.token ? 'font-mono tracking-wider text-blue-600 bg-blue-50 px-2 py-0.5 rounded inline-block' : ''"></p>
            </div>
          </template>
        </div>
      </div>

      <p class="mt-4 text-center text-xs text-slate-400">Kartu ini adalah bukti terdaftar sebagai pemilih dalam pemilihan <?php echo htmlspecialchars($schoolName); ?></p>
    </div>

    <!-- Footer -->
    <p class="mt-12 text-center text-xs text-slate-400">
      <a href="/e-voting/index.php" class="text-blue-600 hover:underline">&larr; Kembali ke Beranda</a>
    </p>
    </div>
  </div>

  <script>
    const voters = <?php echo json_encode(array_map(fn($v) => [
      'id' => (int)$v['id'],
      'voter_number' => $v['voter_number'],
      'nik' => $v['nik'],
      'name' => $v['name'],
      'student_name' => $v['student_name'] ?? '',
      'class_name' => $v['class_name'] ?? '',
      'has_voted' => $v['has_voted'],
    ], $voters)); ?>;

    function voterSearch() {
      return {
        query: '',
        open: false,
        selected: null,
        filtered: [],
        fields: [],
        search() {
          if (this.query.length < 1) {
            this.filtered = [];
            this.open = false;
            return;
          }
          const q = this.query.toLowerCase();
          this.filtered = voters.filter(v =>
            v.name.toLowerCase().includes(q) ||
            v.voter_number.toLowerCase().includes(q) ||
            v.nik.includes(q)
          ).slice(0, 20);
          this.open = true;
        },
        select(v) {
          this.selected = v;
          this.query = v.name + ' (' + v.voter_number + ')';
          this.open = false;
          this.fields = [
            { label: 'Nomor Pemilih', value: v.voter_number },
            { label: 'Nama Lengkap', value: v.name },
            { label: 'NIK', value: v.nik },
            ...(v.student_name ? [{ label: 'Nama Siswa', value: v.student_name }] : []),
            ...(v.class_name ? [{ label: 'Kelas', value: v.class_name }] : []),
          ];
        }
      };
    }
  </script>
</body>
</html>
