<?php
require_once __DIR__ . '/../auth/guard.php';
require_login(['guru','staff','bk','kesiswaan','kurikulum','admin','super','dinas']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/_schema.php';

header('Content-Type: application/json; charset=utf-8');

try {
  edugate_v5_ensure_tables($pdo);
  $sid = school_id();
  if ($sid <= 0) throw new RuntimeException('Tenant tidak valid');
  // ambil kelas dari users role siswa (distinct)
  $stmt = $pdo->prepare("SELECT DISTINCT kelas FROM users WHERE school_id=? AND role='siswa' AND kelas IS NOT NULL AND kelas<>'' ORDER BY kelas ASC");
  $stmt->execute([$sid]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $data = array_values(array_map(fn($r)=>(string)$r['kelas'], $rows));
  echo json_encode(['ok'=>true,'data'=>$data]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}