<?php
require __DIR__ . '/_bootstrap.php';

require_login(['admin','super','kurikulum','kesiswaan','bk','staff']);
ensure_schema();

$sid = require_tenant();

// Ambil semua user non-siswa (guru/staff/admin) supaya fleksibel.
$stmt = $pdo->prepare("SELECT username, nama, role, kelas FROM users WHERE school_id=? AND role <> 'siswa' ORDER BY nama ASC, username ASC");
$stmt->execute([$sid]);
$rows = $stmt->fetchAll();

json_out(['ok'=>true,'data'=>$rows]);
