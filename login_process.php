<?php
// public_html/auth/login_process.php
declare(strict_types=1);

session_start();

// Koneksi DB (standar proyek ini: public_html/config/database.php)
require __DIR__ . "/../config/database.php";
// Resolve tenant dari subdomain
require __DIR__ . "/../config/tenant.php";

function back_with_error(string $msg): void {
  $_SESSION['flash_error'] = $msg;
  header("Location: /");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: /");
  exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  back_with_error("Username dan password wajib diisi.");
}

try {
  $sid = (int)($_SESSION['school_id'] ?? 0);
  if ($sid <= 0) back_with_error('Tenant tidak valid.');

  $stmt = $pdo->prepare("SELECT * FROM users WHERE school_id = :sid AND username = :u LIMIT 1");
  $stmt->execute([":sid" => $sid, ":u" => $username]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) back_with_error("User tidak ditemukan.");

  $name =
    (!empty($row['name']) ? (string)$row['name'] :
    (!empty($row['nama_lengkap']) ? (string)$row['nama_lengkap'] :
    (!empty($row['nama']) ? (string)$row['nama'] : $username)));

  $kelas = isset($row['kelas']) ? (string)$row['kelas'] : null;

  $role = isset($row['role']) ? trim((string)$row['role']) : '';
  if ($role === '' || $role === '-' || $role === 'null') {
    $role = ($kelas !== null && trim($kelas) !== '' && trim($kelas) !== '-') ? 'siswa' : '';
  }

  $ok = false;

  $hash = (string)($row['password_hash'] ?? '');
  if ($hash !== '') {
    $ok = password_verify($password, $hash);
  } else {
    $plain = (string)($row['password'] ?? '');
    if ($plain !== '' && hash_equals($plain, $password)) {
      $ok = true;

      // cek kolom password_hash aman
      $desc = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
      $cols = array_column($desc, 'Field');

      if (in_array('password_hash', $cols, true)) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $up = $pdo->prepare("UPDATE users SET password_hash=:h WHERE school_id=:sid AND username=:u");
        $up->execute([":h" => $newHash, ":sid" => $sid, ":u" => $username]);
      }
    }
  }

  if (!$ok) back_with_error("Password salah.");

  session_regenerate_id(true);

  $_SESSION['login'] = true;
  $_SESSION['role']  = $role ?: 'siswa';
  $_SESSION['user']  = [
    "id"       => $row['id'] ?? null,
    "username" => $row['username'] ?? $username,
    "name"     => $name,
    "role"     => $role ?: 'siswa',
    "kelas"    => $kelas,
    "school_id"=> $sid,
  ];

  header("Location: /");
  exit;

} catch (Throwable $e) {
  back_with_error("Server error: " . $e->getMessage());
}
