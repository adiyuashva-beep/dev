<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

edugate_v5_ensure_tables($pdo);
$sid = school_id();
if ($sid <= 0) { http_response_code(400); echo 'Tenant tidak valid'; exit; }

$from = trim((string)($_GET['from'] ?? ''));
$to   = trim((string)($_GET['to'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));

if ($from === '') $from = date('Y-m-d', strtotime('-30 days'));
if ($to === '') $to = date('Y-m-d');

$where = ['school_id=:sid', 'tanggal BETWEEN :f AND :t'];
$params = [':sid'=>$sid, ':f'=>$from, ':t'=>$to];
if ($kelas !== '') { $where[] = 'kelas=:k'; $params[':k']=$kelas; }

$sql = "SELECT tanggal, username, nama, kelas, status_terakhir, jam_masuk, jam_pulang
        FROM absensi
        WHERE " . implode(' AND ', $where) . "
        ORDER BY tanggal DESC, kelas ASC, nama ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$fname = 'rekap_absensi_siswa_' . $from . '_to_' . $to . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $fname);

$out = fopen('php://output', 'w');
// BOM for Excel
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['tanggal','username','nama','kelas','status','jam_masuk','jam_pulang']);
foreach ($rows as $r) {
  fputcsv($out, [$r['tanggal'],$r['username'],$r['nama'],$r['kelas'],$r['status_terakhir'],$r['jam_masuk'],$r['jam_pulang']]);
}
fclose($out);
