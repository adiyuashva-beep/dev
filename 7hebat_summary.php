<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kesiswaan','kurikulum','bk']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');

  $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
  $tanggal = preg_replace('/[^0-9\-]/', '', (string)$tanggal);
  if (strlen($tanggal) !== 10) $tanggal = date('Y-m-d');

  $stmt = $pdo->prepare("SELECT u.kelas,
      COUNT(*) AS total,
      SUM(CASE WHEN k.id IS NOT NULL THEN 1 ELSE 0 END) AS filled,
      SUM(CASE WHEN k.status='valid' THEN 1 ELSE 0 END) AS valid,
      SUM(CASE WHEN k.status='reject' THEN 1 ELSE 0 END) AS reject,
      SUM(CASE WHEN k.id IS NULL OR k.status='draft' THEN 1 ELSE 0 END) AS draft
    FROM users u
    LEFT JOIN kebiasaan7 k ON k.school_id=:sid AND k.tanggal=:t AND k.siswa_username=u.username
    WHERE u.school_id=:sid AND u.role='siswa' AND u.kelas IS NOT NULL AND u.kelas<>''
    GROUP BY u.kelas
    ORDER BY u.kelas");
  $stmt->execute([':sid'=>$sid, ':t'=>$tanggal]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
