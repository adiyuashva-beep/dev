<?php
require __DIR__ . '/_bootstrap.php';

require_login(['admin','super','kesiswaan','kurikulum','bk','staff']);
ensure_schema();

$sid = require_tenant();

$tgl_from = trim((string)($_GET['from'] ?? ''));
$tgl_to   = trim((string)($_GET['to'] ?? ''));
$status   = trim((string)($_GET['status'] ?? 'submitted'));
$q        = trim((string)($_GET['q'] ?? ''));

if ($tgl_from === '') $tgl_from = date('Y-m-d', strtotime('-7 days'));
if ($tgl_to === '') $tgl_to = date('Y-m-d');

$allowedStatus = ['submitted','approved','rejected'];
if (!in_array($status, $allowedStatus, true)) $status = 'submitted';

$where = ['school_id=:sid', 'tanggal BETWEEN :f AND :t', 'status=:st'];
$params = [':sid'=>$sid, ':f'=>$tgl_from, ':t'=>$tgl_to, ':st'=>$status];

if ($q !== '') {
  $where[] = '(LOWER(username) LIKE :q OR LOWER(nama) LIKE :q OR LOWER(jenis) LIKE :q)';
  $params[':q'] = '%' . strtolower($q) . '%';
}

$sql = "SELECT id, tanggal, username, nama, jenis, jumlah_hari, keterangan, bukti_url, status,
               validator_username, validator_name, validator_note, validated_at
        FROM absensi_guru_ket
        WHERE " . implode(' AND ', $where) . "
        ORDER BY tanggal DESC, id DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

json_out(['ok'=>true,'data'=>$rows]);
