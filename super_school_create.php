<?php
require __DIR__ . '/_bootstrap.php';
require_login(['super']);
ensure_schema();

$in = json_in();
$nama = trim((string)($in['nama_sekolah'] ?? ''));
$jenjang = strtoupper(trim((string)($in['jenjang'] ?? '')));
$subdomain = strtolower(trim((string)($in['subdomain'] ?? '')));

$admin_username = trim((string)($in['admin_username'] ?? 'admin'));
$admin_name = trim((string)($in['admin_name'] ?? 'Admin'));
$admin_password = (string)($in['admin_password'] ?? '');

$settings = $in['settings'] ?? null;

if ($nama === '' || $subdomain === '' || !in_array($jenjang, ['SD','SMP','SMA'], true)) {
  json_out(['ok'=>false,'error'=>'nama_sekolah, jenjang(SD/SMP/SMA), subdomain wajib'], 400);
}
if (!preg_match('/^[a-z0-9-]{3,40}$/', $subdomain)) {
  json_out(['ok'=>false,'error'=>'subdomain harus 3-40 char: huruf kecil/angka/-'], 400);
}
if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $admin_username)) {
  json_out(['ok'=>false,'error'=>'admin_username invalid'], 400);
}

if ($admin_password === '') $admin_password = $admin_username;

$settings_json = null;
if (is_array($settings)) {
  $settings_json = json_encode($settings, JSON_UNESCAPED_SLASHES);
}

try {
  $pdo->beginTransaction();

  $st = $pdo->prepare("INSERT INTO schools (nama_sekolah, jenjang, subdomain, status, settings_json) VALUES (?,?,?, 'active', ?)");
  $st->execute([$nama, $jenjang, $subdomain, $settings_json]);
  $school_id = (int)$pdo->lastInsertId();

  // buat admin pertama
  $hash = password_hash($admin_password, PASSWORD_BCRYPT);
  $ins = $pdo->prepare("INSERT INTO users (school_id, name, username, password_hash, role, kelas) VALUES (?,?,?,?, 'admin', NULL)");
  $ins->execute([$school_id, $admin_name, $admin_username, $hash]);

  $pdo->commit();

  json_out(['ok'=>true,'message'=>'Sekolah dibuat','data'=>['school_id'=>$school_id]]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // duplicate
  if ($e instanceof PDOException && (int)($e->errorInfo[1] ?? 0) === 1062) {
    json_out(['ok'=>false,'error'=>'Subdomain atau admin_username sudah ada'], 409);
  }
  json_out(['ok'=>false,'error'=>$e->getMessage()], 500);
}
