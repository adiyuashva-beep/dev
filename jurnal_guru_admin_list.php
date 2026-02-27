<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

  $tgl_from = trim((string)($_GET['from'] ?? ''));
  $tgl_to   = trim((string)($_GET['to'] ?? ''));
  $kelas    = trim((string)($_GET['kelas'] ?? ''));
  $guru     = trim((string)($_GET['guru'] ?? ''));

  // default: 7 hari terakhir
  if ($tgl_from === '') $tgl_from = date('Y-m-d', strtotime('-7 days'));
  if ($tgl_to === '') $tgl_to = date('Y-m-d');

  $where = ['school_id = :sid', 'tanggal BETWEEN :f AND :t'];
  $params = [':sid'=>$sid, ':f'=>$tgl_from, ':t'=>$tgl_to];

  if ($kelas !== '') {
    $where[] = 'kelas = :k';
    $params[':k'] = $kelas;
  }
  if ($guru !== '') {
    $where[] = 'guru_username = :g';
    $params[':g'] = $guru;
  }

  $sql = "SELECT id, tanggal, jam_ke_mulai, jam_ke_selesai, guru_username, guru_nama, kelas, mapel, topik, catatan, foto_json
          FROM jurnal_guru
          WHERE " . implode(' AND ', $where) . "
          ORDER BY tanggal DESC, COALESCE(jam_ke_mulai, 0) DESC, id DESC";

  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
