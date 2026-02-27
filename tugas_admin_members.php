<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['admin','super','kurikulum']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');
  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id wajib']);
    exit;
  }
  $chk = $pdo->prepare('SELECT id FROM guru_tugas WHERE school_id=? AND id=? LIMIT 1');
  $chk->execute([$sid, $id]);
  if (!$chk->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Tugas tidak ditemukan']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT siswa_username AS username, siswa_nama AS nama, kelas FROM guru_tugas_anggota WHERE school_id=? AND tugas_id=? ORDER BY kelas, siswa_nama');
  $stmt->execute([$sid, $id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
