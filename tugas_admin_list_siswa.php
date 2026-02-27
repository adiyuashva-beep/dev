<?php
require __DIR__ . '/_bootstrap.php';

require_login(['admin','super','kurikulum','kesiswaan','bk','staff']);
ensure_schema();

$sid = require_tenant();

$kelas = trim($_GET['kelas'] ?? '');

if ($kelas !== '') {
  $stmt = $pdo->prepare("SELECT username, nama, kelas FROM users WHERE school_id=:sid AND role='siswa' AND kelas=:k ORDER BY nama ASC, username ASC");
  $stmt->execute([':sid'=>$sid, ':k'=>$kelas]);
} else {
  // Default: ambil 300 pertama saja untuk keamanan.
  $stmt = $pdo->prepare("SELECT username, nama, kelas FROM users WHERE school_id=:sid AND role='siswa' ORDER BY kelas ASC, nama ASC LIMIT 300");
  $stmt->execute([':sid'=>$sid]);
}

$rows = $stmt->fetchAll();
json_out(['ok'=>true,'data'=>$rows]);
