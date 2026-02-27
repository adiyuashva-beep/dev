<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kurikulum','bk','kesiswaan','staff']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

edugate_v5_ensure_tables($pdo);
$sid = school_id();
if ($sid <= 0) { http_response_code(400); echo 'Tenant tidak valid'; exit; }

$from = trim((string)($_GET['from'] ?? ''));
$to   = trim((string)($_GET['to'] ?? ''));
$kelas = trim((string)($_GET['kelas'] ?? ''));
$guru  = trim((string)($_GET['guru'] ?? ''));

if ($from === '') $from = date('Y-m-d', strtotime('-30 days'));
if ($to === '') $to = date('Y-m-d');

$where = ['school_id=:sid', 'tanggal BETWEEN :f AND :t'];
$params = [':sid'=>$sid, ':f'=>$from, ':t'=>$to];
if ($kelas !== '') { $where[]='kelas=:k'; $params[':k']=$kelas; }
if ($guru !== '') { $where[]='guru_username=:g'; $params[':g']=$guru; }

$sql = "SELECT tanggal, guru_username, guru_nama, kelas, mapel, topik, jam_ke_mulai, jam_ke_selesai, catatan
        FROM jurnal_guru
        WHERE " . implode(' AND ', $where) . "
        ORDER BY tanggal DESC, kelas ASC, guru_nama ASC";
$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

$fname = 'rekap_jurnal_guru_' . $from . '_to_' . $to . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $fname);

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['tanggal','guru_username','guru_nama','kelas','mapel','topik','jp_mulai','jp_selesai','catatan']);
foreach ($rows as $r) {
  fputcsv($out, [$r['tanggal'],$r['guru_username'],$r['guru_nama'],$r['kelas'],$r['mapel'],$r['topik'],$r['jam_ke_mulai'],$r['jam_ke_selesai'],$r['catatan']]);
}
fclose($out);
