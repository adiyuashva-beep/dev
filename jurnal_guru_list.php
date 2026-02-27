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

  $stmt = $pdo->prepare('
    SELECT
      id, tanggal, jam, jam_ke_mulai, jam_ke_selesai,
      kelas, mapel, topik, catatan, foto_json
    FROM jurnal_guru
    WHERE school_id=? AND guru_username=?
      AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY tanggal DESC, COALESCE(jam_ke_mulai, 0) DESC, id DESC
  ');
  $stmt->execute([$sid, $guru_username]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}