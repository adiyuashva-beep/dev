<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','bk','kesiswaan','kurikulum','guru','staff']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');
  $kelas = trim((string)($_GET['kelas'] ?? ''));
  if ($kelas === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'kelas wajib']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT username, name AS nama, kelas FROM users WHERE school_id=? AND role='siswa' AND kelas=? ORDER BY name");
  $stmt->execute([$sid, $kelas]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
