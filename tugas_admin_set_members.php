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
  $members = $in['members'] ?? [];
  if ($id <= 0 || !is_array($members)) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'id dan members wajib']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM guru_tugas WHERE school_id=? AND id=?');
  $stmt->execute([$sid, $id]);
  if ((int)$stmt->fetchColumn() === 0) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Tugas tidak ditemukan']);
    exit;
  }

  $usernames = [];
  foreach ($members as $m) {
    $u = trim((string)$m);
    if ($u !== '' && !in_array($u, $usernames, true)) $usernames[] = $u;
  }

  $pdo->beginTransaction();
  $pdo->prepare('DELETE FROM guru_tugas_anggota WHERE school_id=? AND tugas_id=?')->execute([$sid, $id]);

  if (count($usernames) > 0) {
    $place = implode(',', array_fill(0, count($usernames), '?'));
    $stmt = $pdo->prepare("SELECT username, name AS nama, kelas FROM users WHERE school_id=? AND role='siswa' AND username IN ($place)");
    $stmt->execute(array_merge([$sid], $usernames));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byU = [];
    foreach ($rows as $r) $byU[$r['username']] = $r;

    $ins = $pdo->prepare('INSERT INTO guru_tugas_anggota (school_id, tugas_id, siswa_username, siswa_nama, kelas) VALUES (?,?,?,?,?)');
    foreach ($usernames as $u) {
      if (!isset($byU[$u])) continue;
      $r = $byU[$u];
      $ins->execute([$sid, $id, $u, $r['nama'], $r['kelas']]);
    }
  }

  $pdo->commit();

  echo json_encode(['ok'=>true,'message'=>'Anggota diperbarui']);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
