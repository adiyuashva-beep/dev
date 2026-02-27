<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['siswa']);
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

  $sid = school_id(); // [FIX] ambil school_id

  $u = $_SESSION['user'] ?? [];
  $username = (string)($u['username'] ?? '');
  $nama = (string)($u['name'] ?? $username);
  $kelas = (string)($u['kelas'] ?? null);

  $in = json_in();
  $tanggal = preg_replace('/[^0-9\-]/', '', (string)($in['tanggal'] ?? date('Y-m-d')));
  if (strlen($tanggal) !== 10) $tanggal = date('Y-m-d');

  $data_json = $in['data_json'] ?? [];
  if (!is_array($data_json)) $data_json = [];

  $catatan = trim((string)($in['catatan'] ?? ''));
  $jsonStr = json_encode($data_json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

  // cek apakah sudah valid (dengan school_id)
  $stmt = $pdo->prepare('SELECT id, status FROM kebiasaan7 WHERE school_id = ? AND tanggal=? AND siswa_username=? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $ex = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($ex && ($ex['status'] ?? '') === 'valid') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'Laporan sudah VALID. Jika perlu revisi, minta wali kelas untuk reject dulu.']);
    exit;
  }

  if (!$ex) {
    // INSERT dengan school_id
    $stmt = $pdo->prepare('INSERT INTO kebiasaan7 (school_id, tanggal, siswa_username, siswa_nama, kelas, data_json, catatan, status) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$sid, $tanggal, $username, $nama, $kelas, $jsonStr, $catatan !== '' ? $catatan : null, 'draft']);
  } else {
    // UPDATE dengan school_id di WHERE
    $stmt = $pdo->prepare("UPDATE kebiasaan7
      SET siswa_nama=?, kelas=?, data_json=?, catatan=?, status='draft', validator_username=NULL, validator_name=NULL, validator_note=NULL, validated_at=NULL
      WHERE school_id=? AND tanggal=? AND siswa_username=?");
    $stmt->execute([$nama, $kelas, $jsonStr, $catatan !== '' ? $catatan : null, $sid, $tanggal, $username]);
  }

  // SELECT kembali dengan school_id
  $stmt = $pdo->prepare('SELECT id, tanggal, data_json, catatan, status, validator_note FROM kebiasaan7 WHERE school_id=? AND tanggal=? AND siswa_username=? LIMIT 1');
  $stmt->execute([$sid, $tanggal, $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  $row['data_json'] = json_decode($row['data_json'] ?? '{}', true) ?: new stdClass();

  echo json_encode(['ok'=>true,'message'=>'Tersimpan','data'=>$row]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}