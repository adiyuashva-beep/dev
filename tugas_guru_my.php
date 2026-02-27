<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');
  $u = $_SESSION['user'] ?? [];
  $guru_username = (string)($u['username'] ?? '');

  $stmt = $pdo->prepare("SELECT t.id, t.jenis, t.nama_tugas,
      (SELECT COUNT(*) FROM guru_tugas_anggota a WHERE a.school_id=:sid AND a.tugas_id=t.id) AS jumlah_anggota
    FROM guru_tugas t
    WHERE t.school_id=:sid AND t.guru_username=:g
    ORDER BY t.id DESC");
  $stmt->execute([':sid'=>$sid, ':g'=>$guru_username]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
