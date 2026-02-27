<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id(); // [FIX]

  $u = $_SESSION['user'] ?? [];
  $role = (string)($u['role'] ?? '');
  $wali = (string)($u['kelas'] ?? '');

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id wajib']);
    exit;
  }

  // SELECT dengan school_id
  $stmt = $pdo->prepare('SELECT id, tanggal, siswa_username AS username, siswa_nama AS nama, kelas, data_json, catatan, status, validator_note FROM kebiasaan7 WHERE school_id=? AND id=?');
  $stmt->execute([$sid, $id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Data tidak ditemukan']);
    exit;
  }

  if (in_array($role, ['guru','staff'], true) && $wali !== (string)($row['kelas'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Tidak berhak']);
    exit;
  }

  $row['data_json'] = json_decode($row['data_json'] ?? '{}', true) ?: new stdClass();

  echo json_encode(['ok'=>true,'data'=>$row]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}