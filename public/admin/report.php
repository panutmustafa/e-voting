<?php
require_once __DIR__ . '/../../src/functions.php';
init_session();
require_once __DIR__ . '/../../config/database.php';
require_admin();

$action = $_GET['action'] ?? 'pdf';

$stats = [
    'total_voters' => $pdo->query("SELECT COUNT(*) AS total FROM voters")->fetch()['total'],
    'has_voted' => $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='1'")->fetch()['total'],
    'not_voted' => $pdo->query("SELECT COUNT(*) AS total FROM voters WHERE has_voted='0'")->fetch()['total'],
];
$candidates = $pdo->query("SELECT c.name, COUNT(v.id) AS total_votes FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id GROUP BY c.id ORDER BY total_votes DESC")->fetchAll();

if ($action === 'xlsx') {
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Laporan E-Voting');
    $sheet->setCellValue('A3', 'Total Pemilih');
    $sheet->setCellValue('B3', $stats['total_voters']);
    $sheet->setCellValue('A4', 'Sudah Memilih');
    $sheet->setCellValue('B4', $stats['has_voted']);
    $sheet->setCellValue('A5', 'Belum Memilih');
    $sheet->setCellValue('B5', $stats['not_voted']);
    $sheet->setCellValue('A7', 'Kandidat');
    $sheet->setCellValue('B7', 'Jumlah Suara');
    $row = 8;
    foreach ($candidates as $candidate) {
        $sheet->setCellValue('A' . $row, $candidate['name']);
        $sheet->setCellValue('B' . $row, $candidate['total_votes']);
        $row++;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'evoting');
    $xlsx = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $xlsx->save($tmp);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="laporan-evoting.xlsx"');
    readfile($tmp);
    unlink($tmp);
    exit;
}

$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Laporan E-Voting</title><style>body{font-family:Arial,sans-serif;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;}th{background:#f2f2f2;}</style></head><body><h1>Laporan E-Voting</h1><p>Total Pemilih: ' . $stats['total_voters'] . '</p><p>Sudah Memilih: ' . $stats['has_voted'] . '</p><p>Belum Memilih: ' . $stats['not_voted'] . '</p><table><tr><th>Kandidat</th><th>Jumlah Suara</th></tr>';
foreach ($candidates as $candidate) {
    $html .= '<tr><td>' . htmlspecialchars($candidate['name']) . '</td><td>' . (int)$candidate['total_votes'] . '</td></tr>';
}
$html .= '</table></body></html>';

$dompdf = new Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="laporan-evoting.pdf"');
echo $dompdf->output();
