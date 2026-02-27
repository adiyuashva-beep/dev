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
  $guru_username = trim((string)($in['guru_username'] ?? ''));
  $jenis = trim((string)($in['jenis'] ?? ''));
  $nama_tugas = trim((string)($in['nama_tugas'] ?? ''));

  if ($guru_username==='' || $jenis==='' || $nama_tugas==='') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'guru_username, jenis, nama_tugas wajib']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT name FROM users WHERE school_id=? AND username=? AND role IN (\'guru\',\'staff\') LIMIT 1');
  $stmt->execute([$sid, $guru_username]);
  $guru_nama = (string)($stmt->fetchColumn() ?? '');
  if ($guru_nama === '') {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Guru tidak ditemukan']);
    exit;
  }

  $stmt = $pdo->prepare('INSERT INTO guru_tugas (school_id, guru_username, guru_nama, jenis, nama_tugas) VALUES (?,?,?,?,?)');
  $stmt->execute([$sid, $guru_username, $guru_nama, $jenis, $nama_tugas]);
  $id = (int)$pdo->lastInsertId();

  echo json_encode(['ok'=>true,'message'=>'Tugas dibuat','data'=>['id'=>$id]]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
