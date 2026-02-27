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
  $role = (string)($u['role'] ?? '');
  $wali = (string)($u['kelas'] ?? '');

  $kelas = trim((string)($_GET['kelas'] ?? ''));
  $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
  $tanggal = preg_replace('/[^0-9\-]/', '', (string)$tanggal);
  if (strlen($tanggal) !== 10) $tanggal = date('Y-m-d');

  if ($kelas === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'kelas wajib']);
    exit;
  }

  // guru/staff hanya boleh kelas wali
  if (in_array($role, ['guru','staff'], true) && $wali !== $kelas) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Kamu hanya boleh melihat kelas wali kamu sendiri.']);
    exit;
  }

  $stmt = $pdo->prepare("SELECT u.username, u.name AS nama, u.kelas,
      k.id, k.status
    FROM users u
    LEFT JOIN kebiasaan7 k ON k.school_id=:sid AND k.tanggal=:t AND k.siswa_username=u.username
    WHERE u.school_id=:sid AND u.role='siswa' AND u.kelas=:k
    ORDER BY u.name");
  $stmt->execute([':sid'=>$sid, ':t'=>$tanggal, ':k'=>$kelas]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$r) {
    $r['id'] = $r['id'] ? (int)$r['id'] : null;
    $r['status'] = $r['status'] ?: 'draft';
  }

  echo json_encode(['ok'=>true,'data'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
