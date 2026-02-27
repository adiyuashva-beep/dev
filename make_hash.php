<?php
// Utility untuk generate hash password.
// Demi keamanan: hanya bisa dipakai admin/super yang sedang login.

require __DIR__ . '/auth/guard.php';
require_login(['admin','super']);

$pass = $_GET['p'] ?? '';
if ($pass === '') {
  header('Content-Type: text/plain; charset=utf-8');
  echo "Tambahkan parameter ?p=PASSWORD";
  exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo password_hash($pass, PASSWORD_DEFAULT);
