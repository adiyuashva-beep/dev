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
  $role = (string)($u['role'] ?? '');

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id wajib']);
    exit;
  }

  // pastikan tugas milik guru tsb (kecuali admin/super/kurikulum)
  if (!in_array($role, ['admin','super','kurikulum'], true)) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM guru_tugas WHERE school_id=? AND id=? AND guru_username=?');
    $stmt->execute([$sid, $id, $guru_username]);
    if ((int)$stmt->fetchColumn() === 0) {
      http_response_code(403);
      echo json_encode(['ok'=>false,'error'=>'Tidak berhak melihat tugas ini']);
      exit;
    }
  }

  $stmt = $pdo->prepare('SELECT siswa_username AS username, siswa_nama AS nama, kelas FROM guru_tugas_anggota WHERE school_id=? AND tugas_id=? ORDER BY kelas, siswa_nama');
  $stmt->execute([$sid, $id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
