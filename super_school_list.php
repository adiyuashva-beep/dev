<?php
require __DIR__ . '/_bootstrap.php';
require_login(['super']);
ensure_schema();

// super admin bisa lihat semua sekolah
$st = $pdo->query("SELECT id, nama_sekolah, jenjang, subdomain, status, timezone, settings_json, created_at FROM schools ORDER BY id DESC");
$rows = $st->fetchAll();
json_out(['ok'=>true,'data'=>$rows]);
