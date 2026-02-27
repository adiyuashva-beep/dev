<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kurikulum']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

function json_in(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '[]', true);
  return is_array($data) ? $data : [];
}

try {
  edugate_v5_ensure_tables($pdo);
  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');
  $in = json_in();
  $id = (int)($in['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id wajib']);
    exit;
  }

  $stmt = $pdo->prepare('DELETE FROM guru_tugas WHERE school_id=? AND id=?');
  $stmt->execute([$sid, $id]);
  echo json_encode(['ok'=>true,'message'=>'Tugas dihapus']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
