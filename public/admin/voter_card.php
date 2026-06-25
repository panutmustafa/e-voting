<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM voters WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$voter = $stmt->fetch();
if (!$voter) {
    exit('Pemilih tidak ditemukan');
}

$settings = get_settings($pdo);
$schoolName = $settings['school_name'] ?? 'SD Negeri Jomblang 2';
$logo = $settings['school_logo'] ?? '';
$logoPath = $logo ? __DIR__ . '/../../' . $logo : '';

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
