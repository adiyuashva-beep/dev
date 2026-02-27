<?php
require __DIR__ . '/_bootstrap.php';

require_login(['admin','super','kurikulum','kesiswaan','bk','staff']);
ensure_schema();

$sid = require_tenant();

$in = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim($in['username'] ?? '');
$kelas = trim($in['kelas'] ?? ''); // boleh kosong untuk menghapus wali

if (!$username) json_out(['ok'=>false,'error'=>'username guru wajib']);

// Pastikan target user ada dan bukan siswa
$stmt = $pdo->prepare("SELECT username, role FROM users WHERE school_id=:sid AND username=:u LIMIT 1");
$stmt->execute([':sid'=>$sid, ':u'=>$username]);
$u = $stmt->fetch();
if (!$u) json_out(['ok'=>false,'error'=>'User tidak ditemukan']);
if (($u['role'] ?? '') === 'siswa') json_out(['ok'=>false,'error'=>'Tidak bisa set wali untuk siswa']);

// Simpan ke kolom kelas pada tabel users.
$up = $pdo->prepare("UPDATE users SET kelas=:k WHERE school_id=:sid AND username=:u");
$up->execute([':k'=>$kelas ?: null, ':sid'=>$sid, ':u'=>$username]);

json_out(['ok'=>true,'message'=>'Wali kelas tersimpan']);
