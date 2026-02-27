<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);

  $u = $_SESSION['user'] ?? [];
  $username = (string)($u['username'] ?? '');
  $tanggal = date('Y-m-d');

  $stmt = $pdo->prepare('SELECT jam_masuk, jam_pulang, status_terakhir, foto_masuk, foto_pulang FROM absensi_guru WHERE tanggal=? AND username=? LIMIT 1');
  $stmt->execute([$tanggal, $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['jam_masuk'=>null,'jam_pulang'=>null,'status_terakhir'=>null,'foto_masuk'=>null,'foto_pulang'=>null];

  echo json_encode(['ok'=>true,'data'=>$row]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
