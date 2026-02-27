<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['siswa']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json');

try {
  edugate_v5_ensure_tables($pdo);

  $sid = school_id(); // [FIX]

  $u = $_SESSION['user'] ?? [];
  $username = (string)($u['username'] ?? '');

  $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
  $tanggal = preg_replace('/[^0-9\-]/', '', (string)$tanggal);
  if (strlen($tanggal) !== 10) $tanggal = date('Y-m-d');

  // SELECT dengan school_id
  $stmt = $pdo->prepare('SELECT id, tanggal, data_json, catatan, status, validator_note FROM kebiasaan7 WHERE school_id=? AND tanggal=? AND siswa_username=? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    $out = [
      'id'=>null,
      'tanggal'=>$tanggal,
      'data_json'=>new stdClass(),
      'catatan'=>'',
      'status'=>'draft',
      'validator_note'=>''
    ];
    echo json_encode(['ok'=>true,'data'=>$out]);
    exit;
  }

  $row['data_json'] = json_decode($row['data_json'] ?? '{}', true) ?: new stdClass();

  echo json_encode(['ok'=>true,'data'=>$row]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}