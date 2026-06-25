<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();
$theme = get_site_theme($pdo);
$currentPage = 'candidates';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (isset($_POST['create_candidate'])) {
        $photo = upload_file($_FILES['photo'] ?? [], __DIR__ . '/../../uploads', ['jpg','jpeg','png','gif','webp'], 5 * 1024 * 1024);
        $video = upload_file($_FILES['video'] ?? [], __DIR__ . '/../../uploads', ['mp4','webm','mov'], 50 * 1024 * 1024);
        $cvFile = upload_file($_FILES['cv_file'] ?? [], __DIR__ . '/../../uploads', ['pdf','doc','docx'], 10 * 1024 * 1024);

        $stmt = $pdo->prepare('INSERT INTO candidates (name, position, photo, video, cv_file, vision, mission, program, motto, education, organization, experience) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            trim($_POST['name'] ?? ''),
            trim($_POST['position'] ?? ''),
            $photo,
            $video,
            $cvFile,
            trim($_POST['vision'] ?? ''),
            trim($_POST['mission'] ?? ''),
            trim($_POST['program'] ?? ''),
            trim($_POST['motto'] ?? ''),
            trim($_POST['education'] ?? ''),
            trim($_POST['organization'] ?? ''),
            trim($_POST['experience'] ?? ''),
        ]);
        log_action($pdo, 'create_candidate', 'Created candidate ' . ($_POST['name'] ?? ''));
        header('Location: /e-voting/public/admin/candidates.php');
        exit;
    } elseif (isset($_POST['update_candidate'])) {
        $id = (int)$_POST['candidate_id'];
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();
        if (!$old) {
            header('Location: /e-voting/public/admin/candidates.php');
            exit;
        }
        $photo = $old['photo'];
        $video = $old['video'];
        $cvFile = $old['cv_file'];
        if (!empty($_FILES['photo']['name'])) {
            $photo = upload_file($_FILES['photo'], __DIR__ . '/../../uploads', ['jpg','jpeg','png','gif','webp'], 5 * 1024 * 1024);
            if ($old['photo'] && file_exists(__DIR__ . '/../../' . $old['photo'])) {
                unlink(__DIR__ . '/../../' . $old['photo']);
            }
        }
        if (!empty($_FILES['video']['name'])) {
            $video = upload_file($_FILES['video'], __DIR__ . '/../../uploads', ['mp4','webm','mov'], 50 * 1024 * 1024);
            if ($old['video'] && file_exists(__DIR__ . '/../../' . $old['video'])) {
                unlink(__DIR__ . '/../../' . $old['video']);
            }
        }
        if (!empty($_FILES['cv_file']['name'])) {
            $cvFile = upload_file($_FILES['cv_file'], __DIR__ . '/../../uploads', ['pdf','doc','docx'], 10 * 1024 * 1024);
            if ($old['cv_file'] && file_exists(__DIR__ . '/../../' . $old['cv_file'])) {
                unlink(__DIR__ . '/../../' . $old['cv_file']);
            }
        }
        $stmt = $pdo->prepare('UPDATE candidates SET name=?, position=?, photo=?, video=?, cv_file=?, vision=?, mission=?, program=?, motto=?, education=?, organization=?, experience=? WHERE id=?');
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['position']),
            $photo, $video, $cvFile,
            trim($_POST['vision'] ?? ''),
            trim($_POST['mission'] ?? ''),
            trim($_POST['program'] ?? ''),
            trim($_POST['motto'] ?? ''),
            trim($_POST['education'] ?? ''),
            trim($_POST['organization'] ?? ''),
            trim($_POST['experience'] ?? ''),
            $id
        ]);
        log_action($pdo, 'update_candidate', 'Updated candidate ID ' . $id);
        header('Location: /e-voting/public/admin/candidates.php');
        exit;
    } elseif (isset($_POST['delete_candidate'])) {
        $id = (int)$_POST['candidate_id'];
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ?");
        $stmt->execute([$id]);
        $candidate = $stmt->fetch();
        if ($candidate) {
            foreach (['photo', 'video', 'cv_file'] as $field) {
                if ($candidate[$field] && file_exists(__DIR__ . '/../../' . $candidate[$field])) {
                    unlink(__DIR__ . '/../../' . $candidate[$field]);
                }
            }
        }
        $stmt = $pdo->prepare('DELETE FROM candidates WHERE id = ?');
        $stmt->execute([$id]);
        log_action($pdo, 'delete_candidate', 'Deleted candidate ID ' . $id);
        header('Location: /e-voting/public/admin/candidates.php');
        exit;
    }
    header('Location: /e-voting/public/admin/candidates.php');
    exit;
}

$candidates = $pdo->query('SELECT * FROM candidates ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kandidat</title>
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
        <a href="/e-voting/public/admin/index.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'dashboard' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zM14 16a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2v-4z" />
          </svg>
          Dashboard
        </a>
        
        <a href="/e-voting/public/admin/voters.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'voters' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Data Pemilih
        </a>
        
        <a href="/e-voting/public/admin/candidates.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'candidates' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Kandidat
        </a>
        
        <a href="/e-voting/public/admin/monitoring.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'monitoring' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          Monitoring
        </a>
        
        <a href="/e-voting/public/admin/results.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'results' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Hasil Voting
        </a>
        
        <a href="/e-voting/public/admin/settings.php" class="flex items-center gap-3 rounded-2xl px-4 py-3 font-medium transition-all duration-200 <?php echo $currentPage === 'settings' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/10' : ($theme === 'dark' ? 'text-slate-300 hover:bg-slate-800' : 'text-slate-700 hover:bg-slate-100'); ?>">
          <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Pengaturan
        </a>
      </nav>
    </div>

    <div class="mt-auto space-y-1.5 border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-200'; ?> pt-4">
      <a href="/e-voting/index.php?page=home" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 <?php echo $theme === 'dark' ? 'text-slate-400 hover:text-slate-200 hover:bg-slate-800' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100'; ?>">
        <svg class="h-5 w-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Halaman Beranda
      </a>

      <!-- Theme Toggle -->
      <form method="post" action="/e-voting/public/admin/toggle_theme.php" class="block">
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

      <a href="/e-voting/index.php?page=logout" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-200 text-red-500 hover:bg-red-500/10">
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
          <p class="text-sm uppercase tracking-[0.3em] text-amber-400">Kelola Kandidat</p>
          <h1 class="text-2xl font-semibold">Data Kandidat</h1>
        </div>
      </div>
      <form method="post" action="/e-voting/public/admin/candidates.php" enctype="multipart/form-data" class="mt-8 grid gap-4 md:grid-cols-2">
        <?php csrf_field(); ?>
        <input type="hidden" name="create_candidate" value="1">
        <input name="name" placeholder="Nama Lengkap" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3" required>
        <input name="position" placeholder="Jabatan" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3" required>
        <input type="file" name="photo" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input type="file" name="video" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input type="file" name="cv_file" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <textarea name="vision" placeholder="Visi" class="md:col-span-2 rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3"></textarea>
        <textarea name="mission" placeholder="Misi" class="md:col-span-2 rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3"></textarea>
        <textarea name="program" placeholder="Program Kerja" class="md:col-span-2 rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3"></textarea>
        <input name="motto" placeholder="Motto" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="education" placeholder="Riwayat Pendidikan" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="organization" placeholder="Riwayat Organisasi" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <input name="experience" placeholder="Pengalaman" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3">
        <div class="md:col-span-2"><button class="rounded-2xl bg-blue-600 px-5 py-3 font-semibold">Tambah Kandidat</button></div>
      </form>
      <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <?php foreach ($candidates as $candidate): ?>
          <div class="overflow-hidden rounded-3xl border <?php echo $theme === 'dark' ? 'border-slate-800 bg-slate-950' : 'border-slate-200 bg-slate-50'; ?>">
            <img src="/e-voting/<?php echo htmlspecialchars($candidate['photo'] ?: 'uploads/default.png'); ?>" class="h-48 w-full object-cover" onerror="this.src='/e-voting/uploads/default.png'; this.style.objectFit='contain'; this.style.padding='2rem'; this.style.background='#1e293b'">
            <div class="p-5">
              <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($candidate['name']); ?></h3>
              <p class="mt-2 text-sm <?php echo $theme === 'dark' ? 'text-slate-400' : 'text-slate-600'; ?>"><?php echo htmlspecialchars($candidate['position']); ?></p>
              <div class="mt-4 flex gap-2">
                <button onclick='openEditModal(<?php echo json_encode($candidate, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                        class="rounded-lg bg-yellow-500/10 border border-yellow-500/25 hover:bg-yellow-500/25 px-3 py-1.5 text-xs font-semibold text-yellow-500 transition-colors">
                  Edit
                </button>
                <form method="post" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kandidat ini?')">
                  <?php csrf_field(); ?>
                  <input type="hidden" name="delete_candidate" value="1">
                  <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                  <button type="submit" 
                          class="rounded-lg bg-red-500/10 border border-red-500/25 hover:bg-red-500/25 px-3 py-1.5 text-xs font-semibold text-red-500 transition-colors">
                    Hapus
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>
<!-- Edit Candidate Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="w-full max-w-2xl rounded-3xl border <?php echo $theme === 'dark' ? 'border-slate-800 bg-slate-900' : 'border-slate-200 bg-white'; ?> p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between border-b <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-100'; ?> pb-4">
      <h2 class="text-xl font-bold">Edit Data Kandidat</h2>
      <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-200">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    
    <form method="post" enctype="multipart/form-data" action="candidates.php" class="mt-4 grid gap-4 md:grid-cols-2">
      <?php csrf_field(); ?>
      <input type="hidden" name="update_candidate" value="1">
      <input type="hidden" id="edit_candidate_id" name="candidate_id">
      
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
        <input id="edit_name" name="name" required class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Jabatan</label>
        <input id="edit_position" name="position" required class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Foto</label>
        <input type="file" name="photo" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
        <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak ingin mengubah</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Video</label>
        <input type="file" name="video" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
        <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak ingin mengubah</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">CV/File</label>
        <input type="file" name="cv_file" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
        <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika tidak ingin mengubah</p>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Visi</label>
        <textarea id="edit_vision" name="vision" rows="3" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm"></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Misi</label>
        <textarea id="edit_mission" name="mission" rows="3" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm"></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Program Kerja</label>
        <textarea id="edit_program" name="program" rows="3" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm"></textarea>
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Motto</label>
        <input id="edit_motto" name="motto" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Riwayat Pendidikan</label>
        <input id="edit_education" name="education" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Riwayat Organisasi</label>
        <input id="edit_organization" name="organization" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      <div>
        <label class="mb-1 block text-xs font-semibold text-slate-400 uppercase tracking-wider">Pengalaman</label>
        <input id="edit_experience" name="experience" class="w-full rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 bg-slate-950' : 'border-slate-300 bg-slate-50'; ?> px-4 py-3 text-sm">
      </div>
      
      <div class="md:col-span-2 mt-4 flex justify-end gap-3 border-t <?php echo $theme === 'dark' ? 'border-slate-800' : 'border-slate-100'; ?> pt-4">
        <button type="button" onclick="closeEditModal()" class="rounded-2xl border <?php echo $theme === 'dark' ? 'border-slate-700 hover:bg-slate-800' : 'border-slate-300 hover:bg-slate-100'; ?> px-5 py-3 font-semibold text-sm transition-colors">Batal</button>
        <button type="submit" class="rounded-2xl bg-blue-600 hover:bg-blue-500 px-5 py-3 font-semibold text-sm text-white transition-colors">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openEditModal(candidate) {
    document.getElementById('edit_candidate_id').value = candidate.id;
    document.getElementById('edit_name').value = candidate.name;
    document.getElementById('edit_position').value = candidate.position;
    document.getElementById('edit_vision').value = candidate.vision || '';
    document.getElementById('edit_mission').value = candidate.mission || '';
    document.getElementById('edit_program').value = candidate.program || '';
    document.getElementById('edit_motto').value = candidate.motto || '';
    document.getElementById('edit_education').value = candidate.education || '';
    document.getElementById('edit_organization').value = candidate.organization || '';
    document.getElementById('edit_experience').value = candidate.experience || '';
    document.getElementById('editModal').classList.remove('hidden');
  }

  function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
  }
</script>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('-translate-x-full');document.getElementById('sidebarOverlay').classList.toggle('hidden');}</script>
</body>
</html>
