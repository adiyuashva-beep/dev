<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super']);
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

  $sid = school_id(); // [FIX]

  $u = $_SESSION['user'] ?? [];
  $role = (string)($u['role'] ?? '');
  $validator_username = (string)($u['username'] ?? '');
  $validator_name = (string)($u['name'] ?? $validator_username);
  $wali = (string)($u['kelas'] ?? '');

  $in = json_in();
  $id = (int)($in['id'] ?? 0);
  $status = strtolower((string)($in['status'] ?? ''));
  $note = trim((string)($in['note'] ?? ''));

  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id wajib']);
    exit;
  }
  if (!in_array($status, ['valid','reject'], true)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'status harus valid/reject']);
    exit;
  }

  // SELECT dengan school_id
  $stmt = $pdo->prepare('SELECT id, kelas FROM kebiasaan7 WHERE school_id=? AND id=?');
  $stmt->execute([$sid, $id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Data tidak ditemukan']);
    exit;
  }

  if (in_array($role, ['guru','staff'], true) && $wali !== (string)($row['kelas'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Kamu hanya boleh validasi kelas wali kamu sendiri.']);
    exit;
  }

  // UPDATE dengan school_id
  $stmt = $pdo->prepare('UPDATE kebiasaan7 SET status=?, validator_username=?, validator_name=?, validator_note=?, validated_at=NOW() WHERE school_id=? AND id=?');
  $stmt->execute([$status, $validator_username, $validator_name, $note !== '' ? $note : null, $sid, $id]);

  echo json_encode(['ok'=>true,'message'=>'Validasi tersimpan']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}